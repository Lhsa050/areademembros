<?php
/**
 * Partial: Card de produto (usado no dashboard)
 * Espera: $product, $slug
 */
$unlocked = $product['unlocked'];
$isDripLocked = !$unlocked && !empty($product['release_date']);
$hasDirectAccess = $unlocked && !empty($product['direct_access']) && !empty($product['direct_access_url']);
$hideTypeTag = $hasDirectAccess && !empty($product['direct_access_is_link']);
$checkoutUrl = tracked_checkout_url($product['checkout_url'] ?? '');
$href = $hasDirectAccess ? $product['direct_access_url'] : ($unlocked ? url('/m/' . $slug . '/product/' . $product['id']) : ($isDripLocked ? '#' : ($checkoutUrl ?: '#')));
$target = $hasDirectAccess ? ($product['direct_access_target'] ?? '_self') : ((!$unlocked && !$isDripLocked && $checkoutUrl !== '') ? '_blank' : '_self');
$relAttr = $target === '_blank' ? ' rel="noopener"' : '';
$downloadAttr = $hasDirectAccess && !empty($product['direct_access_download']) ? ' download' : '';
$accessIcon = $hasDirectAccess ? ($product['direct_access_icon'] ?? 'download') : 'play';
$accessLabel = $hasDirectAccess ? ($product['direct_access_label'] ?? __('access')) : __('access');
?>
<a href="<?= e($href) ?>" target="<?= e($target) ?>"<?= $relAttr ?><?= $downloadAttr ?> class="product-card <?= $unlocked ? 'unlocked' : 'locked' ?>" style="text-decoration:none; display:block; cursor: pointer;">
    <div class="card-image">
        <?php if (!empty($product['image'])): ?>
            <img src="<?= url($product['image']) ?>" alt="<?= e($product['title']) ?>">
        <?php else: ?>
            <div class="placeholder-icon">
                <?= icon($product['type'] === 'video' ? 'play-circle' : 'file-text', 'width:48px;height:48px;') ?>
            </div>
        <?php endif; ?>
        <?php if (!$unlocked): ?>
            <div class="lock-icon" style="top:12px; right:12px; left:auto; transform:none; width:32px; height:32px;">
                <?= icon('lock', 'width:16px;height:16px;') ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!$hideTypeTag): ?>
            <span class="card-type <?= $product['type'] === 'video' ? 'type-video' : 'type-pdf' ?>">
                <?= icon($product['type'] === 'video' ? 'play-circle' : 'file-text', 'width:12px;height:12px;') ?>
                <?= $product['type'] === 'video' ? __('video_course') : __('pdf_material') ?>
            </span>
        <?php endif; ?>
        <h3 class="card-title"><?= e($product['title']) ?></h3>
        <p class="card-desc"><?= e($product['description']) ?></p>
        
        <?php if ($unlocked): ?>
            <span class="btn btn-primary">
                <?= icon($accessIcon, 'width:16px;height:16px;') ?>
                <?= e($accessLabel) ?>
            </span>
        <?php elseif (!empty($product['release_date'])): ?>
            <span class="btn btn-secondary" style="opacity: 0.8; background: linear-gradient(135deg, #7c3aed20, #6d28d920); border: 1px solid #7c3aed40; color: #a78bfa;">
                <?= icon('clock', 'width:16px;height:16px;') ?>
                <?= __('releases_on') ?> <?= $product['release_date'] ?>
            </span>
        <?php else: ?>
            <?php if ($checkoutUrl !== ''): ?>
                <span class="btn btn-checkout">
                    <?= icon('shopping-cart', 'width:16px;height:16px;') ?>
                    <?= __('buy_access') ?>
                </span>
            <?php else: ?>
                <span class="btn btn-secondary" style="opacity: 0.7;">
                    <?= icon('lock', 'width:16px;height:16px;') ?>
                    <?= __('locked') ?>
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</a>
