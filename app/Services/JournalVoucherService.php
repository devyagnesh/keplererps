<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\DocumentStatus;
use App\Enums\VoucherType;
use App\Models\FinancialYear;
use App\Models\JournalVoucher;
use App\Models\JournalVoucherLine;
use App\Models\LedgerAccount;
use App\Repositories\Interfaces\JournalVoucherRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Journal voucher business logic — the single entry point into the general ledger (M13).
 */
class JournalVoucherService
{
    /**
     * Rounding tolerance when comparing the debit and credit totals.
     */
    public const BALANCE_TOLERANCE = 0.01;

    public function __construct(
        protected JournalVoucherRepositoryInterface $repository,
        protected NumberingService $numbering,
        protected FinancialYearService $financialYears,
        protected ActivityLogService $activityLog,
        protected PeriodLockService $periodLocks
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): JournalVoucher
    {
        return $this->repository->findById($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): JournalVoucher
    {
        return DB::transaction(function () use ($data): JournalVoucher {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $voucherType = VoucherType::tryFrom((string) ($data['voucher_type'] ?? '')) ?? VoucherType::Journal;
            if ($voucherType->isSystemGenerated()) {
                throw ValidationException::withMessages([
                    'voucher_type' => 'Sales and purchase vouchers are generated automatically from their documents.',
                ]);
            }

            $financialYear = $this->resolveFinancialYear((string) $data['document_date']);

            $data['document_no'] = $this->numbering->next($this->seriesFor($voucherType));
            $data['voucher_type'] = $voucherType->value;
            $data['status'] = DocumentStatus::Draft->value;
            $data['financial_year_id'] = $financialYear?->id;
            $data['created_by'] = Auth::id();
            $data['updated_by'] = Auth::id();

            $voucher = $this->repository->create($data);
            $this->syncLines($voucher, $lines);

            return $this->repository->findById($voucher->id);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): JournalVoucher
    {
        return DB::transaction(function () use ($id, $data): JournalVoucher {
            $voucher = $this->repository->findById($id);
            $this->assertManuallyEditable($voucher);

            $lines = $data['lines'] ?? [];
            unset($data['lines'], $data['document_no'], $data['status'], $data['voucher_type'], $data['source_type'], $data['source_id']);

            if (! empty($data['document_date'])) {
                $data['financial_year_id'] = $this->resolveFinancialYear((string) $data['document_date'])?->id;
            }

            $data['updated_by'] = Auth::id();
            $this->repository->update($id, $data);
            $this->syncLines($voucher->fresh(), $lines);

            return $this->repository->findById($id);
        });
    }

    public function delete(int $id): bool
    {
        $voucher = $this->repository->findById($id);
        $this->assertManuallyEditable($voucher);

        return $this->repository->delete($id);
    }

    /**
     * Post a balanced voucher into the ledger.
     */
    public function post(int $id, ?string $overrideReason = null): JournalVoucher
    {
        return DB::transaction(function () use ($id, $overrideReason): JournalVoucher {
            $voucher = JournalVoucher::query()->with('lines')->lockForUpdate()->findOrFail($id);

            if ($voucher->status !== DocumentStatus::Draft) {
                throw ValidationException::withMessages([
                    'journal_voucher' => 'Only draft vouchers can be posted.',
                ]);
            }

            $this->assertBalanced($voucher);
            $this->assertOpenPeriod($voucher->document_date->toDateString(), $overrideReason);

            $voucher->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ])->save();

            $this->activityLog->log(
                event: 'status_changed',
                description: "Journal voucher {$voucher->document_no} posted.",
                subject: $voucher,
                properties: ['new_status' => DocumentStatus::Posted->value],
                logName: 'finance'
            );

            return $this->repository->findById($id);
        });
    }

    /**
     * Cancel a voucher; posted vouchers stay on record with a cancelled status.
     */
    public function cancel(int $id): JournalVoucher
    {
        return DB::transaction(function () use ($id): JournalVoucher {
            $voucher = JournalVoucher::query()->lockForUpdate()->findOrFail($id);

            if ($voucher->status === DocumentStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'journal_voucher' => 'This voucher is already cancelled.',
                ]);
            }

            if ($voucher->status === DocumentStatus::Posted) {
                $this->assertOpenPeriod($voucher->document_date->toDateString());
            }

            $voucher->forceFill([
                'status' => DocumentStatus::Cancelled,
                'updated_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($id);
        });
    }

    /**
     * Create and immediately post a voucher generated by the system from a source document.
     *
     * @param  list<array{ledger_account_id: int, party_id?: int|null, debit?: float, credit?: float, narration?: string|null}>  $lines
     */
    public function postSystemVoucher(
        VoucherType $voucherType,
        string $documentDate,
        array $lines,
        Model $source,
        ?string $narration = null,
        ?string $referenceNo = null
    ): JournalVoucher {
        return DB::transaction(function () use ($voucherType, $documentDate, $lines, $source, $narration, $referenceNo): JournalVoucher {
            $existing = $this->repository->findForSource($source::class, (int) $source->getKey());
            if ($existing !== null && $existing->status !== DocumentStatus::Cancelled) {
                return $this->repository->findById($existing->id);
            }

            $financialYear = $this->resolveFinancialYear($documentDate);
            $this->assertOpenPeriod($documentDate);

            $voucher = $this->repository->create([
                'document_no' => $this->numbering->next($this->seriesFor($voucherType)),
                'document_date' => $documentDate,
                'financial_year_id' => $financialYear?->id,
                'voucher_type' => $voucherType->value,
                'status' => DocumentStatus::Draft->value,
                'reference_no' => $referenceNo,
                'source_type' => $source::class,
                'source_id' => $source->getKey(),
                'narration' => $narration,
                'created_by' => Auth::id(),
                'updated_by' => Auth::id(),
            ]);

            $this->syncLines($voucher, $lines);
            $voucher->refresh()->load('lines');
            $this->assertBalanced($voucher);

            $voucher->forceFill([
                'status' => DocumentStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ])->save();

            return $this->repository->findById($voucher->id);
        });
    }

    /**
     * Cancel the voucher auto-posted from a source document, if one exists.
     */
    public function cancelForSource(Model $source): ?JournalVoucher
    {
        $voucher = $this->repository->findForSource($source::class, (int) $source->getKey());

        if ($voucher === null || $voucher->status === DocumentStatus::Cancelled) {
            return null;
        }

        return $this->cancel($voucher->id);
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    protected function syncLines(JournalVoucher $voucher, array $lines): void
    {
        $voucher->lines()->delete();
        $debit = 0.0;
        $credit = 0.0;

        foreach (array_values($lines) as $index => $line) {
            $accountId = (int) ($line['ledger_account_id'] ?? 0);
            if ($accountId === 0) {
                continue;
            }

            $lineDebit = round((float) ($line['debit'] ?? 0), 2);
            $lineCredit = round((float) ($line['credit'] ?? 0), 2);

            if ($lineDebit <= 0 && $lineCredit <= 0) {
                continue;
            }

            if ($lineDebit > 0 && $lineCredit > 0) {
                throw ValidationException::withMessages([
                    'lines' => 'A voucher line cannot hold both a debit and a credit amount.',
                ]);
            }

            $account = LedgerAccount::query()->findOrFail($accountId);
            if (! $account->is_active) {
                throw ValidationException::withMessages([
                    'lines' => "Account {$account->code} is inactive.",
                ]);
            }

            JournalVoucherLine::query()->create([
                'journal_voucher_id' => $voucher->id,
                'ledger_account_id' => $account->id,
                'party_id' => $line['party_id'] ?? null,
                'debit' => $lineDebit,
                'credit' => $lineCredit,
                'narration' => $line['narration'] ?? null,
                'sort_order' => $index,
            ]);

            $debit += $lineDebit;
            $credit += $lineCredit;
        }

        if ($voucher->lines()->count() < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A voucher needs at least one debit and one credit line.',
            ]);
        }

        $voucher->forceFill([
            'total_debit' => round($debit, 2),
            'total_credit' => round($credit, 2),
        ])->save();
    }

    protected function assertBalanced(JournalVoucher $voucher): void
    {
        $debit = round((float) $voucher->lines->sum(fn (JournalVoucherLine $line) => (float) $line->debit), 2);
        $credit = round((float) $voucher->lines->sum(fn (JournalVoucherLine $line) => (float) $line->credit), 2);

        if ($debit <= 0) {
            throw ValidationException::withMessages([
                'lines' => 'A voucher must carry an amount.',
            ]);
        }

        if (abs($debit - $credit) > self::BALANCE_TOLERANCE) {
            throw ValidationException::withMessages([
                'lines' => sprintf(
                    'Voucher is out of balance: debit %s vs credit %s.',
                    number_format($debit, 2, '.', ''),
                    number_format($credit, 2, '.', '')
                ),
            ]);
        }
    }

    protected function assertManuallyEditable(JournalVoucher $voucher): void
    {
        if ($voucher->source_type !== null) {
            throw ValidationException::withMessages([
                'journal_voucher' => 'System-generated vouchers follow their source document.',
            ]);
        }

        if ($voucher->status !== DocumentStatus::Draft) {
            throw ValidationException::withMessages([
                'journal_voucher' => 'Only draft vouchers can be modified.',
            ]);
        }
    }

    protected function assertOpenPeriod(string $documentDate, ?string $overrideReason = null): void
    {
        $closed = FinancialYear::query()
            ->where('is_closed', true)
            ->whereDate('starts_on', '<=', $documentDate)
            ->whereDate('ends_on', '>=', $documentDate)
            ->exists();

        if ($closed) {
            throw ValidationException::withMessages([
                'document_date' => 'The financial year covering this date is closed.',
            ]);
        }

        $this->periodLocks->assertOpen($documentDate, $overrideReason);
    }

    protected function resolveFinancialYear(string $documentDate): ?FinancialYear
    {
        return FinancialYear::query()
            ->whereDate('starts_on', '<=', $documentDate)
            ->whereDate('ends_on', '>=', $documentDate)
            ->first()
            ?? $this->financialYears->current();
    }

    protected function seriesFor(VoucherType $voucherType): DocumentSeriesType
    {
        return match ($voucherType) {
            VoucherType::Receipt => DocumentSeriesType::Receipt,
            VoucherType::Payment => DocumentSeriesType::Payment,
            default => DocumentSeriesType::JournalVoucher,
        };
    }
}
