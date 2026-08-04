<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GoodsReceipt;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\QualityCheck;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReceivingController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly AccountingPostingService $posting) {}

    public function index(Request $request): JsonResponse
    {
        $query = GoodsReceipt::query()->where('tenant_id', $request->user()->tenant_id)->with(['lines.purchaseOrderLine', 'purchaseOrder'])->latest('receipt_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'purchase_order_id' => ['required', 'uuid'], 'warehouse_id' => ['required', 'uuid'], 'receipt_date' => ['required', 'date'], 'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'], 'lines.*.purchase_order_line_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.lot_number' => ['sometimes', 'nullable', 'string', 'max:100'], 'lines.*.expiry_date' => ['sometimes', 'nullable', 'date'],
        ]);
        $tenantId = $request->user()->tenant_id;
        $purchaseOrder = PurchaseOrder::query()->where('tenant_id', $tenantId)->whereKey($data['purchase_order_id'])->with('lines')->firstOrFail();
        abort_unless(in_array($purchaseOrder->status, ['approved', 'partially_received'], true), 422, 'PO belum dapat diterima.');
        $warehouse = Warehouse::query()->whereHas('branch', fn (Builder $query) => $query->where('company_id', $purchaseOrder->company_id))->whereKey($data['warehouse_id'])->where('status', 'active')->firstOrFail();
        $lineIds = collect($data['lines'])->pluck('purchase_order_line_id');
        abort_unless($purchaseOrder->lines->whereIn('id', $lineIds)->count() === $lineIds->count(), 422, 'PO line tidak sesuai dengan PO.');

        foreach ($data['lines'] as $line) {
            $poLine = $purchaseOrder->lines->firstWhere('id', $line['purchase_order_line_id']);
            abort_unless((float) $poLine->received_quantity + (float) $line['quantity'] <= (float) $poLine->quantity, 422, 'Quantity receipt melebihi quantity PO.');
        }

        $receipt = DB::transaction(function () use ($data, $request, $tenantId, $purchaseOrder, $warehouse): GoodsReceipt {
            $receipt = GoodsReceipt::create([
                'tenant_id' => $tenantId, 'company_id' => $purchaseOrder->company_id, 'branch_id' => $purchaseOrder->branch_id, 'warehouse_id' => $warehouse->id,
                'purchase_order_id' => $purchaseOrder->id, 'number' => $this->nextNumber($purchaseOrder->company_id), 'receipt_date' => $data['receipt_date'], 'status' => 'received', 'notes' => $data['notes'] ?? null,
            ]);
            foreach ($data['lines'] as $line) {
                $receiptLine = $receipt->lines()->create([...$line, 'accepted_quantity' => 0, 'rejected_quantity' => 0]);
                QualityCheck::create(['tenant_id' => $tenantId, 'goods_receipt_line_id' => $receiptLine->id, 'result' => 'pending']);
            }
            $this->audit->record($request, 'received', $receipt);

            return $receipt;
        });

        return response()->json(['data' => $receipt->load(['lines.purchaseOrderLine', 'purchaseOrder'])], 201);
    }

    public function qualityCheck(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['result' => ['required', 'in:passed,failed,partial'], 'reason' => ['sometimes', 'nullable', 'string'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.goods_receipt_line_id' => ['required', 'uuid'], 'lines.*.accepted_quantity' => ['required', 'numeric', 'gte:0'], 'lines.*.rejected_quantity' => ['required', 'numeric', 'gte:0']]);
        $receipt = GoodsReceipt::query()->where('tenant_id', $request->user()->tenant_id)->with('lines')->findOrFail($id);
        abort_unless($receipt->status === 'received', 422, 'Receipt tidak berada pada status QC.');
        $lineIds = collect($data['lines'])->pluck('goods_receipt_line_id');
        abort_unless($receipt->lines->count() === $lineIds->unique()->count() && $receipt->lines->whereIn('id', $lineIds)->count() === $lineIds->count(), 422, 'Semua receipt line wajib memiliki hasil QC.');
        foreach ($data['lines'] as $qcLine) {
            $line = $receipt->lines->firstWhere('id', $qcLine['goods_receipt_line_id']);
            abort_unless(abs(((float) $qcLine['accepted_quantity'] + (float) $qcLine['rejected_quantity']) - (float) $line->quantity) < 0.000001, 422, 'Accepted dan rejected quantity harus sama dengan quantity receipt.');
        }
        DB::transaction(function () use ($data, $request, $receipt): void {
            foreach ($data['lines'] as $qcLine) {
                $line = $receipt->lines->firstWhere('id', $qcLine['goods_receipt_line_id']);
                $accepted = $qcLine['accepted_quantity'];
                $rejected = $qcLine['rejected_quantity'];
                $line->update(['accepted_quantity' => $accepted, 'rejected_quantity' => $rejected]);
                QualityCheck::query()->where('goods_receipt_line_id', $line->id)->update(['result' => $data['result'], 'accepted_quantity' => $accepted, 'rejected_quantity' => $rejected, 'reason' => $data['reason'] ?? null, 'checked_by' => $request->user()->id, 'checked_at' => now()]);
            }
            $receipt->update(['status' => 'qc_completed']);
            $this->audit->record($request, 'quality_checked', $receipt);
        });

        return response()->json(['data' => $receipt->fresh()->load('lines')]);
    }

    public function post(Request $request, string $id): JsonResponse
    {
        $receipt = GoodsReceipt::query()->where('tenant_id', $request->user()->tenant_id)->with(['lines.purchaseOrderLine', 'purchaseOrder'])->findOrFail($id);
        abort_unless($receipt->status === 'qc_completed', 422, 'QC harus selesai sebelum posting stock.');
        $receipt = DB::transaction(function () use ($receipt, $request): GoodsReceipt {
            foreach ($receipt->lines as $line) {
                if ((float) $line->accepted_quantity <= 0) {
                    continue;
                }
                $poLine = $line->purchaseOrderLine;
                $item = Item::findOrFail($poLine->item_id);
                $movement = StockMovement::create([
                    'tenant_id' => $receipt->tenant_id, 'company_id' => $receipt->company_id, 'warehouse_id' => $receipt->warehouse_id, 'item_id' => $poLine->item_id, 'unit_id' => $poLine->unit_id,
                    'movement_type' => 'purchase_receipt', 'direction' => 'in', 'quantity' => $line->accepted_quantity, 'unit_cost' => $poLine->unit_price, 'source_type' => GoodsReceipt::class, 'source_id' => $receipt->id,
                    'lot_number' => $line->lot_number, 'expiry_date' => $line->expiry_date, 'occurred_at' => now(),
                ]);
                $balance = StockBalance::query()->where('warehouse_id', $receipt->warehouse_id)->where('item_id', $poLine->item_id)->lockForUpdate()->first();
                if (! $balance) {
                    $balance = StockBalance::create(['tenant_id' => $receipt->tenant_id, 'warehouse_id' => $receipt->warehouse_id, 'item_id' => $poLine->item_id, 'on_hand' => 0, 'reserved' => 0, 'average_cost' => 0]);
                }
                $oldQty = (float) $balance->on_hand;
                $newQty = $oldQty + (float) $movement->quantity;
                $newCost = $newQty > 0 ? (($oldQty * (float) $balance->average_cost) + ((float) $movement->quantity * (float) $movement->unit_cost)) / $newQty : 0;
                $balance->update(['on_hand' => $newQty, 'average_cost' => $newCost]);
                $poLine->increment('received_quantity', $line->quantity);
            }
            $receipt->update(['status' => 'posted']);
            $total = $receipt->lines->sum(fn ($line) => (float) $line->accepted_quantity * (float) $line->purchaseOrderLine->unit_price);
            $this->posting->post($receipt, 'Goods receipt', $receipt->receipt_date->toDateString(), [['account' => 'inventory', 'type' => 'asset', 'debit' => $total, 'credit' => 0], ['account' => 'grni', 'type' => 'liability', 'debit' => 0, 'credit' => $total]]);
            $this->audit->record($request, 'posted', $receipt);

            return $receipt;
        });

        return response()->json(['data' => $receipt->fresh()->load(['lines', 'purchaseOrder'])]);
    }

    public function stock(Request $request): JsonResponse
    {
        $query = StockBalance::query()->where('tenant_id', $request->user()->tenant_id)->when($request->filled('warehouse_id'), fn (Builder $query) => $query->where('warehouse_id', $request->string('warehouse_id')))->when($request->filled('item_id'), fn (Builder $query) => $query->where('item_id', $request->string('item_id')));

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    private function nextNumber(string $companyId): string
    {
        $sequence = GoodsReceipt::query()->where('company_id', $companyId)->lockForUpdate()->count() + 1;
        do {
            $number = sprintf('GR-%s-%06d', now()->format('Y'), $sequence++);
        } while (GoodsReceipt::query()->where('company_id', $companyId)->where('number', $number)->exists());

        return $number;
    }
}
