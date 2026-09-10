<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request)
    {
        // $request->validated() only returns fields declared in the Form Request rules —
        // this prevents mass-assignment of unexpected fields. Never use $request->all().
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'], // auto-hashed via the 'hashed' cast on User
        ]);

        // Every new registration defaults to the least-privileged role.
        // Staff/admin roles are only ever assigned by an existing admin — never self-service.
        $user->assignRole('customer');

        ActivityLog::record('user.registered', ['user_id' => $user->id]);

        // Dispatch the Registered event to trigger SendEmailVerificationNotification
        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            Log::error('Failed sending verification email on registration: '.$e->getMessage(), [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        Auth::login($user);

        return redirect()->route('verification.notice');
    }
}
