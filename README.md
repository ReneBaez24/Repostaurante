# Punk Brothers · Sistema de restaurante

Sistema en Laravel para tomar pedidos por mesa, mandarlos a cocina y cobrar.

## Flujo

1. **Mesas** (`/`): vista general. Eliges una mesa libre, número de comensales y zona → *Abrir mesa y agregar pedido*.
2. **Selección de pedidos** (`/pedidos/{id}`): `+` / `−` en cada producto, búsqueda y filtro por categoría. Los productos nuevos quedan *Sin enviar* hasta pulsar **Enviar a cocina**.
3. **Cocina** (`/cocina`): ve las comandas *En preparación* y las marca como *Preparado*.
4. **Cobro** (`/pedidos/{id}/cobro`): método de pago y propina (0/5/10 %). Después de cobrar, la mesa se libera desde la vista general con **Liberar mesa**.

Estado de cada mesa (se calcula, no se guarda):
- **Libre**: sin pedido.
- **Abierta**: mesa abierta, todavía no se envía nada a cocina.
- **Esperando orden**: hay productos en preparación en cocina.
- **Ocupada**: cocina ya entregó lo que se envió. **Sigue ocupada después de cobrar**, hasta que el mesero pulsa **Liberar mesa** en la vista general.

En la pantalla de pedido, **Cancelar pedido** descarta el pedido y deja la mesa libre (sólo si no se ha enviado nada a cocina).

## Roles

| Rol | Puede |
|---|---|
| `admin` | Todo lo anterior + panel `/admin`: usuarios y roles, menú, mesas y reporte de ventas del día |
| `usuario` | Mesas, pedidos, cocina y cobro |

Las contraseñas se guardan cifradas con bcrypt (cast `hashed` del modelo `User`).
El login tiene un límite de 5 intentos por minuto.

## Instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Usuarios de prueba (creados por `UsuarioSeeder`). **Cámbialos en producción**:

| Correo | Contraseña | Rol |
|---|---|---|
| admin@punkbrothers.com | admin123 | admin |
| rene@punkbrothers.com | mesero123 | usuario |

Para MySQL, cambia en `.env`: `DB_CONNECTION=mysql`, `DB_DATABASE=punkbrothers`, `DB_USERNAME`, `DB_PASSWORD`.

## Base de datos

```
users ──< pedidos >── mesas
  │          │
  │          ├──< pedido_items >── productos >── categorias
  │          │
  └────────< pagos (1 por pedido)
```

| Tabla | Campos principales |
|---|---|
| `users` | name, email (único), password (bcrypt), **role** (`admin` / `usuario`) |
| `mesas` | numero (único), capacidad, zona (`interior` / `terraza`), activa |
| `categorias` | nombre, slug, orden |
| `productos` | categoria_id, nombre, descripcion, precio, disponible |
| `pedidos` | mesa_id, user_id (quién abrió), comensales, estado (`abierto` / `pagado` / `cancelado`), abierto_at, cerrado_at, liberada_at (cuándo se liberó la mesa) |
| `pedido_items` | pedido_id, producto_id, cantidad, precio_unitario (copia del precio al pedir), notas, estado (`sin_enviar` / `en_preparacion` / `preparado`), enviado_at, preparado_at |
| `pagos` | pedido_id (único), user_id (quién cobró), metodo (`tarjeta` / `efectivo`), consumo, impuesto, propina_porcentaje, propina, total |

Los precios del menú **incluyen** impuesto (10 %, configurable en `config/restaurante.php` o con `RESTAURANTE_IMPUESTO` en `.env`).

## Reglas importantes

- Una mesa sólo puede tener un pedido abierto (se bloquea la fila al abrirla).
- No se pueden abrir más comensales que sillas.
- Lo que ya se envió a cocina no se puede quitar del pedido.
- No se puede cobrar con productos sin enviar, ni cobrar dos veces el mismo pedido.
- Sólo se puede liberar una mesa ya cobrada, y no se puede volver a abrir hasta liberarla.
- El total se recalcula en el servidor al cobrar.
- Productos, mesas o usuarios con historial no se borran: se desactivan (productos y mesas) o se bloquea el borrado (usuarios).
- Siempre debe existir al menos un administrador.

## Pruebas

```bash
php artisan test
```
