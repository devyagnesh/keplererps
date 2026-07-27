<?php

namespace App\Services;

use App\Enums\DocumentSeriesType;
use App\Enums\NotificationEvent;
use App\Enums\SalaryRunStatus;
use App\Models\Employee;
use App\Models\ProductionEntry;
use App\Models\SalaryRun;
use App\Models\SalarySlip;
use App\Repositories\Interfaces\SalaryRunRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;

/**
 * Monthly salary run: build slips from attendance, then post the payroll journal (M14).
 *
 * Earnings are prorated on payable attendance days against the calendar days of the period,
 * which keeps the thin v1 free of statutory slabs while still respecting leave and absence.
 */
class SalaryRunService
{
    public function __construct(
        protected SalaryRunRepositoryInterface $repository,
        protected EmployeeService $employees,
        protected AttendanceService $attendance,
        protected AccountingPostingService $accounting,
        protected NumberingService $numbering,
        protected NotificationDispatchService $notifications,
        protected StatutoryPayrollService $statutory,
        protected WhatsAppService $whatsapp,
        protected DocumentPdfService $pdf,
        protected SystemSettingService $settings
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{draw: int, recordsTotal: int, recordsFiltered: int, data: list<array<string, mixed>>}
     */
    public function dataTable(array $params): array
    {
        return $this->repository->getForDataTable($params);
    }

    public function find(int $id): SalaryRun
    {
        return $this->repository->findById($id);
    }

    /**
     * Create a draft run for a period and build a slip for every payable employee.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SalaryRun
    {
        $year = (int) $data['period_year'];
        $month = (int) $data['period_month'];

        if ($this->repository->findOpenForPeriod($year, $month) !== null) {
            throw ValidationException::withMessages([
                'period_month' => 'A salary run already exists for this period. Cancel it before starting another.',
            ]);
        }

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return DB::transaction(function () use ($data, $year, $month, $start, $end): SalaryRun {
            $run = $this->repository->create([
                'document_no' => $this->numbering->next(DocumentSeriesType::SalaryRun),
                'period_year' => $year,
                'period_month' => $month,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'payment_date' => $data['payment_date'] ?? $end->toDateString(),
                'status' => SalaryRunStatus::Draft->value,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $this->rebuildSlips($run);

            return $this->repository->findById($run->id);
        });
    }

    /**
     * Recalculate every slip of a draft run from the current attendance data.
     */
    public function recalculate(int $id): SalaryRun
    {
        $run = $this->repository->findById($id);
        $this->assertEditable($run);

        return DB::transaction(function () use ($run): SalaryRun {
            $this->rebuildSlips($run);

            return $this->repository->findById($run->id);
        });
    }

    /**
     * Post the run: freeze the slips and write the payroll journal into the general ledger.
     */
    public function post(int $id): SalaryRun
    {
        $run = $this->repository->findById($id);
        $this->assertEditable($run);

        if ($run->slips->isEmpty()) {
            throw ValidationException::withMessages([
                'salary_run' => 'This run has no salary slips to post.',
            ]);
        }

        if (round((float) $run->net_total, 2) <= 0) {
            throw ValidationException::withMessages([
                'salary_run' => 'The net payable for this run is zero; check attendance before posting.',
            ]);
        }

        return DB::transaction(function () use ($run): SalaryRun {
            $run->forceFill([
                'status' => SalaryRunStatus::Posted,
                'posted_at' => now(),
                'posted_by' => Auth::id(),
            ])->save();

            $this->accounting->postSalaryRun($run);

            $posted = $this->repository->findById($run->id);
            $this->notifications->dispatch(
                NotificationEvent::SalaryRunPosted,
                [
                    'document_no' => $posted->document_no,
                    'period' => $posted->periodLabel(),
                    'net_total' => number_format((float) $posted->net_total, 2, '.', ''),
                ],
                route('admin.salary-runs.edit', $posted)
            );

            $this->deliverSlipsViaWhatsApp($posted);

            return $posted;
        });
    }

    /**
     * Cancel a run and reverse its journal so the period can be re-run.
     */
    public function cancel(int $id): SalaryRun
    {
        $run = $this->repository->findById($id);

        if ($run->status === SalaryRunStatus::Cancelled) {
            throw ValidationException::withMessages([
                'salary_run' => 'This salary run is already cancelled.',
            ]);
        }

        return DB::transaction(function () use ($run): SalaryRun {
            if ($run->status === SalaryRunStatus::Posted) {
                $this->accounting->reverse($run);
            }

            $run->forceFill(['status' => SalaryRunStatus::Cancelled])->save();

            return $this->repository->findById($run->id);
        });
    }

    /**
     * Delete a draft run along with its slips.
     */
    public function delete(int $id): bool
    {
        $run = $this->repository->findById($id);

        if ($run->status !== SalaryRunStatus::Draft) {
            throw ValidationException::withMessages([
                'salary_run' => 'Only a draft salary run can be deleted.',
            ]);
        }

        return $this->repository->delete($id);
    }

    /**
     * Rebuild the slips of a run and refresh its totals.
     */
    protected function rebuildSlips(SalaryRun $run): void
    {
        $start = $run->period_start->toDateString();
        $end = $run->period_end->toDateString();
        $periodDays = (int) $run->period_start->daysInMonth;
        $attendance = $this->attendance->summaryForPeriod($start, $end);

        $run->slips()->delete();

        $gross = 0.0;
        $deductions = 0.0;
        $net = 0.0;
        $count = 0;

        foreach ($this->employees->payableOn($end) as $employee) {
            $summary = $attendance->get($employee->id, [
                'payable_days' => 0.0,
                'overtime_hours' => 0.0,
                'marked_days' => 0,
            ]);

            if ($employee->user_id !== null) {
                $pieceSummary = $this->pieceSummaryForEmployee($employee, $start, $end);
                $summary['pieces'] = $pieceSummary['pieces'];
                $summary['piece_amount'] = $pieceSummary['amount'];
            }

            $slip = $this->buildSlip($employee, $summary, $periodDays);
            $run->slips()->create($slip);

            $gross += $slip['gross_amount'];
            $deductions += $slip['deduction_amount'];
            $net += $slip['net_amount'];
            $count++;
        }

        $run->forceFill([
            'employee_count' => $count,
            'gross_total' => round($gross, 2),
            'deduction_total' => round($deductions, 2),
            'net_total' => round($net, 2),
        ])->save();
    }

    /**
     * Sum posted production good quantity and piece earnings for an operator in the period.
     *
     * @return array{pieces: float, amount: float}
     */
    protected function pieceSummaryForEmployee(Employee $employee, string $start, string $end): array
    {
        $entries = ProductionEntry::query()
            ->where('operator_user_id', $employee->user_id)
            ->whereNotNull('posted_at')
            ->whereDate('document_date', '>=', $start)
            ->whereDate('document_date', '<=', $end)
            ->with('workOrder.item:id,piece_rate')
            ->get(['id', 'work_order_id', 'good_quantity']);

        $pieces = 0.0;
        $amount = 0.0;
        $employeeRate = (float) ($employee->piece_rate ?? 0);

        foreach ($entries as $entry) {
            $qty = (float) $entry->good_quantity;
            $pieces += $qty;

            $itemRate = $entry->workOrder?->item?->piece_rate;
            $rate = $itemRate !== null && (float) $itemRate > 0
                ? (float) $itemRate
                : $employeeRate;

            $amount += $qty * $rate;
        }

        return [
            'pieces' => round($pieces, 4),
            'amount' => round($amount, 2),
        ];
    }

    /**
     * Prorate one employee's earnings for the period including statutory deductions.
     *
     * @param  array{payable_days: float, overtime_hours: float, marked_days: int, pieces?: float, piece_amount?: float}  $summary
     * @return array<string, mixed>
     */
    protected function buildSlip(Employee $employee, array $summary, int $periodDays): array
    {
        // With no attendance marked at all, the employee is paid the full month.
        $payableDays = $summary['marked_days'] > 0
            ? min((float) $summary['payable_days'], (float) $periodDays)
            : (float) $periodDays;

        $factor = $periodDays > 0 ? $payableDays / $periodDays : 0.0;
        $basic = round($employee->basicAmount() * $factor, 2);
        $allowance = round($employee->allowanceAmount() * $factor, 2);
        $pieceAmount = round((float) ($summary['piece_amount'] ?? 0), 2);
        $pieces = round((float) ($summary['pieces'] ?? 0), 4);
        $overtime = round((float) $summary['overtime_hours'] * (float) $employee->overtime_rate_per_hour, 2);
        $grossAmount = round($basic + $allowance + $overtime + $pieceAmount, 2);
        $deductions = $this->statutory->deductions($employee, $basic, $grossAmount);
        $deduction = round(min($deductions['total'], $grossAmount), 2);

        return [
            'employee_id' => $employee->id,
            'payable_days' => round($payableDays, 2),
            'period_days' => $periodDays,
            'overtime_hours' => round((float) $summary['overtime_hours'], 2),
            'pieces' => $pieces,
            'basic_amount' => $basic,
            'allowance_amount' => $allowance,
            'overtime_amount' => $overtime,
            'piece_amount' => $pieceAmount,
            'gross_amount' => $grossAmount,
            'deduction_amount' => $deduction,
            'net_amount' => round($grossAmount - $deduction, 2),
        ];
    }

    /**
     * Email/WhatsApp each slip PDF to employees with a mobile number on file.
     */
    protected function deliverSlipsViaWhatsApp(SalaryRun $run): void
    {
        $run->load(['slips.employee']);

        $template = (string) $this->settings->get('whatsapp_template_salary_slip', 'salary_slip');
        $tempDir = storage_path('app/temp/salary-slips');

        if (! File::isDirectory($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        foreach ($run->slips as $slip) {
            /** @var SalarySlip $slip */
            $employee = $slip->employee;

            if ($employee === null || ! filled($employee->mobile)) {
                continue;
            }

            $password = $employee->date_of_birth !== null
                ? $employee->date_of_birth->format('dmY')
                : substr(preg_replace('/\D+/', '', (string) $employee->mobile) ?: '0000', -4);

            $html = $this->slipHtml($run, $slip);
            $pdfBinary = $this->pdf->fromHtml($html, userPassword: $password);

            $filename = sprintf('slip-%s-%s.pdf', $run->document_no, $employee->employee_code);
            $path = $tempDir.'/'.$filename;
            File::put($path, $pdfBinary);

            $this->whatsapp->sendTemplate(
                (string) $employee->mobile,
                $template,
                [
                    $run->periodLabel(),
                    number_format((float) $slip->net_amount, 2, '.', ''),
                ],
                'en',
                [
                    'event' => NotificationEvent::SalarySlipGenerated->value,
                    'salary_run_id' => $run->id,
                    'employee_id' => $employee->id,
                    'pdf_path' => $path,
                ]
            );

            $this->notifications->dispatch(
                NotificationEvent::SalarySlipGenerated,
                [
                    'employee' => $employee->full_name,
                    'period' => $run->periodLabel(),
                    'net_amount' => number_format((float) $slip->net_amount, 2, '.', ''),
                ],
                route('admin.salary-runs.edit', $run)
            );
        }
    }

    /**
     * Minimal HTML payslip for PDF generation.
     */
    protected function slipHtml(SalaryRun $run, SalarySlip $slip): string
    {
        $employee = $slip->employee;
        $name = e($employee?->full_name ?? '');
        $code = e($employee?->employee_code ?? '');
        $period = e($run->periodLabel());
        $net = number_format((float) $slip->net_amount, 2, '.', '');

        return <<<HTML
        <!DOCTYPE html><html><head><meta charset="utf-8"><title>Payslip</title></head>
        <body style="font-family: sans-serif; font-size: 12px;">
        <h2>Payslip — {$period}</h2>
        <p><strong>{$code}</strong> — {$name}</p>
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
        <tr><td>Basic</td><td align="right">{$slip->basic_amount}</td></tr>
        <tr><td>Allowance</td><td align="right">{$slip->allowance_amount}</td></tr>
        <tr><td>Overtime</td><td align="right">{$slip->overtime_amount}</td></tr>
        <tr><td>Piece ({$slip->pieces} pcs)</td><td align="right">{$slip->piece_amount}</td></tr>
        <tr><td>Deductions</td><td align="right">{$slip->deduction_amount}</td></tr>
        <tr><td><strong>Net Pay</strong></td><td align="right"><strong>{$net}</strong></td></tr>
        </table>
        </body></html>
        HTML;
    }

    protected function assertEditable(SalaryRun $run): void
    {
        if (! $run->status->isEditable()) {
            throw ValidationException::withMessages([
                'salary_run' => 'A '.strtolower($run->status->label()).' salary run can no longer be changed.',
            ]);
        }
    }
}
