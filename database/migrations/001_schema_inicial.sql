-- =============================================================
-- GTD App — Migración 001: Esquema inicial
-- MariaDB 11.4 | InnoDB | utf8mb4_unicode_ci
-- =============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- -------------------------------------------------------------
-- usuarios
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(100)  NOT NULL,
    email         VARCHAR(150)  NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- areas
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS areas (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED  NOT NULL,
    nombre      VARCHAR(100)  NOT NULL,
    descripcion TEXT,
    color       VARCHAR(7)    NOT NULL DEFAULT '#534AB7',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_areas_usuario (usuario_id),
    CONSTRAINT fk_areas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- contextos  (@casa, @trabajo, @telefono, etc.)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS contextos (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED  NOT NULL,
    nombre      VARCHAR(50)   NOT NULL,
    color       VARCHAR(7)    NOT NULL DEFAULT '#534AB7',
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_contextos_usuario (usuario_id),
    CONSTRAINT fk_contextos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- personas  (destinatarios de acciones delegadas)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS personas (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED  NOT NULL,
    nombre      VARCHAR(100)  NOT NULL,
    email       VARCHAR(150)  NULL DEFAULT NULL,
    rol         VARCHAR(100)  NULL DEFAULT NULL,
    created_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_personas_usuario (usuario_id),
    CONSTRAINT fk_personas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- proyectos
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS proyectos (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id   INT UNSIGNED  NOT NULL,
    area_id      INT UNSIGNED  NULL DEFAULT NULL,
    nombre             VARCHAR(200)  NOT NULL,
    descripcion        TEXT,
    resultado_deseado  TEXT,
    estado             ENUM('activo','pausa','completado') NOT NULL DEFAULT 'activo',
    fecha_limite DATE          NULL DEFAULT NULL,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at   TIMESTAMP     NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_proyectos_usuario       (usuario_id),
    KEY idx_proyectos_usuario_estado (usuario_id, estado),
    CONSTRAINT fk_proyectos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_proyectos_area    FOREIGN KEY (area_id)    REFERENCES areas    (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- items  (núcleo GTD — todos los tipos en una sola tabla)
--
-- Tipos de ítem: inbox | accion | proyecto_accion | delegada |
--                incubada | referencia | completada | eliminada
--
-- Reglas de negocio relevantes para los índices:
--   · Cada módulo filtra por (usuario_id, tipo, deleted_at IS NULL)
--   · El calendario filtra por tipo_tiempo = 'cita'
--   · contexto_id es obligatorio para accion / proyecto_accion / delegada
--   · proyecto_id es obligatorio para proyecto_accion
--   · persona_id  es obligatorio para delegada
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS items (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED  NOT NULL,
    titulo           VARCHAR(255)  NOT NULL,
    notas            TEXT,

    tipo             ENUM(
                         'inbox',
                         'accion',
                         'proyecto_accion',
                         'delegada',
                         'incubada',
                         'referencia',
                         'completada',
                         'eliminada'
                     ) NOT NULL DEFAULT 'inbox',

    -- Solo 'cita' aparece en el calendario (día y hora fijos)
    tipo_tiempo      ENUM('ninguno','dia','cita') NULL DEFAULT NULL,

    -- Relaciones contextuales (opcionales según tipo)
    area_id          INT UNSIGNED  NULL DEFAULT NULL,
    contexto_id      INT UNSIGNED  NULL DEFAULT NULL,
    proyecto_id      INT UNSIGNED  NULL DEFAULT NULL,
    persona_id       INT UNSIGNED  NULL DEFAULT NULL,

    -- Fechas
    fecha_accion     DATE          NULL DEFAULT NULL,   -- cuándo ejecutar
    fecha_cita       DATETIME      NULL DEFAULT NULL,   -- hora fija (tipo_tiempo='cita')
    fecha_limite     DATE          NULL DEFAULT NULL,
    fecha_delegacion DATE          NULL DEFAULT NULL,
    fecha_revision   DATE          NULL DEFAULT NULL,   -- para tipo='incubada'
    fecha_completada DATETIME      NULL DEFAULT NULL,

    -- Solo para tipo='referencia'
    etiquetas        VARCHAR(300)  NULL DEFAULT NULL,

    -- Atributos de ejecución (GTD)
    duracion_minutos SMALLINT UNSIGNED NULL DEFAULT NULL,
    energia          ENUM('baja','media','alta') NULL DEFAULT NULL,

    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at       TIMESTAMP     NULL DEFAULT NULL,

    PRIMARY KEY (id),

    -- Módulos filtran por usuario + tipo + no eliminado
    KEY idx_items_usuario_tipo    (usuario_id, tipo),
    -- Exclusión de soft-deleted en listados generales
    KEY idx_items_usuario_deleted (usuario_id, deleted_at),
    -- Relaciones frecuentes en vistas de detalle
    KEY idx_items_proyecto        (proyecto_id),
    KEY idx_items_contexto        (contexto_id),
    KEY idx_items_persona         (persona_id),
    -- Calendario y planificación
    KEY idx_items_fecha_cita      (usuario_id, fecha_cita),
    KEY idx_items_fecha_accion    (usuario_id, fecha_accion),

    CONSTRAINT fk_items_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios  (id),
    CONSTRAINT fk_items_area     FOREIGN KEY (area_id)     REFERENCES areas     (id),
    CONSTRAINT fk_items_contexto FOREIGN KEY (contexto_id) REFERENCES contextos (id),
    CONSTRAINT fk_items_proyecto FOREIGN KEY (proyecto_id) REFERENCES proyectos (id),
    CONSTRAINT fk_items_persona  FOREIGN KEY (persona_id)  REFERENCES personas  (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- revisiones_semanales
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS revisiones_semanales (
    id               INT UNSIGNED     NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED     NOT NULL,
    estado           ENUM('en_progreso','completada') NOT NULL DEFAULT 'en_progreso',
    paso_actual      TINYINT UNSIGNED NOT NULL DEFAULT 1,
    notas            TEXT,
    fecha_inicio     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_completada TIMESTAMP        NULL DEFAULT NULL,
    created_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_revisiones_usuario        (usuario_id),
    KEY idx_revisiones_usuario_estado (usuario_id, estado),
    CONSTRAINT fk_revisiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;
