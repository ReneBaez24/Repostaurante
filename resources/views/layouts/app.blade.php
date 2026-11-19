<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Punk Brothers - Restaurante</title>
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
                <li><a href="{{ route('overview') }}">Mesas</a></li>
                <li><a href="#">Sobre Nosotros</a></li>
                <li><a href="{{ route('login') }}">Iniciar Sesión</a></li>
            </ul>
        </nav>
        <div class="user-info">
            Usuario
            <span>Rene Baez</span>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>