<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Item;
use App\Models\PurchaseRequest;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseRequestController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = PurchaseRequest::query()
            ->with(['lines.item', 'lines.unit', 'company', 'branch'])
            ->where('tenant_id', $request->user()->tenant_id)
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->latest('request_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $purchaseRequest = $this->ownedQuery($request)->with(['lines.item', 'lines.unit', 'company', 'branch'])->findOrFail($id);

        return response()->json(['data' => $purchaseRequest]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'uuid'],
            'branch_id' => ['required', 'uuid'],
            'request_date' => ['required', 'date'],
            'required_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:request_date'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'uuid'],
            'lines.*.unit_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.estimated_unit_price' => ['sometimes', 'numeric', 'min:0'],
            'lines.*.notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $tenantId = $request->user()->tenant_id;
        $company = Company::query()->where('tenant_id', $tenantId)->whereKey($data['company_id'])->where('status', 'active')->firstOrFail();
        $branch = Branch::query()->where('company_id', $company->id)->whereKey($data['branch_id'])->where('status', 'active')->firstOrFail();
        $itemIds = collect($data['lines'])->pluck('item_id')->unique();
        $unitIds = collect($data['lines'])->pluck('unit_id')->unique();

        abort_unless(Item::query()->where('tenant_id', $tenantId)->whereIn('id', $itemIds)->where('status', 'active')->count() === $itemIds->count(), 422, 'Item tidak valid atau tidak aktif.');
        abort_unless(Unit::query()->where('tenant_id', $tenantId)->whereIn('id', $unitIds)->where('status', 'active')->count() === $unitIds->count(), 422, 'Unit tidak valid atau tidak aktif.');

        $purchaseRequest = DB::transaction(function () use ($data, $request, $tenantId, $company, $branch): PurchaseRequest {
            $purchaseRequest = PurchaseRequest::create([
                'tenant_id' => $tenantId,
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'requester_id' => $request->user()->id,
                'number' => $this->nextNumber($company->id),
                'request_date' => $data['request_date'],
                'required_date' => $data['required_date'] ?? null,
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'estimated_total' => collect($data['lines'])->sum(fn (array $line) => ($line['quantity'] * ($line['estimated_unit_price'] ?? 0))),
            ]);

            $purchaseRequest->lines()->createMany($data['lines']);
            $this->audit->record($request, 'created', $purchaseRequest);

            return $purchaseRequest;
        });

        return response()->json(['data' => $purchaseRequest->load(['lines.item', 'lines.unit'])], 201);
    }

    public function submit(Request $request, string $id): JsonResponse
    {
        $purchaseRequest = $this->ownedQuery($request)->with('lines')->findOrFail($id);
        abort_unless($purchaseRequest->status === 'draft', 422, 'Hanya PR draft yang dapat disubmit.');
        abort_unless($purchaseRequest->lines->isNotEmpty(), 422, 'PR harus memiliki minimal satu line.');
        $before = $purchaseRequest->toArray();

        DB::transaction(function () use ($purchaseRequest, $request, $before): void {
            $purchaseRequest->update(['status' => 'submitted']);
            $this->audit->record($request, 'submitted', $purchaseRequest, $before);
        });

        return response()->json(['data' => $purchaseRequest->fresh()->load(['lines.item', 'lines.unit'])]);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $purchaseRequest = $this->ownedQuery($request)->findOrFail($id);
        abort_unless($purchaseRequest->status === 'draft', 422, 'Hanya PR draft yang dapat diedit.');
        $data = $request->validate([
            'request_date' => ['sometimes', 'date'],
            'required_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:request_date'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);
        $before = $purchaseRequest->toArray();
        $purchaseRequest->update($data);
        $this->audit->record($request, 'updated', $purchaseRequest, $before);

        return response()->json(['data' => $purchaseRequest->fresh()->load(['lines.item', 'lines.unit'])]);
    }

    public function cancel(Request $request, string $id): JsonResponse
    {
        $purchaseRequest = $this->ownedQuery($request)->findOrFail($id);
        abort_unless(in_array($purchaseRequest->status, ['draft', 'submitted'], true), 422, 'PR tidak dapat dibatalkan pada status ini.');
        $before = $purchaseRequest->toArray();

        DB::transaction(function () use ($purchaseRequest, $request, $before): void {
            $purchaseRequest->update(['status' => 'cancelled']);
            $this->audit->record($request, 'cancelled', $purchaseRequest, $before);
        });

        return response()->json(['data' => $purchaseRequest->fresh()]);
    }

    private function ownedQuery(Request $request): Builder
    {
        return PurchaseRequest::query()->where('tenant_id', $request->user()->tenant_id);
    }

    private function nextNumber(string $companyId): string
    {
        $sequence = PurchaseRequest::query()->where('company_id', $companyId)->lockForUpdate()->count() + 1;

        do {
            $number = sprintf('PR-%s-%06d', now()->format('Y'), $sequence++);
        } while (PurchaseRequest::query()->where('company_id', $companyId)->where('number', $number)->exists());

        return $number;
    }
}
