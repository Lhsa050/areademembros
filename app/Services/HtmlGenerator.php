<?php

namespace App\Services;

use App\Models\AccessLevel;
use App\Models\Product;
use App\Models\Lesson;
use App\Core\Database;

/**
 * Gerador de HTML Estático
 */
class HtmlGenerator
{
    private string $siteName;
    private string $theme;
    private int $funnelId;
    private array $accessLevels;
    private bool $useRelativePaths = false;

    public function __construct(string $siteName, string $theme, int $funnelId = 0)
    {
        $this->siteName = $siteName;
        $this->theme = $theme;
        $this->funnelId = $funnelId;
        $this->accessLevels = $this->loadAccessLevels();
    }

    /**
     * Define se deve usar caminhos relativos (para ZIP) ou absolutos (para preview)
     */
    public function setUseRelativePaths(bool $value): self
    {
        $this->useRelativePaths = $value;
        return $this;
    }

    private function loadAccessLevels(): array
    {
        if ($this->funnelId > 0) {
            $levels = AccessLevel::getByFunnel($this->funnelId);
        } else {
            $levels = AccessLevel::all('id', 'ASC');
        }

        foreach ($levels as &$level) {
            $level['products'] = [];
            $products = Product::getByAccessLevel($level['id']);
            foreach ($products as $product) {
                if ($product['type'] === 'video') {
                    $product = Product::findWithModules($product['id']);
                }
                $level['products'][] = $product;
            }
        }
        return $levels;
    }

    public function generate(): string
    {
        $colors = $this->getThemeColors();
        $catalogJs = $this->buildCatalogJs();
        $keysJs = $this->buildKeysJs();
        return $this->buildHtml($colors, $catalogJs, $keysJs);
    }

    private function getThemeColors(): array
    {
        return match ($this->theme) {
            // Tema 1: Elegante Escuro - Preto com dourado, sofisticado
            'elegante-escuro' => [
                'primary' => '#8A651F', 'primaryDark' => '#5F4314',
                'background' => '#0D0D0B', 'card' => '#181713',
                'text' => '#D8D3C7', 'heading' => '#FFF8E7',
                'bgVideo' => '#050505',
                'fontSerif' => 'Playfair Display', 'fontSans' => 'Inter'
            ],
            // Tema 2: Elegante Claro - Branco com dourado, luxuoso
            'elegante-claro' => [
                'primary' => '#8A651F', 'primaryDark' => '#5F4314',
                'background' => '#F7F4EF', 'card' => '#FFFFFF',
                'text' => '#3F3A33', 'heading' => '#17130E',
                'bgVideo' => '#17130E',
                'fontSerif' => 'Playfair Display', 'fontSans' => 'Inter'
            ],
            // Tema 3: Moderno Azul - Azul profissional, tecnologia
            'moderno-azul' => [
                'primary' => '#2563EB', 'primaryDark' => '#1E3A8A',
                'background' => '#F3F7FB', 'card' => '#FFFFFF',
                'text' => '#334155', 'heading' => '#0B1220',
                'bgVideo' => '#0B1220',
                'fontSerif' => 'Inter', 'fontSans' => 'Inter'
            ],
            // Tema 4: Moderno Verde - Verde natureza, saúde
            'moderno-verde' => [
                'primary' => '#047857', 'primaryDark' => '#064E3B',
                'background' => '#F2F8F5', 'card' => '#FFFFFF',
                'text' => '#2F4A3D', 'heading' => '#0F2E24',
                'bgVideo' => '#0F2E24',
                'fontSerif' => 'Inter', 'fontSans' => 'Inter'
            ],
            // Tema 5: Premium Dourado - Tons quentes, premium
            'premium-dourado' => [
                'primary' => '#B45309', 'primaryDark' => '#7C2D12',
                'background' => '#FFF7ED', 'card' => '#FFFFFF',
                'text' => '#5F3717', 'heading' => '#2A1206',
                'bgVideo' => '#2A1206',
                'fontSerif' => 'Playfair Display', 'fontSans' => 'Lato'
            ],
            // Tema 6: Minimalista - Cinza elegante, universal
            default => [
                'primary' => '#4F46E5', 'primaryDark' => '#312E81',
                'background' => '#F7F8FA', 'card' => '#FFFFFF',
                'text' => '#374151', 'heading' => '#111827',
                'bgVideo' => '#111827',
                'fontSerif' => 'Inter', 'fontSans' => 'Inter'
            ],
        };
    }

    private function buildCatalogJs(): string
    {
        $catalog = [];
        $addedProducts = [];
        
        // Primeiro, coletamos todos os níveis de acesso por produto
        $productAccessLevels = [];
        foreach ($this->accessLevels as $level) {
            foreach ($level['products'] as $product) {
                $productId = $product['id'];
                if (!isset($productAccessLevels[$productId])) {
                    $productAccessLevels[$productId] = [];
                }
                $productAccessLevels[$productId][] = $level['uuid_key'];
            }
        }
        
        // Agora carregamos TODOS os produtos do funil
        $allProducts = Product::getByFunnel($this->funnelId);
        
        foreach ($allProducts as $product) {
            $productId = $product['id'];
            
            // Evita duplicatas
            if (isset($addedProducts[$productId])) continue;
            $addedProducts[$productId] = true;
            
            // Carrega módulos se for vídeo
            if ($product['type'] === 'video') {
                $productWithModules = Product::findWithModules($productId);
                $product['modules'] = $productWithModules['modules'] ?? [];
            }
            
            // Gera URLs para arquivos (absolutas para preview online, relativas para ZIP offline)
            $baseUrl = $this->useRelativePaths ? '' : (rtrim($_ENV['APP_URL'] ?? '', '/') . '/');
            $filePath = $product['file'] ?? '';
            $imagePath = $product['image'] ?? '';
            
            $item = [
                'id' => 'product-' . $productId,
                'tipo' => $product['type'],
                'titulo' => $product['title'],
                'descricao' => $product['description'] ?? '',
                'img' => $imagePath ? ($baseUrl . $imagePath) : 'https://placehold.co/800x600/1a1a1a/d4af37?text=Produto',
                'arquivo' => $filePath ? ($baseUrl . $filePath) : '',
                'checkoutUrl' => $product['checkout_url'] ?? '',
                'publico' => !empty($product['is_public']),
                'niveis' => $productAccessLevels[$productId] ?? [], // níveis que têm acesso
            ];
            
            if ($product['type'] === 'video' && !empty($product['modules'])) {
                $item['modulos'] = [];
                foreach ($product['modules'] as $module) {
                    $modItem = ['id' => 'mod-' . $module['id'], 'titulo' => $module['title'], 'aulas' => []];
                    foreach ($module['lessons'] ?? [] as $lesson) {
                        $lessonFile = $lesson['file'] ?? '';
                        $modItem['aulas'][] = [
                            'id' => 'lesson-' . $lesson['id'], 
                            'titulo' => $lesson['title'], 
                            'youtubeId' => $lesson['youtube_id'] ?? '',
                            'descricao' => $lesson['description'] ?? '',
                            'arquivo' => $lessonFile ? ($baseUrl . $lessonFile) : ''
                        ];
                    }
                    $item['modulos'][] = $modItem;
                }
            }
            $catalog[] = $item;
        }
        
        return json_encode($catalog, JSON_UNESCAPED_UNICODE);
    }

    private function buildKeysJs(): string
    {
        $keys = [];
        foreach ($this->accessLevels as $level) {
            $keys[] = $level['uuid_key'];
        }
        return json_encode($keys, JSON_UNESCAPED_UNICODE);
    }

    public function getLinks(): array
    {
        $links = [];
        foreach ($this->accessLevels as $level) {
            $links[] = ['name' => $level['name'], 'key' => $level['uuid_key'], 'param' => '?key=' . $level['uuid_key']];
        }
        return $links;
    }

    private function buildHtml(array $c, string $catalogJs, string $keysJs): string
    {
        $siteName = e($this->siteName);
        // Seleciona fontes baseado no fontSerif do tema
        $googleFonts = ($c['fontSerif'] === 'Inter') 
            ? 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap' 
            : 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400&display=swap';

        $primary = $c['primary'];
        $primaryDark = $c['primaryDark'];
        $background = $c['background'];
        $card = $c['card'];
        $text = $c['text'];
        $heading = $c['heading'];
        $bgVideo = $c['bgVideo'];
        $fontSerif = $c['fontSerif'];
        $fontSans = $c['fontSans'];

        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Membros - {$siteName}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="{$googleFonts}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.plyr.io/3.7.8/plyr.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>tailwind.config={theme:{extend:{colors:{brand:{gold:"{$primary}",goldDark:"{$primaryDark}",rose:"{$background}",text:"{$text}",heading:"{$heading}",bgVideo:"{$bgVideo}",card:"{$card}"}},fontFamily:{serif:["{$fontSerif}","serif"],sans:["{$fontSans}","sans-serif"]}}}}</script>
    <style>
        .fade-in{animation:fadeIn .6s ease-out}@keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .card-hover{transition:all .3s ease}.card-hover:hover{transform:translateY(-5px);box-shadow:0 20px 25px -5px rgba(0,0,0,.1)}
        .confetti{position:fixed;width:10px;height:10px;opacity:0;animation:confetti-fall 3s ease-out forwards}@keyframes confetti-fall{0%{opacity:1;transform:translateY(-100vh) rotate(0)}100%{opacity:0;transform:translateY(100vh) rotate(720deg)}}
        .custom-scroll::-webkit-scrollbar{width:6px}.custom-scroll::-webkit-scrollbar-track{background:{$background}}.custom-scroll::-webkit-scrollbar-thumb{background:{$primary};border-radius:3px}
        /* Footer e Barra do Player com cores do tema */
        .theme-footer{background-color:{$bgVideo} !important;color:rgba(255,255,255,0.4) !important}
        .theme-player-bar{background-color:{$bgVideo} !important}
        /* Plyr Video Player - Estratégia de Stretching para esconder branding do YouTube */
        /* Container precisa esconder overflow para cortar o iframe esticado */
        .plyr__video-embed,.plyr--youtube .plyr__video-embed,.plyr--youtube.plyr__video-embed,.plyr--youtube .plyr__video-wrapper{overflow:hidden !important}
        /* MÁGICA: Estica o iframe para 200% da altura e posiciona -50% no topo - corta as barras superior/inferior do YouTube */
        .plyr--youtube iframe,.plyr--youtube .plyr__video-embed iframe{position:absolute !important;top:-50% !important;left:0 !important;width:100% !important;height:200% !important}
        /* Mostra poster quando pausado/parado para cobrir qualquer branding restante */
        .plyr--youtube.plyr--paused.plyr__poster-enabled .plyr__poster,.plyr--youtube.plyr--stopped.plyr__poster-enabled .plyr__poster{opacity:1 !important}
        /* Personalização do Plyr */
        .plyr{--plyr-color-main:{$primary};width:100%;height:100%}
        .plyr__control--overlaid{background:{$primary} !important}
        .plyr__control--overlaid svg{fill:#fff}
        #video-container{width:100%;height:100%;background:#000;border-radius:0;overflow:hidden}
        #plyr-player{width:100%;height:100%}
        #plyr-player .plyr{width:100%;height:100%}
        /* Garante que controles fiquem visíveis */
        .plyr__controls{z-index:50 !important;opacity:1 !important}
        .plyr__control{opacity:1 !important}
        .plyr__volume,.plyr__progress,.plyr__time{display:flex !important}
    </style>
</head>
<body class="bg-brand-rose font-sans text-brand-text antialiased">

<div id="welcome-screen" class="fixed inset-0 z-[300] bg-brand-rose flex items-center justify-center p-4">
    <div class="bg-brand-card rounded-3xl shadow-2xl max-w-lg w-full p-10 text-center fade-in border border-brand-gold/20">
        <div class="mb-6 flex justify-center"><div class="w-20 h-20 rounded-full bg-gradient-to-br from-brand-gold to-brand-goldDark flex items-center justify-center shadow-lg"><i data-lucide="check" class="w-10 h-10 text-white"></i></div></div>
        <h1 class="font-serif text-4xl text-brand-heading mb-3">Parabéns!</h1>
        <h2 class="text-xl text-brand-gold font-semibold mb-4">Sua compra foi confirmada</h2>
        <p class="text-brand-text/70 mb-8 leading-relaxed">Muito obrigado por fazer parte! Seus materiais exclusivos estão prontos.</p>
        <button id="btn-enter" class="w-full bg-gradient-to-r from-brand-gold to-brand-goldDark hover:from-brand-goldDark hover:to-brand-gold text-white font-bold py-4 px-6 rounded-xl transition-all shadow-lg hover:shadow-xl text-lg flex items-center justify-center gap-3">
            <i data-lucide="arrow-right" class="w-5 h-5"></i> Acessar Meus Produtos
        </button>
    </div>
</div>

<div id="denied-screen" class="hidden fixed inset-0 z-[300] bg-brand-rose flex items-center justify-center p-4">
    <div class="bg-brand-card rounded-3xl shadow-2xl max-w-lg w-full p-10 text-center fade-in border border-red-200">
        <div class="mb-6 flex justify-center"><div class="w-20 h-20 rounded-full bg-red-100 flex items-center justify-center"><i data-lucide="alert-circle" class="w-10 h-10 text-red-500"></i></div></div>
        <h1 class="font-serif text-3xl text-brand-heading mb-3">Acesso Negado</h1>
        <p class="text-brand-text/70 mb-6">Este link não é válido. Verifique seu link de acesso.</p>
    </div>
</div>

<div id="main-content" class="hidden min-h-screen flex flex-col">
    <header class="bg-brand-card shadow-sm sticky top-0 z-40 border-b border-brand-gold/10">
        <div class="max-w-7xl mx-auto px-4 h-20 flex items-center"><i data-lucide="gem" class="text-brand-gold w-6 h-6 mr-2"></i><span class="font-serif text-xl font-bold text-brand-heading">{$siteName}</span></div>
    </header>
    <div class="bg-brand-card border-b border-brand-gold/10 py-12 text-center px-4">
        <h1 class="text-3xl md:text-5xl font-serif text-brand-heading mb-4">Seus Materiais</h1>
        <p class="text-brand-text/60 max-w-2xl mx-auto">Selecione um produto abaixo para acessar.</p>
    </div>
    <main class="flex-grow max-w-7xl mx-auto px-4 py-12 w-full"><div id="catalog-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8"></div></main>
    <footer class="theme-footer py-8 text-center text-sm"><p>&copy; <span id="year"></span> {$siteName}.</p></footer>
</div>

<div id="course-player" class="hidden fixed inset-0 z-[200] bg-brand-card flex-col md:flex-row h-screen w-screen overflow-hidden fade-in">
    <aside class="w-full md:w-80 bg-brand-rose border-r border-brand-gold/20 flex flex-col h-[40vh] md:h-full order-2 md:order-1 z-20 shadow-xl">
        <div class="p-4 border-b border-brand-gold/20 bg-brand-card flex justify-between items-center"><h3 class="font-bold text-brand-heading truncate pr-2 max-w-[200px]" id="player-course-title">Curso</h3><button id="btn-close" class="text-xs font-bold text-brand-text/60 hover:text-brand-gold flex items-center gap-1 bg-brand-rose px-3 py-1.5 rounded-full border border-brand-gold/20"><i data-lucide="x" class="w-3 h-3"></i> FECHAR</button></div>
        <div class="px-4 py-4 bg-brand-card border-b border-brand-gold/10"><div class="flex justify-between text-xs mb-2 font-bold text-brand-text/50 uppercase tracking-wider"><span>Progresso</span><span id="course-progress-text">0%</span></div><div class="w-full bg-brand-rose rounded-full h-2 overflow-hidden"><div id="course-progress-bar" class="bg-brand-gold h-full rounded-full transition-all" style="width:0%"></div></div></div>
        <div id="modules-list" class="flex-1 overflow-y-auto custom-scroll p-3 space-y-4"></div>
    </aside>
    <main class="flex-1 bg-brand-bgVideo flex flex-col h-[60vh] md:h-full order-1 md:order-2 overflow-hidden">
        <div class="flex-1 flex items-center justify-center bg-black relative w-full">
            <div id="video-container" class="w-full h-full absolute inset-0 hidden">
                <div id="plyr-player" class="plyr__video-embed lgmPlayer"></div>
            </div>
            <div id="video-placeholder" class="absolute inset-0 flex flex-col items-center justify-center bg-brand-heading z-10 text-white p-8 text-center"><div class="w-20 h-20 rounded-full bg-white/10 flex items-center justify-center mb-6 animate-pulse"><i data-lucide="play" class="w-8 h-8 fill-current ml-1"></i></div><h2 class="text-2xl font-serif mb-2">Pronto para começar?</h2><p class="text-white/40">Selecione uma aula na lista.</p></div>
        </div>
        <div class="theme-player-bar p-4 flex justify-between items-center shrink-0 border-t border-white/10 z-30"><h2 class="text-sm md:text-lg font-serif truncate mr-4 text-white" id="current-lesson-title">Nenhuma aula</h2><button id="mark-watched-btn" class="flex items-center gap-2 px-5 py-2.5 rounded-full border border-white/20 hover:bg-white/10 text-sm font-bold shrink-0 opacity-50 cursor-not-allowed text-white" disabled><i data-lucide="check-circle" class="w-4 h-4"></i><span>Marcar Visto</span></button></div>
        <div id="lesson-extra" class="hidden bg-brand-card border-t border-brand-gold/10 p-4 max-h-[150px] overflow-y-auto custom-scroll">
            <p id="lesson-description" class="text-sm text-brand-text/80 mb-3"></p>
            <a id="lesson-download" href="#" onclick="forceDownload(event, this.href)" class="hidden inline-flex items-center gap-2 bg-brand-gold text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-brand-goldDark transition"><i data-lucide="download" class="w-4 h-4"></i> Baixar Material</a>
        </div>
    </main>
</div>

<script>
var VALID_KEYS={$keysJs};
var CATALOGO={$catalogJs};
var currentCourse=null,currentLesson=null;
var watchedData=JSON.parse(localStorage.getItem("member_watched"))||{};
var confettiColors=["{$primary}","{$primaryDark}","#FFD700","#FF6B6B","#4ECDC4"];

// Sistema de acesso acumulativo - armazena array de níveis acessados
var userAccessLevels=JSON.parse(localStorage.getItem("member_access_levels"))||[];

// Força download de arquivo em vez de abrir no navegador
function forceDownload(e, url){
    e.preventDefault();
    if(!url || url==='#') return;
    var filename=url.split('/').pop().split('?')[0];
    
    // Usa fetch + blob para forçar download
    fetch(url)
        .then(function(response){
            if(!response.ok) throw new Error('Erro ao baixar');
            return response.blob();
        })
        .then(function(blob){
            var blobUrl=window.URL.createObjectURL(blob);
            var a=document.createElement('a');
            a.href=blobUrl;
            a.download=filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(blobUrl);
        })
        .catch(function(err){
            // Fallback: abre em nova aba
            window.open(url,'_blank');
        });
}

function hasAccessToProduct(item){
    if(item.publico)return true;
    for(var i=0;i<item.niveis.length;i++){
        if(userAccessLevels.indexOf(item.niveis[i])!==-1)return true;
    }
    return false;
}

document.addEventListener("DOMContentLoaded",function(){
    document.getElementById("year").innerText=new Date().getFullYear();
    var urlParams=new URLSearchParams(window.location.search);
    var keyParam=urlParams.get("key");
    
    if(keyParam && VALID_KEYS.indexOf(keyParam)!==-1){
        // Adiciona novo nível ao array se ainda não existe
        if(userAccessLevels.indexOf(keyParam)===-1){
            userAccessLevels.push(keyParam);
            localStorage.setItem("member_access_levels",JSON.stringify(userAccessLevels));
        }
        
        // Verifica se é primeira visita PARA ESTE KEY
        var visitedKey="visited_"+keyParam;
        if(!localStorage.getItem(visitedKey)){
            localStorage.setItem(visitedKey,"1");
            showWelcome();
            createConfetti();
        }else{
            showMainContent();
        }
    }else if(userAccessLevels.length>0){
        // Sem key válida na URL mas tem níveis anteriores - mostra conteúdo
        showMainContent();
    }else{
        document.getElementById("welcome-screen").classList.add("hidden");
        document.getElementById("denied-screen").classList.remove("hidden");
    }
    
    document.getElementById("btn-enter").addEventListener("click",enterMemberArea);
    document.getElementById("btn-close").addEventListener("click",closePlayer);
    document.getElementById("mark-watched-btn").addEventListener("click",toggleWatchedCurrent);
    
    lucide.createIcons();
});

function showWelcome(){
    document.getElementById("welcome-screen").classList.remove("hidden");
    document.getElementById("denied-screen").classList.add("hidden");
    document.getElementById("main-content").classList.add("hidden");
}

function enterMemberArea(){
    document.getElementById("welcome-screen").classList.add("hidden");
    showMainContent();
}

function showMainContent(){
    document.getElementById("welcome-screen").classList.add("hidden");
    document.getElementById("denied-screen").classList.add("hidden");
    document.getElementById("main-content").classList.remove("hidden");
    document.getElementById("main-content").classList.add("fade-in");
    renderCatalog();
}

function createConfetti(){
    for(var i=0;i<50;i++){
        var c=document.createElement("div");
        c.className="confetti";
        c.style.left=Math.random()*100+"vw";
        c.style.backgroundColor=confettiColors[Math.floor(Math.random()*confettiColors.length)];
        c.style.animationDelay=Math.random()*2+"s";
        c.style.borderRadius=Math.random()>0.5?"50%":"0";
        document.body.appendChild(c);
    }
    setTimeout(function(){
        var confettis=document.querySelectorAll(".confetti");
        for(var i=0;i<confettis.length;i++){confettis[i].remove();}
    },5000);
}

function renderCatalog(){
    var grid=document.getElementById("catalog-grid");
    grid.innerHTML="";
    for(var i=0;i<CATALOGO.length;i++){
        var item=CATALOGO[i];
        var hasAccess=hasAccessToProduct(item);
        var isVideo=item.tipo==="video";
        var div=document.createElement("div");
        div.className="group relative bg-brand-card rounded-2xl shadow-lg border border-brand-gold/10 overflow-hidden flex flex-col fade-in transition-all hover:shadow-2xl hover:border-brand-gold/30";
        div.innerHTML=buildCardHtml(item,isVideo,hasAccess);
        
        if(hasAccess){
            if(isVideo){
                div.querySelector(".card-img").addEventListener("click",(function(id){return function(){openCourse(id);};})(item.id));
                div.querySelector(".card-btn").addEventListener("click",(function(id){return function(){openCourse(id);};})(item.id));
            }
        }else{
            // Produto bloqueado - redireciona para checkout
            var checkoutUrl=item.checkoutUrl||"#";
            div.querySelector(".card-img").addEventListener("click",(function(url){return function(){if(url&&url!="#")window.open(url,"_blank");};})(checkoutUrl));
            div.querySelector(".card-btn").addEventListener("click",(function(url){return function(){if(url&&url!="#")window.open(url,"_blank");};})(checkoutUrl));
        }
        grid.appendChild(div);
    }
    lucide.createIcons();
}

function buildCardHtml(item,isVideo,hasAccess){
    var btnText=hasAccess?(isVideo?"ACESSAR AGORA":"BAIXAR AGORA"):"QUERO ESTE PRODUTO";
    var btnIcon=hasAccess?(isVideo?"play-circle":"download"):"shopping-cart";
    var btnClass=hasAccess?"bg-gradient-to-r from-brand-gold to-brand-goldDark text-white hover:shadow-lg":"bg-brand-gold text-white hover:bg-brand-goldDark";
    
    // Badge de tipo - ambos usam cores do tema
    var typeBadge=isVideo?"<div class=\"absolute bottom-3 left-3 bg-gradient-to-r from-brand-gold to-brand-goldDark text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg uppercase tracking-wider z-20 flex items-center gap-1\"><i data-lucide=\"film\" class=\"w-3 h-3\"></i> Curso</div>":"<div class=\"absolute bottom-3 left-3 bg-gradient-to-r from-brand-gold to-brand-goldDark text-white text-[10px] font-bold px-3 py-1 rounded-full shadow-lg uppercase tracking-wider z-20 flex items-center gap-1\"><i data-lucide=\"file-text\" class=\"w-3 h-3\"></i> Material</div>";
    
    // Cadeado para produtos bloqueados - usa cores do tema
    var lockBadge=hasAccess?"":"<div class=\"absolute top-3 right-3 bg-brand-gold text-white p-2 rounded-full shadow-lg z-20\"><i data-lucide=\"lock\" class=\"w-4 h-4\"></i></div>";
    
    // Botão
    var btnHtml="<button class=\"card-btn w-full py-3.5 px-4 rounded-xl font-bold text-xs tracking-wider flex items-center justify-center gap-2 shadow-md "+btnClass+" transition-all cursor-pointer\"><i data-lucide=\""+btnIcon+"\" class=\"w-4 h-4\"></i> "+btnText+"</button>";
    
    if(!hasAccess && !item.checkoutUrl){
        btnHtml="<div class=\"w-full py-3.5 px-4 rounded-xl font-bold text-xs tracking-wider flex items-center justify-center gap-2 bg-brand-text/20 text-brand-text/50 cursor-not-allowed\"><i data-lucide=\"lock\" class=\"w-4 h-4\"></i> BLOQUEADO</div>";
    }
    
    if(!hasAccess && isVideo===false && item.arquivo){
        btnHtml="<button class=\"card-btn w-full py-3.5 px-4 rounded-xl font-bold text-xs tracking-wider flex items-center justify-center gap-2 shadow-md "+btnClass+" transition-all cursor-pointer\"><i data-lucide=\""+btnIcon+"\" class=\"w-4 h-4\"></i> "+btnText+"</button>";
    }
    
    if(hasAccess && !isVideo && item.arquivo){
        btnHtml="<a href=\""+item.arquivo+"\" download=\"\" onclick=\"forceDownload(event, this.href)\" class=\"card-btn w-full py-3.5 px-4 rounded-xl font-bold text-xs tracking-wider flex items-center justify-center gap-2 shadow-md "+btnClass+" transition-all cursor-pointer\"><i data-lucide=\"download\" class=\"w-4 h-4\"></i> BAIXAR AGORA</a>";
    }
    
    return "<div class=\"card-img bg-brand-rose relative overflow-hidden cursor-pointer\" style=\"aspect-ratio:4/3;\"><img src=\""+item.img+"\" onerror=\"this.src='https://placehold.co/800x600/1a1a1a/d4af37?text=Produto'\" class=\"h-full w-full object-cover transition-transform group-hover:scale-105\">"+typeBadge+lockBadge+"</div><div class=\"p-6 flex-1 flex flex-col\"><h3 class=\"font-serif text-xl text-brand-heading mb-2 line-clamp-2\">"+item.titulo+"</h3><p class=\"text-sm text-brand-text/60 mb-6 flex-1 line-clamp-2\">"+item.descricao+"</p>"+btnHtml+"</div>";
}

function openCourse(courseId){
    currentCourse=null;
    for(var i=0;i<CATALOGO.length;i++){
        if(CATALOGO[i].id===courseId){currentCourse=CATALOGO[i];break;}
    }
    if(!currentCourse)return;
    document.getElementById("player-course-title").innerText=currentCourse.titulo;
    document.getElementById("main-content").classList.add("hidden");
    var player=document.getElementById("course-player");
    player.classList.remove("hidden");
    player.classList.add("flex");
    resetPlayerView();
    renderModules();
    updateGlobalProgress();
    
    // Auto-play primeira aula
    if(currentCourse.modulos && currentCourse.modulos.length>0 && currentCourse.modulos[0].aulas && currentCourse.modulos[0].aulas.length>0){
        var primeiraAula=currentCourse.modulos[0].aulas[0];
        playLesson(primeiraAula.id, primeiraAula.youtubeId, primeiraAula.titulo, primeiraAula.descricao||'', primeiraAula.arquivo||'');
    }
}

function closePlayer(){
    document.getElementById("course-player").classList.add("hidden");
    document.getElementById("course-player").classList.remove("flex");
    document.getElementById("main-content").classList.remove("hidden");
    resetPlayerView();
    currentCourse=null;
    currentLesson=null;
    // Destroi player Plyr se existir
    if(window.currentPlyrPlayer){window.currentPlyrPlayer.destroy();window.currentPlyrPlayer=null;}
}

function resetPlayerView(){
    var container=document.getElementById("video-container");
    container.classList.add("hidden");
    document.getElementById("plyr-player").innerHTML="";
    if(window.currentPlyrPlayer){window.currentPlyrPlayer.destroy();window.currentPlyrPlayer=null;}
    document.getElementById("video-placeholder").classList.remove("hidden");
    document.getElementById("current-lesson-title").innerText="Nenhuma aula";
    var btn=document.getElementById("mark-watched-btn");
    btn.classList.add("opacity-50","cursor-not-allowed");
    btn.disabled=true;
}

function renderModules(){
    var list=document.getElementById("modules-list");
    list.innerHTML="";
    if(!currentCourse||!currentCourse.modulos)return;
    for(var m=0;m<currentCourse.modulos.length;m++){
        var mod=currentCourse.modulos[m];
        var modDiv=document.createElement("div");
        modDiv.className="mb-6";
        modDiv.innerHTML="<h4 class=\"px-1 mb-2 text-xs font-bold text-brand-text/40 uppercase tracking-widest flex items-center gap-2\"><span class=\"w-2 h-2 rounded-full bg-brand-gold/50\"></span>"+mod.titulo+"</h4><div class=\"lessons-container\"></div>";
        var lessonsContainer=modDiv.querySelector(".lessons-container");
        for(var a=0;a<mod.aulas.length;a++){
            var aula=mod.aulas[a];
            var lessonDiv=document.createElement("div");
            var isWatched=watchedData[aula.id];
            var isSelected=currentLesson&&currentLesson.id===aula.id;
            var bgClass=isSelected?"bg-brand-card border-brand-gold":"bg-brand-card border-transparent hover:border-brand-gold/30";
            var textClass=isSelected?"text-brand-heading font-bold":"text-brand-text";
            var icon=isWatched?"check-circle-2":"play-circle";
            var iconColor=isWatched?"text-green-500":(isSelected?"text-brand-gold":"text-brand-text/30");
            lessonDiv.className="cursor-pointer p-3 rounded-lg border "+bgClass+" transition-all mb-2 flex items-center gap-3";
            lessonDiv.innerHTML="<i data-lucide=\""+icon+"\" class=\"w-5 h-5 "+iconColor+" shrink-0\"></i><p class=\"text-sm truncate "+textClass+"\">"+aula.titulo+"</p>";
            lessonDiv.addEventListener("click",(function(id,ytId,title,desc,file){return function(){playLesson(id,ytId,title,desc,file);};})(aula.id,aula.youtubeId,aula.titulo,aula.descricao||'',aula.arquivo||''));
            lessonsContainer.appendChild(lessonDiv);
        }
        list.appendChild(modDiv);
    }
    lucide.createIcons();
}

function playLesson(id,ytUrlOrId,title,descricao,arquivo){
    currentLesson={id:id,ytUrlOrId:ytUrlOrId,title:title,descricao:descricao||'',arquivo:arquivo||''};
    var cleanId=extractYoutubeId(ytUrlOrId);
    document.getElementById("video-placeholder").classList.add("hidden");
    var container=document.getElementById("video-container");
    container.classList.remove("hidden");
    
    // Destroi player anterior se existir
    if(window.currentPlyrPlayer){
        try{window.currentPlyrPlayer.destroy();}catch(e){}
        window.currentPlyrPlayer=null;
    }
    
    // Cria elemento de vídeo do Plyr usando data attributes (formato correto para YouTube)
    var plyrDiv=document.getElementById("plyr-player");
    plyrDiv.innerHTML='<div data-plyr-provider="youtube" data-plyr-embed-id="'+cleanId+'"></div>';
    
    // Inicializa Plyr
    window.currentPlyrPlayer=new Plyr(plyrDiv.querySelector('[data-plyr-provider]'),{
        youtube:{noCookie:false,rel:0,showinfo:0,iv_load_policy:3,modestbranding:1,autoplay:1},
        controls:['play-large','play','progress','current-time','mute','volume','settings','fullscreen'],
        autoplay:true
    });
    
    // Controle de poster
    window.currentPlyrPlayer.on('pause',function(){var p=document.querySelector('.plyr--youtube .plyr__poster');if(p)p.style.opacity='1';});
    window.currentPlyrPlayer.on('play',function(){var p=document.querySelector('.plyr--youtube .plyr__poster');if(p)p.style.opacity='0';});
    window.currentPlyrPlayer.on('ended',function(){var p=document.querySelector('.plyr--youtube .plyr__poster');if(p)p.style.opacity='1';});
    
    document.getElementById("current-lesson-title").innerText=title;
    
    // Exibe descrição e download
    var extraSection=document.getElementById("lesson-extra");
    var descEl=document.getElementById("lesson-description");
    var downloadEl=document.getElementById("lesson-download");
    if(descricao||arquivo){
        extraSection.classList.remove("hidden");
        descEl.innerText=descricao||'';
        if(arquivo){
            downloadEl.classList.remove("hidden");
            downloadEl.href=arquivo;
        }else{
            downloadEl.classList.add("hidden");
        }
    }else{
        extraSection.classList.add("hidden");
    }
    lucide.createIcons();
    
    var btn=document.getElementById("mark-watched-btn");
    btn.classList.remove("opacity-50","cursor-not-allowed");
    btn.disabled=false;
    updateWatchedButton();
    renderModules();
}

function extractYoutubeId(urlOrId){
    var regExp=/^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
    var match=urlOrId.match(regExp);
    return(match&&match[2].length===11)?match[2]:urlOrId;
}

function toggleWatchedCurrent(){
    if(!currentLesson)return;
    watchedData[currentLesson.id]=!watchedData[currentLesson.id];
    localStorage.setItem("member_watched",JSON.stringify(watchedData));
    updateWatchedButton();
    renderModules();
    updateGlobalProgress();
}

function updateWatchedButton(){
    var btn=document.getElementById("mark-watched-btn");
    var isWatched=watchedData[currentLesson.id];
    if(isWatched){
        btn.classList.add("bg-green-500","border-green-500","text-white");
        btn.innerHTML="<i data-lucide=\"check\" class=\"w-4 h-4\"></i> <span>Concluída</span>";
    }else{
        btn.classList.remove("bg-green-500","border-green-500","text-white");
        btn.innerHTML="<i data-lucide=\"check-circle\" class=\"w-4 h-4\"></i> <span>Marcar Visto</span>";
    }
    lucide.createIcons();
}

function updateGlobalProgress(){
    if(!currentCourse||!currentCourse.modulos)return;
    var total=0,watched=0;
    for(var m=0;m<currentCourse.modulos.length;m++){
        for(var a=0;a<currentCourse.modulos[m].aulas.length;a++){
            total++;
            if(watchedData[currentCourse.modulos[m].aulas[a].id])watched++;
        }
    }
    var pct=total===0?0:Math.round((watched/total)*100);
    document.getElementById("course-progress-bar").style.width=pct+"%";
    document.getElementById("course-progress-text").innerText=pct+"%";
}
</script>
<script src="https://cdn.plyr.io/3.7.8/plyr.polyfilled.js"></script>
</body>
</html>
HTML;
    }

    public function saveToFile(): string
    {
        $html = $this->generate();
        $dir = ABSPATH . '/storage/generated';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = 'area-membros-funil-' . $this->funnelId . '.html';
        file_put_contents($dir . '/' . $filename, $html);
        return $filename;
    }

    public function getFilename(): string
    {
        return 'area-membros-funil-' . $this->funnelId . '.html';
    }
}
