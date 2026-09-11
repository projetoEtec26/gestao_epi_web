<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Invalida o cache do navegador para que novos deploys reflitam de imediato
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$config = require __DIR__ . '/../config/api.php';
if (!defined('APP_ROOT')) {
    define('APP_ROOT', $config['app_root_url'] ?? '/gestao_epi_web/');
}

// Função helper global para formatação de moeda brasileira (R$) sem dependência da extensão 'intl'
if (!function_exists('formatarValorMonetario')) {
    function formatarValorMonetario(float $valor): string {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
}

// 1. Validação de Sessão Geral
if (!isset($_SESSION['token']) || $_SESSION['token'] === '' || !isset($_SESSION['usuario'])) {
    $_SESSION['error_message'] = 'Por favor, realize o login para acessar o sistema.';
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
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title . ' - Gestão EPI' : 'Gestão EPI' ?></title>
    
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
</head>
<body>
<div id="app-wrapper">
