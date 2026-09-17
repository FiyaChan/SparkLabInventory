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

@if ($theme === 'admin')
    {{-- Bahagian Admin: Paparkan Logo Syarikat (Centered & Clean) --}}
    <div class="d-flex align-items-center justify-content-center w-100 app-logo-container">
        @if ($logoPath)
            <img src="{{ $logoPath }}" alt="Company Logo" 
                 style="max-height: 46px; max-width: 170px; height: auto; width: auto; object-fit: contain; display: block; margin: 0 auto;">
        @else
            <div class="brand-icon" style="width: 36px; height: 36px; font-size: 1.1rem; border-radius: 8px; margin: 0 auto;">
                <i class="bi bi-cpu-fill"></i>
            </div>
        @endif
    </div>
@else
    {{-- Bahagian E-Commerce / Customer Store: Ikon Bag Shopping + SparkLab Kids Science --}}
    <div class="d-inline-flex align-items-center app-logo-container" style="gap: 12px;">
        <div class="sci-logo-flask flex-shrink-0" style="{{ $size === 'sm' ? 'width: 34px; height: 34px; font-size: 1.05rem; border-radius: 10px;' : ($size === 'lg' ? 'width: 50px; height: 50px; font-size: 1.6rem; border-radius: 14px;' : 'width: 42px; height: 42px; font-size: 1.3rem; border-radius: 12px;') }}">
            <i class="bi bi-bag-heart-fill" style="color: #FBBF24;"></i>
        </div>

        @if ($showText)
            <span class="sci-heading fw-bold" 
                  style="{{ $size === 'sm' ? 'font-size: 0.95rem;' : ($size === 'lg' ? 'font-size: 1.45rem;' : 'font-size: 1.25rem;') }}; color: {{ $calculatedColor }}; font-weight: 800; letter-spacing: -0.01em; white-space: nowrap;">
                SparkLab <span style="font-weight: 600; opacity: 0.88; font-size: 0.88em;">Kids Science</span>
            </span>
        @endif
    </div>
@endif

