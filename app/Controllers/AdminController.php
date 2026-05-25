<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\Admin;

/**
 * Controller de Administradores
 */
class AdminController
{
    /**
     * Lista admins
     */
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $admins = Admin::all('name', 'ASC');

        view('admin.admins.index', [
            'admins' => $admins,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(): void
    {
        Auth::requireSuperAdmin();

        view('admin.admins.create', [
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva novo admin
     */
    public function store(): void
    {
        Auth::requireSuperAdmin();
        Security::requireCsrf();

        if (!Validator::make($_POST, [
            'name' => 'required|max:100',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:admin,super_admin'
        ])) {
            back();
        }

        Admin::createWithPassword([
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],
            'role' => $_POST['role'],
            'status' => 'active'
        ]);

        flash('success', 'Administrador criado com sucesso!');
        redirect(url('/admins'));
    }

    /**
     * Formulário de edição
     */
    public function edit(string $id): void
    {
        Auth::requireSuperAdmin();

        $admin = Admin::find((int) $id);
        if (!$admin) {
            flash('error', 'Administrador não encontrado.');
            redirect(url('/admins'));
        }

        view('admin.admins.edit', [
            'admin' => $admin,
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza admin
     */
    public function update(string $id): void
    {
        Auth::requireSuperAdmin();
        Security::requireCsrf();

        $admin = Admin::find((int) $id);
        if (!$admin) {
            flash('error', 'Administrador não encontrado.');
            redirect(url('/admins'));
        }

        $rules = [
            'name' => 'required|max:100',
            'email' => 'required|email|unique:admins,email,' . $id,
            'role' => 'required|in:admin,super_admin',
            'status' => 'required|in:active,inactive'
        ];

        // Senha é opcional na edição
        if (!empty($_POST['password'])) {
            $rules['password'] = 'min:6|confirmed';
        }

        if (!Validator::make($_POST, $rules)) {
            back();
        }

        Admin::updateWithPassword((int) $id, [
            'name' => $_POST['name'],
            'email' => $_POST['email'],
            'password' => $_POST['password'] ?? '',
            'role' => $_POST['role'],
            'status' => $_POST['status']
        ]);

        flash('success', 'Administrador atualizado com sucesso!');
        redirect(url('/admins'));
    }

    /**
     * Deleta admin
     */
    public function destroy(string $id): void
    {
        Auth::requireSuperAdmin();
        Security::requireCsrf();

        // Não pode deletar a si mesmo
        if ((int) $id === Auth::id()) {
            flash('error', 'Você não pode deletar sua própria conta.');
            redirect(url('/admins'));
        }

        Admin::delete((int) $id);

        flash('success', 'Administrador removido com sucesso!');
        redirect(url('/admins'));
    }
}
