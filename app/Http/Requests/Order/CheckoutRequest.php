<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // route middleware already enforces 'auth'
    }

    public function rules(): array
    {
        return [
            'shipping_name' => ['required', 'string', 'max:255'],
            'shipping_phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]+$/'],
            'shipping_address' => ['required', 'string', 'max:500'],

            // Never trust a client-submitted amount or "is_paid" flag here —
            // notice there is NO total_amount field in this request at all.
            // The server always recalculates the total from live cart +
            // product prices inside OrderService — this is what stops a
            // tampered request from checking out at an attacker-chosen price.
            'payment_method' => ['required', Rule::in(['cod', 'online_simulation'])],
        ];
    }
}
