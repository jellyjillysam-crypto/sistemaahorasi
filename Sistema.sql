-- ============================================================
-- SISTEMA DE VENTAS
-- ============================================================

-- Reinicio total: borra la base si ya existe y la crea de nuevo desde cero.


-- ------------------------------------------------------------
-- TABLA: usuarios
-- ------------------------------------------------------------
CREATE TABLE usuarios (
    id_usuario   INT          NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL UNIQUE,
    telefono     VARCHAR(20),
    activo       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_usuarios PRIMARY KEY (id_usuario)
);

-- ------------------------------------------------------------
-- TABLA: productos
-- ------------------------------------------------------------
CREATE TABLE productos (
    id_producto  INT            NOT NULL AUTO_INCREMENT,
    nombre       VARCHAR(150)   NOT NULL,
    descripcion  TEXT,
    precio       DECIMAL(10, 2) NOT NULL,
    stock        INT            NOT NULL DEFAULT 0,
    imagen       VARCHAR(255),  -- nombre del archivo guardado en la carpeta imagenes_productos/
    activo       TINYINT(1)     NOT NULL DEFAULT 1,
    created_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_productos PRIMARY KEY (id_producto),
    CONSTRAINT chk_precio   CHECK (precio >= 0),
    CONSTRAINT chk_stock    CHECK (stock  >= 0)
);

-- ------------------------------------------------------------
-- TABLA: ventas
-- ------------------------------------------------------------
CREATE TABLE ventas (
    id_venta      INT            NOT NULL AUTO_INCREMENT,
    id_usuario    INT            NOT NULL,
    id_producto   INT            NOT NULL,
    cantidad      INT            NOT NULL DEFAULT 1,
    precio_unit   DECIMAL(10, 2) NOT NULL,  -- precio al momento de la venta
    total_bruto   DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    tasa_impuesto DECIMAL(5, 2)  NOT NULL DEFAULT 16.00, -- porcentaje, ej: 16.00 = 16%
    total_impuesto DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total_final   DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT pk_ventas        PRIMARY KEY (id_venta),
    CONSTRAINT fk_venta_usuario FOREIGN KEY (id_usuario)  REFERENCES usuarios  (id_usuario),
    CONSTRAINT fk_venta_producto FOREIGN KEY (id_producto) REFERENCES productos (id_producto),
    CONSTRAINT chk_cantidad     CHECK (cantidad > 0)
);

-- ------------------------------------------------------------
-- TRIGGER: calcular totales ANTES de INSERT en ventas
-- ------------------------------------------------------------
DELIMITER $$

CREATE TRIGGER trg_ventas_before_insert
BEFORE INSERT ON ventas
FOR EACH ROW
BEGIN
    -- Total bruto = cantidad × precio unitario
    SET NEW.total_bruto    = NEW.cantidad * NEW.precio_unit;

    -- Impuesto = total bruto × (tasa / 100)
    SET NEW.total_impuesto = NEW.total_bruto * (NEW.tasa_impuesto / 100);

    -- Total final = total bruto + impuesto
    SET NEW.total_final    = NEW.total_bruto + NEW.total_impuesto;
END$$

-- ------------------------------------------------------------
-- TRIGGER: recalcular totales ANTES de UPDATE en ventas
-- ------------------------------------------------------------
CREATE TRIGGER trg_ventas_before_update
BEFORE UPDATE ON ventas
FOR EACH ROW
BEGIN
    SET NEW.total_bruto    = NEW.cantidad * NEW.precio_unit;
    SET NEW.total_impuesto = NEW.total_bruto * (NEW.tasa_impuesto / 100);
    SET NEW.total_final    = NEW.total_bruto + NEW.total_impuesto;
END$$

DELIMITER ;

-- ============================================================
-- DATOS DE PRUEBA
-- ============================================================

INSERT INTO usuarios (nombre, email, telefono) VALUES
    ('Ana García',   'ana.garcia@correo.com',   '555-1001'),
    ('Luis Pérez',   'luis.perez@correo.com',   '555-1002'),
    ('María López',  'maria.lopez@correo.com',  '555-1003');

INSERT INTO productos (nombre, descripcion, precio, stock) VALUES
    ('Laptop Pro 15',  'Laptop de alto rendimiento 15"', 18500.00, 10),
    ('Mouse Inalámbrico', 'Mouse ergonómico 2.4 GHz',    350.00,   50),
    ('Teclado Mecánico',  'Teclado retroiluminado RGB',  850.00,   30);

-- Los totales se calculan automáticamente por el trigger
INSERT INTO ventas (id_usuario, id_producto, cantidad, precio_unit, tasa_impuesto) VALUES
    (1, 1, 1, 18500.00, 16.00),  -- Ana compra 1 Laptop
    (2, 2, 3,   350.00, 16.00),  -- Luis compra 3 Mouse
    (3, 3, 2,   850.00, 16.00);  -- María compra 2 Teclados

-- ============================================================
-- CONSULTA DE VERIFICACIÓN
-- ============================================================

SELECT
    v.id_venta,
    u.nombre          AS usuario,
    p.nombre          AS producto,
    v.cantidad,
    v.precio_unit,
    v.total_bruto,
    v.tasa_impuesto,
    v.total_impuesto,
    v.total_final,
    v.created_at
FROM ventas   v
JOIN usuarios  u ON u.id_usuario  = v.id_usuario
JOIN productos p ON p.id_producto = v.id_producto
ORDER BY v.id_venta;
