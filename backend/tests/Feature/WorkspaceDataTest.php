<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkspaceDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_reads_updates_and_lookups_are_tenant_scoped(): void
    {
        [$user, $tenant, $company, $branch, $warehouse] = $this->context('one');
        [$otherUser, $otherTenant] = $this->context('two');
        $lead = Lead::create(['tenant_id' => $tenant->id, 'owner_id' => $user->id, 'name' => 'Original', 'status' => 'new']);
        Lead::create(['tenant_id' => $otherTenant->id, 'owner_id' => $otherUser->id, 'name' => 'Hidden', 'status' => 'new']);
        Sanctum::actingAs($user);

        $this->getJson('/api/workspace-data/leads')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Original');
        $this->getJson('/api/workspace-data/leads/'.$lead->id)->assertOk()->assertJsonPath('data.id', $lead->id);
        $this->patchJson('/api/workspace-data/leads/'.$lead->id, ['name' => 'Updated', 'status' => 'qualified'])->assertOk()->assertJsonPath('data.name', 'Updated');
        $this->getJson('/api/workspace-data/leads/'.Lead::where('tenant_id', $otherTenant->id)->value('id'))->assertNotFound();
        $this->getJson('/api/lookups/companies')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $company->id);
        $this->getJson('/api/lookups/branches?company_id='.$company->id)->assertOk()->assertJsonPath('data.0.id', $branch->id);
        $this->getJson('/api/lookups/warehouses?branch_id='.$branch->id)->assertOk()->assertJsonPath('data.0.id', $warehouse->id);
    }

    private function context(string $suffix): array
    {
        $tenant = Tenant::create(['code' => 'tenant-'.$suffix, 'name' => 'Tenant '.$suffix]);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'CO-'.$suffix, 'name' => 'Company '.$suffix]);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'BR-'.$suffix, 'name' => 'Branch '.$suffix]);
        $warehouse = Warehouse::create(['branch_id' => $branch->id, 'code' => 'WH-'.$suffix, 'name' => 'Warehouse '.$suffix]);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);

        return [$user, $tenant, $company, $branch, $warehouse];
    }
}
