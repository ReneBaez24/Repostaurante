@extends('layouts.app')

@section('title', 'Administración')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>PANEL DE ADMINISTRACIÓN</h2>
        <p style="margin-bottom: 20px;">Bienvenido, {{ auth()->user()->name }}. Desde aquí podrás gestionar el sistema.</p>

        <div class="mesas-grid" style="grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            <a href="{{ route('admin.usuarios.index') }}" class="mesa-card" style="text-align: center;">
                <h3>Usuarios</h3>
                <div class="stat">{{ $usuarios }}</div>
                <p>Gestionar roles y accesos</p>
            </a>
            <a href="{{ route('admin.productos.index') }}" class="mesa-card" style="text-align: center;">
                <h3>Menú</h3>
                <div class="stat">{{ $productos }}</div>
                <p>Agregar/Editar platillos</p>
            </a>
            <a href="{{ route('admin.mesas.index') }}" class="mesa-card" style="text-align: center;">
                <h3>Mesas</h3>
                <div class="stat">{{ $mesasAbiertas }}/{{ $mesas }}</div>
                <p>Abiertas ahora / total</p>
            </a>
            <a href="{{ route('admin.inventario.index') }}" class="mesa-card" style="text-align: center;">
                <h3>Inventario</h3>
                <div class="stat">{{ $ingredientes }}</div>
                <p>Ingredientes por código de barras</p>
            </a>
            <a href="{{ route('admin.reportes') }}" class="mesa-card" style="text-align: center;">
                <h3>Reportes</h3>
                <div class="stat">${{ number_format($ventasHoy, 2) }}</div>
                <p>Ventas del día · {{ $cuentasHoy }} cuentas</p>
            </a>
        </div>
    </div>
</div>
@endsection
