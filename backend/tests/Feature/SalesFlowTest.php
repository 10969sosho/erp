<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\StockBalance;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_order_delivery_and_invoice_flow(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $customer] = $this->context();
        Sanctum::actingAs($user);
        StockBalance::create(['tenant_id' => $user->tenant_id, 'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'on_hand' => 20, 'reserved' => 0, 'average_cost' => 100]);
        $order = $this->postJson('/api/sales-orders', ['company_id' => $company->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'order_date' => '2026-08-03', 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 5, 'unit_price' => 200]]])->assertCreated()->assertJsonPath('data.status', 'confirmed');
        $orderId = $order->json('data.id');
        $lineId = $order->json('data.lines.0.id');
        $this->postJson('/api/deliveries', ['sales_order_id' => $orderId, 'warehouse_id' => $warehouse->id, 'delivery_date' => '2026-08-04', 'lines' => [['sales_order_line_id' => $lineId, 'quantity' => 5]]])->assertCreated()->assertJsonPath('data.status', 'posted');
        $this->postJson('/api/sales-invoices', ['sales_order_id' => $orderId, 'invoice_date' => '2026-08-04'])->assertCreated()->assertJsonPath('data.status', 'posted');
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'on_hand' => 15]);
    }

    public function test_delivery_rejects_insufficient_stock(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $customer] = $this->context();
        Sanctum::actingAs($user);
        StockBalance::create(['tenant_id' => $user->tenant_id, 'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'on_hand' => 1, 'reserved' => 0, 'average_cost' => 100]);
        $order = $this->postJson('/api/sales-orders', ['company_id' => $company->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'order_date' => '2026-08-03', 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 5, 'unit_price' => 200]]]);
        $this->postJson('/api/deliveries', ['sales_order_id' => $order->json('data.id'), 'warehouse_id' => $warehouse->id, 'delivery_date' => '2026-08-04', 'lines' => [['sales_order_line_id' => $order->json('data.lines.0.id'), 'quantity' => 5]]])->assertStatus(422);
    }

    public function test_sales_invoice_requires_delivery(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $customer] = $this->context();
        Sanctum::actingAs($user);
        $order = $this->postJson('/api/sales-orders', ['company_id' => $company->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'order_date' => '2026-08-03', 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 1, 'unit_price' => 200]]]);
        $this->postJson('/api/sales-invoices', ['sales_order_id' => $order->json('data.id'), 'invoice_date' => '2026-08-04'])->assertStatus(422);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo Tenant']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Demo Company']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'Head Office']);
        $warehouse = Warehouse::create(['branch_id' => $branch->id, 'code' => 'MAIN', 'name' => 'Main Warehouse']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);
        $item = Item::create(['tenant_id' => $tenant->id, 'sku' => 'SKU-001', 'name' => 'Sample Item', 'base_unit_id' => $unit->id]);
        $customer = Party::create(['tenant_id' => $tenant->id, 'code' => 'CUS-001', 'type' => 'customer', 'legal_name' => 'Customer One']);

        return [$user, $company, $branch, $warehouse, $unit, $item, $customer];
    }
}
