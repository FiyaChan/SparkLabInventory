@extends('layouts.shop')

@section('content')
<div class="mb-4">
    <h4 class="sci-heading fw-bold mb-1"><i class="bi bi-person-badge me-2" style="color: #7E22CE;"></i>Parent & Young Scientist Profile</h4>
    <p class="text-muted small mb-0">Update contact details, delivery address, and manage account security.</p>
</div>

<div class="row g-4">
    <!-- Edit Profile Card -->
    <div class="col-lg-6">
        <div class="sci-card p-4 h-100">
            <h5 class="sci-heading mb-4" style="color: #1E1B4B;"><i class="bi bi-person-fill me-2" style="color: #7E22CE;"></i>Account & Delivery Details</h5>

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="name" class="sci-form-label">Parent / Scientist Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="sci-form-control form-control @error('name') is-invalid @enderror" id="name" name="name" 
                           value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="sci-form-label">Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="sci-form-control form-control @error('email') is-invalid @enderror" id="email" name="email" 
                           value="{{ old('email', $user->email) }}" required>
                    <div class="text-muted small mt-1">Note: Modifying your email requires verification.</div>
                    @error('email')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="phone" class="sci-form-label">Contact Phone Number</label>
                    <input type="text" class="sci-form-control form-control @error('phone') is-invalid @enderror" id="phone" name="phone" 
                           value="{{ old('phone', $user->phone) }}" placeholder="+60123456789">
                    @error('phone')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-4">
                    <label for="address" class="sci-form-label">Home / School Delivery Address</label>
                    <textarea class="sci-form-control form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $user->address) }}</textarea>
                    @error('address')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="btn btn-sci-primary">
                    <i class="bi bi-check2-circle me-1"></i>Save Profile Information
                </button>
            </form>
        </div>
    </div>

    <!-- Password & 2FA Column -->
    <div class="col-lg-6">
        <!-- Change Password Card -->
        <div class="sci-card p-4 mb-4">
            <h5 class="sci-heading mb-4" style="color: #1E1B4B;"><i class="bi bi-key me-2" style="color: #7E22CE;"></i>Change Account Password</h5>

            <form method="POST" action="{{ route('profile.password') }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label for="current_password" class="sci-form-label">Current Password <span class="text-danger">*</span></label>
                    <input type="password" class="sci-form-control form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required>
                    @error('current_password')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="sci-form-label">New Password <span class="text-danger">*</span></label>
                    <input type="password" class="sci-form-control form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                    <div class="text-muted small mt-1">Min 8 characters with upper/lowercase, number & symbol.</div>
                    @error('password')<div class="invalid-feedback text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="sci-form-label">Confirm New Password <span class="text-danger">*</span></label>
                    <input type="password" class="sci-form-control form-control" id="password_confirmation" name="password_confirmation" required>
                </div>

                <button type="submit" class="btn btn-sci-primary">
                    <i class="bi bi-shield-lock me-1"></i>Update Password
                </button>
            </form>
        </div>

        <!-- MFA Settings Card -->
        <div class="sci-glass-panel p-4">
            <h5 class="sci-heading mb-3" style="color: #1E1B4B;"><i class="bi bi-shield-check me-2" style="color: #7E22CE;"></i>Two-Factor Authentication (2FA)</h5>
            
            @if ($user->mfa_enabled)
                <div class="sci-alert sci-alert-success mb-3">
                    <i class="bi bi-shield-fill-check fs-3"></i>
                    <div>
                        <strong>2FA is Active:</strong> Your account is protected with authenticator security verification.
                    </div>
                </div>

                <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#disableMfaModal">
                    <i class="bi bi-shield-x me-1"></i>Disable 2FA Security
                </button>

                <!-- Disable MFA Confirmation Modal -->
                <div class="modal fade" id="disableMfaModal" tabindex="-1" aria-labelledby="disableMfaModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow-lg rounded-4">
                            <form method="POST" action="{{ route('mfa.disable') }}">
                                @csrf
                                <div class="modal-header bg-danger text-white rounded-top-4">
                                    <h5 class="modal-title fs-6 fw-bold" id="disableMfaModalLabel">
                                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Disable Two-Factor Authentication
                                    </h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <p class="text-muted small mb-3">
                                        Disabling 2FA will reduce your account security. To proceed, please confirm your current account password:
                                    </p>
                                    <div class="mb-3">
                                        <label for="mfa_current_password" class="form-label small fw-semibold">Current Password</label>
                                        <input type="password" class="form-control" id="mfa_current_password" name="current_password" required placeholder="Enter current password">
                                    </div>
                                </div>
                                <div class="modal-footer bg-light rounded-bottom-4">
                                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-danger btn-sm">Confirm & Disable 2FA</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-muted small mb-3">
                    Enhance your account security by requiring a 6-digit confirmation code from an authenticator app (like Google Authenticator) during login.
                </p>
                <a href="{{ route('mfa.setup') }}" class="btn btn-sci-outline btn-sm">
                    <i class="bi bi-qr-code me-1"></i>Set Up 2FA Security
                </a>
            @endif
        </div>
    </div>
</div>
@endsection
