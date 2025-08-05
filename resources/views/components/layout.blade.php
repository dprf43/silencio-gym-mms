<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Silencio System</title>

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
        
        <!-- Dropdown Styles -->
        <link rel="stylesheet" href="{{ asset('css/dropdown.css') }}">
        <!-- Sidebar Styles -->
        <link rel="stylesheet" href="{{ asset('css/sidebar.css') }}">
    </head>
    <body class="m-0 p-0 h-screen w-screen">
        <main class="h-full w-full flex">
            {{ $slot }}
        </main>
        
        <!-- Dropdown JavaScript -->
        <script src="{{ asset('js/dropdown.js') }}"></script>
        <!-- Sidebar JavaScript -->
        <script src="{{ asset('js/sidebar.js') }}"></script>
    </body>
</html>
