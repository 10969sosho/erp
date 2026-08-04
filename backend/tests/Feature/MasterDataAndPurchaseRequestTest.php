<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MasterDataAndPurchaseRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_archive_tenant_scoped_item(): void
    {
        [$user, $tenant] = $this->createContext();
        Sanctum::actingAs($user);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);

        $response = $this->postJson('/api/master-data/items', [
            'sku' => 'SKU-001', 'name' => 'Sample Item', 'base_unit_id' => $unit->id,
            'minimum_price' => 1000,
        ]);

        $response->assertCreated()->assertJsonPath('data.sku', 'SKU-001');
        $id = $response->json('data.id');
        $this->postJson("/api/master-data/items/{$id}/archive")->assertOk()->assertJsonPath('data.status', 'inactive');
    }

    public function test_purchase_request_can_be_created_and_submitted(): void
    {
        [$user, $tenant, $company, $branch] = $this->createContext();
        Sanctum::actingAs($user);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);
        $item = Item::create(['tenant_id' => $tenant->id, 'sku' => 'SKU-001', 'name' => 'Sample Item', 'base_unit_id' => $unit->id]);

        $response = $this->postJson('/api/purchase-requests', [
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'request_date' => '2026-08-03',
            'lines' => [[
                'item_id' => $item->id,
                'unit_id' => $unit->id,
                'quantity' => 5,
                'estimated_unit_price' => 2500,
            ]],
        ]);

        $response->assertCreated()->assertJsonPath('data.status', 'draft')->assertJsonPath('data.estimated_total', '12500.0000');
        $id = $response->json('data.id');
        $this->postJson("/api/purchase-requests/{$id}/submit")->assertOk()->assertJsonPath('data.status', 'submitted');
    }

    public function test_purchase_request_rejects_company_from_another_tenant(): void
    {
        [$user, $tenant, $company, $branch] = $this->createContext();
        $otherTenant = Tenant::create(['code' => 'other', 'name' => 'Other Tenant']);
        $otherCompany = Company::create(['tenant_id' => $otherTenant->id, 'code' => 'OTHER', 'name' => 'Other Company']);
        Sanctum::actingAs($user);

        $this->postJson('/api/purchase-requests', [
            'company_id' => $otherCompany->id,
            'branch_id' => $branch->id,
            'request_date' => '2026-08-03',
            'lines' => [],
        ])->assertStatus(422);
    }

    public function test_item_rejects_base_unit_from_another_tenant(): void
    {
        [$user, $tenant] = $this->createContext();
        $otherTenant = Tenant::create(['code' => 'other', 'name' => 'Other Tenant']);
        $otherUnit = Unit::create(['tenant_id' => $otherTenant->id, 'code' => 'OTHER', 'name' => 'Other Unit']);
        Sanctum::actingAs($user);

        $this->postJson('/api/master-data/items', [
            'sku' => 'SKU-OTHER', 'name' => 'Invalid Item', 'base_unit_id' => $otherUnit->id,
        ])->assertStatus(422);
    }

    private function createContext(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo Tenant']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Demo Company']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'Head Office']);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);

        return [$user, $tenant, $company, $branch];
    }
}
