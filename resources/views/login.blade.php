@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="sidebar" style="width: 400px; padding: 40px;">
        <h2 style="text-align: center;">INICIAR SESIÓN</h2>
        <p style="text-align: center; font-size: 12px; margin-bottom: 30px;">Ingresa tus credenciales para acceder al sistema</p>
        
        <form action="#" method="POST">
            @csrf <!-- token de seguridad de Laravel  -->
            <label style="font-size: 12px;">Correo Electrónico</label>
            <input type="email" name="email" placeholder="ejemplo@punkbrothers.com" required>
            
            <label style="font-size: 12px;">Contraseña</label>
            <input type="password" name="password" placeholder="********" required>

            <button type="submit" class="btn" style="margin-top: 10px;">Entrar al sistema</button>
        </form>
    </div>
</div>
@endsection