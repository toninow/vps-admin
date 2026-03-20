# Documentación del sistema Musical Princesa

Documentación de arquitectura, base de datos, módulos, flujos y plan de implementación del sistema integral (panel administrador, motor de importación y app Flutter STOCK MP). Incluye las **decisiones obligatorias** cerradas y la **especificación funcional y técnica cerrada** antes de implementación.

## Documento maestro (definición cerrada)

| Documento | Uso |
|-----------|-----|
| **[ESPECIFICACION_FUNCIONAL_TECNICA.md](ESPECIFICACION_FUNCIONAL_TECNICA.md)** | **Especificación definitiva y cerrada** del sistema: resumen ejecutivo, alcance, arquitectura, reglas de negocio, panel admin, app Flutter, escáner con cámara, buscador, offline-first, flujo importación→exportación, entidades, riesgos, orden de implementación y checklist final. **No contiene código.** Es el documento de referencia antes de generar migraciones, modelos, controladores, vistas, API ni app Flutter. |

## Índice de documentos (detalle técnico)

| Documento | Contenido |
|-----------|-----------|
| [01_ARQUITECTURA_Y_STACK.md](01_ARQUITECTURA_Y_STACK.md) | Visión general; **fuente de verdad del stock** (normalized = origen, master = operativo, stock_changes = historial); **categorías** (parent_id, category_id = hoja, category_path_export = HOJA, INICIO, PADRE1...); **motor por reglas** (alias, transformadores, validadores, matching textual; sin IA obligatoria); **export solo desde master_products**; estrategia offline (qué descargar, imagen principal offline, secundarias bajo demanda). |
| [02_BASE_DE_DATOS.md](02_BASE_DE_DATOS.md) | Diseño de BD: tablas; **normalized_products.quantity** = stock origen; **master_products.quantity** = stock operativo; **stock_changes** (previous_quantity, new_quantity, **delta**, **change_mode** replace\|increment, source); categorías (parent_id); category_path_export; **correspondencia CSV PrestaShop ↔ master_products** (normalized no interviene). |
| [03_ESTRUCTURA_MODULOS.md](03_ESTRUCTURA_MODULOS.md) | Estructura panel admin y app Flutter; **motor inteligente por reglas** (ColumnDetector, TransformPipeline, CategoryMatcher sin IA); Export solo desde master_products; MobileSyncService con change_mode; estrategia de descarga en app. |
| [04_FLUJOS.md](04_FLUJOS.md) | Flujo importación → exportación (stock origen en normalizados, operativo en maestros; export solo master_products); **stock en app: modos replace e increment** (payload, conflictos por modo); **estrategia offline** (qué productos/imágenes descargar; imagen principal offline; secundarias bajo demanda); sync backend ↔ app; export PrestaShop cerrada. |
| [05_PANTALLAS_Y_ENTIDADES.md](05_PANTALLAS_Y_ENTIDADES.md) | Entidades y relaciones (stock, categorías, stock_changes con change_mode); pantallas admin y app; **Export PrestaShop** solo desde master_products; edición stock con replace/increment en app. |
| [06_PLAN_IMPLEMENTACION.md](06_PLAN_IMPLEMENTACION.md) | Plan por fases (2–8): migraciones con delta/change_mode; motor por reglas en Fase 3; stock operativo y stock_changes en Fase 4; API con change_mode en Fase 5; app con estrategia offline e imagen principal en Fase 6; export solo master_products en Fase 8. |
| [07_ESTADO_MPSFP_2026-03-20.md](07_ESTADO_MPSFP_2026-03-20.md) | Estado real del submodulo MPSFP: resumen del prompt maestro, avance medido, coberturas por proveedor, estado EAN y siguientes pasos operativos. |

## Decisiones obligatorias (resumen)

- **Stock**: normalized_products.quantity = solo origen importación; master_products.quantity = stock operativo; stock_changes registra todo (previous_quantity, new_quantity, delta, change_mode, source).
- **Categorías**: categories con parent_id; master_products.category_id = hoja; category_path_export = HOJA, INICIO, PADRE1, PADRE2...; category_paths solo si aporta valor.
- **Motor**: inteligencia por reglas, alias, transformadores, validadores y matching textual; **no IA generativa ni embeddings** como obligatorio (IA = fase futura opcional).
- **App stock**: dos modos, **replace** e **increment**; stock_changes con delta y change_mode; resolución de conflictos definida por modo.
- **Offline**: no descargar todo el catálogo; definir qué productos se descargan; **imagen principal** disponible offline; imágenes secundarias bajo demanda.
- **Export PrestaShop**: **solo desde master_products**; normalized_products es histórico/intermedio y no interviene en el CSV.

## Orden de lectura recomendado

1. **01** – Arquitectura y reglas (stock, categorías, motor, export, offline).  
2. **02** – Base de datos y correspondencia CSV ↔ master_products.  
3. **03** – Dónde va cada pieza (admin y app).  
4. **04** – Flujos (importación, stock replace/increment, offline, export).  
5. **05** – Pantallas y entidades.  
6. **06** – Plan de implementación por fases.

Identidad visual: **#E6007E**, **#FFFFFF**, **#555555**.
