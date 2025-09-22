<?php

class AuthController extends Controller
{
    private UserModel $users;

    public function __construct()
    {
        $this->users = new UserModel();
    }

    public function showLogin(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }

        $this->render('auth/login', [
            'title' => 'Iniciar sesión',
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
        ]);

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function login(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];
        if ($email === '') {
            $errors['email'] = 'El correo es obligatorio';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['email' => $email];
            $this->redirect('/login');
        }

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['errors'] = ['general' => 'Credenciales inválidas'];
            $_SESSION['old'] = ['email' => $email];
            $this->redirect('/login');
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $this->redirect('/');
    }

    public function showRegister(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/');
        }

        $this->render('auth/register', [
            'title' => 'Crear cuenta',
            'errors' => $_SESSION['errors'] ?? [],
            'old' => $_SESSION['old'] ?? [],
        ]);

        unset($_SESSION['errors'], $_SESSION['old']);
    }

    public function register(): void
    {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['password_confirmation'] ?? '';

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'El nombre es obligatorio';
        }
        if ($email === '') {
            $errors['email'] = 'El correo es obligatorio';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El correo no es válido';
        }
        if ($password === '') {
            $errors['password'] = 'La contraseña es obligatoria';
        } elseif (strlen($password) < 6) {
            $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
        }
        if ($password !== $confirm) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
            $_SESSION['old'] = ['name' => $name, 'email' => $email];
            $this->redirect('/registro');
        }

        if ($this->users->findByEmail($email)) {
            $_SESSION['errors'] = ['email' => 'El correo ya está registrado'];
            $_SESSION['old'] = ['name' => $name, 'email' => $email];
            $this->redirect('/registro');
        }

        $this->users->create($name, $email, $password);
        $_SESSION['success'] = 'Cuenta creada, ahora puedes iniciar sesión';
        $this->redirect('/login');
    }

    public function logout(): void
    {
        session_destroy();
        session_start();
        $this->redirect('/login');
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }
}
