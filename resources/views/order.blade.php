@extends('layouts.app')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>SELECCIÓN DE PEDIDOS</h2>
        
        <input type="text" placeholder="🔍 Buscar pollo, guarnición o bebida...">
        
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn btn-outline" style="width: auto;">Todos</button>
            <button class="btn btn-outline" style="width: auto;">Ahumados</button>
            <button class="btn btn-outline" style="width: auto;">Asados</button>
            <button class="btn btn-outline" style="width: auto;">Bebidas</button>
        </div>

        <div class="productos-grid">
            <!-- producto -->
            <div class="producto-card">
                <h4>¼ pollo ahumado</h4>
                <p>Cuarto de pollo, humo de leña</p>
                <div class="producto-footer">
                    <span class="price">$12.00</span>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-outline" style="padding: 5px 10px; width: auto;">-</button>
                        <button class="btn btn-outline" style="padding: 5px 10px; width: auto;">+</button>
                    </div>
                </div>
            </div>
            
            <div class="producto-card">
                <h4>Limonada casera</h4>
                <p>Limón natural y hierbabuena</p>
                <div class="producto-footer">
                    <span class="price">$3.50</span>
                    <div style="display: flex; gap: 5px;">
                        <button class="btn btn-outline" style="padding: 5px 10px; width: auto;">-</button>
                        <button class="btn btn-outline" style="padding: 5px 10px; width: auto;">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel del Ticket -->
    <div class="sidebar">
        <h3>MESA 08 <span class="status" style="float:right; background:#FFF3CD; padding:2px 8px; border-radius:4px;">En servicio</span></h3>
        <p style="font-size: 12px; margin-bottom: 20px;">Pedido #024 • 2 comensales</p>
        
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">
            <p><strong>2</strong> ¼ pollo ahumado <span style="float:right;">$24.00</span></p>
        </div>
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">
            <p><strong>1</strong> ¼ pollo asado <span style="float:right;">$9.50</span></p>
        </div>
        <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 10px; margin-bottom: 10px;">
            <p><strong>2</strong> Limonada casera <span style="float:right;">$7.00</span></p>
        </div>

        <h3 style="margin-top: 20px;">6 unidades - Total de la cuenta <span style="float:right;">$46.50</span></h3>

        <button class="btn" style="margin-bottom: 10px;">Enviar a cocina • 1 nuevo</button>
        <!-- esta ruta deberia llevar a la vista de cobro -->
        <a href="{{ route('checkout') }}" class="btn btn-outline" style="display: block; text-align: center; text-decoration: none;">Ver cuenta</a>
    </div>
</div>
@endsection