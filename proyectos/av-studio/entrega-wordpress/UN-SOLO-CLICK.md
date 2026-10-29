# AV Studio Maestro — Un solo clic

## Qué hace el botón

1. Sube **todas las fotos** a Medios  
2. Crea **plantillas Elementor**  
3. Crea **todas las páginas** ya diseñadas (Inicio, Cursos, Contacto, Sobre nosotros, Servicios, Legales, Blog)  
4. Crea **menú principal + menú legal** y los asigna  
5. Pone **Inicio** como portada y **Blog** como entradas  
6. Publica **3 posts** de ejemplo  
7. Aplica el **CSS** de marca  
8. Configura **Header/Footer** (Theme Builder si hay Elementor Pro; si no, los embebe en las páginas)  
9. Instala el **runtime** en `wp-content/mu-plugins/` (estilos + JS + blog) para que puedas **borrar este plugin** sin romper el diseño

---

## Cómo usarlo

1. Instala y activa **Elementor** (Pro recomendado)  
2. Tema recomendado: **Hello Elementor**  
3. **Plugins → Subir** → `av-studio-importer.zip` (**versión 2.7.2**)  
4. Activa **AV Studio Maestro**  
5. Menú lateral: **AV Studio**  
6. Pulsa **REPARAR / INSTALAR SITIO COMPLETO**  
7. Espera 1–2 minutos y lee el **Log de instalación** en esa misma pantalla  
8. Abre el sitio (Ctrl+F5)

### Si ya tenías una versión anterior

**Sube el ZIP 2.7.2** (reemplaza el plugin). Luego:

1. AV Studio → **REPARAR** (actualiza fotos + datos de empresa)  
2. Cuando veas el aviso verde «Runtime instalado», puedes **Desactivar → Eliminar** AV Studio Maestro  
3. Ctrl+F5 en el sitio

Qué cambia la **2.7.2**:

- Datos de empresa corregidos (contacto, dirección GA, historia, misión/visión, slogan)  
- Banco de fotos reales + refresh al reparar (2.7.1)  
- Runtime MU-plugin: puedes borrar el Maestro sin perder estilos (2.7.0)

El log también queda en `wp-content/uploads/avsi-import-log.txt`.  
Si una página dice `FALLBACK-generic` o `verify_els=0`, copia el log y envíalo.  
En el log verás `Pro: Header #… / Footer #… → Entire Site` cuando el header/footer quedan activos.

Listo. Para editar: Páginas → Editar con Elementor.

---

## Borrar el plugin sin romper estilos

1. Asegúrate de ver **Runtime instalado** en AV Studio (o pulsa «INSTALAR RUNTIME…»)  
2. Plugins → **AV Studio Maestro → Desactivar → Eliminar**  
3. Las páginas, medios y el diseño **se quedan**  
4. Lo que NO debes borrar: `mu-plugins/av-studio-runtime.php` y `mu-plugins/av-studio-runtime/`

---

## Archivo (elige uno) — versión 2.7.2

- Fácil: `C:\Users\jagmr\Downloads\av-studio-importer.zip`
- Proyecto: `wordpress-elementor/av-studio-importer.zip`

Sube el **.zip completo**, sin descomprimir.

---

## Si sale «El archivo del plugin no existe»

1. Borra el plugin si aparece a medias: Plugins → AV Studio Maestro → Eliminar  
2. Sube de nuevo **solo** `av-studio-importer.zip` (no la carpeta suelta)  
3. Si Hostinger corta la subida: File Manager → `wp-content/plugins/` → Subir el ZIP → Extraer ahí → Activar en WordPress  
4. Dentro debe quedar: `wp-content/plugins/av-studio-importer/av-studio-importer.php`
