-- =============================================================
-- GTD App — Migración 002: columna estado en areas
-- Permite archivar áreas sin eliminarlas (soft-archive).
-- =============================================================

ALTER TABLE areas
    ADD COLUMN estado ENUM('activo','archivado') NOT NULL DEFAULT 'activo'
    AFTER color;
