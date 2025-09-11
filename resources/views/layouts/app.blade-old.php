<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- <meta name="sso-token" content=""> -->
    <title>{{ config('app.name', 'Laravel') }}</title>
    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link href="https://cdn.datatables.net/1.10.25/css/jquery.dataTables.min.css" rel="stylesheet">

    <link href="{{ asset('plugins/sweet-alert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css">
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css"
        integrity="sha512-vKMx8UnXk60zUwyUnUPM3HbQo8QfmNx7+ltw8Pm5zLusl1XIfwcxo8DbWCqMGKaWeNxWA8yrx5v3SaVpMvR3CA=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script src="{{ asset('plugins/sweet-alert2/sweetalert2.min.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.7/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <link href="{{ asset('admin/assets/dashboard/css/style.css') }}" rel="stylesheet">

    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <link href="{{ asset('assets/css/common.css') }}" rel="stylesheet">
    <style>
        /* body{
    overflow-y: hidden;
} */
        /* .file-manager-content-body.ps {
    max-height: 750px;

} */

        .table {
            width: 100% !important;
        }
    </style>

    {{--  @vite(['resources/css/app.css', 'resources/js/app.js']) --}}

    @stack('style')
</head>

<body>
    <div id="app" class="position-relative">
        @php($authUser = loginUser())
        @php($hasContactId = $contactId ?? null)

        @if (@$authUser)
            @if (!$hasContactId)
                <nav class="navbar main_nav navbar-expand-md w-100 navbar-light bg-white shadow-sm">
                    {{-- position-absolute --}}
                    <div class="container m-0">
                        <a class="navbar-brand" href="{{ url('/') }}">
                            {{ config('app.name', 'Laravel') }}
                        </a>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                            aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                            <span class="navbar-toggler-icon"></span>
                        </button>

                        <div class="navbar-collapse" id="navbarSupportedContent">
                            <!-- Left Side Of Navbar -->
                            <ul class="navbar-nav me-auto">

                                @include('menus.' . (isAdmin() ? 'admin' : 'location'))
                            </ul>
                            <!-- Right Side Of Navbar -->
                            <ul class="navbar-nav ms-auto">
                                <!-- Authentication Links -->
                                @guest
                                    @if (Route::has('login'))
                                        <li class="nav-item">
                                            <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                        </li>
                                    @endif
                                @else
                                    @if (isAdmin())
                                        <li class="nav-item dropdown">
                                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#"
                                                role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                                aria-expanded="false">
                                                {{ Auth::user()->name }}
                                            </a>

                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                                <li>
                                                    <a class="dropdown-item" href="{{ route('logout') }}">

                                                        {{ __('Logout') }}
                                                    </a>
                                                </li>
                                            </ul>

                                            <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                                class="d-none">
                                                @csrf
                                            </form>
                                        </li>
                                    @endif
                                @endguest
                            </ul>
                        </div>
                    </div>
                </nav>
            @endif
        @endif

        <main class=" main-div ptc-10 pbc-50 mt-4">
            @yield('content')
        </main>
    </div>

</body>


<script src="{{ asset('assets/js/ajaxHandler.js') }}"></script>

@stack('script')
<script>
    @if (session('message'))
        toastr.success("{{ session('message') }}");
    @elseif (session('error'))
        toastr.error("{{ session('error') }}");
    @endif
</script>
<script src="{{ asset('admin/assets/dashboard/js/select2.min.js') }}"></script>
<link rel="stylesheet" type="text/css"
    href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.min.css">

<script type="text/javascript"
    src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>

<script>
    function PopFullWindow(url, title = '') {
        var w = screen.availWidth;
        var h = screen.availHeight;
        var left = 0;
        var top = 0;
        var myWindow = window.open(url, title,
            "fullscreen=no, toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=" +
            w + ", height=" + h + ", top=" + top + ", left=" + left);
        return myWindow
    }
    $(document).ready(function() {

        @stack('ready_script')
        // if (window.parent == window.self) {
        //     $('.contain-class').addClass('container')
        //     $('.main_nav').removeClass('d-none');
        // } else {
        //     $('.contain-class').removeClass('container')
        //     $('.main_nav').addClass('d-none');
        // }
    })

    function dispMessage(isError, message, timeout = 10000) {
        try {
            if (isError) {
                toastr.error(message, {
                    timeOut: timeout
                });
            } else {
                toastr.success(message, {
                    timeOut: timeout
                });
            }

        } catch (error) {
            alert(message);
        }
    }
</script>

</html>
