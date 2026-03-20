# 2. Diseño de base de datos – Sistema Musical Princesa

## Convenciones

- **Prefijos**: tablas nuevas sin prefijo genérico; nombres en plural y claros (`supplier_imports`, `normalized_products`).
- **IDs**: `id` bigint PK; FKs con `_id`; timestamps `created_at`, `updated_at` donde aplique.
- **Soft deletes**: solo donde el negocio lo exija (ej. `users`, `master_products`); el resto borrado físico o por estado.
- **JSON**: para columnas configurables (mapeos, reglas) cuando la estructura varía por proveedor.

---

## Reglas de negocio fijas (resumen)

- **Stock**: `normalized_products.quantity` = solo stock de origen/importación; **no** es stock operativo. `master_products.quantity` = stock operativo real; toda actualización de stock debe reflejarse ahí y en `stock_changes` (previous_quantity, new_quantity, delta, change_mode, source).
- **Producto final de negocio**: solo **master_products**. **normalized_products** es histórico/intermedio; el CSV de exportación a PrestaShop se genera **exclusivamente desde master_products** (y tablas ligadas a maestro: categoría, imágenes).
- **Categorías**: `categories` con `parent_id`; `master_products.category_id` = categoría final/hoja; `master_products.category_path_export` = valor listo para CSV (orden: HOJA, INICIO, PADRE1, PADRE2...).

---

## 2.1 Tablas existentes (resumen)

- **users**: id, name, email, password, status, last_login_at, timestamps (y campos extra ya migrados).
- **roles / permissions**: Spatie (model_has_roles, role_has_permissions, etc.).
- **projects**: id, name, slug, description, public_url, admin_url, local_path, project_type, framework, status, icon_path, color, has_mobile_app, has_api, etc.
- **project_user**: project_id, user_id, access_level.
- **mobile_integrations**: project_id, platform, app_name, current_version, min_supported_version, connection_status.
- **activity_logs**: id, user_id, action, description, subject_type, subject_id, properties, ip, user_agent, created_at.

Se mantienen; el resto del diseño se integra con ellas.

---

## 2.2 Nuevas tablas (por dominio)

### A) Proveedores e importaciones

**suppliers**

| Columna       | Tipo           | Descripción |
|---------------|----------------|-------------|
| id            | bigint PK      | |
| name          | string         | Nombre comercial (ej. ADAGIO). |
| slug          | string unique  | Para URLs y referencias. |
| code          | string nullable| Código interno. |
| is_active     | boolean        | Default true. |
| config        | json nullable  | Config global del proveedor (encoding, separador CSV, etc.). |
| created_at    | timestamp      | |
| updated_at    | timestamp      | |

**supplier_imports**

| Columna         | Tipo           | Descripción |
|-----------------|----------------|-------------|
| id              | bigint PK      | |
| supplier_id     | FK suppliers   | |
| user_id         | FK users       | Quién subió. |
| filename_original| string         | Nombre del archivo subido. |
| file_path       | string         | Ruta en storage. |
| file_type       | string         | csv, xlsx, xml. |
| status          | string         | uploaded, detecting_columns, mapping, preview, processing, processed, failed, approved. |
| total_rows      | int nullable   | Tras lectura. |
| processed_rows  | int default 0  | |
| error_rows      | int default 0  | |
| mapping_snapshot| json nullable  | Mapeo usado (columnas origen → destino). |
| started_at      | timestamp nullable | |
| finished_at     | timestamp nullable | |
| error_message   | text nullable  | Si status = failed. |
| created_at      | timestamp      | |
| updated_at      | timestamp      | |

**supplier_import_rows**

Filas crudas de una importación (para preview y reproceso).

| Columna             | Tipo           | Descripción |
|---------------------|----------------|-------------|
| id                  | bigint PK      | |
| supplier_import_id  | FK supplier_imports | |
| row_index           | int            | Índice 1-based en el archivo. |
| raw_data            | json           | Datos leídos (columnas origen). |
| normalized_data     | json nullable  | Tras transformación (campos PrestaShop). |
| status              | string         | pending, processed, error, skipped. |
| error_message       | text nullable  | |
| created_at          | timestamp      | |
| updated_at          | timestamp      | |

Índices: (supplier_import_id, row_index).

**supplier_field_mappings**

Mapeo reutilizable por proveedor: columna/origen → campo normalizado.

| Columna       | Tipo           | Descripción |
|---------------|----------------|-------------|
| id            | bigint PK      | |
| supplier_id   | FK suppliers   | |
| source_key    | string         | Nombre columna en archivo (ej. ean13, codigo_producto_distribuidor). |
| target_field  | string         | Campo destino PrestaShop (ean13, name, description, etc.). |
| transform     | string nullable| Nombre del transformador (trim, uppercase, ean13_pad, etc.). |
| priority      | int default 0  | Si varias columnas mapean al mismo destino. |
| is_active     | boolean default true | |
| created_at    | timestamp      | |
| updated_at    | timestamp      | |

**supplier_column_aliases** (opcional, para detección automática)

Alias conocidos para detectar columnas equivalentes (motor por reglas/alias, sin IA).

| Columna      | Tipo     | Descripción |
|--------------|----------|-------------|
| id           | bigint PK| |
| target_field | string   | Campo normalizado. |
| alias        | string   | Posible nombre en archivo (fabricante, brand, marca). |
| created_at   | timestamp| |

Sin FK a supplier; catálogo global para sugerir mapeos.

---

### B) Categorías (árbol maestro)

**categories**

Árbol jerárquico con **parent_id** (nullable para la raíz).

| Columna     | Tipo           | Descripción |
|-------------|----------------|-------------|
| id          | bigint PK      | |
| parent_id   | FK categories nullable | Padre; null = raíz (ej. INICIO). |
| name        | string         | Nombre del nodo. |
| slug        | string         | Único en el árbol o por rama. |
| position    | int default 0  | Orden entre hermanos. |
| is_active   | boolean default true | |
| created_at  | timestamp      | |
| updated_at  | timestamp      | |

Índice: (parent_id).

**Construcción de la ruta exportable (category_path_export)**  
La ruta lista para PrestaShop se construye en el orden: **HOJA, INICIO, PADRE1, PADRE2, ...** (primero la categoría hoja/final, luego la raíz, luego los padres de la hoja hasta la raíz). Ejemplo: `ACÚSTICAS ELECTRIFICADAS, INICIO, GUITARRAS Y BAJOS, GUITARRAS, GUITARRAS ELECTROACÚSTICAS`. Esta cadena se guarda en `master_products.category_path_export` y es la que se escribe en el CSV. Se puede calcular con recursive CTE desde `categories` (parent_id) cuando se asigna o actualiza la categoría del producto maestro; no es obligatoria una tabla auxiliar.

**category_paths** (opcional, solo si aporta valor técnico)

Si se necesita acelerar listados o exportaciones masivas sin recalcular rutas en cada petición, se puede mantener una tabla materializada:

| Columna     | Tipo           | Descripción |
|-------------|----------------|-------------|
| id          | bigint PK      | |
| category_id | FK categories  | Categoría (normalmente hoja). |
| path_ids    | json           | [id_raíz, id_nivel2, ..., id_hoja]. |
| path_names  | json           | ["INICIO", "GUITARRAS Y BAJOS", ..., "ACÚSTICAS ELECTRIFICADAS"]. |
| updated_at  | timestamp      | Actualizado por job/trigger cuando cambia el árbol. |

Si no se usa esta tabla, la ruta se calcula on-the-fly con recursive CTE y se persiste solo en `master_products.category_path_export`.

---

### C) Productos normalizados y maestros

**normalized_products**

Un registro por fila de importación procesada. Es capa **histórica/intermedia**; **no** es la fuente del CSV de exportación. Se usa para revisión, unificación y trazabilidad.

| Columna             | Tipo           | Descripción |
|---------------------|----------------|-------------|
| id                  | bigint PK      | |
| supplier_import_id  | FK supplier_imports | |
| supplier_import_row_id | FK supplier_import_rows nullable | |
| master_product_id   | FK master_products nullable | Cuando se une a maestro. |
| supplier_id         | FK suppliers   | |
| supplier_reference  | string nullable| Referencia proveedor. |
| name                | string         | Nombre* generado (MARCA - MODELO - NOMBRE). |
| summary             | text nullable  | Resumen. |
| description         | text nullable  | Descripción (características). |
| ean13               | string(13) nullable | EAN13 validado o vacío. |
| quantity            | int default 0  | **Solo stock de origen/importación.** No es stock operativo. |
| price_tax_incl      | decimal nullable | |
| cost_price          | decimal nullable | |
| tax_rule_id         | int default 1  | Regla fija. |
| warehouse           | string default 'CARPETANA' | |
| active              | tinyint default 1 | 0/1. |
| brand               | string nullable | Marca normalizada. |
| category_path_export| text nullable  | Cadena intermedia (puede usarse para sugerencias). |
| tags                | text nullable  | Etiquetas separadas por coma. |
| validation_status   | string         | pending, valid, warnings, errors. |
| ean_status          | string nullable| ok, empty, invalid. |
| created_at          | timestamp      | |
| updated_at          | timestamp      | |

Índices: (supplier_import_id), (master_product_id), (ean13), (supplier_id, supplier_reference).

**master_products**

Producto unificado y **única fuente de verdad para negocio y exportación**. El CSV PrestaShop se genera desde aquí (y relaciones: categoría, imágenes).

| Columna             | Tipo           | Descripción |
|---------------------|----------------|-------------|
| id                  | bigint PK      | |
| ean13               | string(13) nullable unique | Puede ser null si no hay EAN. |
| name                | string         | Nombre principal. |
| summary             | text nullable  | |
| description         | text nullable  | |
| quantity            | int default 0  | **Stock operativo real.** Única cantidad usada para app y export. |
| price_tax_incl      | decimal nullable | |
| cost_price          | decimal nullable | |
| tax_rule_id         | int default 1  | |
| warehouse           | string default 'CARPETANA' | |
| active              | tinyint default 1 | |
| brand               | string nullable | |
| category_id         | FK categories nullable | **Siempre categoría final/hoja.** |
| category_path_export| text nullable  | **Valor final listo para CSV:** HOJA, INICIO, PADRE1, PADRE2... |
| tags                | text nullable  | |
| is_approved         | boolean default false | Aprobado para export. |
| approved_at         | timestamp nullable | |
| approved_by_id      | FK users nullable | |
| version             | int default 1  | Para concurrencia/sync. |
| created_at          | timestamp      | |
| updated_at          | timestamp      | |

Índices: (ean13), (category_id), (is_approved).

**master_product_suppliers**

Relación N:M entre producto maestro y productos normalizados (varios proveedores → un maestro).

| Columna               | Tipo           | Descripción |
|-----------------------|----------------|-------------|
| id                    | bigint PK      | |
| master_product_id     | FK master_products | |
| normalized_product_id | FK normalized_products | |
| supplier_id           | FK suppliers   | |
| supplier_reference    | string nullable| |
| is_primary            | boolean default false | Referencia principal. |
| created_at            | timestamp      | |
| updated_at            | timestamp      | |

Índice único recomendado: (normalized_product_id) único (cada normalizado en un solo maestro).

---

### D) Imágenes

**product_images**

Imágenes asociadas al **producto maestro** (exportación y app). Origen puede quedar en normalized_product_id si se desea trazabilidad.

| Columna               | Tipo           | Descripción |
|-----------------------|----------------|-------------|
| id                    | bigint PK      | |
| master_product_id     | FK master_products | |
| normalized_product_id | FK normalized_products nullable | Origen. |
| url_original          | string nullable| URL del proveedor. |
| path_local            | string         | Ruta en servidor (images-prestashop/...). |
| position              | int default 0  | Orden. |
| is_cover              | boolean default false | Portada (imagen principal para offline). |
| width                 | int nullable   | 800. |
| height                | int nullable   | 800. |
| status                | string         | pending_download, downloaded, failed. |
| error_message         | text nullable  | |
| created_at            | timestamp      | |
| updated_at            | timestamp      | |

Índices: (master_product_id), (normalized_product_id).

---

### E) Incidencias y validaciones

**product_ean_issues**

EAN vacíos o inválidos (pueden referenciar normalizado y/o maestro según flujo).

| Columna                | Tipo           | Descripción |
|------------------------|----------------|-------------|
| id                     | bigint PK      | |
| normalized_product_id  | FK normalized_products nullable | |
| master_product_id      | FK master_products nullable | |
| issue_type             | string         | empty, invalid. |
| value_received         | string nullable| Lo que vino. |
| resolved_at            | timestamp nullable | |
| resolved_by_id         | FK users nullable | |
| resolution_notes        | text nullable  | |
| created_at             | timestamp      | |
| updated_at             | timestamp      | |

**duplicate_product_groups**

Grupos de productos que comparten EAN (posibles duplicados).

| Columna           | Tipo     | Descripción |
|-------------------|----------|-------------|
| id                | bigint PK| |
| ean13             | string(13) | EAN común. |
| master_product_id | FK master_products nullable | Elegido como maestro. |
| status            | string   | pending_review, merged, ignored. |
| created_at        | timestamp| |
| updated_at        | timestamp| |

**duplicate_product_group_items**

| Columna                   | Tipo           | Descripción |
|---------------------------|----------------|-------------|
| id                        | bigint PK      | |
| duplicate_product_group_id| FK duplicate_product_groups | |
| normalized_product_id     | FK normalized_products | |
| master_product_id         | FK master_products nullable | |
| created_at                | timestamp      | |

**product_category_suggestions**

Sugerencias de categoría (matching automático por reglas/texto o manual).

| Columna                | Tipo           | Descripción |
|------------------------|----------------|-------------|
| id                     | bigint PK      | |
| normalized_product_id  | FK normalized_products | |
| master_product_id      | FK master_products nullable | |
| category_id            | FK categories  | Sugerida. |
| source                 | string         | auto, manual. |
| score                  | decimal nullable | Si es automático. |
| accepted_at            | timestamp nullable | |
| created_at             | timestamp      | |
| updated_at             | timestamp      | |

---

### F) Stock, escaneos y sincronización (app)

**stock_changes**

Registra **todo el historial de cambios de stock**: importación, app y admin. Cada cambio debe reflejar valor anterior, nuevo, delta y modo.

| Columna           | Tipo           | Descripción |
|-------------------|----------------|-------------|
| id                | bigint PK      | |
| master_product_id | FK master_products | |
| user_id           | FK users       | |
| device_id         | string nullable| Identificador dispositivo app (null si import/admin). |
| previous_quantity | int            | Cantidad antes del cambio. |
| new_quantity      | int            | Cantidad después del cambio. |
| delta             | int            | Diferencia (new_quantity - previous_quantity); en modo increment puede ser el valor sumado. |
| change_mode       | string         | **replace** (sustituir valor) o **increment** (sumar/restar). |
| source            | string         | import, app, admin. |
| sync_status       | string nullable| Para eventos app: pending, synced, conflict. |
| synced_at         | timestamp nullable | |
| created_at        | timestamp      | |

**stock_scan_events** (o product_scan_events)

Eventos de escaneo en app.

| Columna           | Tipo           | Descripción |
|-------------------|----------------|-------------|
| id                | bigint PK      | |
| master_product_id | FK master_products | |
| user_id           | FK users       | |
| device_id         | string nullable| |
| scanned_at        | timestamp      | |
| created_at        | timestamp      | |

**mobile_sync_queue** (cola en backend de eventos enviados por la app)

| Columna       | Tipo           | Descripción |
|---------------|----------------|-------------|
| id            | bigint PK      | |
| device_id     | string         | |
| user_id       | FK users       | |
| action        | string         | stock_update, ean_update, category_update, scan. |
| payload       | json           | product_id, old_value, new_value, version, **change_mode** (replace|increment), delta si aplica. |
| status        | string         | pending, processing, applied, conflict, failed. |
| processed_at  | timestamp nullable | |
| error_message | text nullable  | |
| created_at    | timestamp      | |
| updated_at    | timestamp      | |

**app_devices**

| Columna     | Tipo           | Descripción |
|-------------|----------------|-------------|
| id          | bigint PK      | |
| user_id     | FK users       | |
| device_id   | string         | UUID o similar. |
| device_name | string nullable| |
| platform    | string nullable| android, ios. |
| last_seen_at| timestamp nullable | |
| created_at  | timestamp      | |
| updated_at  | timestamp      | |

Índice único: (user_id, device_id).

---

### G) Sesiones y presencia (tiempo real)

**user_sessions** (o ampliar uso de `sessions` de Laravel)

| Columna          | Tipo           | Descripción |
|------------------|----------------|-------------|
| id               | bigint PK      | |
| user_id          | FK users       | |
| guard            | string         | web, sanctum. |
| device_id        | string nullable| Para app. |
| last_activity_at | timestamp      | |
| created_at       | timestamp      | |
| updated_at       | timestamp      | |

---

### H) Configuración

**settings**

| Columna   | Tipo     | Descripción |
|-----------|----------|-------------|
| id        | bigint PK| |
| key       | string unique | |
| value     | text nullable | |
| type      | string   | string, int, bool, json. |
| updated_at| timestamp| |

---

## 2.3 Correspondencia exportación PrestaShop ↔ master_products

El CSV final se genera **solo desde master_products** (y tablas relacionadas). Ejemplo de correspondencia:

| Columna CSV PrestaShop        | Origen en sistema |
|------------------------------|-------------------|
| Activo (0/1)                  | master_products.active |
| Nombre*                       | master_products.name |
| Resumen                       | master_products.summary |
| Descripción                   | master_products.description |
| Categorias (x,y,z...)         | master_products.category_path_export |
| Precio impuestos incluidos    | master_products.price_tax_incl |
| Precio de coste               | master_products.cost_price |
| ID regla de impuestos         | master_products.tax_rule_id (fijo 1) |
| Referencia Nº                 | master_products.id o referencia interna |
| Nº Referencia proveedor       | Desde master_product_suppliers (is_primary) |
| Proveedor                     | Nombre supplier desde master_product_suppliers |
| Marca                         | master_products.brand |
| Almacen                       | master_products.warehouse (CARPETANA) |
| EAN13                         | master_products.ean13 |
| Cantidad                      | **master_products.quantity** (stock operativo) |
| Etiquetas (x,y,z...)         | master_products.tags |
| URLs de las imagenes (x,y,z...) | product_images.path_local (URL pública) ordenadas por position; is_cover puede definir primera. |

**normalized_products no interviene en el CSV de exportación.**

---

## 2.4 Relaciones principales (resumen)

- **supplier** → supplier_imports, supplier_field_mappings, normalized_products.
- **supplier_import** → supplier_import_rows, normalized_products; user (uploader).
- **normalized_product** → master_product (opcional), supplier, product_images, product_ean_issues, duplicate_group_items, product_category_suggestions. **Rol: histórico/intermedio.**
- **master_product** → category (hoja), master_product_suppliers, product_images, stock_changes, stock_scan_events. **Rol: verdad de negocio y export.**
- **category** → parent/children (categories); master_products (category_id = hoja); category_path_export en master_products.
- **user** → supplier_imports, stock_changes, stock_scan_events, mobile_sync_queue, app_devices, user_sessions, activity_logs.

Este diseño permite implementación por fases y cumple las reglas de stock, categorías y exportación definidas en el documento 01.
