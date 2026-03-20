# 1. Arquitectura del sistema Musical Princesa

## Visión general

El sistema se compone de **tres bloques** conectados, con el **backend y panel admin como centro de verdad** y la **app móvil como cliente offline-first** que sincroniza con él.

```
                    ┌─────────────────────────────────────────────────────────┐
                    │                  PANEL ADMIN WEB                         │
                    │  (Laravel + Blade + Tailwind · VPS)                      │
                    │  Dashboard · Usuarios · Proyectos · Proveedores ·        │
                    │  Importaciones · Productos · Categorías · Imágenes ·    │
                    │  Incidencias · Duplicados · EAN · Export PrestaShop ·   │
                    │  Monitorización tiempo real · Logs                       │
                    └───────────────────────────┬─────────────────────────────┘
                                                │
                        ┌───────────────────────┼───────────────────────┐
                        │                       │                       │
                        ▼                       ▼                       ▼
            ┌───────────────────┐   ┌───────────────────┐   ┌───────────────────┐
            │  Motor de         │   │  API REST          │   │  Broadcasting     │
            │  importación      │   │  (Laravel API)      │   │  (presencia,      │
            │  CSV/Excel/XML    │   │  Auth · Productos   │   │  tiempo real)     │
            │  Mapeo · Norm.     │   │  Stock · Sync      │   │                   │
            └───────────────────┘   └─────────┬─────────┘   └─────────┬─────────┘
                        │                     │                       │
                        │                     │                       │
                        ▼                     ▼                       ▼
            ┌───────────────────────────────────────────────────────────────────┐
            │                     APP FLUTTER · STOCK MP                         │
            │  Android / iOS · Tablet / Móvil · Offline-first · Sync engine      │
            │  Stock · EAN · Categorías · Escaneo · Cola local · Conflictos     │
            └───────────────────────────────────────────────────────────────────┘
```

- **Panel admin**: centro de control; usuarios, permisos, proveedores, importaciones, productos normalizados (histórico/intermedio), productos maestros (verdad de negocio), categorías, imágenes, incidencias, **exportación PrestaShop solo desde master_products**, monitorización.
- **Motor de importación**: parte del mismo Laravel; **inteligencia por reglas, alias, transformadores, validadores y matching textual** (sin IA generativa ni embeddings como parte obligatoria).
- **API REST**: mismo backend; autenticación compartida; endpoints para catálogo (maestros), stock, categorías y sincronización.
- **App Flutter**: consume API; offline-first con BD local; **estrategia de descarga definida** (no todo el catálogo; imagen principal disponible offline; secundarias bajo demanda); cola de sync y resolución de conflictos.

---

## 2. Fuente de verdad del stock (regla obligatoria)

- **normalized_products.quantity**: es **solo el stock de origen/importación**. Refleja lo que vino del archivo del proveedor. No debe usarse como stock operativo ni para exportación final. Es dato histórico/intermedio.
- **master_products.quantity**: es el **stock operativo real del sistema**. Es la única cantidad que se usa para negocio, app móvil y exportación CSV a PrestaShop. Toda modificación de stock (importación, app, admin) debe actualizar esta columna y registrar el cambio en `stock_changes`.
- **stock_changes**: registra el **historial de todos los cambios de stock** realizados por:
  - importación (al crear o actualizar maestro desde normalizado),
  - app móvil (replace o increment),
  - panel admin (edición manual).
  Cada registro debe incluir: `previous_quantity`, `new_quantity`, `delta`, `change_mode` (replace | increment) y `source` (import | app | admin). Ver documento 02 para el esquema exacto.

Esta regla debe respetarse en arquitectura, base de datos, flujos y API. La exportación CSV a PrestaShop usa exclusivamente `master_products.quantity`.

---

## 3. Categorías y ruta exportable (reglas obligatorias)

- **categories**: tabla con **parent_id** (nullable para la raíz). Árbol jerárquico clásico.
- **master_products.category_id**: referencia **siempre a la categoría final/hoja** (nodo sin hijos o el nodo que se considere “final” en la clasificación del producto). No se guarda la raíz ni nodos intermedios en esta FK; la ruta completa se construye y se guarda en `category_path_export`.
- **master_products.category_path_export**: es el **valor final listo para exportación a PrestaShop**. Formato: cadena con categorías en el orden **HOJA, INICIO, PADRE1, PADRE2, ...** (primero la hoja, luego la raíz, luego los padres de la hoja hacia la raíz). Ejemplo: `ACÚSTICAS ELECTRIFICADAS, INICIO, GUITARRAS Y BAJOS, GUITARRAS, GUITARRAS ELECTROACÚSTICAS`.
- **category_paths**: tabla auxiliar **solo si aporta valor técnico** (por ejemplo listados masivos o export muy frecuente). Si no se usa, la ruta se calcula on-the-fly con recursive CTE desde `categories` (parent_id). La decisión de incluir o no `category_paths` queda en implementación según rendimiento.

---

## 4. Motor “inteligente” de importación (decisión obligatoria)

En esta fase el sistema es “inteligente” mediante:

- **Reglas** configurables por proveedor (mapeos, transformadores, validadores).
- **Alias** de columnas (ej. fabricante, brand, marca → Marca) para detección automática de equivalencias.
- **Transformadores** (trim, uppercase, ean13_pad, capitalización, etc.) aplicados por campo.
- **Validadores** (EAN13, longitud, formato) que marcan incidencias.
- **Matching textual** para categorías (nombre, slug) contra el árbol maestro; sin embeddings ni IA generativa.

**La arquitectura no depende de IA generativa ni de embeddings como parte obligatoria.** Si en el futuro se desea incorporar sugerencias por IA (por ejemplo categoría o nombre), se hará como **fase futura opcional** y no como requisito del diseño actual.

---

## 5. Exportación PrestaShop (regla obligatoria)

- El **CSV final de exportación a PrestaShop se genera exclusivamente desde master_products** (y tablas relacionadas: categoría, imágenes asociadas al maestro, etc.).
- **normalized_products** es capa **histórica/intermedia**: sirve para importación, revisión, unificación y trazabilidad, pero **no es la fuente del CSV de exportación**. La correspondencia entre columnas del CSV y campos del sistema queda cerrada sobre `master_products` (y sus relaciones). Ver documento 02 y 04 para la lista exacta de columnas CSV y su origen en master_products.

---

## 6. Decisión de stack (justificada)

### Backend y panel web

| Tecnología | Decisión | Justificación |
|------------|----------|----------------|
| **Laravel** | Mantener (ya en uso) | Madurez, ecosistema, colas, jobs, API, broadcasting, mismo código para web y API. |
| **Blade + Tailwind** | Mantener | Panel admin ya existe; Tailwind permite diseño consistente y sistema de diseño con colores MP (#E6007E, #555555). |
| **MySQL / MariaDB** | Mantener | Ya usado; suficiente para transacciones, relaciones y volumen esperado. |
| **Spatie Permission** | Mantener | RBAC ya integrado; ampliar permisos para proveedores, importaciones, productos, stock, mobile. |
| **Laravel Queues** | Usar | Importaciones pesadas, descarga de imágenes y exportaciones en background. |
| **Laravel Sanctum** | Usar | Tokens para API Flutter; mismo guard de usuarios. |
| **Laravel Broadcasting (Pusher/Soketi)** | Recomendado | Presencia, actividad en tiempo real; opcional Fase 7. |
| **Almacenamiento** | Local + `public/images-prestashop/` | Imágenes en servidor; rutas públicas para PrestaShop y app. |

### App móvil Flutter

| Tecnología | Decisión | Justificación |
|------------|----------|----------------|
| **Flutter** | Sí | Un solo código para Android e iOS; UI adaptable a móvil y tablet. |
| **Drift** | Recomendado para BD local | SQLite con type-safety, migraciones, soporte offline robusto. |
| **Arquitectura** | Por features + capas (data/domain/presentation) | Escalable, testeable; SyncEngine dedicado. |
| **Connectivity + reachability** | connectivity_plus + ping al backend | Comprobar que el backend responde, no solo “hay WiFi”. |
| **Sync** | Cola local + endpoints REST; stock con modos **replace** e **increment** | Backend como fuente de verdad; conflictos resueltos según modo (ver 04). |

### Identidad visual (resumen)

- **Colores**: #E6007E (principal), #FFFFFF (fondos), #555555 (texto/gris).
- **Panel web**: Tailwind con extensión de tema (brand, neutral).
- **Flutter**: `lib/core/theme/` (AppColors, AppTextStyles, AppSpacing, ThemeData) sin hardcodear colores.

---

## 7. Principios de conectividad y estrategia offline

- **Admin web**: siempre online; fuente de verdad para usuarios, permisos, proveedores, importaciones, **productos maestros** y exportación.
- **App STOCK MP**: **offline-first** en tienda; **online-first** para sincronización (subir cambios, bajar catálogo, resolver conflictos).
- **Estrategia offline (obligatoria)**:
  - **No** se descarga todo el catálogo por defecto; se define qué productos se descargan (por ejemplo por categoría, por fecha de actualización, por límite o por “productos ya consultados/escaneados”). El detalle concreto se define en el documento 04.
  - **Imagen principal** del producto debe quedar disponible offline para los productos que el usuario tenga en local (descarga al sincronizar o al abrir ficha).
  - **Imágenes secundarias** pueden descargarse bajo demanda (al abrir galería o slider).
  - Los **datos mínimos** que la app necesita para funcionar offline son: usuario/sesión local, productos descargados (campos necesarios para listado y ficha), categorías necesarias para selector, imagen principal por producto descargado, y cola local de cambios (sync_queue).

---

## 8. Resumen de arquitectura

- **Monolito backend** (Laravel): web + API + jobs + motor de importación (reglas/alias/transformadores/validadores/matching textual).
- **Stock**: normalized_products.quantity = origen importación; master_products.quantity = stock operativo; stock_changes = historial completo (previous_quantity, new_quantity, delta, change_mode).
- **Categorías**: parent_id en categories; category_id en master_products = hoja; category_path_export = valor export (HOJA, INICIO, PADRE1, PADRE2...).
- **Export PrestaShop**: solo desde master_products; normalized_products no interviene en el CSV final.
- **App Flutter**: SyncEngine, cola local, modos replace/increment para stock; estrategia de descarga e imágenes definida en 04.

Este documento se complementa con: Base de datos (02), Estructura de módulos (03), Flujos (04), Pantallas y entidades (05) y Plan de implementación (06).
