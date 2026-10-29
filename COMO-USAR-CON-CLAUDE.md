# Cómo usar el kit con Claude (u otra IA)

## Una vez por cliente nuevo

### 1. Crear carpeta de proyecto

```powershell
Copy-Item -Recurse "proyectos\_plantilla-proyecto" "proyectos\nombre-del-cliente"
```

### 2. Meter documentos

En `proyectos/nombre-del-cliente/brief/` coloca:

- Información de la empresa  
- Acerca  
- Plan de estudios / oferta / servicios  
- Branding / estilos globales  
- Carpeta de imágenes  

Pueden ser `.docx`, `.pdf` o `.md`. No hace falta renombrarlos a la fuerza.

### 3. Abrir el repo en Claude / Cursor

Abre la **raíz de este repositorio** (Site Kit) y pega:

```
Lee CLAUDE.md y AGENTS.md.
Cliente: proyectos/nombre-del-cliente/
El brief está en proyectos/nombre-del-cliente/brief/
Ejecuta prompts/PIPELINE.md completo.
Obligatorio design/anti-template.md: esta web NO puede parecerse a AV Studio ni a otros proyectos/.
Al final deja el payload en engine/site-maestro/payload/ y una copia en proyectos/nombre-del-cliente/payload/.
```

### 4. Revisar el concepto

Antes de aceptar el diseño, abre `proyectos/nombre-del-cliente/concepto.md`.  
Si el archetype o la estructura de home te suena a “otra academia dorada”, pide otro concepto.

### 5. Empaquetar e instalar

```powershell
Compress-Archive -Path "engine\site-maestro\*" -DestinationPath "proyectos\nombre-del-cliente\site-maestro.zip" -Force
```

En el hosting WordPress del cliente:

1. Hello Elementor + Elementor  
2. Subir el ZIP → Activar **Site Maestro**  
3. Un clic: **Instalar sitio completo**  
4. Runtime OK → puedes borrar el plugin instalador  

---

## Qué garantiza (y qué no)

| Garantiza el kit | Lo inventa la IA cada vez |
|------------------|---------------------------|
| Brief estandarizado | Layout / tipografía / atmósfera |
| Motor de instalación 1-clic | Templates Elementor |
| Runtime para borrar el plugin | CSS/JS de marca |
| Reglas anti-clon | Experiencia según rubro |

Si dos webs se parecen, el fallo está en saltarse `anti-template.md`, no en el motor.
