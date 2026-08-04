<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\StockBalance;
use App\Models\TaxCode;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceivablesTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_snapshot_receipt_return_and_credit_note_flow(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $customer] = $this->context();
        Sanctum::actingAs($user);
        $tax = TaxCode::create(['tenant_id' => $user->tenant_id, 'code' => 'VAT11', 'name' => 'VAT 11%', 'rate' => 11, 'effective_from' => '2026-01-01']);
        $order = SalesOrder::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'customer_id' => $customer->id, 'number' => 'SO-2026-000001', 'order_date' => '2026-08-01', 'subtotal' => 1000, 'tax_total' => 110, 'total' => 1110, 'status' => 'confirmed']);
        $line = SalesOrderLine::create(['sales_order_id' => $order->id, 'item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 2, 'unit_price' => 500, 'tax_rate' => 11, 'line_total' => 1110, 'delivered_quantity' => 2]);
        StockBalance::create(['tenant_id' => $user->tenant_id, 'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'on_hand' => 0, 'reserved' => 0, 'average_cost' => 400]);
        $invoice = SalesInvoice::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'customer_id' => $customer->id, 'sales_order_id' => $order->id, 'number' => 'SI-2026-000001', 'invoice_date' => '2026-08-02', 'subtotal' => 1000, 'tax_total' => 110, 'total' => 1110, 'status' => 'posted']);

        $this->postJson("/api/sales-invoices/{$invoice->id}/tax-snapshot", ['tax_lines' => [['tax_code_id' => $tax->id, 'taxable_amount' => 1000]]])->assertOk()->assertJsonPath('data.tax_lines.0.rate', '11.0000');
        $return = $this->postJson('/api/sales-returns', ['company_id' => $company->id, 'branch_id' => $branch->id, 'warehouse_id' => $warehouse->id, 'sales_order_id' => $order->id, 'sales_invoice_id' => $invoice->id, 'return_date' => '2026-08-04', 'reason' => 'Customer return', 'lines' => [['sales_order_line_id' => $line->id, 'quantity' => 1]]])->assertCreated();
        $this->postJson('/api/credit-notes', ['company_id' => $company->id, 'sales_invoice_id' => $invoice->id, 'sales_return_id' => $return->json('data.id'), 'credit_date' => '2026-08-04', 'reason' => 'Return approved'])->assertCreated()->assertJsonPath('data.total', '500.0000');
        $this->postJson('/api/customer-receipts', ['company_id' => $company->id, 'customer_id' => $customer->id, 'receipt_date' => '2026-08-05', 'method' => 'bank', 'amount' => 1110, 'allocations' => [['sales_invoice_id' => $invoice->id, 'amount' => 1110]]])->assertCreated()->assertJsonPath('data.status', 'posted');
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'on_hand' => 1]);
    }

    public function test_customer_receipt_rejects_invoice_from_another_customer(): void
    {
        [$user, $company, $branch, $warehouse, $unit, $item, $customer] = $this->context();
        $other = Party::create(['tenant_id' => $user->tenant_id, 'code' => 'CUS-002', 'type' => 'customer', 'legal_name' => 'Other Customer']);
        $invoice = SalesInvoice::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'customer_id' => $other->id, 'sales_order_id' => SalesOrder::create(['tenant_id' => $user->tenant_id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'customer_id' => $other->id, 'number' => 'SO-2026-000002', 'order_date' => '2026-08-01', 'status' => 'confirmed'])->id, 'number' => 'SI-2026-000002', 'invoice_date' => '2026-08-02', 'total' => 100, 'status' => 'posted']);
        Sanctum::actingAs($user);
        $this->postJson('/api/customer-receipts', ['company_id' => $company->id, 'customer_id' => $customer->id, 'receipt_date' => '2026-08-05', 'method' => 'bank', 'amount' => 100, 'allocations' => [['sales_invoice_id' => $invoice->id, 'amount' => 100]]])->assertStatus(422);
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
