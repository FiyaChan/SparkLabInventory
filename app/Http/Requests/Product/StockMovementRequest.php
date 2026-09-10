<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('inventory.adjust');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment'])],

            // Always a positive number in the form; the service decides the sign
            // based on 'type' — keeps the UI intuitive ("add 50 units") while the
            // ledger internally stores signed deltas for easy SUM() calculations.
            'quantity' => ['required', 'integer', 'min:1'],

            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
