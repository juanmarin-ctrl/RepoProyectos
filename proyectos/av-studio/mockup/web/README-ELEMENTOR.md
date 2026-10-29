# AV Studio Academy — Mockup para WordPress Elementor

Mockup HTML visual completo, listo para **recrear sección a sección en Elementor** (no es un tema bajo código para el cliente).

## Marca aplicada

| Token | Valor |
|-------|--------|
| Nombre | AV Studio Academy · América Villasmil |
| Dominio | abstudioacademy.com |
| Email | contacto@abstudioacademy.com |
| Colores | Negro `#0C0C0C` · Blanco `#FFFFFF` · Mist `#F3F1EE` · Oro `#B8954F` |
| Tipografía | Display: Cormorant Garamond · Body: Manrope |
| Estilo | Quiet luxury, editorial, mucho aire, tipografía Didot-like |

## Páginas del mockup

1. `index.html` — Inicio  
2. `cursos.html` — Cursos (Tricología, Ciencia del Color, Corrección)  
3. `sobre-nosotros.html` — Sobre nosotros  
4. `servicios.html` — Servicios academia + salón  
5. `blog.html` — Blog (entradas WP)  
6. `contacto.html` — Contacto  
7. `aviso-legal.html`  
8. `privacidad.html`  
9. `cookies.html`  
10. `reembolsos.html`  
11. `terminos.html`  

## Cómo ver el mockup

Abrir `index.html` en el navegador, o servir la carpeta:

```bash
npx --yes serve "mockup-web"
```

## Mapeo a Elementor (autoeditable)

| Mockup | Elementor |
|--------|-----------|
| Header / Footer JS | Theme Builder → Header / Footer |
| `.section` | Container / Section |
| `.hero` | Container full-width + Background Image + Overlay |
| `.btn` | Widget Button (estilos globales) |
| `.course-card` | Loop Grid / Posts / Containers anidados |
| Blog cards | CPT Post + Loop o Archive |
| Formulario | Elementor Form + SMTP |
| Textos legales | Páginas WP + plantilla simple |
| Colores / tipografías | Site Settings → Global Colors & Fonts |
| Animaciones `.reveal` | Motion Effects / Entrance |

## Autogestión del cliente

- Textos e imágenes: editar widgets Heading, Text Editor, Image.  
- Cursos nuevos: duplicar sección/card o CPT “Curso”.  
- Blog: entradas nativas de WordPress.  
- Legal: páginas de texto.  
- **Sin tocar código** si se monta con Elementor + estilos globales.

## Siguiente paso recomendado

1. Validar mockup con el cliente.  
2. Subir a Hostinger / WordPress (`abstudioacademy.com`).  
3. Instalar Elementor (+ Pro si hay Theme Builder / Loop).  
4. Cargar Global Styles con la paleta y tipografías.  
5. Replicar cada página del mockup.  
6. Conectar LMS / pasarela / formularios según alcance TEDUCAS.
