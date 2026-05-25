<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\AccessLevel;
use App\Models\Product;
use App\Services\PasswordGenerator;

/**
 * Controller de Níveis de Acesso
 */
class AccessLevelController
{
    /**
     * Lista níveis
     */
    public function index(): void
    {
        Auth::require();

        $levels = AccessLevel::all('id', 'ASC');

        // Adiciona contagem de produtos
        foreach ($levels as &$level) {
            $level['product_count'] = count(AccessLevel::getProductIds($level['id']));
        }

        view('admin.access-levels.index', [
            'levels' => $levels,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(): void
    {
        Auth::require();

        $products = Product::allOrdered();

        view('admin.access-levels.create', [
            'products' => $products,
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva novo nível
     */
    public function store(): void
    {
        Auth::require();
        Security::requireCsrf();

        if (!Validator::make($_POST, [
            'name' => 'required|max:100',
            'password' => 'required|max:50'
        ])) {
            back();
        }

        $id = AccessLevel::create([
            'name' => $_POST['name'],
            'uuid_key' => AccessLevel::generateUuidKey(),
            'password' => strtoupper(trim($_POST['password']))
        ]);

        // Sincroniza produtos
        $productIds = $_POST['products'] ?? [];
        AccessLevel::syncProducts($id, $productIds);

        flash('success', 'Nível de acesso criado com sucesso!');
        redirect(url('/access-levels'));
    }

    /**
     * Formulário de edição
     */
    public function edit(string $id): void
    {
        Auth::require();

        $level = AccessLevel::find((int) $id);
        if (!$level) {
            flash('error', 'Nível de acesso não encontrado.');
            redirect(url('/access-levels'));
        }

        $products = Product::allOrdered();
        $selectedProducts = AccessLevel::getProductIds((int) $id);

        view('admin.access-levels.edit', [
            'level' => $level,
            'products' => $products,
            'selectedProducts' => $selectedProducts,
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza nível
     */
    public function update(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $level = AccessLevel::find((int) $id);
        if (!$level) {
            flash('error', 'Nível de acesso não encontrado.');
            redirect(url('/access-levels'));
        }

        if (!Validator::make($_POST, [
            'name' => 'required|max:100',
            'password' => 'required|max:50'
        ])) {
            back();
        }

        AccessLevel::update((int) $id, [
            'name' => $_POST['name'],
            'password' => strtoupper(trim($_POST['password']))
        ]);

        // Sincroniza produtos
        $productIds = $_POST['products'] ?? [];
        AccessLevel::syncProducts((int) $id, $productIds);

        flash('success', 'Nível de acesso atualizado com sucesso!');
        redirect(url('/access-levels'));
    }

    /**
     * Deleta nível
     */
    public function destroy(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        // Remove vínculos com produtos
        AccessLevel::syncProducts((int) $id, []);
        
        AccessLevel::delete((int) $id);

        flash('success', 'Nível de acesso removido com sucesso!');
        redirect(url('/access-levels'));
    }

    /**
     * Gera senha (AJAX)
     */
    public function generatePassword(): void
    {
        Auth::require();

        $type = $_POST['type'] ?? 'simple';

        $password = match ($type) {
            'secure' => PasswordGenerator::secure(),
            'words' => PasswordGenerator::words(),
            default => PasswordGenerator::simple()
        };

        json_response(['password' => $password]);
    }
}
