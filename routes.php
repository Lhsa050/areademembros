<?php
/**
 * Definição de Rotas
 */

use App\Core\Router;

// === WEBHOOKS (público, sem auth) ===
Router::post('/webhook/{token}', 'WebhookController@handle');

// Rotas de Autenticação
Router::get('/login', 'AuthController@showLogin');
Router::post('/login', 'AuthController@login');
Router::get('/logout', 'AuthController@logout');

// Dashboard
Router::get('/', 'DashboardController@index');
Router::get('/dashboard', 'DashboardController@index');

// Suporte centralizado (admin)
Router::get('/support', 'SupportController@index');
Router::get('/support/tickets/{id}', 'SupportController@show');
Router::post('/support/tickets/{id}/reply', 'SupportController@reply');
Router::post('/support/tickets/{id}/status', 'SupportController@updateStatus');

// Produtos globais
Router::get('/products', 'ProductController@index');
Router::get('/products/create', 'ProductController@create');
Router::post('/products', 'ProductController@store');
Router::get('/products/{id}/edit', 'ProductController@edit');
Router::post('/products/{id}', 'ProductController@update');
Router::post('/products/{id}/delete', 'ProductController@destroy');
Router::post('/products/{productId}/modules', 'ProductController@storeGlobalModule');
Router::post('/products/{productId}/files', 'ProductController@storeGlobalProductFile');

// === MEMBROS (admin, escopado por funil) ===
Router::get('/funnels/{funnelId}/members', 'MemberController@index');
Router::get('/funnels/{funnelId}/members/create', 'MemberController@create');
Router::post('/funnels/{funnelId}/members', 'MemberController@store');
Router::get('/funnels/{funnelId}/members/{id}/edit', 'MemberController@edit');
Router::post('/funnels/{funnelId}/members/{id}', 'MemberController@update');
Router::post('/funnels/{funnelId}/members/{id}/delete', 'MemberController@destroy');
Router::post('/funnels/{funnelId}/members/{id}/send-access', 'MemberController@sendAccessEmail');
Router::post('/funnels/{funnelId}/members/{id}/products', 'MemberController@addProduct');
Router::post('/funnels/{funnelId}/members/{id}/products/{productId}/remove', 'MemberController@removeProduct');

// Gestão de Admins
Router::get('/admins', 'AdminController@index');
Router::get('/admins/create', 'AdminController@create');
Router::post('/admins', 'AdminController@store');
Router::get('/admins/{id}/edit', 'AdminController@edit');
Router::post('/admins/{id}', 'AdminController@update');
Router::post('/admins/{id}/delete', 'AdminController@destroy');

// Configurações
Router::get('/settings', 'SettingsController@index');
Router::post('/settings', 'SettingsController@update');
Router::post('/settings/test-email', 'SettingsController@testEmail');

// Banco de Dados
Router::get('/settings/database', 'SettingsController@database');
Router::post('/settings/database/backup', 'SettingsController@createBackup');
Router::post('/settings/database/restore', 'SettingsController@restoreBackup');
Router::post('/settings/database/delete', 'SettingsController@deleteBackup');
Router::get('/settings/database/download', 'SettingsController@downloadBackup');
Router::post('/settings/database/migrate', 'SettingsController@runMigrations');

// Fiscal / Notas fiscais
Router::get('/fiscal', 'FiscalController@index');
Router::get('/fiscal/settings', 'FiscalController@settings');
Router::post('/fiscal/settings', 'FiscalController@updateSettings');
Router::post('/fiscal/certificate', 'FiscalController@uploadCertificate');
Router::get('/fiscal/taxation', 'FiscalController@taxation');
Router::post('/fiscal/taxation/groups', 'FiscalController@saveTaxGroup');
Router::post('/fiscal/taxation/groups/{id}', 'FiscalController@saveTaxGroup');
Router::post('/fiscal/taxation/rules', 'FiscalController@saveTaxRule');
Router::post('/fiscal/taxation/rules/{id}', 'FiscalController@saveTaxRule');
Router::get('/fiscal/closing', 'FiscalController@closing');
Router::post('/fiscal/closing/generate', 'FiscalController@generateClosing');
Router::get('/fiscal/closing/{id}/download', 'FiscalController@downloadClosing');
Router::post('/fiscal/sales/{id}/issue', 'FiscalController@issue');
Router::post('/fiscal/invoices/{id}/cancel', 'FiscalController@cancel');
Router::get('/fiscal/export', 'FiscalController@export');
Router::get('/fiscal/invoices/{id}/download/{type}', 'FiscalController@download');

// Atualizações do Sistema
Router::get('/update', 'UpdateController@index');
Router::get('/update/check', 'UpdateController@check');
Router::post('/update/apply', 'UpdateController@apply');
Router::post('/update/restore', 'UpdateController@restore');

// === FUNIS ===
Router::get('/funnels', 'FunnelController@index');
Router::get('/funnels/create', 'FunnelController@create');
Router::post('/funnels', 'FunnelController@store');
Router::post('/funnels/generate-password', 'FunnelController@generatePassword');
Router::get('/funnels/{funnelId}/settings', 'FunnelSettingsController@index');
Router::post('/funnels/{funnelId}/settings', 'FunnelSettingsController@update');
Router::post('/funnels/{funnelId}/settings/test-email', 'FunnelSettingsController@testEmail');

// Produtos do Funil
Router::get('/funnels/{funnelId}/products', 'FunnelController@products');
Router::get('/funnels/{funnelId}/products/create', 'FunnelController@createProduct');
Router::post('/funnels/{funnelId}/products', 'FunnelController@storeProduct');
Router::post('/funnels/{funnelId}/products/reorder', 'ProductController@reorderProducts');
Router::get('/funnels/{funnelId}/products/{productId}/edit', 'FunnelController@editProduct');
Router::post('/funnels/{funnelId}/products/{productId}', 'FunnelController@updateProduct');
Router::post('/funnels/{funnelId}/products/{productId}/delete', 'FunnelController@destroyProduct');
Router::post('/funnels/{funnelId}/products/{productId}/duplicate', 'FunnelController@duplicateProduct');

Router::get('/funnels/{id}', 'FunnelController@show');
Router::get('/funnels/{id}/edit', 'FunnelController@edit');
Router::post('/funnels/{id}', 'FunnelController@update');
Router::post('/funnels/{id}/delete', 'FunnelController@destroy');

// Módulos e Aulas (AJAX)
Router::post('/funnels/{funnelId}/products/{productId}/modules', 'ProductController@storeModule');
Router::post('/modules/{moduleId}', 'ProductController@updateModule');
Router::post('/modules/{moduleId}/delete', 'ProductController@destroyModule');
Router::post('/modules/{moduleId}/lessons', 'ProductController@storeLesson');
Router::post('/lessons/{lessonId}', 'ProductController@updateLesson');
Router::post('/lessons/{lessonId}/delete', 'ProductController@destroyLesson');
Router::post('/lessons/{lessonId}/upload-file', 'ProductController@uploadLessonFile');
Router::post('/lessons/{lessonId}/files', 'ProductController@storeLessonFile');
Router::post('/lesson-files/{fileId}', 'ProductController@updateLessonFile');
Router::post('/lesson-files/{fileId}/delete', 'ProductController@destroyLessonFile');

// Arquivos do Produto (AJAX)
Router::post('/funnels/{funnelId}/products/{productId}/files', 'ProductController@storeProductFile');
Router::post('/product-files/{fileId}', 'ProductController@updateProductFile');
Router::post('/product-files/{fileId}/delete', 'ProductController@destroyProductFile');


// Importação de Membros via XLSX
Router::get('/funnels/{funnelId}/import', 'ImportController@showUpload');
Router::post('/funnels/{funnelId}/import/parse', 'ImportController@parseXlsx');
Router::post('/funnels/{funnelId}/import/process', 'ImportController@processImport');
Router::post('/funnels/{funnelId}/import/batch', 'ImportController@processBatch');

// Duplicação
Router::post('/funnels/{funnelId}/duplicate', 'FunnelController@duplicateFunnel');

// Ofertas (Upsell)
Router::get('/funnels/{funnelId}/offers', 'OfferController@index');
Router::get('/funnels/{funnelId}/offers/create', 'OfferController@create');
Router::post('/funnels/{funnelId}/offers', 'OfferController@store');
Router::get('/funnels/{funnelId}/offers/{offerId}/edit', 'OfferController@edit');
Router::post('/funnels/{funnelId}/offers/{offerId}', 'OfferController@update');
Router::post('/funnels/{funnelId}/offers/{offerId}/delete', 'OfferController@destroy');

// Notificações Push
Router::get('/funnels/{funnelId}/notifications', 'NotificationController@index');
Router::post('/funnels/{funnelId}/notifications/send', 'NotificationController@send');

// === SUPORTE PUBLICO ===
Router::get('/suporte', 'PublicSupportController@index');
Router::post('/suporte/start', 'PublicSupportController@start');
Router::get('/suporte/t/{token}', 'PublicSupportController@ticket');
Router::post('/suporte/t/{token}/message', 'PublicSupportController@message');
Router::get('/suporte/{slug}', 'PublicSupportController@index');
Router::post('/suporte/{slug}/start', 'PublicSupportController@start');

// === ÁREA DO MEMBRO (login público, escopado por slug do funil) ===
Router::get('/m/{slug}', 'MemberAuthController@index'); // Redirecionamento inteligente
Router::get('/m/{slug}/login', 'MemberAuthController@showLogin');
Router::post('/m/{slug}/login', 'MemberAuthController@login');
Router::get('/m/{slug}/logout', 'MemberAuthController@logout');
Router::get('/m/{slug}/api/me', 'MemberAuthController@me'); // API User Data
Router::get('/m/{slug}/dashboard', 'MemberAreaController@dashboard');
Router::get('/m/{slug}/api/offer', 'MemberAreaController@activeOffer'); // API oferta ativa
Router::get('/m/{slug}/manifest.json', 'PwaController@manifest'); // PWA manifest
Router::post('/m/{slug}/api/push/subscribe', 'NotificationController@subscribe'); // Push subscription
Router::get('/m/{slug}/support', 'MemberSupportController@index');
Router::post('/m/{slug}/support', 'MemberSupportController@store');
Router::get('/m/{slug}/support/{id}', 'MemberSupportController@show');
Router::post('/m/{slug}/support/{id}/message', 'MemberSupportController@message');
Router::get('/m/{slug}/product/{id}', 'MemberAreaController@product');
Router::get('/m/{slug}/product/{productId}/lesson/{lessonId}', 'MemberAreaController@lesson');

// === ROTA PÚBLICA (deve ficar por último para não conflitar) ===
Router::get('/{slug}', 'PublicController@show');
