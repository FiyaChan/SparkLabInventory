@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Edit Product: {{ $product->name }}</h4>
        <p class="text-muted small mb-0">Modify catalog details, pricing rules, categories, and media assets.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.inventory.show', $product) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-stack me-1"></i>Manage Stock
        </a>
        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-admin-outline">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
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

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-info-circle text-primary me-2"></i>Product Details
                </div>
                <div class="admin-card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name', $product->name) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4">{{ old('description', $product->description) }}</textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">SKU Code <span class="text-danger">*</span></label>
                            <input type="text" name="sku" class="form-control" value="{{ old('sku', $product->sku) }}" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-currency-dollar text-success me-2"></i>Pricing Structure
                </div>
                <div class="admin-card-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Selling Price (RM) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">RM</span>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $product->price) }}" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cost Price (RM)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light">RM</span>
                                <input type="number" step="0.01" name="cost_price" class="form-control" value="{{ old('cost_price', $product->cost_price) }}">
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center gap-2 mb-0">
                        <i class="bi bi-info-circle-fill fs-5 text-primary"></i>
                        <div class="small">
                            Stock quantities are audited through ledger records. To adjust physical quantity, use the 
                            <a href="{{ route('admin.inventory.show', $product) }}" class="alert-link fw-semibold">Inventory Management</a> screen.
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
                        Manage options for this product (e.g. sizes, volumes, editions). If this product has no variations, leave this section empty.
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
                                @forelse($product->variations as $index => $variation)
                                    <tr>
                                        <td>
                                            <input type="hidden" name="variations[{{ $index }}][id]" value="{{ $variation->id }}">
                                            <input type="text" name="variations[{{ $index }}][name]" class="form-control form-control-sm" value="{{ old("variations.$index.name", $variation->name) }}" placeholder="e.g. 500ml / Size L" required>
                                        </td>
                                        <td>
                                            <input type="text" name="variations[{{ $index }}][sku]" class="form-control form-control-sm" value="{{ old("variations.$index.sku", $variation->sku) }}" placeholder="Auto / SKU">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-light">RM</span>
                                                <input type="number" step="0.01" min="0" name="variations[{{ $index }}][price]" class="form-control form-control-sm" value="{{ old("variations.$index.price", $variation->price) }}" placeholder="0.00" required>
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" min="0" name="variations[{{ $index }}][stock]" class="form-control form-control-sm" value="{{ old("variations.$index.stock", $variation->stock) }}" placeholder="0">
                                        </td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="this.closest('tr').remove(); checkVariationsCount();">
                                                <i class="bi bi-trash3"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <!-- No initial rows -->
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center text-muted py-3 border border-top-0 rounded-bottom-2 bg-light" id="noVariationsNotice" style="{{ $product->variations->isNotEmpty() ? 'display: none;' : '' }}">
                        <small>No variations added. Standard single product.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="admin-card mb-4">
                <div class="admin-card-header">
                    <i class="bi bi-images text-info me-2"></i>Media Gallery
                </div>
                <div class="admin-card-body">
                    @if ($product->images->isNotEmpty())
                        <label class="form-label mb-2">Existing Images</label>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($product->images as $image)
                                <div class="position-relative">
                                    <img src="{{ Storage::url($image->path) }}" style="width: 72px; height: 72px; object-fit: cover;"
                                         class="rounded border" alt="Product thumbnail">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label">Add Additional Images</label>
                        <input type="file" name="images[]" class="form-control" multiple accept="image/*">
                        <div class="form-text small text-muted mt-1">Up to 5 images, max 2MB each.</div>
                    </div>
                </div>
            </div>

            <div class="admin-card">
                <div class="admin-card-header">
                    <i class="bi bi-toggle-on text-primary me-2"></i>Visibility & Actions
                </div>
                <div class="admin-card-body">
                    <div class="form-check form-switch mb-3">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
                                @checked(old('is_active', $product->is_active))>
                        <label class="form-check-label fw-semibold" for="is_active">Active in Catalog</label>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-admin-primary">
                            <i class="bi bi-save me-1"></i>Update Product
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
    let variationIndex = {{ $product->variations->count() }};
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
