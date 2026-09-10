@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Business Intelligence & Reports</h4>
        <p class="text-muted small mb-0">Generate financial summaries, monitor customer volume, and export inventory analytics.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="admin-card h-100">
            <div class="admin-card-body d-flex flex-column">
                <div class="admin-stat-icon primary mb-3">
                    <i class="bi bi-cash-coin"></i>
                </div>
                <h5 class="fw-bold mb-2">Sales & Revenue Report</h5>
                <p class="text-muted small mb-4 flex-grow-1">Breakdown of gross revenue, order volume, and daily fulfillment metrics over custom date ranges with PDF/Excel export.</p>
                <a href="{{ route('admin.reports.sales') }}" class="btn btn-admin-primary">
                    <i class="bi bi-arrow-right-circle me-1"></i>View Sales Report
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="admin-card h-100">
            <div class="admin-card-body d-flex flex-column">
                <div class="admin-stat-icon success mb-3">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h5 class="fw-bold mb-2">Inventory Valuation Report</h5>
                <p class="text-muted small mb-4 flex-grow-1">Comprehensive audit of physical stock levels, total capital valuation, and automated low-stock vulnerability warnings.</p>
                <a href="{{ route('admin.reports.inventory') }}" class="btn btn-admin-primary">
                    <i class="bi bi-arrow-right-circle me-1"></i>View Inventory Report
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="admin-card h-100">
            <div class="admin-card-body d-flex flex-column">
                <div class="admin-stat-icon purple mb-3">
                    <i class="bi bi-people"></i>
                </div>
                <h5 class="fw-bold mb-2">Customer Activity Report</h5>
                <p class="text-muted small mb-4 flex-grow-1">Analysis of registered customers, lifetime order frequency, aggregated spending, and active account status.</p>
                <a href="{{ route('admin.reports.customers') }}" class="btn btn-admin-primary">
                    <i class="bi bi-arrow-right-circle me-1"></i>View Customer Report
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
