@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Add New Product</h4>
        <p class="text-muted small mb-0">Create a new item in the central catalog with SKU, pricing, and initial stock level.</p>
    </div>
    <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-admin-outline">
        <i class="bi bi-arrow-left me-1"></i>Back to Products
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please correct the following errors:</div>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-info-circle text-primary me-2"></i>General Information
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Laboratory Microscope X-200" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Detailed technical specifications, chemical formula, or experimental instructions...">{{ old('description') }}</textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select category...</option>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU / Code <span class="text-muted small">(auto-generated if left blank)</span></label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku') }}" placeholder="e.g. SCI-LAB-009">
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-currency-dollar text-success me-2"></i>Pricing & Inventory Initialization
                </div>
                <div class="admin-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (RM) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">RM</span>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price') }}" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price (RM)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">RM</span>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price') }}" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Base Stock Quantity</label>
                            <input type="number" name="initial_quantity" class="form-control" value="{{ old('initial_quantity', 0) }}" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reorder Level Alert Threshold</label>
                            <input type="number" name="reorder_level" class="form-control" value="{{ old('reorder_level', 10) }}" min="0">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Variations Card (Optional) -->
            <div class="admin-card mb-4">
                <div class="admin-card-header d-flex justify-content-between align-items-center">
                    <div>
                        <i class="bi bi-layers text-purple me-2" style="color: #7E22CE;"></i>Product Variations (Optional)
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnAddVariationRow" style="font-size: 0.78rem;">
                        <i class="bi bi-plus-lg me-1"></i>Add Variation
                    </button>
                </div>
                <div class="admin-card-body">
                    <p class="text-muted small mb-3">
                        Add variations if this product comes in different options (e.g. <em>500ml, 1 Liter</em>, <em>Size: Large</em>, or <em>Deluxe Pack</em>). If this product has no variations, leave this section empty.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0" id="variationsTable" style="font-size: 0.82rem;">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 150px;">Variation Name <span class="text-danger">*</span></th>
                                    <th style="width: 140px;">SKU (Optional)</th>
                                    <th style="width: 120px;">Price (RM) <span class="text-danger">*</span></th>
                                    <th style="width: 90px;">Stock</th>
                                    <th style="width: 40px;" class="text-center"></th>
                                </tr>
                            </thead>
                            <tbody id="variationsTableBody">
                                <!-- Dynamic rows -->
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted py-3 border border-top-0 rounded-bottom-2 bg-light" id="noVariationsNotice">
                        <small>No variations added. Standard single product.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-images text-info me-2"></i>Product Media
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">Upload Images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                        <div class="form-text small text-muted mt-1">Up to 5 images (JPG, PNG, WebP), max 2MB each.</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <i class="bi bi-toggle-on text-primary me-2"></i>Publish Status
                </div>
                <div class="admin-card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" @checked(old('is_active', true))>
                        <label class="form-check-label fw-semibold" for="is_active">Publish in Store</label>
                    </div>
                    <p class="text-muted small mb-3">When enabled, customers can view and purchase this product in the public catalog.</p>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="bi bi-check2-circle me-1"></i>Save & Publish Product
                        </button>
                        <a href="{{ route('admin.products.index') }}" class="btn btn-admin-outline">Cancel</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    let variationIndex = 0;
    const variationsTableBody = document.getElementById('variationsTableBody');
    const noVariationsNotice = document.getElementById('noVariationsNotice');
    const btnAddVariationRow = document.getElementById('btnAddVariationRow');

    function checkVariationsCount() {
        const rows = variationsTableBody.querySelectorAll('tr');
        if (rows.length === 0) {
            noVariationsNotice.style.display = 'block';
        } else {
            noVariationsNotice.style.display = 'none';
        }
    }

    btnAddVariationRow.addEventListener('click', function() {
        const basePrice = document.querySelector('input[name="price"]').value || '';
        const baseSku = document.querySelector('input[name="sku"]').value || '';
        const defaultSku = baseSku ? `${baseSku}-V${variationIndex + 1}` : '';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="text" name="variations[${variationIndex}][name]" class="form-control form-control-sm" placeholder="e.g. 500ml / Size L" required>
            </td>
            <td>
                <input type="text" name="variations[${variationIndex}][sku]" class="form-control form-control-sm" placeholder="Auto / SKU" value="${defaultSku}">
            </td>
            <td>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light">RM</span>
                    <input type="number" step="0.01" min="0" name="variations[${variationIndex}][price]" class="form-control form-control-sm" placeholder="0.00" value="${basePrice}" required>
                </div>
            </td>
            <td>
                <input type="number" min="0" name="variations[${variationIndex}][stock]" class="form-control form-control-sm" placeholder="0" value="0">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); checkVariationsCount();">
                    <i class="bi bi-trash3"></i>
                </button>
            </td>
        `;
        variationsTableBody.appendChild(tr);
        variationIndex++;
        checkVariationsCount();
    });

    checkVariationsCount();
</script>
@endpush
