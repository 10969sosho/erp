<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_transfer_moves_balance_between_warehouses(): void
    {
        [$user, $company, $source, $destination, $unit, $item] = $this->context();
        Sanctum::actingAs($user);
        StockBalance::create(['tenant_id' => $user->tenant_id, 'warehouse_id' => $source->id, 'item_id' => $item->id, 'on_hand' => 10, 'reserved' => 0, 'average_cost' => 100]);
        $this->postJson('/api/stock-transfers', ['company_id' => $company->id, 'source_warehouse_id' => $source->id, 'destination_warehouse_id' => $destination->id, 'transfer_date' => '2026-08-05', 'reason' => 'Replenishment', 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity' => 4]]])->assertCreated();
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $source->id, 'item_id' => $item->id, 'on_hand' => 6]);
        $this->assertDatabaseHas('stock_balances', ['warehouse_id' => $destination->id, 'item_id' => $item->id, 'on_hand' => 4]);
    }

    public function test_adjustment_cannot_create_negative_stock(): void
    {
        [$user, $company, $source, $destination, $unit, $item] = $this->context();
        Sanctum::actingAs($user);
        StockBalance::create(['tenant_id' => $user->tenant_id, 'warehouse_id' => $source->id, 'item_id' => $item->id, 'on_hand' => 1, 'reserved' => 0, 'average_cost' => 100]);
        $this->postJson('/api/stock-adjustments', ['company_id' => $company->id, 'warehouse_id' => $source->id, 'adjustment_date' => '2026-08-05', 'reason' => 'Count correction', 'lines' => [['item_id' => $item->id, 'unit_id' => $unit->id, 'quantity_delta' => -2]]])->assertStatus(422);
    }

    private function context(): array
    {
        $tenant = Tenant::create(['code' => 'demo', 'name' => 'Demo Tenant']);
        $company = Company::create(['tenant_id' => $tenant->id, 'code' => 'MAIN', 'name' => 'Demo Company']);
        $branch = Branch::create(['company_id' => $company->id, 'code' => 'HQ', 'name' => 'Head Office']);
        $source = Warehouse::create(['branch_id' => $branch->id, 'code' => 'SRC', 'name' => 'Source']);
        $destination = Warehouse::create(['branch_id' => $branch->id, 'code' => 'DST', 'name' => 'Destination']);
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'company_id' => $company->id, 'branch_id' => $branch->id, 'status' => 'active']);
        $unit = Unit::create(['tenant_id' => $tenant->id, 'code' => 'PCS', 'name' => 'Pieces']);
        $item = Item::create(['tenant_id' => $tenant->id, 'sku' => 'SKU-001', 'name' => 'Sample Item', 'base_unit_id' => $unit->id]);

        return [$user, $company, $source, $destination, $unit, $item];
    }
}
