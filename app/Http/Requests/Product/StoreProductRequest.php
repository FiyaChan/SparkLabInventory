<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Uses the Spatie permission checked via User::can() (backed by our
        // seeded 'product.create' permission) rather than a bare role check —
        // lets us later grant this to a new role without touching controllers.
        return $this->user()->can('product.create');
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],

            // Initial stock — optional at creation time, handled via StockService
            'initial_quantity' => ['nullable', 'integer', 'min:0'],
            'reorder_level' => ['nullable', 'integer', 'min:0'],

            // Images: strict MIME + size limits. 'image' rule itself already blocks
            // non-image files; max:2048 caps at 2MB to prevent storage abuse / DoS
            // via oversized uploads.
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],

            // Product Variations (optional)
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['required_with:variations', 'string', 'max:255'],
            'variations.*.sku' => ['nullable', 'string', 'max:100'],
            'variations.*.price' => ['required_with:variations', 'numeric', 'min:0', 'max:999999.99'],
            'variations.*.cost_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'variations.*.stock' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Auto-generate a SKU if the admin left it blank, so "SKU Management"
     * doesn't become a blocking manual step for every single product.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('sku')) {
            $this->merge([
                'sku' => 'SKU-'.strtoupper(Str::random(8)),
            ]);
        }

        // Always generate slug server-side from name — never trust a client-submitted slug,
        // which could otherwise be used to inject unexpected URL segments.
        $this->merge([
            'slug' => Str::slug($this->input('name')).'-'.Str::random(6),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
