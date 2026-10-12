# Sistema de ventas + Encuesta UPVM

Aplicacion PHP + MySQL + jQuery Mobile publicada en Render, con la base de datos en Railway.

- **Portada:** `inicio.html`
- **Sistema de ventas (CRUD):** `index.php` (tablas `usuarios`, `productos`, `ventas`)
- **Encuesta:** `encuesta.html` -> `guardar.php` -> `regalo.html` (tabla `respuestas`)

## Base de datos (Railway)

Todas las tablas viven en la misma base de datos de Railway:

1. Importa `Sistema.sql` (sin las lineas `CREATE DATABASE` / `USE`).
2. Importa `base_datos.sql` (tabla `respuestas`).

## Variables de entorno (Render)

El codigo no contiene contrasenas. `config.php` las lee con `getenv()`:

| Variable | Contenido |
|---|---|
| `DB_HOST` | Host publico de Railway (`...proxy.rlwy.net`) |
| `DB_PORT` | Puerto publico de Railway |
| `DB_USER` | Usuario de MySQL |
| `DB_PASS` | Contrasena de MySQL |
| `DB_NAME` | Nombre de la base (`railway`) |

## Ejecutar en tu computadora

1. Crea un archivo `config.local.php` (no se sube a GitHub, esta en `.gitignore`) que devuelva un arreglo:

```php
<?php
return [
    'host' => 'localhost', 'port' => '3306',
    'database' => 'sistema', 'username' => 'root', 'password' => '',
];
```

2. Ejecuta `php -S localhost:8000` dentro de esta carpeta y abre `http://localhost:8000/inicio.html`.

## Publicar en Render

1. Sube el proyecto a GitHub (con `index.php` y `Dockerfile` en la raiz).
2. En Render crea un **Web Service** con runtime **Docker** e instancia **Free**.
3. Agrega las cinco variables de entorno de la tabla anterior.
4. Cada `git push` vuelve a publicar el sitio automaticamente.

No subas contrasenas reales a un repositorio.
