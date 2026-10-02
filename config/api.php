<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

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

$appRoot = '/OLD/gestao_epi_web_14/';
if (php_sapi_name() === 'cli-server') {
    $appRoot = '/';
} elseif (!empty($_SERVER['SCRIPT_NAME'])) {
    $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    if (basename($dir) === 'pages' || basename($dir) === 'components' || basename($dir) === 'services') {
        $dir = dirname($dir);
    }
    $dir = str_replace('\\', '/', $dir);
    $appRoot = ($dir === '/' || $dir === '.') ? '/' : rtrim($dir, '/') . '/';
}

// Detecta automaticamente se está no ambiente Local (XAMPP) ou Produção (Locaweb)
$isLocalhost = (
    php_sapi_name() === 'cli' || 
    (isset($_SERVER['HTTP_HOST']) && (
        str_contains($_SERVER['HTTP_HOST'], 'localhost') || 
        str_contains($_SERVER['HTTP_HOST'], '127.0.0.1')
    ))
);

return [
    // URL da API: no XAMPP busca a gestao_epi_api_8, na Locaweb busca a /api/
    'api_base_url' => getenv('API_BASE_URL') ?: (
        $isLocalhost 
            ? 'http://localhost/gestao_epi_api_8/' 
            : 'http://gestaoepi.tecnologia.ws/api/'
    ),
    
    // Banco MySQL oficial da Locaweb
    'db_host' => getenv('DB_HOST') ?: 'db_gestao_epi.mysql.dbaas.com.br',
    'db_port' => getenv('DB_PORT') ?: '3306',
    'db_name' => getenv('DB_NAME') ?: 'db_gestao_epi',
    'db_user' => getenv('DB_USER') ?: 'db_gestao_epi',
    'db_pass' => getenv('DB_PASS') ?: 'Gestaoepi@1',

    // Raiz da aplicação Web-PHP
    'app_root_url' => getenv('APP_ROOT_URL') ?: $appRoot
];