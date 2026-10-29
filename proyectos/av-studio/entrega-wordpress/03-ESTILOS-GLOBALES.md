# 03 — Estilos globales Elementor (Site Settings)

Configura esto **antes** de diseñar páginas.

Ruta: **Elementor → Site Settings** (engranaje del editor).

---

## 1. Global Colors

| Nombre | Hex | Uso |
|--------|-----|-----|
| Ink | `#12100E` | Textos, botones oscuros |
| Ink Soft | `#2A2622` | Textos secundarios |
| Paper | `#fffcf8` | Fondos claros |
| Mist | `#f6f1ea` | Secciones suaves |
| Muted | `#7a736a` | Párrafos |
| Gold | `#c4a35a` | Acentos / CTA |
| Gold Deep | `#9a7a3c` | Eyebrows |
| Gold Soft | `#e2d0a4` | Detalles claros |
| White | `#ffffff` | — |
| Black Soft | `#100e0c` | Footer / CTA oscuro |

Asigna:
- Primary → Gold `#c4a35a`
- Secondary → Ink `#12100E`
- Text → Ink Soft
- Accent → Gold Soft

---

## 2. Global Fonts

Instala / selecciona desde Google Fonts en Elementor:

### Display (titulares)
- Familia: **Cormorant Garamond**
- Pesos: 400, 500, 600
- Estilo: Regular + Italic

### Body
- Familia: **Manrope**
- Pesos: 400, 500, 600, 700

### Escalas sugeridas

| Estilo | Fuente | Tamaño desktop | Peso |
|--------|--------|----------------|------|
| Primary Headline (H1) | Cormorant Garamond | 64–72px | 500 |
| Secondary Headline (H2) | Cormorant Garamond | 42–52px | 500 |
| H3 | Cormorant Garamond | 28–32px | 500 |
| Body | Manrope | 16px | 400 |
| Small / Eyebrow | Manrope | 11–12px | 600 |
| Button | Manrope | 12px | 600 |

Letter-spacing botones/eyebrows: `0.14em`–`0.22em`, mayúsculas.

---

## 3. Buttons (Site Settings → Buttons)

### Primario (oro)
- Fondo: Gold `#c4a35a` (o degradado `#d4b56a` → `#b08a42`)
- Texto: Ink `#12100E`
- Border radius: **999px**
- Padding: `14px 28px`
- Hover: Gold Soft / elevación suave

### Secundario (oscuro)
- Fondo: Ink
- Texto: White
- Radius: 999px

### Ghost
- Fondo: transparente
- Borde: 1px Ink o White según fondo
- Radius: 999px

---

## 4. Layout

- Content Width: **1180px**
- Gaps containers: 16–24px
- Border radius general de cards: **22px**
- Section padding vertical: `80px`–`120px`

---

## 5. Site Identity

**Apariencia → Personalizar → Identidad del sitio**
- Logo: `media/brand/monograma-av-oro.png` o logo completo
- Favicon: recorte del monograma 512×512

---

## Después de esto

1. Pega `css/elementor-custom.css` en Custom CSS  
2. Construye Header/Footer  
3. Arma Inicio con la guía 02
