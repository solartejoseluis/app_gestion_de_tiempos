<?php
declare(strict_types=1);

class AreaModel extends Model
{
    protected string $table = 'areas';

    public function findAllWithStats(int $userId): array
    {
        $sql = "
            SELECT
                a.id, a.nombre, a.color, a.estado, a.created_at,
                COUNT(DISTINCT p.id) AS proyectos_activos,
                COUNT(DISTINCT i.id) AS acciones_pendientes
            FROM areas a
            LEFT JOIN proyectos p
                   ON p.area_id    = a.id
                  AND p.deleted_at IS NULL
                  AND p.estado     = 'activo'
            LEFT JOIN items i
                   ON i.area_id    = a.id
                  AND i.deleted_at IS NULL
                  AND i.tipo IN ('accion', 'proyecto_accion')
            WHERE a.usuario_id  = ?
              AND a.deleted_at IS NULL
            GROUP BY a.id, a.nombre, a.color, a.estado, a.created_at
            ORDER BY
                CASE WHEN a.estado = 'activo' THEN 0 ELSE 1 END ASC,
                a.nombre ASC
        ";

        return $this->query($sql, [$userId])->fetchAll();
    }

    public function archivar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET estado = 'archivado' WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function tieneItemsActivos(int $id): array
    {
        $sql = "
            SELECT
                (SELECT COUNT(*) FROM proyectos
                  WHERE area_id    = ?
                    AND estado     = 'activo'
                    AND deleted_at IS NULL) AS proyectos,
                (SELECT COUNT(*) FROM items
                  WHERE area_id    = ?
                    AND tipo IN ('accion','proyecto_accion','delegada','incubada','referencia')
                    AND deleted_at IS NULL) AS items
        ";
        $row = $this->query($sql, [$id, $id])->fetch();

        return [
            'proyectos' => (int) ($row['proyectos'] ?? 0),
            'items'     => (int) ($row['items']     ?? 0),
        ];
    }

    public function restaurar(int $id): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET estado = 'activo' WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount() > 0;
    }

    public function crear(array $data): int
    {
        return $this->insert($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function eliminar(int $id): bool
    {
        return $this->softDelete($id);
    }
}
