# Aplicación MVC en PHP con DuckDB

Este proyecto es un ejemplo sencillo de una aplicación PHP que implementa el patrón Modelo-Vista-Controlador (MVC) con autenticación de usuarios utilizando DuckDB como base de datos y estilos basados en Bootstrap.

## Requisitos

- PHP 8.2 o superior con la extensión `pdo_duckdb` habilitada.
- Composer opcional si deseas añadir dependencias adicionales.

## Puesta en marcha

1. Clona el repositorio y sitúate en la carpeta del proyecto.
2. Inicia el servidor embebido de PHP apuntando al directorio `public`:
   ```bash
   php -S localhost:8000 -t public
   ```
3. Accede a `http://localhost:8000` en tu navegador.

La primera ejecución creará automáticamente el archivo de base de datos `storage/app.duckdb` y la tabla `users`.

## Estructura del proyecto

```
app/
  controllers/   Controladores de la aplicación.
  core/          Componentes base (Router, App, Database, etc.).
  models/        Modelos que interactúan con DuckDB.
  views/         Plantillas de la interfaz usando Bootstrap.
config/          Configuración global.
public/          Punto de entrada público (`index.php`).
storage/         Archivos de base de datos DuckDB.
```

## Funcionalidades

- Registro de usuarios con validaciones básicas.
- Inicio y cierre de sesión.
- Protección de la página principal para usuarios autenticados.
- Interfaz adaptada con Bootstrap 5.

## Notas

- El formulario de cierre de sesión incluye un campo oculto con el `session_id` para facilitar la implementación de protección CSRF si se desea.
- Puedes extender la aplicación añadiendo nuevos controladores y modelos siguiendo el patrón establecido.
