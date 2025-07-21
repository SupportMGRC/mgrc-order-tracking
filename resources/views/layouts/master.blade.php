<!doctype html>
<html lang="en" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="sm-hover" data-sidebar-image="none" data-preloader="disable">

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
    <!-- multi.js css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/libs/multi.js/multi.min.css') }}" />
    <!-- autocomplete css -->
    <link rel="stylesheet" href="{{ asset('assets/libs/@tarekraafat/autocomplete.js/css/autoComplete.css') }}"> 
    
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
    <!-- Calendar init -->
    <script src="{{ asset('assets/js/pages/calendar.init.js') }}"></script>
    <!-- prismjs plugin -->
    <script src="{{ asset('assets/libs/prismjs/prism.js') }}"></script>
    <script src="{{ asset('assets/libs/list.js/list.min.js') }}"></script>
    <script src="{{ asset('assets/libs/list.pagination.js/list.pagination.min.js') }}"></script>
    <!-- listjs init -->
    <script src="{{ asset('assets/js/pages/listjs.init.js') }}"></script>
    <!-- Sweet Alerts js -->
    <script src="{{ asset('assets/libs/sweetalert2/sweetalert2.min.js') }}"></script>
    <!-- ecommerce-order init js -->
    <script src="{{ asset('assets/js/pages/ecommerce-order.init.js') }}"></script>
    <!-- init js -->
    <script src="{{ asset('assets/js/pages/ecommerce-product-checkout.init.js') }}"></script>
    <!-- multi.js -->
    <script src="{{ asset('assets/libs/multi.js/multi.min.js') }}"></script>
    <!-- autocomplete js -->
    <script src="{{ asset('assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js') }}"></script>
    <!-- init js -->
    <script src="{{ asset('assets/js/pages/form-advanced.init.js') }}"></script>
    <!-- input spin init -->
    <script src="{{ asset('assets/js/pages/form-input-spin.init.js') }}"></script>
    <!-- input flag init -->
    <script src="{{ asset('assets/js/pages/flag-input.init.js') }}"></script>
    <!-- dropzone min -->
    <script src="{{ asset('assets/libs/dropzone/dropzone-min.js') }}"></script>
    <!-- cleave.js -->
    <script src="{{ asset('assets/libs/cleave.js/cleave.min.js') }}"></script>
    <!--Invoice create init js-->
    <script src="{{ asset('assets/js/pages/invoicecreate.init.js') }}"></script>
    <!-- team init js -->
    {{-- <script src="{{ asset('assets/js/pages/team.init.js') }}"></script> --}}
    <!-- Modern colorpicker bundle -->
    <script src="{{ asset('assets/libs/@simonwep/pickr/pickr.min.js') }}"></script>
    <!-- init js -->
    <script src="{{ asset('assets/js/pages/form-pickers.init.js') }}"></script>
    <!-- Flatpickr -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- multi.js -->
    <script src="{{ asset('assets/libs/multi.js/multi.min.js') }}"></script>
    <!-- autocomplete js -->
    <script src="{{ asset('assets/libs/@tarekraafat/autocomplete.js/autoComplete.min.js') }}"></script>

    <!-- init js -->
    <script src="{{ asset('assets/js/pages/form-advanced.init.js') }}"></script>
    <!-- input spin init -->
    <script src="{{ asset('assets/js/pages/form-input-spin.init.js') }}"></script>
    <!-- input flag init -->
    <script src="{{ asset('assets/js/pages/flag-input.init.js') }}"></script>

    <!-- ApexCharts js -->
    <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
    <!-- Chart JS -->
    <script src="{{ asset('assets/js/pages/dashboard-analytics.init.js') }}"></script>

    <!-- App js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>
    
    @yield('script')

    <!-- Add Toastify JS before the closing body tag -->
    @push('footer-scripts')
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    @endpush
</body>

</html>