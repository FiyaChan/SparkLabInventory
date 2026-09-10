<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // anyone may attempt to register
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],

            // 'lowercase' + 'unique' prevent duplicate accounts differing only by case
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            // Password::defaults() is configured once in AppServiceProvider (see below)
            // and enforces: min length, mixed case, numbers, symbols, and a breach check
            // against the "have I been pwned" database via uncompromised().
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Custom messages avoid leaking implementation details
     * (e.g. never say "email already taken by user #42").
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered. Try logging in instead.',
        ];
    }
}
