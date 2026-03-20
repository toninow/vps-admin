# 3. Estructura de módulos

## 3.1 Panel administrador web (Laravel)

Estructura de carpetas y responsabilidades por módulo. El código vive bajo `app/`, `resources/views/`, `routes/`.

### Motor de importación: inteligencia por reglas (sin IA obligatoria)

El motor “inteligente” se basa en:

- **Reglas** configurables por proveedor (mapeos, transformadores, validadores).
- **Alias** de columnas (supplier_column_aliases o lógica interna) para detectar equivalencias (fabricante, brand, marca → Marca).
- **Transformadores** (trim, uppercase, ean13_pad, capitalización, etc.) aplicados por campo.
- **Validadores** (EAN13, longitud, formato) que marcan incidencias.
- **Matching textual** para categorías (nombre, slug) contra el árbol `categories`.

No se usa IA generativa ni embeddings como parte obligatoria del diseño. Cualquier mejora futura con IA es fase opcional.

### Estructura de carpetas propuesta

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── LoginController.php
│   │   ├── DashboardController.php
│   │   ├── UserController.php
│   │   ├── ProjectController.php
│   │   ├── SupplierController.php
│   │   ├── SupplierImportController.php
│   │   ├── SupplierMappingController.php
│   │   ├── NormalizedProductController.php
│   │   ├── MasterProductController.php
│   │   ├── CategoryController.php
│   │   ├── ProductImageController.php
│   │   ├── ProductEanIssueController.php
│   │   ├── DuplicateProductController.php
│   │   ├── StockController.php
│   │   ├── ExportPrestaShopController.php
│   │   ├── ActivityLogController.php
│   │   ├── MobileIntegrationController.php  (existente)
│   │   └── SettingsController.php
│   └── Middleware/
├── Models/
│   ├── User.php
│   ├── Project.php
│   ├── Supplier.php
│   ├── SupplierImport.php
│   ├── SupplierImportRow.php
│   ├── SupplierFieldMapping.php
│   ├── Category.php
│   ├── NormalizedProduct.php
│   ├── MasterProduct.php
│   ├── MasterProductSupplier.php
│   ├── ProductImage.php
│   ├── ProductEanIssue.php
│   ├── DuplicateProductGroup.php
│   ├── DuplicateProductGroupItem.php
│   ├── ProductCategorySuggestion.php
│   ├── StockChange.php
│   ├── StockScanEvent.php
│   ├── MobileSyncQueue.php
│   ├── AppDevice.php
│   ├── ActivityLog.php
│   └── Setting.php
├── Policies/
├── Services/
│   ├── Import/
│   │   ├── FileReaderFactory.php
│   │   ├── CsvReader.php
│   │   ├── ExcelReader.php
│   │   ├── XmlReader.php
│   │   ├── ColumnDetector.php          # Sugiere mapeos por alias (reglas)
│   │   ├── MappingService.php
│   │   ├── TransformPipeline.php      # Transformadores por campo
│   │   ├── NameGenerator.php
│   │   ├── DescriptionGenerator.php
│   │   ├── CategoryMatcher.php        # Matching textual, no IA
│   │   ├── EanValidator.php
│   │   └── ImportProcessor.php
│   ├── Image/
│   │   ├── ImageDownloader.php
│   │   └── ImageResizer.php
│   ├── Export/
│   │   └── PrestaShopCsvExporter.php  # Solo desde master_products
│   └── Sync/
│       └── MobileSyncService.php      # Aplica stock replace/increment; stock_changes
├── Jobs/
│   ├── ProcessSupplierImport.php
│   ├── ProcessImportRow.php
│   ├── DownloadProductImage.php
│   └── BuildCategoryPaths.php         # Solo si se usa tabla category_paths
├── Listeners/
└── Providers/

resources/views/
├── layouts/
├── auth/
├── dashboard/
├── users/
├── projects/
├── suppliers/
├── imports/
├── products/
│   ├── normalized/
│   ├── master/
│   └── images/
├── categories/
├── ean-issues/
├── duplicates/
├── stock/
├── export/
├── activity-logs/
├── mobile-integrations/
└── settings/
```

### Reglas de negocio en módulos

- **NormalizedProductController**: trabaja sobre capa histórica/intermedia; `quantity` = stock de origen importación.
- **MasterProductController**: verdad de negocio; `quantity` = stock operativo; aprobar para export.
- **ExportPrestaShopController** / **PrestaShopCsvExporter**: leen **solo master_products** (y category_path_export, product_images, master_product_suppliers); nunca normalized_products para el CSV.
- **CategoryController**: árbol con parent_id; al asignar categoría a producto maestro se construye **category_path_export** en orden HOJA, INICIO, PADRE1, PADRE2... y se guarda en master_products.
- **MobileSyncService**: acepta **change_mode** (replace | increment) en actualizaciones de stock; registra en stock_changes (previous_quantity, new_quantity, delta, change_mode, source = app).

### Módulos del panel (lista funcional)

| Módulo | Ruta base | Descripción |
|--------|-----------|-------------|
| Dashboard | /dashboard | Métricas, enlaces a secciones, tiempo real (Fase 7). |
| Usuarios | /users | CRUD, estado, roles, último acceso. |
| Proyectos | /projects | CRUD (existente); app vinculada. |
| Proveedores | /suppliers | CRUD; mapeos; importaciones. |
| Importaciones | /imports | Subida; detección; mapeo (alias/reglas); preview; procesar. |
| Mapeos | /suppliers/{id}/mappings | Origen → destino; transformadores. |
| Productos normalizados | /products/normalized | Listado/detalle; capa intermedia; quantity = origen. |
| Productos maestros | /products/master | Listado/detalle; stock operativo; aprobar; export. |
| Categorías | /categories | Árbol (parent_id); category_path_export para maestros. |
| EAN issues | /ean-issues | Vacíos/inválidos; resolución. |
| Duplicados | /duplicates | Grupos por EAN; elegir maestro; fusionar. |
| Imágenes | /products/master/.../images | Por maestro; portada; orden. |
| Export PrestaShop | /export | Solo desde master_products; filtros. |
| Logs | /activity-logs | Auditoría. |
| Configuración | /settings | Clave-valor. |

### Permisos propuestos (ampliación RolePermissionSeeder)

Añadir: suppliers.*, imports.*, mappings.*, products.view, products.edit, products.export, master_products.*, categories.*, ean.*, duplicates.*, stock.*, mobile.sync, export.view, export.download. Roles: superadmin, admin, editor, stock_user, viewer.

---

## 3.2 App Flutter STOCK MP

### Estructura de carpetas propuesta

```
lib/
├── main.dart
├── app.dart
├── core/
│   ├── theme/
│   ├── database/       # Drift: local_products, local_categories, sync_queue, etc.
│   ├── sync/           # SyncEngine; envío con change_mode (replace/increment)
│   └── network/
├── data/
│   ├── repositories/
│   └── remote/
├── domain/
│   └── entities/
├── features/
│   ├── auth/
│   ├── dashboard/
│   ├── products/
│   ├── scanner/
│   ├── stock/          # UI: elegir replace o increment
│   ├── category_edit/
│   ├── sync/           # Pendientes; conflictos (por modo replace/increment)
│   └── settings/
└── services/
```

### Tablas locales (Drift) – resumen

| Tabla | Propósito |
|-------|-----------|
| local_products | Catálogo descargado (id, ean13, name, quantity, category_id, category_path_export, version, image_cover_path_local, updated_at, ...). |
| local_product_images | Imagen principal cacheada; secundarias bajo demanda. |
| local_categories | Árbol (id, parent_id, name, path_export). |
| sync_queue | action (stock_update, ean_update, category_update, scan); payload con **change_mode** (replace | increment), new_quantity o delta; status. |
| sync_logs | Log de sync. |
| local_scan_events | Escaneos en este dispositivo. |
| local_user_session | Token, user_id, expiry. |
| local_settings | device_id, last_sync_at, criterios de descarga. |

### Estrategia de descarga (recordatorio)

- No descargar todo el catálogo; definir qué productos (por fecha, categoría, límite o bajo demanda).
- Imagen principal obligatoria offline para productos en local; secundarias bajo demanda. Ver documento 04.

### Consistencia con backend

- **Stock**: modos replace e increment; payload con change_mode; resolución de conflictos según modo (documento 04).
- **Producto de negocio**: en backend solo master_products; en app local_products refleja datos de master_products descargados.
- **Export PrestaShop**: solo desde master_products; la app no exporta CSV.

Este documento se complementa con Flujos (04), Pantallas y entidades (05) y Plan de implementación (06).
