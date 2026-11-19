@extends('layouts.app')

@section('content')
<div class="container">
    <div class="main-content">
        <h2>SECCIÓN DE COBRO</h2>
        
        <div class="sidebar" style="flex: 1; width: 100%;">
            <h3>DETALLE DE LA CUENTA <span style="float:right; color: #888;">#024</span></h3>
            <p style="font-size: 12px; margin-bottom: 20px;">Mesa 08 • 2 comensales • Abierta a las 14:05</p>

            <table style="width: 100%; font-size: 14px; border-collapse: collapse;">
                <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 10px 0;">PRODUCTO</th>
                    <th>CANT.</th>
                    <th>PRECIO</th>
                    <th>IMPORTE</th>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">¼ pollo ahumado</td>
                    <td>2</td>
                    <td>$12.00</td>
                    <td>$24.00</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">¼ pollo asado</td>
                    <td>1</td>
                    <td>$9.50</td>
                    <td>$9.50</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">Limonada casera</td>
                    <td>2</td>
                    <td>$3.50</td>
                    <td>$7.00</td>
                </tr>
                <tr>
                    <td style="padding: 10px 0;">Papas rústicas</td>
                    <td>1</td>
                    <td>$6.00</td>
                    <td>$6.00</td>
                </tr>
            </table>

            <div style="margin-top: 20px; border-top: 1px solid var(--border-color); padding-top: 10px;">
                <p style="font-size: 14px; margin-bottom: 5px;">Subtotal <span style="float:right;">$46.50</span></p>
                <p style="font-size: 14px; margin-bottom: 5px;">Impuesto incluido (10%) <span style="float:right;">$4.23</span></p>
                <h3 style="margin-top: 15px;">TOTAL DEL CONSUMO <span style="float:right;">$46.50</span></h3>
            </div>
        </div>
    </div>

    <!-- Panel de Pago -->
    <div class="sidebar">
        <h3>COBRAR Y CERRAR</h3>
        <p style="font-size: 12px; margin-bottom: 20px;">Selecciona el método de pago.</p>
        
        <p>Método de pago</p>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn btn-outline" style="background: #F5EAD1;">💳 Tarjeta</button>
            <button class="btn btn-outline">💵 Efectivo</button>
        </div>

        <p>Propina opcional</p>
        <div style="display: flex; gap: 10px; margin-bottom: 20px;">
            <button class="btn btn-outline">Sin propina</button>
            <button class="btn btn-outline">5 %</button>
            <button class="btn btn-outline" style="background: #F5EAD1;">10 %</button>
        </div>

        <div style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-bottom: 20px;">
            <p style="font-size: 14px;">Consumo <span style="float:right;">$46.50</span></p>
            <p style="font-size: 14px;">Propina (10 %) <span style="float:right;">$4.65</span></p>
            <h2 style="margin-top: 10px;">TOTAL A COBRAR <span style="float:right;">$51.15</span></h2>
        </div>

        <label style="font-size: 12px; display: block; margin-bottom: 20px;">
            <input type="checkbox" checked> Imprimir comprobante al cerrar
        </label>

        <!-- Simula cierre de mesa -->
        <a href="{{ route('overview') }}" class="btn" style="display: block; text-align: center; text-decoration: none;">Cobrar $51.15 y cerrar mesa</a>
    </div>
</div>
@endsection