<?php
declare(strict_types=1);

class ReferenciaModel extends Model
{
    protected string $table = 'items';

    public function getReferencia(int $usuarioId): array
    {
        $sql = "
            SELECT
                i.id, i.titulo, i.etiquetas, i.area_id, i.proyecto_id,
                i.created_at,
                a.nombre AS area_nombre,
                p.nombre AS proyecto_nombre
            FROM items i
            LEFT JOIN areas     a ON a.id = i.area_id     AND a.deleted_at IS NULL
            LEFT JOIN proyectos p ON p.id = i.proyecto_id AND p.deleted_at IS NULL
            WHERE i.usuario_id = ?
              AND i.tipo = 'referencia'
              AND i.deleted_at IS NULL
            ORDER BY i.created_at DESC
        ";

        return $this->query($sql, [$usuarioId])->fetchAll();
    }
}
