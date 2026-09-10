<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Command — {{ config('app.name') }}</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts & Bootstrap 5 & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <!-- Admin Professional Theme -->
    <link href="{{ asset('css/admin-theme.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="admin-body">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar" id="adminSidebar">
        <a href="{{ route('admin.dashboard') }}" class="admin-sidebar-brand">
            <x-app-logo theme="admin" size="sm" />
        </a>

        <div class="admin-sidebar-menu">
            <div class="admin-sidebar-section-title">Overview</div>
            <div class="admin-nav-item">
                <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
            </div>

            <div class="admin-sidebar-section-title">Inventory Operations</div>
            <div class="admin-nav-item">
                <a class="admin-nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}" href="{{ route('admin.pos.index') }}">
                    <i class="bi bi-shop-window text-warning"></i>
                    <span>POS / Cashier</span>
                    <span class="badge bg-warning-subtle text-warning-emphasis ms-auto" style="font-size: 0.65rem;">Terminal</span>
                </a>
            </div>
            <div class="admin-nav-item">
                <a class="admin-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" href="{{ route('admin.products.index') }}">
                    <i class="bi bi-box-seam-fill"></i>
                    <span>Products</span>
                </a>
            </div>
            <div class="admin-nav-item">
                <a class="admin-nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" href="{{ route('admin.inventory.index') }}">
                    <i class="bi bi-stack"></i>
                    <span>Inventory & Stock</span>
                </a>
            </div>
            <div class="admin-nav-item">
                <a class="admin-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" href="{{ route('admin.orders.index') }}">
                    <i class="bi bi-receipt-cutoff"></i>
                    <span>Orders</span>
                </a>
            </div>

            <div class="admin-sidebar-section-title">Analytics & Control</div>
            @can('report.view')
                <div class="admin-nav-item">
                    <a class="admin-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" href="{{ route('admin.reports.index') }}">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>Reports & BI</span>
                    </a>
                </div>
            @endcan

            @can('user.manage')
                <div class="admin-nav-item">
                    <a class="admin-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                        <i class="bi bi-people-fill"></i>
                        <span>Users & Roles</span>
                    </a>
                </div>
            @endcan

            @can('activity-log.view')
                <div class="admin-nav-item">
                    <a class="admin-nav-link {{ request()->routeIs('admin.activity-logs.*') ? 'active' : '' }}" href="{{ route('admin.activity-logs.index') }}">
                        <i class="bi bi-shield-check"></i>
                        <span>Audit Trail Logs</span>
                    </a>
                </div>
            @endcan

            <div class="admin-sidebar-section-title">External</div>
            <div class="admin-nav-item">
                <a class="admin-nav-link text-info" href="{{ route('shop.index') }}" target="_blank">
                    <i class="bi bi-box-arrow-up-right text-info"></i>
                    <span>View Live Shop</span>
                </a>
            </div>
        </div>

        <div class="admin-sidebar-footer">
            <div class="dropup w-100">
                <button class="admin-user-pill w-100 btn p-0 text-start border-0 d-flex align-items-center justify-content-between text-white" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <div class="admin-user-avatar flex-shrink-0">
                            {{ strtoupper(substr(Auth::user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="admin-user-info">
                            <div class="admin-user-name">{{ Auth::user()->name }}</div>
                            <div class="admin-user-role">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.68rem;">
                                    {{ Auth::user()->getRoleNames()->first() ?? 'Staff' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <i class="bi bi-three-dots-vertical text-muted fs-6 ms-1"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-dark shadow-lg border-0 mb-2 py-2" style="background: #1e293b; min-width: 220px;">
                    <li class="px-3 py-2 border-bottom border-secondary border-opacity-25">
                        <div class="fw-bold text-white small">{{ Auth::user()->name }}</div>
                        <div class="text-muted small text-truncate" style="font-size: 0.75rem;">{{ Auth::user()->email }}</div>
                    </li>
                    <li><a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2 text-primary"></i>My Profile</a></li>
                    <li><a class="dropdown-item py-2 d-flex align-items-center" href="{{ route('shop.index') }}" target="_blank"><i class="bi bi-eye me-2 text-info"></i>View Live Shop</a></li>
                    <li><hr class="dropdown-divider border-secondary border-opacity-25"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="dropdown-item py-2 text-danger d-flex align-items-center">
                                <i class="bi bi-box-arrow-right me-2"></i>Log Out
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="admin-main-wrapper">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div class="d-flex align-items-center gap-2 text-muted small">
                    <i class="bi bi-shield-lock-fill text-primary"></i>
                    <span class="fw-semibold text-dark">Management Console</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('admin.pos.index') }}" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1 shadow-sm">
                    <i class="bi bi-shop-window"></i>
                    <span class="d-none d-sm-inline">POS Terminal</span>
                </a>

                <a href="{{ route('shop.index') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center gap-1">
                    <i class="bi bi-shop"></i>
                    <span>Customer Store</span>
                </a>
            </div>
        </header>

        <!-- Main Content -->
        <main class="admin-content-body">
            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill fs-5 text-success"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-5 text-danger"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('adminSidebar')?.classList.toggle('show');
        });
    </script>
    @stack('scripts')
</body>
</html>
