<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Product::with(['category', 'inventory'])
            ->where('is_active', true)
            ->get();
    }

    public function headings(): array
    {
        return ['SKU', 'Product', 'Category', 'Stock On Hand', 'Reorder Level', 'Status', 'Unit Price (RM)', 'Stock Value (RM)'];
    }

    public function map($product): array
    {
        $qty = $product->inventory->quantity_on_hand ?? 0;
        $reorder = $product->inventory->reorder_level ?? 0;

        return [
            $product->sku,
            $product->name,
            $product->category->name ?? '-',
            $qty,
            $reorder,
            $qty <= $reorder ? 'LOW STOCK' : 'OK',
            number_format($product->price, 2),
            number_format($qty * $product->price, 2),
        ];
    }
}
