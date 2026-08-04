<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Approval;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\CreditNote;
use App\Models\CustomerReceipt;
use App\Models\Delivery;
use App\Models\Document;
use App\Models\FiscalPeriod;
use App\Models\ImportJob;
use App\Models\IntegrationLog;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\QuotationComparison;
use App\Models\ReportJob;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\ServiceTicket;
use App\Models\StockAdjustment;
use App\Models\StockCount;
use App\Models\StockTransfer;
use App\Models\WarehouseBin;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkspaceDataController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, string $resource): JsonResponse
    {
        $query = $this->ownedQuery($request, $resource);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $columns = $this->searchColumns($resource);
            $query->where(fn (Builder $builder) => collect($columns)->each(
                fn (string $column, int $index) => $index === 0
                    ? $builder->where($column, 'like', "%{$search}%")
                    : $builder->orWhere($column, 'like', "%{$search}%")
            ));
        }

        return response()->json($query->latest()->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function show(Request $request, string $resource, string $id): JsonResponse
    {
        return response()->json(['data' => $this->ownedQuery($request, $resource)->findOrFail($id)]);
    }

    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        $entity = $this->ownedQuery($request, $resource)->findOrFail($id);
        $validated = $request->validate($this->updateRules($resource));
        $before = $entity->toArray();
        DB::transaction(function () use ($entity, $validated, $request, $before): void {
            $entity->update($validated);
            $this->audit->record($request, 'updated', $entity, $before);
        });

        return response()->json(['data' => $entity->fresh()]);
    }

    private function ownedQuery(Request $request, string $resource): Builder
    {
        [$model, $relations] = $this->definition($resource);
        $query = $model::query()->with($relations);
        if ($resource === 'warehouse-bins') {
            return $query->whereHas('warehouse.branch.company', fn (Builder $builder) => $builder->where('tenant_id', $request->user()->tenant_id));
        }
        $query->where('tenant_id', $request->user()->tenant_id);
        if (in_array($resource, ['fiscal-periods', 'accounts', 'bank-accounts'], true)) {
            $query->where('company_id', $request->user()->company_id);
        }

        return $query;
    }

    private function definition(string $resource): array
    {
        return match ($resource) {
            'quotation-comparisons' => [QuotationComparison::class, ['lines']],
            'sales-orders' => [SalesOrder::class, ['lines', 'customer']],
            'deliveries' => [Delivery::class, ['lines']],
            'sales-invoices' => [SalesInvoice::class, ['taxLines']],
            'customer-receipts' => [CustomerReceipt::class, ['allocations']],
            'sales-returns' => [SalesReturn::class, ['lines']],
            'credit-notes' => [CreditNote::class, []],
            'stock-transfers' => [StockTransfer::class, ['lines']],
            'stock-adjustments' => [StockAdjustment::class, ['lines']],
            'warehouse-bins' => [WarehouseBin::class, ['warehouse.branch.company']],
            'stock-counts' => [StockCount::class, ['lines']],
            'fiscal-periods' => [FiscalPeriod::class, []],
            'bank-accounts' => [BankAccount::class, []],
            'bank-statements' => [BankStatement::class, ['lines']],
            'leads' => [Lead::class, []],
            'opportunities' => [Opportunity::class, []],
            'activities' => [Activity::class, []],
            'projects' => [Project::class, ['tasks']],
            'service-tickets' => [ServiceTicket::class, []],
            'workflow-definitions' => [WorkflowDefinition::class, []],
            'workflow-instances' => [WorkflowInstance::class, ['approvals']],
            'approvals' => [Approval::class, []],
            'documents' => [Document::class, ['attachments']],
            'integrations' => [IntegrationLog::class, []],
            'report-jobs' => [ReportJob::class, []],
            'import-jobs' => [ImportJob::class, []],
            'accounts' => [Account::class, []],
            default => abort(404, 'Workspace resource tidak ditemukan.'),
        };
    }

    private function searchColumns(string $resource): array
    {
        return match ($resource) {
            'warehouse-bins', 'bank-accounts', 'projects', 'accounts' => ['code', 'name'],
            'leads', 'opportunities' => ['name'],
            'activities', 'service-tickets' => ['subject'],
            'workflow-definitions' => ['name', 'entity_type'],
            'documents' => ['title', 'document_type'],
            'integrations' => ['provider', 'idempotency_key'],
            'report-jobs' => ['report_key'],
            'import-jobs' => ['import_type', 'file_path'],
            default => ['number'],
        };
    }

    private function updateRules(string $resource): array
    {
        return match ($resource) {
            'warehouse-bins' => ['code' => ['sometimes', 'string', 'max:80'], 'name' => ['sometimes', 'string'], 'status' => ['sometimes', 'in:active,inactive']],
            'bank-accounts' => ['code' => ['sometimes', 'string'], 'name' => ['sometimes', 'string'], 'bank_name' => ['sometimes', 'nullable', 'string'], 'account_number' => ['sometimes', 'nullable', 'string'], 'currency' => ['sometimes', 'string', 'size:3'], 'status' => ['sometimes', 'in:active,inactive']],
            'accounts' => ['code' => ['sometimes', 'string', 'max:50'], 'name' => ['sometimes', 'string', 'max:255'], 'parent_id' => ['sometimes', 'nullable', 'uuid'], 'status' => ['sometimes', 'in:active,inactive']],
            'leads' => ['name' => ['sometimes', 'string'], 'email' => ['sometimes', 'nullable', 'email'], 'phone' => ['sometimes', 'nullable', 'string'], 'source' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:new,qualified,converted,lost']],
            'opportunities' => ['name' => ['sometimes', 'string'], 'expected_value' => ['sometimes', 'numeric', 'min:0'], 'probability' => ['sometimes', 'integer', 'between:0,100'], 'expected_close_date' => ['sometimes', 'nullable', 'date'], 'stage' => ['sometimes', 'in:new,qualified,proposal,won,lost'], 'lost_reason' => ['sometimes', 'nullable', 'string']],
            'activities' => ['subject' => ['sometimes', 'string'], 'activity_type' => ['sometimes', 'in:call,email,meeting,task,note'], 'due_at' => ['sometimes', 'nullable', 'date'], 'notes' => ['sometimes', 'nullable', 'string'], 'status' => ['sometimes', 'in:open,completed,cancelled']],
            'projects' => ['code' => ['sometimes', 'string'], 'name' => ['sometimes', 'string'], 'start_date' => ['sometimes', 'nullable', 'date'], 'end_date' => ['sometimes', 'nullable', 'date'], 'budget' => ['sometimes', 'numeric', 'min:0'], 'status' => ['sometimes', 'in:planned,active,completed,cancelled']],
            'service-tickets' => ['subject' => ['sometimes', 'string'], 'priority' => ['sometimes', 'in:low,normal,high,urgent'], 'description' => ['sometimes', 'nullable', 'string'], 'assignee_id' => ['sometimes', 'nullable', 'uuid'], 'status' => ['sometimes', 'in:open,in_progress,resolved,closed']],
            'documents' => ['document_type' => ['sometimes', 'string'], 'title' => ['sometimes', 'string'], 'status' => ['sometimes', 'in:active,inactive']],
            default => abort(405, 'Resource ini immutable dan tidak dapat diedit.'),
        };
    }
}
