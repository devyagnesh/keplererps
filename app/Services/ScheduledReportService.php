<?php

namespace App\Services;

use App\Models\ScheduledReport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * CRUD and due-run dispatch for scheduled register reports.
 */
class ScheduledReportService
{
    public function __construct(
        protected RegisterReportService $registers
    ) {}

    /**
     * @return Collection<int, ScheduledReport>
     */
    public function all(): Collection
    {
        return ScheduledReport::query()
            ->with('creator:id,name')
            ->latest('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ScheduledReport
    {
        return ScheduledReport::query()->create([
            'name' => $data['name'],
            'register_key' => $data['register_key'],
            'frequency' => $data['frequency'] ?? 'daily',
            'recipient_emails' => $data['recipient_emails'],
            'is_active' => (bool) ($data['is_active'] ?? true),
            'created_by' => Auth::id(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): ScheduledReport
    {
        $report = ScheduledReport::query()->findOrFail($id);
        $report->update([
            'name' => $data['name'] ?? $report->name,
            'register_key' => $data['register_key'] ?? $report->register_key,
            'frequency' => $data['frequency'] ?? $report->frequency,
            'recipient_emails' => $data['recipient_emails'] ?? $report->recipient_emails,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $report->is_active,
        ]);

        return $report->fresh();
    }

    public function delete(int $id): bool
    {
        return (bool) ScheduledReport::query()->findOrFail($id)->delete();
    }

    /**
     * Run all active schedules that are due based on frequency.
     *
     * @return list<array{report_id: int, status: string, message?: string}>
     */
    public function runDue(): array
    {
        $fromDate = now()->subDays(7)->toDateString();
        $toDate = now()->toDateString();
        $results = [];

        $dueReports = ScheduledReport::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (ScheduledReport $report): bool => $this->isDue($report));

        foreach ($dueReports as $report) {
            try {
                $method = Str::camel(str_replace('-', '_', $report->register_key));

                if (! method_exists($this->registers, $method)) {
                    report(new \RuntimeException("Unknown register key: {$report->register_key}"));
                    $results[] = [
                        'report_id' => $report->id,
                        'status' => 'failed',
                        'message' => "Unknown register: {$report->register_key}",
                    ];

                    continue;
                }

                $registerData = $this->registers->{$method}($fromDate, $toDate);
                $rows = array_slice($registerData['rows'] ?? [], 0, 50);
                $body = $this->buildCsvSummary($report, $fromDate, $toDate, $rows);

                foreach ($this->parseRecipients($report->recipient_emails) as $email) {
                    Mail::raw($body, function ($message) use ($email, $report): void {
                        $message->to($email)
                            ->subject('[Kepler ERP] '.$report->name);
                    });
                }

                $report->update(['last_run_at' => now()]);

                $results[] = [
                    'report_id' => $report->id,
                    'status' => 'sent',
                ];
            } catch (Throwable $e) {
                report($e);
                $results[] = [
                    'report_id' => $report->id,
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function isDue(ScheduledReport $report): bool
    {
        if ($report->last_run_at === null) {
            return true;
        }

        $days = match ($report->frequency) {
            'weekly' => 7,
            'monthly' => 28,
            default => 1,
        };

        return $report->last_run_at->lte(now()->subDays($days));
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    protected function buildCsvSummary(ScheduledReport $report, string $fromDate, string $toDate, array $rows): string
    {
        $lines = [
            "Report: {$report->name}",
            "Register: {$report->register_key}",
            "Period: {$fromDate} to {$toDate}",
            'Rows (max 50): '.count($rows),
            '',
        ];

        if ($rows === []) {
            $lines[] = 'No rows in period.';

            return implode("\n", $lines);
        }

        $headers = array_keys($rows[0]);
        $lines[] = implode(',', $headers);

        foreach ($rows as $row) {
            $values = array_map(
                fn (mixed $value): string => '"'.str_replace('"', '""', (string) $value).'"',
                array_values($row)
            );
            $lines[] = implode(',', $values);
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<string>
     */
    protected function parseRecipients(string $recipientEmails): array
    {
        return array_values(array_filter(array_map(
            trim(...),
            explode(',', $recipientEmails)
        )));
    }
}
