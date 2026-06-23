<?php
declare(strict_types=1);

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            $this->redirect('/inbox');
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);

        $this->view('auth.login', ['error' => $error]);
    }

    public function login(): void
    {
        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Credenciales incorrectas.';
            $this->redirect('/login');
        }

        $model   = new UserModel();
        $usuario = $model->findByEmail($email);

        if ($usuario === null || !$model->verifyPassword($password, $usuario['password_hash'])) {
            $_SESSION['flash_error'] = 'Credenciales incorrectas.';
            $this->redirect('/login');
        }

        session_regenerate_id(true);
        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];

        $this->redirect('/inbox');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }
}
