# Brief — cómo alimentar un proyecto nuevo

## Opción A (recomendada)

1. Copia `proyectos/_plantilla-proyecto` → `proyectos/nombre-cliente`
2. Pon los documentos del cliente dentro de `proyectos/nombre-cliente/brief/`
3. Usa los nombres sugeridos (abajo) o deja los originales: la IA los normaliza

## Opción B (bandeja rápida)

Suelta PDFs/DOCX/imágenes en `brief/entrada/` y dile al agente:

```
Mueve y normaliza lo de brief/entrada/ al proyecto proyectos/<slug>/
```

## Nombres sugeridos de archivos

| Archivo | Origen típico |
|---------|----------------|
| `01-empresa.md` o `.docx` / `.pdf` | Información de la empresa |
| `02-acerca.md` | Acerca / historia / fundador |
| `03-oferta.md` | Plan de estudios, servicios, catálogo |
| `04-branding.md` | Manual, estilos globales, colores |
| `05-imagenes.md` | Inventario + carpeta `imagenes/` |
| `imagenes/` | Logos y fotos |

Las plantillas vacías están en `brief/plantillas/`.
