@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 460px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Set New Password</h3>
        <p class="text-muted small mb-0">Establish updated credentials for your explorer account</p>
    </div>

    @if ($errors->any())
        <div class="sci-alert sci-alert-danger mb-4">
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="sci-form-label">Email Address</label>
            <input type="email" class="sci-form-control form-control" id="email" name="email"
                   value="{{ old('email', $request->email) }}" required readonly>
        </div>

        <div class="mb-3">
            <label for="password" class="sci-form-label">New Password</label>
            <input type="password" class="sci-form-control form-control" id="password" name="password" required autofocus>
            <div class="text-muted small mt-1">Min 8 characters (upper, lower, digit & symbol).</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="sci-form-label">Confirm New Password</label>
            <input type="password" class="sci-form-control form-control" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-sci-primary w-100 py-2 fs-6">
            <i class="bi bi-check2-circle me-1"></i>Commit New Password
        </button>
    </form>
</div>
@endsection
