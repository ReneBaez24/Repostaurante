@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
<div style="display: flex; justify-content: center; align-items: center; min-height: 80vh;">
    <div class="sidebar" style="width: 400px; padding: 40px;">
        <h2 style="text-align: center;">INICIAR SESIÓN</h2>
        <p style="text-align: center; font-size: 12px; margin-bottom: 30px;">Ingresa tus credenciales para acceder al sistema</p>

        <form action="{{ route('login.attempt') }}" method="POST">
            @csrf <!-- token de seguridad de Laravel  -->
            <label style="font-size: 12px;">Correo Electrónico</label>
            <input type="email" name="email" value="{{ old('email') }}" placeholder="ejemplo@punkbrothers.com" required autofocus>
            @error('email') <p class="field-error">{{ $message }}</p> @enderror

            <label style="font-size: 12px;">Contraseña</label>
            <input type="password" name="password" placeholder="********" required>
            @error('password') <p class="field-error">{{ $message }}</p> @enderror

            <label class="check"><input type="checkbox" name="recordar" value="1"> Mantener sesión iniciada</label>

            <button type="submit" class="btn" style="margin-top: 10px;">Entrar al sistema</button>
        </form>
    </div>
</div>
@endsection
