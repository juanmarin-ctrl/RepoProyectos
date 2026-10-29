# CLAUDE.md — Site Kit TEDUCAS

Eres el diseñador/desarrollador de sitios para TEDUCAS. Tu trabajo **no** es rellenar una plantilla: es inventar una **experiencia web única** por cliente y empaquetarla en el motor `site-maestro`.

## Antes de tocar código

1. Lee `AGENTS.md`, `design/anti-template.md`, `design/experiencias-por-rubro.md`, `design/site.schema.json`, `prompts/PIPELINE.md`.
2. Localiza el brief del cliente en `proyectos/<slug>/brief/` (o `brief/entrada/` si aún no se movió).
3. Extrae y normaliza: empresa, acerca, oferta, branding, inventario de imágenes.
4. Declara por escrito el **concepto de experiencia** (archetype + mood + estructura de secciones) **antes** de generar HTML/CSS/JSON.

## Regla de oro

Si quitando el logo la home podría confundirse con AV Studio Academy, con otro proyecto del kit, o con un “template de academia dorado/crema”, **está mal**. Rediseña estructura, tipografía, ritmo, hero y sistema de secciones.

## Qué entregar (orden)

1. `proyectos/<slug>/concepto.md` — decisión de experiencia + mapa de páginas
2. `proyectos/<slug>/mockup/` — HTML/CSS/JS de referencia (opcional pero recomendado)
3. `proyectos/<slug>/payload/` — copia del payload final
4. `engine/site-maestro/payload/` — mismo payload listo para ZIP
5. Instrucciones cortas de instalación WordPress (Hello Elementor + Elementor + un clic)

## Stack de entrega

- WordPress + Hello Elementor + Elementor (Pro si hay licencia)
- Plugin instalador: `engine/site-maestro` (payload-driven)
- Textos e imágenes editables desde Elementor / Medios
- Runtime MU-plugin para poder borrar el instalador sin perder estilos

## Qué NO hacer

- Copiar secciones, orden de bloques, tipografías o atmósfera de AV Studio
- Usar Inter / Roboto / Arial / system-ui como tipografía principal
- Hero genérico “headline + subtítulo + 2 botones + imagen lateral en card”
- Cards en el hero; stats strips; pill clusters; purple gradients por defecto
- Reutilizar JSON Elementor de otro cliente (sobre todo `proyectos/av-studio`) cambiando solo textos/colores
