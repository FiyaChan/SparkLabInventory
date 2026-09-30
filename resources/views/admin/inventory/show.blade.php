@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $product->name }}</h4>
        <p class="text-muted small mb-0">SKU: <code>{{ $product->sku }}</code> &bull; Manage physical warehouse adjustments and view historical stock audit logs.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-pencil me-1"></i>Edit Product
        </a>
        <a href="{{ route('admin.inventory.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Back to Inventory
        </a>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Action failed:</div>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-4">
    <div class="col-lg-4">
        {{-- Current Stock Card --}}
        <div class="admin-stat-card mb-4">
            <div class="admin-stat-label">Available Physical Stock</div>
            <div class="d-flex align-items-baseline gap-2">
                <div class="admin-stat-value text-primary">{{ $product->inventory->quantity_on_hand ?? 0 }}</div>
                <span class="text-muted fw-semibold">units</span>
            </div>
            <div class="small text-muted mt-2 pt-2 border-top">
                <span>Reorder Point Alert:</span> <strong>{{ $product->inventory->reorder_level ?? 10 }} units</strong>
            </div>
        </div>

        {{-- Record Stock Movement Form --}}
        <div class="admin-card">
            <div class="admin-card-header">
                <i class="bi bi-plus-slash-minus text-primary me-2"></i>Record Movement
            </div>
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.inventory.movement', $product) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Movement Type <span class="text-danger">*</span></label>
                        <select name="type" id="movementTypeSelect" class="form-select" required onchange="updateMovementForm()">
                            <option value="stock_in" @selected(old('type') === 'stock_in')>Stock In (Add to Stock)</option>
                            <option value="stock_out" @selected(old('type') === 'stock_out')>Stock Out (Remove from Stock)</option>
                            <option value="adjustment" @selected(old('type') === 'adjustment')>Stock Adjustment (Set Exact Physical Count)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="quantityLabel">Quantity to Add <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantityInput" min="1" class="form-control" value="{{ old('quantity') }}" placeholder="e.g. 50" required>
                        <div class="form-text small text-muted" id="quantityHelp">Units to add to current stock (Current: {{ $product->inventory->quantity_on_hand ?? 0 }}).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Reason / Reference <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g. PO #8892 or Physical Audit Count" required>
                    </div>

                    <button type="submit" class="btn btn-admin-primary w-100">
                        <i class="bi bi-check2-circle me-1"></i>Commit Stock Record
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clock-history text-primary"></i>
                    <span>Stock Movement Audit Ledger</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Action</th>
                            <th class="text-center">Qty Change</th>
                            <th>Reason / Reference</th>
                            <th>Recorded By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="small text-muted">{{ $movement->created_at->format('d M Y, H:i') }}</td>
                                <td>
                                    @if ($movement->type === 'stock_in')
                                        <span class="admin-badge success">STOCK IN</span>
                                    @elseif ($movement->type === 'stock_out')
                                        <span class="admin-badge danger">STOCK OUT</span>
                                    @else
                                        <span class="admin-badge info">ADJUSTMENT</span>
                                    @endif
                                </td>
                                <td class="text-center fw-bold {{ $movement->quantity >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $movement->quantity >= 0 ? '+' : '' }}{{ $movement->quantity }}
                                </td>
                                <td>{{ $movement->reason }}</td>
                                <td>
                                    <span class="small fw-semibold">{{ $movement->performedBy->name ?? 'System' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No historical movements logged for this product.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($movements->hasPages())
                <div class="p-3 border-top">
                    {{ $movements->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateMovementForm() {
    const type = document.getElementById('movementTypeSelect').value;
    const label = document.getElementById('quantityLabel');
    const input = document.getElementById('quantityInput');
    const help = document.getElementById('quantityHelp');
    const current = {{ (int) ($product->inventory->quantity_on_hand ?? 0) }};

    if (type === 'adjustment') {
        label.innerHTML = 'New Actual Physical Stock Count <span class="text-danger">*</span>';
        input.min = '0';
        input.placeholder = `e.g. ${current}`;
        help.innerText = `Inventory will be updated to exactly this number of units (Current: ${current}).`;
    } else if (type === 'stock_out') {
        label.innerHTML = 'Quantity to Remove <span class="text-danger">*</span>';
        input.min = '1';
        input.placeholder = 'e.g. 5';
        help.innerText = `Units to deduct from current stock (Current: ${current}).`;
    } else {
        label.innerHTML = 'Quantity to Add <span class="text-danger">*</span>';
        input.min = '1';
        input.placeholder = 'e.g. 50';
        help.innerText = `Units to add to current stock (Current: ${current}).`;
    }
}
document.addEventListener('DOMContentLoaded', updateMovementForm);
</script>
@endsection
