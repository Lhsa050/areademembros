<?php
/**
 * Front Controller
 */

// Carrega bootstrap
require_once dirname(__DIR__) . '/bootstrap.php';

// Carrega rotas
require_once ABSPATH . '/routes.php';

// Processa a requisição
use App\Core\Router;
Router::dispatch();
