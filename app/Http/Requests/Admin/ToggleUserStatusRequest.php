<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ToggleUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.manage');
    }

    public function rules(): array
    {
        return [];
    }
}
