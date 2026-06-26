-- =============================================================
-- GTD App — Migración 004: columna estado en personas
-- La columna rol ya existe en el schema inicial.
-- =============================================================

ALTER TABLE personas
    ADD COLUMN estado ENUM('activo','archivado') NOT NULL DEFAULT 'activo'
        AFTER rol;
