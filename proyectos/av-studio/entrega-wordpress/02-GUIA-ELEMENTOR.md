# 02 — Reconstruir el mockup en Elementor (autoeditable)

Ten abierto el mockup: `../mockup-web/index.html` (o http://127.0.0.1:5173) como referencia visual.

---

## 1. Site Settings (hacer PRIMERO)

**Elementor → Site Settings** (o engranaje del editor)

Sigue **`03-ESTILOS-GLOBALES.md`** al pie de la letra:
- Global Colors
- Global Fonts
- Buttons
- Container / Layout (ancho 1180px)

Luego: **Site Settings → Custom CSS**  
Pega el contenido de `css/elementor-custom.css` y **Actualizar**.

---

## 2. Theme Builder — Header

**Plantillas → Theme Builder → Header → Añadir**

### Estructura recomendada (1 Container horizontal)

1. **Container** full width, padding lateral, sticky = Top  
2. Dentro: Container “píldora” (borde redondeado 999px)
   - Fondo hero: `rgba(12,10,8,0.55)` + blur (Custom CSS clase `av-header-glass`)
   - Fondo scrolled: usar Motion/conditions o CSS `.elementor-sticky--effects`

**Widgets (izquierda → derecha):**
| Widget | Contenido |
|--------|-----------|
| Imagen | `monograma-av-oro.png` (circular) |
| Título / HTML | AV STUDIO + Academy |
| Menú Nav | Menú Principal |
| Botón | “Ver cursos” → página Cursos |

**Condiciones de visualización:** Entire Site  
**Publicar.**

> Sin Elementor Pro: usa el plugin “Elementor Header & Footer Builder” con la misma estructura.

---

## 3. Theme Builder — Footer

Fondo oscuro `#100e0c`, tipografía clara, 4 columnas:

1. Logo + párrafo academia  
2. Academia (enlaces)  
3. Contacto (`contacto@abstudioacademy.com`, Calbuco)  
4. Legal (menú Legal)

Condición: Entire Site. Publicar.

---

## 4. Página Inicio (orden de secciones)

Editar **Inicio → Editar con Elementor**. Usa **Containers** (no sections antiguas si puedes).

### Sección 1 — Hero
- Container min-height: 100vh  
- Background: imagen `hero-inicio-salon.jpg` + Overlay negro 45–70%  
- Widgets: eyebrow “AV Studio Academy”, H1, texto, 2 botones  
- Motion: Fade In Up en el bloque de texto  

### Sección 2 — Marquee / franja
- Texto repetido o widget “Text Marquee” (Pro) / Heading simple con keywords  

### Sección 3 — La academia + stats
- 2 columnas: texto + grid de 4 stats (Containers anidados)  
- Imágenes laterales del collage con `salon-interior`, `herramientas`, `fibra-brillo`  

### Sección 4 — Cursos (3 cards)
- 3 Containers: imagen + chip + título + texto + botón  
- Duplicar card para nuevos cursos (así el cliente edita fácil)  

### Sección 5 — Metodología
- Imagen `metodologia-retrato` + lista 01/02/03  

### Sección 6 — Atmósfera / mosaico fotos
- Gallery o Containers con imágenes  

### Sección 7 — Servicios preview (3 tiles)
- Imagen de fondo + título + texto  

### Sección 8 — Testimonios (3 quotes)  

### Sección 9 — Blog preview
- Si Pro: **Loop Grid** de Posts  
- Si no: 3 cards manuales o widget Posts  

### Sección 10 — CTA final
- Fondo oscuro/oro, título + botones  

**Publicar** y marcar como página de inicio.

---

## 5. Resto de páginas (método)

Para cada página: abrir mockup correspondiente + `contenidos/*.md` → pegar textos → mismas reglas de Containers.

| Página WP | Mockup | Contenido |
|-----------|--------|-----------|
| Cursos | `cursos.html` | `contenidos/cursos.md` |
| Sobre nosotros | `sobre-nosotros.html` | `contenidos/sobre-nosotros.md` |
| Servicios | `servicios.html` | `contenidos/servicios.md` |
| Contacto | `contacto.html` | `contenidos/contacto.md` |
| Blog | Archive | Entradas WP |
| Legales | legales | `contenidos/legales.md` |

**Truco de velocidad:** en Inicio, guarda secciones útiles como **Plantilla de bloque** (Guardar → Block) y reutilízalas (hero interior, CTA, cards).

---

## 6. Formulario de Contacto

Widget **Form** (Pro) o shortcode de Forminator/WPForms:

Campos: Nombre, Email, Teléfono, Interés (select), Mensaje  
Acción: Email a `contacto@abstudioacademy.com`  
Configura SMTP (Hostinger → Emails o WP Mail SMTP).

---

## 7. Cómo edita el cliente (sin código)

1. WP Admin → **Páginas** → elegir página  
2. **Editar con Elementor**  
3. Clic en el texto / imagen → cambiar  
4. **Actualizar**  

Para un curso nuevo: duplicar una card de Cursos → cambiar imagen y textos.

---

## 8. Checklist de calidad antes de entregar

- [ ] Header sticky legible en hero y al scroll  
- [ ] Footer con legales  
- [ ] Menú móvil OK  
- [ ] Formulario prueba real llega al correo  
- [ ] Imágenes comprimidas (WebP si Hostinger lo ofrece)  
- [ ] Página 404 básica  
- [ ] Cookies banner activo  
- [ ] HTTPS forzado  
- [ ] El cliente puede editar un título sin ayuda  

---

## Siguiente

→ Configura colores/fuentes en **`03-ESTILOS-GLOBALES.md`**  
→ CSS en **`css/elementor-custom.css`**
