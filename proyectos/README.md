# Proyectos por cliente

Este repositorio **es** el Site Kit. Cada carpeta en `proyectos/` = un cliente = una experiencia distinta.

```
proyectos/
  av-studio/               ← primer proyecto (referencia; no clonar look)
  _plantilla-proyecto/     ← copiar esto para clientes nuevos
  (proximo-cliente)/
```

## Crear un proyecto nuevo

```powershell
Copy-Item -Recurse "proyectos\_plantilla-proyecto" "proyectos\nombre-cliente"
```

1. Llena `proyectos/nombre-cliente/brief/`
2. Abre Claude/Cursor en la **raíz del repo**
3. Pega el prompt de `COMO-USAR-CON-CLAUDE.md` o `prompts/PIPELINE.md`
4. Guarda el ZIP en `proyectos/nombre-cliente/site-maestro.zip`

## Regla

Nunca copies `payload/` o `mockup/` de un proyecto a otro como base visual.  
Antes de diseñar, mira `av-studio/concepto.md` y **aleja** tipografía, atmósfera y orden de secciones.
