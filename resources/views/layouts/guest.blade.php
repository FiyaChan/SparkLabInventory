<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Secure Inventory &amp; E-Commerce Management System</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Bootstrap 5 & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700;900&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Science Theme -->
    <link href="{{ asset('css/science-theme.css') }}" rel="stylesheet">
</head>
<body class="sci-theme">
    <nav class="sci-navbar navbar">
        <div class="container-fluid px-lg-5">
            <a class="sci-brand" href="{{ route('shop.index') }}">
                <x-app-logo theme="science" size="md" />
            </a>
            <div>
                <a href="{{ route('shop.index') }}" class="btn btn-sci-outline btn-sm">
                    <i class="bi bi-arrow-left me-1"></i>Return to Catalog
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5 flex-grow-1 d-flex align-items-center justify-content-center">
        @yield('content')
    </main>

    <footer class="text-center py-3 text-muted small" style="border-top: 1px solid rgba(255,255,255,0.06);">
        &copy; {{ date('Y') }} {{ config('app.name') }} &bull; Secure Inventory &amp; E-Commerce Management System. All rights reserved.
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
