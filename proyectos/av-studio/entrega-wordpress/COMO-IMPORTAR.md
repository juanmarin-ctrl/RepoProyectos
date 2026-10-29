# ¿Se puede “importar” el mockup a Elementor?

## Respuesta corta

**Sí, parcialmente.** Elementor **no importa un HTML completo** y lo convierte solo en bloques editables.

Lo que **sí** puedes hacer:

| Método | ¿Editable fácil? | Qué preparé |
|--------|------------------|-------------|
| **1. Importar plantillas JSON** (recomendado) | Sí — widgets Elementor | Carpeta `import-elementor/templates/` |
| **2. Kit / Site Settings** | Sí | Colores + CSS ya documentados |
| **3. Pegar HTML en widget HTML** | No (código) | No recomendado para el cliente |
| **4. Convertidores externos** | Variable | UiChemy / servicios de pago |

Por eso el camino correcto es: **Importar plantillas → ajustar imágenes → publicar**.

---

## Método recomendado (10–20 min)

### 1. Requisitos
- WordPress + **Elementor** activo (Pro ayuda para Header/Footer tipo Theme Builder)
- Tema **Hello Elementor**

### 2. Importar plantillas
1. WP Admin → **Plantillas → Plantillas guardadas**  
   (o **Templates → Saved Templates**)
2. Clic en **Importar plantillas**
3. Sube uno a uno (o el ZIP) desde:

```
wordpress-elementor/import-elementor/
```

Archivos:
- `av-header.json` — Header
- `av-footer.json` — Footer
- `av-inicio.json` — Página Inicio
- `av-cursos.json` — Página Cursos
- `av-contacto.json` — Contacto
- `av-pagina-interior.json` — Base para Sobre nosotros / Servicios / Legales
- `av-templates.zip` — todo empaquetado

### 3. Aplicar Header / Footer
**Con Elementor Pro:**
1. Plantillas → Theme Builder  
2. Crear Header → **Insertar** → Mis plantillas → `AV Header`  
3. Condición: Entire Site → Publicar  
4. Igual con Footer  

**Sin Pro:**  
Plugin “Elementor Header & Footer Builder” → asignar las plantillas importadas.

### 4. Crear páginas y cargar plantilla
1. Páginas → Añadir → “Inicio”  
2. Editar con Elementor  
3. Icono de carpetas → **Mis plantillas** → insertar `AV Inicio`  
4. Repite con Cursos, Contacto, etc.  
5. **Reemplaza imágenes** por las de `media-para-subir.zip` (las plantillas usan placeholders Unsplash)

### 5. Pegar CSS y colores
1. `03-ESTILOS-GLOBALES.md` → Site Settings  
2. Pegar `css/elementor-custom.css` en Custom CSS  

---

## Qué NO hace el import automáticamente
- No sube tus fotos locales (hay que vincularlas en Medios)
- No crea el menú de WordPress (hay que asignarlo al widget Nav)
- No configura SMTP / formularios reales
- Las animaciones “salón” del mockup HTML son más suaves en Elementor (Motion Effects + CSS)

---

## Si quieres “un solo clic”
Elementor Kit completo (todo el sitio) solo se genera **exportando desde un WP ya montado**.  
Flujo ideal del equipo:
1. Montar 1 vez con estas plantillas  
2. **Elementor → Tools → Export Kit**  
3. Ese `.zip` sí se importa en otros WordPress del cliente en un clic  

Cuando tengas el primer sitio armado, exportamos el Kit oficial.
