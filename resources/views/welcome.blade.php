<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Science Experiment Kits for Kids</title>

    <!-- Google Fonts & Bootstrap 5 & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Bright Purple Kids Science Theme -->
    <link href="{{ asset('css/science-theme.css') }}" rel="stylesheet">
</head>
<body class="sci-theme">
    <!-- Navbar -->
    <nav class="sci-navbar navbar navbar-expand-lg">
        <div class="container-fluid px-lg-5">
            <a class="sci-brand" href="{{ route('shop.index') }}">
                <x-app-logo theme="science" size="md" />
            </a>

            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('shop.index') }}" class="btn btn-sci-outline btn-sm d-none d-sm-inline-flex">
                    <i class="bi bi-stars me-1 text-warning"></i>Browse All Kits
                </a>
                @auth
                    <a href="{{ route('profile.edit') }}" class="btn btn-sci-secondary btn-sm">
                        <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                    </a>
                    @if (Auth::user()->hasAnyRole(['admin', 'staff']))
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sci-primary btn-sm">
                            <i class="bi bi-speedometer2 me-1"></i>Admin Console
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="btn btn-sci-outline btn-sm">Sign In</a>
                    <a href="{{ route('register') }}" class="btn btn-sci-primary btn-sm">Join Club</a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-5 text-center position-relative overflow-hidden">
        <div class="container py-lg-5">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="d-inline-flex align-items-center gap-2 mb-3 flex-wrap justify-content-center">
                        <span class="sci-badge sci-badge-purple">
                            <i class="bi bi-rocket-takeoff-fill text-warning"></i> Fun STEM Learning
                        </span>
                        <span class="sci-badge sci-badge-pink">
                            <i class="bi bi-magic"></i> 100% Kid-Safe & Non-Toxic
                        </span>
                        <span class="sci-badge sci-badge-cyan">
                            <i class="bi bi-award-fill text-warning"></i> Ages 5 to 14+
                        </span>
                    </div>

                    <h1 class="display-3 fw-bold mb-3" style="letter-spacing: -0.03em; color: #1E1B4B;">
                        Spark Wonder with Epic <br>
                        <span style="background: linear-gradient(135deg, #7E22CE 0%, #9333EA 40%, #EC4899 75%, #EA580C 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                            Science Experiment Kits!
                        </span>
                        <span class="d-inline-block ms-1 align-middle" style="-webkit-text-fill-color: initial; text-shadow: none;">🚀🧪✨</span>
                    </h1>

                    <p class="lead mb-4 mx-auto fw-medium" style="max-width: 760px; font-size: 1.25rem; color: #334155;">
                        Transform your home into a magical playground of discovery! Bubbling volcanoes, glowing crystal gardens, slime chemistry, and DIY solar robotics designed to ignite young minds.
                    </p>

                    <div class="d-flex justify-content-center gap-3 flex-wrap mb-5">
                        <a href="{{ route('shop.index') }}" class="btn btn-sci-primary btn-lg px-4 py-3 fs-5">
                            <i class="bi bi-stars"></i>
                            <span>Explore Science Kits</span>
                        </a>
                        <a href="{{ route('register') }}" class="btn btn-sci-outline btn-lg px-4 py-3 fs-5">
                            <i class="bi bi-box2-heart"></i>
                            <span>Join Explorer Club</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Experiment Pillars Cards -->
            <div class="row g-4 mt-2 text-start">
                <div class="col-md-4">
                    <div class="sci-card h-100 p-4">
                        <div class="sci-badge sci-badge-pink mb-3">
                            <i class="bi bi-fire text-danger"></i> Eruptions & Slime
                        </div>
                        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">🌋 Volcano & Slime Lab</h4>
                        <p class="small mb-0 fw-medium" style="color: #334155; line-height: 1.6;">
                            Create foaming volcanic explosions, magnetic goo, glow-in-the-dark slime, and fizzy chemical color-changing potions.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sci-card h-100 p-4">
                        <div class="sci-badge sci-badge-purple mb-3">
                            <i class="bi bi-gem text-primary"></i> Geology & Discovery
                        </div>
                        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">💎 Crystal Growing Garden</h4>
                        <p class="small mb-0 fw-medium" style="color: #334155; line-height: 1.6;">
                            Grow dazzling sparkling crystals in 24 hours, excavate dinosaur fossils, and explore shimmering mineral geodes.
                        </p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="sci-card h-100 p-4">
                        <div class="sci-badge sci-badge-cyan mb-3">
                            <i class="bi bi-robot text-info"></i> Physics & Robotics
                        </div>
                        <h4 class="fw-bold mb-2" style="color: #1E1B4B;">🚀 Rockets & Robot Builders</h4>
                        <p class="small mb-0 fw-medium" style="color: #334155; line-height: 1.6;">
                            Build water rocket launchers, solar powered robots, magnetic racing tracks, and light-up electrical circuits.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="sci-footer mt-auto">
        <div class="container text-center small py-3">
            <p class="mb-1 text-white">&copy; {{ date('Y') }} {{ config('app.name') }}. Science Experiment Kits for Kids.</p>
            <div class="d-flex justify-content-center gap-3 text-light">
                <a href="{{ route('shop.index') }}">Browse Kits</a>
                <span>&bull;</span>
                <a href="{{ route('login') }}">Member Sign In</a>
                <span>&bull;</span>
                <a href="{{ route('admin.dashboard') }}">Staff Portal</a>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
