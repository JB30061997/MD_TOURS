<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    @include('webcontent.head')
    <body class="font-sans antialiased">
        @inertia
        @include('webcontent.script')
    </body>
</html>