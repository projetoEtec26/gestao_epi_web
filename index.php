<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário já possui um token de sessão válido
if (isset($_SESSION['token']) && $_SESSION['token'] !== '' && isset($_SESSION['usuario'])) {
        $perfil = $_SESSION['usuario']['usu_perfil'] ?? '';
    $homePage = match (strtoupper((string)$perfil)) {
        'ADMINISTRADOR'       => 'pages/usuarios.php',
        'RH_ADMINISTRATIVO'   => 'pages/funcionarios.php',
        'TECNICO_SST'         => 'pages/epis.php',
        'GESTOR'              => 'pages/dashboard.php',
        'ALMOXARIFE_OPERADOR' => 'pages/entregas.php',
        default               => 'pages/entregas.php',
    };
    header('Location: ' . $homePage);
    exit;
}

// Caso contrário, redireciona para a tela de login
header('Location: login.php');
exit;
