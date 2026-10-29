# Importar plantillas **con fotos incluidas**

## Qué hacer (más fácil)

1. En WordPress: **Plugins → Añadir nuevo → Subir plugin**
2. Sube este archivo:

```
wordpress-elementor/av-studio-importer.zip
```

3. **Activa** el plugin **AV Studio Importer**
4. Asegúrate de tener **Elementor** activo
5. Ve a **Herramientas → AV Studio Importar**
6. Pulsa **Importar fotos + plantillas Elementor**

El plugin:
- Sube todas las imágenes a **Medios**
- Crea/actualiza plantillas Elementor (`AV Inicio`, `AV Cursos`, `AV Header`, etc.)
- Enlaza las fotos dentro de esas plantillas
- Intenta pegar el CSS en el Kit de Elementor

---

## Después del import

1. **Plantillas → Plantillas guardadas** → verás las AV *
2. Crea página **Inicio** → Editar con Elementor → insertar plantilla `AV Inicio`
3. Igual con Cursos / Contacto
4. **Theme Builder** (Pro): Header `AV Header` + Footer `AV Footer` → Entire Site
5. Asigna menú al widget de navegación del header (si pide menú)

---

## Si algo falla

- ¿Elementor activo?
- ¿Usuario administrador?
- Revisa el mensaje en la pantalla de importación
- Las fotos también quedan en **Medios** aunque reimportes (no duplica por nombre si ya existen)
