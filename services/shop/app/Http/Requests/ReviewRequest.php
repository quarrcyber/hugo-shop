<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['rating' => ['required', 'integer', 'between:1,5'], 'content' => ['required', 'string', 'min:10', 'max:1500']];
    }
}
