@extends('layouts.admin')

@section('content')
<div class="pos-wrapper mb-4">
    <!-- Top Compact Header Bar -->
    <div class="card border-0 shadow-sm rounded-3 mb-3 bg-white">
        <div class="card-body py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="p-1 px-2 rounded-2 text-white bg-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                    <i class="bi bi-shop-window" style="font-size: 0.95rem;"></i>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark" style="font-size: 0.9rem;">Cashier Terminal</h6>
                    <span class="text-muted" style="font-size: 0.72rem;">Cashier: <strong class="text-primary">{{ Auth::user()->name }}</strong> &bull; Terminal #01</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Held Orders Button -->
                <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 d-flex align-items-center gap-1" id="btnViewHeldOrders" data-bs-toggle="modal" data-bs-target="#heldOrdersModal" style="font-size: 0.78rem;">
                    <i class="bi bi-pause-circle"></i>
                    <span>Held Carts</span>
                    <span class="badge bg-danger rounded-pill ms-1" id="heldOrdersCountBadge" style="display: none; font-size: 0.65rem;">0</span>
                </button>

                <div class="vr mx-1 d-none d-sm-block"></div>

                <div class="text-end d-none d-sm-block">
                    <div class="fw-bold text-dark" id="posClock" style="font-size: 0.8rem;">--:--:--</div>
                    <div class="text-muted" style="font-size: 0.68rem;">{{ date('D, d M Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main POS Workspace: Catalog (Left) + Cart (Right) -->
    <div class="row g-3">
        <!-- LEFT: Product Search, Category Tabs & Grid -->
        <div class="col-lg-7 col-xl-8">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white">
                <div class="card-body p-3">
                    <!-- Search & Filter Controls -->
                    <div class="row g-2 mb-2">
                        <div class="col-md-7">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0 text-muted">
                                    <i class="bi bi-search" style="font-size: 0.75rem;"></i>
                                </span>
                                <input type="text" class="form-control bg-light border-start-0 border-end-0 py-1" id="productSearchInput" 
                                       placeholder="Search item name, SKU or scan barcode... (F2)" autofocus autocomplete="off" style="font-size: 0.82rem;">
                                <button class="btn btn-light border border-start-0 text-muted py-1" type="button" id="btnClearSearch" title="Clear search">
                                    <i class="bi bi-x-lg" style="font-size: 0.75rem;"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <select class="form-select form-select-sm bg-light py-1" id="categoryFilterSelect" style="font-size: 0.82rem;">
                                <option value="all">All Categories ({{ $products->total() ?? count($products) }})</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }} ({{ $category->products_count }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Category Pills Filter -->
                    <div class="category-pills-bar d-flex gap-1 overflow-x-auto pb-2 mb-2" style="white-space: nowrap; scrollbar-width: none;">
                        <button type="button" class="btn btn-sm btn-primary cat-pill-btn active py-1 px-2" data-category="all">
                            All
                        </button>
                        @foreach($categories as $category)
                            <button type="button" class="btn btn-sm btn-light border cat-pill-btn text-muted py-1 px-2" data-category="{{ $category->id }}">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>

                    <!-- Product Grid -->
                    <div class="pos-products-scroll" style="max-height: 570px; overflow-y: auto; padding-right: 2px;">
                        <div class="row g-2" id="productsGrid">
                            @forelse($products as $product)
                                @php
                                    $hasVariations = $product->variations->isNotEmpty();
                                    $stock = $hasVariations ? $product->variations->sum('stock') : ($product->inventory ? $product->inventory->quantity_on_hand : 0);
                                    $reorderLevel = $product->inventory ? $product->inventory->reorder_level : 5;
                                    $isLowStock = $stock > 0 && $stock <= $reorderLevel;
                                    $isOutOfStock = $stock <= 0;
                                    $imgUrl = ($product->primaryImage && $product->primaryImage->path) ? Storage::url($product->primaryImage->path) : null;
                                    
                                    $variationsJson = $product->variations->map(fn($v) => [
                                        'id' => $v->id,
                                        'product_id' => $product->id,
                                        'name' => $v->name,
                                        'sku' => $v->sku ?: ($product->sku . '-' . $v->id),
                                        'price' => (float) $v->price,
                                        'formatted_price' => 'RM ' . number_format($v->price, 2),
                                        'stock' => (int) $v->stock,
                                        'is_in_stock' => $v->stock > 0
                                    ]);
                                @endphp
                                <div class="col-6 col-sm-4 col-md-3 col-xl-3 product-item-col" 
                                     data-id="{{ $product->id }}" 
                                     data-name="{{ $product->name }}" 
                                     data-sku="{{ $product->sku }}" 
                                     data-price="{{ (float)$product->price }}" 
                                     data-stock="{{ $stock }}" 
                                     data-category="{{ $product->category_id }}"
                                     data-has-variations="{{ $hasVariations ? '1' : '0' }}"
                                     data-variations="{{ json_encode($variationsJson) }}">
                                    <div class="card h-100 product-card border rounded-3 p-2 text-start position-relative {{ $isOutOfStock ? 'out-of-stock opacity-50' : 'cursor-pointer' }}" 
                                         onclick="posCart.onProductCardClick(this.parentElement)">
                                        
                                        <!-- Stock / Variation Badges -->
                                        <div class="position-absolute top-0 end-0 m-1 d-flex flex-column align-items-end gap-1">
                                            @if($isOutOfStock)
                                                <span class="badge bg-danger-subtle text-danger" style="font-size: 0.62rem; padding: 2px 5px;">Out</span>
                                            @elseif($isLowStock)
                                                <span class="badge bg-warning-subtle text-warning-emphasis" style="font-size: 0.62rem; padding: 2px 5px;">Stock: {{ $stock }}</span>
                                            @else
                                                <span class="badge bg-success-subtle text-success" style="font-size: 0.62rem; padding: 2px 5px;">Stock: {{ $stock }}</span>
                                            @endif

                                            @if($hasVariations)
                                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.58rem; padding: 1px 4px;">
                                                    <i class="bi bi-layers me-1"></i>{{ $product->variations->count() }} Variants
                                                </span>
                                            @endif
                                        </div>

                                        <!-- Product Image / Icon -->
                                        <div class="product-thumb rounded-2 mb-2 bg-light d-flex align-items-center justify-content-center overflow-hidden" style="height: 80px;">
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" alt="{{ $product->name }}" class="img-fluid" style="max-height: 75px; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\'bi bi-box text-muted opacity-40 fs-2\'></i>';">
                                            @else
                                                <i class="bi bi-box text-muted opacity-40 fs-2"></i>
                                            @endif
                                        </div>

                                        <div class="d-flex flex-column justify-content-between flex-grow-1">
                                            <div>
                                                <div class="text-muted text-uppercase" style="font-size: 0.62rem; letter-spacing: 0.03em;">{{ $product->sku }}</div>
                                                <div class="fw-semibold text-dark text-truncate" style="font-size: 0.8rem;" title="{{ $product->name }}">{{ $product->name }}</div>
                                            </div>
                                            <div class="mt-2 pt-1 border-top d-flex justify-content-between align-items-center">
                                                <span class="fw-bold text-primary" style="font-size: 0.88rem;">RM {{ number_format($product->price, 2) }}</span>
                                                <span class="badge bg-primary-subtle text-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                                    <i class="bi {{ $hasVariations ? 'bi-list' : 'bi-plus' }}" style="font-size: 0.75rem;"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12 text-center py-5 text-muted small">
                                    <i class="bi bi-inbox fs-2 d-block mb-1 text-secondary opacity-50"></i>
                                    No products found in catalog.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT: Active Cart Summary -->
        <div class="col-lg-5 col-xl-4">
            <div class="card border-0 shadow-sm rounded-3 h-100 bg-white d-flex flex-column">
                <!-- Customer Header Selector -->
                <div class="card-header bg-white border-bottom p-2 px-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted fw-semibold" style="font-size: 0.75rem;">
                            <i class="bi bi-person text-primary me-1"></i>Customer
                        </span>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none p-0 text-primary" id="btnChangeCustomer" data-bs-toggle="modal" data-bs-target="#customerModal" style="font-size: 0.75rem;">
                            Select / Search &rarr;
                        </button>
                    </div>
                    <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 border bg-light">
                        <div class="d-flex align-items-center gap-2 text-truncate">
                            <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                <i class="bi bi-person"></i>
                            </div>
                            <div class="text-truncate">
                                <div class="fw-bold text-dark text-truncate" id="selectedCustomerName" style="font-size: 0.78rem;">Walk-in Customer</div>
                                <div class="text-muted" style="font-size: 0.68rem;" id="selectedCustomerInfo">Standard Counter Sale</div>
                            </div>
                        </div>
                        <input type="hidden" id="selectedCustomerId" value="{{ $walkinCustomer->id ?? '' }}">
                        <button type="button" class="btn btn-sm btn-light border-0 text-secondary p-0 px-1" id="btnResetToWalkin" title="Reset to Walk-in">
                            <i class="bi bi-arrow-counterclockwise" style="font-size: 0.75rem;"></i>
                        </button>
                    </div>
                </div>

                <!-- Cart Line Items -->
                <div class="card-body p-0 flex-grow-1 d-flex flex-column" style="min-height: 240px;">
                    <div class="cart-items-box flex-grow-1 overflow-y-auto p-2" id="cartItemsList" style="max-height: 300px;">
                        <!-- Injected by JavaScript -->
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-cart3 fs-2 text-secondary opacity-40 d-block mb-1"></i>
                            <div class="fw-semibold small">Cart is empty</div>
                            <div class="text-muted" style="font-size: 0.72rem;">Click items on the left to add</div>
                        </div>
                    </div>
                </div>

                <!-- Financial Calculation & Actions -->
                <div class="card-footer bg-white border-top p-3">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between text-muted mb-1" style="font-size: 0.78rem;">
                            <span>Subtotal (<span id="cartItemsCount">0</span> items)</span>
                            <span class="fw-semibold text-dark" id="cartSubtotalText">RM 0.00</span>
                        </div>

                        <!-- Discount Line -->
                        <div class="d-flex align-items-center justify-content-between text-muted mb-2" style="font-size: 0.8rem;">
                            <span class="text-secondary fw-semibold">Discount</span>
                            <div class="input-group input-group-sm" style="width: 140px;">
                                <select class="form-select form-select-sm py-1 px-2 fw-bold text-primary bg-light border" id="discountTypeSelect" style="max-width: 65px; font-size: 0.78rem; cursor: pointer;">
                                    <option value="fixed">RM</option>
                                    <option value="percentage">%</option>
                                </select>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm text-end fw-semibold py-1 px-2 border" id="discountValueInput" placeholder="0" value="0" style="font-size: 0.82rem;">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                            <span class="fw-bold text-dark" style="font-size: 0.95rem;">Grand Total</span>
                            <span class="fw-extrabold text-primary" id="cartGrandTotalText" style="font-size: 1.25rem;">RM 0.00</span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-primary fw-bold d-flex justify-content-between align-items-center py-2 px-3 shadow-sm rounded-3" id="btnOpenPaymentModal" disabled style="font-size: 0.88rem;">
                            <span><i class="bi bi-wallet2 me-2"></i>Pay Now (F4)</span>
                            <span id="btnPayAmount">RM 0.00</span>
                        </button>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary w-50 py-1" id="btnHoldOrder" title="Hold Order (F8)" disabled style="font-size: 0.75rem;">
                                <i class="bi bi-pause-fill me-1"></i>Hold Cart
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger w-50 py-1" id="btnClearCart" title="Clear Cart" disabled style="font-size: 0.75rem;">
                                <i class="bi bi-trash3 me-1"></i>Clear
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Product Variation Selector                                        -->
<!-- (Only opens when product has variations; otherwise skipped automatically)  -->
<!-- ========================================================================= -->
<div class="modal fade" id="variationModal" tabindex="-1" aria-labelledby="variationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
                <div>
                    <h6 class="modal-title fw-bold text-dark mb-0" id="variationModalTitle" style="font-size: 0.88rem;">Select Product Option</h6>
                    <span class="text-muted" id="variationModalSubtitle" style="font-size: 0.72rem;">Choose variation to add to cart</span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-3">
                <div class="list-group list-group-flush gap-2" id="variationOptionsList">
                    <!-- Populated dynamically via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Payment Checkout (Cash & QR Pay Only)                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-primary text-white py-2 px-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-wallet2" style="font-size: 1.1rem;"></i>
                    <h6 class="modal-title fw-bold mb-0" id="paymentModalLabel" style="font-size: 0.9rem;">Complete Payment</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>

            <div class="modal-body p-3">
                <!-- Total Amount Banner -->
                <div class="p-2 rounded-3 text-center mb-3 bg-light border">
                    <span class="text-muted text-uppercase fw-semibold d-block" style="font-size: 0.7rem;">Total Amount</span>
                    <span class="fw-bold text-primary" id="modalTotalDueText" style="font-size: 1.5rem;">RM 0.00</span>
                </div>

                <!-- Payment Method Selector (Cash vs QR Only) -->
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-outline-primary active w-50 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 payment-method-btn" id="btnMethodCash" data-method="cash" style="font-size: 0.85rem;">
                        <i class="bi bi-cash-stack"></i>
                        <span>Cash</span>
                    </button>
                    <button type="button" class="btn btn-outline-primary w-50 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 payment-method-btn" id="btnMethodQr" data-method="qr" style="font-size: 0.85rem;">
                        <i class="bi bi-qr-code"></i>
                        <span>QR Pay</span>
                    </button>
                </div>

                <!-- 1. CASH SECTION -->
                <div id="cashPaymentSection">
                    <div class="mb-2">
                        <label for="tenderedAmountInput" class="form-label fw-semibold text-muted mb-1" style="font-size: 0.75rem;">Cash Tendered (RM)</label>
                        <div class="input-group">
                            <span class="input-group-text fw-bold text-muted small">RM</span>
                            <input type="number" step="0.01" class="form-control fw-bold text-primary" 
                                   id="tenderedAmountInput" placeholder="0.00" autocomplete="off" style="font-size: 1.1rem;">
                        </div>
                    </div>

                    <!-- Quick Preset Cash Chips -->
                    <div class="d-flex flex-wrap gap-1 mb-3">
                        <button type="button" class="btn btn-sm btn-light border quick-cash-btn py-1 px-2" data-val="exact" style="font-size: 0.72rem;">Exact</button>
                        <button type="button" class="btn btn-sm btn-light border quick-cash-btn py-1 px-2" data-val="10" style="font-size: 0.72rem;">RM 10</button>
                        <button type="button" class="btn btn-sm btn-light border quick-cash-btn py-1 px-2" data-val="20" style="font-size: 0.72rem;">RM 20</button>
                        <button type="button" class="btn btn-sm btn-light border quick-cash-btn py-1 px-2" data-val="50" style="font-size: 0.72rem;">RM 50</button>
                        <button type="button" class="btn btn-sm btn-light border quick-cash-btn py-1 px-2" data-val="100" style="font-size: 0.72rem;">RM 100</button>
                    </div>

                    <!-- Change Calculation Card -->
                    <div class="card border rounded-3 p-2 text-center bg-light mb-2">
                        <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.68rem;">Change Due</span>
                        <span class="fw-bold text-success my-1" id="changeDueText" style="font-size: 1.3rem;">RM 0.00</span>
                        <span class="text-muted" id="changeDueStatus" style="font-size: 0.72rem;">Exact Amount</span>
                    </div>
                </div>

                <!-- 2. QR CODE SECTION -->
                <div id="qrPaymentSection" class="d-none text-center">
                    <div class="p-3 bg-light rounded-3 border mb-2">
                        <p class="text-muted small mb-2" style="font-size: 0.75rem;">Scan with banking app or DuitNow QR</p>
                        <div class="p-2 bg-white d-inline-block rounded border shadow-sm mb-2">
                            <i class="bi bi-qr-code text-dark" style="font-size: 110px;"></i>
                            <div class="fw-bold text-primary mt-1" id="qrAmountDisplay" style="font-size: 0.9rem;">RM 0.00</div>
                        </div>
                        <div class="text-muted" style="font-size: 0.7rem;">Click "Complete Sale" once customer confirms payment.</div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light py-2 px-3">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold px-3 py-2" id="btnSubmitPayment">
                    <span class="spinner-border spinner-border-sm d-none me-1" id="paymentSpinner" role="status"></span>
                    <i class="bi bi-check2-circle me-1" id="paymentCheckIcon"></i>
                    <span>Complete Sale & Print</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Customer Search & Select                                           -->
<!-- ========================================================================= -->
<div class="modal fade" id="customerModal" tabindex="-1" aria-labelledby="customerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title fw-bold" id="customerModalLabel" style="font-size: 0.88rem;"><i class="bi bi-person text-primary me-2"></i>Select Customer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-3">
                <div class="input-group input-group-sm mb-2">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search" style="font-size: 0.75rem;"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="customerSearchInput" placeholder="Search name, email, phone..." style="font-size: 0.82rem;">
                </div>

                <div class="list-group list-group-flush border rounded-3 overflow-y-auto" id="customerSearchResults" style="max-height: 240px;">
                    <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2"
                       data-id="{{ $walkinCustomer->id ?? '' }}" data-name="Walk-in Customer" data-phone="N/A" data-email="walkin@pos.local">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.8rem;">Walk-in Customer</div>
                            <div class="text-muted" style="font-size: 0.68rem;">Default Counter Sale</div>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Default</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Held Orders (Suspended Carts)                                      -->
<!-- ========================================================================= -->
<div class="modal fade" id="heldOrdersModal" tabindex="-1" aria-labelledby="heldOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header py-2 px-3">
                <h6 class="modal-title fw-bold" id="heldOrdersModalLabel" style="font-size: 0.88rem;"><i class="bi bi-pause-circle text-warning me-2"></i>Held Orders</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="font-size: 0.75rem;"></button>
            </div>
            <div class="modal-body p-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.8rem;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Held At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="heldOrdersTableBody">
                            <tr>
                                <td colspan="6" class="text-center text-muted py-3">No held orders.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL: Post-Checkout Success & Print Receipt                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 text-center p-3">
            <div class="mb-2">
                <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                    <i class="bi bi-check-lg fs-2"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1" style="font-size: 1rem;">Payment Successful!</h6>
            <p class="text-muted mb-2" style="font-size: 0.75rem;">Receipt: <strong class="text-primary" id="successOrderNumber">POS-XXXX</strong></p>

            <div class="p-2 bg-light rounded-3 text-start mb-3 border" style="font-size: 0.78rem;">
                <div class="d-flex justify-content-between text-muted mb-1">
                    <span>Total Paid:</span>
                    <strong class="text-dark" id="successTotalPaid">RM 0.00</strong>
                </div>
                <div class="d-flex justify-content-between text-muted mb-1">
                    <span>Method:</span>
                    <span class="badge bg-primary-subtle text-primary" id="successPaymentMethod" style="font-size: 0.68rem;">CASH</span>
                </div>
                <div class="d-flex justify-content-between text-muted">
                    <span>Change:</span>
                    <strong class="text-success" id="successChangeDue">RM 0.00</strong>
                </div>
            </div>

            <div class="d-grid gap-2">
                <a href="#" target="_blank" class="btn btn-primary py-2 fw-bold" id="btnPrintReceipt" style="font-size: 0.85rem;">
                    <i class="bi bi-printer me-1"></i>Print Receipt (80mm)
                </a>
                <a href="#" class="btn btn-outline-secondary btn-sm" id="btnDownloadPdfReceipt" style="font-size: 0.75rem;">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF Invoice
                </a>
                <button type="button" class="btn btn-light border btn-sm mt-1" data-bs-dismiss="modal" id="btnNewSale" style="font-size: 0.75rem;">
                    <i class="bi bi-plus-circle me-1"></i>New Sale
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .pos-wrapper {
        font-size: 0.82rem;
    }
    .product-card {
        transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
        background: #ffffff;
        border-color: #e2e8f0 !important;
    }
    .product-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.06);
        border-color: #3b82f6 !important;
    }
    .product-card:active {
        transform: scale(0.98);
    }
    .product-card.out-of-stock {
        pointer-events: none;
        background: #f8fafc;
    }
    .cursor-pointer { cursor: pointer; }
    .cat-pill-btn {
        border-radius: 16px;
        font-size: 0.75rem;
    }
    .cart-row {
        transition: background 0.1s;
    }
    .cart-row:hover {
        background-color: #f8fafc;
    }
    .payment-method-btn.active {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }
    .variation-option-card {
        cursor: pointer;
        transition: all 0.15s ease;
        border: 1px solid #e2e8f0;
    }
    .variation-option-card:hover {
        border-color: #3b82f6;
        background-color: #eff6ff;
        transform: translateY(-1px);
    }
</style>
@endpush

@push('scripts')
<script>
    // Live Clock Display
    function updateClock() {
        const now = new Date();
        document.getElementById('posClock').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // =========================================================================
    // POS Cart State Management
    // =========================================================================
    const posCart = {
        items: [],
        customer: {
            id: document.getElementById('selectedCustomerId').value,
            name: 'Walk-in Customer',
            phone: 'N/A',
            email: 'walkin@pos.local'
        },
        discount: {
            type: 'fixed',
            value: 0
        },
        activePaymentMethod: 'cash',

        init() {
            this.render();
            this.bindEvents();
            this.updateHeldCountBadge();
        },

        // Triggered when clicking a product card in the catalog
        onProductCardClick(cardCol) {
            const hasVariations = cardCol.getAttribute('data-has-variations') === '1';
            const variationsAttr = cardCol.getAttribute('data-variations');
            const variations = variationsAttr ? JSON.parse(variationsAttr) : [];

            const id = parseInt(cardCol.getAttribute('data-id'));
            const name = cardCol.getAttribute('data-name');
            const sku = cardCol.getAttribute('data-sku');
            const price = parseFloat(cardCol.getAttribute('data-price'));
            const stock = parseInt(cardCol.getAttribute('data-stock'));

            // If product has variations, open variation selection modal
            if (hasVariations && variations && variations.length > 0) {
                this.openVariationModal({
                    id: id,
                    name: name,
                    sku: sku,
                    price: price,
                    variations: variations
                });
            } else {
                // Product does NOT have variations -> add directly to cart
                this.addItem(id, null, name, null, sku, price, stock);
            }
        },

        openVariationModal(product) {
            document.getElementById('variationModalTitle').innerText = product.name;
            document.getElementById('variationModalSubtitle').innerText = `Base SKU: ${product.sku} &bull; Select variation:`;

            const listEl = document.getElementById('variationOptionsList');
            let html = '';

            product.variations.forEach(v => {
                const isOutOfStock = v.stock <= 0;
                const stockBadge = isOutOfStock
                    ? `<span class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">Out of Stock</span>`
                    : `<span class="badge bg-success-subtle text-success" style="font-size: 0.65rem;">Stock: ${v.stock}</span>`;

                html += `
                    <div class="variation-option-card p-2 px-3 rounded-3 bg-white d-flex justify-content-between align-items-center ${isOutOfStock ? 'opacity-50' : ''}"
                         onclick="${isOutOfStock ? 'alert(\'This variation is out of stock\')' : `posCart.selectVariation(${product.id}, ${v.id}, '${product.name.replace(/'/g, "\\'")}', '${v.name.replace(/'/g, "\\'")}', '${v.sku.replace(/'/g, "\\'")}', ${v.price}, ${v.stock})`}">
                        <div>
                            <div class="fw-bold text-dark" style="font-size: 0.82rem;">${v.name}</div>
                            <div class="text-muted" style="font-size: 0.68rem;">SKU: ${v.sku} &bull; ${stockBadge}</div>
                        </div>
                        <div class="text-end">
                            <span class="fw-bold text-primary d-block" style="font-size: 0.9rem;">${v.formatted_price}</span>
                            <span class="btn btn-sm btn-primary py-0 px-2 mt-1" style="font-size: 0.7rem;" ${isOutOfStock ? 'disabled' : ''}>
                                <i class="bi bi-plus me-1"></i>Select
                            </span>
                        </div>
                    </div>`;
            });

            listEl.innerHTML = html;
            const modal = new bootstrap.Modal(document.getElementById('variationModal'));
            modal.show();
        },

        selectVariation(productId, variationId, productName, variantName, sku, price, stock) {
            const modalEl = document.getElementById('variationModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            this.addItem(productId, variationId, productName, variantName, sku, price, stock);
        },

        addItem(productId, variationId, name, variantName, sku, price, stock) {
            if (stock <= 0) {
                alert('Item is out of stock.');
                return;
            }

            const itemKey = variationId ? `${productId}_${variationId}` : `${productId}`;
            const existing = this.items.find(item => item.key === itemKey);

            if (existing) {
                if (existing.quantity >= stock) {
                    alert(`Maximum available stock reached (${stock}).`);
                    return;
                }
                existing.quantity++;
            } else {
                this.items.push({
                    key: itemKey,
                    id: productId,
                    variation_id: variationId || null,
                    variant_name: variantName || null,
                    name: name,
                    sku: sku,
                    price: price,
                    stock: stock,
                    quantity: 1
                });
            }
            this.render();
        },

        updateQuantity(key, newQty) {
            const item = this.items.find(item => item.key === key);
            if (!item) return;

            newQty = parseInt(newQty);
            if (isNaN(newQty) || newQty <= 0) {
                this.removeItem(key);
                return;
            }

            if (newQty > item.stock) {
                alert(`Requested quantity exceeds available stock (${item.stock}).`);
                item.quantity = item.stock;
            } else {
                item.quantity = newQty;
            }
            this.render();
        },

        removeItem(key) {
            this.items = this.items.filter(item => item.key !== key);
            this.render();
        },

        clearCart() {
            if (this.items.length === 0) return;
            if (confirm('Clear all items from the current cart?')) {
                this.items = [];
                this.discount.value = 0;
                document.getElementById('discountValueInput').value = 0;
                this.render();
            }
        },

        getSubtotal() {
            return this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        },

        getDiscountAmount() {
            const subtotal = this.getSubtotal();
            if (this.discount.type === 'percentage') {
                return (subtotal * Math.min(100, this.discount.value)) / 100;
            }
            return Math.min(subtotal, this.discount.value);
        },

        getGrandTotal() {
            const subtotal = this.getSubtotal();
            const discount = this.getDiscountAmount();
            return Math.max(0, subtotal - discount);
        },

        render() {
            const listEl = document.getElementById('cartItemsList');
            const subtotal = this.getSubtotal();
            const grandTotal = this.getGrandTotal();
            const totalCount = this.items.reduce((sum, item) => sum + item.quantity, 0);

            // Update UI Counters
            document.getElementById('cartItemsCount').innerText = totalCount;
            document.getElementById('cartSubtotalText').innerText = 'RM ' + subtotal.toFixed(2);
            document.getElementById('cartGrandTotalText').innerText = 'RM ' + grandTotal.toFixed(2);
            document.getElementById('btnPayAmount').innerText = 'RM ' + grandTotal.toFixed(2);

            const hasItems = this.items.length > 0;
            document.getElementById('btnOpenPaymentModal').disabled = !hasItems;
            document.getElementById('btnHoldOrder').disabled = !hasItems;
            document.getElementById('btnClearCart').disabled = !hasItems;

            if (!hasItems) {
                listEl.innerHTML = `
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-cart3 fs-2 text-secondary opacity-40 d-block mb-1"></i>
                        <div class="fw-semibold small">Cart is empty</div>
                        <div class="text-muted" style="font-size: 0.72rem;">Click items on the left to add</div>
                    </div>`;
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            this.items.forEach(item => {
                const lineTotal = item.price * item.quantity;
                const variantBadge = item.variant_name 
                    ? `<span class="badge bg-primary-subtle text-primary py-0 px-1 me-1" style="font-size: 0.65rem;">${item.variant_name}</span>`
                    : '';

                html += `
                    <div class="list-group-item p-2 cart-row border-bottom">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div class="text-truncate pe-1">
                                <div class="fw-semibold text-dark text-truncate" style="font-size: 0.78rem;" title="${item.name}">${item.name}</div>
                                <div class="text-muted d-flex align-items-center" style="font-size: 0.68rem;">
                                    ${variantBadge}
                                    <span>${item.sku} &bull; RM ${item.price.toFixed(2)}</span>
                                </div>
                            </div>
                            <span class="fw-bold text-dark" style="font-size: 0.8rem;">RM ${lineTotal.toFixed(2)}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <div class="input-group input-group-sm" style="width: 90px;">
                                <button class="btn btn-outline-secondary py-0 px-1" type="button" onclick="posCart.updateQuantity('${item.key}', ${item.quantity - 1})">-</button>
                                <input type="number" class="form-control text-center py-0 px-1" style="font-size: 0.75rem;" value="${item.quantity}" min="1" max="${item.stock}" 
                                       onchange="posCart.updateQuantity('${item.key}', this.value)">
                                <button class="btn btn-outline-secondary py-0 px-1" type="button" onclick="posCart.updateQuantity('${item.key}', ${item.quantity + 1})">+</button>
                            </div>
                            <button class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="posCart.removeItem('${item.key}')" title="Remove">
                                <i class="bi bi-trash3" style="font-size: 0.75rem;"></i>
                            </button>
                        </div>
                    </div>`;
            });
            html += '</div>';
            listEl.innerHTML = html;
        },

        bindEvents() {
            // Discount inputs
            document.getElementById('discountTypeSelect').addEventListener('change', (e) => {
                this.discount.type = e.target.value;
                this.render();
            });

            document.getElementById('discountValueInput').addEventListener('input', (e) => {
                this.discount.value = parseFloat(e.target.value) || 0;
                this.render();
            });

            document.getElementById('btnClearCart').addEventListener('click', () => this.clearCart());

            // Product Live Search & Filter
            const searchInput = document.getElementById('productSearchInput');
            const categorySelect = document.getElementById('categoryFilterSelect');
            const clearSearchBtn = document.getElementById('btnClearSearch');

            let searchTimeout = null;
            const triggerSearch = () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => this.filterProducts(), 200);
            };

            searchInput.addEventListener('input', triggerSearch);
            categorySelect.addEventListener('change', () => {
                const cat = categorySelect.value;
                document.querySelectorAll('.cat-pill-btn').forEach(btn => {
                    const match = btn.getAttribute('data-category') === cat;
                    btn.classList.toggle('active', match);
                    btn.classList.toggle('btn-primary', match);
                    btn.classList.toggle('btn-light', !match);
                });
                triggerSearch();
            });

            clearSearchBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.focus();
                this.filterProducts();
            });

            // Category Pills click
            document.querySelectorAll('.cat-pill-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const cat = btn.getAttribute('data-category');
                    document.querySelectorAll('.cat-pill-btn').forEach(b => {
                        b.classList.remove('active', 'btn-primary');
                        b.classList.add('btn-light');
                    });
                    btn.classList.add('active', 'btn-primary');
                    btn.classList.remove('btn-light');
                    categorySelect.value = cat;
                    this.filterProducts();
                });
            });

            // Customer Search
            const customerSearchInput = document.getElementById('customerSearchInput');
            let customerTimeout = null;
            customerSearchInput.addEventListener('input', () => {
                clearTimeout(customerTimeout);
                customerTimeout = setTimeout(() => this.searchCustomers(customerSearchInput.value), 250);
            });

            document.getElementById('btnResetToWalkin').addEventListener('click', () => {
                this.setCustomer({
                    id: '{{ $walkinCustomer->id ?? "" }}',
                    name: 'Walk-in Customer',
                    phone: 'N/A',
                    email: 'walkin@pos.local'
                });
            });

            // Hold Order
            document.getElementById('btnHoldOrder').addEventListener('click', () => this.holdCurrentOrder());

            // Payment Modal & Toggle (Cash vs QR)
            document.getElementById('btnOpenPaymentModal').addEventListener('click', () => this.openPaymentModal());

            document.getElementById('btnMethodCash').addEventListener('click', () => this.selectPaymentMethod('cash'));
            document.getElementById('btnMethodQr').addEventListener('click', () => this.selectPaymentMethod('qr'));

            document.getElementById('tenderedAmountInput').addEventListener('input', () => this.calculateCashChange());

            document.querySelectorAll('.quick-cash-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const val = btn.getAttribute('data-val');
                    const grandTotal = this.getGrandTotal();
                    let tendered = grandTotal;
                    if (val !== 'exact') {
                        tendered = parseFloat(val);
                    }
                    document.getElementById('tenderedAmountInput').value = tendered.toFixed(2);
                    this.calculateCashChange();
                });
            });

            // Submit Payment Checkout
            document.getElementById('btnSubmitPayment').addEventListener('click', () => this.submitCheckout());

            // Keyboard Shortcuts
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    searchInput.focus();
                } else if (e.key === 'F4') {
                    e.preventDefault();
                    if (this.items.length > 0) {
                        this.openPaymentModal();
                    }
                } else if (e.key === 'F8') {
                    e.preventDefault();
                    if (this.items.length > 0) {
                        this.holdCurrentOrder();
                    }
                }
            });
        },

        selectPaymentMethod(method) {
            this.activePaymentMethod = method;
            document.getElementById('btnMethodCash').classList.toggle('active', method === 'cash');
            document.getElementById('btnMethodQr').classList.toggle('active', method === 'qr');

            document.getElementById('cashPaymentSection').classList.toggle('d-none', method !== 'cash');
            document.getElementById('qrPaymentSection').classList.toggle('d-none', method !== 'qr');
        },

        filterProducts() {
            const query = document.getElementById('productSearchInput').value.trim();
            const categoryId = document.getElementById('categoryFilterSelect').value;

            fetch(`{{ route('admin.pos.search-products') }}?search=${encodeURIComponent(query)}&category_id=${encodeURIComponent(categoryId)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;
                const grid = document.getElementById('productsGrid');
                if (data.products.length === 0) {
                    grid.innerHTML = `
                        <div class="col-12 text-center py-5 text-muted small">
                            <i class="bi bi-search fs-3 d-block mb-1 text-secondary opacity-50"></i>
                            No products found matching "${query}".
                        </div>`;
                    return;
                }

                let html = '';
                data.products.forEach(p => {
                    const isOutOfStock = p.stock <= 0;
                    const stockBadge = isOutOfStock
                        ? `<span class="badge bg-danger-subtle text-danger" style="font-size: 0.62rem; padding: 2px 5px;">Out</span>`
                        : (p.is_low_stock 
                            ? `<span class="badge bg-warning-subtle text-warning-emphasis" style="font-size: 0.62rem; padding: 2px 5px;">Stock: ${p.stock}</span>`
                            : `<span class="badge bg-success-subtle text-success" style="font-size: 0.62rem; padding: 2px 5px;">Stock: ${p.stock}</span>`);

                    const variantBadge = p.has_variations
                        ? `<span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.58rem; padding: 1px 4px;"><i class="bi bi-layers me-1"></i>${p.variations.length} Variants</span>`
                        : '';

                    const imgTag = p.image_url 
                        ? `<img src="${p.image_url}" alt="${p.name}" class="img-fluid" style="max-height: 75px; object-fit: contain;" onerror="this.onerror=null; this.parentElement.innerHTML='<i class=\\'bi bi-box text-muted opacity-40 fs-2\\'></i>';">`
                        : `<i class="bi bi-box text-muted opacity-40 fs-2"></i>`;

                    const jsonVariations = JSON.stringify(p.variations || []).replace(/'/g, "&apos;");

                    html += `
                        <div class="col-6 col-sm-4 col-md-3 col-xl-3 product-item-col" 
                             data-id="${p.id}" data-name="${p.name}" data-sku="${p.sku}" data-price="${p.price}" data-stock="${p.stock}"
                             data-has-variations="${p.has_variations ? '1' : '0'}"
                             data-variations='${jsonVariations}'>
                            <div class="card h-100 product-card border rounded-3 p-2 text-start position-relative ${isOutOfStock ? 'out-of-stock opacity-50' : 'cursor-pointer'}"
                                 onclick="posCart.onProductCardClick(this.parentElement)">
                                <div class="position-absolute top-0 end-0 m-1 d-flex flex-column align-items-end gap-1">
                                    ${stockBadge}
                                    ${variantBadge}
                                </div>
                                <div class="product-thumb rounded-2 mb-2 bg-light d-flex align-items-center justify-content-center overflow-hidden" style="height: 80px;">
                                    ${imgTag}
                                </div>
                                <div class="d-flex flex-column justify-content-between flex-grow-1">
                                    <div>
                                        <div class="text-muted text-uppercase" style="font-size: 0.62rem; letter-spacing: 0.03em;">${p.sku}</div>
                                        <div class="fw-semibold text-dark text-truncate" style="font-size: 0.8rem;" title="${p.name}">${p.name}</div>
                                    </div>
                                    <div class="mt-2 pt-1 border-top d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary" style="font-size: 0.88rem;">${p.formatted_price}</span>
                                        <span class="badge bg-primary-subtle text-primary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 20px; height: 20px;">
                                            <i class="bi ${p.has_variations ? 'bi-list' : 'bi-plus'}" style="font-size: 0.75rem;"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>`;
                });
                grid.innerHTML = html;
            });
        },

        searchCustomers(query) {
            if (query.length < 2) return;
            fetch(`{{ route('admin.pos.search-customers') }}?query=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' }
            })
            .then(res => res.json())
            .then(data => {
                const resultsEl = document.getElementById('customerSearchResults');
                if (data.customers.length === 0) {
                    resultsEl.innerHTML = '<div class="p-2 text-center text-muted small">No customers found.</div>';
                    return;
                }
                let html = '';
                data.customers.forEach(c => {
                    html += `
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2"
                           onclick="posCart.setCustomer({id: ${c.id}, name: '${c.name.replace(/'/g, "\\'")}', phone: '${c.phone || 'N/A'}', email: '${c.email}'})">
                            <div>
                                <div class="fw-bold text-dark" style="font-size: 0.78rem;">${c.name}</div>
                                <div class="text-muted" style="font-size: 0.68rem;">${c.email} &bull; ${c.phone || 'N/A'}</div>
                            </div>
                            <span class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.7rem;">Select</span>
                        </a>`;
                });
                resultsEl.innerHTML = html;
            });
        },

        setCustomer(customer) {
            this.customer = customer;
            document.getElementById('selectedCustomerId').value = customer.id;
            document.getElementById('selectedCustomerName').innerText = customer.name;
            document.getElementById('selectedCustomerInfo').innerText = `${customer.email} &bull; ${customer.phone || 'N/A'}`;
            const modalEl = document.getElementById('customerModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        },

        openPaymentModal() {
            const grandTotal = this.getGrandTotal();
            document.getElementById('modalTotalDueText').innerText = 'RM ' + grandTotal.toFixed(2);
            document.getElementById('qrAmountDisplay').innerText = 'RM ' + grandTotal.toFixed(2);
            document.getElementById('tenderedAmountInput').value = grandTotal.toFixed(2);
            this.selectPaymentMethod('cash');
            this.calculateCashChange();

            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            paymentModal.show();
        },

        calculateCashChange() {
            const grandTotal = this.getGrandTotal();
            const tendered = parseFloat(document.getElementById('tenderedAmountInput').value) || 0;
            const change = tendered - grandTotal;

            const changeEl = document.getElementById('changeDueText');
            const statusEl = document.getElementById('changeDueStatus');

            if (tendered < grandTotal) {
                const deficit = grandTotal - tendered;
                changeEl.innerText = 'RM 0.00';
                changeEl.className = 'fw-bold text-danger my-1';
                statusEl.innerText = `Insufficient by RM ${deficit.toFixed(2)}`;
            } else {
                changeEl.innerText = 'RM ' + change.toFixed(2);
                changeEl.className = 'fw-bold text-success my-1';
                statusEl.innerText = change === 0 ? 'Exact Amount' : 'Change to Return';
            }
        },

        submitCheckout() {
            const grandTotal = this.getGrandTotal();
            if (this.items.length === 0) return;

            const paymentMethod = this.activePaymentMethod;
            const tenderedAmount = parseFloat(document.getElementById('tenderedAmountInput').value) || grandTotal;
            
            if (paymentMethod === 'cash' && tenderedAmount < grandTotal) {
                alert('Tendered cash amount is less than the grand total.');
                return;
            }

            const payload = {
                customer_id: this.customer.id || null,
                customer_name: this.customer.name,
                customer_phone: this.customer.phone,
                items: this.items.map(item => ({
                    product_id: item.id,
                    variation_id: item.variation_id || null,
                    quantity: item.quantity
                })),
                payment_method: paymentMethod,
                tendered_amount: tenderedAmount,
                discount_type: this.discount.type,
                discount_value: this.discount.value
            };

            const submitBtn = document.getElementById('btnSubmitPayment');
            const spinner = document.getElementById('paymentSpinner');
            const icon = document.getElementById('paymentCheckIcon');
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            icon.classList.add('d-none');

            fetch("{{ route('admin.pos.checkout') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify(payload)
            })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.message || 'Payment failed.');
                }
                return data;
            })
            .then(data => {
                bootstrap.Modal.getInstance(document.getElementById('paymentModal')).hide();

                document.getElementById('successOrderNumber').innerText = data.order.order_number;
                document.getElementById('successTotalPaid').innerText = 'RM ' + parseFloat(data.order.total_amount).toFixed(2);
                document.getElementById('successPaymentMethod').innerText = data.order.payment_method.toUpperCase();
                document.getElementById('successChangeDue').innerText = 'RM ' + parseFloat(data.order.change_due).toFixed(2);

                document.getElementById('btnPrintReceipt').href = data.receipt_url;
                document.getElementById('btnDownloadPdfReceipt').href = data.pdf_url;

                // Reset Cart
                this.items = [];
                this.discount.value = 0;
                document.getElementById('discountValueInput').value = 0;
                this.render();

                const receiptModal = new bootstrap.Modal(document.getElementById('receiptModal'));
                receiptModal.show();
            })
            .catch(error => {
                alert('Error: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                icon.classList.remove('d-none');
            });
        },

        // =====================================================================
        // Held Orders
        // =====================================================================
        getHeldOrders() {
            try {
                return JSON.parse(localStorage.getItem('pos_held_orders') || '[]');
            } catch (e) {
                return [];
            }
        },

        saveHeldOrders(orders) {
            localStorage.setItem('pos_held_orders', JSON.stringify(orders));
            this.updateHeldCountBadge();
        },

        updateHeldCountBadge() {
            const held = this.getHeldOrders();
            const badge = document.getElementById('heldOrdersCountBadge');
            badge.innerText = held.length;
            badge.style.display = held.length > 0 ? 'inline-block' : 'none';
        },

        holdCurrentOrder() {
            if (this.items.length === 0) return;

            const held = this.getHeldOrders();
            const newHeldOrder = {
                id: 'HELD-' + Date.now(),
                customer: this.customer,
                items: this.items,
                discount: this.discount,
                total: this.getGrandTotal(),
                heldAt: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
            };

            held.push(newHeldOrder);
            this.saveHeldOrders(held);

            this.items = [];
            this.discount.value = 0;
            document.getElementById('discountValueInput').value = 0;
            this.render();

            alert('Cart suspended and held successfully.');
        },

        renderHeldOrdersTable() {
            const held = this.getHeldOrders();
            const tbody = document.getElementById('heldOrdersTableBody');

            if (held.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No held orders.</td></tr>';
                return;
            }

            let html = '';
            held.forEach((order, index) => {
                const totalItems = order.items.reduce((sum, item) => sum + item.quantity, 0);
                html += `
                    <tr>
                        <td class="fw-semibold">${index + 1}</td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size: 0.78rem;">${order.customer.name}</div>
                            <div class="text-muted" style="font-size: 0.68rem;">${order.customer.phone}</div>
                        </td>
                        <td>${totalItems} items</td>
                        <td class="fw-bold text-primary">RM ${order.total.toFixed(2)}</td>
                        <td class="text-muted" style="font-size: 0.72rem;">${order.heldAt}</td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-primary py-0 px-2 me-1" onclick="posCart.restoreHeldOrder('${order.id}')" style="font-size: 0.72rem;">
                                Resume
                            </button>
                            <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick="posCart.deleteHeldOrder('${order.id}')" style="font-size: 0.72rem;">
                                Delete
                            </button>
                        </td>
                    </tr>`;
            });
            tbody.innerHTML = html;
        },

        restoreHeldOrder(orderId) {
            let held = this.getHeldOrders();
            const order = held.find(o => o.id === orderId);
            if (!order) return;

            if (this.items.length > 0) {
                if (!confirm('Replace active cart with this held cart?')) return;
            }

            this.items = order.items;
            this.customer = order.customer;
            this.discount = order.discount || { type: 'fixed', value: 0 };
            document.getElementById('discountValueInput').value = this.discount.value;
            document.getElementById('discountTypeSelect').value = this.discount.type;

            document.getElementById('selectedCustomerId').value = this.customer.id;
            document.getElementById('selectedCustomerName').innerText = this.customer.name;
            document.getElementById('selectedCustomerInfo').innerText = `${this.customer.email} &bull; ${this.customer.phone || 'N/A'}`;

            held = held.filter(o => o.id !== orderId);
            this.saveHeldOrders(held);

            bootstrap.Modal.getInstance(document.getElementById('heldOrdersModal')).hide();
            this.render();
        },

        deleteHeldOrder(orderId) {
            if (confirm('Delete this held cart?')) {
                let held = this.getHeldOrders();
                held = held.filter(o => o.id !== orderId);
                this.saveHeldOrders(held);
                this.renderHeldOrdersTable();
            }
        }
    };

    document.addEventListener('DOMContentLoaded', () => {
        posCart.init();
        document.getElementById('btnViewHeldOrders').addEventListener('click', () => {
            posCart.renderHeldOrdersTable();
        });
    });
</script>
@endpush
