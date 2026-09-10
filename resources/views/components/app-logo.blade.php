@props([
    'size' => 'md',
    'theme' => 'science', // 'science' or 'admin'
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
    @if ($logoPath)
        <img src="{{ $logoPath }}" alt="{{ config('app.name') }}" 
             style="{{ $size === 'sm' ? 'height: 28px;' : ($size === 'lg' ? 'height: 48px;' : 'height: 38px;') }} object-fit: contain;">
    @else
        @if ($theme === 'admin')
            <div class="brand-icon" style="{{ $size === 'sm' ? 'width: 30px; height: 30px; font-size: 0.95rem;' : ($size === 'lg' ? 'width: 44px; height: 44px; font-size: 1.3rem;' : 'width: 36px; height: 36px; font-size: 1.1rem;') }}">
                <i class="bi bi-cpu-fill"></i>
            </div>
        @else
            <div class="sci-logo-flask" style="{{ $size === 'sm' ? 'width: 32px; height: 32px; font-size: 1rem;' : ($size === 'lg' ? 'width: 50px; height: 50px; font-size: 1.6rem;' : 'width: 40px; height: 40px; font-size: 1.25rem;') }}">
                <i class="bi bi-stars" style="color: #FBBF24;"></i>
            </div>
        @endif
    @endif

    @if ($showText)
        <span class="{{ $theme === 'admin' ? 'fw-bold text-white' : 'sci-heading fw-bold' }}" 
              style="{{ $size === 'sm' ? 'font-size: 0.95rem;' : ($size === 'lg' ? 'font-size: 1.45rem;' : 'font-size: 1.2rem;') }}; color: {{ $calculatedColor }};">
            {{ config('app.name') }}
        </span>
    @endif
</div>
