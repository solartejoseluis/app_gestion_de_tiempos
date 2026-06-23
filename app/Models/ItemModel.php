<?php
declare(strict_types=1);

class ItemModel extends Model
{
    protected string $table = 'items';

    public function getInbox(int $usuarioId): array
    {
        return $this->findAll(
            'usuario_id = ? AND tipo = ? AND deleted_at IS NULL ORDER BY created_at DESC',
            [$usuarioId, 'inbox']
        );
    }

    public function findById(int $id): ?array
    {
        return $this->findOne('id = ?', [$id]);
    }

    public function crear(int $usuarioId, string $titulo): int
    {
        return $this->insert([
            'usuario_id' => $usuarioId,
            'titulo'     => $titulo,
            'tipo'       => 'inbox',
        ]);
    }

    public function eliminar(int $id, int $usuarioId): bool
    {
        $item = $this->findOne('id = ? AND usuario_id = ? AND deleted_at IS NULL', [$id, $usuarioId]);
        if ($item === null) {
            return false;
        }
        return $this->softDelete($id);
    }
}
