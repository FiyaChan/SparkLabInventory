@props([
    'size' => 'md',
    'theme' => 'science', // 'science' (ecommerce) or 'admin' (staff/admin)
    'showText' => true,
    'textColor' => null
])

@php
    $logoPath = null;
    foreach (['images/logo.png', 'images/logo.svg', 'images/logo.webp', 'images/logo.jpg'] as $path) {
        if (file_exists(public_path($path))) {
            $logoPath = asset($path);
            break;
        }
    }

    $calculatedColor = $textColor ?? ($theme === 'science' ? '#2E1065' : '#FFFFFF');
@endphp

<div class="d-inline-flex align-items-center gap-2 app-logo-container">
    @if ($theme === 'admin')
        {{-- Admin & Staff Area: Logo / Icon Only (Clean) --}}
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" 
                 style="{{ $size === 'sm' ? 'height: 36px; max-width: 160px;' : ($size === 'lg' ? 'height: 52px;' : 'height: 42px;') }} object-fit: contain; background: transparent;">
        @else
            <div class="brand-icon" style="{{ $size === 'sm' ? 'width: 36px; height: 36px; font-size: 1.1rem;' : ($size === 'lg' ? 'width: 44px; height: 44px; font-size: 1.3rem;' : 'width: 38px; height: 38px; font-size: 1.15rem;') }}">
                <i class="bi bi-cpu-fill"></i>
            </div>
        @endif
    @else
        {{-- E-Commerce Customer Store: Stylish Shopping Bag Icon --}}
        <div class="sci-logo-flask" style="{{ $size === 'sm' ? 'width: 32px; height: 32px; font-size: 1rem;' : ($size === 'lg' ? 'width: 50px; height: 50px; font-size: 1.6rem;' : 'width: 40px; height: 40px; font-size: 1.25rem;') }}">
            <i class="bi bi-bag-heart-fill" style="color: #FBBF24;"></i>
        </div>

        @if ($showText)
            <span class="sci-heading fw-bold" 
                  style="{{ $size === 'sm' ? 'font-size: 0.95rem;' : ($size === 'lg' ? 'font-size: 1.45rem;' : 'font-size: 1.2rem;') }}; color: {{ $calculatedColor }};">
                {{ config('app.name') }}
            </span>
        @endif
    @endif
</div>
