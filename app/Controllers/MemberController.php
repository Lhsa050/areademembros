<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\Member;
use App\Models\MemberProduct;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Funnel;
use App\Models\SupportContact;
use App\Services\EmailService;

/**
 * Controller de Membros (Admin) — escopado por funil
 */
class MemberController
{
    /**
     * Resolve o funil pelo ID (admin)
     */
    private function resolveFunnel(string $funnelId): array
    {
        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }
        return $funnel;
    }

    /**
     * Lista membros do funil
     */
    public function index(string $funnelId): void
    {
        Auth::require();
        $funnel = $this->resolveFunnel($funnelId);

        $query = $_GET['q'] ?? null;
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        $offset = ($page - 1) * $perPage;

        $members = Member::search($query, $perPage, $offset, $funnel['id']);
        $total = Member::countSearch($query, $funnel['id']);
        $totalPages = max(1, ceil($total / $perPage));

        // Carrega contagem de produtos por membro
        foreach ($members as &$member) {
            $products = MemberProduct::getActiveByMember($member['id']);
            $member['product_count'] = count($products);
        }

        view('admin.members.index', [
            'members' => $members,
            'query' => $query,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
            'funnel' => $funnel,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(string $funnelId): void
    {
        Auth::require();
        $funnel = $this->resolveFunnel($funnelId);

        view('admin.members.create', [
            'errors' => Validator::getErrors(),
            'funnel' => $funnel,
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva novo membro (no funil)
     */
    public function store(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        if (!Validator::make($_POST, [
            'name' => 'required|min:2',
            'email' => 'required|email',
        ])) {
            back();
        }

        // Verifica email duplicado dentro do funil
        $existing = Member::findByEmail($_POST['email'], $funnel['id']);
        if ($existing) {
            flash('error', 'Já existe um membro com este email neste funil.');
            save_old_input();
            back();
        }

        // Senha
        $password = null;
        $passwordHash = null;
        $loginMode = Setting::get('login_mode', 'email_only', (int) $funnel['id']);

        if (!empty($_POST['password'])) {
            $password = $_POST['password'];
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        } elseif ($loginMode === 'password') {
            $defaultPassword = Setting::get('default_password', '', (int) $funnel['id']);
            if ($defaultPassword) {
                $password = $defaultPassword;
                $passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);
            }
        }

        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');

        $memberId = Member::create([
            'funnel_id' => $funnel['id'],
            'name' => trim($_POST['name']),
            'email' => strtolower(trim($_POST['email'])),
            'cpf' => $cpf ?: null,
            'phone' => $phone ?: null,
            'password' => $passwordHash,
            'status' => 'active',
        ]);
        SupportContact::linkMemberByEmail(strtolower(trim($_POST['email'])), $memberId, (int) $funnel['id']);

        // Envia email de acesso se solicitado
        if (!empty($_POST['send_email']) && $_POST['send_email'] === '1') {
            $member = Member::find($memberId);
            $emailService = new EmailService((int) $funnel['id']);
            if ($emailService->isConfigured()) {
                $emailService->sendAccessEmail($member, $password, $funnel['slug']);
                flash('success', 'Membro criado e email de acesso enviado!');
            } else {
                flash('success', 'Membro criado! (SMTP não configurado, email não enviado)');
            }
        } else {
            flash('success', 'Membro criado com sucesso!');
        }

        clear_old_input();
        redirect(url('/funnels/' . $funnel['id'] . '/members'));
    }

    /**
     * Formulário de edição
     */
    public function edit(string $funnelId, string $id): void
    {
        Auth::require();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            flash('error', 'Membro não encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/members'));
        }

        $memberProducts = MemberProduct::getActiveByMember($member['id']);
        // Mostrar apenas produtos deste funil
        $allProducts = Product::getByFunnel($funnel['id']);

        view('admin.members.edit', [
            'member' => $member,
            'memberProducts' => $memberProducts,
            'allProducts' => $allProducts,
            'errors' => Validator::getErrors(),
            'funnel' => $funnel,
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza membro
     */
    public function update(string $funnelId, string $id): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            flash('error', 'Membro não encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/members'));
        }

        if (!Validator::make($_POST, [
            'name' => 'required|min:2',
            'email' => 'required|email',
        ])) {
            back();
        }

        // Verifica email duplicado (exceto o próprio) dentro do funil
        $existing = Member::findByEmail($_POST['email'], $funnel['id']);
        if ($existing && $existing['id'] !== $member['id']) {
            flash('error', 'Já existe outro membro com este email neste funil.');
            save_old_input();
            back();
        }

        $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
        $phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');

        $updates = [
            'name' => trim($_POST['name']),
            'email' => strtolower(trim($_POST['email'])),
            'cpf' => $cpf ?: null,
            'phone' => $phone ?: null,
            'status' => $_POST['status'] ?? 'active',
        ];

        // Atualiza senha se fornecida
        if (!empty($_POST['password'])) {
            $updates['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        }

        Member::update($member['id'], $updates);
        SupportContact::linkMemberByEmail($updates['email'], (int) $member['id'], (int) $funnel['id']);

        flash('success', 'Membro atualizado com sucesso!');
        redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
    }

    /**
     * Remove membro
     */
    public function destroy(string $funnelId, string $id): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            flash('error', 'Membro não encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/members'));
        }

        Member::delete($member['id']);
        flash('success', 'Membro removido com sucesso!');
        redirect(url('/funnels/' . $funnel['id'] . '/members'));
    }

    /**
     * Envia email de acesso manualmente
     */
    public function sendAccessEmail(string $funnelId, string $id): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            flash('error', 'Membro não encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/members'));
        }

        $emailService = new EmailService((int) $funnel['id']);
        if (!$emailService->isConfigured()) {
            flash('error', 'SMTP não configurado. Vá em Configurações para configurar.');
            redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
        }

        // Se tem senha padrão, inclui no email
        $password = null;
        $loginMode = Setting::get('login_mode', 'email_only', (int) $funnel['id']);
        if ($loginMode === 'password') {
            $defaultPassword = Setting::get('default_password', '', (int) $funnel['id']);
            if ($defaultPassword) {
                $password = $defaultPassword;
            }
        }

        if ($emailService->sendAccessEmail($member, $password, $funnel['slug'])) {
            flash('success', 'Email de acesso enviado para ' . $member['email'] . '!');
        } else {
            flash('error', 'Erro ao enviar email. Verifique as configurações SMTP.');
        }

        redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
    }

    /**
     * Adiciona produto ao membro
     */
    public function addProduct(string $funnelId, string $id): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            json_response(['error' => 'Membro não encontrado'], 404);
        }

        $productId = (int) ($_POST['product_id'] ?? 0);
        $product = Product::findForFunnel($productId, (int) $funnel['id']);
        if (!$product) {
            flash('error', 'Produto não encontrado neste funil.');
            redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
        }

        $granted = MemberProduct::grant($member['id'], $productId, 'admin');
        SupportContact::linkMemberByEmail($member['email'], (int) $member['id'], (int) $funnel['id']);
        if ($granted) {
        // Atualiza timestamp para forçar refresh da sessão do membro
        \App\Core\Database::query("UPDATE members SET updated_at = NOW() WHERE id = ?", [$member['id']]);
        flash('success', 'Produto adicionado ao membro!');
    } else {
            flash('error', 'O membro já possui este produto.');
        }

        redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
    }

    /**
     * Remove produto do membro
     */
    public function removeProduct(string $funnelId, string $id, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();
        $funnel = $this->resolveFunnel($funnelId);

        $member = Member::find((int) $id);
        if (!$member || (int)$member['funnel_id'] !== $funnel['id']) {
            flash('error', 'Membro não encontrado.');
            redirect(url('/funnels/' . $funnel['id'] . '/members'));
        }

        MemberProduct::revoke($member['id'], (int) $productId);
    // Atualiza timestamp para forçar refresh da sessão do membro
    \App\Core\Database::query("UPDATE members SET updated_at = NOW() WHERE id = ?", [$member['id']]);
    
    flash('success', 'Produto removido do membro!');
        redirect(url('/funnels/' . $funnel['id'] . '/members/' . $member['id'] . '/edit'));
    }
}
