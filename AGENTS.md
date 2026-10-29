# AGENTS.md — instrucciones universales (cualquier entorno de IA)

Aplica en Claude, Cursor, Copilot, Windsurf, Continue, etc.

## Misión

Transformar un **brief de cliente** (documentos + branding + imágenes) en un **sitio WordPress instalable con un clic**, con experiencia visual **exclusiva** para ese rubro y ese cliente.

## Inputs esperados (brief)

El agente debe aceptar cualquiera de estos formatos y normalizarlos:

| Documento | Contiene |
|-----------|----------|
| Información de la empresa | Nombre legal, marca, contacto, ubicación, historia corta, propuesta de valor |
| Acerca / About | Biografía, misión, visión, valores, equipo, narrativa |
| Plan de estudios / Oferta / Servicios | Catálogo, módulos, precios si aplica, CTAs |
| Branding / Manual / Estilos globales | Colores, tipografías, logo, tono de voz, usos prohibidos |
| Imágenes | Logos, fotos de producto/espacio/persona, mood |

Si falta un documento, **pregunta** o infiere con cuidado y márcalo como `TODO-CLIENTE` en el contenido.

## Pipeline obligatorio

Seguir `prompts/PIPELINE.md`:

1. **Ingest** — volcar brief a markdown estructurado  
2. **Concepto** — elegir experiencia única (ver anti-template)  
3. **Diseño** — tokens + layout + motion (sin clonar proyectos previos)  
4. **Contenido** — páginas, menús, posts demo, legales base  
5. **Payload** — rellenar `site.json` + CSS + JS + media + templates Elementor  
6. **Empaque** — ZIP del plugin `site-maestro` con ese payload  

## Contrato de salida

El motor solo entiende lo definido en `design/site.schema.json`.  
Todo lo específico del cliente vive en `payload/`. El PHP del motor **no** debe hardcodear marca, colores ni textos del cliente.

## Test de unicidad (obligatorio antes de entregar)

Responde SÍ/NO:

1. ¿La home tiene un hero estructuralmente distinto al del último proyecto del kit?  
2. ¿La tipografía display es distinta y no es Inter/Roboto/Arial?  
3. ¿El primer viewport pasaría el “brand test” (marca dominante, no genérica)?  
4. ¿Hay al menos 2–3 motions intencionales propias de esta experiencia?  
5. ¿Si se cambia el logo, seguiría sintiéndose del mismo rubro/cliente?  

Si alguna es NO → no entregar; rediseñar.

## Relación con AV Studio

`proyectos/av-studio/` es el **primer proyecto** (caso cerrado).  
Úsalo para entender el instalador legacy y para **alejarte** de su look (`concepto.md`). Nunca como skin a reutilizar.
