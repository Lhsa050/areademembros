<?php
/**
 * View: Dashboard do Membro (com suporte a traduções)
 */
$title = __('my_products');
ob_start();

$autoOrganize = !empty($funnel['auto_organize']);
if ($autoOrganize) {
    $myProducts = array_filter($products, fn($p) => $p['unlocked']);
    $otherProducts = array_filter($products, fn($p) => !$p['unlocked']);
}
?>

<div style="margin-bottom: 32px;">
    <h1 class="page-title"><?= __('hello') ?>, <?= e(explode(' ', $member['name'])[0]) ?>! 👋</h1>
    <p class="page-subtitle"><?= __('check_products') ?></p>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state">
        <?= icon('package', 'width:48px;height:48px;') ?>
        <h3 style="font-size:1.125rem;font-weight:600;color:var(--gray-600);margin-top:12px;"><?= __('no_products') ?></h3>
        <p style="font-size:0.875rem;color:var(--gray-400);margin-top:8px;"><?= __('no_products_desc') ?></p>
    </div>
<?php elseif ($autoOrganize): ?>
    <!-- Meus Produtos -->
    <?php if (!empty($myProducts)): ?>
    <div style="margin-bottom:40px;">
        <h2 style="font-size:1.125rem; font-weight:700; color:var(--gray-800); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <?= icon('check-circle', 'width:20px;height:20px;color:var(--brand-500);') ?>
            <?= __('my_products') ?>
        </h2>
        <div class="products-grid">
            <?php foreach ($myProducts as $product): ?>
                <?php include __DIR__ . '/_product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Produtos Recomendados -->
    <?php if (!empty($otherProducts)): ?>
    <div>
        <h2 style="font-size:1.125rem; font-weight:700; color:var(--gray-800); margin-bottom:16px; display:flex; align-items:center; gap:8px;">
            <?= icon('sparkles', 'width:20px;height:20px;color:#f59e0b;') ?>
            <?= __('recommended_products') ?>
        </h2>
        <div class="products-grid">
            <?php foreach ($otherProducts as $product): ?>
                <?php include __DIR__ . '/_product_card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
<?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
            <?php include __DIR__ . '/_product_card.php'; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- Offers Injection & Improved Popup -->
<div id="upsell-container"></div>

<!-- Improved Upsell Popup Template (Hidden by default) -->
<div id="upsell-overlay" class="upsell-overlay" aria-hidden="true">
    <div id="upsell-popup" class="upsell-popup" role="dialog" aria-modal="true" aria-labelledby="upsell-title">
        <button id="upsell-close" class="upsell-close" type="button" aria-label="Fechar oferta">
            <?= icon('x', 'width:20px;height:20px;') ?>
        </button>

        <div class="upsell-visual" aria-hidden="true">
            <div id="upsell-image" class="upsell-image">
                <img alt="">
            </div>
            <div class="upsell-hero-mark">
                <?= icon('sparkles', 'width:34px;height:34px;') ?>
            </div>
            <div class="upsell-badge">
                <?= icon('star', 'width:14px;height:14px;fill:currentColor;') ?>
                Oferta Especial
            </div>
        </div>

        <div class="upsell-panel">
            <div class="upsell-scroll">
                <span class="upsell-kicker">Oferta exclusiva para membros</span>
                <h3 id="upsell-title"></h3>
                <p id="upsell-desc"></p>
            </div>
            
            <div class="upsell-actions">
                <a id="upsell-cta" class="upsell-cta" href="#" target="_blank" rel="noopener">
                    <?= __('upgrade_now') ?? 'Garantir Oferta Agora' ?>
                    <?= icon('arrow-right', 'width:22px;height:22px;') ?>
                </a>
            
                <button id="upsell-skip" class="upsell-skip" type="button">
                    Nao quero aproveitar este desconto
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.upsell-overlay {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 22px;
    background: radial-gradient(circle at 20% 10%, rgba(255,255,255,0.18), transparent 26%), rgba(15, 23, 42, 0.82);
    backdrop-filter: blur(14px);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.28s ease;
    overscroll-behavior: contain;
}
.upsell-overlay.is-visible {
    opacity: 1;
    pointer-events: auto;
}
.upsell-popup {
    position: relative;
    width: min(100%, 620px);
    max-height: calc(100vh - 44px);
    max-height: calc(100dvh - 44px);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    background: var(--surface);
    color: var(--gray-900);
    border: 1px solid rgba(148, 163, 184, 0.28);
    border-radius: 18px;
    box-shadow: 0 28px 70px -22px rgba(0, 0, 0, 0.62);
    transform: translateY(18px) scale(0.97);
    transition: transform 0.34s cubic-bezier(0.16, 1, 0.3, 1);
}
.upsell-popup.is-open {
    transform: translateY(0) scale(1);
}
.upsell-close {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 3;
    width: 42px;
    height: 42px;
    border: 1px solid rgba(255,255,255,0.55);
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    background: rgba(15, 23, 42, 0.42);
    box-shadow: 0 10px 22px rgba(15, 23, 42, 0.18);
    backdrop-filter: blur(10px);
    cursor: pointer;
    transition: transform 0.2s ease, background 0.2s ease;
}
.upsell-close:hover {
    background: rgba(15, 23, 42, 0.62);
    transform: scale(1.06);
}
.upsell-visual {
    position: relative;
    flex: 0 0 auto;
    min-height: 138px;
    overflow: hidden;
    background: radial-gradient(circle at 16% 22%, rgba(255,255,255,0.34), transparent 20%), radial-gradient(circle at 78% 8%, rgba(245, 158, 11, 0.42), transparent 30%), linear-gradient(135deg, var(--brand-600), var(--brand-500));
}
.upsell-popup.has-image .upsell-visual {
    min-height: clamp(190px, 32dvh, 300px);
}
.upsell-image {
    display: none;
    position: absolute;
    inset: 0;
}
.upsell-popup.has-image .upsell-image {
    display: block;
}
.upsell-image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
}
.upsell-visual::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(15,23,42,0.08), rgba(15,23,42,0.58)), linear-gradient(90deg, rgba(15,23,42,0.22), transparent 55%);
    pointer-events: none;
}
.upsell-hero-mark {
    position: absolute;
    right: 72px;
    bottom: 26px;
    width: 76px;
    height: 76px;
    border-radius: 22px;
    display: grid;
    place-items: center;
    color: #fff;
    background: rgba(255,255,255,0.16);
    border: 1px solid rgba(255,255,255,0.28);
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.24);
    transform: rotate(-8deg);
}
.upsell-popup.has-image .upsell-hero-mark {
    display: none;
}
.upsell-badge {
    position: absolute;
    left: 20px;
    bottom: 18px;
    z-index: 2;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    max-width: calc(100% - 90px);
    padding: 8px 13px;
    border-radius: 999px;
    color: #111827;
    background: #fff;
    box-shadow: 0 14px 32px rgba(15, 23, 42, 0.22);
    font-size: 0.76rem;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    white-space: nowrap;
}
.upsell-badge svg {
    color: #d97706;
}
.upsell-panel {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
}
.upsell-scroll {
    flex: 1 1 auto;
    min-height: 0;
    padding: 30px 30px 22px;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
}
.upsell-kicker {
    display: inline-flex;
    margin-bottom: 12px;
    color: var(--brand-600);
    font-size: 0.78rem;
    font-weight: 850;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}
#upsell-title {
    margin: 0;
    color: var(--gray-900);
    font-size: clamp(1.45rem, 3.8vw, 2rem);
    font-weight: 900;
    line-height: 1.08;
    letter-spacing: 0;
    overflow-wrap: anywhere;
}
#upsell-desc {
    margin: 14px 0 0;
    color: var(--gray-600);
    font-size: 1rem;
    line-height: 1.68;
    overflow-wrap: anywhere;
    white-space: pre-line;
}
.upsell-actions {
    flex: 0 0 auto;
    padding: 16px 30px 24px;
    background: var(--surface);
    border-top: 1px solid rgba(148, 163, 184, 0.18);
    box-shadow: 0 -18px 28px -30px rgba(15, 23, 42, 0.5);
}
.upsell-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    width: 100%;
    min-height: 56px;
    padding: 16px 22px;
    border-radius: 13px;
    color: #fff;
    background: linear-gradient(135deg, var(--brand-500), var(--brand-600));
    box-shadow: 0 16px 34px -16px var(--brand-500);
    font-size: 1rem;
    font-weight: 900;
    text-align: center;
    text-decoration: none;
    transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
}
.upsell-cta:hover {
    transform: translateY(-2px);
    filter: brightness(1.04);
    box-shadow: 0 20px 42px -18px var(--brand-500);
}
.upsell-skip {
    display: block;
    width: 100%;
    margin: 12px auto 0;
    padding: 8px 10px;
    border: 0;
    background: transparent;
    color: var(--gray-500);
    cursor: pointer;
    font-size: 0.9rem;
    font-weight: 700;
    transition: color 0.2s ease;
}
.upsell-skip:hover {
    color: var(--gray-800);
    text-decoration: underline;
}

@media (max-width: 640px) {
    .upsell-overlay {
        align-items: flex-end;
        padding: 8px 0 0;
    }
    .upsell-popup {
        width: 100%;
        max-height: calc(100vh - 8px);
        max-height: calc(100dvh - 8px);
        border-radius: 18px 18px 0 0;
        transform: translateY(28px);
    }
    .upsell-popup.is-open {
        transform: translateY(0);
    }
    .upsell-visual {
        min-height: 104px;
    }
    .upsell-popup.has-image .upsell-visual {
        min-height: clamp(150px, 28dvh, 220px);
    }
    .upsell-close {
        top: 12px;
        right: 12px;
        width: 40px;
        height: 40px;
    }
    .upsell-badge {
        left: 16px;
        bottom: 14px;
        max-width: calc(100% - 78px);
        font-size: 0.7rem;
        padding: 7px 11px;
    }
    .upsell-hero-mark {
        right: 64px;
        bottom: 18px;
        width: 58px;
        height: 58px;
        border-radius: 18px;
    }
    .upsell-scroll {
        padding: 24px 18px 18px;
    }
    .upsell-actions {
        padding: 14px 18px max(16px, env(safe-area-inset-bottom));
    }
    .upsell-cta {
        min-height: 52px;
        padding: 14px 18px;
    }
}

.offer-card {
    position: relative;
    border: 2px solid transparent;
    background: linear-gradient(var(--surface), var(--surface)) padding-box, linear-gradient(135deg, #f59e0b, #ef4444) border-box;
    box-shadow: 0 10px 25px -5px rgba(245, 158, 11, 0.15);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.3s;
}
.offer-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 35px -5px rgba(245, 158, 11, 0.25);
}
.offer-badge {
    position: absolute;
    top: -12px;
    right: 20px;
    background: linear-gradient(135deg, #f59e0b, #ef4444);
    color: white;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 1px;
    padding: 6px 12px;
    border-radius: 20px;
    box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
    z-index: 10;
    display: flex;
    align-items: center;
    gap: 4px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const slug = <?= json_encode($slug ?? '', JSON_UNESCAPED_SLASHES) ?>;
    const offerApiBase = <?= json_encode(url('/m/'), JSON_UNESCAPED_SLASHES) ?>;
    if (!slug) return;
    
    fetch(offerApiBase + encodeURIComponent(slug) + '/api/offer')
        .then(r => r.json())
        .then(data => {
            if (!data.offers || data.offers.length === 0) return;
            
            let popupOffer = null;
            
            // Iterate over all active offers
            data.offers.forEach(o => {
                // 1. Inject EVERY active offer as a Product Card
                createOfferCard(o);
                
                // Track if this offer should be a popup
                if (o.show_as_popup) popupOffer = o;
            });
            
            // 2. Show Popup ONLY if an offer has it enabled & not dismissed
            if (popupOffer && !sessionStorage.getItem('upsell_dismissed_session')) {
                showOfferPopup(popupOffer);
            }
        })
        .catch(console.error);
        
    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function safeUrl(value, fallback = '#') {
        if (!value) return fallback;

        try {
            const url = new URL(String(value), window.location.href);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : fallback;
        } catch (e) {
            return fallback;
        }
    }

    function createOfferCard(o) {
        const grid = document.querySelector('.products-grid') || document.querySelector('#upsell-container');
        if (!grid) return;
        
        const card = document.createElement('a');
        const title = escapeHtml(o.title || 'Oferta especial');
        const description = escapeHtml(o.description || 'Aproveite esta condicao especial para membros.');
        const imageUrl = safeUrl(o.image, '');

        card.href = safeUrl(o.checkout_url, '#');
        card.target = '_blank';
        card.rel = 'noopener';
        card.className = 'product-card offer-card locked';
        card.style.textDecoration = 'none';
        card.style.display = 'block';
        card.style.cursor = 'pointer';
        
        let imgHtml = imageUrl
            ? `<img src="${escapeHtml(imageUrl)}" alt="Oferta">`
            : `<div class="placeholder-icon"><i data-lucide="zap" style="width:48px;height:48px;"></i></div>`;
            
        card.innerHTML = `
            <div class="card-image">
                ${imgHtml}
                <div class="lock-icon" style="top:12px; right:12px; left:auto; transform:none; width:32px; height:32px;">
                    <i data-lucide="lock" style="width:16px;height:16px;"></i>
                </div>
            </div>
            
            <div class="card-body">
                <span class="card-type" style="background:var(--brand-50); color:var(--brand-600);">
                    <i data-lucide="zap" style="width:12px;height:12px;"></i>
                    Oferta Exclusiva
                </span>
                <h3 class="card-title">${title}</h3>
                <p class="card-desc">${description}</p>
                
                <span class="btn btn-checkout">
                    <i data-lucide="shopping-cart" style="width:16px;height:16px;"></i>
                    <?= __('buy_access') ?>
                </span>
            </div>
        `;
        
        // Find the "Recommended Products" grid if it exists, otherwise just prepend to the first available grid
        let targetGrid = grid;
        const allGrids = document.querySelectorAll('.products-grid');
        if (allGrids.length > 1) {
            targetGrid = allGrids[1]; // The second grid is typically "Outros Produtos Recomendados"
        }
        
        targetGrid.prepend(card);
        
        if (window.lucide && window.lucide.createIcons) {
            window.lucide.createIcons({ root: card });
        }
    }
    
    function showOfferPopup(o) {
        document.getElementById('upsell-title').textContent = o.title;
        document.getElementById('upsell-desc').textContent = o.description || '';
        
        const imgBox = document.getElementById('upsell-image');
        const image = imgBox.querySelector('img');
        const cta = document.getElementById('upsell-cta');
        const overlay = document.getElementById('upsell-overlay');
        const popup = document.getElementById('upsell-popup');
        const imageUrl = safeUrl(o.image, '');
        const checkoutUrl = safeUrl(o.checkout_url, '');
        const hasImage = Boolean(imageUrl);

        popup.classList.toggle('has-image', hasImage);

        if (imageUrl) {
            image.src = imageUrl;
            image.alt = o.title || 'Oferta especial';
        } else {
            image.removeAttribute('src');
            image.alt = '';
        }
        
        if (checkoutUrl) {
            cta.href = checkoutUrl;
            cta.style.display = 'flex';
        } else {
            cta.style.display = 'none';
        }
        
        const scrollArea = popup.querySelector('.upsell-scroll');
        const originalBodyOverflow = document.body.style.overflow;
        let closed = false;
        
        overlay.style.display = 'flex';
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        if (scrollArea) scrollArea.scrollTop = 0;
        requestAnimationFrame(() => {
            overlay.classList.add('is-visible');
            popup.classList.add('is-open');
        });
        
        const close = (e) => { 
            if (closed) return;
            closed = true;
            if(e) e.preventDefault();
            overlay.classList.remove('is-visible');
            overlay.setAttribute('aria-hidden', 'true');
            popup.classList.remove('is-open');
            document.removeEventListener('keydown', onKeyDown);
            setTimeout(() => {
                overlay.style.display = 'none';
                document.body.style.overflow = originalBodyOverflow;
            }, 280);
            sessionStorage.setItem('upsell_dismissed_session', '1'); 
        };

        const onKeyDown = (e) => {
            if (e.key === 'Escape') close(e);
        };
        
        document.getElementById('upsell-close').onclick = close;
        document.getElementById('upsell-skip').onclick = close;
        cta.onclick = () => { sessionStorage.setItem('upsell_dismissed_session', '1'); }; 
        document.addEventListener('keydown', onKeyDown);
        
        overlay.onclick = e => { 
            if (e.target === overlay) close(); 
        };
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layouts/member.php';
?>
