<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use App\Models\CustomerReceipt;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\TaxCode;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivablesController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly AccountingPostingService $posting) {}

    public function storeInvoiceTax(Request $request, string $invoiceId): JsonResponse
    {
        $data = $request->validate(['tax_lines' => ['required', 'array', 'min:1'], 'tax_lines.*.tax_code_id' => ['required', 'uuid'], 'tax_lines.*.taxable_amount' => ['required', 'numeric', 'gte:0']]);
        $invoice = SalesInvoice::where('tenant_id', $request->user()->tenant_id)->whereKey($invoiceId)->firstOrFail();
        $codes = TaxCode::where('tenant_id', $invoice->tenant_id)->whereIn('id', collect($data['tax_lines'])->pluck('tax_code_id'))->where('status', 'active')->get()->keyBy('id');
        abort_unless($codes->count() === collect($data['tax_lines'])->pluck('tax_code_id')->unique()->count(), 422, 'Tax code tidak valid.');
        $total = collect($data['tax_lines'])->sum(fn ($line) => $line['taxable_amount'] * ((float) $codes[$line['tax_code_id']]->rate / 100));
        abort_unless(abs($total - (float) $invoice->tax_total) < 0.01, 422, 'Tax snapshot tidak sama dengan tax invoice.');
        $invoice->taxLines()->delete();
        $invoice->taxLines()->createMany(collect($data['tax_lines'])->map(fn ($line) => ['tax_code_id' => $line['tax_code_id'], 'taxable_amount' => $line['taxable_amount'], 'rate' => $codes[$line['tax_code_id']]->rate, 'tax_amount' => $line['taxable_amount'] * ((float) $codes[$line['tax_code_id']]->rate / 100)])->all());
        $this->audit->record($request, 'tax_snapshotted', $invoice);

        return response()->json(['data' => $invoice->load('taxLines')]);
    }

    public function storeReceipt(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'customer_id' => ['required', 'uuid'], 'receipt_date' => ['required', 'date'], 'method' => ['required', 'in:bank,cash,petty_cash'], 'amount' => ['required', 'numeric', 'gt:0'], 'allocations' => ['required', 'array', 'min:1'], 'allocations.*.sales_invoice_id' => ['required', 'uuid'], 'allocations.*.amount' => ['required', 'numeric', 'gt:0']]);
        $tenant = $request->user()->tenant_id;
        $ids = collect($data['allocations'])->pluck('sales_invoice_id');
        abort_unless(abs(collect($data['allocations'])->sum('amount') - (float) $data['amount']) < 0.01, 422, 'Allocation receipt harus sama dengan amount.');
        $customer = Party::where('tenant_id', $tenant)->whereKey($data['customer_id'])->whereIn('type', ['customer', 'both'])->where('status', 'active')->firstOrFail();
        $invoices = SalesInvoice::where('tenant_id', $tenant)->where('company_id', $data['company_id'])->where('customer_id', $customer->id)->whereIn('id', $ids)->whereIn('status', ['posted', 'partially_paid'])->get();
        abort_unless($invoices->count() === $ids->unique()->count(), 422, 'Invoice AR tidak valid.');
        foreach ($data['allocations'] as $line) {
            $invoice = $invoices->firstWhere('id', $line['sales_invoice_id']);
            $allocated = (float) CustomerReceipt::whereHas('allocations', fn ($query) => $query->where('sales_invoice_id', $invoice->id))->with('allocations')->get()->flatMap->allocations->sum('amount');
            abort_unless($allocated + (float) $line['amount'] <= (float) $invoice->total, 422, 'Receipt allocation melebihi invoice.');
        }
        $receipt = DB::transaction(function () use ($data, $request, $tenant, $customer): CustomerReceipt {
            $receipt = CustomerReceipt::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'customer_id' => $customer->id, 'number' => $this->number(CustomerReceipt::class, 'REC', $data['company_id']), 'method' => $data['method'], 'receipt_date' => $data['receipt_date'], 'amount' => $data['amount'], 'status' => 'posted']);
            $receipt->allocations()->createMany($data['allocations']);
            foreach ($data['allocations'] as $allocation) {
                $invoice = SalesInvoice::findOrFail($allocation['sales_invoice_id']);
                $allocated = (float) CustomerReceipt::whereHas('allocations', fn ($query) => $query->where('sales_invoice_id', $invoice->id))->with('allocations')->get()->flatMap->allocations->sum('amount');
                $invoice->update(['status' => $allocated >= (float) $invoice->total ? 'paid' : 'partially_paid']);
            }
            $this->posting->post($receipt, 'Customer receipt', $receipt->receipt_date->toDateString(), [['account' => 'bank', 'type' => 'asset', 'debit' => $receipt->amount, 'credit' => 0], ['account' => 'accounts_receivable', 'type' => 'asset', 'debit' => 0, 'credit' => $receipt->amount]]);
            $this->audit->record($request, 'posted', $receipt);

            return $receipt;
        });

        return response()->json(['data' => $receipt->load('allocations')], 201);
    }

    public function storeReturn(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'branch_id' => ['required', 'uuid'], 'warehouse_id' => ['required', 'uuid'], 'sales_order_id' => ['required', 'uuid'], 'sales_invoice_id' => ['sometimes', 'nullable', 'uuid'], 'return_date' => ['required', 'date'], 'reason' => ['required', 'string'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.sales_order_line_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0']]);
        $tenant = $request->user()->tenant_id;
        $order = SalesOrder::where('tenant_id', $tenant)->whereKey($data['sales_order_id'])->with('lines')->firstOrFail();
        $warehouse = Warehouse::whereKey($data['warehouse_id'])->whereHas('branch', fn ($q) => $q->where('company_id', $data['company_id']))->where('status', 'active')->firstOrFail();
        foreach ($data['lines'] as $line) {
            $orderLine = $order->lines->firstWhere('id', $line['sales_order_line_id']);
            $returned = (float) SalesReturn::where('sales_order_id', $order->id)->with('lines')->get()->flatMap->lines->where('sales_order_line_id', $line['sales_order_line_id'])->sum('quantity');
            abort_unless($orderLine && $returned + (float) $line['quantity'] <= (float) $orderLine->delivered_quantity, 422, 'Return melebihi delivery yang tersisa.');
        }
        $return = DB::transaction(function () use ($data, $request, $tenant, $order, $warehouse): SalesReturn {
            $return = SalesReturn::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'branch_id' => $data['branch_id'], 'warehouse_id' => $warehouse->id, 'sales_order_id' => $order->id, 'sales_invoice_id' => $data['sales_invoice_id'] ?? null, 'number' => $this->number(SalesReturn::class, 'RET', $data['company_id']), 'return_date' => $data['return_date'], 'status' => 'posted', 'reason' => $data['reason']]);
            foreach ($data['lines'] as $line) {
                $orderLine = $order->lines->firstWhere('id', $line['sales_order_line_id']);
                $return->lines()->create(['sales_order_line_id' => $orderLine->id, 'quantity' => $line['quantity'], 'unit_price' => $orderLine->unit_price, 'line_total' => $line['quantity'] * $orderLine->unit_price]);
                $balance = StockBalance::where('warehouse_id', $warehouse->id)->where('item_id', $orderLine->item_id)->lockForUpdate()->first();
                if (! $balance) {
                    $balance = StockBalance::create(['tenant_id' => $tenant, 'warehouse_id' => $warehouse->id, 'item_id' => $orderLine->item_id, 'on_hand' => 0, 'reserved' => 0, 'average_cost' => $orderLine->unit_price]);
                }
                $balance->increment('on_hand', $line['quantity']);
                StockMovement::create(['tenant_id' => $tenant, 'company_id' => $order->company_id, 'warehouse_id' => $warehouse->id, 'item_id' => $orderLine->item_id, 'unit_id' => $orderLine->unit_id, 'movement_type' => 'sales_return', 'direction' => 'in', 'quantity' => $line['quantity'], 'unit_cost' => $orderLine->unit_price, 'source_type' => SalesReturn::class, 'source_id' => $return->id, 'occurred_at' => now()]);
            }
            $this->audit->record($request, 'posted', $return);

            return $return;
        });

        return response()->json(['data' => $return->load('lines')], 201);
    }

    public function storeCreditNote(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'sales_invoice_id' => ['required', 'uuid'], 'sales_return_id' => ['required', 'uuid'], 'credit_date' => ['required', 'date'], 'reason' => ['required', 'string']]);
        $tenant = $request->user()->tenant_id;
        $invoice = SalesInvoice::where('tenant_id', $tenant)->whereKey($data['sales_invoice_id'])->firstOrFail();
        $return = SalesReturn::where('tenant_id', $tenant)->whereKey($data['sales_return_id'])->with('lines')->firstOrFail();
        abort_unless($return->sales_invoice_id === $invoice->id, 422, 'Return tidak terkait invoice.');
        $subtotal = $return->lines->sum('line_total');
        $credited = (float) CreditNote::where('sales_invoice_id', $invoice->id)->sum('subtotal');
        abort_unless($credited + $subtotal <= (float) $invoice->subtotal, 422, 'Credit note melebihi invoice yang tersisa.');
        $note = CreditNote::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'customer_id' => $invoice->customer_id, 'sales_invoice_id' => $invoice->id, 'sales_return_id' => $return->id, 'number' => $this->number(CreditNote::class, 'CN', $data['company_id']), 'credit_date' => $data['credit_date'], 'subtotal' => $subtotal, 'tax_total' => 0, 'total' => $subtotal, 'status' => 'posted', 'reason' => $data['reason']]);
        $this->audit->record($request, 'posted', $note);

        return response()->json(['data' => $note], 201);
    }

    private function number(string $model, string $prefix, string $company): string
    {
        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $model::where('company_id', $company)->lockForUpdate()->count() + 1);
    }
}
