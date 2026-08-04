<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_count_bin_document_and_integration_contracts_work(): void
    {
        [$user, $company, $warehouse, $item] = $this->context();
        Sanctum::actingAs($user);
        $this->postJson('/api/warehouse-bins', ['warehouse_id' => $warehouse->id, 'code' => 'A-01', 'name' => 'Rack A'])->assertCreated();
        $count = $this->postJson('/api/stock-counts', ['warehouse_id' => $warehouse->id, 'count_type' => 'cycle', 'count_date' => '2026-08-03', 'item_ids' => [$item->id]])->assertCreated();
        $this->postJson('/api/stock-counts/'.$count->json('data.id').'/submit', ['lines' => [['id' => $count->json('data.lines.0.id'), 'counted_quantity' => 5]]])->assertOk()->assertJsonPath('data.status', 'posted');
        $document = $this->postJson('/api/documents', ['entity_type' => 'item', 'entity_id' => $item->id, 'document_type' => 'specification', 'title' => 'Spec'])->assertCreated();
        $this->postJson('/api/documents/'.$document->json('data.id').'/attachments', ['file_name' => 'spec.pdf', 'storage_path' => 'documents/spec.pdf', 'mime_type' => 'application/pdf', 'size' => 100])->assertCreated();
        $this->postJson('/api/integration-logs', ['provider' => 'test', 'direction' => 'outbound', 'idempotency_key' => 'key-1', 'request_payload' => ['ok' => true]])->assertStatus(202);
        $this->postJson('/api/integration-logs', ['provider' => 'test', 'direction' => 'outbound', 'idempotency_key' => 'key-1'])->assertOk()->assertJsonPath('duplicate', true);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Main']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'HQ']);
        $warehouse = Warehouse::create(['branch_id' => $branch->id, 'code' => 'MAIN', 'name' => 'Main']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);
        $item = Item::create(['tenant_id' => $tenant->id, 'sku' => 'SKU-1', 'name' => 'Item', 'base_unit_id' => $unit->id]);

        return [$user, $company, $warehouse, $item];
    }
}
