<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tickets.import') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:'.config('imports.max_upload_kb'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max' => 'The CSV file must be no larger than '.(int) ceil(config('imports.max_upload_kb') / 1024).' MB.',
            'file.mimes' => 'The file must be a CSV or TXT file.',
        ];
    }
}
