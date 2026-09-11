@extends('layouts.admin')

@section('content')
<div class="container-fluid py-3">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('admin.einvoices.index') }}" class="btn btn-outline-secondary btn-sm rounded-circle" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <h4 class="fw-bold mb-0">LHDN e-Invoicing Tax & Gateway Settings</h4>
            </div>
            <p class="text-muted small mb-0 ps-4 ms-2">Configure merchant tax registration details, MyInvois environment modes, and auto-submission rules.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill fs-5"></i>
            <div>{{ session('status') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.einvoices.settings.update') }}">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <!-- Left Column: Company Tax Profile -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-building text-primary"></i>
                        <span>Supplier (Merchant) Tax Profile</span>
                    </h5>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label for="company_name" class="form-label small fw-semibold">Company / Business Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="company_name" name="company_name" value="{{ old('company_name', $settings->company_name) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="company_tin" class="form-label small fw-semibold">Supplier TIN (Tax Identification No) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="company_tin" name="company_tin" value="{{ old('company_tin', $settings->company_tin) }}" placeholder="e.g. C25890123040" required>
                            <div class="form-text" style="font-size: 0.72rem;">Assigned by LHDN (e.g. C, IG, OG prefix).</div>
                        </div>

                        <div class="col-md-6">
                            <label for="company_reg_no" class="form-label small fw-semibold">Business Reg No (SSM / BRN) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="company_reg_no" name="company_reg_no" value="{{ old('company_reg_no', $settings->company_reg_no) }}" placeholder="e.g. 202601009988" required>
                        </div>

                        <div class="col-md-6">
                            <label for="msic_code" class="form-label small fw-semibold">MSIC Code (5 Digits) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="msic_code" name="msic_code" value="{{ old('msic_code', $settings->msic_code) }}" placeholder="e.g. 47630" required>
                            <div class="form-text" style="font-size: 0.72rem;">Malaysia Standard Industrial Classification.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="company_sst_no" class="form-label small fw-semibold">SST Registration No (If Applicable)</label>
                            <input type="text" class="form-control form-control-sm" id="company_sst_no" name="company_sst_no" value="{{ old('company_sst_no', $settings->company_sst_no) }}" placeholder="e.g. W10-2401-32000001">
                        </div>

                        <div class="col-md-12">
                            <label for="msic_description" class="form-label small fw-semibold">MSIC Description <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="msic_description" name="msic_description" value="{{ old('msic_description', $settings->msic_description) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_email" class="form-label small fw-semibold">Tax Contact Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" id="contact_email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" required>
                        </div>

                        <div class="col-md-6">
                            <label for="contact_phone" class="form-label small fw-semibold">Contact Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" required>
                        </div>

                        <div class="col-md-12">
                            <label for="address_line1" class="form-label small fw-semibold">Address Line 1 <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="address_line1" name="address_line1" value="{{ old('address_line1', $settings->address_line1) }}" required>
                        </div>

                        <div class="col-md-12">
                            <label for="address_line2" class="form-label small fw-semibold">Address Line 2 (Optional)</label>
                            <input type="text" class="form-control form-control-sm" id="address_line2" name="address_line2" value="{{ old('address_line2', $settings->address_line2) }}">
                        </div>

                        <div class="col-md-4">
                            <label for="postal_code" class="form-label small fw-semibold">Postal Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="postal_code" name="postal_code" value="{{ old('postal_code', $settings->postal_code) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label for="city" class="form-label small fw-semibold">City <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="city" name="city" value="{{ old('city', $settings->city) }}" required>
                        </div>

                        <div class="col-md-4">
                            <label for="state" class="form-label small fw-semibold">State <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="state" name="state" value="{{ old('state', $settings->state) }}" required>
                        </div>
                        <input type="hidden" name="country" value="MYS">
                    </div>
                </div>
            </div>

            <!-- Right Column: LHDN Mode & Gateway Settings -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm rounded-3 p-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-shield-lock-fill text-primary"></i>
                        <span>Integration Mode & Environment</span>
                    </h5>

                    <!-- Simulation Mode Selected -->
                    <div class="form-check p-3 rounded-3 mb-3 border {{ old('mode', $settings->mode) === 'simulation' ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-light' }}">
                        <input class="form-check-input ms-0 me-2" type="radio" name="mode" id="mode_simulation" value="simulation" @checked(old('mode', $settings->mode) === 'simulation')>
                        <label class="form-check-label fw-bold text-dark" for="mode_simulation" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-2">
                                <span>Prototype / Simulation Mode</span>
                                <span class="badge bg-primary">Active Demo</span>
                            </div>
                            <div class="text-muted small fw-normal mt-1">
                                Generates complete, compliant UBL 2.1 JSON documents and functional QR validation codes without requiring live LHDN API registration credentials.
                            </div>
                        </label>
                    </div>

                    <!-- Sandbox Mode -->
                    <div class="form-check p-3 rounded-3 mb-3 border {{ old('mode', $settings->mode) === 'sandbox' ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-light' }}">
                        <input class="form-check-input ms-0 me-2" type="radio" name="mode" id="mode_sandbox" value="sandbox" @checked(old('mode', $settings->mode) === 'sandbox')>
                        <label class="form-check-label fw-bold text-dark" for="mode_sandbox" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-2">
                                <span>LHDN Sandbox API</span>
                                <span class="badge bg-warning text-dark">Pre-Production</span>
                            </div>
                            <div class="text-muted small fw-normal mt-1">
                                Direct integration with official MyInvois Sandbox portal (<code>preprod-api.myinvois.hasil.gov.my</code>).
                            </div>
                        </label>
                    </div>

                    <!-- Production Mode -->
                    <div class="form-check p-3 rounded-3 mb-4 border {{ old('mode', $settings->mode) === 'production' ? 'border-primary bg-primary-subtle bg-opacity-10' : 'bg-light' }}">
                        <input class="form-check-input ms-0 me-2" type="radio" name="mode" id="mode_production" value="production" @checked(old('mode', $settings->mode) === 'production')>
                        <label class="form-check-label fw-bold text-dark" for="mode_production" style="cursor: pointer;">
                            <div class="d-flex align-items-center gap-2">
                                <span>LHDN Production API</span>
                                <span class="badge bg-danger">Live Official</span>
                            </div>
                            <div class="text-muted small fw-normal mt-1">
                                Real-time transmission to Inland Revenue Board Production servers.
                            </div>
                        </label>
                    </div>

                    <!-- LHDN API Credentials (Optional / Future) -->
                    <div class="pt-3 border-top mb-4">
                        <h6 class="fw-bold text-dark small mb-3">LHDN MyInvois API Credentials (When Registered)</h6>

                        <div class="mb-3">
                            <label for="lhdn_client_id" class="form-label small fw-semibold">Client ID</label>
                            <input type="text" class="form-control form-control-sm" id="lhdn_client_id" name="lhdn_client_id" value="{{ old('lhdn_client_id', $settings->lhdn_client_id) }}" placeholder="Optional in simulation mode">
                        </div>

                        <div class="mb-3">
                            <label for="lhdn_client_secret" class="form-label small fw-semibold">Client Secret</label>
                            <input type="password" class="form-control form-control-sm" id="lhdn_client_secret" name="lhdn_client_secret" value="{{ old('lhdn_client_secret', $settings->lhdn_client_secret) }}" placeholder="••••••••••••••••">
                        </div>
                    </div>

                    <!-- Automatic Generation Rules -->
                    <div class="pt-3 border-top mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="auto_generate_on_payment" name="auto_generate_on_payment" value="1" @checked(old('auto_generate_on_payment', $settings->auto_generate_on_payment))>
                            <label class="form-check-label fw-bold text-dark small" for="auto_generate_on_payment">
                                Auto-Generate e-Invoice on Payment Completion
                            </label>
                        </div>
                        <div class="text-muted small mt-1 ps-4 ms-2">Automatically generates and validates e-Invoice immediately when a POS sale is tendered or ToyyibPay / online order is paid.</div>
                    </div>

                    <input type="hidden" name="default_tax_rate" value="{{ $settings->default_tax_rate }}">
                    <input type="hidden" name="default_tax_type_code" value="{{ $settings->default_tax_type_code }}">

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-sm py-2 fw-semibold">
                            <i class="bi bi-save me-1"></i>Save Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
