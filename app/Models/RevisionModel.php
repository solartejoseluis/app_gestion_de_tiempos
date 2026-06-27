<?php
declare(strict_types=1);

class RevisionModel extends Model
{
    protected string $table = 'revisiones_semanales';

    public function crear(array $data): int
    {
        return $this->insert($data);
    }

    public function actualizar(int $id, array $data): bool
    {
        return $this->update($id, $data);
    }

    public function getEstado(int $userId): array
    {
        $db = $this->db;

        $stmt = $db->prepare(
            'SELECT DATEDIFF(NOW(), fecha_fin) AS dias
               FROM revisiones_semanales
              WHERE usuario_id = ? AND completada = 1
              ORDER BY fecha_fin DESC LIMIT 1'
        );
        $stmt->execute([$userId]);
        $row             = $stmt->fetch();
        $diasDesdeUltima = $row ? (int) $row['dias'] : 999;

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM items
              WHERE usuario_id = ? AND tipo = 'inbox' AND deleted_at IS NULL"
        );
        $stmt->execute([$userId]);
        $itemsInbox = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM proyectos p
              WHERE p.usuario_id = ? AND p.estado = 'activo' AND p.deleted_at IS NULL
                AND NOT EXISTS (
                    SELECT 1 FROM items i
                     WHERE i.proyecto_id = p.id
                       AND i.tipo IN ('accion','proyecto_accion')
                       AND i.deleted_at IS NULL
                )"
        );
        $stmt->execute([$userId]);
        $proyectosSinAccion = (int) $stmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM items
              WHERE usuario_id = ? AND tipo = 'delegada'
                AND deleted_at IS NULL AND fecha_accion < CURDATE()"
        );
        $stmt->execute([$userId]);
        $delegacionesVencidas = (int) $stmt->fetchColumn();

        return [
            'dias_desde_ultima'     => $diasDesdeUltima,
            'items_inbox'           => $itemsInbox,
            'proyectos_sin_accion'  => $proyectosSinAccion,
            'delegaciones_vencidas' => $delegacionesVencidas,
        ];
    }

    public function iniciar(int $userId): int
    {
        $activa = $this->getActiva($userId);
        if ($activa) {
            $this->query(
                "UPDATE {$this->table} SET fecha_inicio = NOW() WHERE id = ?",
                [(int) $activa['id']]
            );
            return (int) $activa['id'];
        }
        return $this->insert(['usuario_id' => $userId, 'paso_actual' => 1]);
    }

    public function getActiva(int $userId): ?array
    {
        $row = $this->query(
            "SELECT * FROM {$this->table}
              WHERE usuario_id = ? AND completada = 0
              ORDER BY id DESC LIMIT 1",
            [$userId]
        )->fetch();
        return $row ?: null;
    }

    public function avanzarPaso(int $id, int $paso, array $contadores): bool
    {
        return $this->update($id, array_merge(['paso_actual' => $paso], $contadores));
    }

    public function completar(int $id, string $focoSemana): bool
    {
        return $this->update($id, [
            'completada'  => 1,
            'fecha_fin'   => date('Y-m-d H:i:s'),
            'foco_semana' => $focoSemana,
        ]);
    }

    public function getHistorial(int $userId, int $limit = 20): array
    {
        $sql = 'SELECT *,
                       TIMESTAMPDIFF(MINUTE, fecha_inicio, fecha_fin) AS duracion_minutos
                  FROM ' . $this->table . '
                 WHERE usuario_id = ? AND completada = 1
                 ORDER BY fecha_inicio DESC
                 LIMIT ' . $limit;
        return $this->query($sql, [$userId])->fetchAll();
    }
}
