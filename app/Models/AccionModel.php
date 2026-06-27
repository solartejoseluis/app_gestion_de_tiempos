<?php
declare(strict_types=1);

class AccionModel extends Model
{
    protected string $table = 'items';

    public function getAcciones(int $usuarioId, array $filtros = []): array
    {
        $where  = [
            'i.usuario_id = ?',
            "i.tipo IN ('accion', 'proyecto_accion')",
            'i.deleted_at IS NULL',
        ];
        $params = [$usuarioId];

        if (!empty($filtros['contexto_id'])) {
            $where[]  = 'i.contexto_id = ?';
            $params[] = (int) $filtros['contexto_id'];
        }
        if (!empty($filtros['area_id'])) {
            $where[]  = 'i.area_id = ?';
            $params[] = (int) $filtros['area_id'];
        }
        if (!empty($filtros['proyecto_id'])) {
            $where[]  = 'i.proyecto_id = ?';
            $params[] = (int) $filtros['proyecto_id'];
        }

        $sql = "
            SELECT
                i.id, i.titulo, i.notas, i.tipo,
                i.area_id, i.contexto_id, i.proyecto_id,
                i.fecha_accion, i.fecha_cita, i.tipo_tiempo, i.created_at,
                a.nombre AS area_nombre,
                a.color  AS area_color,
                c.nombre AS contexto_nombre,
                c.color  AS contexto_color,
                p.nombre AS proyecto_nombre
            FROM items i
            LEFT JOIN areas     a ON a.id = i.area_id     AND a.deleted_at IS NULL
            LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
            LEFT JOIN proyectos p ON p.id = i.proyecto_id AND p.deleted_at IS NULL
            WHERE " . implode(' AND ', $where) . "
            ORDER BY
                i.fecha_accion IS NULL ASC,
                i.fecha_accion ASC,
                i.created_at   ASC
        ";

        return $this->query($sql, $params)->fetchAll();
    }
}
