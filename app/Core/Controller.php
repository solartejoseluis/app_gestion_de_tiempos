<?php
declare(strict_types=1);

class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($viewPath)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }
        require $viewPath;
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['ok' => $status < 400, 'data' => $data]);
        exit;
    }

    protected function error(string $message, int $status = 400): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => $message]);
        exit;
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . $_ENV['APP_URL'] . $path);
        exit;
    }

    protected function requireAuth(): void
    {
        if (empty($_SESSION['usuario_id'])) {
            $this->redirect('/login');
        }
    }

    protected function layout(string $view, array $data = []): void
    {
        extract($data);
        $contentView = BASE_PATH . '/app/Views/' . str_replace('.', '/', $view) . '.php';
        if (!file_exists($contentView)) {
            throw new RuntimeException("Vista no encontrada: {$view}");
        }
        $counters    = SidebarCounters::get((int) $_SESSION['usuario_id']);
        $layoutPath  = BASE_PATH . '/app/Views/layouts/dashboard.php';
        require $layoutPath;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
}
