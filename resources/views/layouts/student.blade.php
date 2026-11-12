<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Student Portal — Niwasthal')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/lodgemate.css') }}" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body x-data="{ sidebarOpen: false }">
    <div x-show="sidebarOpen" @click="sidebarOpen = false"
         class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-lg-none"
         style="z-index: 1035; display: none;"></div>

    <aside class="lm-sidebar" :class="{ 'open': sidebarOpen }">
        <a href="{{ route('student.dashboard') }}" class="lm-sidebar-brand">
            <span class="brand-icon"><i class="bi bi-buildings-fill"></i></span>
            <span class="d-flex flex-column lh-1">
                <span>Niwasthal</span>
                <small class="text-secondary fw-normal" style="font-size: 0.65rem; letter-spacing: 0.04em; margin-top: 3px;">TENANT PORTAL</small>
            </span>
        </a>

        <nav class="lm-nav">
            <div class="lm-nav-section">Student Portal</div>
            <a href="{{ route('student.dashboard') }}" class="lm-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door-fill"></i> Dashboard
            </a>
            <a href="{{ route('student.room') }}" class="lm-nav-link {{ request()->routeIs('student.room') ? 'active' : '' }}">
                <i class="bi bi-door-open-fill"></i> My Room & Bed
            </a>
            <a href="{{ route('student.rent.index') }}" class="lm-nav-link {{ request()->routeIs('student.rent.*') ? 'active' : '' }}">
                <i class="bi bi-receipt"></i> Rent & Dues
            </a>
            <a href="{{ route('student.payments.index') }}" class="lm-nav-link {{ request()->routeIs('student.payments.*') ? 'active' : '' }}">
                <i class="bi bi-credit-card-fill"></i> Pay & History
            </a>
            <a href="{{ route('student.electricity.index') }}" class="lm-nav-link {{ request()->routeIs('student.electricity.*') ? 'active' : '' }}">
                <i class="bi bi-lightning-charge-fill"></i> Electricity Bills
            </a>
            <a href="{{ route('student.complaints.index') }}" class="lm-nav-link {{ request()->routeIs('student.complaints.*') ? 'active' : '' }}">
                <i class="bi bi-chat-square-text-fill"></i> Complaints
            </a>
            <a href="{{ route('student.notifications.index') }}" class="lm-nav-link {{ request()->routeIs('student.notifications.*') ? 'active' : '' }}">
                <i class="bi bi-bell-fill"></i> Notifications
            </a>
        </nav>

        <div class="p-3 border-top border-secondary border-opacity-25">
            <form action="{{ route('student.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm w-100 d-flex align-items-center justify-content-center gap-2">
                    <i class="bi bi-box-arrow-left"></i> Sign Out
                </button>
            </form>
        </div>
    </aside>

    <div class="lm-main">
        <header class="lm-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light btn-sm d-lg-none" @click="sidebarOpen = !sidebarOpen">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h5 class="mb-0 fw-semibold">@yield('page-title', 'Student Dashboard')</h5>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('student.notifications.index') }}" class="btn btn-light btn-sm">
                    <i class="bi bi-bell"></i>
                </a>
                <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill">
                    <i class="bi bi-person-circle me-1"></i> {{ auth()->user()->name }}
                </span>
            </div>
        </header>

        <main class="lm-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4">
                    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4">
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
