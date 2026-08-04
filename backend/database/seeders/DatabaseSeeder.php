<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Activity;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CreditNote;
use App\Models\CustomerReceipt;
use App\Models\CustomerReceiptAllocation;
use App\Models\Delivery;
use App\Models\DeliveryLine;
use App\Models\Document;
use App\Models\FiscalPeriod;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\ImportJob;
use App\Models\IntegrationLog;
use App\Models\Item;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Opportunity;
use App\Models\Party;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Permission;
use App\Models\PriceList;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestLine;
use App\Models\QualityCheck;
use App\Models\QuotationComparison;
use App\Models\QuotationComparisonLine;
use App\Models\ReportJob;
use App\Models\Rfq;
use App\Models\RfqLine;
use App\Models\RfqSupplier;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceTaxLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\SalesReturn;
use App\Models\SalesReturnLine;
use App\Models\ServiceTicket;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentLine;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountLine;
use App\Models\StockTransfer;
use App\Models\StockTransferLine;
use App\Models\SupplierQuotation;
use App\Models\SupplierQuotationLine;
use App\Models\TaxCode;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseBin;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    private string $tenant;

    private string $companyMain;

    private string $companySecondary;

    private string $branchHQ;

    private string $branchEast;

    private string $warehouseMain;

    private string $warehouseEast;

    private string $adminId;

    private array $units;

    private array $items;

    private array $customers;

    private array $suppliers;

    private array $taxCodes;

    private array $accounts;

    public function run(): void
    {
        echo "\n🚀 Seeding ERP Distributor — full demo data...\n";

        $this->seedFoundation();
        $this->seedMasterData();
        $this->seedAccounts();
        $this->seedFiscalPeriods();
        $this->seedCRM();
        $this->seedPurchaseCycle();
        $this->seedSalesCycle();
        $this->seedStockOperations();
        $this->seedFinanceOperations();
        $this->seedPlatformData();

        echo "\n✅ Semua data demo berhasil dibuat.\n";
    }

    private function seedFoundation(): void
    {
        echo "  ↳ Foundation + admin user...\n";

        $tenant = Tenant::firstOrCreate(['code' => 'demo'], ['name' => 'PT Distributor Nusantara', 'status' => 'active']);
        $this->tenant = $tenant->id;

        $companyMain = Company::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'MAIN'], ['name' => 'PT Distributor Nusantara', 'base_currency' => 'IDR', 'status' => 'active']);
        $this->companyMain = $companyMain->id;

        $companySecondary = Company::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'SRV'], ['name' => 'PT Nusantara Service Center', 'base_currency' => 'IDR', 'status' => 'active']);
        $this->companySecondary = $companySecondary->id;

        $branchHQ = Branch::firstOrCreate(['company_id' => $companyMain->id, 'code' => 'HQ'], ['name' => 'Head Office', 'status' => 'active']);
        $this->branchHQ = $branchHQ->id;

        $branchEast = Branch::firstOrCreate(['company_id' => $companyMain->id, 'code' => 'EAST'], ['name' => 'Cabang Surabaya', 'status' => 'active']);
        $this->branchEast = $branchEast->id;

        Branch::firstOrCreate(['company_id' => $companySecondary->id, 'code' => 'SVC'], ['name' => 'Service Center', 'status' => 'active']);

        $this->warehouseMain = Warehouse::firstOrCreate(['branch_id' => $branchHQ->id, 'code' => 'MAIN'], ['name' => 'Gudang Pusat', 'status' => 'active', 'costing_method' => 'average'])->id;
        $this->warehouseEast = Warehouse::firstOrCreate(['branch_id' => $branchEast->id, 'code' => 'EAST'], ['name' => 'Gudang Surabaya', 'status' => 'active', 'costing_method' => 'average'])->id;

        foreach (['platform.health.view', 'platform.tenant.view', 'security.user.view', 'security.role.view', 'security.role.manage', 'security.role.assign'] as $key) {
            Permission::firstOrCreate(['key' => $key], ['description' => 'System permission']);
        }

        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'System Administrator', 'tenant_id' => $tenant->id, 'company_id' => $companyMain->id, 'branch_id' => $branchHQ->id,
            'password' => Hash::make('ChangeMe123!'), 'status' => 'active',
        ]);
        $this->adminId = $admin->id;

        User::firstOrCreate(['email' => 'budi@nusantara.id'], [
            'name' => 'Budi Santoso', 'tenant_id' => $tenant->id, 'company_id' => $companyMain->id, 'branch_id' => $branchHQ->id,
            'password' => Hash::make('password'), 'status' => 'active',
        ]);
        User::firstOrCreate(['email' => 'sari@nusantara.id'], [
            'name' => 'Sari Indah', 'tenant_id' => $tenant->id, 'company_id' => $companyMain->id, 'branch_id' => $branchEast->id,
            'password' => Hash::make('password'), 'status' => 'active',
        ]);

        $adminRole = Role::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'platform-admin'], ['name' => 'Platform Administrator', 'status' => 'active']);
        Role::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'purchasing'], ['name' => 'Purchasing Staff', 'status' => 'active']);
        Role::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'sales'], ['name' => 'Sales Person', 'status' => 'active']);
        Role::firstOrCreate(['tenant_id' => $tenant->id, 'code' => 'warehouse'], ['name' => 'Warehouse Operator', 'status' => 'active']);

        foreach (Permission::whereIn('key', ['platform.health.view', 'platform.tenant.view', 'security.user.view', 'security.role.view', 'security.role.manage'])->pluck('id') as $permissionId) {
            DB::table('role_permissions')->updateOrInsert(['role_id' => $adminRole->id, 'permission_id' => $permissionId, 'scope_type' => 'tenant'], ['id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('user_roles')->updateOrInsert(['user_id' => $admin->id, 'role_id' => $adminRole->id], ['id' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
    }

    private function seedMasterData(): void
    {
        echo "  ↳ Master data — units, items, parties, tax codes...\n";

        $this->units = [
            'pcs' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'PCS', 'name' => 'Pieces', 'precision' => 0, 'status' => 'active'])->id,
            'kg' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'KG', 'name' => 'Kilogram', 'precision' => 2, 'status' => 'active'])->id,
            'ltr' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'LTR', 'name' => 'Liter', 'precision' => 2, 'status' => 'active'])->id,
            'box' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'BOX', 'name' => 'Box/Dus', 'precision' => 0, 'status' => 'active'])->id,
            'roll' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'ROLL', 'name' => 'Roll', 'precision' => 0, 'status' => 'active'])->id,
            'mtr' => Unit::create(['tenant_id' => $this->tenant, 'code' => 'MTR', 'name' => 'Meter', 'precision' => 2, 'status' => 'active'])->id,
        ];

        $items = [
            ['sku' => 'SKU-001', 'name' => 'Kabel NYA 2.5mm', 'type' => 'stock', 'base_unit_id' => $this->units['roll'], 'lot_tracking' => true, 'minimum_price' => 85000],
            ['sku' => 'SKU-002', 'name' => 'Saklar Seri Panasonic', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 15000],
            ['sku' => 'SKU-003', 'name' => 'Cat Tembok Putih 5kg', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'lot_tracking' => true, 'minimum_price' => 45000],
            ['sku' => 'SKU-004', 'name' => 'Besi Beton 10mm', 'type' => 'stock', 'base_unit_id' => $this->units['mtr'], 'minimum_price' => 12000],
            ['sku' => 'SKU-005', 'name' => 'Pipa PVC 4 inch', 'type' => 'stock', 'base_unit_id' => $this->units['mtr'], 'minimum_price' => 28000],
            ['sku' => 'SKU-006', 'name' => 'Semen Tiga Roda 50kg', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 72000],
            ['sku' => 'SKU-007', 'name' => 'Lampu LED 18W', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 35000],
            ['sku' => 'SKU-008', 'name' => 'Kran Air San-Ei', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 45000],
            ['sku' => 'SKU-009', 'name' => 'Jasa Instalasi Listrik', 'type' => 'service', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 150000],
            ['sku' => 'SKU-010', 'name' => 'Jasa Perbaikan Plumbing', 'type' => 'service', 'base_unit_id' => $this->units['pcs'], 'minimum_price' => 200000],
        ];
        $this->items = [];
        foreach ($items as $item) {
            $this->items[$item['sku']] = Item::create(['tenant_id' => $this->tenant, ...$item, 'status' => 'active'])->id;
        }
        Item::create(['tenant_id' => $this->tenant, 'sku' => 'SKU-ARCHIVE', 'name' => 'Produk Tidak Aktif', 'type' => 'stock', 'base_unit_id' => $this->units['pcs'], 'status' => 'inactive']);

        $parties = [
            ['code' => 'CUST-001', 'type' => 'customer', 'legal_name' => 'Toko Bangunan Jaya', 'email' => 'toko@bangunanjaya.id', 'phone' => '021-5550101', 'credit_limit' => 150000000],
            ['code' => 'CUST-002', 'type' => 'customer', 'legal_name' => 'CV Maju Elektrik', 'email' => 'admin@majuelektrik.id', 'phone' => '031-5550202', 'credit_limit' => 75000000],
            ['code' => 'CUST-003', 'type' => 'customer', 'legal_name' => 'PT Griya Asri Development', 'email' => 'procurement@griyaasri.co.id', 'phone' => '022-5550303', 'credit_limit' => 300000000],
            ['code' => 'CUST-004', 'type' => 'both', 'legal_name' => 'UD Sumber Rezeki', 'email' => 'ud@sumberrezeki.id', 'phone' => '024-5550404', 'credit_limit' => 50000000],
            ['code' => 'SUPP-001', 'type' => 'supplier', 'legal_name' => 'PT Kabelindo Nusantara', 'email' => 'sales@kabelindo.co.id', 'phone' => '021-5551001'],
            ['code' => 'SUPP-002', 'type' => 'supplier', 'legal_name' => 'CV Sumber Material', 'email' => 'order@sumbermaterial.id', 'phone' => '031-5551002'],
            ['code' => 'SUPP-003', 'type' => 'supplier', 'legal_name' => 'PT Cat Nusantara Jaya', 'email' => 'distributor@catnusantara.co.id', 'phone' => '021-5551003'],
            ['code' => 'PERS-001', 'type' => 'person', 'legal_name' => 'Agus Hermawan', 'email' => 'agus@personal.id', 'phone' => '0812-5550001'],
        ];
        $this->customers = [];
        $this->suppliers = [];
        foreach ($parties as $party) {
            $created = Party::create(['tenant_id' => $this->tenant, ...$party, 'status' => 'active']);
            if (in_array($party['type'], ['customer', 'both'])) {
                $this->customers[] = $created->id;
            }
            if (in_array($party['type'], ['supplier', 'both'])) {
                $this->suppliers[] = $created->id;
            }
        }
        Party::create(['tenant_id' => $this->tenant, 'code' => 'SUPP-ARCHIVE', 'type' => 'supplier', 'legal_name' => 'Supplier Lama', 'status' => 'inactive']);

        $taxes = [
            ['code' => 'PPN-11', 'name' => 'PPN 11%', 'rate' => 11, 'effective_from' => '2025-01-01'],
            ['code' => 'PPH-23', 'name' => 'PPh 23 Jasa', 'rate' => 2, 'effective_from' => '2025-01-01'],
            ['code' => 'PPN-0', 'name' => 'Non-PPN', 'rate' => 0, 'effective_from' => '2025-01-01'],
        ];
        $this->taxCodes = [];
        foreach ($taxes as $tax) {
            $this->taxCodes[$tax['code']] = TaxCode::create(['tenant_id' => $this->tenant, ...$tax, 'status' => 'active'])->id;
        }
        TaxCode::create(['tenant_id' => $this->tenant, 'code' => 'TAX-OLD', 'name' => 'Pajak Kadaluwarsa', 'rate' => 5, 'effective_from' => '2023-01-01', 'effective_to' => '2023-12-31', 'status' => 'inactive']);

        PriceList::create(['tenant_id' => $this->tenant, 'code' => 'RETAIL-2026', 'name' => 'Harga Ecer 2026', 'effective_from' => '2026-01-01', 'status' => 'active']);
    }

    private function seedAccounts(): void
    {
        echo "  ↳ Chart of accounts...\n";

        $accounts = [
            ['code' => '1-1000', 'name' => 'Kas', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1-1100', 'name' => 'Bank BCA', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1-1200', 'name' => 'Piutang Usaha', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1-1300', 'name' => 'Persediaan Barang', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '1-1400', 'name' => 'PPN Masukan', 'type' => 'asset', 'normal_balance' => 'debit'],
            ['code' => '2-1000', 'name' => 'Hutang Usaha', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2-1100', 'name' => 'GRNI Accrual', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '2-1200', 'name' => 'PPN Keluaran', 'type' => 'liability', 'normal_balance' => 'credit'],
            ['code' => '3-1000', 'name' => 'Modal Disetor', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '3-2000', 'name' => 'Laba Ditahan', 'type' => 'equity', 'normal_balance' => 'credit'],
            ['code' => '4-1000', 'name' => 'Pendapatan Penjualan', 'type' => 'revenue', 'normal_balance' => 'credit'],
            ['code' => '5-1000', 'name' => 'Harga Pokok Penjualan', 'type' => 'expense', 'normal_balance' => 'debit'],
            ['code' => '5-2000', 'name' => 'Beban Operasional', 'type' => 'expense', 'normal_balance' => 'debit'],
        ];
        $this->accounts = [];
        foreach ($accounts as $account) {
            $this->accounts[$account['code']] = Account::create(['tenant_id' => $this->tenant, 'company_id' => $this->companyMain, ...$account, 'status' => 'active'])->id;
        }
    }

    private function seedFiscalPeriods(): void
    {
        for ($m = 1; $m <= 12; $m++) {
            $starts = now()->setMonth($m)->startOfMonth();
            $ends = now()->setMonth($m)->endOfMonth();
            FiscalPeriod::firstOrCreate(
                ['company_id' => $this->companyMain, 'year' => 2026, 'period' => $m],
                ['tenant_id' => $this->tenant, 'starts_on' => $starts, 'ends_on' => $ends, 'status' => 'open']
            );
        }
    }

    private function seedCRM(): void
    {
        echo "  ↳ CRM — leads, opportunities, activities, projects, service tickets...\n";

        $leadIds = [];
        $sources = ['Website', 'Referral', 'Trade Show', 'Cold Call'];
        $leads = [
            ['name' => 'Hotel Grand Permata', 'email' => 'procurement@hotelpermata.com', 'phone' => '021-6660101', 'source' => 'Website'],
            ['name' => 'RS Sehat Sentosa', 'email' => 'admin@rssentosamedika.id', 'phone' => '022-6660202', 'source' => 'Referral'],
            ['name' => 'Universitas Nusantara', 'email' => 'rektorat@unusantara.ac.id', 'source' => 'Trade Show'],
            ['name' => 'PT Griya Indah Jaya', 'email' => 'info@griyaindah.id', 'source' => 'Cold Call'],
        ];
        foreach ($leads as $i => $lead) {
            $leadIds[] = Lead::create([
                'tenant_id' => $this->tenant, 'owner_id' => $this->adminId,
                'name' => $lead['name'], 'email' => $lead['email'] ?? null,
                'phone' => $lead['phone'] ?? null, 'source' => $lead['source'],
                'status' => $i < 2 ? 'new' : 'qualified',
            ])->id;
        }
        Lead::create([
            'tenant_id' => $this->tenant, 'owner_id' => $this->adminId,
            'name' => 'PT Lama Tidak Aktif', 'source' => 'Referral', 'status' => 'lost',
        ]);

        Opportunity::create([
            'tenant_id' => $this->tenant, 'customer_id' => $this->customers[0], 'owner_id' => $this->adminId,
            'name' => 'Supply kabel proyek Hotel', 'stage' => 'proposal', 'expected_value' => 250000000, 'probability' => 60,
            'expected_close_date' => '2026-09-15',
        ]);
        Opportunity::create([
            'tenant_id' => $this->tenant, 'customer_id' => $this->customers[1], 'owner_id' => $this->adminId,
            'name' => 'Saklar + lampu RS Sentosa', 'stage' => 'qualified', 'expected_value' => 45000000, 'probability' => 40,
        ]);

        $activityTypes = ['call', 'email', 'meeting', 'task', 'note'];
        foreach ($activityTypes as $i => $type) {
            Activity::create([
                'tenant_id' => $this->tenant, 'user_id' => $this->adminId,
                'subject' => ucfirst($type).' dengan '.$leads[$i % count($leads)]['name'],
                'activity_type' => $type, 'status' => $i < 3 ? 'completed' : 'open',
                'due_at' => now()->addDays($i + 1),
            ]);
        }

        $project = Project::create([
            'tenant_id' => $this->tenant, 'company_id' => $this->companyMain,
            'code' => 'PROJ-001', 'name' => 'Implementasi Gudang Baru Surabaya',
            'status' => 'active', 'start_date' => '2026-06-01', 'end_date' => '2026-12-31', 'budget' => 850000000,
        ]);
        ProjectTask::create(['project_id' => $project->id, 'assignee_id' => $this->adminId, 'name' => 'Survey lokasi gudang', 'status' => 'done', 'progress' => 100]);
        ProjectTask::create(['project_id' => $project->id, 'assignee_id' => $this->adminId, 'name' => 'Pengadaan rak gudang', 'status' => 'in_progress', 'progress' => 45, 'due_date' => '2026-09-01']);
        ProjectTask::create(['project_id' => $project->id, 'name' => 'Instalasi sistem WMS', 'status' => 'todo', 'due_date' => '2026-11-01']);

        Project::create([
            'tenant_id' => $this->tenant, 'company_id' => $this->companyMain,
            'code' => 'PROJ-002', 'name' => 'Migrasi ERP Phase 2', 'status' => 'planned', 'budget' => 350000000,
        ]);

        ServiceTicket::create([
            'tenant_id' => $this->tenant, 'customer_id' => $this->customers[1],
            'number' => 'TCK-2026-000001', 'subject' => 'Lampu LED mati setelah 2 minggu', 'priority' => 'high', 'status' => 'open',
            'description' => 'Customer melaporkan 3 unit lampu mati total setelah instalasi.',
        ]);
        ServiceTicket::create([
            'tenant_id' => $this->tenant, 'customer_id' => $this->customers[2],
            'number' => 'TCK-2026-000002', 'subject' => 'Keterlambatan pengiriman DO-002', 'priority' => 'urgent', 'status' => 'in_progress',
            'description' => 'Proyek Griya Asri tertunda karena pengiriman molor 3 hari.',
        ]);
    }

    private function seedPurchaseCycle(): void
    {
        echo "  ↳ Procurement — PR → RFQ → Quotation → Comparison → PO → Receiving → Invoice → Payment...\n";

        $tn = $this->tenant;
        $cm = $this->companyMain;
        $br = $this->branchHQ;
        $wh = $this->warehouseMain;
        $uid = $this->adminId;

        // --- PR 1: draft + submitted later ---
        $pr1 = PurchaseRequest::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'requester_id' => $uid,
            'number' => 'PR-2026-000001', 'request_date' => '2026-07-15', 'required_date' => '2026-08-01',
            'status' => 'submitted', 'notes' => 'Restock kabel dan saklar', 'estimated_total' => 150000 * 80000 + 2500 * 14000,
        ]);
        PurchaseRequestLine::create(['purchase_request_id' => $pr1->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 150, 'estimated_unit_price' => 80000]);
        PurchaseRequestLine::create(['purchase_request_id' => $pr1->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity' => 2500, 'estimated_unit_price' => 14000]);

        // --- PR 2: draft (still editable) ---
        $pr2 = PurchaseRequest::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'requester_id' => $uid,
            'number' => 'PR-2026-000002', 'request_date' => '2026-08-01', 'status' => 'draft',
            'notes' => 'Tambahan stok cat dan semen', 'estimated_total' => 200 * 43000 + 500 * 70000,
        ]);
        PurchaseRequestLine::create(['purchase_request_id' => $pr2->id, 'item_id' => $this->items['SKU-003'], 'unit_id' => $this->units['pcs'], 'quantity' => 200, 'estimated_unit_price' => 43000]);
        PurchaseRequestLine::create(['purchase_request_id' => $pr2->id, 'item_id' => $this->items['SKU-006'], 'unit_id' => $this->units['pcs'], 'quantity' => 500, 'estimated_unit_price' => 70000]);

        // --- PR 3: cancelled ---
        $pr3 = PurchaseRequest::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'requester_id' => $uid,
            'number' => 'PR-2026-000003', 'request_date' => '2026-06-01', 'status' => 'cancelled', 'estimated_total' => 0,
        ]);
        PurchaseRequestLine::create(['purchase_request_id' => $pr3->id, 'item_id' => $this->items['SKU-004'], 'unit_id' => $this->units['mtr'], 'quantity' => 100, 'estimated_unit_price' => 11000]);

        // --- RFQ from PR1 ---
        $rfq = Rfq::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'purchase_request_id' => $pr1->id,
            'number' => 'RFQ-2026-000001', 'request_date' => '2026-07-20', 'quotation_deadline' => '2026-07-28',
            'status' => 'sent', 'notes' => 'Mohon penawaran harga terbaik.',
        ]);
        $rfqLine1 = RfqLine::create(['rfq_id' => $rfq->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 150]);
        $rfqLine2 = RfqLine::create(['rfq_id' => $rfq->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity' => 2500]);
        RfqSupplier::create(['rfq_id' => $rfq->id, 'supplier_id' => $this->suppliers[0], 'status' => 'sent', 'sent_at' => now()]);
        RfqSupplier::create(['rfq_id' => $rfq->id, 'supplier_id' => $this->suppliers[1], 'status' => 'sent', 'sent_at' => now()]);

        // --- RFQ 2: draft (editable) ---
        $rfq2 = Rfq::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'purchase_request_id' => $pr2->id,
            'number' => 'RFQ-2026-000002', 'request_date' => '2026-08-01', 'status' => 'draft', 'notes' => 'Draft RFQ cat + semen.',
        ]);
        $rfq2L1 = RfqLine::create(['rfq_id' => $rfq2->id, 'item_id' => $this->items['SKU-003'], 'unit_id' => $this->units['pcs'], 'quantity' => 200]);
        RfqSupplier::create(['rfq_id' => $rfq2->id, 'supplier_id' => $this->suppliers[2], 'status' => 'invited']);

        // --- Supplier Quotation from SUPPL-001 ---
        $sq = SupplierQuotation::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'rfq_id' => $rfq->id, 'supplier_id' => $this->suppliers[0],
            'number' => 'SQ-2026-000001', 'currency' => 'IDR', 'quotation_date' => '2026-07-22', 'payment_days' => 30,
            'subtotal' => 150 * 78000 + 2500 * 13500, 'tax_total' => (150 * 78000 + 2500 * 13500) * 0.11,
            'total' => (150 * 78000 + 2500 * 13500) * 1.11, 'status' => 'submitted',
        ]);
        SupplierQuotationLine::create(['supplier_quotation_id' => $sq->id, 'rfq_line_id' => $rfqLine1->id, 'quantity' => 150, 'unit_price' => 78000, 'tax_rate' => 11, 'line_total' => 150 * 78000 * 1.11]);
        SupplierQuotationLine::create(['supplier_quotation_id' => $sq->id, 'rfq_line_id' => $rfqLine2->id, 'quantity' => 2500, 'unit_price' => 13500, 'tax_rate' => 11, 'line_total' => 2500 * 13500 * 1.11]);

        // --- Supplier Quotation from SUPPL-002 ---
        $sq2 = SupplierQuotation::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'rfq_id' => $rfq->id, 'supplier_id' => $this->suppliers[1],
            'number' => 'SQ-2026-000002', 'currency' => 'IDR', 'quotation_date' => '2026-07-23', 'payment_days' => 45,
            'subtotal' => 150 * 81000 + 2500 * 14000, 'tax_total' => (150 * 81000 + 2500 * 14000) * 0.11,
            'total' => (150 * 81000 + 2500 * 14000) * 1.11, 'status' => 'submitted',
        ]);
        SupplierQuotationLine::create(['supplier_quotation_id' => $sq2->id, 'rfq_line_id' => $rfqLine1->id, 'quantity' => 150, 'unit_price' => 81000, 'tax_rate' => 11, 'line_total' => 150 * 81000 * 1.11]);
        SupplierQuotationLine::create(['supplier_quotation_id' => $sq2->id, 'rfq_line_id' => $rfqLine2->id, 'quantity' => 2500, 'unit_price' => 14000, 'tax_rate' => 11, 'line_total' => 2500 * 14000 * 1.11]);

        // --- Comparison: select SQ-001 ---
        $cmp = QuotationComparison::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'rfq_id' => $rfq->id,
            'number' => 'CMP-2026-000001', 'status' => 'approved',
            'selected_quotation_id' => $sq->id, 'decision_notes' => 'Harga SUPPL-001 lebih kompetitif.',
        ]);
        QuotationComparisonLine::create(['quotation_comparison_id' => $cmp->id, 'supplier_quotation_id' => $sq->id, 'evaluated_total' => $sq->total]);
        QuotationComparisonLine::create(['quotation_comparison_id' => $cmp->id, 'supplier_quotation_id' => $sq2->id, 'evaluated_total' => $sq2->total]);

        // --- Purchase Order from selected quotation ---
        $po = PurchaseOrder::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'supplier_id' => $this->suppliers[0],
            'purchase_request_id' => $pr1->id, 'supplier_quotation_id' => $sq->id,
            'number' => 'PO-2026-000001', 'currency' => 'IDR', 'order_date' => '2026-07-25', 'expected_date' => '2026-08-15',
            'payment_days' => $sq->payment_days, 'subtotal' => $sq->subtotal, 'tax_total' => $sq->tax_total, 'total' => $sq->total,
            'status' => 'approved',
        ]);
        $poLine1 = PurchaseOrderLine::create(['purchase_order_id' => $po->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 150, 'unit_price' => 78000, 'tax_rate' => 11, 'line_total' => 150 * 78000 * 1.11, 'received_quantity' => 0]);
        $poLine2 = PurchaseOrderLine::create(['purchase_order_id' => $po->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity' => 2500, 'unit_price' => 13500, 'tax_rate' => 11, 'line_total' => 2500 * 13500 * 1.11, 'received_quantity' => 0]);

        // --- Goods Receipt (partial, then QC then post) ---
        $gr = GoodsReceipt::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'warehouse_id' => $wh, 'purchase_order_id' => $po->id,
            'number' => 'GR-2026-000001', 'receipt_date' => '2026-08-03', 'status' => 'qc_completed',
        ]);
        $grLine1 = GoodsReceiptLine::create(['goods_receipt_id' => $gr->id, 'purchase_order_line_id' => $poLine1->id, 'quantity' => 150, 'accepted_quantity' => 0, 'rejected_quantity' => 0]);
        $grLine2 = GoodsReceiptLine::create(['goods_receipt_id' => $gr->id, 'purchase_order_line_id' => $poLine2->id, 'quantity' => 1000, 'accepted_quantity' => 0, 'rejected_quantity' => 0]);

        QualityCheck::create(['tenant_id' => $tn, 'goods_receipt_line_id' => $grLine1->id, 'result' => 'passed', 'accepted_quantity' => 150, 'rejected_quantity' => 0, 'checked_by' => $uid, 'checked_at' => now()]);
        QualityCheck::create(['tenant_id' => $tn, 'goods_receipt_line_id' => $grLine2->id, 'result' => 'passed', 'accepted_quantity' => 1000, 'rejected_quantity' => 0, 'checked_by' => $uid, 'checked_at' => now()]);

        // --- Purchase Invoice ---
        PurchaseInvoice::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'supplier_id' => $this->suppliers[0], 'purchase_order_id' => $po->id,
            'number' => 'PI-2026-000001', 'supplier_invoice_number' => 'INV/KBL/2026/089',
            'invoice_date' => '2026-08-03', 'due_date' => '2026-09-02',
            'subtotal' => $po->subtotal, 'tax_total' => $po->tax_total, 'total' => $po->total,
            'status' => 'matched', 'match_notes' => 'Sesuai PO 001 dan GR 001.',
        ]);

        // --- Supplier Payment ---
        $payment = Payment::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'number' => 'PAY-2026-000001', 'payment_type' => 'outgoing',
            'method' => 'bank', 'payment_date' => '2026-08-04', 'amount' => 50000000, 'status' => 'posted',
        ]);
        PaymentAllocation::create(['payment_id' => $payment->id, 'purchase_invoice_id' => PurchaseInvoice::where('number', 'PI-2026-000001')->value('id'), 'amount' => 50000000]);
    }

    private function seedSalesCycle(): void
    {
        echo "  ↳ Sales — SO → Delivery → Invoice → Receipt → Return → Credit Note...\n";

        $tn = $this->tenant;
        $cm = $this->companyMain;
        $br = $this->branchHQ;
        $wh = $this->warehouseMain;
        $uid = $this->adminId;

        // Stock needed for delivery — seed initial balances
        StockBalance::updateOrCreate(
            ['warehouse_id' => $wh, 'item_id' => $this->items['SKU-001']],
            ['tenant_id' => $tn, 'on_hand' => 500, 'reserved' => 0, 'average_cost' => 78000]
        );
        StockBalance::updateOrCreate(
            ['warehouse_id' => $wh, 'item_id' => $this->items['SKU-002']],
            ['tenant_id' => $tn, 'on_hand' => 3000, 'reserved' => 0, 'average_cost' => 13500]
        );
        StockBalance::updateOrCreate(
            ['warehouse_id' => $wh, 'item_id' => $this->items['SKU-003']],
            ['tenant_id' => $tn, 'on_hand' => 800, 'reserved' => 0, 'average_cost' => 43000]
        );
        StockBalance::updateOrCreate(
            ['warehouse_id' => $wh, 'item_id' => $this->items['SKU-007']],
            ['tenant_id' => $tn, 'on_hand' => 1200, 'reserved' => 0, 'average_cost' => 35000]
        );
        StockBalance::updateOrCreate(
            ['warehouse_id' => $this->warehouseEast, 'item_id' => $this->items['SKU-001']],
            ['tenant_id' => $tn, 'on_hand' => 80, 'reserved' => 0, 'average_cost' => 78000]
        );
        StockBalance::updateOrCreate(
            ['warehouse_id' => $this->warehouseEast, 'item_id' => $this->items['SKU-005']],
            ['tenant_id' => $tn, 'on_hand' => 200, 'reserved' => 0, 'average_cost' => 28000]
        );

        // --- Sales Order 1 ---
        $so = SalesOrder::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'customer_id' => $this->customers[0],
            'number' => 'SO-2026-000001', 'order_date' => '2026-08-01', 'required_date' => '2026-08-10',
            'subtotal' => 20 * 90000 + 200 * 18000, 'tax_total' => (20 * 90000 + 200 * 18000) * 0.11,
            'total' => (20 * 90000 + 200 * 18000) * 1.11, 'status' => 'confirmed',
        ]);
        $soLine1 = SalesOrderLine::create(['sales_order_id' => $so->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 20, 'unit_price' => 90000, 'tax_rate' => 11, 'line_total' => 20 * 90000 * 1.11, 'delivered_quantity' => 0]);
        $soLine2 = SalesOrderLine::create(['sales_order_id' => $so->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity' => 200, 'unit_price' => 18000, 'tax_rate' => 11, 'line_total' => 200 * 18000 * 1.11, 'delivered_quantity' => 0]);

        // --- Delivery (partial) ---
        $do = Delivery::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'warehouse_id' => $wh, 'sales_order_id' => $so->id,
            'number' => 'DO-2026-000001', 'delivery_date' => '2026-08-03', 'status' => 'posted',
        ]);
        DeliveryLine::create(['delivery_id' => $do->id, 'sales_order_line_id' => $soLine1->id, 'quantity' => 10]);
        DeliveryLine::create(['delivery_id' => $do->id, 'sales_order_line_id' => $soLine2->id, 'quantity' => 100]);
        $soLine1->update(['delivered_quantity' => 10]);
        $soLine2->update(['delivered_quantity' => 100]);

        // --- Sales Invoice ---
        $si = SalesInvoice::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'customer_id' => $this->customers[0], 'sales_order_id' => $so->id,
            'number' => 'SI-2026-000001', 'invoice_date' => '2026-08-03', 'due_date' => '2026-09-02',
            'subtotal' => $so->subtotal, 'tax_total' => $so->tax_total, 'total' => $so->total, 'status' => 'posted',
        ]);
        SalesInvoiceTaxLine::create(['sales_invoice_id' => $si->id, 'tax_code_id' => $this->taxCodes['PPN-11'], 'taxable_amount' => $so->subtotal, 'rate' => 11, 'tax_amount' => $so->tax_total]);

        // --- Customer Receipt ---
        $receipt = CustomerReceipt::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'customer_id' => $this->customers[0],
            'number' => 'REC-2026-000001', 'method' => 'bank', 'receipt_date' => '2026-08-04', 'amount' => 3000000, 'status' => 'posted',
        ]);
        CustomerReceiptAllocation::create(['customer_receipt_id' => $receipt->id, 'sales_invoice_id' => $si->id, 'amount' => 3000000]);

        // --- Sales Return ---
        $sret = SalesReturn::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'warehouse_id' => $wh,
            'sales_order_id' => $so->id, 'sales_invoice_id' => $si->id,
            'number' => 'RET-2026-000001', 'return_date' => '2026-08-04', 'status' => 'posted',
            'reason' => 'Kabel cacat gulungan — 1 roll rusak.',
        ]);
        SalesReturnLine::create(['sales_return_id' => $sret->id, 'sales_order_line_id' => $soLine1->id, 'quantity' => 1, 'unit_price' => 90000, 'line_total' => 90000]);

        // --- Credit Note ---
        CreditNote::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'customer_id' => $this->customers[0],
            'sales_invoice_id' => $si->id, 'sales_return_id' => $sret->id,
            'number' => 'CN-2026-000001', 'credit_date' => '2026-08-04',
            'subtotal' => $sret->lines->sum('line_total'), 'tax_total' => 0,
            'total' => $sret->lines->sum('line_total'), 'status' => 'posted',
            'reason' => 'Credit untuk retur RET-001.',
        ]);

        // --- Sales Order 2 (confirmed, not delivered) ---
        $so2 = SalesOrder::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'branch_id' => $br, 'customer_id' => $this->customers[2],
            'number' => 'SO-2026-000002', 'order_date' => '2026-08-02',
            'subtotal' => 50 * 150000 + 200 * 45000, 'tax_total' => (50 * 150000 + 200 * 45000) * 0.11,
            'total' => (50 * 150000 + 200 * 45000) * 1.11, 'status' => 'confirmed',
        ]);
        SalesOrderLine::create(['sales_order_id' => $so2->id, 'item_id' => $this->items['SKU-007'], 'unit_id' => $this->units['pcs'], 'quantity' => 50, 'unit_price' => 150000, 'tax_rate' => 11, 'line_total' => 50 * 150000 * 1.11, 'delivered_quantity' => 0]);
        SalesOrderLine::create(['sales_order_id' => $so2->id, 'item_id' => $this->items['SKU-003'], 'unit_id' => $this->units['pcs'], 'quantity' => 200, 'unit_price' => 45000, 'tax_rate' => 11, 'line_total' => 200 * 45000 * 1.11, 'delivered_quantity' => 0]);
    }

    private function seedStockOperations(): void
    {
        echo "  ↳ Stock — bins, transfers, adjustments, counts...\n";

        $tn = $this->tenant;
        $cm = $this->companyMain;
        $wh = $this->warehouseMain;
        $whE = $this->warehouseEast;

        // Warehouse Bins
        WarehouseBin::create(['warehouse_id' => $wh, 'code' => 'A-01', 'name' => 'Rak A Baris 1', 'status' => 'active']);
        WarehouseBin::create(['warehouse_id' => $wh, 'code' => 'A-02', 'name' => 'Rak A Baris 2', 'status' => 'active']);
        WarehouseBin::create(['warehouse_id' => $wh, 'code' => 'B-01', 'name' => 'Rak B Baris 1', 'status' => 'active']);
        WarehouseBin::create(['warehouse_id' => $whE, 'code' => 'E-01', 'name' => 'Area Timur', 'status' => 'active']);

        // Stock Transfer
        $trf = StockTransfer::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'source_warehouse_id' => $wh, 'destination_warehouse_id' => $whE,
            'number' => 'TRF-2026-000001', 'transfer_date' => '2026-08-02', 'status' => 'posted',
            'reason' => 'Relokasi stok ke gudang Surabaya',
        ]);
        StockTransferLine::create(['stock_transfer_id' => $trf->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 30]);
        StockTransferLine::create(['stock_transfer_id' => $trf->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity' => 200]);

        $trf2 = StockTransfer::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'source_warehouse_id' => $whE, 'destination_warehouse_id' => $wh,
            'number' => 'TRF-2026-000002', 'transfer_date' => '2026-08-03', 'status' => 'posted',
            'reason' => 'Pengembalian stok dari Surabaya',
        ]);
        StockTransferLine::create(['stock_transfer_id' => $trf2->id, 'item_id' => $this->items['SKU-001'], 'unit_id' => $this->units['roll'], 'quantity' => 5]);

        // Stock Adjustment
        $adj = StockAdjustment::create([
            'tenant_id' => $tn, 'company_id' => $cm, 'warehouse_id' => $wh,
            'number' => 'ADJ-2026-000001', 'adjustment_date' => '2026-08-03', 'status' => 'posted',
            'reason' => 'Koreksi stok setelah audit internal',
        ]);
        StockAdjustmentLine::create(['stock_adjustment_id' => $adj->id, 'item_id' => $this->items['SKU-002'], 'unit_id' => $this->units['pcs'], 'quantity_delta' => -5, 'unit_cost' => 13500]);

        // Stock Count
        $count = StockCount::create([
            'tenant_id' => $tn, 'warehouse_id' => $wh,
            'number' => 'CNT-2026-000001', 'count_type' => 'cycle', 'status' => 'open',
            'count_date' => '2026-08-03', 'reason' => 'Cycle count minggu pertama Agustus',
        ]);
        StockCountLine::create(['stock_count_id' => $count->id, 'item_id' => $this->items['SKU-001'], 'system_quantity' => 500]);
        StockCountLine::create(['stock_count_id' => $count->id, 'item_id' => $this->items['SKU-002'], 'system_quantity' => 3000]);
        StockCountLine::create(['stock_count_id' => $count->id, 'item_id' => $this->items['SKU-007'], 'system_quantity' => 1200]);
    }

    private function seedFinanceOperations(): void
    {
        echo "  ↳ Finance — bank, statements, journals...\n";

        $tn = $this->tenant;
        $cm = $this->companyMain;
        $uid = $this->adminId;

        // Bank accounts
        $ba = BankAccount::create([
            'tenant_id' => $tn, 'company_id' => $cm,
            'code' => 'BCA-OP', 'name' => 'BCA Operasional', 'bank_name' => 'Bank BCA',
            'account_number' => '0123456789', 'currency' => 'IDR', 'status' => 'active',
        ]);
        BankAccount::create([
            'tenant_id' => $tn, 'company_id' => $cm,
            'code' => 'MDR-OP', 'name' => 'Mandiri Operasional', 'bank_name' => 'Bank Mandiri',
            'account_number' => '9876543210', 'currency' => 'IDR', 'status' => 'active',
        ]);

        // Bank statement
        $bs = BankStatement::create([
            'tenant_id' => $tn, 'bank_account_id' => $ba->id,
            'statement_number' => 'BCA-2026-08-001', 'statement_date' => '2026-08-04',
            'opening_balance' => 150000000, 'closing_balance' => 155000000, 'status' => 'open',
        ]);
        BankStatementLine::create(['bank_statement_id' => $bs->id, 'transaction_date' => '2026-08-01', 'reference' => 'TRX-001', 'description' => 'Payment dari Toko Bangunan Jaya', 'amount' => 3000000, 'direction' => 'in', 'status' => 'matched', 'matched_payment_id' => Payment::where('number', 'PAY-2026-000001')->value('id')]);
        BankStatementLine::create(['bank_statement_id' => $bs->id, 'transaction_date' => '2026-08-02', 'reference' => 'TRX-002', 'description' => 'Biaya admin bulanan', 'amount' => 15000, 'direction' => 'out', 'status' => 'unmatched']);
        BankStatementLine::create(['bank_statement_id' => $bs->id, 'transaction_date' => '2026-08-03', 'reference' => 'TRX-003', 'description' => 'Pajak restoran', 'amount' => 250000, 'direction' => 'out', 'status' => 'unmatched']);

        // Manual Journal
        $jv = Journal::create([
            'tenant_id' => $tn, 'company_id' => $cm,
            'number' => 'JV-2026-000001', 'journal_date' => '2026-08-01',
            'description' => 'Biaya listrik bulan Juli', 'status' => 'posted',
        ]);
        JournalLine::create(['journal_id' => $jv->id, 'account_id' => $this->accounts['5-2000'], 'debit' => 4500000, 'credit' => 0]);
        JournalLine::create(['journal_id' => $jv->id, 'account_id' => $this->accounts['1-1000'], 'debit' => 0, 'credit' => 4500000]);

        $jv2 = Journal::create([
            'tenant_id' => $tn, 'company_id' => $cm,
            'number' => 'JV-2026-000002', 'journal_date' => '2026-08-02',
            'description' => 'Biaya internet kantor', 'status' => 'posted',
        ]);
        JournalLine::create(['journal_id' => $jv2->id, 'account_id' => $this->accounts['5-2000'], 'debit' => 750000, 'credit' => 0]);
        JournalLine::create(['journal_id' => $jv2->id, 'account_id' => $this->accounts['1-1000'], 'debit' => 0, 'credit' => 750000]);

        // Import/Report/Integration jobs
        ImportJob::create(['tenant_id' => $tn, 'requested_by' => $uid, 'import_type' => 'item_import', 'file_path' => 'imports/item-20260801.xlsx', 'status' => 'queued', 'total_rows' => 200, 'processed_rows' => 0]);
        ImportJob::create(['tenant_id' => $tn, 'requested_by' => $uid, 'import_type' => 'party_import', 'file_path' => 'imports/party-20260801.xlsx', 'status' => 'completed', 'total_rows' => 45, 'processed_rows' => 45]);
        ReportJob::create(['tenant_id' => $tn, 'requested_by' => $uid, 'report_key' => 'trial_balance', 'format' => 'xlsx', 'filters' => ['year' => 2026, 'month' => 8], 'status' => 'completed', 'file_path' => 'reports/trial-balance-202608.xlsx']);
        ReportJob::create(['tenant_id' => $tn, 'requested_by' => $uid, 'report_key' => 'ar_aging', 'format' => 'pdf', 'status' => 'queued']);
        IntegrationLog::create(['tenant_id' => $tn, 'provider' => 'marketplace', 'direction' => 'inbound', 'idempotency_key' => 'mp-order-001', 'status' => 'queued', 'attempts' => 0, 'request_payload' => ['order_id' => 'MP-8863']]);
        IntegrationLog::create(['tenant_id' => $tn, 'provider' => 'marketplace', 'direction' => 'outbound', 'idempotency_key' => 'mp-ship-001', 'status' => 'queued', 'attempts' => 1]);
    }

    private function seedPlatformData(): void
    {
        echo "  ↳ Platform — documents, workflow, notifications...\n";

        $tn = $this->tenant;
        $uid = $this->adminId;

        // Documents
        $doc = Document::create([
            'tenant_id' => $tn,
            'entity_type' => 'App\\Models\\PurchaseOrder', 'entity_id' => PurchaseOrder::where('number', 'PO-2026-000001')->value('id'),
            'document_type' => 'specification', 'title' => 'Spesifikasi Kabel NYA', 'status' => 'active',
        ]);
        Document::create([
            'tenant_id' => $tn,
            'entity_type' => 'App\\Models\\SalesOrder', 'entity_id' => SalesOrder::where('number', 'SO-2026-000001')->value('id'),
            'document_type' => 'contract', 'title' => 'Kontrak penjualan SO-001', 'status' => 'active',
        ]);

        $attachments = [
            ['document_id' => $doc->id, 'file_name' => 'kabel-nya-spec.pdf', 'storage_path' => 'documents/kabel-nya-spec.pdf', 'mime_type' => 'application/pdf', 'size' => 245000, 'sha256' => hash('sha256', 'spec-pdf')],
            ['document_id' => $doc->id, 'file_name' => 'kabel-nya-photo.jpg', 'storage_path' => 'documents/kabel-nya-photo.jpg', 'mime_type' => 'image/jpeg', 'size' => 180000],
        ];
        foreach ($attachments as $attachment) {
            DB::table('attachments')->insert([...$attachment, 'id' => (string) Str::uuid(), 'tenant_id' => $tn, 'scan_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        }

        // Workflow
        $wf = WorkflowDefinition::create([
            'tenant_id' => $tn, 'entity_type' => 'purchase_request',
            'name' => 'PR Approval Standard', 'steps' => [
                ['approver_id' => $uid],
                ['approver_id' => User::where('email', 'budi@nusantara.id')->value('id')],
            ], 'active' => true,
        ]);

        WorkflowDefinition::create([
            'tenant_id' => $tn, 'entity_type' => 'sales_order',
            'name' => 'SO > 100jt Approval', 'steps' => [
                ['approver_id' => User::where('email', 'sari@nusantara.id')->value('id')],
            ], 'active' => true,
        ]);

        $wfi = WorkflowInstance::create([
            'tenant_id' => $tn, 'definition_id' => $wf->id,
            'entity_type' => 'purchase_request', 'entity_id' => PurchaseRequest::where('number', 'PR-2026-000001')->value('id'),
            'status' => 'pending', 'current_step' => 0,
        ]);
        DB::table('approvals')->insert([
            'id' => (string) Str::uuid(), 'tenant_id' => $tn, 'workflow_instance_id' => $wfi->id,
            'approver_id' => $uid, 'step' => 0, 'decision' => 'pending',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Notifications
        Notification::create([
            'tenant_id' => $tn, 'recipient_id' => $uid,
            'type' => 'approval.requested', 'title' => 'PR-2026-000001 menunggu approval',
            'body' => 'Anda ditugaskan sebagai approver untuk purchase request pertama.', 'status' => 'unread',
            'data' => ['entity_type' => 'purchase_request', 'entity_id' => 'PR-2026-000001'],
        ]);
        Notification::create([
            'tenant_id' => $tn, 'recipient_id' => $uid,
            'type' => 'system.alert', 'title' => 'QC Receipt GR-001 selesai',
            'body' => 'Hasil quality check: semua line passed. Siap diposting.', 'status' => 'read', 'read_at' => now(),
        ]);
        Notification::create([
            'tenant_id' => $tn, 'recipient_id' => User::where('email', 'budi@nusantara.id')->value('id'),
            'type' => 'approval.requested', 'title' => 'PR-2026-000001 step 2',
            'body' => 'Dokumen telah disetujui approver pertama. Menunggu keputusan Anda.', 'status' => 'unread',
        ]);
    }
}
