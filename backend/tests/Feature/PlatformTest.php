<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Party;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_approval_creates_notification_and_completes(): void
    {
        [$user] = $this->context();
        Sanctum::actingAs($user);
        $definition = $this->postJson('/api/workflow-definitions', ['entity_type' => 'purchase_order', 'name' => 'PO approval', 'steps' => [['approver_id' => $user->id]]])->assertCreated()->json('data.id');
        $instance = $this->postJson('/api/workflow-instances', ['definition_id' => $definition, 'entity_type' => 'purchase_order', 'entity_id' => $user->id])->assertCreated();
        $approval = $instance->json('data.approvals.0.id');
        $this->getJson('/api/notifications')->assertOk()->assertJsonPath('data.0.status', 'unread');
        $this->postJson("/api/approvals/{$approval}/decide", ['decision' => 'approved'])->assertOk()->assertJsonPath('data.status', 'approved');
    }

    public function test_crm_project_service_and_job_registry_endpoints_work(): void
    {
        [$user, $company] = $this->context();
        Sanctum::actingAs($user);
        $this->postJson('/api/leads', ['name' => 'Prospect'])->assertCreated();
        $this->postJson('/api/opportunities', ['name' => 'Opportunity', 'expected_value' => 1000])->assertCreated();
        $project = $this->postJson('/api/projects', ['company_id' => $company->id, 'code' => 'P-001', 'name' => 'Implementation'])->assertCreated();
        $this->postJson('/api/projects/'.$project->json('data.id').'/tasks', ['name' => 'Discovery'])->assertCreated();
        $customer = Party::create(['tenant_id' => $user->tenant_id, 'code' => 'CUS-001', 'type' => 'customer', 'legal_name' => 'Customer One']);
        $this->postJson('/api/service-tickets', ['customer_id' => $customer->id, 'subject' => 'Support'])->assertCreated();
        $this->postJson('/api/service-tickets', ['customer_id' => $user->id, 'subject' => 'Invalid'])->assertStatus(404);
        $this->postJson('/api/report-jobs', ['report_key' => 'trial_balance', 'format' => 'xlsx'])->assertStatus(202);
        $this->postJson('/api/import-jobs', ['import_type' => 'items', 'file_path' => 'imports/items.csv'])->assertStatus(202);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Main']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'HQ']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);

        return [$user, $company];
    }
}
