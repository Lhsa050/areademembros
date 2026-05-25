<?php
$title = $lesson['title'];
$appName = $appName ?? '';
ob_start();
?>

<div class="breadcrumb">
    <a href="<?= url('/m/' . $slug . '/product/' . $product['id']) ?>"><?= icon('arrow-left', 'width:16px;height:16px;') ?></a>
    <a href="<?= url('/m/' . $slug . '/dashboard') ?>">Dashboard</a>
    <span>/</span>
    <a href="<?= url('/m/' . $slug . '/product/' . $product['id']) ?>"><?= e($product['title']) ?></a>
    <span>/</span>
    <span><?= e($lesson['title']) ?></span>
</div>

<div class="lesson-layout">
    <!-- Main content -->
    <div class="lesson-content">
        <?php if (!empty($lesson['youtube_id'])): ?>
        <!-- Video Player -->
        <?php
        $useCustomPlayer = is_plyr_enabled((int) ($funnel['id'] ?? 0));
        if ($useCustomPlayer):
        ?>
        <!-- Plyr.js - Player personalizado com branding do YouTube escondido -->
        <div class="video-container">
            <div class="plyr__video-embed lgmPlayer">
                <iframe 
                    src="https://www.youtube.com/embed/<?= e($lesson['youtube_id']) ?>?iv_load_policy=3&modestbranding=1&playsinline=1&showinfo=0&rel=0&enablejsapi=1"
                    allowfullscreen
                    allowtransparency
                    allow="autoplay"
                    referrerpolicy="strict-origin-when-cross-origin"
                ></iframe>
            </div>
        </div>
        <?php else: ?>
        <!-- Player padrão do YouTube -->
        <div class="tutor-video-player">
            <div class="tutor-ratio tutor-ratio-16x9">
                <iframe src="https://www.youtube.com/embed/<?= e($lesson['youtube_id']) ?>" title="<?= e($lesson['title']) ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
            </div>
        </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- Lesson without video -->
        <div style="background:linear-gradient(135deg, var(--brand-50), var(--gray-100)); border:1px solid var(--gray-200); border-radius:16px; padding:32px; margin-bottom:24px; display:flex; align-items:center; gap:16px;">
            <div style="width:56px; height:56px; border-radius:12px; background:var(--brand-50); border:1px solid var(--brand-100); display:flex; align-items:center; justify-content:center; flex-shrink:0; color:var(--brand-500);">
                <?= icon('file-text', 'width:28px;height:28px;') ?>
            </div>
            <div>
                <h1 style="font-size:1.5rem; font-weight:700; color:var(--gray-900); margin-bottom:4px;"><?= e($lesson['title']) ?></h1>
                <p style="font-size:0.875rem; color:var(--gray-500);">Material de apoio</p>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($lesson['youtube_id'])): ?>
        <h1 style="font-size:1.5rem; font-weight:700; color:var(--gray-900); margin-bottom:12px;"><?= e($lesson['title']) ?></h1>
        <?php endif; ?>

        <?php if (!empty($lesson['description'])): ?>
        <div style="background:var(--surface); border:1px solid var(--gray-200); border-radius:12px; padding:20px; margin-bottom:24px; color:var(--gray-700); line-height:1.8; font-size:0.9375rem;">
            <?= nl2br(e($lesson['description'])) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($lessonFiles)): ?>
        <div style="margin-bottom:24px;">
            <h3 style="font-size:0.9375rem; font-weight:600; color:var(--gray-800); margin-bottom:12px; display:flex; align-items:center; gap:8px;">
                <?= icon('files', 'width:16px;height:16px;color:var(--brand-500);') ?>
                Arquivos da Aula
            </h3>
            <?php foreach ($lessonFiles as $lf): ?>
            <?php $lfIsLink = ($lf['file_type'] ?? 'upload') === 'link'; ?>
            <div style="background:var(--surface); border:1px solid var(--gray-200); border-radius:10px; padding:14px 16px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between; <?= !empty($lf['release_date']) ? 'opacity:0.5;' : '' ?>">
                <div style="display:flex; align-items:center; gap:10px;">
                    <div style="width:36px; height:36px; border-radius:8px; <?= !empty($lf['release_date']) ? 'background:var(--gray-100); color:var(--gray-400);' : ($lfIsLink ? 'background:#eef2ff; color:#6366f1;' : 'background:#f0fdf4; color:#16a34a;') ?> display:flex; align-items:center; justify-content:center;">
                        <?php if (!empty($lf['release_date'])): ?>
                            <?= icon('lock', 'width:16px;height:16px;') ?>
                        <?php elseif ($lfIsLink): ?>
                            <?= icon('external-link', 'width:16px;height:16px;') ?>
                        <?php else: ?>
                            <?= icon('file-down', 'width:16px;height:16px;') ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <p style="font-weight:600; color:var(--gray-800); font-size:0.875rem;"><?= e($lf['title']) ?></p>
                        <?php if (!empty($lf['release_date'])): ?>
                        <p style="font-size:0.75rem; color:#d97706;"><?= icon('clock', 'width:12px;height:12px;display:inline;') ?> Libera em <?= $lf['release_date'] ?></p>
                        <?php elseif ($lfIsLink): ?>
                        <p style="font-size:0.75rem; color:var(--gray-400);"><?= icon('link', 'width:10px;height:10px;display:inline;') ?> Link externo</p>
                        <?php else: ?>
                        <p style="font-size:0.75rem; color:var(--gray-400);"><?= strtoupper(pathinfo($lf['file'], PATHINFO_EXTENSION)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (empty($lf['release_date'])): ?>
                <?php if ($lfIsLink): ?>
                <a href="<?= e($lf['file']) ?>" target="_blank"
                   class="btn btn-primary" style="width:auto; padding:8px 16px; font-size:0.8125rem; background:linear-gradient(135deg, #6366f1, #4f46e5);">
                    <?= icon('external-link', 'width:14px;height:14px;') ?>
                    Acessar
                </a>
                <?php else: ?>
                <a href="<?= url($lf['file']) ?>" target="_blank" download
                   class="btn btn-primary" style="width:auto; padding:8px 16px; font-size:0.8125rem; background:linear-gradient(135deg, #16a34a, #15803d);">
                    <?= icon('download', 'width:14px;height:14px;') ?>
                    Baixar
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif (!empty($lesson['file'])): ?>
        <!-- Fallback: arquivo único antigo -->
        <div style="background:var(--surface); border:1px solid var(--gray-200); border-radius:12px; padding:20px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:40px; height:40px; border-radius:10px; background:#f0fdf4; display:flex; align-items:center; justify-content:center; color:#16a34a;">
                    <?= icon('file-down', 'width:20px;height:20px;') ?>
                </div>
                <div>
                    <p style="font-weight:600; color:var(--gray-800); font-size:0.9375rem;">Material da Aula</p>
                    <p style="font-size:0.8125rem; color:var(--gray-400);"><?= strtoupper(pathinfo($lesson['file'], PATHINFO_EXTENSION)) ?></p>
                </div>
            </div>
            <a href="<?= url($lesson['file']) ?>" target="_blank" download
               class="btn btn-primary" style="width:auto; padding:10px 20px; background:linear-gradient(135deg, #16a34a, #15803d);">
                <?= icon('download', 'width:16px;height:16px;') ?>
                Baixar
            </a>
        </div>
        <?php endif; ?>

        <!-- Prev/Next Navigation -->
        <div class="lesson-nav">
            <?php if ($prevLesson): ?>
            <a href="<?= url('/m/' . $slug . '/product/' . $product['id'] . '/lesson/' . $prevLesson['id']) ?>"
               class="btn btn-secondary" style="width:auto;">
                <?= icon('chevron-left', 'width:16px;height:16px;') ?>
                Anterior
            </a>
            <?php else: ?>
            <div></div>
            <?php endif; ?>

            <?php if ($nextLesson): ?>
                <?php if (!empty($nextLesson['release_date'])): ?>
                <span class="btn btn-secondary" style="width:auto; opacity:0.6; cursor:not-allowed;">
                    <?= icon('lock', 'width:16px;height:16px;') ?>
                    Libera em <?= $nextLesson['release_date'] ?>
                </span>
                <?php else: ?>
                <a href="<?= url('/m/' . $slug . '/product/' . $product['id'] . '/lesson/' . $nextLesson['id']) ?>"
                   class="btn btn-primary" style="width:auto;">
                    Próxima
                    <?= icon('chevron-right', 'width:16px;height:16px;') ?>
                </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sidebar -->
    <div class="lesson-sidebar">
        <?php foreach ($modules as $mi => $module): ?>
        <div class="module-accordion <?= $mi === 0 ? 'open' : '' ?>" style="<?= !empty($module['release_date']) ? 'opacity:0.6;' : '' ?>">
            <div class="module-header" onclick="this.parentElement.classList.toggle('open')">
                <h3>
                    <?php if (!empty($module['release_date'])): ?>
                        <?= icon('lock', 'width:14px;height:14px;color:var(--gray-400);') ?>
                    <?php else: ?>
                        <span style="font-size:0.75rem; font-weight:700; color:var(--gray-500);"><?= $mi + 1 ?></span>
                    <?php endif; ?>
                    <?= e($module['title']) ?>
                    <?php if (!empty($module['release_date'])): ?>
                        <span style="font-size:0.6875rem; color:#d97706; font-weight:400;"><?= $module['release_date'] ?></span>
                    <?php endif; ?>
                </h3>
                <?= icon('chevron-down', 'width:16px;height:16px;color:var(--gray-400);') ?>
            </div>
            <?php if (empty($module['release_date'])): ?>
            <div class="module-body">
                <?php foreach ($module['lessons'] as $l): ?>
                <a href="<?= empty($l['release_date']) ? url('/m/' . $slug . '/product/' . $product['id'] . '/lesson/' . $l['id']) : 'javascript:void(0)' ?>"
                   class="lesson-link <?= $l['id'] == $lesson['id'] ? 'active' : '' ?>"
                   style="<?= !empty($l['release_date']) ? 'opacity:0.5; cursor:not-allowed;' : '' ?>">
                    <span class="lesson-icon">
                        <?php if (!empty($l['release_date'])): ?>
                            <?= icon('lock', 'width:14px;height:14px;') ?>
                        <?php elseif ($l['id'] == $lesson['id']): ?>
                            <?= icon('play', 'width:14px;height:14px;') ?>
                        <?php elseif (empty($l['youtube_id'])): ?>
                            <?= icon('file-text', 'width:14px;height:14px;') ?>
                        <?php else: ?>
                            <?= icon('play-circle', 'width:14px;height:14px;') ?>
                        <?php endif; ?>
                    </span>
                    <span class="lesson-title"><?= e($l['title']) ?></span>
                    <?php if (!empty($l['release_date'])): ?>
                    <span style="font-size:0.6875rem; color:#d97706; margin-left:auto;"><?= $l['release_date'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php if (!empty($lesson['youtube_id']) && $useCustomPlayer): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.lgmPlayer').forEach(function(el) {
            var player = new Plyr(el, {
                youtube: { noCookie: false, rel: 0, showinfo: 0, iv_load_policy: 3, modestbranding: 1 },
                controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'pip', 'fullscreen']
            });

            player.on('play', function() {
                var poster = document.querySelector('.plyr--youtube.plyr__poster-enabled .plyr__poster');
                if (poster) poster.style.opacity = '0';
            });

            player.on('pause', function() {
                var poster = document.querySelector('.plyr--youtube.plyr__poster-enabled .plyr__poster');
                if (poster) poster.style.opacity = '1';
            });

            player.on('ended', function() {
                var poster = document.querySelector('.plyr--youtube.plyr__poster-enabled .plyr__poster');
                if (poster) poster.style.opacity = '1';
            });
        });
    });
</script>
<?php endif; ?>


<?php
$content = ob_get_clean();
include ABSPATH . '/views/layouts/member.php';
?>
