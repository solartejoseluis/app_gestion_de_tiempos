<?php
declare(strict_types=1);

class ProyectoModel extends Model
{
    protected string $table = 'proyectos';

    public function getProyectosConStats(int $usuarioId): array
    {
        $sql = "
            SELECT
                p.id, p.nombre, p.descripcion, p.resultado_deseado,
                p.estado, p.area_id, p.fecha_limite, p.created_at,
                p.updated_at AS fecha_completada,
                a.nombre AS area_nombre,
                a.color  AS area_color,
                SUM(CASE WHEN i.id IS NOT NULL AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                          AS total_items,
                SUM(CASE WHEN i.id IS NOT NULL AND i.tipo = 'completada'
                              AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                          AS items_completados,
                SUM(CASE WHEN i.id IS NOT NULL
                              AND i.tipo IN ('accion','proyecto_accion')
                              AND i.deleted_at IS NULL
                         THEN 1 ELSE 0 END)                                          AS proximas_acciones
            FROM proyectos p
            LEFT JOIN areas a ON a.id = p.area_id AND a.deleted_at IS NULL
            LEFT JOIN items i ON i.proyecto_id = p.id
            WHERE p.usuario_id = ?
              AND p.deleted_at IS NULL
              AND p.estado IN ('activo','pausa','completado')
            GROUP BY
                p.id, p.nombre, p.descripcion, p.resultado_deseado,
                p.estado, p.area_id, p.fecha_limite, p.created_at,
                a.nombre, a.color
            ORDER BY
                area_nombre ASC,
                p.nombre    ASC
        ";
        return $this->query($sql, [$usuarioId])->fetchAll();
    }

    public function actualizarEstado(int $id, int $usuarioId, array $campos): bool
    {
        $item = $this->findOne('id = ? AND usuario_id = ? AND deleted_at IS NULL', [$id, $usuarioId]);
        if ($item === null) {
            return false;
        }
        $this->update($id, $campos);
        return true;
    }
}
