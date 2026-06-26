-- =============================================================
-- GTD App — Migración 003: columnas descripcion y estado en contextos
-- =============================================================

ALTER TABLE contextos
    ADD COLUMN descripcion TEXT NULL DEFAULT NULL
        AFTER nombre,
    ADD COLUMN estado ENUM('activo','archivado') NOT NULL DEFAULT 'activo'
        AFTER color;
