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
        $minQuantity = $this->input('type') === 'adjustment' ? 0 : 1;

        return [
            'type' => ['required', Rule::in(['stock_in', 'stock_out', 'adjustment'])],

            // Always a positive number in the form; for adjustments, 0 is allowed (e.g. write-off to zero)
            'quantity' => ['required', 'integer', "min:{$minQuantity}"],

            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
