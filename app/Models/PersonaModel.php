<?php
declare(strict_types=1);

class PersonaModel extends Model
{
    protected string $table = 'personas';

    public function findAllWithStats(int $userId): array
    {
        $sql = "
            SELECT
                p.id, p.nombre, p.rol, p.estado, p.created_at,
                COUNT(DISTINCT i.id) AS tareas_activas,
                COUNT(DISTINCT CASE WHEN i.fecha_accion < CURDATE() THEN i.id END) AS tareas_vencidas
            FROM personas p
            LEFT JOIN items i
                   ON i.persona_id  = p.id
                  AND i.deleted_at  IS NULL
                  AND i.tipo        = 'delegada'
            WHERE p.usuario_id = ?
              AND p.deleted_at  IS NULL
            GROUP BY p.id, p.nombre, p.rol, p.estado, p.created_at
            ORDER BY
                CASE WHEN p.estado = 'activo' THEN 0 ELSE 1 END ASC,
                p.nombre ASC
        ";

        return $this->query($sql, [$userId])->fetchAll();
    }

    public function crear(array $data): int
    {
        return $this->insert($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function archivar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET estado = 'archivado' WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function restaurar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET estado = 'activo' WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function eliminar(int $id): bool
    {
        return $this->softDelete($id);
    }

    public function tieneTareasActivas(int $id): int
    {
        $sql = "
            SELECT COUNT(*) FROM items
             WHERE persona_id = ?
               AND tipo       = 'delegada'
               AND deleted_at IS NULL
        ";
        return (int) $this->query($sql, [$id])->fetchColumn();
    }

    public function existeNombre(string $nombre, int $userId, ?int $excludeId = null): bool
    {
        if ($excludeId === null) {
            $sql    = "SELECT id FROM {$this->table}
                        WHERE usuario_id = ? AND nombre = ? AND deleted_at IS NULL LIMIT 1";
            $params = [$userId, $nombre];
        } else {
            $sql    = "SELECT id FROM {$this->table}
                        WHERE usuario_id = ? AND nombre = ? AND deleted_at IS NULL AND id != ? LIMIT 1";
            $params = [$userId, $nombre, $excludeId];
        }
        return (bool) $this->query($sql, $params)->fetch();
    }
}
