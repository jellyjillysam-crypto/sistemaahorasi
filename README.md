# Sistema de ventas + Encuesta UPVM

Aplicacion PHP + MySQL + jQuery Mobile publicada en Render, con la base de datos en Railway.

- **Menu principal:** `inicio.php` (pide iniciar sesion; `/` redirige al login si no hay sesion)
- **Sistema de ventas (CRUD):** `index.php` (tablas `usuarios`, `productos`, `ventas`)
- **Encuesta:** `encuesta.html` -> `guardar.php` -> `regalo.html` (tabla `respuestas`)
- **Login y metricas:** `registro.php`, `login.php`, `bienvenida.php` (Mi cuenta), `dashboard.php` (tablas `usuarios` y `eventos`)

## Base de datos (Railway)

Todas las tablas viven en la misma base de datos de Railway:

1. Importa `Sistema.sql` (sin las lineas `CREATE DATABASE` / `USE`). Ya incluye las columnas de login en `usuarios` y la tabla `eventos`.
2. Importa `base_datos.sql` (tabla `respuestas`).
3. **Si ya tenias la base creada**, no vuelvas a importar `Sistema.sql`: ejecuta una sola vez `migracion_login_metricas.sql`
   (agrega `password_hash`, `sexo`, `edad`, `carrera` a `usuarios` y crea `eventos`).

## Login y metricas

- Una sola tabla `usuarios`: quien se registra en `registro.php` tambien aparece en el CRUD de ventas.
  Los usuarios creados desde el CRUD no tienen contrasena, asi que no pueden iniciar sesion.
- `tracker.js` (incluido en todas las paginas) muestra el aviso de cookies; si se acepta, guarda las cookies
  `visitante_id` y `analitica` y manda vistas, clics y tiempo a `eventos.php`. Al iniciar sesion, lo que ese
  navegador hizo como anonimo se asocia al usuario.
- `dashboard.php` + `panel.js` consultan `metricas.php` cada 5 s (requiere sesion).
- Las metricas registran con que opcion del menu se interactua (`Menú: ...`, `Menú ventas: ...`) y cada seccion de `index.php` cuenta como su propia pagina.
- No se registra lo que se escribe en los formularios.

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

2. Ejecuta `php -S localhost:8000` dentro de esta carpeta y abre `http://localhost:8000/`.

## Publicar en Render

1. Sube el proyecto a GitHub (con `index.php` y `Dockerfile` en la raiz).
2. En Render crea un **Web Service** con runtime **Docker** e instancia **Free**.
3. Agrega las cinco variables de entorno de la tabla anterior.
4. Cada `git push` vuelve a publicar el sitio automaticamente.

No subas contrasenas reales a un repositorio.
