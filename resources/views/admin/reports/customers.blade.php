@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Performance Report</h4>
        <p class="text-muted small mb-0">Track customer registrations, lifetime order count, and gross spend values.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.reports.customers.export.excel') }}" class="btn btn-sm btn-outline-success">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i>Export Excel
        </a>
        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Reports Menu
        </a>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Email Address</th>
                    <th>Join Date</th>
                    <th class="text-center">Total Orders</th>
                    <th class="text-end">Lifetime Spend</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr>
                        <td class="fw-semibold">{{ $customer->name }}</td>
                        <td>{{ $customer->email }}</td>
                        <td class="small text-muted">{{ $customer->created_at->format('d M Y') }}</td>
                        <td class="text-center fw-bold">{{ $customer->orders_count }}</td>
                        <td class="text-end fw-bold text-success">RM {{ number_format($customer->total_spent ?? 0, 2) }}</td>
                        <td>
                            <span class="admin-badge {{ $customer->is_active ? 'success' : 'secondary' }}">
                                {{ $customer->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No registered customers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($customers->hasPages())
        <div class="p-3 border-top">
            {{ $customers->links() }}
        </div>
    @endif
</div>
@endsection
