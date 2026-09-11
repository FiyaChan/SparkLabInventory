<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // user can only ever edit their own profile (controller uses auth()->user())
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:500'],
            'tin' => ['nullable', 'string', 'max:30'],
            'id_type' => ['nullable', 'string', 'max:20', Rule::in(['NRIC', 'BRN', 'PASSPORT', 'ARMY', 'GENERAL_PUBLIC'])],
            'id_number' => ['nullable', 'string', 'max:30'],
            'sst_number' => ['nullable', 'string', 'max:30'],
        ];
    }
}
