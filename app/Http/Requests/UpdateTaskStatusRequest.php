<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in(['pending', 'in_progress', 'done']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: pending, in_progress, done.',
        ];
    }
}
