<?php
declare(strict_types=1);

class ContextoModel extends Model
{
    protected string $table = 'contextos';

    public function findAllWithStats(int $userId): array
    {
        $sql = "
            SELECT
                c.id, c.nombre, c.descripcion, c.color, c.estado, c.created_at,
                COUNT(DISTINCT i.id) AS acciones_activas
            FROM contextos c
            LEFT JOIN items i
                   ON i.contexto_id = c.id
                  AND i.deleted_at  IS NULL
                  AND i.tipo IN ('accion', 'proyecto_accion', 'delegada')
            WHERE c.usuario_id = ?
              AND c.deleted_at IS NULL
            GROUP BY c.id, c.nombre, c.descripcion, c.color, c.estado, c.created_at
            ORDER BY
                CASE WHEN c.estado = 'activo' THEN 0 ELSE 1 END ASC,
                c.nombre ASC
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

    public function tieneItemsActivos(int $id): int
    {
        $sql = "
            SELECT COUNT(*) FROM items
             WHERE contexto_id = ?
               AND tipo IN ('accion', 'proyecto_accion', 'delegada')
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

    public function cargarSugeridos(int $userId): int
    {
        $sugeridos = [
            'llamar', 'computador', 'email', 'casa', 'comisiones',
            'agenda', 'reunion', 'lectura', 'energia-alta', 'energia-baja',
        ];

        $insertados = 0;
        foreach ($sugeridos as $nombre) {
            if (!$this->existeNombre($nombre, $userId)) {
                $this->crear([
                    'usuario_id'  => $userId,
                    'nombre'      => $nombre,
                    'descripcion' => '',
                    'color'       => '#6c757d',
                    'estado'      => 'activo',
                ]);
                $insertados++;
            }
        }
        return $insertados;
    }
}
