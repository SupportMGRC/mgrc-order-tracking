<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">

<head>

    <meta charset="utf-8" />
    <title>MGRC Order Tracking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Order Management and Tracking System" name="description" />
    <meta content="MGRC" name="author" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- App favicon -->
    <link rel="shortcut icon" href="{{ asset('assets/images/mgrc/MGRC-logo-only.png') }}">


    {{-- Sidebar is static and expanded. The theme ships it at 250px, which is more
         width than these labels need; 210px is the single number to change if it
         wants adjusting. Desktop only - below 768px the sidebar is an overlay
         drawer and must keep the theme's own sizing. --}}
    <style>
        @media (min-width: 768px) {
            [data-layout="vertical"][data-sidebar-size="lg"] .navbar-menu {
                width: 210px;
            }
            [data-layout="vertical"][data-sidebar-size="lg"] .main-content {
                margin-left: 210px;
            }
            [data-layout="vertical"][data-sidebar-size="lg"] #page-topbar,
            [data-layout="vertical"][data-sidebar-size="lg"] .footer {
                left: 210px;
            }
            /* Tighter rows so the narrower panel does not feel cramped. */
            [data-layout="vertical"][data-sidebar-size="lg"] .navbar-menu .navbar-nav .nav-link {
                padding: 0.55rem 1.1rem;
            }
            /* Keep the wordmark inside the narrower panel. Constrain by height, not
               width: height:auto lets a wide logo scale up to fill the panel. */
            [data-layout="vertical"][data-sidebar-size="lg"] .navbar-brand-box {
                padding: 0 0.75rem;
            }
            [data-layout="vertical"][data-sidebar-size="lg"] .navbar-brand-box .logo-lg img {
                height: 44px;
                width: auto;
                max-width: 100%;
            }
        }

        /* Mobile drawer: show the full wordmark, not just the square mark. */
        @media (max-width: 767.98px) {
            .navbar-brand-box .logo-sm {
                display: none !important;
            }
            .navbar-brand-box .logo-lg {
                display: inline-block !important;
            }
            .navbar-brand-box .logo-lg img {
                height: 42px;
                width: auto;
                max-width: 100%;
            }
        }

        /* iOS Safari zooms the page in whenever a focused input is under 16px,
           and never zooms back out. The theme sets .form-control to 14px, so
           tapping any field left the whole app zoomed. 16px on touch widths
           only; desktop keeps the theme's sizing. */
        @media (max-width: 767.98px) {
            .form-control,
            .form-select,
            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="number"],
            input[type="search"],
            input[type="tel"],
            input[type="date"],
            input[type="time"],
            textarea,
            select {
                font-size: 16px;
            }
        }
    </style>

    <!-- Layout config Js -->
    <script src="{{ asset('assets/js/layout.js') }}"></script>

    <!-- Bootstrap Css -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="{{ asset('assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- custom Css-->
    <link href="{{ asset('assets/css/custom.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- fullcalendar css -->
    <link href="{{ asset('assets/libs/fullcalendar/main.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- Sweet Alert css-->
    <link href="{{ asset('assets/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
    <!-- multi.js css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/libs/multi.js/multi.min.css') }}" />
    <!-- autocomplete css -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css') }}">
    <!-- dropzone css -->
    <link rel="stylesheet" href="{{ asset('assets/libs/dropzone/dropzone.css') }}" type="text/css" />
    <!-- One of the following themes -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@simonwep/pickr/themes/classic.min.css') }}" /> <!-- 'classic' theme -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@simonwep/pickr/themes/monolith.min.css') }}" /> <!-- 'monolith' theme -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@simonwep/pickr/themes/nano.min.css') }}" /> <!-- 'nano' theme -->
    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    {{-- multi.js and autoComplete stylesheets were listed a second time here. --}}
    
    <!-- Toastify CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    
    @stack('head-scripts')
</head>

<body>

    <!-- Begin page -->
    <div id="layout-wrapper">

        @include("components.topbar")
        @include("components.sidebar")

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="main-content">

            <div class="page-content">
                <div class="container-fluid">

                    @yield('content')

                </div>
                <!-- container-fluid -->
            </div>
            <!-- End Page-content -->

            @include("components.footer")
        </div>
        <!-- end main content-->

    </div>
    <!-- END layout-wrapper -->



    @include("components.customizer")

    <!-- JAVASCRIPT -->
    <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
    <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
    <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/plugins/lord-icon-2.1.0.js') }}"></script>
    <script src="{{ asset('assets/js/plugins.js') }}"></script>
    <!-- calendar min js -->
    <script src="{{ asset('assets/libs/fullcalendar/main.min.js') }}"></script>
    {{-- calendar.init.js removed: it is the theme's demo calendar page script.
         It looks for #calendar and demo buttons (btn-new-event, event-category,
         edit-event-btn) that exist in no view, and called flatpickr() on a null
         element - the "Cannot convert undefined or null to object" error. The
         dashboard builds its own calendar on #delivery-calendar; fullcalendar
         itself is still loaded above. --}}
    <!-- prismjs plugin -->
    <script src="{{ asset('assets/libs/prismjs/prism.js') }}"></script>
    <script src="{{ asset('assets/libs/list.js/list.min.js') }}"></script>
    <script src="{{ asset('assets/libs/list.pagination.js/list.pagination.min.js') }}"></script>
    <!-- Sweet Alerts js -->
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- multi.js -->
    <script src="{{ asset('assets/libs/multi.js/multi.min.js') }}"></script>
    <!-- autocomplete js -->
    <script src="{{ asset('assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js') }}"></script>
    <!-- dropzone min -->
    <script src="{{ asset('assets/libs/dropzone/dropzone-min.js') }}"></script>
    <!-- cleave.js -->
    <script src="{{ asset('assets/libs/cleave.js/cleave.min.js') }}"></script>
    <!-- team init js -->
    {{-- <script src="{{ asset('assets/js/pages/team.init.js') }}"></script> --}}
    <!-- Modern colorpicker bundle -->
    <script src="{{ asset('assets/libs/@simonwep/pickr/pickr.min.js') }}"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    {{-- multi.js, autoComplete, form-advanced.init, form-input-spin.init and
         flag-input.init were listed a second time here. Loading them twice ran
         every initialiser twice and produced duplicate console errors. The first
         copies above are the ones in use. --}}

    {{-- Theme demo initialisers removed: listjs, ecommerce-order,
         ecommerce-product-checkout, form-advanced, invoicecreate,
         form-input-spin, flag-input and form-pickers. Each scanned for demo
         markup TRACOM does not have (.country-flagimg, .classic-colorpicker,
         autocomplete fields) and threw on load. The libraries themselves are
         kept: plugins.js and app.js initialise [data-choices] and
         [data-provider="flatpickr"], and orderhistory uses list.js directly. --}}

    <!-- ApexCharts js -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <!-- Chart JS -->
    <script src="{{ asset('assets/js/pages/dashboard-analytics.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    {{-- Idle lock for every page. Must come after app.js so bootstrap is defined. --}}
    @include("components.security-lock")

    
    @yield('script')

    <!-- Add Toastify JS before the closing body tag -->
    @push('footer-scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    @endpush
</body>

</html>