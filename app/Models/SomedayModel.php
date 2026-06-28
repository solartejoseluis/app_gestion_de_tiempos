<?php
declare(strict_types=1);

class SomedayModel extends Model
{
    protected string $table = 'items';

    public function getSomeday(int $usuarioId): array
    {
        $sql = "
            SELECT
                i.id, i.titulo, i.notas, i.area_id,
                i.fecha_revision, i.hora_inicio, i.hora_fin, i.created_at,
                a.nombre AS area_nombre,
                a.color  AS area_color
            FROM items i
            LEFT JOIN areas a ON a.id = i.area_id AND a.deleted_at IS NULL
            WHERE i.usuario_id = ?
              AND i.tipo = 'incubada'
              AND i.deleted_at IS NULL
            ORDER BY
                CASE
                    WHEN i.fecha_revision <= CURDATE() THEN 0
                    WHEN i.fecha_revision IS NULL      THEN 1
                    ELSE 2
                END ASC,
                i.fecha_revision ASC,
                i.created_at     ASC
        ";

        return $this->query($sql, [$usuarioId])->fetchAll();
    }
}
