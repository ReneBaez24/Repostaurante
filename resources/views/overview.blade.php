@extends('layouts.app')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>VISTA GENERAL</h2>
        <div class="mesas-grid">
            <!-- ejemplo de mesa libre -->
            <div class="mesa-card">
                <span class="status">Libre</span>
                <p>MESA</p>
                <div class="number">01</div>
                <div class="details">4 Sillas</div>
            </div>
            <!-- ejemplo de mesa ocupada -->
            <div class="mesa-card" style="background-color: #E8D5B5;">
                <span class="status" style="background: #FCE4EC; color: #C2185B;">Ocupada</span>
                <p>MESA</p>
                <div class="number">02</div>
                <div class="details">2 comensales • 18 min</div>
            </div>
            <!-- ejemplo de mesa esperando orden -->
            <div class="mesa-card" style="border: 2px solid #FFC107;">
                <span class="status" style="background: #FFF3CD; color: #856404;">Esperando Orden</span>
                <p>MESA</p>
                <div class="number">04</div>
                <div class="details">4 sillas • 12 min</div>
            </div>
            <!-- aquie se agregan mas mesas-->
        </div>
    </div>

    <!-- panel lateral de la mesa -->
    <div class="sidebar">
        <h3>MESA SELECCIONADA <span class="status" style="float:right; background:#D4EDDA; padding:2px 8px; border-radius:4px;">Libre</span></h3>
        <h2>MESA 08</h2>
        <p style="font-size: 12px; margin-bottom: 20px;">Interior • Capacidad para 4 personas</p>
        
        <p>¿Cuántos comensales?</p>
        <div style="display: flex; gap: 10px; margin: 10px 0 20px 0;">
            <button class="btn btn-outline" style="width: 40px;">-</button>
            <input type="text" value="2" style="text-align: center; margin: 0;">
            <button class="btn btn-outline" style="width: 40px;">+</button>
        </div>

        <p>Zona</p>
        <select>
            <option>Salón interior</option>
            <option>Terraza</option>
        </select>

        <!-- esta ruta deberia llevar a la vista de pedidos -->
        <a href="{{ route('order') }}" class="btn" style="display: block; text-align: center; text-decoration: none; margin-top: 20px;">Abrir mesa y agregar pedido →</a>
    </div>
</div>
@endsection