<?php

namespace App\Services;

use App\Events\SlaBreached;
use App\Events\SlaWarningTriggered;
use App\Models\Ticket;

class SlaEscalationService
{
    public function __construct(private readonly TicketActivityLogger $logger) {}

    public function escalateBreaches(): int
    {
        $count = 0;

        Ticket::query()
            ->breachedSla()
            ->whereNull('sla_breached_at')
            ->chunkById(100, function ($tickets) use (&$count): void {
                foreach ($tickets as $ticket) {
                    $timestamp = now();
                    $affected = Ticket::query()
                        ->whereKey($ticket->id)
                        ->whereNull('sla_breached_at')
                        ->update([
                            'sla_breached_at' => $timestamp,
                            'escalated_at' => $timestamp,
                            'escalation_level' => 2,
                            'updated_at' => $timestamp,
                        ]);

                    if ($affected !== 1) {
                        continue;
                    }

                    $ticket->refresh();
                    $this->logger->log(
                        $ticket,
                        'escalated',
                        'Ticket escalated: SLA breached',
                        null,
                    );
                    SlaBreached::dispatch($ticket);
                    $count++;
                }
            });

        return $count;
    }

    public function sendWarnings(): int
    {
        $count = 0;

        Ticket::query()
            ->approachingSla()
            ->whereNull('sla_warning_sent_at')
            ->chunkById(100, function ($tickets) use (&$count): void {
                foreach ($tickets as $ticket) {
                    $timestamp = now();
                    $affected = Ticket::query()
                        ->whereKey($ticket->id)
                        ->whereNull('sla_warning_sent_at')
                        ->where(function ($query): void {
                            $query->whereNull('escalation_level')
                                ->orWhere('escalation_level', '<', 1);
                        })
                        ->update([
                            'sla_warning_sent_at' => $timestamp,
                            'escalation_level' => 1,
                            'updated_at' => $timestamp,
                        ]);

                    if ($affected !== 1) {
                        continue;
                    }

                    $ticket->refresh();
                    $this->logger->log(
                        $ticket,
                        'sla_warning',
                        'SLA warning: approaching breach',
                        null,
                    );
                    SlaWarningTriggered::dispatch($ticket);
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Guarded updates make overlapping runs unable to notify twice.
     *
     * @return array{breached: int, warned: int}
     */
    public function run(): array
    {
        return [
            'breached' => $this->escalateBreaches(),
            'warned' => $this->sendWarnings(),
        ];
    }
}
