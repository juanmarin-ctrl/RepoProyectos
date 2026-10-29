# 01 — Instalación Hostinger + WordPress

## A. WordPress en Hostinger

1. Entra a [hpanel.hostinger.com](https://hpanel.hostinger.com)
2. Selecciona el hosting de **abstudioacademy.com**
3. Si WordPress **ya está instalado**: abre **WP Admin**
4. Si no:
   - **Sitios web** → **Crear / Autoinstalador** → WordPress
   - Dominio: `abstudioacademy.com`
   - Idioma: Español
   - Guarda usuario y contraseña de admin

### Ajustes iniciales (WP Admin)

**Ajustes → Generales**
- Título: `AV Studio Academy`
- Descripción: `Formación en tricología cosmética, color y química capilar`
- URL del sitio / WordPress: `https://abstudioacademy.com` (HTTPS)

**Ajustes → Enlaces permanentes**
- Estructura: **Nombre de la entrada**

**Ajustes → Lectura**
- Tu página de inicio muestra: **Una página estática**
- Página de inicio: `Inicio` (cuando la crees)
- Página de entradas: `Blog`

---

## B. Tema base (sin código para el cliente)

1. **Apariencia → Temas → Añadir**
2. Instala y activa **Hello Elementor**
3. (Opcional) Instala **Hello Elementor Child** solo si el equipo lo usa; no es obligatorio

Hello + Elementor = sitio 100% construido en Elementor, editable visualmente.

---

## C. Plugins obligatorios

Instala y activa (ver también `checklists/plugins.md`):

| Plugin | Para qué |
|--------|----------|
| **Elementor** | Constructor visual |
| **Elementor Pro** | Header/Footer Theme Builder, Forms, Loop (si hay licencia) |
| **Elementor Header & Footer Builder** | Solo si NO hay Pro |
| **Hostinger** (si viene) | Optimización hosting |
| **LiteSpeed Cache** o cache Hostinger | Velocidad |
| **WP Mail SMTP** o Hostinger SMTP | Correos del formulario |
| **Rank Math** o Yoast | SEO básico |
| **Complianz** o cookie plugin | Aviso de cookies |

### Opcional (academia / LMS — según alcance TEDUCAS)

- Tutor LMS / LearnDash / MasterStudy
- WooCommerce (si venden cursos)
- MemberPress / Restrict Content (membresías)

No instales LMS hasta definir estructura de cursos comerciales.

---

## D. Seguridad y limpieza

1. Elimina temas que no uses (salvo Hello)
2. Elimina plugins innecesarios del instalador
3. Crea usuario editor para el cliente (rol **Editor** o **Administrador** con cuidado)
4. Activa SSL (Hostinger → SSL → Forzar HTTPS)

---

## E. Subir medios

1. **Medios → Añadir nuevo**
2. Sube todo lo de:
   - `media/brand/`
   - `media/stock/`
3. Edita cada imagen importante y pon **Texto alternativo** (accesibilidad/SEO)

Sugerencia de alt:
- `monograma-av-oro.png` → “Monograma AV Studio Academy”
- `hero-inicio-salon.jpg` → “Interior de salón profesional AV Studio”

---

## F. Crear las páginas vacías

**Páginas → Añadir nueva** (solo título, sin contenido; se editan con Elementor):

1. Inicio  
2. Cursos  
3. Sobre nosotros  
4. Servicios  
5. Blog *(puede ser la página de entradas)*  
6. Contacto  
7. Aviso legal  
8. Política de privacidad  
9. Política de cookies  
10. Política de reembolsos  
11. Términos y condiciones  

Luego: **Ajustes → Lectura** → Inicio = “Inicio”, Entradas = “Blog”.

---

## G. Menú

**Apariencia → Menús** → Crear menú `Principal`:

- Inicio, Cursos, Sobre nosotros, Servicios, Blog, Contacto  
- Ubicación: Primary / Header (según Theme Builder)

Menú secundario `Legal` (footer):
- Aviso legal, Privacidad, Cookies, Reembolsos, Términos

---

## Siguiente

→ **`02-GUIA-ELEMENTOR.md`**
