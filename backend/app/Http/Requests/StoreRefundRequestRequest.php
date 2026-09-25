<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRefundRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'order_number' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9-]+$/'],
            'message' => ['required', 'string', 'min:10', 'max:'.config('refunds.max_message_length')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_number.regex' => 'Order numbers only contain letters, numbers and dashes, e.g. ORD-10001.',
            'message.min' => 'Please describe the issue in a little more detail.',
        ];
    }
}
