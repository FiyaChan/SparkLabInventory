<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PosCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('pos.create-order') || $this->user()->hasAnyRole(['admin', 'superadmin', 'staff']);
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.variation_id' => ['nullable', 'integer', 'exists:product_variations,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,qr,online_simulation'],
            'tendered_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string', 'in:fixed,percentage'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
