<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use App\Services\AccountingPostingService;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountingController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function accounts(Request $request): JsonResponse
    {
        $query = Account::query()->where('tenant_id', $request->user()->tenant_id)->where('company_id', $request->user()->company_id)->orderBy('code');

        return response()->json($query->paginate(min($request->integer('per_page', 50), 100)));
    }

    public function storeAccount(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:255'], 'type' => ['required', 'in:asset,liability,equity,revenue,expense'], 'normal_balance' => ['required', 'in:debit,credit'], 'parent_id' => ['sometimes', 'nullable', 'uuid']]);
        $account = Account::create([...$data, 'tenant_id' => $request->user()->tenant_id, 'company_id' => $request->user()->company_id, 'status' => 'active']);
        $this->audit->record($request, 'created', $account);

        return response()->json(['data' => $account], 201);
    }

    public function journals(Request $request): JsonResponse
    {
        $query = Journal::query()->where('tenant_id', $request->user()->tenant_id)->where('company_id', $request->user()->company_id)->with('lines')->latest('journal_date');

        return response()->json($query->paginate(min($request->integer('per_page', 20), 100)));
    }

    public function storeJournal(Request $request): JsonResponse
    {
        $data = $request->validate(['journal_date' => ['required', 'date'], 'description' => ['sometimes', 'nullable', 'string'], 'lines' => ['required', 'array', 'min:2'], 'lines.*.account_id' => ['required', 'uuid'], 'lines.*.debit' => ['sometimes', 'numeric', 'min:0'], 'lines.*.credit' => ['sometimes', 'numeric', 'min:0'], 'lines.*.description' => ['sometimes', 'nullable', 'string']]);
        $tenant = $request->user()->tenant_id;
        $company = $request->user()->company_id;
        app(AccountingPostingService::class)->assertOpenPeriod($tenant, $company, $data['journal_date']);
        $ids = collect($data['lines'])->pluck('account_id')->unique();
        $accounts = Account::where('tenant_id', $tenant)->where('company_id', $company)->whereIn('id', $ids)->where('status', 'active')->get();
        abort_unless($accounts->count() === $ids->count(), 422, 'Account tidak valid.');
        foreach ($data['lines'] as $line) {
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);
            abort_unless(($debit > 0) xor ($credit > 0), 422, 'Setiap journal line harus memiliki debit atau credit, bukan keduanya.');
        }
        $debit = collect($data['lines'])->sum('debit');
        $credit = collect($data['lines'])->sum('credit');
        abort_unless(abs($debit - $credit) < 0.0001, 422, 'Journal tidak balance.');
        $journal = DB::transaction(function () use ($data, $request, $tenant, $company): Journal {
            $j = Journal::create(['tenant_id' => $tenant, 'company_id' => $company, 'number' => $this->nextNumber($company), 'journal_date' => $data['journal_date'], 'description' => $data['description'] ?? null, 'status' => 'posted']);
            $j->lines()->createMany($data['lines']);
            $this->audit->record($request, 'posted', $j);

            return $j;
        });

        return response()->json(['data' => $journal->load('lines')], 201);
    }

    public function trialBalance(Request $request): JsonResponse
    {
        $rows = Account::query()->where('accounts.tenant_id', $request->user()->tenant_id)->where('accounts.company_id', $request->user()->company_id)->leftJoin('journal_lines', 'accounts.id', '=', 'journal_lines.account_id')->leftJoin('journals', function ($join) {
            $join->on('journals.id', '=', 'journal_lines.journal_id')->where('journals.status', 'posted');
        })->groupBy('accounts.id', 'accounts.code', 'accounts.name')->select('accounts.id', 'accounts.code', 'accounts.name', DB::raw('COALESCE(SUM(journal_lines.debit),0) as debit'), DB::raw('COALESCE(SUM(journal_lines.credit),0) as credit'))->orderBy('accounts.code')->get();

        return response()->json(['data' => $rows]);
    }

    public function closePeriod(Request $request, string $id): JsonResponse
    {
        $period = FiscalPeriod::where('tenant_id', $request->user()->tenant_id)->where('company_id', $request->user()->company_id)->findOrFail($id);
        abort_unless($period->status === 'open', 422, 'Fiscal period tidak sedang open.');
        $period->update(['status' => 'closed']);

        return response()->json(['data' => $period]);
    }

    public function reopenPeriod(Request $request, string $id): JsonResponse
    {
        $period = FiscalPeriod::where('tenant_id', $request->user()->tenant_id)->where('company_id', $request->user()->company_id)->findOrFail($id);
        abort_unless($period->status === 'closed', 422, 'Fiscal period tidak sedang closed.');
        $period->update(['status' => 'open']);

        return response()->json(['data' => $period]);
    }

    private function nextNumber(string $company): string
    {
        $n = Journal::where('company_id', $company)->lockForUpdate()->count() + 1;

        return sprintf('JV-%s-%06d', now()->format('Y'), $n);
    }
}
