<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockControlController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function transfer(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'source_warehouse_id' => ['required', 'uuid'], 'destination_warehouse_id' => ['required', 'uuid', 'different:source_warehouse_id'], 'transfer_date' => ['required', 'date'], 'reason' => ['required', 'string'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.item_id' => ['required', 'uuid'], 'lines.*.unit_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0']]);
        $tenant = $request->user()->tenant_id;
        Warehouse::whereKey($data['source_warehouse_id'])->where('status', 'active')->whereHas('branch', fn ($q) => $q->where('company_id', $data['company_id']))->firstOrFail();
        Warehouse::whereKey($data['destination_warehouse_id'])->where('status', 'active')->whereHas('branch', fn ($q) => $q->where('company_id', $data['company_id']))->firstOrFail();
        $items = Item::where('tenant_id', $tenant)->whereIn('id', collect($data['lines'])->pluck('item_id'))->where('status', 'active')->get()->keyBy('id');
        $units = Unit::where('tenant_id', $tenant)->whereIn('id', collect($data['lines'])->pluck('unit_id'))->where('status', 'active')->get();
        abort_unless($items->count() === collect($data['lines'])->pluck('item_id')->unique()->count() && $units->count() === collect($data['lines'])->pluck('unit_id')->unique()->count(), 422, 'Item atau unit tidak valid.');
        foreach ($data['lines'] as $line) {
            $balance = StockBalance::where('warehouse_id', $data['source_warehouse_id'])->where('item_id', $line['item_id'])->first();
            abort_unless($balance && (float) $balance->on_hand - (float) $balance->reserved >= (float) $line['quantity'], 422, 'Stock sumber tidak cukup.');
        }
        $transfer = DB::transaction(function () use ($data, $request, $tenant): StockTransfer {
            $t = StockTransfer::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'source_warehouse_id' => $data['source_warehouse_id'], 'destination_warehouse_id' => $data['destination_warehouse_id'], 'number' => $this->number(StockTransfer::class, 'TRF', $data['company_id']), 'transfer_date' => $data['transfer_date'], 'status' => 'posted', 'reason' => $data['reason']]);
            foreach ($data['lines'] as $line) {
                $t->lines()->create($line);
                $source = StockBalance::where('warehouse_id', $data['source_warehouse_id'])->where('item_id', $line['item_id'])->lockForUpdate()->first();
                $source->decrement('on_hand', $line['quantity']);
                $dest = StockBalance::where('warehouse_id', $data['destination_warehouse_id'])->where('item_id', $line['item_id'])->lockForUpdate()->first();
                if (! $dest) {
                    $dest = StockBalance::create(['tenant_id' => $tenant, 'warehouse_id' => $data['destination_warehouse_id'], 'item_id' => $line['item_id'], 'on_hand' => 0, 'reserved' => 0, 'average_cost' => $source->average_cost]);
                } $dest->increment('on_hand', $line['quantity']);
                foreach ([[$data['source_warehouse_id'], 'out'], [$data['destination_warehouse_id'], 'in']] as [$warehouse, $direction]) {
                    StockMovement::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'warehouse_id' => $warehouse, 'item_id' => $line['item_id'], 'unit_id' => $line['unit_id'], 'movement_type' => 'stock_transfer', 'direction' => $direction, 'quantity' => $line['quantity'], 'unit_cost' => $source->average_cost, 'source_type' => StockTransfer::class, 'source_id' => $t->id, 'occurred_at' => now()]);
                }
            } $this->audit->record($request, 'posted', $t);

            return $t;
        });

        return response()->json(['data' => $transfer->load('lines')], 201);
    }

    public function adjustment(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'warehouse_id' => ['required', 'uuid'], 'adjustment_date' => ['required', 'date'], 'reason' => ['required', 'string'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.item_id' => ['required', 'uuid'], 'lines.*.unit_id' => ['required', 'uuid'], 'lines.*.quantity_delta' => ['required', 'numeric', 'not_in:0'], 'lines.*.unit_cost' => ['sometimes', 'numeric', 'min:0']]);
        $tenant = $request->user()->tenant_id;
        Warehouse::whereKey($data['warehouse_id'])->where('status', 'active')->whereHas('branch', fn ($q) => $q->where('company_id', $data['company_id']))->firstOrFail();
        $adjustment = DB::transaction(function () use ($data, $request, $tenant): StockAdjustment {
            $a = StockAdjustment::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'warehouse_id' => $data['warehouse_id'], 'number' => $this->number(StockAdjustment::class, 'ADJ', $data['company_id']), 'adjustment_date' => $data['adjustment_date'], 'status' => 'posted', 'reason' => $data['reason']]);
            foreach ($data['lines'] as $line) {
                $a->lines()->create($line);
                $balance = StockBalance::where('warehouse_id', $data['warehouse_id'])->where('item_id', $line['item_id'])->lockForUpdate()->first();
                if (! $balance) {
                    $balance = StockBalance::create(['tenant_id' => $tenant, 'warehouse_id' => $data['warehouse_id'], 'item_id' => $line['item_id'], 'on_hand' => 0, 'reserved' => 0, 'average_cost' => $line['unit_cost'] ?? 0]);
                } $newOnHand = (float) $balance->on_hand + (float) $line['quantity_delta'];
                abort_unless($newOnHand >= 0, 422, 'Adjustment menyebabkan stock minus.');
                $balance->update(['on_hand' => $newOnHand, 'average_cost' => $line['unit_cost'] ?? $balance->average_cost]);
                StockMovement::create(['tenant_id' => $tenant, 'company_id' => $data['company_id'], 'warehouse_id' => $data['warehouse_id'], 'item_id' => $line['item_id'], 'unit_id' => $line['unit_id'], 'movement_type' => 'stock_adjustment', 'direction' => (float) $line['quantity_delta'] > 0 ? 'in' : 'out', 'quantity' => abs($line['quantity_delta']), 'unit_cost' => $line['unit_cost'] ?? $balance->average_cost, 'source_type' => StockAdjustment::class, 'source_id' => $a->id, 'occurred_at' => now()]);
            } $this->audit->record($request, 'posted', $a);

            return $a;
        });

        return response()->json(['data' => $adjustment->load('lines')], 201);
    }

    private function number(string $model, string $prefix, string $company): string
    {
        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $model::where('company_id', $company)->lockForUpdate()->count() + 1);
    }
}
