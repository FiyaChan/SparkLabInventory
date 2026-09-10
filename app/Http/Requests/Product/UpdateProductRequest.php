<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('product.update');
    }

    public function rules(): array
    {
        // route('product') resolves the bound Product model from the route,
        // e.g. Route::put('/products/{product}', ...)
        $productId = $this->route('product')->id;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Product Variations (optional)
            'variations' => ['nullable', 'array'],
            'variations.*.id' => ['nullable', 'integer'],
            'variations.*.name' => ['required_with:variations', 'string', 'max:255'],
            'variations.*.sku' => ['nullable', 'string', 'max:100'],
            'variations.*.price' => ['required_with:variations', 'numeric', 'min:0', 'max:999999.99'],
            'variations.*.cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variations.*.stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
