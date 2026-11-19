<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Restaurante') · Punk Brothers</title>
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
</head>
<body>
    <header>
        <div class="logo-container">
            <!-- AQUI VA EL LOGO PERO EL FOKIN WASAP LE QUITO LA TRANSPARENCIA -->
            <!-- alrato lo arreglo -->
            <img src="{{ asset('img/logo.png') }}" alt="Logo" style="height: 40px; display: none;">
            <h1>PUNK BROTHERS</h1>
            <p>LOS MEJORES POLLOS AL VAPOR</p>
        </div>
        <nav>
            <ul>
                @auth
                    <li><a href="{{ route('overview') }}" @class(['active' => request()->routeIs('overview', 'order', 'checkout')])>Mesas</a></li>
                    <li><a href="{{ route('cocina') }}" @class(['active' => request()->routeIs('cocina')])>Cocina</a></li>
                    @if (auth()->user()->esAdmin())
                        <li><a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.*')])>Admin</a></li>
                    @endif
                @endauth
                <li><a href="#">Sobre Nosotros</a></li>
                @guest
                    <li><a href="{{ route('login') }}">Iniciar Sesión</a></li>
                @endguest
            </ul>
        </nav>
        @auth
            <div class="user-info">
                {{ auth()->user()->role->etiqueta() }}
                <span>{{ auth()->user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="logout-form">
                    @csrf
                    <button type="submit">Cerrar sesión</button>
                </form>
            </div>
        @endauth
    </header>

    <main>
        @if (session('ok'))
            <div class="alert alert-ok">{{ session('ok') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-error">{{ session('error') }}</div>
        @endif
        @if ($errors->any() && ! request()->routeIs('login'))
            <div class="alert alert-error">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
