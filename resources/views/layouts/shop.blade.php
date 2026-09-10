<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Kids Science Experiment Kits & STEM Toys</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Bootstrap 5 & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Science Kids Bright Purple Theme -->
    <link href="{{ asset('css/science-theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="sci-theme">
    <!-- Announcement Ticker -->
    <div class="sci-announcement-ticker">
        <span class="sci-pulse-dot"></span>
        <span>✨ <strong>KIDS SCIENCE EXPLORER:</strong> Hands-on STEM Experiment Kits, Magic Slime & Rocket Labs | 🚀 100% Kid-Safe & Non-Toxic</span>
        <span class="d-none d-md-inline text-white-50">|</span>
        <span class="d-none d-md-inline"><i class="bi bi-gift-fill text-warning me-1"></i>Free Explorer Sticker Pack with Every Order!</span>
    </div>

    <!-- Science Kids Navbar -->
    <nav class="sci-navbar navbar navbar-expand-lg">
        <div class="container-fluid px-lg-4">
            <a class="sci-brand flex-shrink-0" href="{{ route('shop.index') }}">
                <x-app-logo theme="science" size="md" />
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#sciNavbarContent">
                <i class="bi bi-list fs-2" style="color: #7E22CE;"></i>
            </button>

            <div class="collapse navbar-collapse" id="sciNavbarContent">
                <!-- Search Bar (Shown on non-catalog pages for quick search) -->
                @unless(request()->routeIs('shop.index'))
                    <div class="mx-lg-auto my-2 my-lg-0 flex-grow-1 d-none d-md-block" style="max-width: 380px;">
                        <form method="GET" action="{{ route('shop.index') }}" class="position-relative w-100">
                            <i class="bi bi-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #9333EA; font-size: 0.95rem; pointer-events: none; z-index: 5;"></i>
                            <input type="text" name="search" class="sci-search-input" style="padding-left: 2.75rem !important;"
                                   placeholder="Search science kits..." value="{{ request('search') }}">
                        </form>
                    </div>
                @endunless

                <!-- Navigation Action Links -->
                <div class="d-flex align-items-center gap-1 gap-xl-2 flex-nowrap ms-auto">
                    <a href="{{ route('shop.index') }}" class="sci-nav-link {{ request()->routeIs('shop.index') ? 'active' : '' }}">
                        <i class="bi bi-stars text-warning fs-5"></i>
                        <span>Explore Kits</span>
                    </a>

                    @auth
                        <a href="{{ route('wishlist.index') }}" class="sci-nav-link position-relative {{ request()->routeIs('wishlist.*') ? 'active' : '' }}">
                            <i class="bi bi-heart-fill fs-5" style="color: #EC4899;"></i>
                            <span>Wishlist</span>
                        </a>

                        <a href="{{ route('cart.index') }}" class="sci-nav-link position-relative {{ request()->routeIs('cart.*') ? 'active' : '' }}">
                            <i class="bi bi-cart3 fs-5" style="color: #9333EA;"></i>
                            <span>My Cart</span>
                        </a>

                        <a href="{{ route('orders.index') }}" class="sci-nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}">
                            <i class="bi bi-rocket-takeoff-fill fs-5" style="color: #0284C7;"></i>
                            <span>My Orders</span>
                        </a>

                        <!-- Compact User Profile Avatar Dropdown -->
                        <div class="dropdown ms-2">
                            <button class="btn p-0 border-0 dropdown-toggle no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ Auth::user()->name }}">
                                <div class="sci-user-avatar">
                                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                                </div>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg py-2" style="background: #FFFFFF; border: 2px solid #E9D5FF; border-radius: 14px; min-width: 220px;">
                                <li class="px-3 py-2 border-bottom" style="border-color: #F3E8FF !important;">
                                    <div class="fw-bold text-dark small text-truncate">{{ Auth::user()->name }}</div>
                                    <div class="text-muted small text-truncate" style="font-size: 0.75rem;">{{ Auth::user()->email }}</div>
                                </li>
                                <li>
                                    <a class="dropdown-item fw-semibold d-flex align-items-center text-dark py-2" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-person-gear text-purple me-2" style="color: #7E22CE;"></i>Parent / Scientist Profile
                                    </a>
                                </li>
                                @if (Auth::user()->hasAnyRole(['admin', 'staff']))
                                    <li>
                                        <a class="dropdown-item fw-semibold text-warning-emphasis d-flex align-items-center py-2" href="{{ route('admin.dashboard') }}">
                                            <i class="bi bi-speedometer2 text-warning me-2"></i>Admin Console
                                        </a>
                                    </li>
                                @endif
                                <li><hr class="dropdown-divider my-1" style="border-color: #F3E8FF;"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button class="dropdown-item fw-semibold text-danger d-flex align-items-center py-2">
                                            <i class="bi bi-box-arrow-right me-2"></i>Log Out
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sci-outline btn-sm text-nowrap">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Sign In
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-sci-primary btn-sm text-nowrap">
                            <i class="bi bi-stars me-1"></i>Join Club
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="container-fluid px-lg-5 py-4 flex-grow-1">
        @if (session('status'))
            <div class="sci-alert sci-alert-success">
                <i class="bi bi-check2-circle fs-4 text-emerald"></i>
                <div>{{ session('status') }}</div>
            </div>
        @endif

        @if (session('error'))
            <div class="sci-alert sci-alert-danger">
                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                <div>{{ session('error') }}</div>
            </div>
        @endif

        @yield('content')
    </main>

    <!-- Science Kids High-Contrast Footer -->
    <footer class="sci-footer">
        <div class="container-fluid px-lg-5">
            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="sci-footer-title">
                        <x-app-logo theme="science" size="sm" textColor="#FFFFFF" />
                    </div>
                    <p class="small pe-lg-4">
                        Making science playful, magical, and unforgettable! From foaming volcanic eruptions and glowing slime to crystal gardens and DIY solar robots for kids aged 5 to 14+.
                    </p>
                    <div class="d-flex gap-2 mt-3 flex-wrap">
                        <span class="sci-badge sci-badge-purple"><i class="bi bi-shield-check"></i> 100% Non-Toxic</span>
                        <span class="sci-badge sci-badge-pink"><i class="bi bi-magic"></i> Step-by-Step Guides</span>
                        <span class="sci-badge sci-badge-cyan"><i class="bi bi-award"></i> STEM Certified</span>
                    </div>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <div class="sci-footer-title">Experiment Kits</div>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('shop.index') }}"><i class="bi bi-chevron-right me-1 text-warning"></i>All Science Kits</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'chemistry']) }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Slime & Chemistry</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'physics']) }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Rockets & Physics</a></li>
                        <li><a href="{{ route('shop.index', ['category' => 'biology']) }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Crystal & Biology</a></li>
                    </ul>
                </div>

                <div class="col-6 col-md-3 col-lg-2">
                    <div class="sci-footer-title">Young Explorer</div>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('orders.index') }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Track Delivery</a></li>
                        <li><a href="{{ route('cart.index') }}"><i class="bi bi-chevron-right me-1 text-warning"></i>My Cart</a></li>
                        <li><a href="{{ route('wishlist.index') }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Dream Wishlist</a></li>
                        <li><a href="{{ route('profile.edit') }}"><i class="bi bi-chevron-right me-1 text-warning"></i>Parent Account</a></li>
                    </ul>
                </div>

                <div class="col-lg-4">
                    <div class="sci-footer-title">Parent & Safety Pledge</div>
                    <p class="small">
                        Every kit is thoroughly child-safety tested with eco-friendly non-toxic ingredients and easy visual instruction cards.
                    </p>
                    <div class="p-3 rounded" style="background: rgba(255, 255, 255, 0.08); border: 1px dashed rgba(216, 180, 254, 0.4);">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-award-fill fs-2 text-warning"></i>
                            <div class="small text-white">
                                <strong>Certified STEM Learning:</strong> Designed to spark curious thinking and ignite lifelong love for science!
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-top d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 small text-light opacity-75" style="border-color: rgba(255,255,255,0.15) !important;">
                <div>
                    &copy; {{ date('Y') }} {{ config('app.name') }}. Science Experiment Kits for Kids.
                </div>
                <div class="d-flex gap-3">
                    <a href="{{ route('shop.index') }}" class="text-white">Shop Kits</a>
                    <a href="{{ route('login') }}" class="text-white">Member Login</a>
                    <a href="{{ route('admin.dashboard') }}" class="text-white">Staff Portal</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
