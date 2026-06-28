<?php
declare(strict_types=1);

class AgendaController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->layout('agenda.index', [
            'pageTitle'    => 'Agenda',
            'currentRoute' => '/agenda',
        ]);
    }
}
