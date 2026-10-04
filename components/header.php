<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// Invalida o cache do navegador para que novos deploys reflitam de imediato
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$config = require __DIR__ . '/../config/api.php';
if (!defined('APP_ROOT')) {
    define('APP_ROOT', $config['app_root_url'] ?? '/');
}

// Função helper global para formatação de moeda brasileira (R$) sem dependência da extensão 'intl'
if (!function_exists('formatarValorMonetario')) {
    function formatarValorMonetario(float $valor): string {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

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

// 1. Validação de Sessão Geral & Bloqueio Estrito por Troca Obrigatória de Senha
if (!isset($_SESSION['token']) || $_SESSION['token'] === '' || !isset($_SESSION['usuario'])) {
    $_SESSION['error_message'] = 'Por favor, realize o login para acessar o sistema.';
    header('Location: ' . APP_ROOT . 'login.php');
    exit;
}

if (($_SESSION['exige_troca_senha'] ?? false) === true) {
    $_SESSION['error_message'] = 'Troca obrigatória de senha pendente. Por favor, cadastre uma nova senha para continuar.';
    header('Location: ' . APP_ROOT . 'login.php');
    exit;
}

$currentUser = $_SESSION['usuario'];
$userProfile = $currentUser['usu_perfil'] ?? '';

// 2. Validação de Controle de Acesso por Perfil
if (isset($page_roles) && is_array($page_roles)) {
    if (!in_array($userProfile, $page_roles, true)) {
        header('Location: ' . APP_ROOT . 'pages/403.php');
        exit;
    }
}

$isDarkMode = ($_COOKIE['theme-mode'] ?? '') === 'dark';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="<?= $isDarkMode ? 'dark-mode' : '' ?>" style="<?= $isDarkMode ? 'background-color: #0f172a !important; color: #f8fafc !important;' : '' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="<?= $isDarkMode ? '#0f172a' : '#2563eb' ?>">
    <title><?= isset($page_title) ? $page_title . ' - Gestão EPI' : 'Gestão EPI' ?></title>
    
    <!-- Anti-Flicker Instantâneo do Modo Escuro (Executado síncronamente no início do <head> antes do carregamento de CSS da rede) -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme-mode') || (document.cookie.match(/theme-mode=([^;]+)/) || [])[1];
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.style.backgroundColor = '#0f172a';
                document.documentElement.style.color = '#f8fafc';
                document.cookie = 'theme-mode=dark; path=/; max-age=31536000; SameSite=Lax';
            }
        })();
    </script>
    <style id="anti-flicker-dark-style">
        html.dark-mode, 
        html.dark-mode body, 
        html.dark-mode #app-wrapper, 
        html.dark-mode #main-content,
        html.dark-mode .content-body,
        html.dark-mode .settings-container,
        html.dark-mode .settings-card,
        html.dark-mode .welcome-card,
        html.dark-mode .dash-card,
        html.dark-mode .card-custom,
        html.dark-mode .card,
        html.dark-mode .modal-content,
        html.dark-mode #topbar {
            background-color: #0f172a !important;
            color: #f8fafc !important;
        }
        html.dark-mode .settings-card,
        html.dark-mode .welcome-card,
        html.dark-mode .dash-card,
        html.dark-mode .card-custom,
        html.dark-mode .card,
        html.dark-mode .modal-content,
        html.dark-mode #topbar {
            background-color: #1e293b !important;
            border-color: #334155 !important;
        }
        html.dark-mode .text-muted,
        html.dark-mode .text-secondary,
        html.dark-mode small,
        html.dark-mode #topbar .text-muted {
            color: #94a3b8 !important;
        }
        html.dark-mode .text-dark,
        html.dark-mode #topbar .text-dark {
            color: #f8fafc !important;
        }
        html.dark-mode table td,
        html.dark-mode .table td {
            color: #e2e8f0 !important;
        }
        html.dark-mode table th,
        html.dark-mode .table th {
            color: #94a3b8 !important;
            background-color: #0f172a !important;
        }
        /* Desativa transições de background durante navegação para evitar efeito fade branco -> escuro */
        html.dark-mode *,
        html.dark-mode *::before,
        html.dark-mode *::after {
            transition: background-color 0s ease, border-color 0s ease, color 0s ease !important;
        }
    </style>

    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_ROOT ?>assets/favicon.svg">
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Google Fonts (Outfit) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Customizado (Design System, Modo Escuro, Sidebar) -->
    <link rel="stylesheet" href="<?= APP_ROOT ?>assets/css/style.css?v=<?= time() ?>">
    
    <!-- Chart.js CDN (Para Gráficos) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Persistência de Autenticação Client-Side e Logs no Console -->
    <script>
        console.log('[AUTH SESSION ACTIVE] Status HTTP 200 - Usuário: <?= htmlspecialchars($currentUser['usu_login'] ?? '') ?> | Perfil: <?= htmlspecialchars($userProfile) ?>');
        try {
            localStorage.setItem('token', <?= json_encode((string)($_SESSION['token'] ?? '')) ?>);
            localStorage.setItem('usuario', <?= json_encode(json_encode($_SESSION['usuario'] ?? [])) ?>);
        } catch (e) {
            console.error('[AUTH ERROR] Erro ao sincronizar localStorage:', e);
        }
    </script>
</head>
<body class="<?= $isDarkMode ? 'dark-mode' : '' ?>" style="<?= $isDarkMode ? 'background-color: #0f172a !important; color: #f8fafc !important;' : '' ?>">
<div id="app-wrapper">
