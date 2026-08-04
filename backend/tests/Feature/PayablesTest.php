<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
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

class PayablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_and_payment_can_be_matched_to_received_po(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $supplier] = $this->context();
        Sanctum::actingAs($user);
        $po = PurchaseOrder::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'supplier_id' => $supplier->id, 'number' => 'PO-2026-000001', 'currency' => 'IDR', 'order_date' => '2026-08-01', 'status' => 'approved', 'subtotal' => 100000, 'tax_total' => 0, 'total' => 100000]);
        $line = PurchaseOrderLine::create(['purchase_order_id' => $po->id, 'item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 10, 'unit_price' => 10000, 'line_total' => 100000, 'received_quantity' => 10]);
        $receipt = GoodsReceipt::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'warehouse_id' => $warehouse->id, 'purchase_order_id' => $po->id, 'number' => 'GR-2026-000001', 'receipt_date' => '2026-08-03', 'status' => 'posted']);
        GoodsReceiptLine::create(['goods_receipt_id' => $receipt->id, 'purchase_order_line_id' => $line->id, 'quantity' => 10, 'accepted_quantity' => 10, 'rejected_quantity' => 0]);
        $invoice = $this->postJson('/api/purchase-invoices', ['company_id' => $company->id, 'purchase_order_id' => $po->id, 'supplier_invoice_number' => 'SUP-INV-001', 'invoice_date' => '2026-08-04', 'total' => 100000, 'subtotal' => 100000, 'tax_total' => 0])->assertCreated()->assertJsonPath('data.status', 'matched');
        $this->postJson('/api/payments', ['company_id' => $company->id, 'payment_date' => '2026-08-05', 'method' => 'bank', 'amount' => 100000, 'allocations' => [['purchase_invoice_id' => $invoice->json('data.id'), 'amount' => 100000]]])->assertCreated()->assertJsonPath('data.status', 'posted');
        $this->assertDatabaseHas('purchase_invoices', ['id' => $invoice->json('data.id'), 'status' => 'paid']);
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
