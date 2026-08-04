<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Approval;
use App\Models\ImportJob;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Opportunity;
use App\Models\Party;
use App\Models\Project;
use App\Models\ReportJob;
use App\Models\ServiceTicket;
use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlatformController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function createWorkflow(Request $request): JsonResponse
    {
        $data = $request->validate(['entity_type' => ['required', 'string', 'max:120'], 'name' => ['required', 'string', 'max:255'], 'steps' => ['required', 'array', 'min:1'], 'steps.*.approver_id' => ['required', 'uuid']]);
        $workflow = WorkflowDefinition::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'active' => true]);
        $this->audit->record($request, 'created', $workflow);

        return response()->json(['data' => $workflow], 201);
    }

    public function startApproval(Request $request): JsonResponse
    {
        $data = $request->validate(['definition_id' => ['required', 'uuid'], 'entity_type' => ['required', 'string'], 'entity_id' => ['required', 'uuid']]);
        $definition = WorkflowDefinition::where('tenant_id', $request->user()->tenant_id)->whereKey($data['definition_id'])->where('active', true)->firstOrFail();
        $instance = DB::transaction(function () use ($data, $request, $definition): WorkflowInstance {
            $i = WorkflowInstance::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'status' => 'pending', 'current_step' => 0]);
            $step = $definition->steps[0];
            $approval = $i->approvals()->create(['tenant_id' => $request->user()->tenant_id, 'approver_id' => $step['approver_id'], 'step' => 0, 'decision' => 'pending']);
            Notification::create(['tenant_id' => $request->user()->tenant_id, 'recipient_id' => $step['approver_id'], 'type' => 'approval.requested', 'title' => 'Approval required', 'body' => 'Dokumen membutuhkan approval.', 'status' => 'unread', 'data' => ['workflow_instance_id' => $i->id]]);
            $this->audit->record($request, 'started', $i);

            return $i;
        });

        return response()->json(['data' => $instance->load('approvals')], 201);
    }

    public function decideApproval(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['decision' => ['required', 'in:approved,rejected'], 'comment' => ['sometimes', 'nullable', 'string']]);
        $approval = Approval::where('tenant_id', $request->user()->tenant_id)->whereKey($id)->where('approver_id', $request->user()->id)->where('decision', 'pending')->firstOrFail();
        $instance = $approval->workflowInstance;
        $definition = WorkflowDefinition::findOrFail($instance->definition_id);
        $approval->update([...$data, 'decided_at' => now()]);
        $next = $instance->current_step + 1;
        if ($data['decision'] === 'rejected') {
            $instance->update(['status' => 'rejected']);
        } elseif (isset($definition->steps[$next])) {
            $instance->update(['current_step' => $next]);
            $step = $definition->steps[$next];
            $instance->approvals()->create(['tenant_id' => $instance->tenant_id, 'approver_id' => $step['approver_id'], 'step' => $next, 'decision' => 'pending']);
            Notification::create(['tenant_id' => $instance->tenant_id, 'recipient_id' => $step['approver_id'], 'type' => 'approval.requested', 'title' => 'Approval required', 'body' => 'Dokumen membutuhkan approval.', 'status' => 'unread', 'data' => ['workflow_instance_id' => $instance->id]]);
        } else {
            $instance->update(['status' => 'approved']);
        }$this->audit->record($request, 'decided', $approval);

        return response()->json(['data' => $instance->fresh()->load('approvals')]);
    }

    public function notifications(Request $request): JsonResponse
    {
        return response()->json(Notification::where('tenant_id', $request->user()->tenant_id)->where('recipient_id', $request->user()->id)->latest()->paginate(20));
    }

    public function readNotification(Request $request, string $id): JsonResponse
    {
        $n = Notification::where('tenant_id', $request->user()->tenant_id)->where('recipient_id', $request->user()->id)->findOrFail($id);
        $n->update(['status' => 'read', 'read_at' => now()]);

        return response()->json(['data' => $n]);
    }

    public function storeLead(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string'], 'email' => ['sometimes', 'nullable', 'email'], 'phone' => ['sometimes', 'nullable', 'string'], 'source' => ['sometimes', 'nullable', 'string']]);
        $lead = Lead::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'owner_id' => $request->user()->id, 'status' => 'new']);
        $this->audit->record($request, 'created', $lead);

        return response()->json(['data' => $lead], 201);
    }

    public function storeOpportunity(Request $request): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string'], 'customer_id' => ['sometimes', 'nullable', 'uuid'], 'expected_value' => ['sometimes', 'numeric', 'min:0'], 'probability' => ['sometimes', 'integer', 'between:0,100'], 'expected_close_date' => ['sometimes', 'nullable', 'date']]);
        $o = Opportunity::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'owner_id' => $request->user()->id]);
        $this->audit->record($request, 'created', $o);

        return response()->json(['data' => $o], 201);
    }

    public function storeActivity(Request $request): JsonResponse
    {
        $data = $request->validate(['subject' => ['required', 'string'], 'activity_type' => ['required', 'in:call,email,meeting,task,note'], 'due_at' => ['sometimes', 'nullable', 'date'], 'notes' => ['sometimes', 'nullable', 'string'], 'related_type' => ['sometimes', 'nullable', 'string'], 'related_id' => ['sometimes', 'nullable', 'uuid']]);
        $a = Activity::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'user_id' => $request->user()->id]);
        $this->audit->record($request, 'created', $a);

        return response()->json(['data' => $a], 201);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'code' => ['required', 'string'], 'name' => ['required', 'string'], 'start_date' => ['sometimes', 'nullable', 'date'], 'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'], 'budget' => ['sometimes', 'numeric', 'min:0']]);
        $p = Project::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'status' => 'planned']);
        $this->audit->record($request, 'created', $p);

        return response()->json(['data' => $p], 201);
    }

    public function storeTask(Request $request, string $projectId): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string'], 'assignee_id' => ['sometimes', 'nullable', 'uuid'], 'due_date' => ['sometimes', 'nullable', 'date']]);
        $p = Project::where('tenant_id', $request->user()->tenant_id)->findOrFail($projectId);
        $t = $p->tasks()->create([...$data, 'status' => 'todo', 'progress' => 0]);
        $this->audit->record($request, 'created', $t);

        return response()->json(['data' => $t], 201);
    }

    public function storeTicket(Request $request): JsonResponse
    {
        $data = $request->validate(['customer_id' => ['required', 'uuid'], 'subject' => ['required', 'string'], 'priority' => ['sometimes', 'in:low,normal,high,urgent'], 'description' => ['sometimes', 'nullable', 'string']]);
        Party::where('tenant_id', $request->user()->tenant_id)->whereKey($data['customer_id'])->whereIn('type', ['customer', 'both'])->where('status', 'active')->firstOrFail();
        $ticket = ServiceTicket::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'number' => $this->number($request->user()->tenant_id), 'status' => 'open']);
        $this->audit->record($request, 'created', $ticket);

        return response()->json(['data' => $ticket], 201);
    }

    public function createReportJob(Request $request): JsonResponse
    {
        $data = $request->validate(['report_key' => ['required', 'string'], 'format' => ['required', 'in:xlsx,csv,pdf'], 'filters' => ['sometimes', 'array']]);
        $job = ReportJob::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'requested_by' => $request->user()->id, 'status' => 'queued']);

        return response()->json(['data' => $job], 202);
    }

    public function createImportJob(Request $request): JsonResponse
    {
        $data = $request->validate(['import_type' => ['required', 'string'], 'file_path' => ['required', 'string']]);
        $job = ImportJob::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'requested_by' => $request->user()->id, 'status' => 'queued']);

        return response()->json(['data' => $job], 202);
    }

    private function number(string $tenant): string
    {
        return sprintf('TCK-%s-%06d', now()->format('Y'), ServiceTicket::where('tenant_id', $tenant)->count() + 1);
    }
}
