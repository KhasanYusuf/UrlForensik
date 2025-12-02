<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Digital Forensik System')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">

    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.1);
        }
        .card {
            box-shadow: 0 0 10px rgba(0,0,0,.05);
            border: none;
        }
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        .dropdown-item i {
            width: 20px;
        }
        .dataTables_wrapper .dataTables_length select {
            padding: 0.375rem 2.25rem 0.375rem 0.75rem;
        }
        /* Allow dropdowns to overflow the responsive table wrapper */
        .table-responsive { overflow: visible !important; }
        /* Ensure table cells don't clip absolutely-positioned children */
        .table td, .table th { overflow: visible; }
        /* Make dropdown menus appear above other UI layers */
        .dataTables_wrapper .dropdown-menu,
        .dropdown-menu { z-index: 3000; }
        /* If using Bootstrap 5, make static dropdown display work when placed inside overflow containers */
        .dropdown[data-bs-display="static"] .dropdown-menu { position: absolute; }
    </style>

    @stack('styles')
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-shield-alt me-2"></i>Digital Forensik System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>{{ Auth::user()->name }}
                            <span class="badge bg-light text-dark ms-1">{{ ucfirst(Auth::user()->role) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li>
                                <a class="dropdown-item" href="#">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar & Content -->
    <div class="container-fluid mt-3">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-white sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('monitored_sites*') ? 'active' : '' }}" href="{{ route('monitored_sites.index') }}">
                                <i class="fas fa-globe me-2"></i>Monitored Sites
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('kasus*') ? 'active' : '' }}" href="{{ route('kasus.index') }}">
                                <i class="fas fa-folder-open me-2"></i>Data Kasus
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('bukti-digital*') ? 'active' : '' }}" href="{{ route('bukti_digital.index') }}">
                                <i class="fas fa-file-alt me-2"></i>Bukti Digital
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('tindakan-forensik*') ? 'active' : '' }}" href="{{ route('tindakan_forensik.index') }}">
                                <i class="fas fa-clipboard-check me-2"></i>Tindakan Forensik
                            </a>
                        </li>
                        @if(Auth::user()->role === 'admin')
                        <li class="nav-item">
                            <hr class="my-2">
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('users*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                                <i class="fas fa-user-shield me-2"></i>Manajemen User
                            </a>
                        </li>
                        @endif
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Render any stacked modals from views -->
    @stack('modals')

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Setup AJAX CSRF Token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Toast Helper Function
        function showToast(icon, title) {
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                didOpen: (toast) => {
                    toast.addEventListener('mouseenter', Swal.stopTimer)
                    toast.addEventListener('mouseleave', Swal.resumeTimer)
                }
            });

            Toast.fire({
                icon: icon,
                title: title
            });
        }
    </script>

    @stack('scripts')
</body>
</html>
