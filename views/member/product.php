<?php
$title = $product['title'];
$appName = $appName ?? '';
ob_start();
?>

<div class="breadcrumb">
    <a href="<?= url('/m/' . $slug . '/dashboard') ?>"><?= icon('arrow-left', 'width:16px;height:16px;') ?></a>
    <a href="<?= url('/m/' . $slug . '/dashboard') ?>">Dashboard</a>
    <span>/</span>
    <span><?= e($product['title']) ?></span>
</div>

<?php if (!empty($product['image'])): ?>
<div style="border-radius:16px; overflow:hidden; margin-bottom:24px; aspect-ratio:4/3;">
    <img src="<?= url($product['image']) ?>" alt="<?= e($product['title']) ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
</div>
<?php endif; ?>

<h1 class="page-title"><?= e($product['title']) ?></h1>
<p class="page-subtitle"><?= e($product['description']) ?></p>

<?php if ($product['type'] === 'pdf'): ?>
<!-- ARQUIVOS PARA DOWNLOAD -->
<div style="margin-bottom:24px;">
    <h2 style="font-size:1.125rem; font-weight:600; color:var(--gray-900); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
        <?= icon('files', 'width:20px;height:20px;color:var(--brand-500);') ?>
        Arquivos para Download
    </h2>

    <?php if (!empty($productFiles)): ?>
    <div style="display:flex; flex-direction:column; gap:12px;">
        <?php foreach ($productFiles as $file): ?>
        <?php
            $isLink = ($file['file_type'] ?? 'upload') === 'link';
            $linkTarget = !empty($file['open_in_new_tab']) ? '_blank' : '_self';
            $linkRel = $linkTarget === '_blank' ? ' rel="noopener"' : '';
        ?>
        <div style="background:var(--surface); border:1px solid var(--gray-200); border-radius:12px; padding:20px; display:flex; align-items:center; gap:16px; transition:all 0.2s; <?= !empty($file['release_date']) ? 'opacity:0.6;' : '' ?>">
            <div style="width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; <?= !empty($file['release_date']) ? 'background:var(--gray-100); color:var(--gray-400);' : ($isLink ? 'background:var(--brand-50); color:var(--brand-500);' : 'background:var(--brand-50); color:var(--brand-500);') ?>">
                <?php if (!empty($file['release_date'])): ?>
                    <?= icon('lock', 'width:20px;height:20px;') ?>
                <?php elseif ($isLink): ?>
                    <?= icon('external-link', 'width:20px;height:20px;') ?>
                <?php else: ?>
                    <?= icon('file-down', 'width:20px;height:20px;') ?>
                <?php endif; ?>
            </div>
            <div style="flex:1; min-width:0;">
                <p style="font-weight:600; color:var(--gray-800); font-size:0.9375rem;"><?= e($file['title']) ?></p>
                <?php if (!empty($file['release_date'])): ?>
                <p style="font-size:0.8125rem; color:#d97706; display:flex; align-items:center; gap:4px; margin-top:4px;">
                    <?= icon('clock', 'width:14px;height:14px;') ?>
                    Libera em <?= $file['release_date'] ?>
                </p>
                <?php elseif ($isLink): ?>
                <p style="font-size:0.8125rem; color:var(--gray-400);"><?= icon('link', 'width:12px;height:12px;display:inline;') ?> Link externo</p>
                <?php else: ?>
                <p style="font-size:0.8125rem; color:var(--gray-400);"><?= strtoupper(pathinfo($file['file'], PATHINFO_EXTENSION)) ?></p>
                <?php endif; ?>
            </div>
            <?php if (empty($file['release_date'])): ?>
            <?php if ($isLink): ?>
            <a href="<?= e($file['file']) ?>" target="<?= e($linkTarget) ?>"<?= $linkRel ?>
               class="btn btn-primary" style="width:auto; padding:10px 24px;">
                <?= icon('external-link', 'width:16px;height:16px;') ?>
                Acessar
            </a>
            <?php else: ?>
            <a href="<?= url($file['file']) ?>" target="_blank" download
               class="btn btn-primary" style="width:auto; padding:10px 24px;">
                <?= icon('download', 'width:16px;height:16px;') ?>
                Baixar
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <!-- Fallback: arquivo único antigo -->
    <?php if (!empty($product['file'])): ?>
    <div class="pdf-container">
        <a href="<?= url($product['file']) ?>" target="_blank" download class="btn btn-primary" style="width:auto; display:inline-flex;">
            <?= icon('download', 'width:16px;height:16px;') ?>
            Baixar Arquivo
        </a>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($product['type'] === 'video' && !empty($modules)): ?>
<!-- CURSO -->
<?php if ($firstLesson): ?>
<div style="margin-bottom:24px; text-align:center;">
    <a href="<?= url('/m/' . $slug . '/product/' . $product['id'] . '/lesson/' . $firstLesson['id']) ?>"
       class="btn btn-primary" style="width:auto; display:inline-flex; padding:14px 32px; font-size:1rem;">
        <?= icon('play', 'width:18px;height:18px;') ?>
        Começar Curso
    </a>
</div>
<?php endif; ?>

<div style="display:flex; flex-direction:column; gap:0;">
    <?php foreach ($modules as $mi => $module): ?>
    <div class="module-accordion <?= $mi === 0 ? 'open' : '' ?>" style="<?= !empty($module['release_date']) ? 'opacity:0.6;' : '' ?>">
        <div class="module-header" onclick="this.parentElement.classList.toggle('open')">
            <h3>
                <?php if (!empty($module['release_date'])): ?>
                    <?= icon('lock', 'width:16px;height:16px;color:var(--gray-400);') ?>
                <?php else: ?>
                    <span style="display:inline-flex; align-items:center; justify-content:center; width:24px; height:24px; border-radius:50%; background:var(--brand-50); color:var(--brand-500); font-size:0.75rem; font-weight:700;"><?= $mi + 1 ?></span>
                <?php endif; ?>
                <?= e($module['title']) ?>
                <?php if (!empty($module['release_date'])): ?>
                    <span style="font-size:0.75rem; color:#d97706; font-weight:400; margin-left:8px;">
                        <?= icon('clock', 'width:12px;height:12px;display:inline;') ?> Libera em <?= $module['release_date'] ?>
                    </span>
                <?php else: ?>
                    <span class="module-count"><?= count($module['lessons']) ?> aula<?= count($module['lessons']) !== 1 ? 's' : '' ?></span>
                <?php endif; ?>
            </h3>
            <?= icon('chevron-down', 'width:18px;height:18px;color:var(--gray-400);transition:transform 0.2s;') ?>
        </div>
        <?php if (empty($module['release_date'])): ?>
        <div class="module-body">
            <?php foreach ($module['lessons'] as $li => $lesson): ?>
            <a href="<?= empty($lesson['release_date']) ? url('/m/' . $slug . '/product/' . $product['id'] . '/lesson/' . $lesson['id']) : 'javascript:void(0)' ?>"
               class="lesson-link" style="<?= !empty($lesson['release_date']) ? 'opacity:0.5; cursor:not-allowed;' : '' ?>">
                <span class="lesson-icon">
                    <?php if (!empty($lesson['release_date'])): ?>
                        <?= icon('lock', 'width:16px;height:16px;') ?>
                    <?php elseif (empty($lesson['youtube_id'])): ?>
                        <?= icon('file-text', 'width:16px;height:16px;') ?>
                    <?php else: ?>
                        <?= icon('play-circle', 'width:16px;height:16px;') ?>
                    <?php endif; ?>
                </span>
                <span class="lesson-title" style="flex:1;"><?= e($lesson['title']) ?></span>
                <?php if (!empty($lesson['release_date'])): ?>
                <span style="font-size:0.75rem; color:#d97706;">
                    <?= icon('clock', 'width:12px;height:12px;display:inline;') ?> <?= $lesson['release_date'] ?>
                </span>
                <?php elseif (!empty($lesson['file'])): ?>
                <span style="color:var(--gray-400);"><?= icon('paperclip', 'width:14px;height:14px;') ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/member.php';
?>
