<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\QuotationComparison;
use App\Models\Rfq;
use App\Models\SupplierQuotation;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasingController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function rfqs(Request $request): JsonResponse
    {
        $query = Rfq::query()->with(['lines.item', 'lines.unit', 'suppliers.supplier', 'purchaseRequest'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest('request_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeRfq(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'uuid'], 'branch_id' => ['required', 'uuid'], 'purchase_request_id' => ['sometimes', 'nullable', 'uuid'],
            'request_date' => ['required', 'date'], 'quotation_deadline' => ['sometimes', 'nullable', 'date', 'after_or_equal:request_date'],
            'notes' => ['sometimes', 'nullable', 'string'], 'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'uuid'], 'lines.*.unit_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.notes' => ['sometimes', 'nullable', 'string'], 'supplier_ids' => ['required', 'array', 'min:1'], 'supplier_ids.*' => ['required', 'uuid'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $company = Company::query()->where('tenant_id', $tenantId)->whereKey($data['company_id'])->where('status', 'active')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->whereKey($data['branch_id'])->where('status', 'active')->firstOrFail();
        $this->assertMasterReferences($tenantId, $data['lines'], $data['supplier_ids']);
        $purchaseRequest = null;

        if (! empty($data['purchase_request_id'])) {
            $purchaseRequest = PurchaseRequest::query()->where('tenant_id', $tenantId)->whereKey($data['purchase_request_id'])->whereIn('status', ['submitted', 'approved'])->with('lines')->firstOrFail();
            abort_unless($purchaseRequest->company_id === $company->id && $purchaseRequest->branch_id === $branch->id, 422, 'PR dan RFQ harus berada pada company dan branch yang sama.');
        }

        $rfq = DB::transaction(function () use ($data, $request, $tenantId, $company, $branch, $purchaseRequest): Rfq {
            $rfq = Rfq::create([
                'tenant_id' => $tenantId, 'company_id' => $company->id, 'branch_id' => $branch->id, 'purchase_request_id' => $purchaseRequest?->id,
                'number' => $this->nextNumber(Rfq::class, 'RFQ', $company->id), 'request_date' => $data['request_date'], 'quotation_deadline' => $data['quotation_deadline'] ?? null,
                'status' => 'draft', 'notes' => $data['notes'] ?? null,
            ]);
            $rfq->lines()->createMany($data['lines']);
            foreach ($data['supplier_ids'] as $supplierId) {
                $rfq->suppliers()->create(['supplier_id' => $supplierId, 'status' => 'invited']);
            }
            $this->audit->record($request, 'created', $rfq);

            return $rfq;
        });

        return response()->json(['data' => $rfq->load(['lines.item', 'lines.unit', 'suppliers.supplier'])], 201);
    }

    public function submitRfq(Request $request, string $id): JsonResponse
    {
        $rfq = Rfq::query()->where('tenant_id', $request->user()->tenant_id)->with(['lines', 'suppliers'])->findOrFail($id);
        abort_unless($rfq->status === 'draft', 422, 'Hanya RFQ draft yang dapat disubmit.');
        abort_unless($rfq->lines->isNotEmpty() && $rfq->suppliers->isNotEmpty(), 422, 'RFQ harus memiliki line dan supplier.');
        $before = $rfq->toArray();
        DB::transaction(function () use ($rfq, $request, $before): void {
            $rfq->update(['status' => 'sent']);
            $rfq->suppliers()->update(['status' => 'sent', 'sent_at' => now()]);
            $this->audit->record($request, 'submitted', $rfq, $before);
        });

        return response()->json(['data' => $rfq->fresh()->load(['lines', 'suppliers.supplier'])]);
    }

    public function showRfq(Request $request, string $id): JsonResponse
    {
        $rfq = Rfq::query()->where('tenant_id', $request->user()->tenant_id)->with(['lines.item', 'lines.unit', 'suppliers.supplier', 'purchaseRequest'])->findOrFail($id);

        return response()->json(['data' => $rfq]);
    }

    public function updateRfq(Request $request, string $id): JsonResponse
    {
        $rfq = Rfq::query()->where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        abort_unless($rfq->status === 'draft', 422, 'Hanya RFQ draft yang dapat diedit.');
        $data = $request->validate([
            'request_date' => ['sometimes', 'date'],
            'quotation_deadline' => ['sometimes', 'nullable', 'date', 'after_or_equal:request_date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $before = $rfq->toArray();
        $rfq->update($data);
        $this->audit->record($request, 'updated', $rfq, $before);

        return response()->json(['data' => $rfq->fresh()->load(['lines.item', 'lines.unit', 'suppliers.supplier'])]);
    }

    public function quotations(Request $request): JsonResponse
    {
        $query = SupplierQuotation::query()->with(['supplier', 'lines.rfqLine'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('rfq_id'), fn (Builder $query) => $query->where('rfq_id', $request->string('rfq_id')))
            ->latest('quotation_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeQuotation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'uuid'], 'rfq_id' => ['required', 'uuid'], 'supplier_id' => ['required', 'uuid'], 'quotation_date' => ['required', 'date'],
            'valid_until' => ['sometimes', 'nullable', 'date', 'after_or_equal:quotation_date'], 'currency' => ['sometimes', 'string', 'size:3'], 'payment_days' => ['sometimes', 'integer', 'min:0'], 'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'], 'lines.*.rfq_line_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'], 'lines.*.unit_price' => ['required', 'numeric', 'min:0'], 'lines.*.tax_rate' => ['sometimes', 'numeric', 'between:0,100'], 'lines.*.promised_date' => ['sometimes', 'nullable', 'date'],
        ]);
        $tenantId = $request->user()->tenant_id;
        $rfq = Rfq::query()->where('tenant_id', $tenantId)->whereKey($data['rfq_id'])->with(['lines', 'suppliers'])->firstOrFail();
        abort_unless($rfq->status === 'sent', 422, 'Quotation hanya dapat dibuat untuk RFQ yang sudah dikirim.');
        abort_unless($rfq->suppliers->contains('supplier_id', $data['supplier_id']), 422, 'Supplier tidak terdaftar pada RFQ.');
        abort_unless($this->lineIdsBelongToRfq($rfq, collect($data['lines'])->pluck('rfq_line_id')), 422, 'RFQ line tidak sesuai dengan RFQ.');
        $supplier = Party::query()->where('tenant_id', $tenantId)->whereKey($data['supplier_id'])->where('type', '!=', 'customer')->where('status', 'active')->firstOrFail();
        $subtotal = collect($data['lines'])->sum(fn (array $line) => $line['quantity'] * $line['unit_price']);
        $taxTotal = collect($data['lines'])->sum(fn (array $line) => $line['quantity'] * $line['unit_price'] * (($line['tax_rate'] ?? 0) / 100));
        $quotation = DB::transaction(function () use ($data, $request, $tenantId, $rfq, $supplier, $subtotal, $taxTotal): SupplierQuotation {
            $quotation = SupplierQuotation::create([
                'tenant_id' => $tenantId, 'company_id' => $data['company_id'], 'rfq_id' => $rfq->id, 'supplier_id' => $supplier->id,
                'number' => $this->nextNumber(SupplierQuotation::class, 'SQ', $data['company_id']), 'currency' => $data['currency'] ?? 'IDR', 'quotation_date' => $data['quotation_date'], 'valid_until' => $data['valid_until'] ?? null,
                'payment_days' => $data['payment_days'] ?? 0, 'subtotal' => $subtotal, 'tax_total' => $taxTotal, 'total' => $subtotal + $taxTotal, 'status' => 'submitted', 'notes' => $data['notes'] ?? null,
            ]);
            $quotation->lines()->createMany(collect($data['lines'])->map(fn (array $line) => [...$line, 'line_total' => ($line['quantity'] * $line['unit_price']) * (1 + (($line['tax_rate'] ?? 0) / 100))])->all());
            $this->audit->record($request, 'submitted', $quotation);

            return $quotation;
        });

        return response()->json(['data' => $quotation->load(['supplier', 'lines.rfqLine'])], 201);
    }

    public function compareQuotations(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'rfq_id' => ['required', 'uuid'], 'selected_quotation_id' => ['required', 'uuid'], 'decision_notes' => ['required', 'string'], 'quotation_ids' => ['required', 'array', 'min:1'], 'quotation_ids.*' => ['required', 'uuid']]);
        $tenantId = $request->user()->tenant_id;
        $rfq = Rfq::query()->where('tenant_id', $tenantId)->whereKey($data['rfq_id'])->firstOrFail();
        $quotations = SupplierQuotation::query()->where('tenant_id', $tenantId)->where('rfq_id', $rfq->id)->whereIn('id', $data['quotation_ids'])->where('status', 'submitted')->get();
        abort_unless($quotations->count() === count(array_unique($data['quotation_ids'])), 422, 'Quotation comparison memiliki quotation tidak valid.');
        $selected = $quotations->firstWhere('id', $data['selected_quotation_id']);
        abort_unless($selected, 422, 'Quotation terpilih harus termasuk dalam comparison.');
        $comparison = DB::transaction(function () use ($data, $request, $tenantId, $quotations): QuotationComparison {
            $comparison = QuotationComparison::create(['tenant_id' => $tenantId, 'company_id' => $data['company_id'], 'rfq_id' => $data['rfq_id'], 'number' => $this->nextNumber(QuotationComparison::class, 'CMP', $data['company_id']), 'status' => 'approved', 'selected_quotation_id' => $data['selected_quotation_id'], 'decision_notes' => $data['decision_notes']]);
            $comparison->lines()->createMany($quotations->map(fn (SupplierQuotation $quotation) => ['supplier_quotation_id' => $quotation->id, 'evaluated_total' => $quotation->total])->all());
            $this->audit->record($request, 'approved', $comparison);

            return $comparison;
        });

        return response()->json(['data' => $comparison->load('lines')], 201);
    }

    public function purchaseOrders(Request $request): JsonResponse
    {
        $query = PurchaseOrder::query()->with(['lines', 'supplier'])->where('tenant_id', $request->user()->tenant_id)->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))->latest('order_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storePurchaseOrder(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'branch_id' => ['required', 'uuid'], 'supplier_quotation_id' => ['required', 'uuid'], 'order_date' => ['required', 'date'], 'expected_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:order_date'], 'notes' => ['sometimes', 'nullable', 'string']]);
        $tenantId = $request->user()->tenant_id;
        $quotation = SupplierQuotation::query()->where('tenant_id', $tenantId)->whereKey($data['supplier_quotation_id'])->with(['lines.rfqLine', 'rfq'])->firstOrFail();
        $comparison = QuotationComparison::query()->where('tenant_id', $tenantId)->where('rfq_id', $quotation->rfq_id)->where('selected_quotation_id', $quotation->id)->where('status', 'approved')->first();
        abort_unless($comparison, 422, 'Supplier quotation harus dipilih dalam comparison yang approved.');
        $company = Company::query()->where('tenant_id', $tenantId)->whereKey($data['company_id'])->where('status', 'active')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->whereKey($data['branch_id'])->where('status', 'active')->firstOrFail();
        abort_unless($quotation->company_id === $company->id && $quotation->rfq->branch_id === $branch->id, 422, 'Company atau branch PO tidak sesuai dengan quotation.');
        $purchaseOrder = DB::transaction(function () use ($data, $request, $tenantId, $quotation, $company, $branch): PurchaseOrder {
            $purchaseOrder = PurchaseOrder::create(['tenant_id' => $tenantId, 'company_id' => $company->id, 'branch_id' => $branch->id, 'supplier_id' => $quotation->supplier_id, 'purchase_request_id' => $quotation->rfq->purchase_request_id, 'supplier_quotation_id' => $quotation->id, 'number' => $this->nextNumber(PurchaseOrder::class, 'PO', $company->id), 'currency' => $quotation->currency, 'order_date' => $data['order_date'], 'expected_date' => $data['expected_date'] ?? null, 'payment_days' => $quotation->payment_days, 'subtotal' => $quotation->subtotal, 'tax_total' => $quotation->tax_total, 'total' => $quotation->total, 'status' => 'approved', 'notes' => $data['notes'] ?? null]);
            $purchaseOrder->lines()->createMany($quotation->lines->map(fn ($line) => ['item_id' => $line->rfqLine->item_id, 'unit_id' => $line->rfqLine->unit_id, 'quantity' => $line->quantity, 'unit_price' => $line->unit_price, 'tax_rate' => $line->tax_rate, 'line_total' => $line->line_total])->all());
            $this->audit->record($request, 'approved', $purchaseOrder);

            return $purchaseOrder;
        });

        return response()->json(['data' => $purchaseOrder->load(['lines', 'supplier'])], 201);
    }

    private function assertMasterReferences(string $tenantId, array $lines, array $supplierIds): void
    {
        $itemIds = collect($lines)->pluck('item_id')->unique();
        $unitIds = collect($lines)->pluck('unit_id')->unique();
        abort_unless(Item::query()->where('tenant_id', $tenantId)->whereIn('id', $itemIds)->where('status', 'active')->count() === $itemIds->count(), 422, 'Item tidak valid atau tidak aktif.');
        abort_unless(Unit::query()->where('tenant_id', $tenantId)->whereIn('id', $unitIds)->where('status', 'active')->count() === $unitIds->count(), 422, 'Unit tidak valid atau tidak aktif.');
        abort_unless(Party::query()->where('tenant_id', $tenantId)->whereIn('id', $supplierIds)->where('type', '!=', 'customer')->where('status', 'active')->count() === count(array_unique($supplierIds)), 422, 'Supplier tidak valid atau tidak aktif.');
    }

    private function lineIdsBelongToRfq(Rfq $rfq, $lineIds): bool
    {
        return $rfq->lines()->whereIn('id', $lineIds)->count() === $lineIds->count();
    }

    private function nextNumber(string $model, string $prefix, string $companyId): string
    {
        $sequence = $model::query()->where('company_id', $companyId)->lockForUpdate()->count() + 1;
        do {
            $number = sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $sequence++);
        } while ($model::query()->where('company_id', $companyId)->where('number', $number)->exists());

        return $number;
    }
}
