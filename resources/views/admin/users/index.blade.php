@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Users, Staff & Roles</h4>
        <p class="text-muted small mb-0">Manage system administrators, staff permissions, customer account statuses, and role assignments.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn btn-admin-primary">
        <i class="bi bi-person-plus-fill me-1"></i>Add Staff / Admin
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger shadow-sm mb-4">
        <ul class="mb-0 ps-3 small">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="admin-card">
    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email Address</th>
                    <th>Role Assignment</th>
                    <th>Account Status</th>
                    <th>Last Active</th>
                    <th class="text-end">Access Control</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="admin-user-avatar" style="width: 32px; height: 32px; font-size: 0.78rem;">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold">{{ $user->name }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="text-muted">{{ $user->email }}</td>
                        <td>
                            <form method="POST" action="{{ route('admin.users.role', $user) }}" class="d-inline-flex">
                                @csrf
                                @method('PATCH')
                                <select name="role" class="form-select form-select-sm" style="width: 130px;"
                                        {{ $user->id === auth()->id() ? 'disabled' : '' }}
                                        onchange="this.form.submit()">
                                    @foreach (['admin', 'staff', 'customer'] as $role)
                                        <option value="{{ $role }}" @selected($user->hasRole($role))>{{ ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </td>
                        <td>
                            @if ($user->is_active)
                                <span class="admin-badge success"><i class="bi bi-check-circle-fill"></i> Active</span>
                            @else
                                <span class="admin-badge danger"><i class="bi bi-dash-circle-fill"></i> Disabled</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $user->last_login_at?->diffForHumans() ?? 'Never logged in' }}
                        </td>
                        <td class="text-end">
                            @if ($user->id !== auth()->id())
                                <form method="POST" action="{{ route('admin.users.status', $user) }}" class="d-inline"
                                      onsubmit="return confirm('{{ $user->is_active ? 'Disable' : 'Enable' }} this user account?');">
                                    @csrf
                                    @method('PATCH')
                                    <button class="btn btn-sm btn-outline-{{ $user->is_active ? 'danger' : 'success' }}">
                                        {{ $user->is_active ? 'Disable Account' : 'Enable Account' }}
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-light text-muted border py-1 px-2">(Your Account)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="p-3 border-top">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
