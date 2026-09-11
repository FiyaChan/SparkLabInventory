@extends('layouts.shop')

@section('content')
<div class="mb-4">
    <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-box-arrow-up-right me-2" style="color: #7E22CE;"></i>Order Checkout & Delivery</h4>
    <p class="text-muted small mb-0">Enter your delivery address and select a payment method for your science kits.</p>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="sci-card p-4">
            <h5 class="sci-heading mb-4" style="color: #1E1B4B;"><i class="bi bi-geo-alt me-2 text-purple" style="color: #7E22CE;"></i>Delivery Details</h5>

            @if ($errors->any())
                <div class="sci-alert sci-alert-danger mb-4">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('checkout.store') }}">
                @csrf

                <div class="mb-3">
                    <label for="shipping_name" class="sci-form-label">Recipient Name <span class="text-danger">*</span></label>
                    <input type="text" class="sci-form-control form-control" id="shipping_name" name="shipping_name" 
                           value="{{ old('shipping_name', auth()->user()->name) }}" required>
                </div>

                <div class="mb-3">
                    <label for="shipping_phone" class="sci-form-label">Contact Phone Number <span class="text-danger">*</span></label>
                    <input type="text" class="sci-form-control form-control" id="shipping_phone" name="shipping_phone" 
                           value="{{ old('shipping_phone') }}" placeholder="e.g. +60123456789" required>
                </div>

                <div class="mb-4">
                    <label for="shipping_address" class="sci-form-label">Home / School Delivery Address <span class="text-danger">*</span></label>
                    <textarea class="sci-form-control form-control" id="shipping_address" name="shipping_address" rows="3" placeholder="House/Apartment Number, Street Address, Postal Code, City..." required>{{ old('shipping_address') }}</textarea>
                </div>

                <!-- LHDN MyInvois Tax Details Card -->
                <div class="mb-4 p-3 rounded-3" style="background: #F0FDF4; border: 1.5px solid #BBF7D0;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="sci-form-label mb-0 fw-bold d-flex align-items-center gap-2" style="color: #166534; cursor: pointer;" for="require_einvoice">
                            <input class="form-check-input mt-0" type="checkbox" name="require_einvoice" id="require_einvoice" value="1" 
                                   @checked(old('require_einvoice', auth()->user()->tin ? 1 : 0)) 
                                   onchange="document.getElementById('einvoice_fields').style.display = this.checked ? 'block' : 'none';">
                            <span><i class="bi bi-file-earmark-check-fill text-success me-1"></i>Perlukan e-Invois LHDN (MyInvois)?</span>
                        </label>
                        <span class="badge bg-success-subtle text-success border border-success-subtle small">LHDN Malaysia</span>
                    </div>
                    <p class="text-muted small mb-0">Tandakan pilihan ini jika anda memerlukan e-Invois rasmi untuk pelepasan cukai individu (LHDN) atau tuntutan perniagaan/syarikat.</p>

                    <div id="einvoice_fields" class="mt-3 pt-3 border-top" style="border-color: #DCFCE7 !important; display: {{ (old('require_einvoice') || auth()->user()->tin) ? 'block' : 'none' }};">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="buyer_tin" class="form-label small fw-semibold text-dark">No. TIN Cukai (Tax Identification No)</label>
                                <input type="text" class="form-control form-control-sm" id="buyer_tin" name="buyer_tin" 
                                       value="{{ old('buyer_tin', auth()->user()->tin) }}" placeholder="cth. IG12345678090 atau C12345678090">
                                <div class="form-text" style="font-size: 0.72rem;">Jika tiada, sistem menggunakan TIN Am LHDN secara automatik.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="buyer_id_type" class="form-label small fw-semibold text-dark">Jenis Pengenalan</label>
                                <select class="form-select form-select-sm" id="buyer_id_type" name="buyer_id_type">
                                    <option value="NRIC" @selected(old('buyer_id_type', auth()->user()->id_type) === 'NRIC')>MyKad / Kad Pengenalan (NRIC)</option>
                                    <option value="BRN" @selected(old('buyer_id_type', auth()->user()->id_type) === 'BRN')>No. Pendaftaran Syarikat (SSM/BRN)</option>
                                    <option value="PASSPORT" @selected(old('buyer_id_type', auth()->user()->id_type) === 'PASSPORT')>Pasport Antarabangsa</option>
                                    <option value="ARMY" @selected(old('buyer_id_type', auth()->user()->id_type) === 'ARMY')>Angkatan Tentera / Polis</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="buyer_id_number" class="form-label small fw-semibold text-dark">Nombor Pengenalan (IC / Passport / BRN)</label>
                                <input type="text" class="form-control form-control-sm" id="buyer_id_number" name="buyer_id_number" 
                                       value="{{ old('buyer_id_number', auth()->user()->id_number) }}" placeholder="cth. 920512-10-5544 atau 202601009988">
                            </div>
                            <div class="col-md-6">
                                <label for="buyer_sst_no" class="form-label small fw-semibold text-dark">No. Cukai SST (Jika Berkenaan)</label>
                                <input type="text" class="form-control form-control-sm" id="buyer_sst_no" name="buyer_sst_no" 
                                       value="{{ old('buyer_sst_no', auth()->user()->sst_number) }}" placeholder="cth. W10-1808-31000000">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4 p-3 rounded-3" style="background: #F8F5FF; border: 2px solid #E9D5FF;">
                    <label class="sci-form-label d-block mb-3" style="color: #1E1B4B;"><i class="bi bi-credit-card me-2 text-purple" style="color: #7E22CE;"></i>Payment Method</label>
                    
                    <div class="form-check p-3 rounded-3 mb-2 bg-white border" style="border-color: #E9D5FF !important;">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_toyyibpay" value="toyyibpay" @checked(old('payment_method') === 'toyyibpay' || !old('payment_method'))>
                        <label class="form-check-label fw-bold text-dark w-100" for="pay_toyyibpay" style="cursor: pointer;">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-1">
                                <span>ToyyibPay (FPX Online Banking & Cards)</span>
                                <span class="badge" style="background-color: #7E22CE; font-size: 0.75rem;">FPX Malaysian Banks</span>
                            </div>
                            <div class="text-muted small fw-normal mt-1">
                                Pay securely with Maybank2u, CIMB Clicks, Bank Islam, RHB, Public Bank, Hong Leong, Touch 'n Go & more via ToyyibPay.
                            </div>
                        </label>
                    </div>

                    <div class="form-check p-3 rounded-3 mb-2 bg-white border" style="border-color: #E9D5FF !important;">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_cod" value="cod" @checked(old('payment_method') === 'cod')>
                        <label class="form-check-label fw-bold text-dark w-100" for="pay_cod" style="cursor: pointer;">
                            <div>Cash on Delivery (COD)</div>
                            <div class="text-muted small fw-normal mt-1">Pay with cash directly upon package arrival at your doorstep.</div>
                        </label>
                    </div>

                    <div class="form-check p-3 rounded-3 bg-white border" style="border-color: #E9D5FF !important;">
                        <input class="form-check-input ms-0 me-2" type="radio" name="payment_method" id="pay_simulation" value="online_simulation" @checked(old('payment_method') === 'online_simulation')>
                        <label class="form-check-label fw-bold text-dark w-100" for="pay_simulation" style="cursor: pointer;">
                            <div>Online Banking Simulation (Sandbox Testing)</div>
                            <div class="text-muted small fw-normal mt-1">Instant offline checkout simulation with immediate confirmation for testing.</div>
                        </label>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-sci-primary btn-lg py-3 fs-6">
                        <i class="bi bi-stars me-1"></i>Confirm Order & Place Delivery
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="sci-glass-panel p-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-cart3 me-2 text-purple" style="color: #7E22CE;"></i>Order Items</h5>
            
            <div class="d-flex flex-column gap-3 mb-4">
                @foreach ($cart->items as $item)
                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom" style="border-color: #EDE9FE !important;">
                        <div>
                            <div class="fw-bold text-dark small">{{ $item->product->name }}</div>
                            <small class="text-muted">RM {{ number_format($item->product->price, 2) }} &times; {{ $item->quantity }} items</small>
                        </div>
                        <span class="sci-price small">RM {{ number_format($item->quantity * $item->product->price, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-2 border-top d-flex justify-content-between align-items-baseline mb-4" style="border-color: #E9D5FF !important;">
                <span class="sci-heading fw-bold fs-5" style="color: #1E1B4B;">Total Due:</span>
                <span class="sci-price fs-2">RM {{ number_format($cart->total, 2) }}</span>
            </div>

            <div class="d-grid">
                <a href="{{ route('cart.index') }}" class="btn btn-sci-outline btn-sm">
                    <i class="bi bi-pencil me-1"></i>Edit Cart
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
