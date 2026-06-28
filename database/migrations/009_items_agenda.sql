ALTER TABLE items
    ADD COLUMN hora_inicio TIME NULL AFTER fecha_accion,
    ADD COLUMN hora_fin    TIME NULL AFTER hora_inicio,
    ADD COLUMN bloque_id   INT UNSIGNED NULL AFTER hora_fin;
