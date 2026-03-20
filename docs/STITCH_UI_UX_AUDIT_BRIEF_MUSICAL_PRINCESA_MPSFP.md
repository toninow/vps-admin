# Brief Completo Para Auditoria UI/UX En Google Stitch

## 1. Objetivo Del Encargo

Necesitamos una auditoria profunda de UI/UX sobre dos capas del mismo sistema:

1. El panel administrador general de **Musical Princesa**
2. El submodulo operativo **MPSFP** dentro de `Proyectos`

No queremos una critica superficial ni una lista de consejos genericos. Queremos un analisis profesional que detecte:

- problemas de claridad
- problemas de jerarquia visual
- problemas de navegacion
- problemas de arquitectura de informacion
- problemas de carga cognitiva
- problemas de consistencia
- problemas de accesibilidad
- problemas de escalabilidad de interfaz
- problemas de usabilidad en flujos reales con mucho volumen de datos

Y ademas queremos propuestas concretas de mejora orientadas a construir un sistema serio, modular, mantenible y usable en produccion.

---

## 2. Contexto Del Producto

Este sistema forma parte de la operativa de **Musical Princesa**.

Su objetivo es centralizar:

- gestion del panel administrador
- usuarios, roles y permisos
- proyectos del VPS
- importacion de catalogos de multiples proveedores
- normalizacion de productos
- deteccion de incidencias EAN
- sugerencia de categorias
- consolidacion futura a catalogo maestro
- preparacion de exportacion a PrestaShop
- futura integracion con app movil `STOCK MP`

El submodulo **MPSFP** significa:

`Musical Princesa Sistema Formato Proveedores`

Y es el centro de trabajo para:

- crear proveedores
- subir archivos CSV/XLSX/XML
- mapear columnas
- procesar importaciones
- revisar productos normalizados
- detectar productos repetidos entre proveedores
- revisar categorias sugeridas
- preparar el catalogo final

---

## 3. Stack Actual

- Backend: Laravel 10
- UI web: Blade + Tailwind por CDN
- Base de datos: MySQL / MariaDB
- RBAC: Spatie Permission
- Proyecto alojado en VPS propio
- El admin general y `MPSFP` comparten login y sesion

No estamos buscando rehacer el producto en otro stack. La auditoria debe asumir que el sistema seguira sobre esta base.

---

## 4. Identidad Visual Que Debe Respetarse

Marca: **Musical Princesa**

Colores corporativos:

- Rosa principal: `#E6007E`
- Blanco: `#FFFFFF`
- Gris/negro corporativo: `#555555`

Reglas de diseño:

- interfaz profesional y elegante
- no infantil
- no sobrecargada
- alta claridad visual
- buena legibilidad
- buena jerarquia de informacion
- consistencia entre admin general y submodulo `MPSFP`
- uso claro de estados por color
- contraste suficiente

---

## 5. Usuarios Principales

### 5.1 Superadmin / Admin

Necesita:

- entender el estado general del sistema
- entrar rapido en secciones criticas
- operar con volumen alto de productos
- detectar errores con rapidez
- revisar importaciones
- revisar normalizacion
- ver productos repetidos entre proveedores
- revisar categorias sugeridas
- preparar catalogo final

### 5.2 Editor / Operador de catalogo

Necesita:

- una interfaz clara
- flujos guiados
- saber en que seccion esta
- saber que puede hacer en cada pantalla
- entender estados y alertas sin interpretar datos tecnicos

### 5.3 Usuario movil futuro

No es el foco de esta auditoria visual web, pero la web debe dejar preparado el sistema para:

- categorias sugeridas
- EAN pendientes
- stock
- confirmacion final de categoria desde app

---

## 6. Alcance De La Auditoria

La auditoria debe cubrir:

### 6.1 Panel Administrador General

Analizar:

- login
- dashboard
- menu lateral global
- proyectos
- configuracion
- logs
- coherencia visual base

### 6.2 Submodulo MPSFP

Analizar especialmente:

- portada del submodulo
- navegacion primaria y secundaria
- flujo de proveedores
- flujo de importaciones
- productos normalizados
- vista modal / ficha de producto
- cruce de proveedores
- revision de categorias
- catalogo maestro
- exportacion

### 6.3 Componentes Reutilizables

Analizar:

- sidebar
- topbar
- tabs
- submenus
- filtros
- tablas
- badges
- alertas
- botones
- cards
- modales
- estados vacios
- loaders / progreso

---

## 7. Rutas Y Pantallas Clave A Revisar

### 7.1 Admin General

- `/login`
- `/dashboard`
- `/projects`
- `/projects/{id}`

### 7.2 MPSFP

- `/projects/3/MPSFP`
- `/projects/3/MPSFP/proveedores`
- `/projects/3/MPSFP/importaciones`
- `/projects/3/MPSFP/importaciones/{id}`
- `/projects/3/MPSFP/normalizados`
- `/projects/3/MPSFP/normalizados/{id}`
- `/projects/3/MPSFP/cruce-proveedores`
- `/projects/3/MPSFP/categorias/revision`
- `/projects/3/MPSFP/maestros`
- `/projects/3/MPSFP/exportacion`

Si el analisis necesita simplificar, priorizar:

1. `Resumen MPSFP`
2. `Importaciones`
3. `Normalizados`
4. `Cruce proveedores`
5. `Categorias`

---

## 8. Estado Funcional Actual Que Debe Tener En Cuenta Stitch

### 8.1 Lo que ya existe

- login compartido para admin y MPSFP
- `MPSFP` como submodulo dentro de `Proyectos`
- alta y gestion de proveedores
- subida de archivos de proveedor
- preview y mapeo
- procesamiento en segundo plano con progreso
- productos normalizados
- tags generadas en todo el catalogo
- categorias sugeridas
- deteccion de mismo producto entre proveedores por EAN
- inicio de consolidacion a `master_products`

### 8.2 Lo que aun no esta cerrado del todo

- exportacion PrestaShop real
- app Flutter
- API movil completa
- tiempo real completo
- confirmacion final de categoria desde app
- pipeline final de imagenes al servidor

La auditoria debe centrarse en lo que ya se puede usar hoy, pero tambien señalar si la UI actual deja preparada o no la evolucion futura.

---

## 9. Problemas Reales Ya Detectados En La UX

Estas observaciones no son teoricas: vienen del uso real del sistema.

### 9.1 Navegacion

- confusion entre `Proyecto` y `Submodulo`
- dificultad para entender donde empieza el admin general y donde empieza `MPSFP`
- submenus no siempre suficientemente claros
- algunas secciones no se sienten como parte de un flujo continuo

### 9.2 Dashboard / Portada De MPSFP

- mezcla de resumen, accesos, estados y flujo operativo en la misma pantalla
- exceso de informacion simultanea
- falta de jerarquia mas fuerte entre “empezar”, “trabajo diario” y “revision”
- no siempre queda claro que debe hacer el usuario primero

### 9.3 Tabla De Productos Normalizados

- alta densidad informativa
- sensacion de datos repetidos
- dificultad para entender:
  - que es estado tecnico
  - que es estado de catalogo
  - que es sugerido
  - que es confirmado
  - que viene del proveedor
  - que es dato ya normalizado
- filtros correctos a nivel tecnico, pero no siempre intuitivos a nivel UX

### 9.4 Modal / Ficha De Producto

- aun no se siente como una ficha de producto madura tipo PrestaShop
- necesita mejor seccionado
- mejor jerarquia entre:
  - identidad del producto
  - datos comerciales
  - imagenes
  - categoria
  - etiquetas
  - incidencias
  - trazabilidad

### 9.5 Estados Visuales

- falta una semantica visual mas fuerte para:
  - sugerido
  - confirmado
  - pendiente
  - error
  - invalido
  - sin imagen
  - sin EAN
  - sin maestro

### 9.6 Arquitectura De Informacion

- algunas pantallas estan pensadas desde el dato tecnico y no desde la tarea del usuario
- no siempre se diferencia entre:
  - dato bruto del proveedor
  - dato normalizado
  - dato listo para exportar

### 9.7 Escalabilidad Visual

- el sistema trabaja con volumen alto de catalogo
- la UI debe soportar decenas o cientos de miles de productos
- la solucion no puede ser simplemente “mostrar mas texto”

---

## 10. Principios UX Que Queremos Que Stitch Evalúe

Pedir a Stitch que evalúe al menos:

1. Claridad de navegación
2. Arquitectura de información
3. Flujo de trabajo
4. Jerarquía visual
5. Densidad informativa
6. Legibilidad
7. Uso del color y estados
8. Consistencia entre pantallas
9. Escaneabilidad de tablas y cards
10. Calidad de filtros y búsqueda
11. Calidad de los modales
12. Comprensión de estados del producto
13. Adaptabilidad responsive
14. Accesibilidad básica
15. Preparación para escalar a más módulos y más datos

---

## 11. Preguntas Concretas Que Queremos Que Responda

### 11.1 Sobre el admin general

- El panel general transmite estructura y control o parece una mezcla de módulos?
- El menú lateral actual es correcto o necesita nueva agrupación?
- Se entiende la relación entre `Proyectos` y `MPSFP`?
- El login y la capa base reflejan correctamente la identidad de marca?

### 11.2 Sobre MPSFP

- La portada actual de `MPSFP` ayuda a trabajar o distrae?
- La navegación principal y el submenú contextual están bien resueltos?
- Qué debería ver primero el usuario al entrar?
- Cómo reorganizarías `Resumen`, `Importaciones`, `Normalizados`, `Cruce`, `Categorias`, `Maestros` y `Exportación`?
- Qué patrón visual usarías para distinguir mejor origen, normalización y catálogo final?
- Cómo debe verse el estado de una categoría `suggested` frente a una `confirmed`?
- Cómo debe verse el estado de EAN, imagen, maestro y proveedor?

### 11.3 Sobre Normalizados

- Cómo simplificarías la tabla sin perder información crítica?
- Qué columnas deben ser visibles por defecto?
- Qué debería ir en expandibles o modales?
- Cómo rediseñarías los filtros para que sean más comprensibles?
- Cómo debería ser el modal de producto si queremos parecerse a una ficha de PrestaShop 1.7.8.11?

### 11.4 Sobre flujos

- El flujo real “Proveedor -> Importación -> Preview -> Mapeo -> Proceso -> Normalizados -> Categoría sugerida -> Maestro -> Exportación” se entiende bien?
- En qué puntos se rompe la continuidad?
- Qué pantallas necesitan wizard, breadcrumbs, tabs, sidepanels o estados vacíos guiados?

---

## 12. Resultado Esperado De La Auditoria

Queremos que Stitch entregue:

### 12.1 Diagnostico Ejecutivo

- resumen de los mayores problemas de UX/UI
- gravedad de cada problema
- impacto en productividad
- impacto en aprendizaje del sistema

### 12.2 Hallazgos Priorizados

Clasificados por:

- critico
- alto
- medio
- bajo

Y para cada hallazgo:

- problema
- por qué afecta
- ejemplo de pantalla
- propuesta de mejora

### 12.3 Propuesta De Reorganizacion

Queremos una propuesta concreta para:

- sidebar global
- topbar
- portada de `MPSFP`
- tabs principales
- submenus
- tablas de alto volumen
- modales / fichas

### 12.4 Sistema Visual

Queremos recomendaciones para:

- jerarquia tipografica
- espaciado
- grid
- cards
- tablas
- alertas
- badges
- chips
- filtros
- modales
- loaders
- estados vacios
- estados de error

### 12.5 Propuesta Operativa

Queremos una respuesta accionable:

- que cambiar primero
- que cambiar despues
- que puede esperar
- que es quick win
- que requiere rediseño mayor

---

## 13. Restricciones Importantes

La propuesta debe respetar estas restricciones:

- seguimos en Laravel + Blade
- no rehacer todo en React o similar
- no romper la identidad de Musical Princesa
- no diseñar una demo minimalista
- no hacer un panel “bonito” pero poco útil
- priorizar usabilidad real sobre adorno visual
- la interfaz debe servir para trabajar con mucho volumen de datos

---

## 14. Criterios De Calidad De La Propuesta

La propuesta de Stitch solo sera valida si:

- mejora la claridad real del sistema
- reduce la carga cognitiva
- hace entendible el flujo completo
- diferencia claramente estados y capas del dato
- mantiene coherencia entre admin y MPSFP
- escala bien con miles de productos
- es viable de construir sobre el stack actual

---

## 15. Prompt Recomendado Para Pegar En Stitch

Copia y pega este bloque en Stitch:

```text
Actua como un experto senior en UX, UI, arquitectura de informacion y diseño de producto B2B para herramientas operativas internas.

Necesito que analices profundamente la interfaz de un sistema llamado Musical Princesa y su submodulo MPSFP.

Quiero una auditoria profesional, no comentarios superficiales.

Contexto:
- Es un panel administrador web en Laravel + Blade + Tailwind.
- Tiene branding corporativo de Musical Princesa.
- MPSFP es un submodulo dentro de Proyectos para gestionar proveedores, importaciones, productos normalizados, categorias sugeridas, cruce entre proveedores, futuros productos maestros y exportacion a PrestaShop.
- El sistema trabaja con gran volumen de datos y debe ser usable en operaciones reales.

Quiero que analices:
- navegación global
- estructura del submodulo
- dashboard/resumen
- tablas y filtros
- modal/ficha de producto
- jerarquia visual
- estados por color
- claridad del flujo de trabajo
- consistencia
- carga cognitiva
- escalabilidad visual
- accesibilidad

Necesito que me devuelvas:
1. diagnostico ejecutivo
2. hallazgos priorizados por gravedad
3. propuesta de reorganizacion del sistema
4. propuesta visual concreta
5. quick wins
6. cambios estructurales mayores
7. recomendaciones especificas para:
   - dashboard de MPSFP
   - lista de productos normalizados
   - modal de producto estilo PrestaShop
   - sidebar y submenus
   - filtros y busquedas
   - estados visuales

Restricciones:
- No propongas rehacer todo en otro framework.
- Mantener branding Musical Princesa:
  - #E6007E
  - #FFFFFF
  - #555555
- Debe seguir siendo una herramienta profesional, no una landing bonita.
- Piensa en volumen alto de catalogo y trabajo operativo real.

Problemas ya detectados:
- confusion entre proyecto y submodulo
- portada de MPSFP demasiado cargada
- tabla de normalizados confusa
- estados de producto poco claros
- modal de producto todavia inmaduro
- navegacion y submenus mejorables
- dificultad para entender que es sugerido, confirmado, pendiente o error

Quiero recomendaciones accionables y especificas, no generalidades.
```

---

## 16. Pantallas Del Codigo Actual A Tener En Cuenta

Referencias utiles dentro del proyecto:

- Layout general: `resources/views/layouts/app.blade.php`
- Ficha de proyecto: `resources/views/projects/show.blade.php`
- Dashboard MPSFP: `resources/views/projects/mpsfp/dashboard.blade.php`
- Navegacion MPSFP: `resources/views/projects/mpsfp/_nav.blade.php`
- Importaciones:
  - `resources/views/imports/index.blade.php`
  - `resources/views/imports/show.blade.php`
  - `resources/views/imports/create.blade.php`
  - `resources/views/imports/mapping.blade.php`
  - `resources/views/imports/preview.blade.php`
- Normalizados:
  - `resources/views/products/normalized/index.blade.php`
  - `resources/views/products/normalized/show.blade.php`
  - `resources/views/products/normalized/_detail_panel.blade.php`
- Cruce proveedores:
  - `resources/views/projects/mpsfp/cross-suppliers/index.blade.php`
- Categorias:
  - `resources/views/projects/mpsfp/categories/review.blade.php`
- Catalogo maestro:
  - `resources/views/products/master/index.blade.php`
  - `resources/views/products/master/show.blade.php`

---

## 17. Lo Que Consideramos Un Buen Resultado

Stitch deberia ayudarnos a llegar a una interfaz donde:

- el usuario sepa siempre donde esta
- el usuario sepa que debe hacer despues
- el sistema diferencie claramente:
  - proveedor
  - importacion
  - producto normalizado
  - sugerencia
  - confirmacion
  - producto maestro
  - exportacion
- los listados grandes sean usables
- los estados visuales no requieran interpretar texto tecnico
- el modulo `MPSFP` se sienta como una herramienta operativa seria y no como una suma de pantallas sueltas

---

## 18. Nota Final

No buscamos una opinion estetica. Buscamos una auditoria de producto con criterio de operativa real, claridad estructural, flujo de trabajo, mantenimiento y escalabilidad.
