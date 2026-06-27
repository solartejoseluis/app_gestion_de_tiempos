<?php
declare(strict_types=1);

class EsperaModel extends Model
{
    protected string $table = 'items';

    public function getEspera(int $usuarioId): array
    {
        $sql = "
            SELECT
                i.id, i.titulo, i.notas, i.area_id, i.contexto_id, i.persona_id,
                i.fecha_accion, i.fecha_cita, i.tipo_tiempo, i.fecha_delegacion, i.created_at,
                p.nombre AS persona_nombre,
                c.nombre AS contexto_nombre,
                c.color  AS contexto_color,
                a.nombre AS area_nombre
            FROM items i
            LEFT JOIN personas  p ON p.id = i.persona_id  AND p.deleted_at IS NULL
            LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
            LEFT JOIN areas     a ON a.id = i.area_id     AND a.deleted_at IS NULL
            WHERE i.usuario_id = ?
              AND i.tipo = 'delegada'
              AND i.deleted_at IS NULL
            ORDER BY
                (i.fecha_accion < CURDATE()) DESC,
                i.fecha_accion ASC,
                i.created_at   ASC
        ";

        return $this->query($sql, [$usuarioId])->fetchAll();
    }
}
