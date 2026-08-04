<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FiscalPeriod;
use App\Models\Journal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AccountingPostingService
{
    public function post(Model $source, string $sourceAction, string $date, array $entries): Journal
    {
        $tenantId = $source->tenant_id;
        $companyId = $source->company_id;
        $this->assertOpenPeriod($tenantId, $companyId, $date);

        $debit = collect($entries)->sum('debit');
        $credit = collect($entries)->sum('credit');
        abort_unless(abs($debit - $credit) < 0.0001 && $debit > 0, 422, 'Accounting posting tidak balance.');

        return DB::transaction(function () use ($source, $sourceAction, $date, $entries, $companyId, $tenantId): Journal {
            $existing = Journal::query()->where('tenant_id', $tenantId)->where('source_type', $source::class)->where('source_id', $source->getKey())->where('status', 'posted')->first();
            if ($existing) {
                return $existing;
            }

            $journal = Journal::create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'number' => $this->nextNumber($companyId),
                'journal_date' => $date,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
                'description' => $sourceAction,
                'status' => 'posted',
            ]);

            foreach ($entries as $entry) {
                $account = $this->account($tenantId, $companyId, $entry['account'], $entry['type']);
                $journal->lines()->create(['account_id' => $account->id, 'debit' => $entry['debit'], 'credit' => $entry['credit'], 'description' => $entry['description'] ?? $sourceAction]);
            }

            return $journal;
        });
    }

    public function assertOpenPeriod(string $tenantId, string $companyId, string $date): FiscalPeriod
    {
        $day = now()->parse($date);
        $period = FiscalPeriod::query()->where('tenant_id', $tenantId)->where('company_id', $companyId)->whereDate('starts_on', '<=', $day)->whereDate('ends_on', '>=', $day)->first();
        if (! $period) {
            $period = FiscalPeriod::create(['tenant_id' => $tenantId, 'company_id' => $companyId, 'year' => $day->year, 'period' => $day->month, 'starts_on' => $day->copy()->startOfMonth(), 'ends_on' => $day->copy()->endOfMonth(), 'status' => 'open']);
        }
        abort_unless($period->status === 'open', 422, 'Fiscal period sudah ditutup.');

        return $period;
    }

    private function account(string $tenantId, string $companyId, string $code, string $type): Account
    {
        $normal = in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit';

        return Account::firstOrCreate(['tenant_id' => $tenantId, 'company_id' => $companyId, 'code' => $code], ['name' => ucfirst(str_replace('_', ' ', $code)), 'type' => $type, 'normal_balance' => $normal, 'status' => 'active']);
    }

    private function nextNumber(string $companyId): string
    {
        return sprintf('JV-%s-%06d', now()->format('Y'), Journal::query()->where('company_id', $companyId)->lockForUpdate()->count() + 1);
    }
}
