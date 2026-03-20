# Endurecimiento del mapeo automático de importaciones

## 1. Análisis: por qué ocurrieron los falsos positivos

Con un CSV mínimo (columnas: `nombre`, `ean13`, `precio`, `cantidad`) el sistema mapeó:

| Campo        | Valor asignado (incorrecto)     | Causa |
|-------------|---------------------------------|-------|
| **brand**   | = nombre ("Guitarra Test")      | Solo puntuación por **contenido**: heurística "texto corto (≤20 chars, ≤3 palabras)" da 2.0. La columna "nombre" no tiene alias de cabecera para `brand`; aun así ganó por ser la única con puntuación. |
| **description** | = nombre                    | Mismo bloque de contenido que `name`/`summary`: cualquier columna con texto de longitud media (avg ≥ 5) recibe 1.0. "nombre" tiene alias para `name` (5.0) pero no para `description`; aun así sumó 1.0 por contenido y quedó como "mejor" para description. |
| **cost_price**  | = precio (mismo que PVP)    | La columna "precio" tiene alias para `price_tax_incl` (5.0). Para `cost_price` no hay alias ("coste", "costo", "precio compra"); solo recibió 2.0 por **contenido** (decimales). Esa puntuación fue suficiente para ganar y reutilizar la misma columna que precio venta. |

Causas de fondo:

- **Sin umbral mínimo**: cualquier puntuación > 0 podía ganar; no se exigía evidencia mínima por target.
- **Sin reglas de exclusión**: la misma columna podía ganar varios targets (nombre → name, summary, description, brand; precio → price_tax_incl y cost_price).
- **Heurísticas de contenido demasiado genéricas**: "texto corto" y "decimales" no distinguen nombre vs marca ni PVP vs coste.

## 2. Propuesta de endurecimiento (aplicada)

### 2.1 Umbral mínimo por target

Se añade `getMinimumScoreByTarget()` en `BaseSupplierProfile` con valores por defecto:

- **brand**, **cost_price**: **5.0**. Solo se mapean con coincidencia clara de cabecera (alias exacto = 5.0). La puntuación por contenido sola (≤ 2.0) no alcanza.
- **description**: **4.0**. Evita mapear una columna solo por "tiene texto largo" (1.0) sin alias de descripción.
- **image_urls**: **3.0**. Exige contenido tipo URL o alias.
- Resto (name, summary, ean13, quantity, price_tax_incl, category, tags, supplier_reference): **2.0**. Siguen permitiendo contenido heurístico pero con un mínimo.

### 2.2 Reglas de exclusión

Dentro de `suggestMapping`, una vez elegida la mejor columna por puntuación total y superado el umbral:

- **brand**: si la mejor columna es la **misma que la asignada a `name`** y la puntuación de **cabecera** para brand en esa columna es &lt; 2.0, no se asigna brand (se deja sin mapear).
- **description**: igual: si la mejor columna es la de **name** y la puntuación de cabecera para description es &lt; 2.0, no se asigna description.
- **cost_price**: si la mejor columna es la **misma que `price_tax_incl`** y la puntuación de cabecera para cost_price es &lt; 2.0, no se asigna cost_price.

Con esto se evita reutilizar "nombre" como brand/description y "precio" como cost_price cuando solo hay evidencia por contenido, no por cabecera.

### 2.3 Orden de asignación

Se procesan los targets en el orden estándar (`TARGET_FIELDS`). Así, al evaluar brand, description y cost_price ya están fijadas las columnas de name y price_tax_incl y se pueden aplicar las exclusiones.

### 2.4 Perfiles concretos

- **GenericSupplierProfile**: no se modifica; solo define aliases. Hereda umbrales y exclusiones de la base.
- **AdagioSupplierProfile** (y el resto): siguen igual; si en el futuro un proveedor requiere reglas más permisivas, puede sobrescribir `getMinimumScoreByTarget()`.

## 3. Archivos modificados

| Ruta | Cambios |
|------|---------|
| `app/Services/Suppliers/Profiles/BaseSupplierProfile.php` | `getMinimumScoreByTarget()`, refactor de `suggestMapping` con matriz de puntuaciones por cabecera y total, umbral mínimo y exclusiones brand/description/cost_price. |
| `app/Services/Suppliers/Profiles/GenericSupplierProfile.php` | Sin cambios. |

## 4. Cómo comprobar

Tras el cambio, con el mismo CSV de prueba (`nombre`, `ean13`, `precio`, `cantidad`):

1. Crear nueva importación de prueba (o borrar la anterior y repetir).
2. Ejecutar `php artisan imports:create-test --supplier=ADAGIO --file=storage/app/adagio.csv`.
3. Ejecutar `php artisan imports:validate-mapping 1` (o el id correspondiente).

Resultado esperado: **brand**, **description** y **cost_price** deben aparecer con 0% rellenados (o sin mapeo); **name**, **summary**, **ean13**, **quantity**, **price_tax_incl** siguen mapeados y con datos.

Con un archivo real que tenga columnas "marca", "descripcion", "precio compra" (o aliases equivalentes), esos campos seguirán mapeándose cuando la cabecera dé puntuación suficiente.
