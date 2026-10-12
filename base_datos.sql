-- Tabla de la encuesta.
-- Ejecutala en DBeaver conectado a la base "railway" (la misma donde estan usuarios, productos y ventas).
CREATE TABLE IF NOT EXISTS respuestas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  profesores  VARCHAR(30)  NOT NULL,
  instalacion VARCHAR(30)  NOT NULL,
  wifi        VARCHAR(40)  NOT NULL,
  biblioteca  VARCHAR(20)  NOT NULL,
  sugerencia  VARCHAR(300) NOT NULL,
  fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
