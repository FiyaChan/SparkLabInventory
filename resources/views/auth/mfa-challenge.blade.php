@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 440px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Two-Factor Challenge</h3>
        <p class="text-muted small mb-0">Enter the temporary code from your authenticator app</p>
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

    <form method="POST" action="{{ route('mfa.verify') }}">
        @csrf

        <div class="mb-4">
            <label for="code" class="sci-form-label text-center d-block">6-Digit Authenticator Code</label>
            <input type="text" class="sci-form-control form-control text-center fs-3 fw-bold letter-spacing-lg" 
                   id="code" name="code" pattern="[0-9]*" inputmode="numeric" 
                   maxlength="6" placeholder="000000" required autofocus autocomplete="one-time-code" style="letter-spacing: 0.25em; color: #7E22CE !important;">
        </div>

        <button type="submit" class="btn btn-sci-primary w-100 py-2 fs-6">
            <i class="bi bi-check-circle me-1"></i>Verify & Authorize
        </button>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: #EDE9FE !important;">
            <a href="{{ route('login') }}" class="small fw-bold text-decoration-none" style="color: #7E22CE;"><i class="bi bi-arrow-left me-1"></i>Back to Sign In</a>
        </div>
    </form>
</div>
@endsection
