<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceivingInventoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_goods_receipt_qc_and_stock_posting_updates_balance(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $supplier] = $this->context();
        Sanctum::actingAs($user);
        $po = PurchaseOrder::create([
            'tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'supplier_id' => $supplier->id,
            'number' => 'PO-2026-000001', 'currency' => 'IDR', 'order_date' => '2026-08-01', 'status' => 'approved', 'subtotal' => 100000, 'total' => 100000,
        ]);
        $line = PurchaseOrderLine::create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 10000, 'line_total' => 100000]);

        $receipt = $this->postJson('/api/goods-receipts', [
            'purchase_order_id' => $po->id, 'warehouse_id' => $warehouse->id, 'receipt_date' => '2026-08-03',
            'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 10, 'lot_number' => 'LOT-001', 'expiry_date' => '2027-08-03']],
        ])->assertCreated()->assertJsonPath('data.status', 'received');
        $receiptId = $receipt->json('data.id');
        $this->postJson("/api/goods-receipts/{$receiptId}/quality-check", ['result' => 'partial', 'lines' => [['goods_receipt_line_id' => $receipt->json('data.lines.0.id'), 'accepted_quantity' => 8, 'rejected_quantity' => 2]]])->assertOk()->assertJsonPath('data.status', 'qc_completed');
        $this->postJson("/api/goods-receipts/{$receiptId}/post")->assertOk()->assertJsonPath('data.status', 'posted');
        $this->getJson('/api/stock')->assertOk()->assertJsonPath('data.0.on_hand', '8.000000')->assertJsonPath('data.0.average_cost', '10000.0000');
    }

    public function test_receipt_cannot_exceed_open_purchase_order_quantity(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $supplier] = $this->context();
        Sanctum::actingAs($user);
        $po = PurchaseOrder::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'supplier_id' => $supplier->id, 'number' => 'PO-2026-000001', 'currency' => 'IDR', 'order_date' => '2026-08-01', 'status' => 'approved']);
        $line = PurchaseOrderLine::create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 10000, 'line_total' => 100000]);

        $this->postJson('/api/goods-receipts', ['purchase_order_id' => $po->id, 'warehouse_id' => $warehouse->id, 'receipt_date' => '2026-08-03', 'lines' => [['purchase_order_line_id' => $line->id, 'quantity' => 11]]])->assertStatus(422);
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
        $supplier = Party::create(['tenant_id' => $tenant->id, 'code' => 'SUP-001', 'type' => 'supplier', 'legal_name' => 'Supplier One']);

        return [$user, $company, $branch, $warehouse, $unit, $item, $supplier];
    }
}
