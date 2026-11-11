<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil / São Paulo (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/services/ApiService.php';
require_once __DIR__ . '/services/SecurityService.php';

use Services\ApiService;
use Services\SecurityService;

SecurityService::initSecureSession();

try {
    // Se o usuário possui sessão ativa, tenta informar a API para invalidar o token JWT
    if (isset($_SESSION['token']) && $_SESSION['token'] !== '') {
        $api = new ApiService();
        $api->post('auth/logout', []);
    }
} catch (\Throwable $e) {
    // Silencia qualquer falha de rede ao deslogar para garantir que o logout local ocorra
}

// Limpa todas as variáveis de sessão
$_SESSION = [];

// Se desejar destruir o cookie de sessão, faça-o
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destrói a sessão
session_destroy();

// Inicia uma nova sessão apenas para gravar a mensagem de logout com segurança
SecurityService::initSecureSession();
$_SESSION['success_message'] = 'Você foi desconectado com sucesso.';

header('Location: login.php?v=' . time());
exit;
