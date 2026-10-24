<?php

require_once __DIR__ . '/../models/User.php';

class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/login.php';
    }

    public function login(): void
    {
        Csrf::requireValid();

        $email = trim($_POST['email'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'email', 'Email')->required($_POST, 'password', 'Password');
        $errors = $v->errors();

        if (empty($errors) && !Auth::attempt($email, $password)) {
            $errors['login'] = 'Invalid email/password, or this account is inactive.';
        }

        if (!empty($errors)) {
            $old = $_POST;
            require __DIR__ . '/../views/auth/login.php';
            return;
        }

        redirect(Auth::dashboardRoute(Auth::role()));
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            redirect(Auth::dashboardRoute(Auth::role()));
        }
        $errors = [];
        $old = [];
        require __DIR__ . '/../views/auth/register.php';
    }

    public function register(): void
    {
        Csrf::requireValid();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $role = $_POST['role'] ?? '';
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $v = new Validator();
        $v->required($_POST, 'name', 'Name')->maxLength($_POST, 'name', 120, 'Name')
          ->required($_POST, 'email', 'Email')->email($_POST, 'email', 'Email')
          ->required($_POST, 'phone', 'Phone')->maxLength($_POST, 'phone', 20, 'Phone')
          ->required($_POST, 'role', 'Role')->inArray($_POST, 'role', ['driver', 'rider'], 'Role')
          ->required($_POST, 'password', 'Password')->minLength($_POST, 'password', 6, 'Password');
        $errors = $v->errors();

        if (empty($errors) && $password !== $confirm) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }
        if (empty($errors) && User::emailExists($email)) {
            $errors['email'] = 'An account with this email already exists.';
        }

        if (!empty($errors)) {
            require __DIR__ . '/../views/auth/register.php';
            return;
        }

        User::register($name, $email, $phone, $password, $role);
        flash_set('success', 'Account created. Please log in.');
        redirect('login');
    }

    public function logout(): void
    {
        Csrf::requireValid();
        Auth::logout();
        redirect('login');
    }
}
