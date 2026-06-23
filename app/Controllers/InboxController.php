<?php
declare(strict_types=1);

class InboxController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->view('inbox.index', [
            'nombre' => $_SESSION['usuario_nombre'],
        ]);
    }
}
