<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Party;
use App\Models\PurchaseRequest;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchasingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_purchase_to_order_flow_can_be_completed(): void
    {
        [$user, $tenant, $company, $branch, $unit, $item, $supplier] = $this->context();
        Sanctum::actingAs($user);
        $purchaseRequest = PurchaseRequest::create([
            'tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'requester_id' => $user->id,
            'number' => 'PR-2026-000001', 'request_date' => '2026-08-03', 'status' => 'submitted', 'estimated_total' => 100000,
        ]);
        $purchaseRequest->lines()->create(['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 10, 'estimated_unit_price' => 10000]);

        $rfq = $this->postJson('/api/rfqs', [
            'company_id' => $company->id, 'branch_id' => $branch->id, 'purchase_request_id' => $purchaseRequest->id,
            'request_date' => '2026-08-03', 'quotation_deadline' => '2026-08-10',
            'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 10]], 'supplier_ids' => [$supplier->id],
        ])->assertCreated()->assertJsonPath('data.status', 'draft');
        $rfqId = $rfq->json('data.id');
        $rfqLineId = $rfq->json('data.lines.0.id');
        $this->postJson("/api/rfqs/{$rfqId}/submit")->assertOk()->assertJsonPath('data.status', 'sent');

        $quotation = $this->postJson('/api/supplier-quotations', [
            'company_id' => $company->id, 'rfq_id' => $rfqId, 'supplier_id' => $supplier->id, 'quotation_date' => '2026-08-04',
            'currency' => 'IDR', 'payment_days' => 30,
            'lines' => [['rfq_line_id' => $rfqLineId, 'quantity' => 10, 'unit_price' => 9000, 'tax_rate' => 11]],
        ])->assertCreated()->assertJsonPath('data.status', 'submitted')->assertJsonPath('data.total', '99900.0000');
        $quotationId = $quotation->json('data.id');

        $comparison = $this->postJson('/api/quotation-comparisons', [
            'company_id' => $company->id, 'rfq_id' => $rfqId, 'selected_quotation_id' => $quotationId,
            'quotation_ids' => [$quotationId], 'decision_notes' => 'Harga terbaik dan supplier aktif.',
        ])->assertCreated()->assertJsonPath('data.status', 'approved');
        $this->assertNotEmpty($comparison->json('data.id'));

        $this->postJson('/api/purchase-orders', [
            'company_id' => $company->id, 'branch_id' => $branch->id, 'supplier_quotation_id' => $quotationId,
            'order_date' => '2026-08-05', 'expected_date' => '2026-08-12',
        ])->assertCreated()->assertJsonPath('data.status', 'approved')->assertJsonPath('data.total', '99900.0000');
    }

    public function test_quotation_cannot_use_supplier_not_invited_to_rfq(): void
    {
        [$user, $tenant, $company, $branch, $unit, $item, $supplier] = $this->context();
        $otherSupplier = Party::create(['tenant_id' => $tenant->id, 'code' => 'SUP-002', 'type' => 'supplier', 'legal_name' => 'Other Supplier']);
        Sanctum::actingAs($user);
        $rfq = $this->postJson('/api/rfqs', [
            'company_id' => $company->id, 'branch_id' => $branch->id, 'request_date' => '2026-08-03',
            'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 1]], 'supplier_ids' => [$supplier->id],
        ]);
        $rfqId = $rfq->json('data.id');
        $this->postJson("/api/rfqs/{$rfqId}/submit");
        $this->postJson('/api/supplier-quotations', [
            'company_id' => $company->id, 'rfq_id' => $rfqId, 'supplier_id' => $otherSupplier->id, 'quotation_date' => '2026-08-04',
            'lines' => [['rfq_line_id' => $rfq->json('data.lines.0.id'), 'quantity' => 1, 'unit_price' => 100]],
        ])->assertStatus(422);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo Tenant']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Demo Company']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'Head Office']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);
        $item = Item::create(['tenant_id' => $tenant->id, 'sku' => 'SKU-001', 'name' => 'Sample Item', 'base_unit_id' => $unit->id]);
        $supplier = Party::create(['tenant_id' => $tenant->id, 'code' => 'SUP-001', 'type' => 'supplier', 'legal_name' => 'Supplier One']);

        return [$user, $tenant, $company, $branch, $unit, $item, $supplier];
    }
}
