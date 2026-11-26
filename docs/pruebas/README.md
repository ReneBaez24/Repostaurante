# Pruebas unitarias: antes y después

El proyecto tiene 5 pruebas unitarias en total. Se escribieron **antes** que el código que prueban:

- **Antes:** `antes.txt`, 5 en rojo.
- **Después:** `despues.txt`, 5 en verde.

| # | Prueba | Regla que protege |
| --- | --- | --- |
| 1 | `CodigoBarrasTest::test_acepta_codigos_validos_y_los_normaliza` | Acepta EAN-8, UPC-A, EAN-13 y GTIN-14; limpia espacios y guiones; dígito verificador |
| 2 | `CodigoBarrasTest::test_rechaza_codigos_invalidos_con_mensajes_comprensibles` | Vacío, letras, largo incorrecto y dígito verificador erróneo, cada uno con su mensaje |
| 3 | `CalculadoraCuentaTest::test_calcula_impuesto_incluido_propina_y_total` | Impuesto incluido (10 %), propina y total de la cuenta de cobro, redondeo a centavos, propina negativa rechazada |
| 4 | `ProductoEnvasadoTest::test_traduce_la_respuesta_de_la_api_y_tolera_datos_faltantes` | Traducción de la respuesta de Open Food Facts: nombre en español, marca, alérgenos en español, Nutri-Score, datos faltantes |
| 5 | `OpenFoodFactsClientTest::test_convierte_fallas_de_la_api_en_mensajes_comprensibles` | 404, 503, 429 y sin conexión se convierten en mensajes claros; un código mal escrito no llega a la API |

## Reproducir el antes y el después

Cada estado tiene su commit:

```bash
# ANTES: pruebas escritas, código sin implementar
git checkout $(git log --format=%h --grep="estado ANTES" -1)
php artisan test --testsuite=Unit         # 5 errores: "Pendiente de implementar"

# DESPUÉS: regresar a tu rama con el código implementado
git checkout -
php artisan test --testsuite=Unit         # 5 pruebas en verde
```

Haz esto con los cambios guardados en git (`git status` limpio).

Si aplicaste los cambios desde un .zip (sin el historial de git), `antes.txt` y `despues.txt` tienen la salida de las dos corridas.

Para ver la lista con nombres legibles, como en los archivos:

```bash
php vendor/bin/phpunit --testsuite=Unit --testdox
```
