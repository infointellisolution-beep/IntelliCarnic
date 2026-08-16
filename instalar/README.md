# IntelliCarnic - Carpeta de Instalación

Esta carpeta contiene los scripts necesarios para instalar el sistema en la PC de cada cliente.

---

## 📁 Archivos incluidos

| Archivo | Descripción |
|---------|-------------|
| `configurar_inicio_automatico.vbs` | Configura Laragon para iniciar automáticamente cuando se enciende la PC (Opción 2 / Nativa). **Ejecutar primero.** |
| `crear_acceso_directo.vbs` | Crea el ícono **"IntelliCarnic - TPV"** en el Escritorio del cliente. **Ejecutar segundo.** |
| `IntelliCarnic.vbs` | Lanzador invisible (sin ventana negra). **No ejecutar directamente.** Es el núcleo del ícono del Escritorio. |
| `abrir_sistema.bat` | Script inteligente que verifica si Laragon está activo antes de abrir el navegador. **No ejecutar directamente.** |
| `intellicarnic.ico` | *(Opcional)* Icono personalizado del sistema. Colocar aquí para que aparezca en el Escritorio. |

---

## 🚀 Pasos de instalación en la PC del cliente

### Paso 1 - Instalar Laragon
1. Descargar e instalar Laragon desde [laragon.org](https://laragon.org/download).
2. Asegurarse de instalar en la ruta predeterminada: `C:\laragon\`.

### Paso 2 - Copiar el proyecto
1. Copiar la carpeta del proyecto `intelliCarnic` a: `C:\laragon\www\intelliCarnic\`.

### Paso 3 - Configurar la base de datos
1. Abrir Laragon → Terminal → `php artisan migrate --force`.

### Paso 4 - Configurar inicio automático (Opción 2 Nativa)
1. Hacer **doble clic** en `configurar_inicio_automatico.vbs`.
2. Confirmar el mensaje de éxito.

### Paso 5 - Crear el ícono en el Escritorio
1. *(Opcional)* Colocar el archivo `intellicarnic.ico` en esta carpeta `instalar\`.
2. Hacer **doble clic** en `crear_acceso_directo.vbs`.
3. Confirmar el mensaje de éxito.

---

## 🛡️ Comportamiento del ícono del Escritorio

```
Cliente hace doble clic en el ícono
          │
          ▼
¿Laragon está corriendo?
    │               │
   SÍ              NO
    │               │
    ▼               ▼
Abre el        Enciende Laragon
navegador      automáticamente
al instante    y abre el sistema
```

- **Situación normal** → Laragon inicia con Windows → ícono abre el sistema **al instante**.
- **Si alguien cerró Laragon accidentalmente** → El ícono detecta que está apagado, lo enciende automáticamente y abre el sistema. **Auto-recuperación transparente para el cliente.**
