@props([
    'size' => 'md',
    'theme' => 'science', // 'science' (ecommerce) or 'admin' (staff/admin)
    'showText' => true,
    'textColor' => null
])

@php
    $logoPath = null;
    $possiblePaths = [
        'images/logo.png',
        'images/logo.jpg',
        'images/logo.jpeg',
        'images/logo.svg',
        'images/logo.webp',
        'logo.png',
        'logo.jpg',
        'logo.jpeg',
        'logo.svg',
        'logo.webp'
    ];

    foreach ($possiblePaths as $path) {
        if (file_exists(public_path($path))) {
            $logoPath = asset($path);
            break;
        }
    }

    $calculatedColor = $textColor ?? ($theme === 'science' ? '#2E1065' : '#FFFFFF');
@endphp

<div class="d-inline-flex align-items-center justify-content-center w-100 app-logo-container">
    @if ($theme === 'admin')
        {{-- Bahagian Admin: Paparkan Logo Syarikat (Centered) --}}
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="Company Logo" 
                 style="max-height: 46px; max-width: 170px; height: auto; width: auto; object-fit: contain; display: block; margin: 0 auto;">
        @else
            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1.1rem; border-radius: 8px; margin: 0 auto;">
                <i class="bi bi-cpu-fill"></i>
            </div>
        @endif
    @else
        {{-- Bahagian E-Commerce / Customer Store: Ikon Bag Shopping --}}
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
