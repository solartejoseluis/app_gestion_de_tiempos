<?php
declare(strict_types=1);

class BloqueModel extends Model
{
    protected string $table = 'bloques_tiempo';

    public function getAll(int $userId): array
    {
        $sql = "
            SELECT id, nombre, color, dias_semana, hora_inicio, hora_fin,
                   fecha_inicio, fecha_fin, estado, created_at
            FROM bloques_tiempo
            WHERE usuario_id = ?
              AND deleted_at IS NULL
            ORDER BY
                CASE WHEN estado = 'activo' THEN 0 ELSE 1 END ASC,
                hora_inicio ASC
        ";
        return $this->query($sql, [$userId])->fetchAll();
    }

    public function getBloquesSemana(int $userId, string $lunFecha, string $domFecha): array
    {
        $sql = "
            SELECT id, nombre, color, dias_semana, hora_inicio, hora_fin,
                   fecha_inicio, fecha_fin, estado
            FROM bloques_tiempo
            WHERE usuario_id  = ?
              AND estado      = 'activo'
              AND deleted_at  IS NULL
              AND (fecha_inicio IS NULL OR fecha_inicio <= ?)
              AND (fecha_fin   IS NULL OR fecha_fin   >= ?)
        ";
        return $this->query($sql, [$userId, $domFecha, $lunFecha])->fetchAll();
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
            "UPDATE {$this->table} SET estado = 'inactivo' WHERE id = ?"
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

    public function existeNombre(string $nombre, int $userId, ?int $excludeId = null): bool
    {
        $sql    = 'SELECT id FROM bloques_tiempo WHERE nombre = ? AND usuario_id = ? AND deleted_at IS NULL';
        $params = [$nombre, $userId];
        if ($excludeId !== null) {
            $sql    .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return (bool) $this->query($sql, $params)->fetch();
    }
}
