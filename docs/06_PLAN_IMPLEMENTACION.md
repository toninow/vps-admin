# 6. Plan de implementación por fases

El desarrollo se realiza por fases reales, cada una con entregables claros. Las reglas obligatorias (stock, categorías, motor por reglas, modos replace/increment, offline, export solo desde master_products) están fijadas en los documentos 01, 02 y 04 y deben respetarse en cada fase.

---

## FASE 1 – Análisis y diseño (HECHO)

- [x] Análisis funcional completo.
- [x] Propuesta de stack y arquitectura (01).
- [x] Diseño de base de datos (02) con reglas de stock, categorías y export.
- [x] Estructura de módulos (03); motor por reglas/alias/transformadores; export solo master_products.
- [x] Flujos (04): stock replace/increment; conflictos; estrategia offline; export desde master_products.
- [x] Entidades y pantallas (05).
- [x] Plan de fases (06) revisado.

**No hay código en Fase 1; solo documentación.**

---

## FASE 2 – Base del backend y admin

**Objetivo**: Estructura Laravel lista para nuevos módulos; auth y RBAC ampliados; migraciones con reglas de stock y categorías; sistema de diseño web.

**Entregables**:

1. **Migraciones** (orden por dependencias):
   - suppliers
   - supplier_imports
   - supplier_import_rows
   - supplier_field_mappings
   - supplier_column_aliases (opcional)
   - categories (con parent_id)
   - category_paths (solo si se decide usar tabla materializada; si no, se calcula ruta on-the-fly)
   - normalized_products (quantity = stock origen; comentar en migración)
   - master_products (quantity = stock operativo; category_id = hoja; category_path_export)
   - master_product_suppliers
   - product_images
   - product_ean_issues
   - duplicate_product_groups
   - duplicate_product_group_items
   - product_category_suggestions
   - **stock_changes** (previous_quantity, new_quantity, **delta**, **change_mode** (replace|increment), source)
   - stock_scan_events
   - mobile_sync_queue (payload puede incluir change_mode)
   - app_devices
   - user_sessions (o uso de sessions de Laravel)
   - settings

2. **Modelos Eloquent** con relaciones; en MasterProduct y NormalizedProduct dejar claro el rol de quantity (operativo vs origen).

3. **Seeders**:
   - RolePermissionSeeder ampliado (suppliers.*, imports.*, mappings.*, products.*, master_products.*, categories.*, ean.*, duplicates.*, stock.*, mobile.sync, export.*).
   - Rol stock_user.
   - CategorySeeder (raíz INICIO o estructura mínima).
   - SettingsSeeder (almacen CARPETANA, tax_rule_id 1).

4. **Sistema de diseño** en panel (Tailwind: brand #E6007E, neutral #555555; botones, inputs, cards).

5. **Políticas** para Supplier, SupplierImport, NormalizedProduct, MasterProduct, Category, etc.

6. **Rutas web** (skeleton) con middleware auth + active y can:.

7. **Vistas Blade mínimas** (opcional): listados vacíos o "Próximamente".

**Orden sugerido**: migraciones → modelos → seeders → políticas → rutas → vistas placeholder.

---

## FASE 3 – Módulo de proveedores e importación

**Objetivo**: CRUD proveedores; subida de archivos; detección de formato; mapeo por proveedor (alias y reglas); preview; motor de transformación. **Inteligencia solo por reglas, alias, transformadores y validadores; sin IA generativa ni embeddings.**

**Entregables**:

1. **Proveedores**: SupplierController; vistas index, create, edit, show. SupplierFieldMappingController o integrado en show (origen → destino, transformadores).

2. **Importaciones**:
   - SupplierImportController: create (subida + proveedor), store; redirigir a mapeo.
   - FileReaderFactory + CsvReader, ExcelReader, XmlReader.
   - **ColumnDetector**: sugerir columnas equivalentes usando **supplier_column_aliases** (o lógica interna); sin IA.
   - Pantalla mapeo: columnas origen; sugerencia por alias; guardar en supplier_field_mappings y/o mapping_snapshot.
   - Preview: N filas con MappingService + TransformPipeline (trim, uppercase, ean13_pad, etc.).
   - Job ProcessSupplierImport (esqueleto): crear supplier_import_rows con raw_data; opcionalmente primera versión de normalized_products (quantity = valor del archivo, stock origen).

3. **MappingService** y **TransformPipeline**: reglas y transformadores por campo; sin modelos de lenguaje ni embeddings.

**Dependencias**: Fase 2.

---

## FASE 4 – Normalización, validación, incidencias, categorías e imágenes

**Objetivo**: Productos normalizados completos (quantity = stock origen); validación EAN; incidencias; duplicados; árbol de categorías (parent_id); matching textual de categorías; category_path_export (HOJA, INICIO, PADRE1...); creación/actualización de master_products (quantity = stock operativo; al fusionar o al crear desde importación registrar stock_change con source = import, change_mode = replace); descarga y redimensionado de imágenes.

**Entregables**:

1. **Normalización**:
   - NameGenerator, DescriptionGenerator, EanValidator, **CategoryMatcher (matching textual, no IA)**.
   - ImportProcessor: crear normalized_product por fila (quantity = stock del archivo); product_ean_issues; duplicate_product_groups.
   - Al crear o actualizar **master_product** desde normalizado: asignar stock operativo inicial (desde normalizado principal o 0); **registrar stock_change** (previous_quantity, new_quantity, delta, change_mode = replace, source = import).

2. **Categorías**:
   - CategoryController: CRUD; vista árbol (parent_id).
   - Construcción de **category_path_export** (HOJA, INICIO, PADRE1, PADRE2...) al asignar category_id (hoja) a master_product. Job BuildCategoryPaths solo si se usa tabla category_paths.

3. **Productos normalizados y maestros**:
   - NormalizedProductController: index, show (quantity = origen).
   - MasterProductController: index, show (quantity = operativo); Unificar desde duplicados; Aprobar para export.
   - Relación normalized → master vía master_product_suppliers; stock operativo solo en master_products.

4. **Imágenes**: ProductImageController; ImageDownloader; ImageResizer 800x800; Job DownloadProductImage; product_images ligadas a master_product; is_cover para portada.

5. **Incidencias**: ProductEanIssueController; DuplicateProductController (elegir maestro; fusionar; crear master y stock_change inicial si aplica).

6. **Vistas**: normalizados, maestros, categorías, EAN issues, duplicados, imágenes.

**Dependencias**: Fase 3.

---

## FASE 5 – API para Flutter

**Objetivo**: API REST (Sanctum); endpoints para productos (desde master_products), categorías, **stock con modos replace e increment**, sincronización; presencia opcional.

**Entregables**:

1. **Autenticación**: POST /api/login → token Sanctum; auth:sanctum en rutas protegidas.

2. **Endpoints**:
   - GET /api/products (filtros: updated_since, category_id, limit según estrategia de descarga); datos desde **master_products**.
   - GET /api/products/{id}.
   - GET /api/categories (árbol o lista con path).
   - PUT/PATCH /api/products/{id}/stock: aceptar **change_mode** (replace | increment). En replace: body con new_quantity; validar versión; actualizar master_products.quantity; registrar stock_changes (previous_quantity, new_quantity, delta, change_mode, source = app). En increment: body con delta; new_quantity = quantity_actual + delta; actualizar y registrar stock_changes. Devolver 200 o 409 (conflict) según política de concurrencia (documento 04).
   - PUT/PATCH /api/products/{id}/ean, /api/products/{id}/category (con version).
   - POST /api/sync/push: eventos con **change_mode** en payload de stock; procesar y responder applied | conflict por evento.
   - GET /api/sync/pull (updated_since): productos y categorías desde master_products.
   - POST /api/presence (opcional): actualizar app_devices.last_seen_at, user_sessions.

3. **Resources**: ProductResource, CategoryResource (desde master_products y categories).
4. **Validación**: version en actualizaciones; EAN13; quantity 0–9999; change_mode enum.
5. **CORS y rate limiting**.

**Dependencias**: Fase 2 y 4.

---

## FASE 6 – App Flutter STOCK MP

**Objetivo**: App Android/iOS; login; dashboard; listado/ficha producto; escaneo; edición stock (modos replace e increment), EAN y categoría; offline-first; SyncEngine; resolución de conflictos por modo; **estrategia de descarga definida**; **imagen principal offline, secundarias bajo demanda**.

**Entregables**:

1. **Proyecto Flutter**:
   - core/theme (AppColors, AppTextStyles, AppSpacing).
   - core/database (Drift): local_products, local_categories, sync_queue (payload con change_mode), sync_logs, local_scan_events, local_user_session, local_settings; local_product_images para imagen principal cacheada.
   - data/remote: ApiClient; AuthApi, ProductsApi, CategoriesApi, StockApi (con change_mode), SyncApi.
   - core/sync: SyncEngine (envía change_mode; maneja 409 y resolución por modo replace/increment).
   - features: auth, dashboard, products (listado, detalle, slider: principal offline, secundarias bajo demanda), scanner, stock_edit (**replace / increment**), category_edit, sync (pendientes, conflictos), settings.

2. **Estrategia de descarga** (implementar la elegida en doc 04):
   - Qué productos se descargan (por updated_since, categoría, límite o bajo demanda).
   - **Imagen principal** descargada y guardada en local para cada producto en catálogo local (disponible offline).
   - **Imágenes secundarias** descargadas bajo demanda (al abrir galería/slider).

3. **Flujos**: Login; descarga inicial/incremental; escaneo; edición stock (replace o increment) → sync_queue; SyncEngine con change_mode; pantalla conflictos (mantener mío / usar servidor / aplicar delta / descartar según modo).

4. **UI**: responsive; colores MP; componentes grandes y táctiles.

5. **Testing**: compilación Android/iOS; login → listado → detalle → edición stock (replace e increment).

**Dependencias**: Fase 5.

---

## FASE 7 – Monitorización en tiempo real (admin)

**Objetivo**: Dashboard y listados con datos en tiempo real (usuarios activos, escaneos, pendientes, conflictos, estado de importaciones).

**Entregables**: Broadcasting; eventos (UserActivityUpdated, ScanEventCreated, StockChangeCreated, SyncConflictDetected, ImportStatusUpdated); Laravel Echo en dashboard; presencia (heartbeat desde app); vistas de monitor.

**Dependencias**: Fase 5 y 6.

---

## FASE 8 – Exportación PrestaShop y cierre

**Objetivo**: Export CSV **exclusivamente desde master_products** (y tablas ligadas); filtros; validaciones; documentación y hardening.

**Entregables**:

1. **Export**:
   - **PrestaShopCsvExporter**: leer **solo master_products** (y category_path_export, product_images, master_product_suppliers para referencia proveedor). **No usar normalized_products** para el CSV. Correspondencia columnas CSV ↔ master_products según documento 02, sección 2.3. Cantidad = master_products.quantity (stock operativo).
   - ExportPrestaShopController: filtros (aprobados, proveedor, fecha, estado); validación (EAN ok, categoría asignada); descarga archivo.

2. **Validaciones finales**: comprobar EAN y categoría en productos aprobados antes de export; advertencias si aplica.

3. **Documentación técnica**: README; docs de API (endpoints, auth, change_mode); despliegue; variables de entorno.

4. **Hardening**: permisos; logs; rate limiting; backups.

**Dependencias**: Fase 4 (maestros aprobados e imágenes) y Fase 5.

---

## Orden de ejecución recomendado

1. Fase 2 (base backend y admin).  
2. Fase 3 (proveedores e importación; motor por reglas).  
3. Fase 4 (normalización, categorías, imágenes, incidencias, duplicados; stock operativo en master; stock_changes).  
4. Fase 5 (API con change_mode replace/increment).  
5. Fase 6 (App Flutter; estrategia offline; imagen principal offline).  
6. Fase 7 (tiempo real) y Fase 8 (export solo master_products) según prioridad.

Cada fase debe respetar las reglas de stock, categorías, motor sin IA obligatoria, modos replace/increment y export solo desde master_products definidas en 01, 02 y 04.
