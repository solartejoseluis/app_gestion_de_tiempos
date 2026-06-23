<?php
declare(strict_types=1);

class InboxController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $model = new ItemModel();
        $items = $model->getInbox((int) $_SESSION['usuario_id']);
        $this->layout('inbox.index', [
            'pageTitle'    => 'Inbox',
            'currentRoute' => '/inbox',
            'items'        => $items,
        ]);
    }

    public function store(): void
    {
        $this->requireAuth();
        $titulo = trim($this->input('texto', ''));
        if ($titulo === '') {
            $this->error('El texto no puede estar vacío.');
        }
        if (mb_strlen($titulo) > 255) {
            $this->error('El texto no puede superar 255 caracteres.');
        }
        $model = new ItemModel();
        $id    = $model->crear((int) $_SESSION['usuario_id'], $titulo);
        $item  = $model->findById($id);
        $this->json($item);
    }

    public function lista(): void
    {
        $this->requireAuth();
        $model = new ItemModel();
        $items = $model->getInbox((int) $_SESSION['usuario_id']);
        $this->json($items);
    }

    public function destroy(): void
    {
        $this->requireAuth();
        $id = (int) $this->input('id', 0);
        if ($id <= 0) {
            $this->error('ID inválido.');
        }
        $model = new ItemModel();
        if (!$model->eliminar($id, (int) $_SESSION['usuario_id'])) {
            $this->error('Ítem no encontrado.', 404);
        }
        $this->json(null);
    }
}
