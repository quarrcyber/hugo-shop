<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'recipient_name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'regex:/^0\d{9}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'ward' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'payment_method' => ['required', Rule::in(['card', 'cod'])],
            'credit_card_id' => ['nullable', 'integer', Rule::exists('credit_cards', 'id')->where('user_id', $this->user()?->id)],
            'card_number' => ['exclude_unless:payment_method,card', 'required_without:credit_card_id', 'string', 'regex:/^\d{16}$/'],
            'expiration' => ['exclude_unless:payment_method,card', 'required_without:credit_card_id', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['exclude_unless:payment_method,card', 'required_without:credit_card_id', 'regex:/^\d{3,4}$/'],
            'save_card' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
