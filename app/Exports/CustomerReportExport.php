<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        // withCount/withSum push the aggregation into SQL rather than pulling
        // every order into PHP memory and summing there — matters once the
        // orders table has thousands of rows.
        return User::role('customer')
            ->withCount('orders')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', 'completed')], 'total_amount')
            ->get();
    }

    public function headings(): array
    {
        return ['Name', 'Email', 'Registered', 'Total Orders', 'Total Spent (RM)', 'Account Status'];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->email,
            $user->created_at->format('Y-m-d'),
            $user->orders_count,
            number_format($user->total_spent ?? 0, 2),
            $user->is_active ? 'Active' : 'Disabled',
        ];
    }
}
