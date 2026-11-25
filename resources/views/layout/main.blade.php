@include('layout.header')

<section class="topbar-container z-30">
    @include('layout.nav')
    @include('layout.sidebar')
</section>

<main class="main-content has-sidebar">
    @include('partials._alerts')
    @yield('content')
</main>

@include('layout.footer')
