<?php
declare(strict_types=1);

class InboxController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $this->layout('inbox.index', [
            'pageTitle'    => 'Inbox',
            'currentRoute' => '/inbox',
        ]);
    }
}
