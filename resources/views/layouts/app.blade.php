<!doctype html>
<html @php(language_attributes())>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ get_template_directory_uri() }}/resources/images/favicon.ico">
        <link rel="apple-touch-icon" sizes="16x16" href="{{ get_template_directory_uri() }}/resources/images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ get_template_directory_uri() }}/resources/images/favicon-32x32.png">
    
    @php(do_action('get_header'))
    @php(wp_head())

    @stack('head')

    {!! $schemaJson !!}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body @php(body_class('antialiased font-serif min-h-screen flex flex-col bg-black'))>
    @php(wp_body_open())

    @include('sections.header')

    @include('partials.breadcrumbs')

    <main id="main" class="main grow">
        @yield('content')
    </main>

    @hasSection('sidebar')
        <aside class="sidebar">
            @yield('sidebar')
        </aside>
    @endif

    @include('sections.footer')

    @include('partials.floating-buttons')

    {{-- @include('partials.age-gate') --}}

    @php(do_action('get_footer'))
    @php(wp_footer())
</body>

</html>
