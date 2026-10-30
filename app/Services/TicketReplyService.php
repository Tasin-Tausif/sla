<?php

namespace App\Services;

use App\Enums\TicketStatus;
use App\Models\Attachment;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TicketReplyService
{
    public function __construct(
        private readonly TicketActivityLogger $logger,
        private readonly TicketService $tickets,
    ) {}

    /**
     * Add a reply (public reply or internal note) together with its files.
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function add(Ticket $ticket, User $author, string $body, bool $isInternal, array $files = []): TicketReply
    {
        return DB::transaction(function () use ($ticket, $author, $body, $isInternal, $files): TicketReply {
            $reply = TicketReply::create([
                'ticket_id' => $ticket->id,
                'user_id' => $author->id,
                'body' => $body,
                'is_internal' => $isInternal,
            ]);

            $attachmentCount = $this->storeFiles($ticket, $author, $files, $reply->id);

            if (! $isInternal && $this->recordsFirstResponse($ticket, $author)) {
                $ticket->first_response_at = now();
                $ticket->save();
            }

            $this->logger->log(
                $ticket,
                $isInternal ? 'note_added' : 'replied',
                $isInternal ? 'Internal note added' : 'Reply added',
                $author,
                ['is_internal' => $isInternal],
            );

            if ($attachmentCount > 0) {
                $this->logger->log(
                    $ticket,
                    'attachment_added',
                    'Attachments added',
                    $author,
                    ['count' => $attachmentCount, 'reply_id' => $reply->id],
                );
            }

            if (! $isInternal) {
                $this->applyCustomerStatusTransition($ticket, $author);
            }

            return $reply;
        });
    }

    /**
     * Store files uploaded together with the ticket itself (no reply yet).
     *
     * @param  array<int, UploadedFile>  $files
     */
    public function attachToTicket(Ticket $ticket, User $uploader, array $files): void
    {
        if ($files === []) {
            return;
        }

        DB::transaction(function () use ($ticket, $uploader, $files): void {
            $attachmentCount = $this->storeFiles($ticket, $uploader, $files);

            if ($attachmentCount > 0) {
                $this->logger->log(
                    $ticket,
                    'attachment_added',
                    'Attachments added',
                    $uploader,
                    ['count' => $attachmentCount],
                );
            }
        });
    }

    private function recordsFirstResponse(Ticket $ticket, User $author): bool
    {
        return $ticket->first_response_at === null
            && $ticket->created_by !== $author->id
            && $author->hasAnyRole(['admin', 'agent']);
    }

    /**
     * A customer reply moves Waiting for Customer back to In Progress and
     * reopens a Resolved ticket.
     */
    private function applyCustomerStatusTransition(Ticket $ticket, User $author): void
    {
        if (! $author->hasRole('customer')) {
            return;
        }

        $status = $ticket->status instanceof TicketStatus
            ? $ticket->status
            : TicketStatus::from($ticket->status);

        $target = match ($status) {
            TicketStatus::WaitingForCustomer => TicketStatus::InProgress,
            TicketStatus::Resolved => TicketStatus::Open,
            default => null,
        };

        if ($target !== null) {
            $this->tickets->changeStatus($ticket, $target, $author);
        }
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeFiles(Ticket $ticket, User $uploader, array $files, ?int $replyId = null): int
    {
        $disk = (string) config('tickets.attachments.disk');
        $count = 0;

        foreach ($files as $file) {
            $storedPath = $file->store("tickets/{$ticket->id}", $disk);

            if (! is_string($storedPath) || $storedPath === '') {
                throw new RuntimeException("Unable to store the attachment for ticket [{$ticket->id}].");
            }

            Attachment::create([
                'ticket_id' => $ticket->id,
                'reply_id' => $replyId,
                'uploaded_by' => $uploader->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'disk' => $disk,
                'mime_type' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);

            $count++;
        }

        return $count;
    }
}
