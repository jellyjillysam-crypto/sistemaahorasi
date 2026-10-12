<?php
// Configuracion de la base de datos.
// No lleva contrasenas: los valores vienen de las variables de entorno
// que se configuran en el panel de Render (DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME).
return [
    'host'        => getenv('DB_HOST') ?: 'localhost',
    'port'        => getenv('DB_PORT') ?: '3306',
    'database'    => getenv('DB_NAME') ?: '',
    'username'    => getenv('DB_USER') ?: '',
    'password'    => getenv('DB_PASS') ?: '',
    'charset'     => 'utf8mb4',
    // Opcional: clave de openrouteservice para el mapa (variable ORS_API_KEY)
    'ors_api_key' => getenv('ORS_API_KEY') ?: '',
];
