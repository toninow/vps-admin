# 4. Flujos del sistema

## 4.1 Flujo completo: importación proveedor → exportación PrestaShop

```
1. SUBIDA Y DETECCIÓN
   Usuario sube archivo (CSV/Excel/XML)
   → Sistema guarda en storage, crea supplier_import (status: uploaded)
   → Detecta tipo (por extensión + peek contenido)
   → Lee cabeceras / primer nivel XML
   → status: detecting_columns

2. ASIGNACIÓN DE PROVEEDOR
   Usuario asigna o confirma proveedor (o se detecta por nombre/patrón)
   → supplier_import.supplier_id asignado

3. MAPEO DE COLUMNAS
   Sistema sugiere mapeo mediante alias (fabricante→Marca, ean13→EAN13, etc.)
   Usuario revisa/edita en pantalla (origen → destino, transformador opcional)
   → Se guarda en supplier_field_mappings (por proveedor) y/o mapping_snapshot en import
   → status: mapping

4. PREVIEW
   Se leen N filas (ej. 100) y se transforman con el mapeo actual
   → Se muestran en tabla: columnas origen → columnas normalizadas
   → Usuario puede corregir mapeo y volver a preview
   → status: preview

5. PROCESAMIENTO (JOB)
   Usuario confirma "Procesar"
   → Job ProcessSupplierImport
   → Para cada fila (o por lotes):
     - Leer raw_data
     - Aplicar MappingService + TransformPipeline (reglas/transformadores, sin IA)
     - Generar Nombre* (NameGenerator: MARCA - MODELO - NOMBRE)
     - Generar Descripción (DescriptionGenerator: CARACTERÍSTICAS + lista)
     - Validar EAN (EanValidator): ok / empty / invalid
     - Matching categorías por texto (CategoryMatcher) → product_category_suggestions
     - Crear normalized_product (quantity = stock de origen del archivo; no es stock operativo)
     - Enlazar supplier_import_row
     - Crear product_ean_issues si EAN empty/invalid
     - Detectar duplicados por EAN → duplicate_product_groups
   → Descarga de imágenes (Job DownloadProductImage por imagen)
   → Actualizar total_rows, processed_rows, error_rows; status: processed
   → Si fallo global: status: failed, error_message

6. REVISIÓN Y UNIFICACIÓN
   Usuario revisa productos normalizados (capa intermedia/histórica)
   - Corrige EAN vacíos/inválidos (o se marcan para edición en app)
   - Revisa duplicados por EAN; elige producto maestro; fusiona
   - Acepta/sustituye sugerencias de categoría
   → Se crean/actualizan master_products y master_product_suppliers
   → Stock operativo inicial del maestro: puede copiarse del normalizado elegido como principal o fijarse a 0; en cualquier caso se registra un stock_change (source: import) con previous_quantity, new_quantity, delta, change_mode (replace)
   → master_products.category_id = categoría hoja elegida
   → master_products.category_path_export = ruta construida (HOJA, INICIO, PADRE1, PADRE2...)

7. IMÁGENES
   Imágenes descargadas a public/images-prestashop/; 800x800; orden y portada (is_cover)
   → product_images ligadas a master_product; errores en status/error_message

8. APROBACIÓN
   Usuario marca productos/lote como "Aprobado para exportación"
   → master_product.is_approved = true; approved_at; approved_by_id

9. EXPORTACIÓN CSV PRESTASHOP
   Usuario va a Exportar PrestaShop. Filtros: aprobados, por proveedor, por fecha, etc.
   → La exportación se hace EXCLUSIVAMENTE desde master_products (y tablas ligadas: category_path_export, product_images). normalized_products no interviene en el CSV final.
   → PrestaShopCsvExporter genera CSV con columnas exactas (Activo, Nombre*, Resumen, Descripción, Categorias (x,y,z), Precio impuestos incluidos, Precio de coste, ID regla de impuestos, Referencia Nº, Nº Referencia proveedor, Proveedor, Marca, Almacen, EAN13, Cantidad, Etiquetas, URLs de las imagenes)
   → Cantidad en CSV = master_products.quantity (stock operativo)
   → Descarga archivo
   → Opcional: notificar a app STOCK MP para refrescar catálogo
```

### Reglas fijas en el flujo

- **Stock**: normalized_products.quantity = solo origen importación; master_products.quantity = stock operativo; todo cambio de stock se registra en stock_changes (previous_quantity, new_quantity, delta, change_mode, source).
- **Categorías**: category_id en master_products = hoja; category_path_export = HOJA, INICIO, PADRE1, PADRE2...
- **Export**: solo desde master_products; normalized_products es histórico/intermedio.

---

## 4.2 Stock en la app: modos replace e increment

### Modos de actualización de stock (obligatorios)

- **replace**: el usuario indica el **valor final** de cantidad. El backend sustituye `master_products.quantity` por ese valor (tras validar versión si hay concurrencia).
- **increment**: el usuario indica un **delta** (positivo o negativo) que se **suma** al valor actual en servidor. Ejemplo: cantidad actual 10, delta +5 → new_quantity 15.

En ambos casos se guarda en **stock_changes**: `previous_quantity`, `new_quantity`, `delta`, `change_mode` (replace | increment), `source` = app.

### Payload en sync (app → backend)

- **replace**: `{ product_id, version, new_quantity, change_mode: "replace" }`. Backend comprueba version; si coincide, asigna new_quantity a master_products.quantity y registra stock_change (previous_quantity = valor anterior, new_quantity, delta = new_quantity - previous_quantity, change_mode = replace).
- **increment**: `{ product_id, version, delta, change_mode: "increment" }`. Backend comprueba version; si coincide, lee quantity actual, calcula new_quantity = quantity + delta (con límites 0–9999), actualiza master_products.quantity y registra stock_change (previous_quantity, new_quantity, delta, change_mode = increment).

### Resolución de conflictos por modo

- **replace**: si la versión no coincide (otro usuario o proceso cambió el producto), hay conflicto. Backend devuelve 409 con cantidad remota actual y versión. Opciones para el usuario: (1) mantener mi cambio (reintentar con última versión o forzar), (2) usar valor del servidor, (3) revisar diferencias y decidir.
- **increment**: si la versión no coincide, se puede aplicar el delta sobre el **valor actual del servidor** (no sobre el valor que el usuario tenía al editar), evitando sobrescribir cambios ajenos: new_quantity = quantity_actual_servidor + delta. Así no hay conflicto de valor, solo de “orden de llegada”. Alternativamente, si se desea tratar como conflicto cuando la versión cambió, el backend devuelve 409 y el usuario elige: aplicar mi delta sobre el valor actual del servidor, o descartar mi cambio.

La decisión de implementación (increment siempre aplicado sobre valor actual vs. 409 cuando versión cambió) se fija en Fase 5/6; la arquitectura soporta ambos con `change_mode` y `delta` en stock_changes.

---

## 4.3 Flujo de trabajo app Flutter: online / offline

### Estados de conectividad (UI)

- **Online**: backend alcanzable; se puede sincronizar y refrescar.
- **Offline**: sin conexión o backend no alcanzable; solo datos locales.
- **Sincronizando**: enviando/recibiendo cambios.
- **Cambios pendientes**: hay eventos en cola local sin enviar.
- **Conflicto detectado**: el servidor devolvió conflicto; el usuario debe resolver.

### Estrategia offline (obligatoria)

**No se descarga todo el catálogo por defecto.** Se define una estrategia concreta:

- **Qué productos se descargan** (elegir una o combinar con límites):
  - Por **filtro de categoría** (solo categorías que el usuario usa en tienda),
  - Por **fecha de actualización** (productos modificados desde last_sync_at),
  - Por **límite** (ej. últimos N productos o los M más consultados),
  - O **bajo demanda**: descargar al abrir ficha o al escanear (y cachear en local).
  La implementación elegirá criterio (ej. “productos actualizados desde X” + “límite 5000” o “solo los que el usuario ha escaneado/consultado”).

- **Qué imágenes se descargan**:
  - **Imagen principal (portada)** del producto debe quedar **disponible offline** para cada producto que el usuario tenga en su catálogo local. Es decir: para cada producto descargado en local_products, se descarga al menos la imagen con is_cover = true (o la primera por position) y se guarda en almacenamiento local.
  - **Imágenes secundarias** pueden descargarse **bajo demanda** (al abrir el slider/galería del producto en la ficha).

- **Datos mínimos para que la app funcione offline**:
  - Sesión local (usuario, token o equivalente seguro).
  - Productos descargados: id, ean13, name, quantity, category_id, category_path_export, version, updated_at, y referencia a imagen principal local (ruta o URL cacheada).
  - Categorías necesarias para el selector (árbol o lista con path).
  - Cola local de cambios (sync_queue) con eventos pendientes.
  - Opcional: last_sync_at, device_id en local_settings.

### Flujo offline (en tienda)

1. App sirve todo desde BD local (Drift): local_products, local_categories, imágenes ya cacheadas.
2. Escaneo: se guarda local_scan_events y/o sync_queue (action: scan).
3. Edición stock: usuario elige modo replace o increment; se actualiza quantity en local_products; se inserta en sync_queue (action: stock_update, payload con change_mode, new_quantity o delta, product_version).
4. Edición EAN / categoría: evento ean_update o category_update en sync_queue.
5. UI muestra "Cambios pendientes" y lista de pendientes si se desea.

### Flujo online (cuando hay conexión)

1. ConnectivityService + reachability al backend (ping o endpoint ligero).
2. SyncEngine toma eventos de sync_queue (status = pending), orden created_at.
3. Para cada evento: POST a API (stock con change_mode, ean, category) con product_id, version y payload. Backend aplica en master_products y registra stock_changes con previous_quantity, new_quantity, delta, change_mode; responde 200 o 409 (conflict).
4. App actualiza evento a synced o conflict; en conflict muestra pantalla de resolución (replace: mantener mío / usar servidor; increment: aplicar delta sobre valor actual / descartar).
5. Tras enviar pendientes, app puede solicitar refresco incremental (productos/categorías desde last_sync_at) y actualizar local_products; descargar imagen principal para productos nuevos o actualizados.
6. Imágenes secundarias: descarga bajo demanda al abrir galería.

### Cola local (sync_queue) – mínimo por evento

- id local, action: scan | stock_update | ean_update | category_update
- product_id (id servidor), user_id, device_id
- payload: { old_value, new_quantity o delta, product_version, **change_mode**: replace | increment }
- status: pending | syncing | synced | failed | conflict
- created_at; synced_at o error_message si aplica

### Resolución de conflictos (resumen)

- **Stock replace**: opciones "mantener mi cambio" (reintentar/forzar), "usar valor del servidor".
- **Stock increment**: opciones "aplicar mi delta sobre valor actual del servidor", "descartar mi cambio".
- **EAN**: no sobrescribir automáticamente; mostrar valor remoto vs local; usuario elige.
- **Categoría**: mostrar ruta local vs ruta remota; usuario elige.

---

## 4.4 Flujo de sincronización (backend ↔ app)

**App → Backend**

1. App envía POST /api/sync/push (o endpoints específicos por tipo) con eventos que incluyen **change_mode** para stock.
2. Backend (MobileSyncService): valida token; por cada evento aplica en master_products; registra stock_changes (previous_quantity, new_quantity, delta, change_mode, source = app); actualiza mobile_sync_queue; responde applied o conflict (409 con datos remotos).
3. App marca eventos como synced o conflict y actualiza datos locales (quantity, version).

**Backend → App**

1. GET /api/products con filtros (updated_since, category_id, limit, etc.) según estrategia de descarga elegida.
2. Backend devuelve productos desde **master_products** (no normalized_products); app persiste en local_products.
3. GET /api/categories (árbol o lista con path); app persiste en local_categories.
4. Para cada producto devuelto, la app debe poder obtener la **imagen principal** (URL o endpoint) y guardarla en local para uso offline.
5. Presencia: heartbeat (POST /api/presence o WebSocket); backend actualiza app_devices.last_seen_at, user_sessions; admin puede mostrar "quién está conectado" y "quién escaneó qué".

---

## 4.5 Flujo de monitorización en tiempo real (admin)

- Canales Laravel Broadcasting; eventos: UserActivityUpdated, ScanEventCreated, StockChangeCreated, SyncConflictDetected, ImportStatusUpdated.
- Dashboard escucha y actualiza widgets (usuarios activos, productos escaneados por usuario, pendientes de sync, conflictos, estado de importación).
- Implementación concreta en Fase 7 (documento 06).

---

## 4.6 Exportación PrestaShop (cerrada)

- **Origen único**: master_products (y relaciones: category_path_export, product_images, master_product_suppliers para referencia proveedor).
- **normalized_products** no se usa para generar el CSV; es capa histórica/intermedia.
- Correspondencia columnas CSV ↔ campos en master_products (y tablas ligadas) según documento 02, sección 2.3.
