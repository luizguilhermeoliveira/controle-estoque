<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Controle de Estoque')</title>

    {{-- Bootstrap 5 --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
        crossorigin="anonymous">

    {{-- Bootstrap Icons --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet">

    {{-- DataTables (integração Bootstrap 5) --}}
    <link
        href="https://cdn.datatables.net/2.0.8/css/dataTables.bootstrap5.min.css"
        rel="stylesheet">

    @stack('styles')
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    @include('partials.navbar')

    <main class="container py-4 flex-grow-1">
        @include('partials.alerts')

        @yield('conteudo')
    </main>

    <footer class="bg-white border-top py-3 mt-auto">
        <div class="container text-center text-muted small">
            Controle de Estoque &middot; {{ now()->year }}
        </div>
    </footer>

    {{-- jQuery (requisito do DataTables) --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" crossorigin="anonymous"></script>

    {{-- Bootstrap 5 bundle (inclui Popper) --}}
    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>

    {{-- DataTables --}}
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.bootstrap5.min.js"></script>

    {{-- SweetAlert2 --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @stack('scripts')
</body>
</html>
