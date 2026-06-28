CREATE TABLE bloques_tiempo (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,
    nombre       VARCHAR(100) NOT NULL,
    color        VARCHAR(7) NOT NULL DEFAULT '#f0c040',
    dias_semana  VARCHAR(13) NOT NULL,
    hora_inicio  TIME NOT NULL,
    hora_fin     TIME NOT NULL,
    fecha_inicio DATE NULL,
    fecha_fin    DATE NULL,
    estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at   TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
