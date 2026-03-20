# 5. Entidades principales, relaciones y pantallas

## 5.1 Reglas de negocio (resumen)

- **Stock**: normalized_products.quantity = solo origen importación; master_products.quantity = stock operativo real; stock_changes = historial (previous_quantity, new_quantity, delta, change_mode, source).
- **Categorías**: categories con parent_id; master_products.category_id = categoría hoja; master_products.category_path_export = valor export (HOJA, INICIO, PADRE1, PADRE2...).
- **Export PrestaShop**: solo desde master_products; normalized_products es histórico/intermedio y no interviene en el CSV.
- **App stock**: dos modos, replace e increment; stock_changes guarda change_mode; conflictos resueltos según modo (ver 04).

---

## 5.2 Entidades principales y relaciones (resumen)

```
User
  ├── roles (Spatie)
  ├── supplier_imports (uploader)
  ├── stock_changes
  ├── stock_scan_events
  ├── app_devices
  ├── user_sessions
  └── activity_logs

Project (existente)
  └── project_user
  └── mobile_integrations

Supplier
  ├── supplier_imports
  ├── supplier_field_mappings
  ├── normalized_products
  └── master_product_suppliers (vía normalized)

SupplierImport
  ├── supplier
  ├── user (uploader)
  ├── supplier_import_rows
  ├── normalized_products
  └── mapping_snapshot (json)

NormalizedProduct (histórico/intermedio; quantity = stock origen)
  ├── supplier_import
  ├── supplier
  ├── master_product (nullable)
  ├── product_images (origen)
  ├── product_ean_issues
  ├── duplicate_product_group_items
  └── product_category_suggestions

MasterProduct (verdad de negocio; quantity = stock operativo)
  ├── category (hoja)
  ├── category_path_export (HOJA, INICIO, PADRE1...)
  ├── master_product_suppliers
  ├── product_images
  ├── product_ean_issues (si se referencian a maestro)
  ├── stock_changes (previous_quantity, new_quantity, delta, change_mode)
  ├── stock_scan_events
  └── normalized_products (vía master_product_suppliers)

Category (parent_id)
  ├── parent / children (self)
  ├── master_products (category_id = hoja)
  └── product_category_suggestions

StockChange
  ├── master_product
  ├── user_id
  ├── previous_quantity, new_quantity, delta
  ├── change_mode (replace | increment)
  └── source (import | app | admin)

ProductImage → master_product; is_cover = imagen principal (offline en app)
ProductEanIssue → normalized_product / master_product
DuplicateProductGroup → master_product (elegido); items → normalized_products
```

---

## 5.3 Pantallas principales – Panel administrador web

| Pantalla | Ruta | Descripción breve |
|----------|------|-------------------|
| Login | /login | Email, contraseña, recordarme; marca MP. |
| Dashboard | /dashboard | Cards métricas; enlaces a secciones; tiempo real (Fase 7). |
| Usuarios listado | /users | Tabla; filtros; Ver/Editar. |
| Usuario detalle/edición | /users/{id} | Datos, roles, proyectos; Editar, Volver. |
| Proyectos listado | /projects | Cards o tabla; Ver, Editar, Nuevo. |
| Proyecto detalle/edición | /projects/{id} | Datos; usuarios; integraciones móviles. |
| Proveedores listado | /suppliers | Tabla; Ver, Editar, Mapeos, Importaciones. |
| Proveedor detalle/mapeos | /suppliers/{id} | Datos; Mapeos (origen → destino, transformadores). |
| Importaciones listado | /imports | Tabla; Nueva importación. |
| Nueva importación | /imports/create | Subida; proveedor; detección columnas (alias/reglas). |
| Mapeo y preview | /imports/{id}/mapping, /imports/{id}/preview | Columnas origen → destino; preview; Procesar. |
| Importación detalle | /imports/{id} | Estado; enlace a productos normalizados. |
| Productos normalizados | /products/normalized | Filtros; tabla; quantity = stock origen; Ver. |
| Producto normalizado detalle | /products/normalized/{id} | Campos; incidencias EAN; categoría sugerida; enlace a maestro. |
| Productos maestros | /products/master | Filtros; quantity = stock operativo; Ver, Aprobar. |
| Producto maestro detalle | /products/master/{id} | Datos; proveedores unidos; imágenes; stock operativo; historial stock_changes; Aprobar. |
| Árbol de categorías | /categories | Vista árbol (parent_id); CRUD; category_path_export para maestros. |
| EAN vacíos/inválidos | /ean-issues | Lista; filtrar; marcar resuelto. |
| Duplicados por EAN | /duplicates | Grupos; elegir maestro; Fusionar. |
| Imágenes (por maestro) | /products/master/{id}/images | Lista; orden; portada; reordenar. |
| **Export PrestaShop** | **/export** | **Solo desde master_products.** Filtros (aprobados, proveedor, fecha); Generar CSV; Descargar. |
| Integración app STOCK MP | /mobile-integrations | (Existente) proyectos, app, versión. |
| Logs de actividad | /activity-logs | Tabla auditoría; filtros. |
| Configuración | /settings | Clave-valor; almacén, regla impuestos. |

---

## 5.4 Pantallas principales – App Flutter STOCK MP

| Pantalla | Descripción breve |
|----------|-------------------|
| Login | Email, contraseña; API; guardar token en local_user_session. |
| Dashboard / Home | Estado online/offline; "Cambios pendientes"; Escanear, Buscar, Pendientes, Incidencias. |
| Escáner | Cámara o entrada manual EAN; búsqueda en local_products; → ficha. |
| Búsqueda manual | Campo búsqueda; lista productos; → ficha. |
| Ficha de producto | Slider imágenes (principal offline; secundarias bajo demanda); nombre, EAN, categoría; **stock actual**; Editar stock (replace/increment), Editar EAN, Editar categoría. |
| Edición stock | **Modo replace** (valor final) o **Modo increment** (+/- delta); 0–9999; validación entero; guardar → sync_queue (change_mode, new_quantity o delta). |
| Edición EAN | Campo EAN13; validación 13 dígitos; guardar → sync_queue. |
| Edición categoría | Selector categoría (árbol/ruta); guardar → sync_queue. |
| Productos escaneados por mí | Lista local_scan_events; → ficha. |
| Pendientes de sincronizar | Lista sync_queue pending; indicador "Sincronizando". |
| Incidencias | EAN vacíos/inválidos; → ficha y editar EAN. |
| Conflictos | Eventos en conflict; resolución según modo (replace: mantener mío / usar servidor; increment: aplicar delta / descartar). |
| Ajustes | Cerrar sesión; device_id; última sincronización; borrar caché (opcional). |

---

## 5.5 Sistema de diseño (recordatorio)

- **Colores**: #E6007E (principal), #FFFFFF (fondo), #555555 (texto/gris).
- **Panel web**: Tailwind; .btn-primary, .btn-secondary, .card-hover.
- **Flutter**: AppColors, AppTextStyles, AppSpacing, ThemeData; sin hardcodear hex.

Este documento cierra la definición de entidades y pantallas; el orden de implementación se detalla en el Plan de implementación (06).
