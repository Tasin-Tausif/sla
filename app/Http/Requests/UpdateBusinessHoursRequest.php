<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateBusinessHoursRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hours = $this->input('hours', []);

        foreach ($hours as $day => $values) {
            if (! is_array($values)) {
                continue;
            }

            foreach (['start_time', 'end_time'] as $field) {
                if (is_string($values[$field] ?? null) && strlen($values[$field]) === 8) {
                    $hours[$day][$field] = substr($values[$field], 0, 5);
                }
            }
        }

        $this->merge(['hours' => $hours]);
    }

    public function rules(): array
    {
        return [
            'hours' => ['required', 'array', 'required_array_keys:0,1,2,3,4,5,6'],
            'hours.*' => ['required', 'array'],
            'hours.*.start_time' => ['nullable', 'date_format:H:i'],
            'hours.*.end_time' => [
                'nullable',
                'date_format:H:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    preg_match('/hours\.(\d+)\.end_time/', $attribute, $matches);
                    $start = $this->input("hours.{$matches[1]}.start_time");

                    if ($start === null || $value <= $start) {
                        $fail('The end time must be after the start time.');
                    }
                },
            ],
            'hours.*.is_working_day' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hours = $this->input('hours', []);

            if (! collect($hours)->contains(fn ($hour): bool => is_array($hour) && (bool) ($hour['is_working_day'] ?? false))) {
                $validator->errors()->add('hours', 'At least one working day is required.');
            }

            foreach ($hours as $day => $values) {
                if (! is_array($values)) {
                    continue;
                }

                if (! ($values['is_working_day'] ?? false)) {
                    continue;
                }

                foreach (['start_time', 'end_time'] as $field) {
                    if (blank($values[$field] ?? null)) {
                        $validator->errors()->add("hours.{$day}.{$field}", 'This field is required for a working day.');
                    }
                }
            }
        });
    }
}
