@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 480px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-envelope-check-fill"></i>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Verify Email Address</h3>
        <p class="text-muted small mb-0">Confirmation link sent to your email inbox</p>
    </div>

    <p class="small mb-4" style="color: #475569; line-height: 1.6;">
        Please check your email and click the verification link to activate your explorer account. If you didn't receive the email, click the button below to request another.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="sci-alert sci-alert-success mb-4">
            A new verification link has been dispatched to your email address.
        </div>
    @elseif (session('status'))
        <div class="sci-alert sci-alert-success mb-4">
            {{ session('status') }}
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between pt-2">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-sci-primary btn-sm">
                <i class="bi bi-arrow-repeat me-1"></i>Resend Link
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-sci-secondary btn-sm text-danger">
                <i class="bi bi-box-arrow-right me-1"></i>Sign Out
            </button>
        </form>
    </div>
</div>
@endsection
