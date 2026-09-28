<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

// Funções helper globais para conversão automática de datas e horários em UTC para São Paulo/Brasil (America/Sao_Paulo)
if (!function_exists('formatarDataHoraBr')) {
    function formatarDataHoraBr(?string $datetimeStr, string $format = 'd/m/Y H:i'): string {
        if (empty($datetimeStr) || $datetimeStr === '0000-00-00 00:00:00') {
            return '---';
        }
        try {
            $dt = new DateTime((string)$datetimeStr, new DateTimeZone('UTC'));
            $dt->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            return $dt->format($format);
        } catch (\Throwable $e) {
            $ts = strtotime((string)$datetimeStr);
            return $ts ? date($format, $ts) : (string)$datetimeStr;
        }
    }
}

if (!function_exists('formatarDataBr')) {
    function formatarDataBr(?string $dateStr): string {
        if (empty($dateStr) || $dateStr === '0000-00-00') {
            return '---';
        }
        try {
            $dt = new DateTime((string)$dateStr, new DateTimeZone('America/Sao_Paulo'));
            return $dt->format('d/m/Y');
        } catch (\Throwable $e) {
            $ts = strtotime((string)$dateStr);
            return $ts ? date('d/m/Y', $ts) : (string)$dateStr;
        }
    }
}

if (!function_exists('normalizarTextoPHP')) {
    function normalizarTextoPHP(string $str): string {
        $str = mb_strtolower($str, 'UTF-8');
        $comAcento = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
        $semAcento = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        return str_replace($comAcento, $semAcento, $str);
    }
}

if (!function_exists('normalizarParaOrdenacaoPHP')) {
    function normalizarParaOrdenacaoPHP(string $str): string {
        return normalizarTextoPHP($str);
    }
}

/**
 * Configuração da URL Base da API do ecossistema Gestão EPI.
 * 
 * Por padrão, conecta-se à API em nuvem na Render.
 * Para testar no ambiente local do XAMPP, altere para: 'http://localhost/gestao_epi_api/'
 */
$appRoot = '/';
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
    $uriPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '';
    if (preg_match('#^(/[^/]+/)#', $uriPath, $matches)) {
        $appRoot = $matches[1];
    } else {
        $appRoot = '/';
    }
}

return [
    // API REST: Seleciona automaticamente a API local (18ms) quando em localhost/XAMPP, ou a Nuvem (Render) em produção
    'api_base_url' => getenv('API_BASE_URL') ?: (
        (php_sapi_name() === 'cli' || (isset($_SERVER['HTTP_HOST']) && (str_contains($_SERVER['HTTP_HOST'], 'localhost') || str_contains($_SERVER['HTTP_HOST'], '127.0.0.1'))))
        ? (
            (isset($_SERVER['REQUEST_URI']) && str_contains($_SERVER['REQUEST_URI'], '/OLD/')) || (isset($_SERVER['SCRIPT_NAME']) && str_contains($_SERVER['SCRIPT_NAME'], '/OLD/'))
            ? 'http://localhost/OLD/gestao_epi_api_7/'
            : 'http://localhost/gestao_epi_api_7/'
          )
        : 'https://gestao-epi-api.onrender.com/'
    ),
    
    // Banco de Dados em Nuvem (Aiven Cloud MySQL)
    'db_host' => getenv('DB_HOST') ?: 'db-gestao-epi-gestaoepi.a.aivencloud.com',
    'db_port' => getenv('DB_PORT') ?: '10903',
    'db_name' => getenv('DB_NAME') ?: 'defaultdb',
    'db_user' => getenv('DB_USER') ?: 'avnadmin',
    'db_pass' => getenv('DB_PASS') ?: base64_decode('QVZOU19UMlduaFU3X0RmOE1KMkN2dVcw'),

    // Raiz da aplicação Web-PHP calculada dinamicamente
    'app_root_url' => getenv('APP_ROOT_URL') ?: $appRoot
];


