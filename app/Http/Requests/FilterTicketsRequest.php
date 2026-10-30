<?php

namespace App\Http\Requests;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterTicketsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('sort')) {
            $this->merge([
                'sort' => $this->input('view') === 'closed' ? 'created_at' : 'sla_due_at',
                'direction' => $this->input('view') === 'closed' ? 'desc' : 'asc',
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TicketStatus::class)],
            'priority' => ['nullable', Rule::enum(TicketPriority::class)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'assigned_agent_id' => $this->input('assigned_agent_id') === 'unassigned'
                ? ['nullable', Rule::in(['unassigned'])]
                : ['nullable', 'integer', 'exists:users,id'],
            'customer' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sla_status' => ['nullable', Rule::in(['within', 'approaching', 'breached'])],
            'sort' => ['nullable', Rule::in(['created_at', 'sla_due_at', 'priority', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'view' => ['nullable', Rule::in(['active', 'closed'])],
        ];
    }

    protected function passedValidation(): void
    {
        if ($this->input('assigned_agent_id') === 'unassigned') {
            $this->merge(['assigned_agent_id' => 'unassigned']);
        }
    }
}
