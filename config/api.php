<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

/**
 * Configuração da URL Base da API do ecossistema Gestão EPI.
 * 
 * Por padrão, conecta-se à API em nuvem na Render.
 * Para testar no ambiente local do XAMPP, altere para: 'http://localhost/gestao_epi_api/'
 */
$appRoot = '/gestao_epi_web_2/';
if (php_sapi_name() === 'cli-server') {
    $appRoot = '/';
} elseif (!empty($_SERVER['SCRIPT_NAME'])) {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (basename($dir) === 'pages' || basename($dir) === 'components' || basename($dir) === 'services') {
        $dir = dirname($dir);
    }
    $dir = str_replace('\\', '/', $dir);
    $appRoot = ($dir === '/' || $dir === '.') ? '/' : rtrim($dir, '/') . '/';
} elseif (!empty($_SERVER['REQUEST_URI'])) {
    if (strpos($_SERVER['REQUEST_URI'], '/gestao_epi_web_2') === 0) {
        $appRoot = '/gestao_epi_web_2/';
    } elseif (strpos($_SERVER['REQUEST_URI'], '/gestao_epi_web') === 0) {
        $appRoot = '/gestao_epi_web/';
    } else {
        $appRoot = '/';
    }
}

return [
    'api_base_url' => getenv('API_BASE_URL') ?: 'https://gestao-epi-api.onrender.com/',
    
    // Raiz da aplicação Web-PHP calculada dinamicamente
    'app_root_url' => getenv('APP_ROOT_URL') ?: $appRoot
];


