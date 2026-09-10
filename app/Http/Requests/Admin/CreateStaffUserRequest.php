<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CreateStaffUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only 'user.manage' permission — seeded to admin ONLY, never staff.
        // This is the actual RBAC enforcement point for "who can create
        // privileged accounts," distinct from the broader 'role:admin,staff'
        // route middleware that both roles share for day-to-day operations.
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['staff', 'admin'])],
        ];
    }
}