# TEDUCAS Site Kit — webs distintas por cliente (AI-first)

Monorepo del **plugin/motor reutilizable** + **un folder por cliente**.

No es una plantilla con colores cambiados. Cada proyecto en `proyectos/` debe ser una experiencia visual y estructural propia.

---

## Estructura del repo

```
├── CLAUDE.md / AGENTS.md     ← instrucciones para la IA
├── design/                   ← anti-template + schema + rubros
├── prompts/                  ← pipeline de generación
├── brief/                    ← plantillas + bandeja de entrada
├── engine/site-maestro/      ← plugin WordPress genérico (1 clic)
└── proyectos/
    ├── av-studio/            ← primer cliente (referencia)
    └── _plantilla-proyecto/  ← copiar para el siguiente
```

## Flujo en 4 pasos

```
1. Copiar  proyectos/_plantilla-proyecto  →  proyectos/nombre-cliente
2. Meter docs e imágenes en  brief/
3. Abrir este repo en Claude / Cursor y pegar el prompt de arranque
4. Empaquetar engine/site-maestro  →  instalar en WordPress → un clic
```

### Prompt de arranque

```
Lee CLAUDE.md y AGENTS.md.
Este proyecto está en: proyectos/<nombre-cliente>/
El brief del cliente está en: proyectos/<nombre-cliente>/brief/
Sigue prompts/PIPELINE.md.
Obligatorio: design/anti-template.md — NO parecerse a proyectos/av-studio ni a otros proyectos/.
Salida: payload en engine/site-maestro/payload/ + copia en proyectos/<nombre-cliente>/payload/.
```

Guía detallada: **`COMO-USAR-CON-CLAUDE.md`**

## Principio

```
MOTOR (igual siempre)     engine/site-maestro/
PAYLOAD (único/cliente)   generado por la IA desde el brief
```

## ¿Por qué todo en un solo repo?

- El motor, prompts y reglas anti-clon se mantienen sincronizados  
- La IA puede contrastar con `proyectos/av-studio` y evitar clones  
- Un solo clone para el equipo TEDUCAS  

**Cuidado:** los bancos de fotos engordan el repo. Valora Git LFS o no versionar `brief/imagenes/**` en clientes futuros.
