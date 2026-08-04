<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Payment;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayablesController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly AccountingPostingService $posting) {}

    public function invoices(Request $request): JsonResponse
    {
        $query = PurchaseInvoice::query()->where('tenant_id', $request->user()->tenant_id)->with(['purchaseOrder', 'purchaseOrder.supplier'])->latest('invoice_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'uuid'], 'purchase_order_id' => ['required', 'uuid'], 'supplier_invoice_number' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'], 'due_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:invoice_date'],
            'subtotal' => ['required', 'numeric', 'min:0'], 'tax_total' => ['required', 'numeric', 'min:0'], 'total' => ['required', 'numeric', 'min:0'], 'match_notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $tenantId = $request->user()->tenant_id;
        $po = PurchaseOrder::query()->where('tenant_id', $tenantId)->whereKey($data['purchase_order_id'])->with(['lines', 'supplier'])->firstOrFail();
        abort_unless($po->company_id === $data['company_id'], 422, 'Invoice company tidak sesuai dengan PO.');
        abort_unless(in_array($po->status, ['approved', 'partially_received', 'fully_received'], true), 422, 'PO belum dapat ditagihkan.');
        $received = GoodsReceipt::query()->where('purchase_order_id', $po->id)->where('status', 'posted')->exists();
        abort_unless($received, 422, 'Invoice tidak boleh dibuat sebelum receiving selesai dan diposting.');
        abort_unless(abs((float) $data['total'] - (float) $po->total) < 0.01, 422, 'Invoice total tidak sesuai PO.');
        $invoice = DB::transaction(function () use ($data, $request, $tenantId, $po): PurchaseInvoice {
            $invoice = PurchaseInvoice::create([...$data, 'tenant_id' => $tenantId, 'supplier_id' => $po->supplier_id, 'number' => $this->nextNumber($data['company_id'], 'PI'), 'status' => 'matched']);
            $this->posting->post($invoice, 'Purchase invoice', $invoice->invoice_date->toDateString(), [['account' => 'inventory', 'type' => 'asset', 'debit' => $invoice->subtotal, 'credit' => 0], ['account' => 'input_tax', 'type' => 'asset', 'debit' => $invoice->tax_total, 'credit' => 0], ['account' => 'accounts_payable', 'type' => 'liability', 'debit' => 0, 'credit' => $invoice->total]]);
            $this->audit->record($request, 'matched', $invoice);

            return $invoice;
        });

        return response()->json(['data' => $invoice->load('purchaseOrder')], 201);
    }

    public function payments(Request $request): JsonResponse
    {
        $query = Payment::query()->where('tenant_id', $request->user()->tenant_id)->with('allocations')->latest('payment_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storePayment(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'uuid'], 'payment_date' => ['required', 'date'], 'method' => ['required', 'in:bank,cash,petty_cash'], 'amount' => ['required', 'numeric', 'gt:0'], 'notes' => ['sometimes', 'nullable', 'string'],
            'allocations' => ['required', 'array', 'min:1'], 'allocations.*.purchase_invoice_id' => ['required', 'uuid'], 'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ]);
        $tenantId = $request->user()->tenant_id;
        $allocationTotal = collect($data['allocations'])->sum('amount');
        abort_unless(abs($allocationTotal - (float) $data['amount']) < 0.01, 422, 'Total allocation harus sama dengan payment amount.');
        $invoiceIds = collect($data['allocations'])->pluck('purchase_invoice_id');
        $invoices = PurchaseInvoice::query()->where('tenant_id', $tenantId)->where('company_id', $data['company_id'])->whereIn('id', $invoiceIds)->whereIn('status', ['matched', 'partially_paid'])->get();
        abort_unless($invoices->count() === $invoiceIds->unique()->count(), 422, 'Invoice payment tidak valid atau sudah dibayar.');
        foreach ($data['allocations'] as $allocation) {
            $invoice = $invoices->firstWhere('id', $allocation['purchase_invoice_id']);
            $allocated = (float) $invoice->allocations()->sum('amount');
            abort_unless($allocated + (float) $allocation['amount'] <= (float) $invoice->total, 422, 'Allocation melebihi nilai invoice.');
        }
        $payment = DB::transaction(function () use ($data, $request, $tenantId): Payment {
            $payment = Payment::create(['tenant_id' => $tenantId, 'company_id' => $data['company_id'], 'number' => $this->nextNumber($data['company_id'], 'PAY'), 'payment_type' => 'outgoing', 'method' => $data['method'], 'payment_date' => $data['payment_date'], 'amount' => $data['amount'], 'status' => 'posted', 'notes' => $data['notes'] ?? null]);
            $payment->allocations()->createMany($data['allocations']);
            foreach ($data['allocations'] as $allocation) {
                $invoice = PurchaseInvoice::findOrFail($allocation['purchase_invoice_id']);
                $allocated = (float) $invoice->allocations()->sum('amount');
                $invoice->update(['status' => $allocated >= (float) $invoice->total ? 'paid' : 'partially_paid']);
            }
            $this->posting->post($payment, 'Supplier payment', $payment->payment_date->toDateString(), [['account' => 'accounts_payable', 'type' => 'liability', 'debit' => $payment->amount, 'credit' => 0], ['account' => 'bank', 'type' => 'asset', 'debit' => 0, 'credit' => $payment->amount]]);
            $this->audit->record($request, 'posted', $payment);

            return $payment;
        });

        return response()->json(['data' => $payment->load('allocations')], 201);
    }

    private function nextNumber(string $companyId, string $prefix): string
    {
        $model = $prefix === 'PI' ? PurchaseInvoice::class : Payment::class;
        $sequence = $model::query()->where('company_id', $companyId)->lockForUpdate()->count() + 1;
        do {
            $number = sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $sequence++);
        } while ($model::query()->where('company_id', $companyId)->where('number', $number)->exists());

        return $number;
    }
}
