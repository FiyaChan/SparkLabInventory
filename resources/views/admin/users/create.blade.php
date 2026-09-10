@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Staff / Administrator</h4>
        <p class="text-muted small mb-0">Provision internal credentials for warehouse operators, managers, or super admins.</p>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-admin-outline">
        <i class="bi bi-arrow-left me-1"></i>Back to Users
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please fix the following:</div>
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-body">
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Dr. Jane Smith" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="e.g. janesmith@institution.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role Authorization <span class="text-danger">*</span></label>
                        <select name="role" class="form-select" required>
                            <option value="">Select role privilege...</option>
                            <option value="staff" @selected(old('role') === 'staff')>Staff (Inventory, Products & Orders)</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin / Super Admin (Full Privileges & Logs)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Temporary Initial Password <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters" required>
                        <div class="form-text small text-muted">Must contain uppercase, lowercase, number, and symbol.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-admin-primary flex-grow-1">
                            <i class="bi bi-person-check-fill me-1"></i>Provision Account
                        </button>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-admin-outline">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="admin-card bg-light border-0">
            <div class="admin-card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-shield-lock text-primary me-2"></i>Privilege Guidelines</h6>
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2"><strong>Staff</strong> accounts can update physical stock levels, create and edit catalog items, and process incoming customer orders.</li>
                    <li class="mb-2"><strong>Admin / Super Admin</strong> accounts possess elevated clearance to view security audit trails, execute financial reports, manage user access, and configure systemic controls.</li>
                    <li>Accounts can be disabled or promoted instantly at any time from the main Users console.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
