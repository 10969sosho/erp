<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Party;
use App\Models\TaxCode;
use App\Models\Unit;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterDataController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request, string $resource): JsonResponse
    {
        $model = $this->model($resource);
        $query = $model::query()->where('tenant_id', $request->user()->tenant_id);

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            [$codeColumn, $nameColumn] = match ($resource) {
                'items' => ['sku', 'name'],
                'parties' => ['code', 'legal_name'],
                default => ['code', 'name'],
            };
            $query->where(function ($builder) use ($search, $codeColumn, $nameColumn): void {
                $builder->where($codeColumn, 'like', "%{$search}%")->orWhere($nameColumn, 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($resource === 'parties' && $request->filled('type')) {
            $type = $request->string('type')->toString();
            $query->whereIn('type', $type === 'customer' ? ['customer', 'both'] : ($type === 'supplier' ? ['supplier', 'both'] : [$type]));
        }

        return response()->json($query->orderBy('code')->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function show(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($resource);

        return response()->json(['data' => $model::query()->where('tenant_id', $request->user()->tenant_id)->findOrFail($id)]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        $validated = $request->validate($this->rules($resource));
        $this->validateScopedReferences($request, $resource, $validated);
        $model = $this->model($resource);
        $entity = DB::transaction(function () use ($model, $validated, $request): Model {
            $entity = $model::create([...$validated, 'tenant_id' => $request->user()->tenant_id]);
            $this->audit->record($request, 'created', $entity);

            return $entity;
        });

        return response()->json(['data' => $entity], 201);
    }

    public function update(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($resource);
        $entity = $model::query()->where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        $validated = $request->validate($this->rules($resource, true));
        $this->validateScopedReferences($request, $resource, $validated);
        $before = $entity->toArray();

        DB::transaction(function () use ($entity, $validated, $request, $before): void {
            $entity->update($validated);
            $this->audit->record($request, 'updated', $entity, $before);
        });

        return response()->json(['data' => $entity->fresh()]);
    }

    public function archive(Request $request, string $resource, string $id): JsonResponse
    {
        $model = $this->model($resource);
        $entity = $model::query()->where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        $before = $entity->toArray();

        DB::transaction(function () use ($entity, $request, $before): void {
            $entity->update(['status' => 'inactive']);
            $this->audit->record($request, 'archived', $entity, $before);
        });

        return response()->json(['data' => $entity->fresh()]);
    }

    private function model(string $resource): string
    {
        return match ($resource) {
            'units' => Unit::class,
            'items' => Item::class,
            'parties' => Party::class,
            'tax-codes' => TaxCode::class,
            default => abort(404, 'Master data resource tidak ditemukan.'),
        };
    }

    private function rules(string $resource, bool $update = false): array
    {
        $required = $update ? 'sometimes' : 'required';

        return match ($resource) {
            'units' => [
                'code' => [$required, 'string', 'max:30'], 'name' => [$required, 'string', 'max:255'],
                'precision' => [$required, 'integer', 'between:0,6'], 'status' => ['sometimes', 'in:active,inactive'],
            ],
            'items' => [
                'sku' => [$required, 'string', 'max:100'], 'name' => [$required, 'string', 'max:255'],
                'type' => ['sometimes', 'in:stock,service,asset'], 'base_unit_id' => [$required, 'uuid'],
                'lot_tracking' => ['sometimes', 'boolean'], 'serial_tracking' => ['sometimes', 'boolean'],
                'expiry_tracking' => ['sometimes', 'boolean'], 'minimum_price' => ['sometimes', 'numeric', 'min:0'],
                'status' => ['sometimes', 'in:active,inactive'], 'metadata' => ['sometimes', 'array'],
            ],
            'parties' => [
                'code' => [$required, 'string', 'max:100'], 'type' => [$required, 'in:customer,supplier,both,person'],
                'legal_name' => [$required, 'string', 'max:255'], 'tax_id' => ['sometimes', 'nullable', 'string', 'max:100'],
                'email' => ['sometimes', 'nullable', 'email'], 'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
                'credit_limit' => ['sometimes', 'numeric', 'min:0'], 'status' => ['sometimes', 'in:active,inactive'],
            ],
            'tax-codes' => [
                'code' => [$required, 'string', 'max:50'], 'name' => [$required, 'string', 'max:255'],
                'rate' => [$required, 'numeric', 'between:0,100'], 'effective_from' => [$required, 'date'],
                'effective_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:effective_from'],
                'status' => ['sometimes', 'in:active,inactive'],
            ],
        };
    }

    private function validateScopedReferences(Request $request, string $resource, array $validated): void
    {
        if ($resource === 'items' && isset($validated['base_unit_id'])) {
            abort_unless(
                Unit::query()->where('tenant_id', $request->user()->tenant_id)->where('status', 'active')->whereKey($validated['base_unit_id'])->exists(),
                422,
                'Base unit tidak valid atau tidak aktif.',
            );
        }
    }
}
