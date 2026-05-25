<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;

/**
 * Controller de Autenticação
 */
class AuthController
{
    /**
     * Exibe página de login
     */
    public function showLogin(): void
    {
        // Se já está logado, redireciona
        if (Auth::check()) {
            redirect(url('/dashboard'));
        }

        view('auth.login', [
            'errors' => Validator::getErrors()
        ]);
    }

    /**
     * Processa login
     */
    public function login(): void
    {
        Security::requireCsrf();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Valida
        if (!Validator::make($_POST, [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ])) {
            back();
        }

        // Tenta autenticar
        if (Auth::attempt($email, $password)) {
            clear_old_input();
            flash('success', 'Bem-vindo(a)!');
            redirect(url('/dashboard'));
        }

        flash('error', 'Email ou senha incorretos.');
        save_old_input();
        redirect(url('/login'));
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        Auth::logout();
        flash('success', 'Você saiu do sistema.');
        redirect(url('/login'));
    }
}
