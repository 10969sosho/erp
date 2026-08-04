<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function __construct(private readonly AuditLogger $audit, private readonly AccountingPostingService $posting) {}

    public function storeOrder(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'branch_id' => ['required', 'uuid'], 'customer_id' => ['required', 'uuid'], 'order_date' => ['required', 'date'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.item_id' => ['required', 'uuid'], 'lines.*.unit_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0'], 'lines.*.unit_price' => ['required', 'numeric', 'min:0'], 'lines.*.tax_rate' => ['sometimes', 'numeric', 'between:0,100']]);
        $tenant = $request->user()->tenant_id;
        $company = Company::where('tenant_id', $tenant)->whereKey($data['company_id'])->where('status', 'active')->firstOrFail();
        Branch::where('company_id', $company->id)->whereKey($data['branch_id'])->where('status', 'active')->firstOrFail();
        $customer = Party::where('tenant_id', $tenant)->whereKey($data['customer_id'])->whereIn('type', ['customer', 'both'])->where('status', 'active')->firstOrFail();
        $items = Item::where('tenant_id', $tenant)->whereIn('id', collect($data['lines'])->pluck('item_id'))->where('status', 'active')->get()->keyBy('id');
        abort_unless($items->count() === collect($data['lines'])->pluck('item_id')->unique()->count(), 422, 'Item tidak valid.');
        $subtotal = collect($data['lines'])->sum(fn ($l) => $l['quantity'] * $l['unit_price']);
        $tax = collect($data['lines'])->sum(fn ($l) => $l['quantity'] * $l['unit_price'] * (($l['tax_rate'] ?? 0) / 100));
        $order = DB::transaction(function () use ($data, $request, $tenant, $company, $customer, $subtotal, $tax): SalesOrder {
            $o = SalesOrder::create(['tenant_id' => $tenant, 'company_id' => $company->id, 'branch_id' => $data['branch_id'], 'customer_id' => $customer->id, 'number' => $this->number(SalesOrder::class, 'SO', $company->id), 'order_date' => $data['order_date'], 'subtotal' => $subtotal, 'tax_total' => $tax, 'total' => $subtotal + $tax, 'status' => 'confirmed']);
            $o->lines()->createMany(collect($data['lines'])->map(fn ($l) => [...$l, 'line_total' => $l['quantity'] * $l['unit_price'] * (1 + (($l['tax_rate'] ?? 0) / 100))])->all());
            $this->audit->record($request, 'confirmed', $o);

            return $o;
        });

        return response()->json(['data' => $order->load(['lines', 'customer'])], 201);
    }

    public function storeDelivery(Request $request): JsonResponse
    {
        $data = $request->validate(['sales_order_id' => ['required', 'uuid'], 'warehouse_id' => ['required', 'uuid'], 'delivery_date' => ['required', 'date'], 'lines' => ['required', 'array', 'min:1'], 'lines.*.sales_order_line_id' => ['required', 'uuid'], 'lines.*.quantity' => ['required', 'numeric', 'gt:0']]);
        $tenant = $request->user()->tenant_id;
        $order = SalesOrder::where('tenant_id', $tenant)->whereKey($data['sales_order_id'])->with('lines')->firstOrFail();
        $warehouse = Warehouse::whereKey($data['warehouse_id'])->whereHas('branch', fn ($q) => $q->where('company_id', $order->company_id))->where('status', 'active')->firstOrFail();
        foreach ($data['lines'] as $l) {
            $ol = $order->lines->firstWhere('id', $l['sales_order_line_id']);
            abort_unless($ol && (float) $ol->delivered_quantity + (float) $l['quantity'] <= (float) $ol->quantity, 422, 'Quantity delivery melebihi SO.');
            $balance = StockBalance::where('warehouse_id', $warehouse->id)->where('item_id', $ol->item_id)->first();
            abort_unless($balance && (float) $balance->on_hand - (float) $balance->reserved >= (float) $l['quantity'], 422, 'Stock tidak cukup.');
        }
        $delivery = DB::transaction(function () use ($data, $request, $tenant, $order, $warehouse): Delivery {
            $d = Delivery::create(['tenant_id' => $tenant, 'company_id' => $order->company_id, 'branch_id' => $order->branch_id, 'warehouse_id' => $warehouse->id, 'sales_order_id' => $order->id, 'number' => $this->number(Delivery::class, 'DO', $order->company_id), 'delivery_date' => $data['delivery_date'], 'status' => 'posted']);
            foreach ($data['lines'] as $l) {
                $dl = $d->lines()->create($l);
                $ol = $order->lines()->find($l['sales_order_line_id']);
                $balance = StockBalance::where('warehouse_id', $warehouse->id)->where('item_id', $ol->item_id)->lockForUpdate()->first();
                $balance->decrement('on_hand', $l['quantity']);
                $ol->increment('delivered_quantity', $l['quantity']);
                StockMovement::create(['tenant_id' => $tenant, 'company_id' => $order->company_id, 'warehouse_id' => $warehouse->id, 'item_id' => $ol->item_id, 'unit_id' => $ol->unit_id, 'movement_type' => 'sales_delivery', 'direction' => 'out', 'quantity' => $l['quantity'], 'unit_cost' => $balance->average_cost, 'source_type' => Delivery::class, 'source_id' => $d->id, 'occurred_at' => now()]);
            }
            $cost = $d->lines->sum(fn ($line) => (float) $line->quantity * (float) $order->lines->firstWhere('id', $line->sales_order_line_id)->unit_price);
            $this->posting->post($d, 'Sales delivery', $d->delivery_date->toDateString(), [['account' => 'cost_of_goods_sold', 'type' => 'expense', 'debit' => $cost, 'credit' => 0], ['account' => 'inventory', 'type' => 'asset', 'debit' => 0, 'credit' => $cost]]);
            $this->audit->record($request, 'posted', $d);

            return $d;
        });

        return response()->json(['data' => $delivery->load('lines')], 201);
    }

    public function storeInvoice(Request $request): JsonResponse
    {
        $data = $request->validate(['sales_order_id' => ['required', 'uuid'], 'invoice_date' => ['required', 'date'], 'due_date' => ['sometimes', 'nullable', 'date']]);
        $o = SalesOrder::where('tenant_id', $request->user()->tenant_id)->whereKey($data['sales_order_id'])->firstOrFail();
        abort_unless($o->status === 'confirmed', 422, 'SO tidak dapat ditagihkan.');
        abort_unless($o->lines()->where('delivered_quantity', '>', 0)->exists(), 422, 'Invoice tidak boleh dibuat sebelum delivery selesai.');
        $i = SalesInvoice::create([...$data, 'tenant_id' => $o->tenant_id, 'company_id' => $o->company_id, 'customer_id' => $o->customer_id, 'number' => $this->number(SalesInvoice::class, 'SI', $o->company_id), 'subtotal' => $o->subtotal, 'tax_total' => $o->tax_total, 'total' => $o->total, 'status' => 'posted']);
        $this->posting->post($i, 'Sales invoice', $i->invoice_date->toDateString(), [['account' => 'accounts_receivable', 'type' => 'asset', 'debit' => $i->total, 'credit' => 0], ['account' => 'sales_revenue', 'type' => 'revenue', 'debit' => 0, 'credit' => $i->subtotal], ['account' => 'output_tax', 'type' => 'liability', 'debit' => 0, 'credit' => $i->tax_total]]);
        $this->audit->record($request, 'posted', $i);

        return response()->json(['data' => $i], 201);
    }

    private function number(string $model, string $prefix, string $company): string
    {
        $n = $model::where('company_id', $company)->lockForUpdate()->count() + 1;

        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $n);
    }
}
