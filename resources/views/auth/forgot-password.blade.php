@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 440px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-key-fill"></i>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Recover Password</h3>
        <p class="text-muted small mb-0">Enter your email to receive a secure password reset link.</p>
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

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="sci-form-label">Registered Email</label>
            <input type="email" class="sci-form-control form-control" id="email" name="email"
                   value="{{ old('email') }}" placeholder="parent@example.com" required autofocus>
        </div>

        <button type="submit" class="btn btn-sci-primary w-100 py-2 fs-6">
            <i class="bi bi-envelope-arrow-up me-1"></i>Send Reset Link
        </button>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: #EDE9FE !important;">
            <a href="{{ route('login') }}" class="small fw-bold text-decoration-none" style="color: #7E22CE;"><i class="bi bi-arrow-left me-1"></i>Return to Sign In</a>
        </div>
    </form>
</div>
@endsection
