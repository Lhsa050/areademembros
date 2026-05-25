<?php

namespace App\Services;

/**
 * Serviço de Traduções por Funil
 * 
 * Suporta PT-BR, ES, EN com frases customizáveis por funil.
 */
class TranslationService
{
    private static ?array $translations = null;
    private static string $currentLang = 'pt-BR';
    private static array $customOverrides = [];

    /**
     * Dicionário de traduções padrão
     */
    private static array $dictionary = [
        // === Dashboard ===
        'hello' => [
            'pt-BR' => 'Olá',
            'es' => 'Hola',
            'en' => 'Hello',
        ],
        'my_products' => [
            'pt-BR' => 'Meus Produtos',
            'es' => 'Mis Productos',
            'en' => 'My Products',
        ],
        'recommended_products' => [
            'pt-BR' => 'Produtos Recomendados',
            'es' => 'Productos Recomendados',
            'en' => 'Recommended Products',
        ],
        'check_products' => [
            'pt-BR' => 'Confira seus produtos abaixo',
            'es' => 'Consulta tus productos a continuación',
            'en' => 'Check your products below',
        ],
        'no_products' => [
            'pt-BR' => 'Nenhum produto disponível',
            'es' => 'No hay productos disponibles',
            'en' => 'No products available',
        ],
        'no_products_desc' => [
            'pt-BR' => 'Ainda não há produtos cadastrados nesta área.',
            'es' => 'Aún no hay productos registrados en esta área.',
            'en' => 'No products have been added to this area yet.',
        ],

        // === Product Card ===
        'access' => [
            'pt-BR' => 'Acessar',
            'es' => 'Acceder',
            'en' => 'Access',
        ],
        'buy_access' => [
            'pt-BR' => 'Comprar Acesso',
            'es' => 'Comprar Acceso',
            'en' => 'Buy Access',
        ],
        'upgrade_now' => [
            'pt-BR' => 'Garantir Oferta Agora',
            'es' => 'Obtener Oferta Ahora',
            'en' => 'Upgrade Now',
        ],
        'locked' => [
            'pt-BR' => 'Bloqueado',
            'es' => 'Bloqueado',
            'en' => 'Locked',
        ],
        'releases_on' => [
            'pt-BR' => 'Libera em',
            'es' => 'Se libera el',
            'en' => 'Available on',
        ],
        'video_course' => [
            'pt-BR' => 'Curso em Vídeo',
            'es' => 'Curso en Video',
            'en' => 'Video Course',
        ],
        'pdf_material' => [
            'pt-BR' => 'Material PDF',
            'es' => 'Material PDF',
            'en' => 'PDF Material',
        ],

        // === Product Page / Lesson ===
        'download' => [
            'pt-BR' => 'Baixar',
            'es' => 'Descargar',
            'en' => 'Download',
        ],
        'open_link' => [
            'pt-BR' => 'Acessar',
            'es' => 'Acceder',
            'en' => 'Open',
        ],
        'external_link' => [
            'pt-BR' => 'Link externo',
            'es' => 'Enlace externo',
            'en' => 'External link',
        ],
        'lesson_files' => [
            'pt-BR' => 'Arquivos da Aula',
            'es' => 'Archivos de la Lección',
            'en' => 'Lesson Files',
        ],
        'product_files' => [
            'pt-BR' => 'Arquivos do Produto',
            'es' => 'Archivos del Producto',
            'en' => 'Product Files',
        ],
        'previous' => [
            'pt-BR' => 'Anterior',
            'es' => 'Anterior',
            'en' => 'Previous',
        ],
        'next' => [
            'pt-BR' => 'Próxima',
            'es' => 'Siguiente',
            'en' => 'Next',
        ],
        'back_to_product' => [
            'pt-BR' => 'Voltar ao produto',
            'es' => 'Volver al producto',
            'en' => 'Back to product',
        ],
        'modules' => [
            'pt-BR' => 'Módulos',
            'es' => 'Módulos',
            'en' => 'Modules',
        ],
        'lessons' => [
            'pt-BR' => 'Aulas',
            'es' => 'Lecciones',
            'en' => 'Lessons',
        ],

        // === Login ===
        'login' => [
            'pt-BR' => 'Entrar',
            'es' => 'Iniciar sesión',
            'en' => 'Log In',
        ],
        'login_title' => [
            'pt-BR' => 'Acesse sua área de membros',
            'es' => 'Accede a tu área de miembros',
            'en' => 'Access your member area',
        ],
        'email' => [
            'pt-BR' => 'E-mail',
            'es' => 'Correo electrónico',
            'en' => 'Email',
        ],
        'password' => [
            'pt-BR' => 'Senha',
            'es' => 'Contraseña',
            'en' => 'Password',
        ],
        'cpf' => [
            'pt-BR' => 'CPF',
            'es' => 'Documento',
            'en' => 'Document ID',
        ],
        'phone' => [
            'pt-BR' => 'Telefone',
            'es' => 'Teléfono',
            'en' => 'Phone',
        ],

        // === Footer ===
        'all_rights_reserved' => [
            'pt-BR' => 'Todos os direitos reservados',
            'es' => 'Todos los derechos reservados',
            'en' => 'All rights reserved',
        ],
        'logout' => [
            'pt-BR' => 'Sair',
            'es' => 'Salir',
            'en' => 'Log Out',
        ],
    ];

    /**
     * Inicializa com dados do funil
     */
    public static function init(array $funnel): void
    {
        self::$currentLang = $funnel['language'] ?? 'pt-BR';
        self::$customOverrides = [];

        if (!empty($funnel['custom_translations'])) {
            $custom = is_string($funnel['custom_translations'])
                ? json_decode($funnel['custom_translations'], true)
                : $funnel['custom_translations'];
            if (is_array($custom)) {
                self::$customOverrides = $custom;
            }
        }
    }

    /**
     * Traduz uma chave
     */
    public static function get(string $key, ?string $lang = null): string
    {
        $lang = $lang ?? self::$currentLang;

        // Custom override primeiro
        if (isset(self::$customOverrides[$key])) {
            return self::$customOverrides[$key];
        }

        // Dicionário padrão
        if (isset(self::$dictionary[$key][$lang])) {
            return self::$dictionary[$key][$lang];
        }

        // Fallback para pt-BR
        if (isset(self::$dictionary[$key]['pt-BR'])) {
            return self::$dictionary[$key]['pt-BR'];
        }

        return $key;
    }

    /**
     * Retorna idioma atual
     */
    public static function lang(): string
    {
        return self::$currentLang;
    }

    /**
     * Retorna todas as chaves disponíveis para customização
     */
    public static function allKeys(): array
    {
        return array_keys(self::$dictionary);
    }

    /**
     * Retorna idiomas disponíveis
     */
    public static function availableLanguages(): array
    {
        return [
            'pt-BR' => 'Português (Brasil)',
            'es' => 'Español',
            'en' => 'English',
        ];
    }
}
