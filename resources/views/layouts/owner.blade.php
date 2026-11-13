<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Owner Portal — Niwasthal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/lodgemate.css') }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
</head>
<body x-data="{ sidebarOpen: false }">
    <!-- Mobile Backdrop -->
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-lg-none"
         style="z-index: 1035; display: none;"></div>

    <!-- Sidebar -->
    <aside class="lm-sidebar" :class="{ 'open': sidebarOpen }">
        <a href="{{ route('owner.dashboard') }}" class="lm-sidebar-brand">
            <span class="brand-icon"><i class="bi bi-buildings-fill"></i></span>
            <span class="d-flex flex-column lh-1">
                <span>Niwasthal</span>
                <small class="text-secondary fw-normal" style="font-size: 0.65rem; letter-spacing: 0.04em; margin-top: 3px;">OWNER WORKSPACE</small>
            </span>
        </a>

        <nav class="lm-nav">
            <div class="lm-nav-section">Overview</div>
            <a href="{{ route('owner.dashboard') }}" class="lm-nav-link {{ request()->routeIs('owner.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>

            <div class="lm-nav-section">Management</div>
            <a href="{{ route('owner.properties.index') }}" class="lm-nav-link {{ request()->routeIs('owner.properties.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Properties & Rooms
            </a>
            <a href="{{ route('owner.students.index') }}" class="lm-nav-link {{ request()->routeIs('owner.students.*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i> {{ $occupantTermPlural ?? 'Students' }}
            </a>

            <div class="lm-nav-section">Billing & Finance</div>
            <a href="{{ route('owner.rent.index') }}" class="lm-nav-link {{ request()->routeIs('owner.rent.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Rent & Dues
            </a>
            <a href="{{ route('owner.payments.index') }}" class="lm-nav-link {{ request()->routeIs('owner.payments.*') ? 'active' : '' }}">
                <i class="bi bi-arrow-left-right"></i> Transactions
            </a>
            <a href="{{ route('owner.electricity.index') }}" class="lm-nav-link {{ request()->routeIs('owner.electricity.*') ? 'active' : '' }}">
                <i class="bi bi-lightning-charge-fill"></i> Electricity Bills
            </a>
            <a href="{{ route('owner.deposits.index') }}" class="lm-nav-link {{ request()->routeIs('owner.deposits.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock-fill"></i> Security Deposits
            </a>
            <a href="{{ route('owner.expenses.index') }}" class="lm-nav-link {{ request()->routeIs('owner.expenses.*') ? 'active' : '' }}">
                <i class="bi bi-wallet2"></i> Expenses
            </a>

            <div class="lm-nav-section">Operations</div>
            <a href="{{ route('owner.complaints.index') }}" class="lm-nav-link {{ request()->routeIs('owner.complaints.*') ? 'active' : '' }}">
                <i class="bi bi-exclamation-triangle-fill"></i> Complaints
            </a>
            <a href="{{ route('owner.notifications.index') }}" class="lm-nav-link {{ request()->routeIs('owner.notifications.*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i> Notifications
            </a>
            <a href="{{ route('owner.reports.index') }}" class="lm-nav-link {{ request()->routeIs('owner.reports.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-line-fill"></i> Reports
            </a>
            <a href="{{ route('owner.settings.index') }}" class="lm-nav-link {{ request()->routeIs('owner.settings.*') ? 'active' : '' }}">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </nav>

        <div class="p-3 border-top border-secondary border-opacity-25 lm-sidebar-footer">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-left"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="lm-main">
        <header class="lm-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm d-lg-none" @click="sidebarOpen = !sidebarOpen">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h5 class="mb-0 fw-semibold">@yield('page-title', 'Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('owner.notifications.index') }}" class="btn btn-light btn-sm position-relative">
                    <i class="bi bi-bell"></i>
                </a>
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                        <span class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('owner.settings.index') }}"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="lm-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
