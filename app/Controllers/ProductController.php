<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Security;
use App\Core\Validator;
use App\Models\FiscalTaxGroup;
use App\Models\Funnel;
use App\Models\Product;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\ProductFile;
use App\Models\LessonFile;
use App\Services\PageCache;
use App\Services\ProductContentBuilder;

/**
 * Controller de Produtos
 */
class ProductController
{
    /**
     * Lista produtos
     */
    public function index(): void
    {
        Auth::require();

        $search = trim((string) ($_GET['q'] ?? ''));
        $funnelId = max(0, (int) ($_GET['funnel_id'] ?? 0));
        $products = Product::allOrdered();
        $funnels = Funnel::allOrdered();

        if ($search !== '') {
            $products = array_values(array_filter($products, function (array $product) use ($search): bool {
                return stripos((string) ($product['title'] ?? ''), $search) !== false
                    || stripos((string) ($product['description'] ?? ''), $search) !== false
                    || stripos((string) ($product['external_product_id'] ?? ''), $search) !== false;
            }));
        }

        if ($funnelId > 0) {
            $funnelProductIds = array_flip(array_map('intval', array_column(Product::getByFunnel($funnelId), 'id')));
            $products = array_values(array_filter($products, function (array $product) use ($funnelProductIds): bool {
                return isset($funnelProductIds[(int) ($product['id'] ?? 0)]);
            }));
        }

        view('admin.products.index', [
            'products' => $products,
            'funnels' => $funnels,
            'filters' => [
                'q' => $search,
                'funnel_id' => $funnelId,
            ],
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de criação
     */
    public function create(): void
    {
        Auth::require();

        view('admin.products.create', [
            'taxGroups' => FiscalTaxGroup::forModel('nfse'),
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Salva novo produto
     */
    public function store(): void
    {
        Auth::require();
        Security::requireCsrf();

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

        $data = [
            'funnel_id' => null,
            'type' => $_POST['type'],
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'image' => $imagePath,
            'file' => $filePath,
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'external_product_id' => trim($_POST['external_product_id'] ?? '') ?: null,
            'checkout_url' => trim($_POST['checkout_url'] ?? '') ?: null,
        ] + $this->fiscalPayload($_POST['type'] === 'pdf' ? 'ebook' : 'course');

        if (array_key_exists('access_mode', $_POST)) {
            $data['is_public'] = (($_POST['access_mode'] ?? 'webhook') === 'public') ? 1 : 0;
        }

        $id = Product::create($data);

        if ($_POST['type'] === 'video' && isset($_POST['initial_modules']) && is_array($_POST['initial_modules'])) {
            ProductContentBuilder::createInitialModules((int) $id, $_POST['initial_modules']);
        }

        if ($_POST['type'] === 'pdf' && isset($_POST['initial_product_files']) && is_array($_POST['initial_product_files'])) {
            $firstFilePath = ProductContentBuilder::createInitialProductFiles((int) $id, $_POST['initial_product_files'], $_FILES['initial_product_files'] ?? []);
            if ($filePath === null && $firstFilePath !== null) {
                Product::update((int) $id, ['file' => $firstFilePath]);
            }
        }

        PageCache::clearAll();
        flash('success', 'Produto criado com sucesso!');
        
        // Em video, abre a edicao para revisar ou continuar montando as aulas.
        if ($_POST['type'] === 'video') {
            redirect(url("/products/{$id}/edit"));
        }
        
        redirect(url('/products'));
    }

    /**
     * Exibe produto
     */
    public function show(string $id): void
    {
        Auth::require();

        $product = Product::findWithModules((int) $id);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url('/products'));
        }

        view('admin.products.show', [
            'product' => $product,
            'user' => Auth::user()
        ]);
    }

    /**
     * Formulário de edição
     */
    public function edit(string $id): void
    {
        Auth::require();

        $product = Product::findWithModules((int) $id);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url('/products'));
        }

        view('admin.products.edit', [
            'product' => $product,
            'taxGroups' => FiscalTaxGroup::forModel('nfse'),
            'errors' => Validator::getErrors(),
            'user' => Auth::user()
        ]);
    }

    /**
     * Atualiza produto
     */
    public function update(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        $product = Product::find((int) $id);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url('/products'));
        }

        if (!Validator::make($_POST, [
            'title' => 'required|max:200',
            'description' => 'required'
        ])) {
            back();
        }

        $data = [
            'title' => $_POST['title'],
            'description' => $_POST['description'],
            'price' => $this->parseMoney($_POST['price'] ?? ''),
            'external_product_id' => trim($_POST['external_product_id'] ?? '') ?: null,
            'checkout_url' => trim($_POST['checkout_url'] ?? '') ?: null,
        ] + $this->fiscalPayload($product['type'] === 'pdf' ? 'ebook' : 'course');

        if (array_key_exists('access_mode', $_POST)) {
            $data['is_public'] = (($_POST['access_mode'] ?? 'webhook') === 'public') ? 1 : 0;
        }

        if (array_key_exists('sort_order', $_POST)) {
            $data['sort_order'] = max(1, (int) $_POST['sort_order']);
        }

        // Upload de nova imagem se enviada
        if (!empty($_FILES['image']['name'])) {
            $imagePath = $this->handleImageUpload();
            if ($imagePath) {
                $data['image'] = $imagePath;
            }
        }

        // Upload de novo PDF se enviado
        if ($product['type'] === 'pdf' && !empty($_FILES['file']['name'])) {
            $filePath = $this->handlePdfUpload();
            if ($filePath) {
                $data['file'] = $filePath;
            }
        }

        Product::update((int) $id, $data);

        PageCache::clearAll();
        flash('success', 'Produto atualizado com sucesso!');
        redirect(url('/products'));
    }

    /**
     * Deleta produto
     */
    public function destroy(string $id): void
    {
        Auth::require();
        Security::requireCsrf();

        // Deleta módulos e aulas
        $modules = Module::getByProduct((int) $id);
        foreach ($modules as $module) {
            Module::deleteWithLessons($module['id']);
        }
        ProductFile::deleteByProduct((int) $id);

        Product::delete((int) $id);

        PageCache::clearAll();
        flash('success', 'Produto removido com sucesso!');
        redirect(url('/products'));
    }

    // === MÓDULOS ===

    /**
     * Reordena produtos via drag-and-drop (AJAX)
     */
    public function reorderProducts(string $funnelId): void
    {
        Auth::require();
        Security::requireCsrf();

        $raw = file_get_contents('php://input');
        $input = $raw ? json_decode($raw, true) : [];
        if (!is_array($input)) $input = [];
        
        $order = $input['order'] ?? ($_POST['order'] ?? null);

        if (!is_array($order)) {
            json_response(['success' => false, 'error' => 'Ordem inválida'], 400);
        }

        $db = \App\Core\Database::getInstance();
        $db->beginTransaction();
        try {
            $persistedOrder = Product::reorderForFunnel((int) $funnelId, $order);
            $db->commit();
        } catch (\InvalidArgumentException $e) {
            $db->rollBack();
            json_response(['success' => false, 'error' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            $db->rollBack();
            json_response(['success' => false, 'error' => 'Erro ao reordenar: ' . $e->getMessage()], 500);
        }

        PageCache::clearAll();
        json_response(['success' => true, 'order' => $persistedOrder]);
    }

    /**
     * Adiciona módulo
     */
    public function storeModule(string $funnelId, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            json_response(['success' => false, 'error' => 'Título é obrigatório'], 400);
        }

        $id = Module::create([
            'product_id' => (int) $productId,
            'title' => $_POST['title'],
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true, 'id' => $id]);
    }

    /**
     * Adiciona módulo a um produto global.
     */
    public function storeGlobalModule(string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            json_response(['success' => false, 'error' => 'Título é obrigatório'], 400);
        }

        $product = Product::find((int) $productId);
        if (!$product) {
            json_response(['success' => false, 'error' => 'Produto não encontrado'], 404);
        }

        $id = Module::create([
            'product_id' => (int) $productId,
            'title' => $_POST['title'],
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true, 'id' => $id]);
    }

    /**
     * Atualiza módulo
     */
    public function updateModule(string $moduleId): void
    {
        Auth::require();
        Security::requireCsrf();

        Module::update((int) $moduleId, [
            'title' => $_POST['title'],
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    /**
     * Deleta módulo
     */
    public function destroyModule(string $moduleId): void
    {
        Auth::require();
        Security::requireCsrf();

        Module::deleteWithLessons((int) $moduleId);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    // === AULAS ===

    /**
     * Adiciona aula
     */
    public function storeLesson(string $moduleId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            json_response(['success' => false, 'error' => 'Título é obrigatório'], 400);
        }

        $data = [
            'module_id' => (int) $moduleId,
            'title' => $_POST['title'],
            'youtube_id' => !empty($_POST['youtube_id']) ? Lesson::extractYoutubeId($_POST['youtube_id']) : null,
            'description' => $_POST['description'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ];

        $id = Lesson::create($data);

        PageCache::clearAll();
        json_response(['success' => true, 'id' => $id]);
    }

    /**
     * Atualiza aula
     */
    public function updateLesson(string $lessonId): void
    {
        Auth::require();
        Security::requireCsrf();

        $lesson = Lesson::find((int) $lessonId);

        Lesson::update((int) $lessonId, [
            'title' => $_POST['title'],
            'description' => $_POST['description'] ?? '',
            'youtube_id' => !empty($_POST['youtube_id']) ? Lesson::extractYoutubeId($_POST['youtube_id']) : null,
            'file' => $_POST['file'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    /**
     * Upload de arquivo para aula
     */
    public function uploadLessonFile(string $lessonId): void
    {
        Auth::require();
        Security::requireCsrf();

        $lesson = Lesson::find((int) $lessonId);
        if (!$lesson) {
            json_response(['success' => false, 'error' => 'Aula não encontrada'], 404);
        }

        if (empty($_FILES['file']['name'])) {
            json_response(['success' => false, 'error' => 'Nenhum arquivo enviado'], 400);
        }

        $file = $_FILES['file'];
        $allowed = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            json_response(['success' => false, 'error' => 'Formato não permitido'], 400);
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = uniqid('lesson_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;
        
        // Debug Log
        error_log(date('[Y-m-d H:i:s] ') . "ProductController: Starting Lesson File upload: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $finalPath)) {
            if (!file_exists($finalPath)) {
                error_log(date('[Y-m-d H:i:s] ') . "ProductController: Lesson File moved but NOT FOUND!", 3, ABSPATH . '/debug_upload_log.txt');
                json_response(['success' => false, 'error' => 'Erro: Arquivo não persistiu no disco.'], 500);
            }
            error_log(date('[Y-m-d H:i:s] ') . "ProductController: Lesson File success.", 3, ABSPATH . '/debug_upload_log.txt');
            $filePath = 'assets/uploads/' . $filename;
            Lesson::update((int) $lessonId, ['file' => $filePath]);
            PageCache::clearAll();
            json_response(['success' => true, 'file' => $filePath]);
        }

        json_response(['success' => false, 'error' => 'Erro ao salvar arquivo'], 500);
    }

    /**
     * Deleta aula
     */
    public function destroyLesson(string $lessonId): void
    {
        Auth::require();
        Security::requireCsrf();

        $lesson = Lesson::find((int) $lessonId);

        Lesson::delete((int) $lessonId);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    // === ARQUIVOS DO PRODUTO ===

    /**
     * Adiciona arquivo ao produto (upload ou link externo)
     */
    public function storeProductFile(string $funnelId, string $productId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            json_response(['success' => false, 'error' => 'Nome do arquivo é obrigatório'], 400);
        }

        $fileUrl = trim($_POST['file_url'] ?? '');

        // Modo link externo
        if (!empty($fileUrl)) {
            if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                json_response(['success' => false, 'error' => 'URL inválida'], 400);
            }

            $data = [
                'product_id' => (int) $productId,
                'title' => $_POST['title'],
                'file' => $fileUrl,
                'file_type' => 'link',
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
            ];

            if (ProductFile::supportsOpenInNewTab()) {
                $data['open_in_new_tab'] = !empty($_POST['open_in_new_tab']) ? 1 : 0;
            }

            $id = ProductFile::create($data);

            PageCache::clearAll();
            json_response(['success' => true, 'id' => $id, 'file' => $fileUrl]);
            return;
        }

        // Modo upload
        if (empty($_FILES['file']['name'])) {
            json_response(['success' => false, 'error' => 'Envie um arquivo ou informe um link externo'], 400);
        }

        $file = $_FILES['file'];
        $allowed = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'mp3', 'mp4'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            json_response(['success' => false, 'error' => 'Formato não permitido. Formatos aceitos: ' . implode(', ', $allowed)], 400);
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = uniqid('pfile_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
            json_response(['success' => false, 'error' => 'Erro ao salvar arquivo'], 500);
        }

        $filePath = 'assets/uploads/' . $filename;

        $id = ProductFile::create([
            'product_id' => (int) $productId,
            'title' => $_POST['title'],
            'file' => $filePath,
            'file_type' => 'upload',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true, 'id' => $id, 'file' => $filePath]);
    }

    /**
     * Adiciona arquivo ao produto global (upload ou link externo).
     */
    public function storeGlobalProductFile(string $productId): void
    {
        $this->storeProductFile('0', $productId);
    }

    /**
     * Atualiza arquivo do produto
     */
    public function updateProductFile(string $fileId): void
    {
        Auth::require();
        Security::requireCsrf();

        $data = [
            'title' => $_POST['title'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ];

        if (ProductFile::supportsOpenInNewTab() && array_key_exists('open_in_new_tab', $_POST)) {
            $data['open_in_new_tab'] = !empty($_POST['open_in_new_tab']) ? 1 : 0;
        }

        ProductFile::update((int) $fileId, $data);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    /**
     * Deleta arquivo do produto
     */
    public function destroyProductFile(string $fileId): void
    {
        Auth::require();
        Security::requireCsrf();

        ProductFile::delete((int) $fileId);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    // === ARQUIVOS DA AULA ===

    /**
     * Adiciona arquivo à aula (upload ou link externo)
     */
    public function storeLessonFile(string $lessonId): void
    {
        Auth::require();
        Security::requireCsrf();

        if (empty($_POST['title'])) {
            json_response(['success' => false, 'error' => 'Nome do arquivo é obrigatório'], 400);
        }

        $fileUrl = trim($_POST['file_url'] ?? '');

        // Modo link externo
        if (!empty($fileUrl)) {
            if (!filter_var($fileUrl, FILTER_VALIDATE_URL)) {
                json_response(['success' => false, 'error' => 'URL inválida'], 400);
            }

            $id = LessonFile::create([
                'lesson_id' => (int) $lessonId,
                'title' => $_POST['title'],
                'file' => $fileUrl,
                'file_type' => 'link',
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
            ]);

            PageCache::clearAll();
            json_response(['success' => true, 'id' => $id, 'file' => $fileUrl]);
            return;
        }

        // Modo upload
        if (empty($_FILES['file']['name'])) {
            json_response(['success' => false, 'error' => 'Envie um arquivo ou informe um link externo'], 400);
        }

        $file = $_FILES['file'];
        $allowed = ['pdf', 'zip', 'rar', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'mp3', 'mp4'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            json_response(['success' => false, 'error' => 'Formato não permitido'], 400);
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $filename = uniqid('lfile_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $finalPath)) {
            json_response(['success' => false, 'error' => 'Erro ao salvar arquivo'], 500);
        }

        $filePath = 'assets/uploads/' . $filename;

        $id = LessonFile::create([
            'lesson_id' => (int) $lessonId,
            'title' => $_POST['title'],
            'file' => $filePath,
            'file_type' => 'upload',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ]);

        PageCache::clearAll();
        json_response(['success' => true, 'id' => $id, 'file' => $filePath]);
    }

    /**
     * Atualiza arquivo da aula
     */
    public function updateLessonFile(string $fileId): void
    {
        Auth::require();
        Security::requireCsrf();

        $data = [
            'title' => $_POST['title'] ?? '',
            'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            'release_days' => !empty($_POST['release_days']) ? (int) $_POST['release_days'] : null
        ];

        LessonFile::update((int) $fileId, $data);

        PageCache::clearAll();
        json_response(['success' => true]);
    }

    /**
     * Deleta arquivo da aula
     */
    public function destroyLessonFile(string $fileId): void
    {
        Auth::require();
        Security::requireCsrf();

        LessonFile::delete((int) $fileId);

        PageCache::clearAll();
        json_response(['success' => true]);
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

    // === UPLOADS ===

    /**
     * Processa upload de imagem
     */
    private function handleImageUpload(): ?string
    {
        if (empty($_FILES['image']['name'])) {
            return null;
        }

        $file = $_FILES['image'];
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            flash('error', 'Formato de imagem inválido.');
            return null;
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('img_') . '.' . $ext;
        $finalPath = $uploadDir . $filename;

        // Debug Log
        error_log(date('[Y-m-d H:i:s] ') . "ProductController: Starting Image upload: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Target: " . $finalPath, 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $finalPath)) {
            if (!file_exists($finalPath)) {
                error_log(date('[Y-m-d H:i:s] ') . "ProductController: Image moved but NOT FOUND!", 3, ABSPATH . '/debug_upload_log.txt');
                return null;
            }
            error_log(date('[Y-m-d H:i:s] ') . "ProductController: Image success. Size: " . filesize($finalPath), 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        } else {
            error_log(date('[Y-m-d H:i:s] ') . "ProductController: move_uploaded_file failed for Image.", 3, ABSPATH . '/debug_upload_log.txt');
        }

        return null;
    }

    /**
     * Processa upload de PDF
     */
    private function handlePdfUpload(): ?string
    {
        if (empty($_FILES['file']['name'])) {
            return null;
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($ext !== 'pdf') {
            flash('error', 'Apenas arquivos PDF são permitidos.');
            return null;
        }

        $uploadDir = ABSPATH . '/public/assets/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = uniqid('pdf_') . '.pdf';
        $filepath = $uploadDir . $filename;

        // Debug Log
        error_log(date('[Y-m-d H:i:s] ') . "ProductController: Starting PDF upload: " . $file['name'], 3, ABSPATH . '/debug_upload_log.txt');
        error_log(date('[Y-m-d H:i:s] ') . "Target: " . $filepath, 3, ABSPATH . '/debug_upload_log.txt');

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            if (!file_exists($filepath)) {
                error_log(date('[Y-m-d H:i:s] ') . "ProductController: PDF moved but NOT FOUND!", 3, ABSPATH . '/debug_upload_log.txt');
                return null;
            }
            error_log(date('[Y-m-d H:i:s] ') . "ProductController: PDF success. Size: " . filesize($filepath), 3, ABSPATH . '/debug_upload_log.txt');
            return 'assets/uploads/' . $filename;
        } else {
             error_log(date('[Y-m-d H:i:s] ') . "ProductController: move_uploaded_file failed for PDF.", 3, ABSPATH . '/debug_upload_log.txt');
        }

        return null;
    }
}
