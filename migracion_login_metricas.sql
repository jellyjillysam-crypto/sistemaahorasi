-- ============================================================
-- LOGIN + METRICAS en la MISMA base de datos (railway)
-- Ejecutalo UNA sola vez en DBeaver, conectado a la base donde ya estan
-- usuarios, productos, ventas y respuestas.
-- ============================================================

-- 1) La tabla `usuarios` del sistema de ventas ahora tambien guarda las cuentas con acceso.
--    Los usuarios que ya existen se quedan igual (columnas nuevas en NULL = clientes sin cuenta).
ALTER TABLE usuarios
    ADD COLUMN password_hash VARCHAR(255) NULL,
    ADD COLUMN sexo    ENUM('Hombre', 'Mujer', 'Otro') NULL,
    ADD COLUMN edad    TINYINT UNSIGNED NULL,
    ADD COLUMN carrera VARCHAR(100) NULL;

-- 2) Tabla de eventos (vistas, clics y tiempo). Un renglon = un evento.
CREATE TABLE IF NOT EXISTS eventos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    visitante_id CHAR(36)     NOT NULL,   -- cookie visitante_id: el navegador
    sesion_id    CHAR(36)     NOT NULL,   -- sessionStorage: la visita (pestana)
    usuario_id   INT          NULL,       -- NULL mientras el visitante es anonimo
    tipo         ENUM('vista', 'clic', 'tiempo') NOT NULL,
    pagina       VARCHAR(200) NOT NULL,
    elemento     VARCHAR(100) NULL,       -- solo en clics
    segundos     SMALLINT UNSIGNED NULL,  -- solo en tiempo
    creado_en    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id_usuario) ON DELETE SET NULL,
    INDEX (visitante_id),
    INDEX (sesion_id)
);
