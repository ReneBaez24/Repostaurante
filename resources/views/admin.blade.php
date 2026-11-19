@extends('layouts.app')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>PANEL DE ADMINISTRACIÓN</h2>
        <p style="margin-bottom: 20px;">Bienvenido, Administrador. Desde aquí podrás gestionar el sistema.</p>
        
        <div class="mesas-grid">
            <div class="mesa-card" style="text-align: center;">
                <h3>Usuarios</h3>
                <p>Gestionar roles y accesos</p>
            </div>
            <div class="mesa-card" style="text-align: center;">
                <h3>Menú</h3>
                <p>Agregar/Editar platillos</p>
            </div>
            <div class="mesa-card" style="text-align: center;">
                <h3>Inventario</h3>
                <p>Revisar stock de ingredientes</p>
            </div>
            <div class="mesa-card" style="text-align: center;">
                <h3>Reportes</h3>
                <p>Ventas del día</p>
            </div>
        </div>
    </div>
</div>
@endsection