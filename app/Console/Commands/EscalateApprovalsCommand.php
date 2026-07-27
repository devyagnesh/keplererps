<?php

namespace App\Console\Commands;

use App\Services\ApprovalRuleService;
use Illuminate\Console\Command;

/**
 * Escalate overdue multi-step approval actions.
 */
class EscalateApprovalsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'approvals:escalate';

    /**
     * @var string
     */
    protected $description = 'Mark overdue pending document approval steps as escalated';

    public function handle(ApprovalRuleService $approvals): int
    {
        $count = $approvals->escalateDue();
        $this->info("Escalated {$count} approval step(s).");

        return self::SUCCESS;
    }
}
