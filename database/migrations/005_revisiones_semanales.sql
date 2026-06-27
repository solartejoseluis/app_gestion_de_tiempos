-- =============================================================
-- GTD App — Migración 005: revisiones semanales
-- Recrea la tabla con el schema completo para el módulo 11.
-- =============================================================

DROP TABLE IF EXISTS revisiones_semanales;

CREATE TABLE revisiones_semanales (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id            INT UNSIGNED NOT NULL,
    fecha_inicio          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_fin             DATETIME NULL,
    paso_actual           TINYINT UNSIGNED NOT NULL DEFAULT 1,
    completada            TINYINT(1) NOT NULL DEFAULT 0,
    items_procesados      INT UNSIGNED NOT NULL DEFAULT 0,
    proyectos_revisados   INT UNSIGNED NOT NULL DEFAULT 0,
    delegaciones_cerradas INT UNSIGNED NOT NULL DEFAULT 0,
    incubadas_activadas   INT UNSIGNED NOT NULL DEFAULT 0,
    foco_semana           TEXT NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
