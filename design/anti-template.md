# Anti-template — prohibiciones duras

Este archivo existe para que **ningún proyecto se parezca al anterior**.

## Prohibido reutilizar (entre proyectos del kit)

- Misma secuencia de secciones en home (ej. hero → stats → cursos en cards → metodología → CTA)
- Mismos nombres de clases CSS de marca (`av-*`, mismos tokens “gold luxury academy”)
- Misma pareja tipográfica (ej. serif display + sans body del proyecto AV)
- Misma atmósfera: cream `#F4F1EA` + terracotta + serif editorial
- Purple-on-white / purple→indigo gradients “AI default”
- Dark mode por defecto sin que el branding lo pida
- Hero en card flotante / imagen lateral redondeada / collage de 3 fotos
- Overlays: badges, chips, stickers, “nuevo”, “cupos limitados” sobre el hero
- Stats strip genérico (años / alumnos / países) en el primer viewport
- Grid de 3 cards idénticas como bloque principal de oferta
- Footer “4 columnas + newsletter” clonado

## Primer viewport — presupuesto estricto

Solo:

1. Marca (hero-level, no solo nav)
2. Un headline
3. Una frase de apoyo
4. Un grupo de CTA
5. Una imagen/vídeo dominante a sangrado completo (full-bleed) **salvo** que el concepto de experiencia justifique otra composición **radicalmente distinta** (editorial tipográfico, producto 3D, mapa, etc.)

Sin stats, horarios, direcciones, listados ni promos secundarias en el primer viewport.

## Cómo forzar diferencia (proceso)

Antes de diseñar, escribe en `concepto.md`:

```md
## Experiencia
- Rubro:
- Archetype elegido: (uno, inventado o de experiencias-por-rubro.md)
- Archetypes rechazados y por qué:
- Referencias de atmósfera (NO clones): 2–3 sensaciones, no URLs de templates
- Estructura home (lista ordenada de secciones — debe ser única):
- Tipografía display / body (nombres concretos de Google Fonts u otras):
- Sistema de color (hex + roles):
- Motion (2–3 gestos):
- Qué haría que esta web NO se confunda con una academia de belleza:
```

Si no puedes rellenar eso con decisiones claras, **no generes código**.

## Checklist pre-entrega

- [ ] No se reutilizó JSON Elementor de otro cliente
- [ ] `body_class` y prefijo CSS son del slug del cliente (`site-<slug>`, `.s-<slug>-*`)
- [ ] `site.json.experience.archetype` es distinto al del proyecto anterior
- [ ] Colores/tipografías salen del branding del cliente (o de una decisión justificada)
- [ ] Oferta/curriculum tiene un layout propio del rubro (no “3 cards de curso” por defecto)
