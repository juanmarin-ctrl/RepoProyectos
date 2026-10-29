# Engine — Site Maestro

Plugin WordPress **genérico**. No contiene la marca del cliente.

## Anatomía

| Parte | ¿Cambia por cliente? |
|-------|----------------------|
| `site-maestro.php` + `includes/` + `runtime/` | No |
| `payload/` | **Sí** — lo genera la IA |

## Instalar en un WordPress

1. Asegúrate de que `payload/site.json` es del cliente correcto (no el placeholder).
2. Comprime la carpeta `site-maestro` (debe quedar `site-maestro/site-maestro.php` en la raíz del ZIP).
3. Plugins → Subir → Activar.
4. Menú **Site Maestro** → Instalar sitio completo.
5. Cuando diga Runtime OK → puedes eliminar el instalador.

## Relación con AV Studio Maestro

El plugin viejo está en `proyectos/av-studio/entrega-wordpress/av-studio-importer/` (instalador hardcodeado de un solo cliente).  
Site Maestro es la versión reutilizable: mismos conceptos (import + runtime MU), sin textos/CSS/plantillas fijos.
