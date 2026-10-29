# Payload del cliente

Esta carpeta la **rellena la IA** por proyecto. El PHP del motor no debe hardcodear marca.

## Contenido mínimo

```
site.json          # obligatorio — ver design/site.schema.json
branding.css       # tokens + atmósfera (prefijo del slug)
frontend.js        # motions
media/brand/       # logos
media/stock/       # fotos
templates/         # *.json Elementor con title, type, content[]
```

## Tokens de imagen en JSON Elementor

```
__SITE_MEDIA__/nombre-archivo.jpg
```

## Ejemplo mínimo de template JSON

```json
{
  "title": "Inicio",
  "type": "page",
  "content": []
}
```

Vaciar / regenerar este payload en cada cliente nuevo. No reutilizar JSON de otro proyecto cambiando textos.
