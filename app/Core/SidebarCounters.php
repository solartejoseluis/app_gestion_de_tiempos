<?php
declare(strict_types=1);

class SidebarCounters
{
    public static function get(int $usuarioId): array
    {
        $db   = Database::connection();
        $stmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'inbox'     AND deleted_at IS NULL) AS inbox,
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'accion'    AND deleted_at IS NULL) AS acciones,
                (SELECT COUNT(*) FROM proyectos   WHERE usuario_id = ? AND estado = 'activo'  AND deleted_at IS NULL) AS proyectos,
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'delegada'  AND deleted_at IS NULL) AS espera,
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'delegada'
                                                                        AND fecha_limite IS NOT NULL
                                                                        AND fecha_limite < CURDATE()
                                                                        AND deleted_at IS NULL)                       AS espera_vencidas,
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'incubada'  AND deleted_at IS NULL) AS someday,
                (SELECT COUNT(*) FROM items       WHERE usuario_id = ? AND tipo = 'referencia' AND deleted_at IS NULL) AS referencia,
                (SELECT MAX(fecha_fin) FROM revisiones_semanales WHERE usuario_id = ? AND completada = 1)            AS ultima_revision
        ");
        $stmt->execute(array_fill(0, 8, $usuarioId));
        $row = $stmt->fetch();

        $ultimaRevision  = $row['ultima_revision'];
        $revisionReciente = $ultimaRevision !== null
            && strtotime($ultimaRevision) >= strtotime('-7 days');

        return [
            'inbox'           => (int) $row['inbox'],
            'acciones'        => (int) $row['acciones'],
            'proyectos'       => (int) $row['proyectos'],
            'espera'          => (int) $row['espera'],
            'espera_vencidas' => (int) $row['espera_vencidas'],
            'someday'         => (int) $row['someday'],
            'referencia'      => (int) $row['referencia'],
            'revision_ok'      => $revisionReciente,
            'revision_vencida' => !$revisionReciente,
        ];
    }
}
