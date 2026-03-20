# Estado MPSFP 2026-03-20

## Prompt maestro resumido

Construir el submodulo MPSFP para Musical Princesa como un flujo real de trabajo y no una demo:

- importar catalogos de proveedores de forma manual y automatica
- mapear columnas y detectar campos de forma robusta
- procesar y normalizar productos en un solo pipeline
- clasificar EAN, UPC, SKU y codigos internos correctamente
- sugerir categorias y rutas de exportacion
- generar etiquetas, descripciones y nombres normalizados
- consolidar catalogo maestro por identidad de producto
- permitir revision masiva desde el admin web
- aprobar productos seguros
- exportar a PrestaShop 1.7.8.11
- soportar historico anual por proveedor

## Estado real actual

El flujo ya funciona de extremo a extremo:

1. importacion
2. mapping
3. proceso y normalizacion automatica
4. incidencias EAN
5. sugerencias de categoria
6. consolidacion a maestro
7. aprobacion segura
8. exportacion

Tambien existe automatizacion post-importacion para que el sistema cierre solo el lote sin depender de comandos manuales para cada carga nueva.

## Avance medido

### Catalogo

- maestros aprobados: `60.106`
- maestros con categoria: `70.833`
- maestros exportables: `67.445`
- incidencias EAN abiertas: `10.633`

### Cobertura de categorias por proveedor

- `GEWA`: `90,46%`
- `TICO`: `82,68%`
- `YAMAHA`: `93,63%`
- `ORTOLA`: `90,50%`
- `MADRIDMUSICAL`: `90,99%`
- `LUDWIG NL`: `83,47%`
- `ALHAMBRA`: `83,85%`
- `ALGAM`: `71,46%`
- `EUROMUSICA`: `69,24%`
- `FENDER`: `69,30%`
- `HONSUY`: `75,51%`
- `ZENTRALMEDIA`: `56,69%`
- `KNOBLOCH`: `66,23%`
- `DADDARIO`: `99,64%`
- `EARPRO`: `78,36%`
- `SAMBA`: `100,00%`
- `VALLESTRADE`: `32,36%` y todavia en mejora en la ultima tanda trabajada

### EAN abierto por tipo

- `invalid_checksum`: `4.386`
- `upc_or_other`: `2.670`
- `empty`: `2.666`
- `invalid_length`: `911`

## Lo que ya quedo resuelto

- responsive amplio en vistas principales del admin
- carga por lotes con modal visual de progreso
- proceso y normalizacion unificados en un flujo real
- nombres de producto normalizados por proveedor
- descripciones y caracteristicas formateadas para exportacion
- clasificacion robusta de EAN, UPC12, GTIN8 y codigos internos
- tratamiento de notacion cientifica en Excel para EAN
- saneo de precios compra/venta
- exportacion con formato compatible con PrestaShop 1.7.8.11
- historico anual por proveedor
- consolidacion automatica a `master_products`
- revision masiva web para maestros, EAN y categorias
- aprobacion segura automatica y por lote

## Lo que queda por cerrar

### Prioridad alta

- bajar el volumen de incidencias EAN abiertas
- afinar categorias de los proveedores mas flojos restantes
- revisar productos residuales sin categoria fiable
- validar exportacion final desde la app con un lote real completo

### Prioridad media

- mejorar reglas especificas de negocio para proveedores residuales
- seguir limpiando outliers de precio
- reducir revision manual donde el sistema ya puede decidir solo

## Siguientes pasos recomendados

1. cerrar la depuracion EAN abierta por proveedor, empezando por `ALGAM`, `ZENTRALMEDIA`, `ORTOLA`, `TICO` y `GEWA`
2. terminar de empujar proveedores residuales de categorias hasta dejar el catalogo lo mas homogeneo posible
3. hacer una validacion final de exportacion real a CSV PrestaShop desde la app
4. preparar una revision operativa final del flujo: importar -> normalizar -> revisar -> aprobar -> exportar

## Estimacion honesta

- funcionalidad real del submodulo: `96%`
- faltante serio: `4%`

No estamos ya en fase de demo. El submodulo funciona y lo que queda es cierre fino, depuracion y control de calidad operativo.
