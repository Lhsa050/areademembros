<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\FiscalTaxGroup;
use App\Models\Funnel;
use App\Models\Product;
use App\Models\Member;

use App\Models\Module;
use App\Models\Lesson;
use App\Models\ProductFile;
use App\Models\Generation;
use App\Services\PasswordGenerator;
use App\Services\ImageCompressor;
use App\Services\PageCache;
use App\Services\ProductContentBuilder;

/**
 * Controller de Funis
 */
class FunnelController
{
    /**
     * Lista de funis
     */
    public function index(): void
    {
        Auth::require();

        $funnels = Funnel::allOrdered();
        
        // Adiciona contagens
        foreach ($funnels as &$funnel) {
            $funnel['product_count'] = count(Product::getByFunnel((int) $funnel['id']));
        }

        view('admin.funnels.index', [
            'funnels' => $funnels,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(): void
    {
        Auth::require();

        view('admin.funnels.create', [
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva novo funil
     */
    public function store(): void
    {
        Auth::require();
        Security::requireCsrf();

        if (!Validator::make($_POST, [
            'name' => 'required|max:200',
            'theme' => 'required|in:elegante-escuro,elegante-claro,moderno-azul,moderno-verde,premium-dourado,minimalista'
        ])) {
            back();
        }

        // Gera slug automaticamente se não informado
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = Funnel::generateSlug($_POST['name']);
        } else {
            $slug = Funnel::generateSlug($slug);
        }

        $id = Funnel::create([
            'name' => $_POST['name'],
            'slug' => $slug,
            'description' => $_POST['description'] ?? '',
            'site_name' => $_POST['site_name'] ?? $_POST['name'],
            'theme' => $_POST['theme'],
            'webhook_token' => Funnel::generateWebhookToken()
        ]);

        flash('success', 'Funil criado com sucesso!');
        redirect(url('/funnels/' . $id));
    }

    /**
     * Painel do funil (visão geral)
     */
    public function show(string $id): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $id);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $products = Product::getByFunnel((int) $id);
        $generations = Generation::where('funnel_id', (int) $id);
        $memberCount = Member::countByFunnel((int) $id);

        view('admin.funnels.show', [
            'funnel' => $funnel,
            'products' => $products,
            'generations' => array_slice($generations, 0, 5),
            'memberCount' => $memberCount,
            'user' => Auth::user()
        ]);
    }

    /**
     * Editar funil
     */
    public function edit(string $id): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $id);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        view('admin.funnels.edit', [
            'funnel' => $funnel,
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza funil
     */
    public function update(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $funnel = Funnel::find((int) $id);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        if (!Validator::make($_POST, [
            'name' => 'required|max:200',
            'theme' => 'required|in:elegante-escuro,elegante-claro,moderno-azul,moderno-verde,premium-dourado,minimalista'
        ])) {
            back();
        }

        // Gera slug automaticamente se não informado
        $slug = trim($_POST['slug'] ?? '');
        if (empty($slug)) {
            $slug = Funnel::generateSlug($_POST['name'], (int) $id);
        } else {
            $slug = Funnel::generateSlug($slug, (int) $id);
        }

        Funnel::update((int) $id, [
            'name' => $_POST['name'],
            'slug' => $slug,
            'description' => $_POST['description'] ?? '',
            'site_name' => $_POST['site_name'] ?? $_POST['name'],
            'theme' => $_POST['theme'],
            'auto_organize' => isset($_POST['auto_organize']) ? 1 : 0,
            'language' => $_POST['language'] ?? 'pt-BR'
        ]);

        flash('success', 'Funil atualizado com sucesso!');
        redirect(url('/funnels/' . $id));
    }

    /**
     * Deleta funil
     */
    public function destroy(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        // Remove os vínculos de produtos deste funil sem apagar os produtos globais.
        $products = Product::getByFunnel((int) $id);
        foreach ($products as $product) {
            Product::unlinkFromFunnel((int) $id, (int) $product['id']);
        }



        Funnel::delete((int) $id);

        flash('success', 'Funil removido com sucesso!');
        redirect(url('/funnels'));
    }

    // === PRODUTOS DO FUNIL ===

    /**
     * Lista produtos do funil
     */
    public function products(string $funnelId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $products = Product::getByFunnel((int) $funnelId);

        view('admin.funnels.products.index', [
            'funnel' => $funnel,
            'products' => $products,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação de produto
     */
    public function createProduct(string $funnelId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        view('admin.funnels.products.create', [
            'funnel' => $funnel,
            'availableProducts' => Product::availableForFunnel((int) $funnelId),
            'taxGroups' => FiscalTaxGroup::forModel('nfse'),
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva produto no funil
     */
    public function storeProduct(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $selectedProductIds = $_POST['product_ids'] ?? [];
        if (is_array($selectedProductIds) && !empty($selectedProductIds)) {
            $linked = 0;
            $settings = [
                'release_days' => isset($_POST['release_days']) && $_POST['release_days'] !== '' ? (int) $_POST['release_days'] : null,
                'is_public' => (($_POST['access_mode'] ?? 'webhook') === 'public') ? 1 : 0,
            ];

            foreach (array_unique(array_map('intval', $selectedProductIds)) as $productId) {
                if ($productId <= 0 || !Product::find($productId)) {
                    continue;
                }

                Product::linkToFunnel((int) $funnelId, $productId, $settings);
                $linked++;
            }

            PageCache::clearAll();
            flash($linked > 0 ? 'success' : 'error', $linked > 0 ? 'Produto(s) vinculado(s) ao funil!' : 'Selecione ao menos um produto válido.');
            redirect(url("/funnels/{$funnelId}/products"));
        }

        if (!isset($_POST['type'])) {
            flash('error', 'Selecione ao menos um produto para vincular.');
            redirect(url("/funnels/{$funnelId}/products/create"));
        }

        if (!Validator::make($_POST, [
            'type' => 'required|in:video,pdf',
            'title' => 'required|max:200',
            'description' => 'required'
        ])) {
            back();
        }

        // Upload de imagem
        $imagePath = $this->handleImageUpload();

        // Upload de PDF se for tipo pdf
        $filePath = null;
        if ($_POST['type'] === 'pdf') {
            $filePath = $this->handlePdfUpload();
        }

        $sortOrder = array_key_exists('sort_order', $_POST)
            ? max(1, (int) $_POST['sort_order'])
            : Product::nextSortOrder((int) $funnelId);

        $productId = Product::create(array_merge([
            'funnel_id' => null,
            'type' => $_POST['type'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'image' => $imagePath,
            'file' => $filePath,
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'external_product_id' => trim($_POST['external_product_id'] ?? '') ?: null,
            'checkout_url' => trim($_POST['checkout_url'] ?? '') ?: null,
        ], $this->fiscalPayload($_POST['type'] === 'pdf' ? 'ebook' : 'course')));

        if ($_POST['type'] === 'video' && isset($_POST['initial_modules']) && is_array($_POST['initial_modules'])) {
            ProductContentBuilder::createInitialModules((int) $productId, $_POST['initial_modules']);
        }

        if ($_POST['type'] === 'pdf' && isset($_POST['initial_product_files']) && is_array($_POST['initial_product_files'])) {
            $firstFilePath = ProductContentBuilder::createInitialProductFiles((int) $productId, $_POST['initial_product_files'], $_FILES['initial_product_files'] ?? []);
            if ($filePath === null && $firstFilePath !== null) {
                Product::update((int) $productId, ['file' => $firstFilePath]);
            }
        }

        Product::linkToFunnel((int) $funnelId, $productId, [
            'webhook_token' => Product::generateWebhookToken(),
            'sort_order' => $sortOrder,
            'release_days' => null,
            'is_public' => 0,
        ]);

        PageCache::clearAll();
        flash('success', 'Produto criado com sucesso!');
        
        if ($_POST['type'] === 'video') {
            redirect(url("/funnels/{$funnelId}/products/{$productId}/edit"));
        }
        
        redirect(url("/funnels/{$funnelId}/products"));
    }

    /**
     * Editar produto
     */
    public function editProduct(string $funnelId, string $productId): void
    {
        Auth::require();

        $funnel = Funnel::find((int) $funnelId);
        if (!$funnel) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        $product = Product::findWithModulesForFunnel((int) $productId, (int) $funnelId);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url("/funnels/{$funnelId}/products"));
        }

        view('admin.funnels.products.edit', [
            'funnel' => $funnel,
            'product' => $product,
            'taxGroups' => FiscalTaxGroup::forModel('nfse'),
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza produto
     */
    public function updateProduct(string $funnelId, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        $product = Product::findForFunnel((int) $productId, (int) $funnelId);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url("/funnels/{$funnelId}/products"));
        }

        if (!Validator::make($_POST, [
            'title' => 'required|max:200',
            'description' => 'required'
        ])) {
            back();
        }

        $data = array_merge([
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'external_product_id' => trim($_POST['external_product_id'] ?? '') ?: null,
            'checkout_url' => trim($_POST['checkout_url'] ?? '') ?: null,
        ], $this->fiscalPayload($product['type'] === 'pdf' ? 'ebook' : 'course'));

        $funnelSettings = [
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null,
            'is_public' => (($_POST['access_mode'] ?? 'webhook') === 'public') ? 1 : 0,
            'funnel_role' => in_array($_POST['funnel_role'] ?? '', ['principal', 'bonus', 'orderbump']) ? $_POST['funnel_role'] : null,
        ];

        if (array_key_exists('sort_order', $_POST)) {
            $funnelSettings['sort_order'] = max(1, (int) $_POST['sort_order']);
        }

        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->handleImageUpload();
            if ($imagePath) $data['image'] = $imagePath;
        }

        if ($product['type'] === 'pdf' && !empty($_FILES['file']['name'])) {
            $filePath = $this->handlePdfUpload();
            if ($filePath) $data['file'] = $filePath;
        }

        Product::update((int) $productId, $data);
        Product::updateFunnelSettings((int) $funnelId, (int) $productId, $funnelSettings);


        PageCache::clearAll();
        flash('success', 'Produto atualizado com sucesso!');
        redirect(url("/funnels/{$funnelId}/products"));
    }

    /**
     * Deleta produto
     */
    public function destroyProduct(string $funnelId, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (!Product::belongsToFunnel((int) $productId, (int) $funnelId)) {
            flash('error', 'Produto não encontrado neste funil.');
            redirect(url("/funnels/{$funnelId}/products"));
        }

        Product::unlinkFromFunnel((int) $funnelId, (int) $productId);

        PageCache::clearAll();
        flash('success', 'Produto removido deste funil!');
        redirect(url("/funnels/{$funnelId}/products"));
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


    // === UPLOADS ===

    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['image']['name'])) return null;

        $file = $_FILES['image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            flash('error', 'Formato de imagem inválido.');
            return null;
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        // Converte para WebP usando ImageCompressor
        $result = ImageCompressor::compressUpload($file, $uploadDir);
        
        if ($result) {
            return $result;
        }

        // Fallback se compressão falhar (ou estiver desabilitada)
        $filename = uniqid('img_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;
        
        // Debug Log Image Fallback
        error_log(date('[Y-m-d H:i:s] ') . "Starting Image Fallback: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Fallback Target: " . $finalPath, 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $finalPath)) {
            if (!file_exists($finalPath)) {
                error_log(date('[Y-m-d H:i:s] ') . "CRITICAL: Image moved but not found!", 3, ABSPATH . '/debug_upload_log.txt');
                flash('error', 'Erro crítico: Arquivo movido mas não encontrado no disco.');
                return null;
            }
            error_log(date('[Y-m-d H:i:s] ') . "SUCCESS: Image exists. Size: " . filesize($finalPath), 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        } else {
            error_log(date('[Y-m-d H:i:s] ') . "ERROR: Image move_uploaded_file failed.", 3, ABSPATH . '/debug_upload_log.txt');
        }

        return null;
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

    private function fiscalPayload(string $defaultKind): array
    {
        return [
            'fiscal_kind' => $_POST['fiscal_kind'] ?? $defaultKind,
            'fiscal_tax_group_id' => !empty($_POST['fiscal_tax_group_id']) ? (int) $_POST['fiscal_tax_group_id'] : null,
            'fiscal_document_model' => ($_POST['fiscal_document_model'] ?? 'nfse') === 'nfe' ? 'nfe' : 'nfse',
            'fiscal_issue_policy' => in_array(($_POST['fiscal_issue_policy'] ?? 'on_payment'), ['on_payment', 'manual', 'after_warranty'], true)
                ? $_POST['fiscal_issue_policy']
                : 'on_payment',
            'fiscal_warranty_days' => isset($_POST['fiscal_warranty_days']) && $_POST['fiscal_warranty_days'] !== '' ? max(0, (int) $_POST['fiscal_warranty_days']) : null,
            'fiscal_service_code' => trim($_POST['fiscal_service_code'] ?? ''),
            'fiscal_service_description' => trim($_POST['fiscal_service_description'] ?? ''),
            'fiscal_nbs_code' => trim($_POST['fiscal_nbs_code'] ?? ''),
            'fiscal_iss_rate' => $this->parseMoney($_POST['fiscal_iss_rate'] ?? ''),
            'fiscal_lc116_code' => trim($_POST['fiscal_lc116_code'] ?? ''),
            'fiscal_municipal_service_code' => trim($_POST['fiscal_municipal_service_code'] ?? ''),
            'fiscal_cnae_code' => preg_replace('/\D+/', '', (string) ($_POST['fiscal_cnae_code'] ?? '')),
        ];
    }

    private function handlePdfUpload(): ?string
    {
        if (empty($_FILES['file']['name'])) return null;

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            flash('error', 'Apenas arquivos PDF são permitidos.');
            return null;
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = uniqid('pdf_') . '.pdf';
        $finalPath = $uploadDir . $filename;

        // Debug Log
        error_log(date('[Y-m-d H:i:s] ') . "Starting PDF upload: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Temp: " . $file['tmp_name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Target: " . $finalPath, 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $finalPath)) {
            if (!file_exists($finalPath)) {
                error_log(date('[Y-m-d H:i:s] ') . "CRITICAL: File moved but not found!", 3, ABSPATH . '/debug_upload_log.txt');
                flash('error', 'Erro crítico: PDF movido mas não encontrado no disco.');
                return null;
            }
            error_log(date('[Y-m-d H:i:s] ') . "SUCCESS: File exists. Size: " . filesize($finalPath), 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        } else {
            error_log(date('[Y-m-d H:i:s] ') . "ERROR: move_uploaded_file failed.", 3, ABSPATH . '/debug_upload_log.txt');
        }

        return null;
    }

    // === DUPLICAÇÃO ===

    /**
     * Duplica um funil completo
     */
    public function duplicateFunnel(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $original = Funnel::find((int) $funnelId);
        if (!$original) {
            flash('error', 'Funil não encontrado.');
            redirect(url('/funnels'));
        }

        // Cria cópia do funil
        $newFunnelId = Funnel::create([
            'name' => $original['name'] . ' (Cópia)',
            'slug' => Funnel::generateSlug($original['name'] . '-copia'),
            'description' => $original['description'],
            'site_name' => $original['site_name'],
            'theme' => $original['theme']
        ]);

        // Reaproveita os mesmos produtos no novo funil, mantendo configuracoes do funil.
        $products = Product::getByFunnel((int) $funnelId);
        foreach ($products as $product) {
            Product::linkToFunnel((int) $newFunnelId, (int) $product['id'], [
                'webhook_token' => Product::generateWebhookToken(),
                'sort_order' => $product['sort_order'],
                'release_days' => $product['release_days'] ?? null,
                'is_public' => !empty($product['is_public']) ? 1 : 0,
                'funnel_role' => $product['funnel_role'] ?? null
            ]);
        }



        PageCache::clearAll();
        flash('success', 'Funil duplicado com sucesso!');
        redirect(url("/funnels/{$newFunnelId}"));
    }

    /**
     * Duplica um produto
     */
    public function duplicateProduct(string $funnelId, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        $product = Product::findForFunnel((int) $productId, (int) $funnelId);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url("/funnels/{$funnelId}/products"));
        }

        $newSortOrder = (int) ($product['sort_order'] ?? 0) + 1;
        if ($newSortOrder > 1) {
            Product::shiftSortOrderFrom((int) $funnelId, $newSortOrder);
        } else {
            $newSortOrder = Product::nextSortOrder((int) $funnelId);
        }

        $newProductId = Product::create([
            'funnel_id' => null,
            'type' => $product['type'],
            'title' => $product['title'] . ' (Cópia)',
            'description' => $product['description'],
            'image' => $product['image'],
            'file' => $product['file'],
            'price' => $product['price'] ?? null,
            'external_product_id' => $product['external_product_id'] ?? null,
            'checkout_url' => $product['checkout_url'] ?? null,
            'fiscal_kind' => $product['fiscal_kind'] ?? null,
            'fiscal_service_code' => $product['fiscal_service_code'] ?? null,
            'fiscal_service_description' => $product['fiscal_service_description'] ?? null,
            'fiscal_nbs_code' => $product['fiscal_nbs_code'] ?? null,
            'fiscal_iss_rate' => $product['fiscal_iss_rate'] ?? null,
            'fiscal_tax_group_id' => $product['fiscal_tax_group_id'] ?? null,
            'fiscal_document_model' => $product['fiscal_document_model'] ?? null,
            'fiscal_issue_policy' => $product['fiscal_issue_policy'] ?? null,
            'fiscal_warranty_days' => $product['fiscal_warranty_days'] ?? null,
            'fiscal_lc116_code' => $product['fiscal_lc116_code'] ?? null,
            'fiscal_municipal_service_code' => $product['fiscal_municipal_service_code'] ?? null,
            'fiscal_cnae_code' => $product['fiscal_cnae_code'] ?? null
        ]);

        Product::linkToFunnel((int) $funnelId, $newProductId, [
            'webhook_token' => Product::generateWebhookToken(),
            'sort_order' => $newSortOrder,
            'release_days' => $product['release_days'] ?? null,
            'is_public' => !empty($product['is_public']) ? 1 : 0,
            'funnel_role' => $product['funnel_role'] ?? null,
        ]);

        // Duplica arquivos do produto
        $productFiles = ProductFile::getByProduct((int) $productId);
        foreach ($productFiles as $pFile) {
            $fileData = [
                'product_id' => $newProductId,
                'title' => $pFile['title'],
                'file' => $pFile['file'],
                'file_type' => $pFile['file_type'] ?? 'upload',
                'sort_order' => $pFile['sort_order'],
                'release_days' => $pFile['release_days'] ?? null
            ];
            if (ProductFile::supportsOpenInNewTab()) {
                $fileData['open_in_new_tab'] = !empty($pFile['open_in_new_tab']) ? 1 : 0;
            }
            ProductFile::create($fileData);
        }

        // Duplica módulos e aulas
        if ($product['type'] === 'video') {
            $modules = Module::getByProduct((int) $productId);
            foreach ($modules as $module) {
                $newModuleId = Module::create([
                    'product_id' => $newProductId,
                    'title' => $module['title'],
                    'sort_order' => $module['sort_order'],
                    'release_days' => $module['release_days'] ?? null
                ]);
                $lessons = Lesson::getByModule($module['id']);
                foreach ($lessons as $lesson) {
                    Lesson::create([
                        'module_id' => $newModuleId,
                        'title' => $lesson['title'],
                        'description' => $lesson['description'] ?? '',
                        'youtube_id' => $lesson['youtube_id'] ?? null,
                        'file' => $lesson['file'] ?? '',
                        'sort_order' => $lesson['sort_order'],
                        'release_days' => $lesson['release_days'] ?? null
                    ]);
                }
            }
        }

        PageCache::clearAll();
        flash('success', 'Produto duplicado com sucesso!');
        redirect(url("/funnels/{$funnelId}/products/{$newProductId}/edit"));
    }


}
