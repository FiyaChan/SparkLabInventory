@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5 my-4" style="width: 100%; max-width: 500px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-qr-code"></i>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Setup Two-Factor (2FA)</h3>
        <p class="text-muted small mb-0">Enhanced security for your family account</p>
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

    <div class="p-3 rounded-3 mb-4" style="background: #F8F5FF; border: 2px solid #E9D5FF;">
        <h6 class="fw-bold small mb-2" style="color: #4C1D95;"><i class="bi bi-camera-fill me-1"></i>Step 1: Scan Optical Matrix</h6>
        <p class="text-muted small mb-3">Open Google Authenticator, Authy, or Microsoft Authenticator and scan this code:</p>

        <div class="text-center my-3 p-2 bg-white rounded d-inline-block mx-auto d-block border shadow-sm" style="max-width: 240px;">
            <div class="d-flex justify-content-center p-2">
                {!! $qrCodeSvg !!}
            </div>
        </div>

        <div class="text-center mt-3">
            <span class="text-muted small d-block mb-1">Manual Secret Key:</span>
            <code class="fs-6 fw-bold user-select-all p-1 rounded border" style="background: #FFFFFF; color: #7E22CE;">{{ $secret }}</code>
        </div>
    </div>

    <form method="POST" action="{{ route('mfa.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="code" class="sci-form-label text-center d-block">Step 2: Enter 6-Digit Generator Code</label>
            <input type="text" class="sci-form-control form-control text-center fs-3 fw-bold letter-spacing-lg" 
                   id="code" name="code" pattern="[0-9]*" inputmode="numeric" 
                   maxlength="6" placeholder="000000" required autofocus autocomplete="off" style="letter-spacing: 0.25em; color: #7E22CE !important;">
        </div>

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-sci-primary py-2 fs-6">
                <i class="bi bi-shield-check me-1"></i>Activate 2FA Security
            </button>
            <a href="{{ route('profile.edit') }}" class="btn btn-sci-outline btn-sm">Configure Later</a>
        </div>
    </form>
</div>
@endsection
