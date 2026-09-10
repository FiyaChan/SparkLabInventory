<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('customer.profile.edit', ['user' => Auth::user()]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $emailChanged = $user->email !== $request->validated()['email'];

        $user->fill($request->validated());
        $user->save();

        ActivityLog::record('profile.updated', ['user_id' => $user->id, 'email_changed' => $emailChanged]);

        return back()->with('status', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = Auth::user();

        // Explicit re-check of the CURRENT password before allowing a change —
        // this stops an attacker who has hijacked an active session (e.g. via
        // an unattended browser) from silently locking the real owner out by
        // changing the password without re-proving they know it.
        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update(['password' => $validated['password']]);

        ActivityLog::record('password.changed', ['user_id' => $user->id]);

        return back()->with('status', 'Password changed successfully.');
    }
}
