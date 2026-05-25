<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Funnel;
use App\Services\PageCache;

/**
 * Controller de Ofertas (Upsell)
 */
class OfferController
{
    /**
     * Lista ofertas do funil
     */
    public function index(string $funnelId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $offers = Offer::getByFunnel((int) $funnelId);

        view('admin.funnels.offers.index', [
            'funnel' => $funnel,
            'offers' => $offers,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(string $funnelId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $products = Product::getByFunnel((int) $funnelId);

        view('admin.funnels.offers.create', [
            'funnel' => $funnel,
            'products' => $products,
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva nova oferta
     */
    public function store(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            flash('error', 'Título é obrigatório.');
            back();
        }

        // Upload de imagem
        $imagePath = null;
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $uploadDir = ABSPATH . '/public/assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = uniqid('offer_') . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $imagePath = 'assets/uploads/' . $filename;
                }
            }
        }

        $offerId = Offer::create([
            'funnel_id' => (int) $funnelId,
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'image' => $imagePath,
            'checkout_url' => $_POST['checkout_url'] ?? '',
            'webhook_token' => Offer::generateWebhookToken(),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'show_as_popup' => isset($_POST['show_as_popup']) ? 1 : 0,
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'fiscal_kind' => $_POST['fiscal_kind'] ?? 'course',
            'fiscal_service_code' => trim($_POST['fiscal_service_code'] ?? ''),
            'fiscal_service_description' => trim($_POST['fiscal_service_description'] ?? ''),
            'fiscal_nbs_code' => trim($_POST['fiscal_nbs_code'] ?? ''),
            'fiscal_iss_rate' => $this->parseMoney($_POST['fiscal_iss_rate'] ?? ''),
        ]);

        // Vincula produtos
        $productIds = $_POST['products'] ?? [];
        if (!empty($productIds)) {
            Offer::syncProducts($offerId, $productIds);
        }

        PageCache::clearAll();
        flash('success', 'Oferta criada com sucesso!');
        redirect(url('/funnels/' . $funnelId . '/offers'));
    }

    /**
     * Formulário de edição
     */
    public function edit(string $funnelId, string $offerId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        $offer = Offer::find((int) $offerId);
        if (!$funnel || !$offer) {
            flash('error', 'Não encontrado.');
            redirect(url('/funnels'));
        }

        $products = Product::getByFunnel((int) $funnelId);
        $selectedProductIds = Offer::getProductIds((int) $offerId);

        view('admin.funnels.offers.edit', [
            'funnel' => $funnel,
            'offer' => $offer,
            'products' => $products,
            'selectedProductIds' => $selectedProductIds,
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza oferta
     */
    public function update(string $funnelId, string $offerId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            flash('error', 'Título é obrigatório.');
            back();
        }

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'checkout_url' => $_POST['checkout_url'] ?? '',
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'show_as_popup' => isset($_POST['show_as_popup']) ? 1 : 0,
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'fiscal_kind' => $_POST['fiscal_kind'] ?? 'course',
            'fiscal_service_code' => trim($_POST['fiscal_service_code'] ?? ''),
            'fiscal_service_description' => trim($_POST['fiscal_service_description'] ?? ''),
            'fiscal_nbs_code' => trim($_POST['fiscal_nbs_code'] ?? ''),
            'fiscal_iss_rate' => $this->parseMoney($_POST['fiscal_iss_rate'] ?? ''),
        ];

        // Upload de nova imagem
        if (!empty($_FILES['image']['name'])) {
            $file = $_FILES['image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $uploadDir = ABSPATH . '/public/assets/uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = uniqid('offer_') . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                    $data['image'] = 'assets/uploads/' . $filename;
                }
            }
        }

        Offer::update((int) $offerId, $data);

        // Atualiza produtos
        $productIds = $_POST['products'] ?? [];
        Offer::syncProducts((int) $offerId, $productIds);

        PageCache::clearAll();
        flash('success', 'Oferta atualizada!');
        redirect(url('/funnels/' . $funnelId . '/offers'));
    }

    /**
     * Remove oferta
     */
    public function destroy(string $funnelId, string $offerId): void
    {
        Auth::require();
        Security::requireCsrf();

        // Remove vínculos
        \App\Core\Database::query("DELETE FROM offer_products WHERE offer_id = ?", [(int) $offerId]);
        Offer::delete((int) $offerId);

        PageCache::clearAll();
        flash('success', 'Oferta removida!');
        redirect(url('/funnels/' . $funnelId . '/offers'));
    }

    private function parseMoney(string $value): ?float
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(['R$', ' '], '', $value);
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return max(0, (float) $value);
    }
}
