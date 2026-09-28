<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verifica se o usuário já possui um token de sessão válido
if (isset($_SESSION['token']) && $_SESSION['token'] !== '' && isset($_SESSION['usuario'])) {
    header('Location: pages/dashboard.php');
    exit;
}

// Caso contrário, redireciona para a tela de login
header('Location: login.php');
exit;
