<?php

namespace App\Http\Requests\Cart;

use Illuminate\Foundation\Http\FormRequest;

class AddToCartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // any authenticated user (route middleware already enforces 'auth')
    }

    public function rules(): array
    {
        return [
            // product_id validated against the DB — this alone stops someone
            // POSTing an arbitrary/nonexistent id and causing a null-pointer
            // error deeper in the app.
            'product_id' => ['required', 'integer', 'exists:products,id'],

            // Hard cap of 100 per add — reasonable business limit, and also
            // blocks a scripted abuse case of someone posting quantity=999999999
            // repeatedly to probe for integer overflow / DoS behavior.
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}