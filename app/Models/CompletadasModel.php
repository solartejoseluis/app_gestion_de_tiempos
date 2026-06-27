<?php
declare(strict_types=1);

class CompletadasModel extends Model
{
    protected string $table = 'items';

    public function getItems(int $userId, array $filtros = []): array
    {
        $where  = ['i.usuario_id = ?', "i.tipo = 'completada'", 'i.deleted_at IS NULL'];
        $params = [$userId];

        if (!empty($filtros['area_id'])) {
            $where[]  = 'i.area_id = ?';
            $params[] = (int) $filtros['area_id'];
        }
        if (!empty($filtros['proyecto_id'])) {
            $where[]  = 'i.proyecto_id = ?';
            $params[] = (int) $filtros['proyecto_id'];
        }
        if (!empty($filtros['contexto_id'])) {
            $where[]  = 'i.contexto_id = ?';
            $params[] = (int) $filtros['contexto_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'DATE(COALESCE(i.fecha_completada, i.updated_at)) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'DATE(COALESCE(i.fecha_completada, i.updated_at)) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "
            SELECT
                i.id, i.titulo, i.fecha_completada, i.updated_at,
                a.nombre  AS area_nombre,  a.color  AS area_color,
                p.nombre  AS proyecto_nombre,
                c.nombre  AS contexto_nombre,
                pe.nombre AS persona_nombre
            FROM items i
            LEFT JOIN areas     a  ON a.id  = i.area_id     AND a.deleted_at  IS NULL
            LEFT JOIN proyectos p  ON p.id  = i.proyecto_id AND p.deleted_at  IS NULL
            LEFT JOIN contextos c  ON c.id  = i.contexto_id AND c.deleted_at  IS NULL
            LEFT JOIN personas  pe ON pe.id = i.persona_id  AND pe.deleted_at IS NULL
            WHERE " . implode(' AND ', $where) . "
            ORDER BY COALESCE(i.fecha_completada, i.updated_at) DESC
            LIMIT 200
        ";

        return $this->query($sql, $params)->fetchAll();
    }

    public function getProyectos(int $userId, array $filtros = []): array
    {
        $where  = ['p.usuario_id = ?', "p.estado = 'completado'", 'p.deleted_at IS NULL'];
        $params = [$userId];

        if (!empty($filtros['area_id'])) {
            $where[]  = 'p.area_id = ?';
            $params[] = (int) $filtros['area_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'DATE(p.updated_at) >= ?';
            $params[] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'DATE(p.updated_at) <= ?';
            $params[] = $filtros['fecha_hasta'];
        }

        $sql = "
            SELECT
                p.id, p.nombre, p.resultado_deseado, p.updated_at, p.created_at,
                a.nombre AS area_nombre, a.color AS area_color,
                COUNT(i.id) AS total_acciones
            FROM proyectos p
            LEFT JOIN areas a ON a.id = p.area_id AND a.deleted_at IS NULL
            LEFT JOIN items i ON i.proyecto_id = p.id AND i.deleted_at IS NULL
            WHERE " . implode(' AND ', $where) . "
            GROUP BY p.id, p.nombre, p.resultado_deseado, p.updated_at,
                     p.created_at, a.nombre, a.color
            ORDER BY p.updated_at DESC
            LIMIT 100
        ";

        return $this->query($sql, $params)->fetchAll();
    }

    public function getSelectores(int $userId): array
    {
        $db = Database::connection();

        $stmtAreas = $db->prepare(
            'SELECT id, nombre, color FROM areas
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmtAreas->execute([$userId]);

        $stmtProyectos = $db->prepare(
            "SELECT id, nombre FROM proyectos
             WHERE usuario_id = ? AND estado = 'completado' AND deleted_at IS NULL
             ORDER BY nombre"
        );
        $stmtProyectos->execute([$userId]);

        $stmtContextos = $db->prepare(
            'SELECT id, nombre, color FROM contextos
             WHERE usuario_id = ? AND deleted_at IS NULL ORDER BY nombre'
        );
        $stmtContextos->execute([$userId]);

        return [
            'areas'     => $stmtAreas->fetchAll(),
            'proyectos' => $stmtProyectos->fetchAll(),
            'contextos' => $stmtContextos->fetchAll(),
        ];
    }
}
