<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\FiscalPeriod;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostingAndPeriodTest extends TestCase
{
    use RefreshDatabase;

    public function test_journal_can_be_posted_and_period_can_be_closed_and_reopened(): void
    {
        [$user] = $this->context();
        Sanctum::actingAs($user);
        $cash = $this->postJson('/api/accounts', ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'normal_balance' => 'debit'])->json('data.id');
        $revenue = $this->postJson('/api/accounts', ['code' => '4100', 'name' => 'Revenue', 'type' => 'revenue', 'normal_balance' => 'credit'])->json('data.id');
        $this->postJson('/api/journals', ['journal_date' => '2026-08-03', 'lines' => [['account_id' => $cash, 'debit' => 100, 'credit' => 0], ['account_id' => $revenue, 'debit' => 0, 'credit' => 100]]])->assertCreated();
        $period = FiscalPeriod::firstOrFail();
        $this->postJson("/api/fiscal-periods/{$period->id}/close")->assertOk()->assertJsonPath('data.status', 'closed');
        $this->postJson('/api/journals', ['journal_date' => '2026-08-03', 'lines' => [['account_id' => $cash, 'debit' => 50, 'credit' => 0], ['account_id' => $revenue, 'debit' => 0, 'credit' => 50]]])->assertStatus(422);
        $this->postJson("/api/fiscal-periods/{$period->id}/reopen")->assertOk()->assertJsonPath('data.status', 'open');
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Main']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'HQ']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);

        return [$user];
    }
}
