<?php

namespace App\Controllers;

use App\Core\MemberAuth;
use App\Models\Product;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Funnel;
use App\Models\ProductFile;
use App\Models\LessonFile;
use App\Models\MemberProduct;
use App\Services\PageCache;

/**
 * Controller da Área do Membro — Ultra-leve
 * 
 * Otimizações:
 * - Acesso verificado na sessão (0 queries)
 * - Páginas de produto/aula cacheadas como HTML estático
 * - Funil resolvido da sessão quando possível
 */
class MemberAreaController
{
    private function applyDirectFileAccess(array &$product, array $file): void
    {
        $rawFile = trim((string) ($file['file'] ?? ''));
        if ($rawFile === '') {
            return;
        }

        $isLink = (($file['file_type'] ?? 'upload') === 'link') || filter_var($rawFile, FILTER_VALIDATE_URL);

        $product['direct_access'] = true;
        $product['direct_access_url'] = $isLink ? $rawFile : url($rawFile);
        $product['direct_access_target'] = ($isLink && !empty($file['open_in_new_tab'])) ? '_blank' : '_self';
        $product['direct_access_download'] = !$isLink;
        $product['direct_access_is_link'] = $isLink;
        $product['direct_access_label'] = $isLink ? __('open_link') : __('download');
        $product['direct_access_icon'] = $isLink ? 'external-link' : 'download';
    }

    private function logNonCritical(string $message, \Throwable $e): void
    {
        error_log('[MemberAreaController] ' . $message . ': ' . $e->getMessage());
    }

    /**
     * Resolve o funil pelo slug — usa sessão se possível
     */
    private function resolveFunnel(string $slug): array
    {
        // Se o membro está logado e o slug bate, usa da sessão
        if (MemberAuth::check() && MemberAuth::funnelSlug() === $slug) {
            $funnelId = MemberAuth::funnelId();
            // Dados mínimos do funil da sessão
            $memberData = MemberAuth::user();
            if ($memberData && $funnelId) {
                $funnel = Funnel::findBySlug($slug);
                if ($funnel) return $funnel;
            }
        }

        $funnel = Funnel::findBySlug($slug);
        if (!$funnel) {
            http_response_code(404);
            echo '<h1>Área de membros não encontrada</h1>';
            exit;
        }
        return $funnel;
    }

    /**
     * Calcula a data de liberação baseado em release_days + granted_at
     * Retorna null se não há restrição, ou a data de liberação se ainda está bloqueado
     */
    private function calculateReleaseDate(?int $releaseDays, ?string $grantedAt): ?string
    {
        if ($releaseDays === null || $releaseDays <= 0 || !$grantedAt) {
            return null;
        }
        
        try {
            $grantedDate = new \DateTime($grantedAt);
            $grantedDate->modify("+{$releaseDays} days");
            $now = new \DateTime();
        } catch (\Throwable $e) {
            $this->logNonCritical('release date parse failed', $e);
            return null;
        }

        if ($now >= $grantedDate) {
            return null; // Já liberado
        }
        
        return $grantedDate->format('d/m/Y');
    }

    /**
     * Dashboard — lista os produtos DO FUNIL
     * Queries: 1 (produtos do funil) — acesso vem da sessão
     */
    public function dashboard(string $slug): void
    {
        $funnel = $this->resolveFunnel($slug);
        MemberAuth::require($slug);

        $member = MemberAuth::user();
        $appName = $funnel['site_name'] ?: $funnel['name'];

        // 1 query: busca produtos do funil
        $allProducts = Product::getByFunnel($funnel['id']);

        // Acesso vem da sessão — 0 queries
        $memberProductIds = MemberAuth::productIds();

        foreach ($allProducts as &$product) {
            $isPublic = !empty($product['is_public']);
            $hasMemberAccess = $isPublic || in_array((int) $product['id'], $memberProductIds, true);
            $product['has_member_access'] = $hasMemberAccess;
            $product['unlocked'] = $hasMemberAccess;
            $product['direct_access'] = false;
            
            // Verifica release_days para produtos desbloqueados
            if (!$isPublic && $product['unlocked'] && !empty($product['release_days'])) {
                $grantedAt = MemberAuth::productGrantedAt($product['id']);
                $releaseDate = $this->calculateReleaseDate((int) $product['release_days'], $grantedAt);
                if ($releaseDate) {
                    $product['unlocked'] = false;
                    $product['release_date'] = $releaseDate;
                }
            }
        }
        unset($product);

        $unlockedFileProductIds = [];
        foreach ($allProducts as $product) {
            if (!empty($product['unlocked']) && ($product['type'] ?? '') === 'pdf') {
                $unlockedFileProductIds[] = (int) $product['id'];
            }
        }

        $filesByProduct = [];
        if (!empty($unlockedFileProductIds)) {
            try {
                $filesByProduct = ProductFile::getByProducts($unlockedFileProductIds);
            } catch (\Throwable $e) {
                $this->logNonCritical('product files lookup failed', $e);
            }
        }

        foreach ($allProducts as &$product) {
            if (empty($product['unlocked']) || ($product['type'] ?? '') !== 'pdf') {
                continue;
            }

            $grantedAt = !empty($product['is_public']) ? null : MemberAuth::productGrantedAt((int) $product['id']);
            $productFiles = $filesByProduct[(int) $product['id']] ?? [];

            if (count($productFiles) === 1) {
                $file = $productFiles[0];
                $releaseDate = $this->calculateReleaseDate(
                    !empty($file['release_days']) ? (int) $file['release_days'] : null,
                    $grantedAt
                );

                if (!$releaseDate && !empty($file['file'])) {
                    $this->applyDirectFileAccess($product, $file);
                }
            } elseif (empty($productFiles) && !empty($product['file'])) {
                $this->applyDirectFileAccess($product, [
                    'title' => $product['title'],
                    'file' => $product['file'],
                    'file_type' => 'upload',
                    'open_in_new_tab' => 0,
                ]);
            }
        }
        unset($product);

        view('member.dashboard', [
            'member' => $member,
            'products' => $allProducts,
            'appName' => $appName,
            'funnel' => $funnel,
            'slug' => $slug
        ]);
    }

    /**
     * Página do produto — com cache de HTML
     * Queries sem cache: 2 (produto + módulos/aulas)
     * Queries com cache: 0 (serve HTML estático!)
     */
    public function product(string $slug, string $id): void
    {
        $funnel = $this->resolveFunnel($slug);
        MemberAuth::require($slug);

        // Verifica acesso na sessão — 0 queries
        $product = Product::findForFunnel((int) $id, (int) $funnel['id']);
        if (!$product) {
            flash('error', 'Produto nÃ£o encontrado.');
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        if (!MemberAuth::hasProductAccess((int) $id)) {
            flash('error', 'Você não tem acesso a este produto.');
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        // Verifica release_days do produto
        $productIsPublic = !empty($product['is_public']);
        if (!$productIsPublic && !empty($product['release_days'])) {
            $grantedAt = MemberAuth::productGrantedAt((int) $id);
            $releaseDate = $this->calculateReleaseDate((int) $product['release_days'], $grantedAt);
            if ($releaseDate) {
                flash('error', "Este produto será liberado em {$releaseDate}.");
                redirect(url('/m/' . $slug . '/dashboard'));
            }
        }

        // Cache desabilitado para suportar release_days dinâmico
        // (podemos reabilitar com chave que inclua member_id)

        $member = MemberAuth::user();
        $appName = $funnel['site_name'] ?: $funnel['name'];
        $grantedAt = $productIsPublic ? null : MemberAuth::productGrantedAt((int) $id);

        // Carrega arquivos do produto
        $productFiles = ProductFile::getByProduct((int) $id);
        foreach ($productFiles as &$pf) {
            $pf['release_date'] = $this->calculateReleaseDate(
                !empty($pf['release_days']) ? (int) $pf['release_days'] : null,
                $grantedAt
            );
        }
        unset($pf);

        $modules = Module::getByProduct((int) $id);
        foreach ($modules as &$module) {
            // Verifica release_days do módulo
            $module['release_date'] = $this->calculateReleaseDate(
                !empty($module['release_days']) ? (int) $module['release_days'] : null,
                $grantedAt
            );
            
            $module['lessons'] = Lesson::getByModule($module['id']);
            foreach ($module['lessons'] as &$lesson) {
                $lesson['release_date'] = $this->calculateReleaseDate(
                    !empty($lesson['release_days']) ? (int) $lesson['release_days'] : null,
                    $grantedAt
                );
            }
            unset($lesson);
        }
        unset($module);

        $firstLesson = null;
        if (!empty($modules) && !empty($modules[0]['lessons'])) {
            // Encontra a primeira aula não bloqueada
            foreach ($modules as $m) {
                if (!empty($m['release_date'])) continue;
                foreach ($m['lessons'] as $l) {
                    if (empty($l['release_date'])) {
                        $firstLesson = $l;
                        break 2;
                    }
                }
            }
            // Se não achou nenhuma liberada, usa a primeira de qualquer forma
            if (!$firstLesson && !empty($modules[0]['lessons'])) {
                $firstLesson = $modules[0]['lessons'][0];
            }
        }

        view('member.product', [
            'member' => $member,
            'product' => $product,
            'modules' => $modules,
            'productFiles' => $productFiles,
            'firstLesson' => $firstLesson,
            'appName' => $appName,
            'funnel' => $funnel,
            'slug' => $slug
        ]);
    }

    /**
     * Aula individual — com cache de HTML
     * Queries sem cache: 3 (produto + aula + módulos)
     * Queries com cache: 0 (serve HTML estático!)
     */
    public function lesson(string $slug, string $productId, string $lessonId): void
    {
        $funnel = $this->resolveFunnel($slug);
        MemberAuth::require($slug);

        // Verifica acesso na sessão — 0 queries
        if (!MemberAuth::hasProductAccess((int) $productId)) {
            flash('error', 'Você não tem acesso a este produto.');
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        // Renderiza normalmente
        $product = Product::findForFunnel((int) $productId, (int) $funnel['id']);
        if (!$product) {
            flash('error', 'Produto não encontrado.');
            redirect(url('/m/' . $slug . '/dashboard'));
        }

        $lesson = Lesson::find((int) $lessonId);
        if (!$lesson) {
            flash('error', 'Aula não encontrada.');
            redirect(url('/m/' . $slug . '/product/' . $productId));
        }

        $lessonModule = Module::find((int) $lesson['module_id']);
        if (!$lessonModule || (int) ($lessonModule['product_id'] ?? 0) !== (int) $productId) {
            flash('error', 'Aula nao encontrada neste produto.');
            redirect(url('/m/' . $slug . '/product/' . $productId));
        }

        // Verifica release_days da aula
        $grantedAt = !empty($product['is_public']) ? null : MemberAuth::productGrantedAt((int) $productId);
        $lessonReleaseDate = $this->calculateReleaseDate(
            !empty($lesson['release_days']) ? (int) $lesson['release_days'] : null,
            $grantedAt
        );
        if ($lessonReleaseDate) {
            flash('error', "Esta aula será liberada em {$lessonReleaseDate}.");
            redirect(url('/m/' . $slug . '/product/' . $productId));
        }

        // Verifica release_days do módulo da aula
        // $lessonModule was validated above.
        if ($lessonModule) {
            $moduleReleaseDate = $this->calculateReleaseDate(
                !empty($lessonModule['release_days']) ? (int) $lessonModule['release_days'] : null,
                $grantedAt
            );
            if ($moduleReleaseDate) {
                flash('error', "Este módulo será liberado em {$moduleReleaseDate}.");
                redirect(url('/m/' . $slug . '/product/' . $productId));
            }
        }

        $member = MemberAuth::user();
        $appName = $funnel['site_name'] ?: $funnel['name'];

        $modules = Module::getByProduct((int) $productId);
        foreach ($modules as &$module) {
            $module['release_date'] = $this->calculateReleaseDate(
                !empty($module['release_days']) ? (int) $module['release_days'] : null,
                $grantedAt
            );
            $module['lessons'] = Lesson::getByModule($module['id']);
            foreach ($module['lessons'] as &$l) {
                $l['release_date'] = $this->calculateReleaseDate(
                    !empty($l['release_days']) ? (int) $l['release_days'] : null,
                    $grantedAt
                );
            }
            unset($l);
        }
        unset($module);

        // Navegação prev/next
        $allLessons = [];
        foreach ($modules as $module) {
            foreach ($module['lessons'] as $l) {
                $allLessons[] = $l;
            }
        }

        $currentIndex = null;
        foreach ($allLessons as $i => $l) {
            if ($l['id'] == $lessonId) {
                $currentIndex = $i;
                break;
            }
        }

        $prevLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson = ($currentIndex !== null && $currentIndex < count($allLessons) - 1) ? $allLessons[$currentIndex + 1] : null;

        // Carrega arquivos da aula
        $lessonFiles = LessonFile::getByLesson((int) $lessonId);
        foreach ($lessonFiles as &$lf) {
            $lf['release_date'] = $this->calculateReleaseDate(
                !empty($lf['release_days']) ? (int) $lf['release_days'] : null,
                $grantedAt
            );
        }
        unset($lf);

        view('member.lesson', [
            'member' => $member,
            'product' => $product,
            'lesson' => $lesson,
            'lessonFiles' => $lessonFiles,
            'modules' => $modules,
            'prevLesson' => $prevLesson,
            'nextLesson' => $nextLesson,
            'appName' => $appName,
            'funnel' => $funnel,
            'slug' => $slug
        ]);
    }

    /**
     * API: Retorna ofertas ativas do funil (para dashboard e popup upsell)
     */
    public function activeOffer(string $slug): void
    {
        $funnel = $this->resolveFunnel($slug);
        MemberAuth::require($slug);

        $activeOffers = \App\Models\Offer::getActiveByFunnel((int) $funnel['id']);

        if (empty($activeOffers)) {
            json_response(['offers' => []]);
        }
        
        // Pega IDs dos produtos que o membro já possui
        $memberProductIds = MemberAuth::productIds();

        $formattedOffers = [];
        
        foreach ($activeOffers as $offer) {
            $offerProductIds = \App\Models\Offer::getProductIds((int) $offer['id']);
            
            // Verifica se o membro já tem TODOS os produtos dessa oferta
            // Se a oferta não tem produtos vinculados, ou se o membro já tem todos, a gente oculta a oferta.
            $hasAllProducts = true;
            if (empty($offerProductIds)) {
                 $hasAllProducts = false; // Se a oferta não tem produtos configurados, mostra sempre (ou esconde, mas geralmente mostramos pra ele comprar)
            } else {
                foreach ($offerProductIds as $pid) {
                    if (!in_array($pid, $memberProductIds)) {
                        $hasAllProducts = false;
                        break;
                    }
                }
            }
            
            // Só adiciona a oferta se o usuário NÃO tiver todos os produtos dela
            if (!$hasAllProducts) {
                $formattedOffers[] = [
                    'id' => $offer['id'],
                    'title' => $offer['title'],
                    'description' => $offer['description'],
                    'image' => $offer['image'] ? url($offer['image']) : null,
                    'checkout_url' => tracked_checkout_url($offer['checkout_url'] ?? ''),
                    'show_as_popup' => !empty($offer['show_as_popup'])
                ];
            }
        }

        json_response([
            'offers' => $formattedOffers
        ]);
    }
}
