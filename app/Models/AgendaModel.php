<?php
declare(strict_types=1);

class AgendaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getSemana(int $userId, string $lunFecha, string $domFecha): array
    {
        $bloques = (new BloqueModel())->getBloquesSemana($userId, $lunFecha, $domFecha);

        $sqlItems = "
            SELECT i.id, i.titulo, i.tipo, i.tipo_tiempo,
                   i.fecha_accion, i.hora_inicio, i.hora_fin, i.fecha_cita,
                   i.bloque_id, i.area_id, i.contexto_id, i.proyecto_id,
                   i.duracion_minutos,
                   c.nombre AS contexto_nombre, c.color AS contexto_color,
                   p.nombre AS proyecto_nombre,
                   a.nombre AS area_nombre, a.color AS area_color
            FROM items i
            LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
            LEFT JOIN proyectos p ON p.id = i.proyecto_id AND p.deleted_at IS NULL
            LEFT JOIN areas     a ON a.id = i.area_id     AND a.deleted_at IS NULL
            WHERE i.usuario_id = ?
              AND i.deleted_at IS NULL
              AND i.tipo IN ('accion','proyecto_accion','delegada')
              AND i.fecha_accion BETWEEN ? AND ?
            ORDER BY i.fecha_accion ASC, i.hora_inicio ASC
        ";
        $stmt = $this->db->prepare($sqlItems);
        $stmt->execute([$userId, $lunFecha, $domFecha]);
        $items = $stmt->fetchAll();

        $sqlComp = "
            SELECT i.id, i.titulo, i.tipo, i.tipo_tiempo,
                   i.fecha_accion, i.hora_inicio, i.hora_fin, i.fecha_cita,
                   i.bloque_id, i.area_id, i.duracion_minutos,
                   c.nombre AS contexto_nombre, c.color AS contexto_color,
                   p.nombre AS proyecto_nombre,
                   a.nombre AS area_nombre, a.color AS area_color
            FROM items i
            LEFT JOIN contextos c ON c.id = i.contexto_id AND c.deleted_at IS NULL
            LEFT JOIN proyectos p ON p.id = i.proyecto_id AND p.deleted_at IS NULL
            LEFT JOIN areas     a ON a.id = i.area_id     AND a.deleted_at IS NULL
            WHERE i.usuario_id = ?
              AND i.deleted_at IS NULL
              AND i.tipo = 'completada'
              AND i.fecha_completada BETWEEN ? AND ?
            ORDER BY i.fecha_completada ASC
        ";
        $stmt = $this->db->prepare($sqlComp);
        $stmt->execute([$userId, $lunFecha . ' 00:00:00', $domFecha . ' 23:59:59']);
        $completadas = $stmt->fetchAll();

        return [
            'bloques'     => $bloques,
            'items'       => $items,
            'completadas' => $completadas,
        ];
    }
}
