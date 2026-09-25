<?php

namespace App\Http\Requests\Admin;

use App\Enums\RefundDecision;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRefundRequestRequest extends FormRequest
{
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
            'decision' => ['required', Rule::in([RefundDecision::Approved->value, RefundDecision::Denied->value])],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
