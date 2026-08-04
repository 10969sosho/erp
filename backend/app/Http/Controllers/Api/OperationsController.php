<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Document;
use App\Models\IntegrationLog;
use App\Models\Item;
use App\Models\Payment;
use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseBin;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperationsController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function createBin(Request $request): JsonResponse
    {
        $data = $request->validate(['warehouse_id' => ['required', 'uuid'], 'code' => ['required', 'string', 'max:80'], 'name' => ['required', 'string']]);
        Warehouse::whereKey($data['warehouse_id'])->where('status', 'active')->whereHas('branch', fn ($query) => $query->where('company_id', $request->user()->company_id))->firstOrFail();
        $bin = WarehouseBin::create([...$data, 'status' => 'active']);

        return response()->json(['data' => $bin], 201);
    }

    public function createCount(Request $request): JsonResponse
    {
        $data = $request->validate(['warehouse_id' => ['required', 'uuid'], 'count_type' => ['required', 'in:cycle,opname'], 'count_date' => ['required', 'date'], 'reason' => ['sometimes', 'nullable', 'string'], 'item_ids' => ['required', 'array', 'min:1'], 'item_ids.*' => ['required', 'uuid']]);
        $tenant = $request->user()->tenant_id;
        $count = DB::transaction(function () use ($data, $request, $tenant): StockCount {
            $c = StockCount::create(['tenant_id' => $tenant, 'warehouse_id' => $data['warehouse_id'], 'number' => $this->number(StockCount::class, 'CNT', $tenant), 'count_type' => $data['count_type'], 'count_date' => $data['count_date'], 'status' => 'open', 'reason' => $data['reason'] ?? null]);
            foreach (Item::where('tenant_id', $tenant)->whereIn('id', $data['item_ids'])->get() as $item) {
                $balance = StockBalance::where('warehouse_id', $data['warehouse_id'])->where('item_id', $item->id)->first();
                $c->lines()->create(['item_id' => $item->id, 'system_quantity' => $balance?->on_hand ?? 0]);
            }$this->audit->record($request, 'created', $c);

            return $c;
        });

        return response()->json(['data' => $count->load('lines')], 201);
    }

    public function submitCount(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['lines' => ['required', 'array', 'min:1'], 'lines.*.id' => ['required', 'uuid'], 'lines.*.counted_quantity' => ['required', 'numeric', 'gte:0']]);
        $count = StockCount::where('tenant_id', $request->user()->tenant_id)->whereKey($id)->with('lines')->firstOrFail();
        abort_unless($count->status === 'open', 422, 'Stock count tidak open.');
        $map = collect($data['lines'])->keyBy('id');
        DB::transaction(function () use ($count, $map, $request): void {
            foreach ($count->lines as $line) {
                $input = $map->get($line->id);
                abort_unless($input, 422, 'Semua count line wajib diisi.');
                $line->update(['counted_quantity' => $input['counted_quantity'], 'variance' => (float) $input['counted_quantity'] - (float) $line->system_quantity]);
            }
            foreach ($count->lines as $line) {
                $variance = (float) $line->variance;
                if (abs($variance) < 0.000001) {
                    continue;
                }
                $balance = StockBalance::where('warehouse_id', $count->warehouse_id)->where('item_id', $line->item_id)->lockForUpdate()->first();
                if (! $balance) {
                    $balance = StockBalance::create(['tenant_id' => $count->tenant_id, 'warehouse_id' => $count->warehouse_id, 'item_id' => $line->item_id, 'on_hand' => 0, 'reserved' => 0, 'average_cost' => 0]);
                }
                abort_unless((float) $balance->on_hand + $variance >= 0, 422, 'Stock opname menyebabkan stock minus.');
                $balance->update(['on_hand' => (float) $balance->on_hand + $variance]);
                $item = Item::findOrFail($line->item_id);
                StockMovement::create(['tenant_id' => $count->tenant_id, 'company_id' => Warehouse::findOrFail($count->warehouse_id)->branch->company_id, 'warehouse_id' => $count->warehouse_id, 'item_id' => $line->item_id, 'unit_id' => $item->base_unit_id, 'movement_type' => 'stock_count', 'direction' => $variance > 0 ? 'in' : 'out', 'quantity' => abs($variance), 'unit_cost' => $balance->average_cost, 'source_type' => StockCount::class, 'source_id' => $count->id, 'occurred_at' => now()]);
            }
            $count->update(['status' => 'posted']);
            $this->audit->record($request, 'submitted', $count);
        });

        return response()->json(['data' => $count->fresh()->load('lines')]);
    }

    public function createBankAccount(Request $request): JsonResponse
    {
        $data = $request->validate(['company_id' => ['required', 'uuid'], 'code' => ['required', 'string'], 'name' => ['required', 'string'], 'bank_name' => ['sometimes', 'nullable', 'string'], 'account_number' => ['sometimes', 'nullable', 'string'], 'currency' => ['sometimes', 'size:3']]);
        $account = BankAccount::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'status' => 'active']);

        return response()->json(['data' => $account], 201);
    }

    public function createStatement(Request $request): JsonResponse
    {
        $data = $request->validate(['bank_account_id' => ['required', 'uuid'], 'statement_number' => ['required', 'string'], 'statement_date' => ['required', 'date'], 'opening_balance' => ['numeric'], 'closing_balance' => ['numeric'], 'lines' => ['required', 'array']]);
        $account = BankAccount::where('tenant_id', $request->user()->tenant_id)->whereKey($data['bank_account_id'])->firstOrFail();
        $statement = DB::transaction(function () use ($data, $request, $account): BankStatement {
            $s = BankStatement::create(['tenant_id' => $request->user()->tenant_id, 'bank_account_id' => $account->id, 'statement_number' => $data['statement_number'], 'statement_date' => $data['statement_date'], 'opening_balance' => $data['opening_balance'] ?? 0, 'closing_balance' => $data['closing_balance'] ?? 0, 'status' => 'open']);
            $s->lines()->createMany($data['lines']);

            return $s;
        });

        return response()->json(['data' => $statement->load('lines')], 201);
    }

    public function matchStatementLine(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['payment_id' => ['required', 'uuid']]);
        $line = BankStatementLine::whereHas('bankStatement', fn ($q) => $q->where('tenant_id', $request->user()->tenant_id))->findOrFail($id);
        Payment::where('tenant_id', $request->user()->tenant_id)->where('company_id', $request->user()->company_id)->whereKey($data['payment_id'])->firstOrFail();
        $line->update(['matched_payment_id' => $data['payment_id'], 'status' => 'matched']);

        return response()->json(['data' => $line]);
    }

    public function createDocument(Request $request): JsonResponse
    {
        $data = $request->validate(['entity_type' => ['required', 'string'], 'entity_id' => ['required', 'uuid'], 'document_type' => ['required', 'string'], 'title' => ['required', 'string']]);
        $document = Document::create([...$data, 'tenant_id' => $request->user()->tenant_id]);
        $this->audit->record($request, 'created', $document);

        return response()->json(['data' => $document], 201);
    }

    public function attach(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['file_name' => ['required', 'string'], 'storage_path' => ['required', 'string'], 'mime_type' => ['sometimes', 'nullable', 'string'], 'size' => ['sometimes', 'integer', 'min:0'], 'sha256' => ['sometimes', 'nullable', 'size:64']]);
        $document = Document::where('tenant_id', $request->user()->tenant_id)->findOrFail($id);
        $attachment = $document->attachments()->create([...$data, 'tenant_id' => $document->tenant_id, 'scan_status' => 'pending']);

        return response()->json(['data' => $attachment], 201);
    }

    public function integration(Request $request): JsonResponse
    {
        $data = $request->validate(['provider' => ['required', 'string'], 'direction' => ['required', 'in:inbound,outbound'], 'idempotency_key' => ['required', 'string'], 'request_payload' => ['sometimes', 'array']]);
        $existing = IntegrationLog::where('provider', $data['provider'])->where('idempotency_key', $data['idempotency_key'])->first();
        if ($existing) {
            return response()->json(['data' => $existing, 'duplicate' => true]);
        }$log = IntegrationLog::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'status' => 'queued', 'attempts' => 0]);

        return response()->json(['data' => $log], 202);
    }

    private function number(string $model, string $prefix, string $tenant): string
    {
        return sprintf('%s-%s-%06d', $prefix, now()->format('Y'), $model::where('tenant_id', $tenant)->count() + 1);
    }
}
