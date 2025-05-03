<!DOCTYPE html>
<html lang="tr" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Yönetim Paneli') - SoneAI Admin</title>

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/sone.png') }}" type="image/png">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- DataTables Bootstrap 5 -->
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <!-- Custom Styles -->
    <style>
        :root {
            --bs-primary: #6366f1;
            --bs-primary-rgb: 99, 102, 241;
            --bs-primary-hover: #4f46e5;
            --bs-sidebar-width: 280px;
            --bs-sidebar-collapsed-width: 70px;
        }
        
        /* Base Styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s ease;
        }
        
        /* Dark mode specific styles */
        [data-bs-theme="dark"] {
            --bs-body-bg: #0f172a;
            --bs-body-color: #e2e8f0;
            --bs-border-color: #2d3748;
            --bs-card-bg: #1e293b;
            --bs-card-cap-bg: #1a2234;
            --bs-navbar-color: #e2e8f0;
            --bs-navbar-hover-color: #fff;
            --bs-navbar-active-color: #fff;
            --bs-sidebar-bg: #1e293b;
            --bs-sidebar-hover-bg: #2d3748;
            --bs-sidebar-active-bg: #3e4c6d;
        }
        
        /* Light mode specific styles */
        [data-bs-theme="light"] {
            --bs-body-bg: #f8fafc;
            --bs-body-color: #334155;
            --bs-border-color: #e2e8f0;
            --bs-card-bg: #ffffff;
            --bs-card-cap-bg: #f1f5f9;
            --bs-navbar-color: #334155;
            --bs-navbar-hover-color: #111827;
            --bs-navbar-active-color: #111827;
            --bs-sidebar-bg: #ffffff;
            --bs-sidebar-hover-bg: #f1f5f9;
            --bs-sidebar-active-bg: #e2e8f0;
        }

        /* Layout */
        .wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .content-wrapper {
            flex: 1;
            width: calc(100% - var(--bs-sidebar-width));
            margin-left: var(--bs-sidebar-width);
            transition: margin-left 0.3s ease, width 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-collapsed .content-wrapper {
            width: calc(100% - var(--bs-sidebar-collapsed-width));
            margin-left: var(--bs-sidebar-collapsed-width);
        }
        
        /* Main content area */
        .main-content {
            flex: 1;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        /* Navbar */
        .main-header {
            background-color: var(--bs-card-bg);
            border-bottom: 1px solid var(--bs-border-color);
            transition: all 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        
        /* Sidebar */
        .main-sidebar {
            width: var(--bs-sidebar-width);
            background-color: var(--bs-sidebar-bg);
            border-right: 1px solid var(--bs-border-color);
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 1030;
            transition: width 0.3s ease;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar-collapsed .main-sidebar {
            width: var(--bs-sidebar-collapsed-width);
        }
        
        .brand-link {
            display: flex;
            align-items: center;
            padding: 1.25rem 1rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--bs-navbar-color);
            text-decoration: none;
            border-bottom: 1px solid var(--bs-border-color);
        }
        
        .brand-logo {
            height: 32px;
            width: auto;
            margin-right: 0.75rem;
            filter: drop-shadow(0 0 2px rgba(var(--bs-primary-rgb), 0.5));
        }
        
        .sidebar-collapsed .brand-text,
        .sidebar-collapsed .nav-item-text {
            display: none;
        }
        
        .nav-sidebar {
            padding: 1rem 0;
        }
        
        .nav-sidebar .nav-item {
            margin-bottom: 0.25rem;
        }
        
        .nav-sidebar .nav-link {
            color: var(--bs-navbar-color);
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            border-radius: 0.25rem;
            margin: 0 0.5rem;
            transition: all 0.2s ease;
        }
        
        .nav-sidebar .nav-link:hover {
            background-color: var(--bs-sidebar-hover-bg);
        }
        
        .nav-sidebar .nav-link.active {
            background-color: var(--bs-sidebar-active-bg);
            color: var(--bs-navbar-active-color);
        }
        
        .nav-sidebar .nav-icon {
            margin-right: 0.75rem;
            width: 1.5rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .sidebar-collapsed .nav-sidebar .nav-link {
            padding: 0.75rem;
            justify-content: center;
        }
        
        .sidebar-collapsed .nav-sidebar .nav-icon {
            margin-right: 0;
            font-size: 1.1rem;
        }
        
        /* Footer */
        .main-footer {
            background-color: var(--bs-card-bg);
            border-top: 1px solid var(--bs-border-color);
            padding: 1rem;
            font-size: 0.875rem;
            color: var(--bs-body-color);
            transition: all 0.3s ease;
        }
        
        /* Cards */
        .card {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .card-header {
            background-color: var(--bs-card-cap-bg);
            border-bottom: 1px solid var(--bs-border-color);
            padding: 1rem;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-primary {
            background-color: var(--bs-primary);
            border-color: var(--bs-primary);
        }
        
        .btn-primary:hover, .btn-primary:focus {
            background-color: var(--bs-primary-hover);
            border-color: var(--bs-primary-hover);
        }
        
        /* Theme Toggle */
        .theme-toggle {
            cursor: pointer;
            padding: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .theme-toggle:hover {
            background-color: var(--bs-sidebar-hover-bg);
        }
        
        /* Responsive Fixes */
        @media (max-width: 991.98px) {
            .main-sidebar {
                transform: translateX(-100%);
                box-shadow: none;
            }
            
            .sidebar-open .main-sidebar {
                transform: translateX(0);
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            }
            
            .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-collapsed .content-wrapper {
                margin-left: 0;
                width: 100%;
            }
            
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1025;
                display: none;
            }
            
            .sidebar-open .sidebar-backdrop {
                display: block;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .fade-in {
            animation: fadeIn 0.3s ease;
        }
        
        /* Dashboard Cards */
        .stat-card {
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            border: none;
            height: 100%;
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-card-icon {
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stat-card-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-card-label {
            color: var(--bs-body-color);
            font-size: 0.875rem;
            opacity: 0.8;
        }
    </style>
    
    @stack('styles')
</head>
<body>
<div class="wrapper">
    <!-- Sidebar Backdrop (Mobile) -->
    <div class="sidebar-backdrop"></div>
    
    <!-- Sidebar -->
    <aside class="main-sidebar">
        <!-- Brand Logo -->
        <a href="{{ route('back.pages.index') }}" class="brand-link">
            <img src="{{ asset('images/sone.png') }}" alt="SoneAI Logo" class="brand-logo">
            <span class="brand-text">SoneAI Admin</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-sidebar flex-column">
                    <li class="nav-item">
                        <a href="{{ route('admin.user-stats.index') }}" class="nav-link {{ request()->routeIs('admin.user-stats.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-circle fa-fw"></i>
                            <span class="nav-item-text">Kullanıcı İstatistikleri</span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('admin.message-history.index') }}" class="nav-link {{ request()->routeIs('admin.message-history.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-comments fa-fw"></i>
                            <span class="nav-item-text">Mesaj Geçmişi</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="{{ route('admin.seo.index') }}" class="nav-link {{ request()->routeIs('admin.seo.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-search fa-fw"></i>
                            <span class="nav-item-text">SEO Ayarları</span>
                        </a>
                    </li>

                    <li class="nav-item mt-4">
                        <div class="nav-link theme-toggle" id="theme-toggle">
                            <i class="nav-icon fas fa-sun fa-fw" id="theme-icon"></i>
                            <span class="nav-item-text">Tema Değiştir</span>
                        </div>
                    </li>
                </ul>
            </nav>
        </div>
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" id="sidebar-toggle" href="#">
                        <i class="fas fa-bars"></i>
                    </a>
                </li>
            </ul>

            <!-- Right navbar links -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i> Admin
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.profile') }}"><i class="fas fa-user me-2"></i> Profil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('admin.logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="fas fa-sign-out-alt me-2"></i> Çıkış
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                        {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>

        <!-- Footer -->
    <footer class="main-footer">
            <div class="float-end d-none d-sm-inline">
                <b>Sürüm</b> 1.0.0
        </div>
        <strong>Copyright &copy; {{ date('Y') }} <a href="#">SoneAI</a>.</strong> Tüm hakları saklıdır.
    </footer>
</div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 5 Bundle with Popper.js -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<!-- Custom Script -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sidebar Toggle
        const sidebarToggle = document.getElementById('sidebar-toggle');
        const wrapper = document.querySelector('.wrapper');
        const sidebarBackdrop = document.querySelector('.sidebar-backdrop');
        
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (window.innerWidth < 992) {
                wrapper.classList.toggle('sidebar-open');
            } else {
                wrapper.classList.toggle('sidebar-collapsed');
            }
        });
        
        sidebarBackdrop.addEventListener('click', function() {
            wrapper.classList.remove('sidebar-open');
        });
        
        // Theme Toggle
        const themeToggle = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;
        
        // Check for saved theme preference or respect OS preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            htmlElement.setAttribute('data-bs-theme', savedTheme);
            updateThemeIcon(savedTheme);
        } else {
            // If no saved preference, check OS preference
            const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = prefersDarkMode ? 'dark' : 'light';
            htmlElement.setAttribute('data-bs-theme', theme);
            updateThemeIcon(theme);
        }
        
        themeToggle.addEventListener('click', function() {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        
        function updateThemeIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.remove('fa-moon');
                themeIcon.classList.add('fa-sun');
            } else {
                themeIcon.classList.remove('fa-sun');
                themeIcon.classList.add('fa-moon');
            }
        }
        
        // Initialize all tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>

@stack('scripts')
</body>
</html> 