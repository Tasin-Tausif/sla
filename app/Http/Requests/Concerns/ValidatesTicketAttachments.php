<?php

namespace App\Http\Requests\Concerns;

trait ValidatesTicketAttachments
{
    /**
     * Validation rules for ticket attachments, driven by config/tickets.php.
     *
     * @return array<string, mixed>
     */
    protected function attachmentRules(): array
    {
        $config = config('tickets.attachments');

        return [
            'attachments' => ['array', 'max:'.(int) $config['max_files']],
            'attachments.*' => ['file', 'mimes:'.implode(',', $config['mimes']), 'max:'.(int) $config['max_kb']],
        ];
    }
}
