<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Auto-Billing Commission System')</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        body {
            background-color: var(--light-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            width: inherit;
            /* Sidebar scrolls if content exceeds viewport */
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 11050;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .main-content {
            padding: 30px;
            margin-left: 0;
            /* Default for small screens */
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 12px 12px 0 0 !important;
            padding: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
        }

        .loading-spinner {
            display: none;
        }

        .loading .loading-spinner {
            display: inline-block;
        }

        .loading .loading-text {
            display: none;
        }

        .select2-container--default .select2-selection--single {
            height: 45px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 28px;
            color: #374151;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-connected {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-disconnected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .metric-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            border-left: 4px solid var(--primary-color);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .metric-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }

        /*
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -250px;
                width: 250px;
                z-index: 1000;
                transition: left 0.3s ease;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }
        }
*/


        /* Sidebar close button */
        .sidebar .close-btn {
            display: none;
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            font-size: 24px;
            color: #fff;
            z-index: 1100;
        }


        /* Small screens — hide sidebar by default */
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                transition: all 0.3s ease;
                width: 250px;
            }

            .sidebar.active {
                left: 0;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 20px 15px;
            }

            .sidebar .close-btn {
                display: block;
            }
        }
    </style>

    @stack('styles')
</head>

<body>
    @php($authUser = loginUser())
    @php($isAdmin = $authUser ? $authUser->role == 1 : false)

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            @if ($isAdmin)
                <div class="col-md-3 col-lg-2 px-0">
                    <div class="sidebar">
                        <button class="close-btn" id="sidebarCloseBtn">&times;</button>
                        <div class="p-4">
                            <h4 class="text-white mb-4">
                                <i class="fas fa-chart-line me-2"></i>
                                {{ config('app.name', 'Laravel') }}
                            </h4>
                        </div>

                        <nav class="nav flex-column px-3">
                            {{--
                            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                                href="{{ route('admin.dashboard') }}">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        --}}
                            <a class="nav-link {{ request()->routeIs('admin.profile.edit') ? 'active' : '' }}"
                                href="{{ route('admin.profile.edit') }}">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.subaccounts') ? 'active' : '' }}"
                                href="{{ route('admin.subaccounts') }}">
                                <i class="fas fa-building me-2"></i>
                                Subaccounts
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.plan-mappings.index') ? 'active' : '' }}"
                                href="{{ route('admin.plan-mappings.index') }}">
                                <i class="fas fa-layer-group me-2"></i>
                                Plan Mappings
                            </a>
                            <a class="nav-link {{ request()->routeIs('admin.transactions') ? 'active' : '' }}"
                                href="{{ route('admin.transactions') }}">
                                <i class="fas fa-credit-card me-2"></i>
                                Transactions
                            </a>
                            {{--
                            <a class="nav-link {{ request()->routeIs('admin.orders') ? 'active' : '' }}"
                                href="{{ route('admin.orders') }}">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders
                            </a>
                        --}}
                            <a class="nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}"
                                href="{{ route('admin.settings') }}">
                                <i class="fas fa-cog me-2"></i>
                                Settings
                            </a>

                            <a href="javascript:void(0)" class="nav-link" id="logoutBtn">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                Logout
                            </a>

                        </nav>
                    </div>
                </div>
            @endif

            <!-- Main Content -->
            <div class="{{ $isAdmin ? 'col-md-9 col-lg-10' : 'col-md-12 col-lg-12' }}">
                <div class="main-content">
                    @if ($isAdmin)
                        <div class="d-md-none mb-3">
                            <button id="sidebarToggle" class="btn btn-primary">
                                <i class="fas fa-bars"></i> Menu
                            </button>
                        </div>
                    @endif

                    @yield('content')


                    <!-- Hidden logout form -->
                    <form id="logoutForm" action="{{ route('logout') }}" method="GET" style="display:none;">
                        @csrf
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Global AJAX setup
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Toastr configuration
        toastr.options = {
            "closeButton": true,
            "debug": false,
            "newestOnTop": true,
            "progressBar": true,
            "positionClass": "toast-top-right",
            "preventDuplicates": false,
            "onclick": null,
            "showDuration": "300",
            "hideDuration": "1000",
            "timeOut": "5000",
            "extendedTimeOut": "1000",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        };

        // Global loading state management
        function showLoading(element) {
            $(element).addClass('loading').prop('disabled', true);
        }

        function hideLoading(element) {
            $(element).removeClass('loading').prop('disabled', false);
        }

        // Global error handler
        function handleAjaxError(xhr, status, error) {
            let message = 'An error occurred. Please try again.';

            if (xhr.responseJSON && xhr.responseJSON.message) {
                message = xhr.responseJSON.message;
            }

            toastr.error(message);
        }

        $(document).ready(function() {
            $('#sidebarToggle, .close-btn').on('click', function() {
                $('.sidebar').toggleClass('active');
            });
        });


        document.getElementById('logoutBtn').addEventListener('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You will be logged out of the system.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
