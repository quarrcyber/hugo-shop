<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SavedCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string', 'regex:/^\d{16}$/'],
            'expiration' => ['required', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'cvv' => ['required', 'regex:/^\d{3,4}$/'],
        ];
    }
}
