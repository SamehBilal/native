<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServiceOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isProvider();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fee' => ['required', 'numeric', 'min:0', 'max:99999.99'],
            'eta_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'message' => ['nullable', 'string', 'max:255'],
        ];
    }
}
