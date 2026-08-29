# Carpeta Universal de Instalación (Laravel + Laragon)

Esta carpeta es **100% genérica y reutilizable (Plug & Play)**. Puedes copiarla directamente a cualquier proyecto Laravel dentro de `C:\laragon\www\` y funcionará automáticamente sin modificar código.

---

## ⚙️ Detección Automática Inteligente

Todos los scripts leen de manera dinámica la configuración de tu proyecto desde el archivo `.env`:
* **`APP_NAME`:** Se usa automáticamente para el nombre del icono en el Escritorio (ej. `MiSistema - TPV.lnk`) y los mensajes del instalador.
* **`APP_URL`:** Se detecta automáticamente para abrir la URL exacta de tu proyecto en el navegador.
* **Ícono (`.ico`):** Si colocas un archivo `.ico` dentro de `instalar/` o tienes `public/favicon.ico`, lo asocia automáticamente al acceso directo.
* **Git Branch:** `actualizar_sistema.bat` detecta automáticamente tu rama actual de Git para actualizar el código y ejecutar migraciones.

---

## 📁 Archivos incluidos

| Archivo | Descripción |
|---------|-------------|
| `configurar_inicio_automatico.vbs` | Registra Laragon para iniciar automáticamente con Windows con todos los servicios encendidos. **Ejecutar primero.** |
| `crear_acceso_directo.vbs` | Crea el ícono del sistema en el Escritorio del cliente con el nombre y URL leídos desde `.env`. **Ejecutar segundo.** |
| `iniciar_sistema.vbs` | Lanzador invisible universal (sin ventana negra de CMD). |
| `abrir_sistema.bat` | Script inteligente que verifica si Apache/Nginx están corriendo y abre el sistema de inmediato. |
| `actualizar_sistema.bat` | Script de 1 clic que descarga cambios de Git, ejecuta migraciones y optimiza la caché de Laravel. |
| `*.ico` | *(Opcional)* Ícono personalizado del proyecto para el acceso directo del Escritorio. |

---

## 🚀 Pasos de instalación en la PC del cliente

### Paso 1 - Instalar Laragon
1. Descargar e instalar Laragon en `C:\laragon\`.

### Paso 2 - Copiar el proyecto
1. Copiar la carpeta del proyecto a `C:\laragon\www\tuProyecto\`.

### Paso 3 - Configurar inicio automático
1. Hacer **doble clic** en `configurar_inicio_automatico.vbs`.

### Paso 4 - Crear el ícono en el Escritorio
1. Hacer **doble clic** en `crear_acceso_directo.vbs`.

¡Listo! El cliente tendrá su acceso directo configurado y funcionando.
