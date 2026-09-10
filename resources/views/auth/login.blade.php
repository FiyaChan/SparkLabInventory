@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 440px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-stars"></i>
        </div>
        <div class="mb-2">
            <span class="sci-badge sci-badge-purple text-wrap" style="line-height: 1.4;">
                Secure Inventory &amp; E-Commerce Management System
            </span>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Explorer Sign In</h3>
        <p class="text-muted small mb-0">Sign in to manage your science experiment orders &amp; wishlist</p>
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

    @if (session('status'))
        <div class="sci-alert sci-alert-success mb-4">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="sci-form-label">Email Address</label>
            <input type="email" class="sci-form-control form-control" id="email" name="email"
                   value="{{ old('email') }}" placeholder="parent@example.com" required autofocus>
        </div>

        <div class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="password" class="sci-form-label mb-0">Password</label>
                <a href="{{ route('password.request') }}" class="small fw-bold text-decoration-none" style="color: #7E22CE;">Forgot password?</a>
            </div>
            <input type="password" class="sci-form-control form-control" id="password" name="password" 
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
        </div>

        <div class="mb-4 form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label text-dark small fw-semibold" for="remember">Remember me on this computer</label>
        </div>

        <button type="submit" class="btn btn-sci-primary w-100 py-2 fs-6">
            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In to Club
        </button>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: #EDE9FE !important;">
            <span class="small text-muted">Don't have an account? <a href="{{ route('register') }}" class="fw-bold text-decoration-none" style="color: #7E22CE;">Join Explorer Club</a></span>
        </div>
    </form>
</div>
@endsection
