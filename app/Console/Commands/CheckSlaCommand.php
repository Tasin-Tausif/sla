<?php

namespace App\Console\Commands;

use App\Services\SlaEscalationService;
use Illuminate\Console\Command;

class CheckSlaCommand extends Command
{
    protected $signature = 'tickets:check-sla';

    protected $description = 'Escalate breached tickets and send SLA warnings';

    public function handle(SlaEscalationService $escalation): int
    {
        $result = $escalation->run();

        $this->line("Escalated: {$result['breached']}, Warned: {$result['warned']}");

        return self::SUCCESS;
    }
}
