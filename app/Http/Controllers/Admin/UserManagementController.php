<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CreateStaffUserRequest;
use App\Http\Requests\Admin\ToggleUserStatusRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('user.manage');

        $users = User::with('roles')
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->input('search').'%')
                      ->orWhere('email', 'like', '%'.$request->input('search').'%');
                });
            })
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->authorize('user.manage');
        return view('admin.users.create');
    }

    /**
     * Provisioning staff/admin accounts is deliberately kept as an ADMIN-ONLY
     * action, separate from the public registration flow (Sprint 1), which
     * only ever assigns the 'customer' role. There is no self-service path
     * to becoming staff/admin anywhere in the system — this is the sole entry point.
     */
    public function store(CreateStaffUserRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'email_verified_at' => now(), // admin-created accounts are pre-verified
        ]);

        $user->assignRole($validated['role']);

        ActivityLog::record('user.created_by_admin', [
            'created_user_id' => $user->id,
            'role' => $validated['role'],
            'created_by' => Auth::id(),
        ]);

        return redirect()->route('admin.users.index')->with('status', 'User account created.');
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user)
    {
        // Prevent an admin from demoting themselves — a common self-lockout
        // bug: if the last admin accidentally removes their own admin role,
        // NO ONE in the system can manage users/roles anymore (no other
        // account has 'user.manage'), effectively bricking the admin panel.
        if ($user->id === Auth::id()) {
            return back()->withErrors(['role' => 'You cannot change your own role.']);
        }

        // Prevent removing admin from the LAST remaining admin account —
        // same failure mode as above, just triggered by a different admin
        // demoting someone else instead of themselves.
        if ($user->hasRole('admin') && $request->validated()['role'] !== 'admin') {
            $adminCount = User::role('admin')->count();
            if ($adminCount <= 1) {
                return back()->withErrors(['role' => 'Cannot remove the last remaining admin account.']);
            }
        }

        $oldRole = $user->getRoleNames()->first();
        $user->syncRoles([$request->validated()['role']]);

        ActivityLog::record('user.role_changed', [
            'user_id' => $user->id,
            'from' => $oldRole,
            'to' => $request->validated()['role'],
            'changed_by' => Auth::id(),
        ]);

        return back()->with('status', 'User role updated.');
    }

    public function toggleStatus(ToggleUserStatusRequest $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['status' => 'You cannot disable your own account.']);
        }

        if ($user->hasRole('admin') && $user->is_active) {
            $activeAdminCount = User::role('admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                return back()->withErrors(['status' => 'Cannot disable the last active admin account.']);
            }
        }

        $user->update(['is_active' => ! $user->is_active]);

        ActivityLog::record($user->is_active ? 'user.enabled' : 'user.disabled', [
            'user_id' => $user->id,
            'changed_by' => Auth::id(),
        ]);

        return back()->with('status', 'Account status updated.');
    }
}
