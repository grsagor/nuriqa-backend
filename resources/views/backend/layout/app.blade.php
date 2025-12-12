<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Nuriqa</title>
    <link href="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.5/datatables.min.css" rel="stylesheet"
        integrity="sha384-lw6xqXSsLbv9Bk8p8sb0Eoqxc5YTKvHwiXBG3EDVIqCaj4r4rCCnDDHY4As+neWR" crossorigin="anonymous">

    <link href="{{ asset('assets/css/styles.css') }}" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    @stack('styles');
</head>

<body class="sb-nav-fixed">
    @include('backend.layout.nav')
    <div id="layoutSidenav">
        @include('backend.layout.sidebar')
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4 mt-4">
                    @yield('content')
                </div>
            </main>
            @include('backend.layout.footer')
        </div>
    </div>


    <div class="modal fade" id="crudModal" tabindex="-1" aria-labelledby="crudModalLabel" aria-hidden="true"></div>


    <script src="{{ asset('vendors/jquery/jquery-3.7.1.min.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
    </script>
    <script src="{{ asset('assets/js/scripts.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.8.0/Chart.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/v/dt/jq-3.7.0/dt-2.3.5/datatables.min.js"
        integrity="sha384-GnTdsJpz17/btlSP5bFmeEv1QtW4NUnE9Atd4eMZjHBvJvYTrQTmTzLAKL2wlBEt" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    {{-- <script src="{{ asset('assets/js/datatables-simple-demo.js') }}"></script> --}}
    <script src="{{ asset('assets/js/crud.js') }}"></script>

    @stack('scripts');
</body>

</html>
