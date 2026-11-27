# Punk Brothers · Sistema para restaurante

Sistema web en Laravel para administrar un restaurante pequeño. Cubre el servicio completo de una mesa: abrirla, tomar el pedido, enviarlo a cocina, cobrar y liberarla. También lleva un inventario de ingredientes envasados que se registran escaneando su código de barras, con datos de la API pública de [Open Food Facts](https://world.openfoodfacts.org).

Tiene dos roles:

- **Usuario:** Mesas, pedidos, cocina y cobro.
- **Administrador:** Tiene todas las funcionalidades que rendria un usuario general pero este tiene acceso al panel administrativo.

## Requisitos

- PHP 8.4 o superior, con las extensiones `pdo_sqlite`, `curl`, `mbstring` y `openssl`.
- Composer.
- Acceso a internet para usar la API OpenFoodFacts.

## Instalación

```bash
git clone https://github.com/ReneBaez24/Repostaurante.git
cd Repostaurante
composer install
cp .env.example .env          # en Windows (CMD): copy .env.example .env
php artisan key:generate
php artisan migrate --seed    # si pregunta si crear database.sqlite, responde "yes"
```

## Ejecutar

```bash
php artisan serve
```

Abre <http://localhost:8000> e inicia sesión con un usuario de prueba:

| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@punkbrothers.com | admin123 |
| Usuario | rene@punkbrothers.com | mesero123 |

## Pruebas

```bash
php artisan test
```

Corre las 5 pruebas unitarias del proyecto.

## Comandos útiles

| Comando | Qué hace |
|---|---|
| `php artisan openfoodfacts:diagnosticar` | Prueba la conexión con Open Food Facts y muestra la causa si falla |
| `php artisan inventario:importar codigos.txt` | Importa al inventario una lista de códigos de barras (uno por línea) |
| `php artisan rendimiento:medir --url=https://world.openfoodfacts.net` | Compara el rendimiento de la importación antes y después de la optimización |