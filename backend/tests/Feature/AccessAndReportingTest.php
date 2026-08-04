<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccessAndReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_permission_controls_admin_endpoint(): void
    {
        [$user, $tenant] = $this->context();
        $permission = Permission::create(['key' => 'security.role.view', 'description' => 'View roles']);
        $role = Role::create(['tenant_id' => $tenant->id, 'code' => 'auditor', 'name' => 'Auditor']);
        DB::table('role_permissions')->insert(['id' => (string) Str::uuid(), 'role_id' => $role->id, 'permission_id' => $permission->id, 'scope_type' => 'tenant', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('user_roles')->insert(['id' => (string) Str::uuid(), 'user_id' => $user->id, 'role_id' => $role->id, 'created_at' => now(), 'updated_at' => now()]);
        Sanctum::actingAs($user);
        $this->getJson('/api/roles')->assertOk();
    }

    public function test_user_without_permission_is_forbidden(): void
    {
        [$user] = $this->context();
        Sanctum::actingAs($user);
        $this->getJson('/api/roles')->assertForbidden();
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Main']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'HQ']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);

        return [$user, $tenant];
    }
}
