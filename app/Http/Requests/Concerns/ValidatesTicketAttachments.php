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

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.*.max' => 'Each attachment must be no larger than '.(int) ceil(config('tickets.attachments.max_kb') / 1024).' MB.',
            'attachments.*.mimes' => 'Each attachment must be one of: '.implode(', ', config('tickets.attachments.mimes')).'.',
        ];
    }
}
