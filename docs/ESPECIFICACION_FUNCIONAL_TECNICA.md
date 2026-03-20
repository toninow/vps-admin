# Especificación funcional y técnica del sistema Musical Princesa

Documento definitivo y cerrado del sistema integral: panel administrador web, motor de importación y normalización, exportación CSV PrestaShop y app Flutter STOCK MP. **No incluye código ni implementación;** define únicamente qué se construye y cómo debe comportarse.

---

## 1. Resumen ejecutivo del sistema

### Qué es el sistema

Sistema integral para **Musical Princesa** que centraliza la gestión de catálogo de productos procedentes de múltiples proveedores (CSV, Excel, XML), su normalización y validación, el control de stock operativo en tienda mediante una app móvil, y la exportación final a PrestaShop. El mismo backend sirve al panel web de administración y a la app móvil STOCK MP; los usuarios y el catálogo maestro son compartidos.

### Qué problemas resuelve

- **Fragmentación de datos**: múltiples formatos y nombres de columnas por proveedor se unifican en un modelo común y en un producto maestro por referencia (EAN o decisión manual).
- **Stock desalineado**: el stock importado (origen) se separa del stock operativo real; solo el operativo se usa en tienda y en la exportación a PrestaShop.
- **Trabajo en tienda sin red**: la app permite escanear, buscar, consultar y editar stock, EAN y categoría offline, con sincronización posterior.
- **Trazabilidad**: historial de cambios de stock, escaneos, incidencias de EAN y duplicados, con registro de usuario y origen (importación, app, admin).
- **Exportación controlada**: el CSV para PrestaShop se genera solo desde productos maestros aprobados, con formato y reglas de negocio fijas.

### Qué bloques lo componen

1. **Panel administrador web** (Laravel, Blade, Tailwind): usuarios, permisos, proveedores, importaciones, mapeos, productos normalizados y maestros, categorías, imágenes, incidencias, duplicados, exportación PrestaShop, monitorización y logs.
2. **Motor de importación y normalización** (dentro del mismo Laravel): lectura de archivos (CSV, Excel, XML), detección y mapeo de columnas por reglas y alias, transformación a formato PrestaShop, validación (EAN, categorías), creación de productos normalizados y posterior unificación en productos maestros.
3. **API REST** (Laravel): autenticación compartida con el panel, catálogo (maestros), stock, categorías, sincronización (push/pull) y presencia; consumida por la app Flutter.
4. **App Flutter STOCK MP** (Android/iOS, móvil y tablet): login, escáner de códigos de barras, buscador potente, ficha de producto, edición de stock (replace/increment), EAN y categoría final, modo offline-first con BD local y cola de sincronización, historial del usuario y resolución de conflictos.

---

## 2. Alcance funcional cerrado

### Qué incluye exactamente el sistema

- **Panel admin**: login, usuarios, roles y permisos (RBAC), proyectos (existente), proveedores (CRUD y mapeos), importaciones (subida, mapeo, preview, procesamiento por jobs), productos normalizados (listado, detalle, filtros), productos maestros (listado, detalle, unificación, aprobación para export), categorías (árbol con parent_id, asignación a maestros, category_path_export), imágenes (descarga, 800x800, orden, portada), incidencias EAN (vacíos/inválidos, resolución), duplicados por EAN (grupos, elegir maestro, fusionar), exportación CSV PrestaShop (solo desde master_products, filtros), logs de actividad, configuración (clave-valor), monitorización en tiempo real (usuarios activos, escaneos, pendientes de sync, conflictos, estado de importaciones).
- **Motor de importación**: soporte CSV, Excel, XML; detección de columnas por alias; mapeo origen→destino por proveedor; transformadores (trim, uppercase, ean13_pad, capitalización, etc.); validación EAN13; generación de Nombre* (MARCA - MODELO - NOMBRE) y Descripción (CARACTERÍSTICAS); matching textual de categorías contra árbol maestro; creación de normalized_products (quantity = stock origen); detección de duplicados por EAN e incidencias EAN; descarga de imágenes a servidor.
- **Exportación**: generación de CSV con columnas exactas de PrestaShop desde master_products (y category_path_export, product_images, master_product_suppliers); cantidad = master_products.quantity; filtros por aprobados, proveedor, fecha, estado.
- **App STOCK MP**: login (mismos usuarios que el panel), escáner con cámara (EAN13 prioritario, opcionalmente UPC/EAN8), buscador manual por múltiples campos, ficha de producto (imagen principal offline, secundarias bajo demanda), edición de stock (modos replace e increment), edición de EAN, edición de categoría final, historial de productos escaneados por el usuario, pendientes de sincronización, incidencias y conflictos, modo offline-first con catálogo ligero local y sincronización incremental, detección de cambios del servidor y resolución de conflictos según modo de stock.

### Qué no incluye por ahora

- Integración directa con PrestaShop (solo exportación CSV para importación manual o por otro sistema).
- Facturación, pedidos a proveedores ni gestión de compras.
- IA generativa o embeddings como requisito del motor de importación.
- Múltiples almacenes o ubicaciones dentro del almacén (un único almacén “CARPETANA”).
- Gestión de precios desde la app (solo stock, EAN y categoría).
- Soporte de otros formatos de código de barras distintos de EAN13 (y opcionalmente UPC/EAN8) para la lógica de negocio del EAN final.

### Qué se deja para futuras fases

- Sugerencias o mejoras del motor de importación mediante IA (opcional, no obligatorio).
- Posible integración vía API con PrestaShop para subida automática del CSV.
- Ampliación de tipos de código de barras si el negocio lo exige.
- Múltiples idiomas en la app o en el panel (por defecto español).

---

## 3. Arquitectura general definitiva

- **Backend**: monolito Laravel (mismo despliegue) que incluye aplicación web del panel, API REST, colas (jobs) para importaciones y descarga de imágenes, y motor de importación (servicios, transformadores, validadores). Base de datos MySQL/MariaDB. Autenticación web (sesión) y API (Sanctum) sobre la misma tabla de usuarios.
- **Panel admin**: aplicación web con Blade y Tailwind; servida por el mismo Laravel; acceso por navegador; siempre online; fuente de verdad para configuración, usuarios, permisos, proveedores, importaciones, productos maestros y exportación.
- **Motor de importación**: conjunto de servicios y jobs dentro del backend (FileReaderFactory, lectores por tipo, ColumnDetector, MappingService, TransformPipeline, NameGenerator, DescriptionGenerator, EanValidator, CategoryMatcher, ImportProcessor); no es un microservicio separado. Configuración por proveedor (supplier_field_mappings, supplier_column_aliases) en base de datos.
- **API**: rutas bajo el mismo Laravel (api.php); middleware auth:sanctum; endpoints para login, productos (maestros), categorías, stock (replace/increment), sincronización (push/pull) y presencia; respuestas JSON; soporte de sincronización incremental (updated_since) y paginación para catálogo.
- **App Flutter STOCK MP**: cliente nativo Android e iOS; arquitectura por capas (core, data, domain, features); base de datos local (Drift/SQLite) para catálogo ligero, cola de sync, sesión y configuración; SyncEngine que envía eventos pendientes y aplica respuestas (éxito/conflicto); no descarga los 145.000 productos en formato pesado; descarga catálogo maestro ligero (campos necesarios para listado, búsqueda y ficha) e imagen principal por producto descargado; imágenes secundarias bajo demanda.
- **Sync offline-first**: la app opera sobre datos locales; las ediciones (stock, EAN, categoría) se persisten en BD local y se encolan; cuando hay conexión, el SyncEngine envía la cola al backend; el backend actualiza master_products y stock_changes y devuelve éxito o conflicto; la app actualiza estado local (synced/conflict) y, si hay conflicto, presenta opciones de resolución según el modo (replace/increment). La detección de cambios del servidor se hace por versión (version o updated_at) en cada producto y por sincronización incremental (pull desde last_sync_at).

---

## 4. Reglas de negocio definitivas

- **Stock**: (1) normalized_products.quantity = solo stock de origen/importación; no es stock operativo. (2) master_products.quantity = stock operativo real; es la única cantidad usada para negocio, app y exportación CSV. (3) Toda modificación de stock (importación, app, admin) actualiza master_products.quantity y genera un registro en stock_changes con previous_quantity, new_quantity, delta, change_mode (replace | increment) y source (import | app | admin). (4) La exportación CSV usa exclusivamente master_products.quantity.
- **EAN**: (1) EAN13 = 13 dígitos numéricos; se valida estrictamente; ceros a la izquierda se conservan. (2) Productos con EAN vacío o inválido se registran en product_ean_issues y pueden editarse desde la app (EAN vacío o incorrecto). (3) En la app solo se permite editar EAN cuando está vacío o es incorrecto; el escáner prioriza EAN13; UPC/EAN8 pueden usarse para localizar producto pero el EAN final en el sistema es EAN13 o vacío.
- **Categorías**: (1) categories tiene parent_id (null = raíz). (2) master_products.category_id = categoría hoja/final. (3) master_products.category_path_export = cadena lista para PrestaShop en el orden: HOJA, INICIO, PADRE_1, PADRE_2... (4) La categoría principal para PrestaShop es la primera de la lista (la hoja).
- **Productos maestros**: (1) Un producto maestro puede agrupar varios normalizados (varios proveedores) vía master_product_suppliers. (2) El producto maestro es la única entidad de “producto final” para negocio y exportación. (3) normalized_products es capa intermedia/histórica; no se usa para generar el CSV.
- **Exportación**: (1) El CSV final se genera exclusivamente desde master_products (y category_path_export, product_images, master_product_suppliers para referencia proveedor). (2) Columnas del CSV según correspondencia definida en documentación (Activo, Nombre*, Resumen, Descripción, Categorias, precios, referencias, Proveedor, Marca, Almacen, EAN13, Cantidad, Etiquetas, URLs imágenes). (3) Solo se exportan productos aprobados (is_approved); filtros adicionales por proveedor, fecha, estado.
- **Incidencias**: (1) product_ean_issues registra EAN empty o invalid por producto (normalizado o maestro). (2) Resolución: el usuario (admin o app) corrige el EAN o marca resuelto; opcionalmente resolution_notes. (3) Listas dedicadas en admin (EAN vacíos, EAN inválidos) y en app (incidencias) para guiar la corrección.
- **Duplicados**: (1) duplicate_product_groups agrupa productos que comparten el mismo EAN13. (2) El admin elige un producto maestro por grupo y fusiona; se crean/actualizan master_products y master_product_suppliers. (3) No se unifica automáticamente por similitud de nombre/marca sin confirmación.
- **Imágenes**: (1) Descarga desde URL del proveedor a servidor (public/images-prestashop/); redimensionado 800x800 manteniendo proporción; fondo neutro si aplica. (2) product_images ligadas a master_product; position e is_cover (portada). (3) En la app, imagen principal (portada) debe estar disponible offline para los productos en catálogo local; secundarias bajo demanda.

---

## 4.1 Decisiones operativas finales (congeladas)

**1. Estrategia offline definitiva**

- **Primera carga**: catálogo maestro ligero completo (todos los productos en formato ligero: id, ean13, name, brand, reference, supplier_reference, category_path_export, quantity, version, updated_at, search_keywords_normalized, etc.; sin descargar de inicio todo el catálogo en formato pesado).
- **Imagen principal**: caché progresiva (se descargan en background tras la primera carga del catálogo ligero, o bajo demanda al abrir ficha; una vez descargada queda disponible offline).
- **Imágenes secundarias**: bajo demanda (al abrir galería/slider en la ficha).
- **Sincronización incremental**: por `updated_since`; el backend devuelve solo productos (y categorías) modificados desde esa fecha.
- **Ficha completa**: bajo demanda (al abrir un producto se puede solicitar la ficha completa si no estaba en caché; el listado y la búsqueda trabajan sobre el catálogo ligero).

**2. Política final de stock**

- **Replace**: conflicto estricto con **409** si cambió la versión en el servidor; el backend no aplica el nuevo valor; devuelve 409 con quantity y version actuales; la app muestra pantalla de resolución (mantener mi cambio / usar valor del servidor).
- **Increment**: **aplicar delta sobre el valor actual del servidor** y registrar en stock_changes (previous_quantity, new_quantity = current + delta, delta, change_mode = increment, source = app); el backend devuelve 200 con nuevo quantity y version; no se devuelve 409 por cambio de versión en increment (el delta se aplica siempre sobre el valor actual).

**3. Buscador local definitivo**

- **Campos indexados localmente**: nombre (name), marca (brand), referencia (reference o id interno), referencia proveedor principal (supplier_reference del maestro o del proveedor principal), EAN13 (ean13), category_path_export, y **palabras clave normalizadas** (search_keywords_normalized: texto derivado de nombre, marca, descripción, etiquetas, normalizado a minúsculas/sin acentos para búsqueda insensible).
- La búsqueda debe funcionar **rápida con 145.000 productos**: índices en BD local (SQLite/Drift) sobre ean13, name, brand, supplier_reference, category_path_export y sobre search_keywords_normalized (o FTS); límite de resultados por consulta; sin escaneos completos de tabla sin índice.

---

## 5. Definición completa del panel administrador web

**Módulos**: Dashboard, Usuarios, Roles y permisos (Spatie), Proyectos, Proveedores, Importaciones, Mapeos de proveedor, Productos normalizados, Productos maestros, Categorías, Imágenes (por producto maestro), Incidencias EAN, Duplicados por EAN, Exportación PrestaShop, Integración app STOCK MP (config), Monitorización tiempo real, Logs de actividad, Configuración.

**Pantallas (resumen)**: Login; Dashboard (métricas, enlaces, widgets tiempo real); Usuarios (listado, detalle, edición); Proyectos (listado, detalle, edición); Proveedores (listado, detalle, mapeos); Importaciones (listado, nueva importación, mapeo, preview, detalle de importación); Productos normalizados (listado con filtros, detalle); Productos maestros (listado con filtros, detalle, aprobar); Categorías (árbol, CRUD); EAN issues (listado, filtrar, marcar resuelto); Duplicados (grupos, elegir maestro, fusionar); Imágenes por maestro (listado, orden, portada); Export PrestaShop (filtros, generar CSV, descargar); Mobile integrations; Activity logs; Settings.

**Acciones permitidas**: definidas por permisos (users.*, projects.*, suppliers.*, imports.*, mappings.*, products.view|edit, master_products.*, categories.*, ean.*, duplicates.*, stock.*, export.*, logs.view, settings.*). Roles: superadmin (todo), admin, editor, stock_user (limitado a stock/EAN/categoría en app o vistas restringidas), viewer (solo lectura).

**Métricas en dashboard**: total usuarios, proyectos, importaciones recientes, productos maestros, incidencias EAN, duplicados pendientes; en tiempo real (cuando exista): usuarios activos, escaneos por usuario, pendientes de sync, conflictos, estado de importaciones.

**Validaciones**: en importación (mapeo obligatorio para campos clave, preview antes de procesar); en productos maestros (EAN válido o vacío explícito para aprobar si se desea); antes de export (opcional: aviso si hay aprobados con EAN inválido o sin categoría).

**Revisión de productos**: en admin se revisan productos normalizados (lista, filtros, detalle) y se unifican en maestros (desde duplicados o manualmente); se asignan categoría hoja y category_path_export; se aprueban maestros para exportación; la app puede además revisar/editar stock, EAN y categoría en tienda.

**Exportación**: pantalla Export PrestaShop con filtros (aprobados, proveedor, fecha, estado); generación de CSV solo desde master_products; descarga del archivo; sin uso de normalized_products en el CSV.

---

## 6. Definición completa de la app Flutter STOCK MP

**Módulos**: Auth (login), Dashboard (estado conexión, pendientes, accesos rápidos), Escáner (cámara), Buscador (manual), Productos (listado/ficha), Edición stock (replace/increment), Edición EAN, Edición categoría, Historial (productos escaneados por mí), Pendientes de sync, Incidencias, Conflictos, Ajustes.

**Pantallas**: Login; Dashboard; Escáner (cámara + resultado); Buscador (campo búsqueda + resultados); Listado productos (si aplica); Ficha producto (imágenes, datos, botones editar stock/EAN/categoría); Edición stock (selector modo replace/increment, valor o delta, validación 0–9999); Edición EAN (campo EAN13, validación); Edición categoría (selector categoría/ruta); Productos escaneados por mí; Pendientes de sincronizar; Incidencias (EAN vacíos/inválidos); Conflictos (resolución por modo); Ajustes (sesión, device_id, última sync).

**Flujo de usuario**: Login → Dashboard; desde Dashboard puede ir a Escáner, Buscador, Pendientes, Incidencias o Ajustes; escaneo o búsqueda llevan a Ficha producto; desde Ficha puede editar stock (replace o increment), EAN o categoría; los cambios se guardan en local y en cola de sync; al haber conexión se sincronizan; si hay conflicto se muestra pantalla de resolución.

**Permisos**: los mismos usuarios que el panel; el rol/permiso determina si el usuario puede usar la app y qué puede hacer (editar stock, EAN, categoría); no hay permisos distintos por pantalla dentro de la app más allá de “puede usar app” y “puede editar”.

**Edición de stock**: dos modos. Replace: usuario introduce cantidad final (0–9999); se envía new_quantity y change_mode=replace. Increment: usuario suma o resta (delta); se envía delta y change_mode=increment. Validación: solo enteros; máximo 9999. Al guardar se actualiza quantity en local y se encola evento con product_id, version, new_quantity o delta, change_mode.

**Edición de EAN**: solo cuando el producto tiene EAN vacío o inválido (o según política de negocio). Campo EAN13; validación 13 dígitos; al guardar se encola evento ean_update.

**Edición de categoría**: selector de categoría final (hoja); se muestra ruta completa; al guardar se encola evento category_update con category_id (y opcionalmente category_path_export).

**Historial del usuario**: lista “Productos escaneados por mí” basada en eventos de escaneo registrados en el dispositivo (y/o enviados al servidor stock_scan_events); cada ítem permite abrir la ficha del producto; los eventos de escaneo se guardan localmente y se envían en sync para que el admin pueda ver quién escaneó qué.

---

## 7. Definición completa del escáner con cámara

**Comportamiento general**: la app usa la cámara del dispositivo para capturar códigos de barras. El tipo prioritario es **EAN13**. Opcionalmente se soportan **UPC-A, EAN8** u otros para localizar el producto; si el código no es EAN13, se convierte o se busca por ese valor en un campo auxiliar si existe, pero el EAN final del producto en el sistema sigue siendo EAN13 o vacío; no se guarda UPC como EAN13. La lógica de negocio (duplicados, incidencias, exportación) se basa en EAN13.

**Flujo exitoso (coincidencia exacta)**: (1) Usuario abre escáner y enfoca el código. (2) Se lee el valor (EAN13 o equivalente). (3) Se busca en catálogo local (por ean13 u otro índice si hay conversión UPC→EAN). (4) Si hay un único producto con ese EAN: se registra evento de escaneo (product_id, user_id, device_id, scanned_at); se muestra ficha del producto con feedback de éxito (pantalla de resultado o transición directa a ficha con mensaje breve “Producto encontrado”); opcional feedback auditivo (vibración corta o sonido). (5) El producto queda en “historial de escaneados por mí”.

**Flujo sin coincidencia**: (1) Código leído no existe en catálogo local. (2) Si hay conexión: se puede consultar al backend si existe por EAN; si existe en servidor pero no en local, se puede descargar ese producto y abrirlo. (3) Si no existe en servidor o no hay conexión: se muestra mensaje claro “Producto no encontrado” y opciones: “Buscar manualmente”, “Intentar de nuevo”, “Añadir a incidencias” (opcional). (4) No se crea producto desde el escáner; solo se localiza producto existente. (5) Feedback visual (y opcionalmente auditivo) distinto al de éxito (ej. color o icono distinto, sin vibración de éxito).

**Flujo cuando el código corresponde a un producto con EAN vacío en el sistema**: (1) Puede darse si el proveedor envió EAN y se escaneó el mismo código en tienda pero en master_products el EAN estaba vacío (pendiente de asignar). (2) Comportamiento deseado: si el código escaneado coincide con la referencia o con otro identificador del producto (ej. referencia proveedor) y ese producto tiene EAN vacío, se considera coincidencia y se abre la ficha; opcionalmente se sugiere “¿Asignar este EAN al producto?” para que el usuario confirme y se registre como edición de EAN. (3) Para ello, en local debe poder buscarse por referencia o por un campo “código escaneado pendiente” si se implementa; o la búsqueda exacta por EAN devuelve solo productos con ese Ean13; los productos con EAN vacío se localizan por referencia/búsqueda manual. Se deja definido: “Si el código escaneado es EAN13 y existe un producto con ese ean13 en el sistema, se abre la ficha. Si no hay producto con ese ean13 pero el usuario busca por otro medio y asigna el EAN desde la ficha, se registra como edición EAN.”

**Flujo con múltiples coincidencias o conflicto**: (1) En el modelo actual no hay dos master_products con el mismo EAN13 (ean13 es único en master_products). (2) Si en el futuro hubiera duplicados no fusionados por error: la app mostraría “Varios productos con este código; elija uno” (lista) o se tomaría el primero y se registraría incidencia. (3) Conflicto de sincronización (otro usuario modificó el producto): no es un “múltiple por EAN”; es conflicto de versión al editar stock/EAN/categoría; se resuelve en la pantalla de conflictos (ver sección 9).

**Feedback visual y auditivo**: (1) Éxito: transición a ficha + mensaje breve; opcional vibración corta o sonido de éxito. (2) No encontrado: mensaje “Producto no encontrado” + opciones; opcional sonido o vibración distinta. (3) Error de cámara o lectura: mensaje claro y opción reintentar.

**Escaneo offline**: (1) La búsqueda tras escanear se hace contra el catálogo local (BD Drift). (2) Para que el escaneo sea útil offline, los productos que el usuario pueda necesitar deben estar en local (según estrategia de descarga: por categoría, por actualización reciente, por “ya consultados/escaneados”, o híbrido). (3) Se registra el evento de escaneo localmente (local_scan_events o equivalente en sync_queue con action=scan); al volver conexión se envían al servidor (stock_scan_events). (4) Si el código no está en local y no hay red, se muestra “Producto no encontrado” y se sugiere búsqueda manual o reintento más tarde.

**Escaneo online**: (1) Tras leer el código se busca primero en local. (2) Si no hay resultado en local, se puede consultar la API (por ejemplo GET por ean13); si el servidor devuelve el producto, se puede descargar ese producto (y su imagen principal) al catálogo local y abrir la ficha. (3) El evento de escaneo se guarda en local y se envía en la siguiente sync.

**Datos mínimos en local para escaneo rápido**: para cada producto en catálogo local: id (servidor), ean13, nombre (y opcionalmente referencia, marca) para mostrar en resultado; imagen principal (ruta local) para la ficha. La búsqueda por EAN debe estar indexada en la BD local (índice en ean13) para que con ~145k productos la consulta sea inmediata sobre el subconjunto descargado.

**Registro del evento de escaneo**: (1) En app: se crea registro en tabla local (local_scan_events o ítem en sync_queue con action=scan) con product_id, user_id, device_id, scanned_at (timestamp local). (2) Al sincronizar: el backend recibe el evento y crea/actualiza stock_scan_events (master_product_id, user_id, device_id, scanned_at). (3) El admin puede ver en monitorización “quién escaneó qué y cuándo”.

**Historial del usuario**: la sección “Productos escaneados por mí” lista los escaneos del usuario en este dispositivo (y opcionalmente los del servidor para este usuario); cada ítem enlaza a la ficha del producto; orden por scanned_at descendente; paginación o límite reciente (ej. últimos 100).

---

## 8. Definición completa del buscador

**Campos buscables**: nombre (o título), marca (brand), referencia (id o referencia interna), referencia proveedor principal (supplier_reference), EAN13, nombre del proveedor (vía master_product_suppliers), palabras clave (en nombre, descripción o resumen), categoría (nombre o ruta via category_path_export), etiquetas (tags). En la app se busca sobre el catálogo maestro ligero local.

**Buscador local definitivo (congelado)**: (1) **Campos indexados localmente**: nombre (name), marca (brand), referencia (reference), referencia proveedor principal (supplier_reference), EAN13 (ean13), category_path_export, y **palabras clave normalizadas** (search_keywords_normalized: texto derivado de nombre, marca, descripción, etiquetas, normalizado a minúsculas/sin acentos para búsqueda insensible). (2) La búsqueda debe funcionar **rápida con 145.000 productos**: índices en BD local sobre ean13, name, brand, supplier_reference, category_path_export y sobre search_keywords_normalized (o FTS); límite de resultados por consulta; sin escaneos completos sin índice. (3) Coincidencias: exactas primero (EAN13); luego parciales por nombre/marca/referencia/keywords. (4) Orden: relevancia y luego por nombre o updated_at. (5) Límite de resultados (ej. 50–100) para mantener la UI ágil.

**Estrategia de búsqueda online**: (1) Si hay conexión y el usuario lo desea, se puede enviar la consulta al backend (endpoint de búsqueda con query y filtros). (2) El backend busca en master_products (y relaciones) con índices SQL (FULLTEXT o LIKE según motor); paginación (ej. 20 por página). (3) Los resultados pueden incluir productos que no estaban en local; al abrir uno se puede descargar ese producto (y su imagen principal) a local para uso posterior offline. (4) Orden en servidor: relevancia y/o nombre, updated_at; mismo criterio de coincidencias parciales.

**Índices locales necesarios (definitivos)**: (1) Índice único o principal en ean13. (2) Índices en name, brand, supplier_reference (referencia proveedor principal), category_path_export. (3) Índice o FTS sobre search_keywords_normalized (palabras clave normalizadas) para búsqueda rápida con 145k productos. (4) La app almacena en el catálogo ligero el campo search_keywords_normalized generado en servidor (o en app al recibir el producto) para búsquedas insensibles a mayúsculas/acentos.

**Comportamiento con ~145.000 productos**: (1) La app no mantiene 145k productos completos en local por defecto; mantiene un subconjunto (catálogo ligero: id, ean13, name, brand, supplier_reference, category_id, category_path_export, quantity, version, image_cover_path, etc.) según estrategia (por categoría, por actualización, por “visitados/escaneados”, o límite por ejemplo 20k–30k más recientes). (2) La búsqueda offline es sobre ese subconjunto; por tanto el tiempo de respuesta depende del tamaño del subconjunto (con índices debe ser < 100–200 ms). (3) La búsqueda online puede abarcar los 145k en el servidor; el backend debe usar índices (FULLTEXT en MySQL/MariaDB sobre name, brand, etc.) y paginación estricta para no devolver miles de filas. (4) Búsqueda incremental: en la UI se puede ir refinando (el usuario escribe y se lanza búsqueda con debounce de 300–400 ms); no cargar todos los resultados de golpe; “infinite scroll” o “cargar más” en resultados online. (5) Evitar depresión de la tortuga: índices en servidor y en local; límite de filas por consulta; no hacer LIKE '%...%' sin límite sobre 145k filas sin índice; considerar FTS donde aplique.

---

## 9. Definición completa del offline-first

**Qué datos se descargan en primera carga (sincronización inicial) — estrategia definitiva**: (1) Metadatos de sesión y configuración (user, device_id, last_sync_at). (2) **Catálogo maestro ligero completo**: todos los productos en formato ligero (id, ean13, name, brand, reference, supplier_reference, category_id, category_path_export, quantity, version, updated_at, search_keywords_normalized); no se descarga de inicio el catálogo en formato pesado (descripción larga, todas las imágenes, etc.). (3) Árbol de categorías (o lista plana con path) necesario para selector y rutas. (4) **Imagen principal**: caché progresiva (descarga en background tras la carga del catálogo ligero, o bajo demanda al abrir ficha); una vez descargada queda disponible offline. (5) **Ficha completa** (descripción, todos los campos): bajo demanda al abrir el producto.

**Qué queda en caché local**: (1) Tabla local_products (o equivalente) con el subconjunto de productos descargados. (2) Tabla local_categories. (3) Imágenes principales ya descargadas (almacenamiento de archivos o rutas en BD local). (4) Cola sync_queue (eventos pendientes de envío). (5) local_scan_events (o eventos scan en sync_queue). (6) local_user_session (token, user_id, expiry). (7) local_settings (device_id, last_sync_at, criterios de descarga). (8) sync_logs (opcional, para depuración).

**Qué se descarga bajo demanda**: (1) Imágenes secundarias del producto (al abrir galería/slider en la ficha). (2) Productos que no estaban en el subconjunto inicial pero que el usuario abre vía búsqueda online o por escaneo (cuando el backend devuelve un producto que no estaba en local). (3) Actualizaciones incrementales: al sincronizar, el backend devuelve productos modificados desde last_sync_at (y opcionalmente eliminaciones o desactivaciones); la app actualiza o elimina registros locales.

**Funcionamiento de sync_queue**: (1) Cada edición (stock, EAN, categoría) y cada escaneo generan un evento en la cola local con: action (stock_update, ean_update, category_update, scan), product_id, user_id, device_id, payload (new_quantity o delta, change_mode, version, etc.), status (pending). (2) El SyncEngine, cuando detecta conexión válida al backend, toma eventos con status=pending en orden (created_at), los envía al API (push) y actualiza status a syncing; según respuesta los marca como synced o conflict (o failed). (3) En conflict, el evento queda en estado conflict y se muestra en pantalla Conflictos para que el usuario resuelva (mantener mío, usar servidor, aplicar delta, etc.). (4) Los eventos ya sincronizados pueden conservarse un tiempo para auditoría o purgarse según política.

**Detección de cambios del servidor**: (1) En pull (sincronización incremental): la app envía last_sync_at (o version mínima); el backend devuelve master_products (y categorías) con updated_at > last_sync_at (o version mayor). (2) La app actualiza o inserta esos registros en local y actualiza last_sync_at. (3) Para cada producto se guarda version (o updated_at); al editar en la app se envía esa version; si el servidor tiene una version mayor, hay conflicto (en replace) o se aplica delta sobre valor actual (en increment, según decisión de implementación).

**Detección de si otro usuario modificó el producto**: (1) El backend mantiene version en master_products; cada actualización la incrementa. (2) Al aplicar un cambio desde la app, el backend compara la version enviada con la actual. (3) **Replace (política definitiva)**: si la versión cambió, el backend devuelve **409 Conflict** (conflicto estricto); no aplica el nuevo valor; devuelve quantity y version actuales; la app muestra pantalla de resolución (mantener mi cambio / usar valor del servidor). (4) **Increment (política definitiva)**: el backend **aplica siempre el delta sobre el valor actual del servidor** y registra stock_changes; devuelve 200 con nuevo quantity y version; no se devuelve 409 por cambio de versión. (5) En la ficha se puede mostrar aviso si el servidor tiene version más reciente y ofrecer “Actualizar”.

**Resolución de conflictos**: (1) Stock replace: solo hay conflicto cuando el backend devuelve 409; usuario elige “Mantener mi cambio” (reintento con última version) o “Usar valor del servidor”. (2) Stock increment: no hay conflicto de versión; el backend aplica delta sobre valor actual y registra stock_changes. (3) EAN y categoría: si el backend devuelve 409, se muestra valor local vs remoto; usuario elige “Mantener el mío” o “Usar el del servidor”. (4) Tras resolver, el evento se marca como resuelto y la vista local se actualiza.

---

## 10. Definición del flujo de importación a exportación

1. **Subida de archivo de proveedor**: El usuario del panel sube un archivo (CSV, Excel o XML). El sistema lo guarda en storage, crea un registro de importación (supplier_import) con estado “uploaded” y detecta el tipo por extensión y contenido. Se leen cabeceras (o estructura XML) y se muestra la lista de columnas detectadas.
2. **Asignación de proveedor**: El usuario asigna o confirma el proveedor de la importación. Opcionalmente el sistema puede sugerir proveedor por nombre de archivo o patrón.
3. **Mapeo**: El sistema sugiere mapeo de columnas origen → campos normalizados usando alias (supplier_column_aliases) y reglas por proveedor (supplier_field_mappings). El usuario revisa y edita el mapeo en pantalla (origen, destino, transformador opcional). Se guarda el mapeo y opcionalmente un snapshot en la importación.
4. **Preview**: Se leen N filas (ej. 100) y se transforman con el mapeo actual. Se muestra una tabla de vista previa (origen → normalizado). El usuario puede ajustar mapeo y volver a generar preview.
5. **Procesamiento (job)**: El usuario confirma “Procesar”. Se dispara un job que, por cada fila (o por lotes): lee raw_data, aplica MappingService y TransformPipeline, genera Nombre* (NameGenerator) y Descripción (DescriptionGenerator), valida EAN (EanValidator), hace matching de categorías por texto (CategoryMatcher), crea normalized_product (quantity = stock del archivo), crea product_ean_issues si EAN vacío/inválido, y detecta duplicados por EAN (duplicate_product_groups). En paralelo o después, un job descarga imágenes y las asocia al producto (normalizado y luego al maestro). Se actualizan total_rows, processed_rows, error_rows y el estado de la importación.
6. **Normalización y validación**: Los normalized_products son la salida del job; tienen quantity = stock origen, nombre, descripción, EAN validado o marcado empty/invalid, sugerencias de categoría (product_category_suggestions). Las incidencias quedan en product_ean_issues; los grupos de duplicados en duplicate_product_groups.
7. **Creación de master_products**: En la fase de revisión, el usuario (admin) une normalizados a un producto maestro: desde la lista de duplicados elige el maestro y fusiona (se crean master_products y master_product_suppliers); o crea maestros manualmente y asocia normalizados. Al crear o actualizar el maestro se asigna: categoría hoja (category_id), category_path_export (HOJA, INICIO, PADRE_1...), stock operativo inicial (desde el normalizado principal o 0) y se registra un stock_change (source=import, change_mode=replace). Las imágenes se ligan al maestro; se define orden y portada (is_cover).
8. **Revisión en admin**: El admin revisa productos maestros (listado, filtros, detalle), corrige EAN en incidencias, asigna o corrige categorías, aprueba productos para exportación (is_approved).
9. **Revisión en app**: Los usuarios en tienda pueden, desde la app, ajustar stock (replace/increment), completar o corregir EAN en productos con EAN vacío/inválido y ajustar la categoría final. Esos cambios se encolan y se sincronizan al backend; el backend actualiza master_products y registra stock_changes (y eventos de EAN/categoría). La monitorización en el admin puede mostrar qué productos han sido tocados por la app y si hay conflictos.
10. **Exportación final CSV a PrestaShop**: El usuario del panel va a Exportación PrestaShop, aplica filtros (solo aprobados, por proveedor, por fecha, etc.). El sistema genera el CSV leyendo exclusivamente master_products (y category_path_export, product_images, master_product_suppliers); la columna Cantidad es master_products.quantity. Se descarga el archivo listo para importar en PrestaShop. normalized_products no interviene en el CSV.

---

## 11. Entidades y relaciones definitivas

Lista cerrada de entidades y propósito:

- **users**: Usuarios del sistema (panel y app); autenticación y permisos.
- **roles, permissions, model_has_roles, role_has_permissions**: RBAC (Spatie).
- **projects, project_user**: Proyectos (existente) y asignación de usuarios.
- **mobile_integrations**: Configuración de app por proyecto (existente).
- **suppliers**: Proveedores de catálogo; configuración y mapeos.
- **supplier_imports**: Cada importación (archivo subido); estado, filas, mapeo usado.
- **supplier_import_rows**: Filas crudas de la importación; raw_data y normalized_data.
- **supplier_field_mappings**: Mapeo origen→destino por proveedor; transformador opcional.
- **supplier_column_aliases**: Alias globales para sugerir mapeo (ej. fabricante→Marca).
- **categories**: Árbol de categorías (parent_id); categoría hoja se asigna a master_products.
- **category_paths**: (Opcional) Tabla materializada de rutas para export/rendimiento.
- **normalized_products**: Productos por fila de importación; quantity = stock origen; capa intermedia/histórica.
- **master_products**: Producto único de negocio; quantity = stock operativo; category_id = hoja; category_path_export; fuente del CSV.
- **master_product_suppliers**: Relación N:M maestro ↔ normalizados (varios proveedores por maestro).
- **product_images**: Imágenes del producto maestro; path_local, position, is_cover.
- **product_ean_issues**: Incidencias EAN (empty/invalid) por producto.
- **duplicate_product_groups**: Grupos de productos con mismo EAN13.
- **duplicate_product_group_items**: Ítems de cada grupo (normalized_product_id).
- **product_category_suggestions**: Sugerencias de categoría (matching automático o manual).
- **stock_changes**: Historial de cambios de stock; previous_quantity, new_quantity, delta, change_mode, source.
- **stock_scan_events**: Eventos de escaneo (master_product_id, user_id, device_id, scanned_at).
- **mobile_sync_queue**: Cola en backend de eventos enviados por la app (payload con change_mode).
- **app_devices**: Dispositivos registrados (user_id, device_id, last_seen_at).
- **user_sessions**: Sesiones/presencia (web o app) para monitorización.
- **activity_logs**: Auditoría de acciones en el panel.
- **settings**: Configuración global clave-valor.

Relaciones clave: supplier→imports, mappings; import→rows, normalized_products; normalized_product→master_product (opcional), supplier, ean_issues, duplicate_items, category_suggestions; master_product→category, master_product_suppliers, product_images, stock_changes, stock_scan_events; category→parent/children; stock_changes→master_product, user.

---

## 12. Riesgos y decisiones técnicas importantes

**Riesgos de rendimiento**: (1) 145k productos: importación por lotes y jobs en cola; índices en master_products (ean13, category_id, updated_at, is_approved); paginación y filtros en listados del panel. (2) Exportación CSV: generación por streaming o por chunks para no cargar todo en memoria. (3) App: no descargar 145k registros completos; subconjunto con límite y sincronización incremental; índices en BD local (ean13, name, etc.) y opcional FTS para búsqueda. (4) Búsqueda en servidor: índices FULLTEXT o similares sobre name, brand; límite de resultados y paginación.

**Riesgos de sincronización**: (1) Conflictos: versión en master_products y política clara (replace→409 si version distinta; increment→aplicar delta sobre valor actual o 409 según decisión). (2) Cola local muy grande: límite de eventos pendientes o purga de eventos ya sincronizados; reintentos con backoff. (3) Pérdida de conexión durante push: eventos quedan en pending y se reenvían en la siguiente conexión; el backend debe ser idempotente donde sea posible (ej. por id de evento cliente).

**Riesgos de duplicados**: (1) Dos maestros con mismo EAN por error: restricción única en master_products.ean13 (nullable unique). (2) Duplicados no fusionados: lista dedicada en admin (duplicate_product_groups) y flujo de “elegir maestro y fusionar”. (3) Productos sin EAN: se permiten; se identifican por referencia o id; la app puede asignar EAN desde escaneo o edición.

**Riesgos de importación**: (1) Archivos muy grandes: lectura por streaming o por chunks; job que procese por lotes (ej. 1000 filas) y actualice progreso. (2) Columnas con nombres raros: alias configurables y mapeo manual; no depender de detección automática al 100%. (3) Imágenes que fallan: registrar error en product_images (status=failed, error_message); no bloquear la importación del producto.

**Mitigaciones**: (1) Índices y paginación en todos los listados y búsquedas. (2) Jobs para trabajo pesado; colas con reintentos. (3) Versionado y reglas de conflicto documentadas y aplicadas de forma uniforme. (4) Restricciones de BD (unique ean13, FKs) y validaciones en backend. (5) Estrategia de descarga en app definida y documentada para no saturar almacenamiento ni red.

---

## 13. Orden recomendado de implementación

- **Fase 1 (ya hecha)**: Análisis y especificación; documentos de arquitectura, BD, flujos, escáner, buscador y offline. Sin código.
- **Fase 2**: Backend base: migraciones (incluyendo delta y change_mode en stock_changes), modelos, seeders de permisos y roles, políticas, rutas web skeleton, sistema de diseño en panel. Sin lógica de negocio compleja.
- **Fase 3**: Proveedores e importación: CRUD proveedores, mapeos, subida de archivos, detección de formato, ColumnDetector (alias), MappingService, TransformPipeline, preview y job de procesamiento que cree supplier_import_rows y normalized_products (quantity = stock origen).
- **Fase 4**: Normalización completa: NameGenerator, DescriptionGenerator, EanValidator, CategoryMatcher (texto), creación de product_ean_issues y duplicate_product_groups; categorías (árbol, category_path_export); creación/actualización de master_products y stock_changes (import); imágenes (descarga, 800x800, product_images); vistas y controladores de normalizados, maestros, categorías, EAN issues, duplicados.
- **Fase 5**: API para Flutter: auth Sanctum, endpoints de productos (maestros), categorías, stock (replace/increment con change_mode), sync push/pull (incremental por last_sync_at), búsqueda (query + índices + paginación), presencia; validación de versión y respuestas 200/409.
- **Fase 6**: App Flutter: estructura de proyecto, tema MP, BD local (Drift) con tablas de catálogo ligero, categorías, sync_queue, scan_events, sesión, settings; estrategia de descarga (subconjunto + imagen principal); SyncEngine (push con change_mode, pull incremental); login, dashboard, escáner con cámara (EAN13 prioritario), buscador (campos e índices locales + búsqueda online), ficha producto, edición stock (replace/increment), EAN y categoría, historial escaneados, pendientes, incidencias, conflictos; feedback escáner y flujos definidos en sección 7.
- **Fase 7**: Monitorización en tiempo real en el panel: broadcasting, eventos (escaneos, stock_changes, conflictos, estado importaciones), dashboard con widgets en vivo, presencia (heartbeat desde app).
- **Fase 8**: Exportación PrestaShop (solo desde master_products), filtros, validaciones previas, documentación técnica y hardening.

Dependencias: 2 → 3 → 4; 4 y 2 → 5; 5 → 6; 5 y 6 → 7; 4 y 5 → 8.

---

## 14. Checklist final de definición

**Decisiones cerradas que no deben cambiar antes de programar:**

1. **Stock**: normalized_products.quantity = origen; master_products.quantity = operativo; stock_changes = historial con previous_quantity, new_quantity, delta, change_mode, source; exportación usa solo master_products.quantity.
2. **Exportación PrestaShop**: CSV generado exclusivamente desde master_products (y tablas ligadas); normalized_products no interviene.
3. **Categorías**: categories con parent_id; master_products.category_id = hoja; master_products.category_path_export = HOJA, INICIO, PADRE_1, PADRE_2...; category_paths solo si aporta valor.
4. **Motor inteligente**: reglas, alias, transformadores, validadores, matching textual; sin IA generativa ni embeddings obligatorios.
5. **App**: solo puede modificar stock, EAN y categoría final; stock con modos replace e increment; payload y stock_changes con change_mode y delta.
6. **Offline-first**: BD local; cola de sincronización; imagen principal disponible offline para productos en local; imágenes secundarias bajo demanda; sincronización incremental desde last_sync_at; no descargar 145k productos en formato pesado; catálogo maestro ligero con subconjunto definido por criterios.
7. **Volumen**: ~145.000 productos; ~20 archivos de proveedores; app con subconjunto descargable e índices locales; búsqueda en servidor con índices y paginación.
8. **Escáner**: cámara del dispositivo; EAN13 prioritario; UPC/EAN8 opcional sin romper lógica EAN13; flujos definidos para coincidencia exacta, no encontrado, EAN vacío, múltiples; feedback visual/auditivo; eventos de escaneo registrados localmente y en servidor; historial “productos escaneados por mí”.
9. **Buscador**: campos buscables (nombre, marca, referencia, referencia proveedor, EAN13, proveedor, palabras clave, categoría, etiquetas); búsqueda offline sobre catálogo local con índices (ean13, texto/FTS); búsqueda online con índices en servidor y paginación; comportamiento con 145k productos definido (subconjunto local + servidor paginado).
10. **Conflictos**: replace → conflicto estricto 409 si cambió la versión; increment → aplicar delta sobre valor actual del servidor y registrar stock_changes (sin 409 por versión); EAN y categoría → mostrar local vs remoto y elegir.
11. **Estrategia offline definitiva**: primera carga = catálogo maestro ligero completo; imagen principal con caché progresiva; imágenes secundarias bajo demanda; sync incremental por updated_since; ficha completa bajo demanda.
12. **Buscador local definitivo**: indexar localmente name, brand, reference, supplier_reference (principal), ean13, category_path_export, search_keywords_normalized; búsqueda rápida con 145k productos.
13. **Entidades**: lista cerrada en sección 11; propósito y relaciones definidos.
14. **Fases de implementación**: orden 2 → 3 → 4 → 5 → 6 → 7 y 8; dependencias claras.
15. **Decisiones operativas finales (congeladas)**: ver sección 4.1 (estrategia offline, política stock replace/increment, buscador local).

**Puntos que ya no deben cambiar antes de programar:**  
Las reglas de negocio de la sección 4; la correspondencia CSV ↔ master_products; el formato de category_path_export; los modos replace e increment y su payload; la estrategia de descarga en la app (subconjunto + imagen principal + secundarias bajo demanda); el comportamiento del escáner (EAN13 prioritario, flujos éxito/no encontrado/EAN vacío, registro de eventos); la estrategia de búsqueda (campos, índices locales, búsqueda online paginada); y el uso exclusivo de master_products para la exportación CSV. Cualquier cambio posterior debe reflejarse en una nueva versión de esta especificación antes de tocar código.

---

*Documento de especificación funcional y técnica cerrada. Versión para inicio de implementación. No contiene código ni pseudocódigo.*
