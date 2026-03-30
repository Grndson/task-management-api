<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'    => [
                'required',
                'string',
                'max:255',
                // Unique: title + due_date combination
                Rule::unique('tasks')->where(function ($query) {
                    return $query->where('due_date', $this->input('due_date'));
                }),
            ],
            'due_date' => [
                'required',
                'date',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],
            'priority' => [
                'required',
                Rule::in(['low', 'medium', 'high']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique'          => 'A task with this title already exists for the same due date.',
            'due_date.after_or_equal' => 'The due date must be today or a future date.',
            'priority.in'           => 'Priority must be one of: low, medium, high.',
        ];
    }
}
