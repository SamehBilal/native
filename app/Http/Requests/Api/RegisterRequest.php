<?php

namespace App\Http\Requests\Api;

use App\ServiceType;
use App\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', new Enum(UserRole::class)],
            'service_types' => ['required_if:role,'.UserRole::Provider->value, 'array', 'min:1'],
            'service_types.*' => [new Enum(ServiceType::class)],
            'latitude' => ['required_if:role,'.UserRole::Provider->value, 'numeric', 'between:-90,90'],
            'longitude' => ['required_if:role,'.UserRole::Provider->value, 'numeric', 'between:-180,180'],
            'vehicle_info' => ['nullable', 'string', 'max:255'],
        ];
    }
}
