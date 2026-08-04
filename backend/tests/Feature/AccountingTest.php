<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountingTest extends TestCase
{
    use RefreshDatabase;

    public function test_balanced_journal_is_posted_and_trial_balance_is_available(): void
    {
        [$user, $company] = $this->context();
        Sanctum::actingAs($user);
        $cash = $this->postJson('/api/accounts', ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'normal_balance' => 'debit'])->assertCreated()->json('data.id');
        $sales = $this->postJson('/api/accounts', ['code' => '4100', 'name' => 'Sales', 'type' => 'revenue', 'normal_balance' => 'credit'])->assertCreated()->json('data.id');
        $this->postJson('/api/journals', ['journal_date' => '2026-08-03', 'description' => 'Cash sale', 'lines' => [['account_id' => $cash, 'debit' => 100000, 'credit' => 0], ['account_id' => $sales, 'debit' => 0, 'credit' => 100000]]])->assertCreated()->assertJsonPath('data.status', 'posted');
        $this->getJson('/api/reports/trial-balance')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_unbalanced_journal_is_rejected(): void
    {
        [$user] = $this->context();
        Sanctum::actingAs($user);
        $a = $this->postJson('/api/accounts', ['code' => '1100', 'name' => 'Cash', 'type' => 'asset', 'normal_balance' => 'debit'])->json('data.id');
        $this->postJson('/api/journals', ['journal_date' => '2026-08-03', 'lines' => [['account_id' => $a, 'debit' => 100, 'credit' => 0], ['account_id' => $a, 'debit' => 0, 'credit' => 90]]])->assertStatus(422);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo Tenant']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Demo Company']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'Head Office']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);

        return [$user, $company];
    }
}
