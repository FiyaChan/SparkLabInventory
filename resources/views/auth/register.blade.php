@extends('layouts.guest')

@section('content')
<div class="sci-card p-4 p-md-5" style="width: 100%; max-width: 480px;">
    <div class="text-center mb-4">
        <div class="sci-logo-flask mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.6rem;">
            <i class="bi bi-box2-heart-fill"></i>
        </div>
        <div class="mb-2">
            <span class="sci-badge sci-badge-purple text-wrap" style="line-height: 1.4;">
                Secure Inventory &amp; E-Commerce Management System
            </span>
        </div>
        <h3 class="sci-heading fw-bold mb-1" style="color: #1E1B4B;">Join Explorer Club</h3>
        <p class="text-muted small mb-0">Create your account for hands-on STEM experiments &amp; kit delivery</p>
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

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="sci-form-label">Parent / Scientist Full Name</label>
            <input type="text" class="sci-form-control form-control" id="name" name="name"
                   value="{{ old('name') }}" placeholder="Dr. Jane Doe / Junior Explorer" required autofocus>
        </div>

        <div class="mb-3">
            <label for="email" class="sci-form-label">Email Address</label>
            <input type="email" class="sci-form-control form-control" id="email" name="email"
                   value="{{ old('email') }}" placeholder="parent@example.com" required>
        </div>

        <div class="mb-3">
            <label for="password" class="sci-form-label">Create Password</label>
            <input type="password" class="sci-form-control form-control" id="password" name="password" 
                   placeholder="Min. 8 characters" required>
            <div class="text-muted small mt-1">Must contain letters, numbers, and symbols.</div>
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="sci-form-label">Confirm Password</label>
            <input type="password" class="sci-form-control form-control" id="password_confirmation" name="password_confirmation" 
                   placeholder="Re-enter password" required>
        </div>

        <button type="submit" class="btn btn-sci-primary w-100 py-2 fs-6">
            <i class="bi bi-stars me-1"></i>Create Explorer Account
        </button>

        <div class="text-center mt-4 pt-3 border-top" style="border-color: #EDE9FE !important;">
            <span class="small text-muted">Already registered? <a href="{{ route('login') }}" class="fw-bold text-decoration-none" style="color: #7E22CE;">Sign In Here</a></span>
        </div>
    </form>
</div>
@endsection
