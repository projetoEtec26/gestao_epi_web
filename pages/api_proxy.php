<?php
declare(strict_types=1);

/**
 * Proxy Server-Side dinâmico para chamadas à API REST na nuvem.
 * 
 * Intercepta todas as chamadas client-side (GET, POST, PUT, DELETE) e as repassa
 * de servidor para servidor via cURL no PHP, eliminando os problemas de CORS
 * de forma transparente e escalável.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Validação de sessão do painel administrativo
if (!isset($_SESSION['token']) || $_SESSION['token'] === '') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessão expirada ou não autorizada.']);
    exit;
}

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

// Captura a rota da API que queremos chamar
$route = $_GET['route'] ?? '';
if ($route === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rota de destino do proxy ausente.']);
    exit;
}

// Repassa os demais parâmetros de query (filtros, paginação etc.) para a API
$queryParams = $_GET;
unset($queryParams['route']);
if (!empty($queryParams)) {
    $route .= (str_contains($route, '?') ? '&' : '?') . http_build_query($queryParams);
}

try {
    $api = new ApiService();
    $method = $_SERVER['REQUEST_METHOD'];

    // Captura o payload de entrada em caso de POST/PUT
    $data = null;
    if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
        $body = file_get_contents('php://input');
        if (!empty($body)) {
            $data = json_decode($body, true);
        }
    }

    // Repassa a requisição dinamicamente para a API
    $res = $api->request($method, $route, $data);
    echo json_encode($res);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no processamento do proxy: ' . $e->getMessage()]);
}
