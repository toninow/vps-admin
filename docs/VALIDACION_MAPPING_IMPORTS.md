# Validación real del mapping y transformación (imports)

Objetivo: probar el flujo completo de mapeo sugerido + transformación sobre archivos reales y verificar la calidad del resultado en `normalized_products`.

## Comando: crear importación de prueba desde terminal

No hace falta usar el navegador para crear una importación. Desde un archivo real en el servidor:

```bash
# Flujo completo: crear import + copiar archivo + mapeo sugerido + persistir filas + transformar
php artisan imports:create-test --supplier=ADAGIO --file="/ruta/al/archivo.csv"

# Solo crear registro y copiar archivo
php artisan imports:create-test --supplier=ADAGIO --file="/ruta/al/archivo.csv" --only-create

# Solo ver columnas y primeras filas (no guarda mapeo)
php artisan imports:create-test --supplier=ADAGIO --file="/ruta/al/archivo.csv" --only-preview

# Guardar mapeo y filas pero no transformar
php artisan imports:create-test --supplier=ADAGIO --file="/ruta/al/archivo.csv" --only-map

# Ver tabla del mapeo sugerido antes de aplicar
php artisan imports:create-test --supplier=ADAGIO --file="/ruta/al/archivo.csv" --show-suggested
```

El proveedor se busca por nombre (parcial): `--supplier=ADAGIO` coincide con "ADAGIO" o "Adagio Music", etc. Debe existir y estar activo en la tabla `suppliers`.

Tras crear y procesar, valida con:

```bash
php artisan imports:validate-mapping --supplier=ADAGIO
```

## 1. Estrategia de prueba real

- Usar **una importación real por proveedor** (ADAGIO, GEWA, FENDER, YAMAHA) ya subida y, a ser posible, ya procesada.
- Si no hay importaciones: subir un archivo real de cada proveedor → preview → mapeo (revisar sugerencias del perfil) → guardar mapeo → procesar.
- Tras "Procesar → productos normalizados", validar con:
  - **Admin:** pantalla de detalle de la importación (incluye informe de validación).
  - **CLI:** `php artisan imports:validate-mapping {id}` o `php artisan imports:validate-mapping --supplier=ADAGIO`.
- Revisar **métricas por campo** (rellenados, tasa %, muestras, incidencias) y corregir mapeo o perfiles si algún campo sale mal.

## 2. Comandos y acciones en el admin

| Paso | Acción |
|------|--------|
| 1 | **Admin → Importaciones → Nueva importación.** Elegir proveedor (ej. ADAGIO), subir CSV/Excel del proveedor. |
| 2 | **Preview.** Revisar columnas y primeras filas. |
| 3 | **Mapeo.** Revisar columnas sugeridas por perfil; ajustar si hace falta; **Guardar mapeo** (persiste filas en `supplier_import_rows`). |
| 4 | **Detalle de importación.** Pulsar **"Procesar → productos normalizados"** (ejecuta el transformer y crea `normalized_products`). |
| 5 | En la misma pantalla de detalle, revisar la sección **"Validación del mapeo (normalized_products)"**: tabla por campo con rellenados, tasa %, muestras e incidencias. |

**Comando CLI (tras procesar):**

```bash
# Por ID de importación
php artisan imports:validate-mapping 42

# Por nombre de proveedor (usa la última importación de ese proveedor)
php artisan imports:validate-mapping --supplier=ADAGIO
php artisan imports:validate-mapping --supplier=GEWA
php artisan imports:validate-mapping --supplier=FENDER
php artisan imports:validate-mapping --supplier=YAMAHA

# Ver además el mapeo sugerido por perfil (sin guardar)
php artisan imports:validate-mapping --supplier=ADAGIO --show-suggested
```

## 3. Cómo inspeccionar resultados

- **En la web:** en la ficha de la importación (estado "processed"), el bloque "Validación del mapeo" muestra una tabla con todos los target fields, rellenados/total, tasa %, hasta 2 muestras por campo y hasta 2 incidencias por campo.
- **En CLI:** la misma información en tabla por consola; además se indica el perfil aplicado y las columnas mapeadas.
- **En BD:** consultar `normalized_products` filtrando por `supplier_import_id` y revisar columnas `name`, `ean13`, `quantity`, `price_tax_incl`, `cost_price`, `brand`, `category_path_export`, `tags`, `supplier_reference`, `image_urls`, etc.

## 4. Métricas de calidad que se revisan

Para cada uno de los 12 target fields:

- **name, summary, description, ean13, quantity, price_tax_incl, cost_price, brand, category_path_export, tags, supplier_reference, image_urls**
  - **Rellenados:** número de productos con valor no vacío (para `image_urls`, array no vacío).
  - **Tasa %:** (rellenados / total productos) × 100.
  - **Muestras:** hasta 3 valores de ejemplo (truncados) para comprobar que el contenido es correcto.
  - **Incidencias:** por ejemplo EAN con longitud distinta de 8/12/13, cantidad o precios negativos (se muestran hasta las primeras por campo).

Criterios de calidad orientativos:

- **name:** tasa 100% (el transformer ya omite filas sin nombre).
- **ean13, quantity, price_tax_incl, cost_price, supplier_reference:** tasas altas según lo que suela traer el proveedor.
- **image_urls:** comprobar que hay columna mapeada y que la tasa y las muestras son coherentes con el archivo.
- **category_path_export, tags, description:** dependen del proveedor; revisar que las muestras tengan sentido.

## 5. Lo implementado para facilitar la validación

- **`App\Services\Import\ImportMappingValidationService`:** construye el informe de validación (rellenados, tasa %, muestras, incidencias) sobre los `normalized_products` de una importación.
- **Comando `imports:validate-mapping`:** permite validar por ID o por `--supplier=` y opcionalmente mostrar el mapeo sugerido con `--show-suggested`.
- **Vista `imports.show`:** si la importación está procesada y tiene productos normalizados, se muestra automáticamente el bloque "Validación del mapeo" con la tabla de métricas.
- **Controlador `SupplierImportController::show`:** inyecta `ImportMappingValidationService` y pasa `validationReport` a la vista cuando aplica.
