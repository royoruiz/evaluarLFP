# Aplicación MVC en PHP con MySQL

Este proyecto es un ejemplo sencillo de una aplicación PHP que implementa el patrón Modelo-Vista-Controlador (MVC) con autenticación de usuarios utilizando MySQL como base de datos y estilos basados en Bootstrap.

## Requisitos

- PHP 8.2 o superior con la extensión `pdo_mysql` habilitada.
- Un servidor MySQL accesible con una base de datos disponible para la aplicación.
- Composer opcional si deseas añadir dependencias adicionales.

## Configuración

La aplicación se configura mediante variables de entorno o modificando `config/config.php`. También puedes copiar `config/config.local.example.php` a `config/config.local.php` (incluido en `.gitignore`) para definir valores específicos de tu máquina sin afectar al repositorio. Los valores disponibles son:

- `DB_HOST` (por defecto `127.0.0.1`)
- `DB_PORT` (por defecto `3306`)
- `DB_NAME` (por defecto `mvc_app`)
- `DB_USER` (por defecto `root`)
- `DB_PASS` (cadena vacía por defecto)
- `DB_CHARSET` (por defecto `utf8mb4`)
- `BASE_URL` (por defecto `/`)

Asegúrate de crear la base de datos indicada en `DB_NAME` si no existe. Al iniciar la aplicación se creará automáticamente la tabla `users` si aún no está presente.

## Puesta en marcha

1. Clona el repositorio y sitúate en la carpeta del proyecto.
2. Inicia el servidor embebido de PHP apuntando al directorio `public`:
   ```bash
   php -S localhost:8000 -t public
   ```
3. Accede a `http://localhost:8000` en tu navegador.

## Estructura del proyecto

```
app/
  controllers/   Controladores de la aplicación.
  core/          Componentes base (Router, App, Database, etc.).
  models/        Modelos que interactúan con la base de datos.
  views/         Plantillas de la interfaz usando Bootstrap.
config/          Configuración global.
public/          Punto de entrada público (`index.php`).
storage/         Carpeta disponible para recursos adicionales si se necesitan.
```

## Funcionalidades

- Registro de usuarios con validaciones básicas.
- Inicio y cierre de sesión.
- Protección de la página principal para usuarios autenticados.
- Interfaz adaptada con Bootstrap 5.

## Notas

- El formulario de cierre de sesión incluye un campo oculto con el `session_id` para facilitar la implementación de protección CSRF si se desea.
- Puedes extender la aplicación añadiendo nuevos controladores y modelos siguiendo el patrón establecido.
