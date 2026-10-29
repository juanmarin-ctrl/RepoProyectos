# Pipeline de generación (4 fases)

Ejecuta en orden. No saltes a WordPress sin concepto aprobado (aunque sea auto-aprobado por ti tras el test de unicidad).

---

## Fase 0 — Arranque del proyecto

```text
1. Copiar proyectos/_plantilla-proyecto → proyectos/<slug>/
2. Colocar docs del cliente en proyectos/<slug>/brief/
   (o normalizar desde brief/entrada/)
3. Inventariar imágenes en brief/imagenes/ (renombrar legible: hero-01.jpg, logo.png…)
```

---

## Fase 1 — Ingest del brief

Leer todo lo disponible y producir:

`proyectos/<slug>/brief/_normalizado.md`

Secciones mínimas:

- Identidad (nombre, slogan, contacto, web/redes)
- Historia / acerca
- Oferta (cursos, servicios, productos…) con jerarquía
- Branding (colores hex, tipografías, tono, logos)
- Inventario de imágenes (archivo → uso sugerido)
- Huecos / `TODO-CLIENTE`

Prompt corto:

```
Normaliza el brief de proyectos/<slug>/brief/ a _normalizado.md.
No inventes datos de contacto. Si faltan, marca TODO-CLIENTE.
```

---

## Fase 2 — Concepto de experiencia

Producir `proyectos/<slug>/concepto.md` siguiendo el bloque de `design/anti-template.md`.

Prompt corto:

```
Con el brief normalizado, elige UNA experiencia única para este rubro.
Lee design/anti-template.md y design/experiencias-por-rubro.md.
Prohibido parecerse a AV Studio o a cualquier proyecto en proyectos/.
Escribe concepto.md completo.
```

No generes CSS ni Elementor hasta que el concepto liste secciones de home distintas.

---

## Fase 3 — Diseño + mockup (recomendado)

Producir en `proyectos/<slug>/mockup/`:

- `index.html` (+ páginas clave si aportan)
- `assets/css/styles.css` — tokens + layout (prefijo del slug)
- `assets/js/site.js` — 2–3 motions

Reglas de diseño frontend del usuario del kit:

- Una composición en el primer viewport
- Marca hero-level
- Tipografías expresivas (no Inter/Roboto/Arial/system)
- Fondo con atmósfera (no flat único sin motivo)
- Hero full-bleed por defecto
- Sin cards en el hero; cards solo si son UI interactiva
- Una job por sección

---

## Fase 4 — Payload WordPress

Rellenar:

```
engine/site-maestro/payload/
  site.json          ← válido contra design/site.schema.json
  branding.css
  frontend.js
  media/brand/
  media/stock/
  templates/         ← JSON Elementor (content[]) con tokens __SITE_MEDIA__/archivo
```

Y copiar el mismo payload a `proyectos/<slug>/payload/` como respaldo del cliente.

Tokens de media en JSON:

```text
__SITE_MEDIA__/hero-inicio.jpg
```

El motor los reemplaza por URLs de la Biblioteca al importar.

Prompt corto:

```
Genera el payload completo para site-maestro según design/site.schema.json
y el concepto.md de este cliente. CSS/JS con prefijo del slug.
Templates Elementor propios (no clones). Luego indica cómo zippear el plugin.
```

---

## Fase 5 — Empaque e instalación

```powershell
# Desde la raíz del repo
Compress-Archive -Path engine\site-maestro\* -DestinationPath proyectos\<slug>\site-maestro.zip -Force
```

En WordPress:

1. Tema Hello Elementor  
2. Plugin Elementor (+ Pro si hay)  
3. Subir `site-maestro.zip` → Activar  
4. Menú **Site Maestro** → **Instalar sitio completo**  
5. Verificar runtime → se puede eliminar el instalador  

---

## Prompt único “hazlo todo”

Si el usuario quiere un solo mensaje:

```
Eres el agente del TEDUCAS Site Kit.
Lee CLAUDE.md, AGENTS.md, design/anti-template.md y prompts/PIPELINE.md.
Cliente: proyectos/<slug>/
Ejecuta fases 1→4. Entrega concepto.md, payload en engine/site-maestro/payload/
y lista de comprobación de unicidad respondida.
No copies AV Studio ni proyectos previos.
```
