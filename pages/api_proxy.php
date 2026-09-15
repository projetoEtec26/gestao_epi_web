<?php
declare(strict_types=1);

define('IS_API_PROXY', true);

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
    echo json_encode([
        'success' => false,
        'message' => 'Sessão expirada ou não autorizada. Por favor, faça login novamente.',
        'logged_out' => true,
        'status_code' => 401
    ]);
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

    // Helper para conexão PDO com a base de dados da API (Aiven Cloud)
    $getDb = function(): ?PDO {
        $dbHost = getenv('DB_HOST') ?: 'db-gestao-epi-gestaoepi.a.aivencloud.com';
        $dbPort = getenv('DB_PORT') ?: '10903';
        $dbName = getenv('DB_NAME') ?: 'defaultdb';
        $dbUser = getenv('DB_USER') ?: 'avnadmin';
        $dbPass = getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: '');

        $configFile = 'c:/xampp/htdocs/gestao_epi_api_7/config/config.php';
        if (file_exists($configFile)) {
            try {
                $config = require $configFile;
                $db = $config['db'] ?? [];
                $dbHost = $db['host'] ?? $dbHost;
                $dbPort = $db['port'] ?? $dbPort;
                $dbName = $db['dbname'] ?? $dbName;
                $dbUser = $db['username'] ?? $dbUser;
                $dbPass = $db['password'] ?? $dbPass;
            } catch (Throwable $t) {}
        }

        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $dbHost, $dbPort, $dbName);
            return new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_TIMEOUT => 4,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT
            ]);
        } catch (Throwable $t) {
            return null;
        }
    };

    // 1. Intercepta GET assinaturas/funcionario/{fun_id} (API não implementou showByFuncionario no controller)
    if ($method === 'GET' && preg_match('#^assinaturas/funcionario/(\d+)$#', $route, $matches)) {
        $funId = (int)$matches[1];
        $pdo = $getDb();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT ass_id, fun_id, ass_status, ass_tentativas_falha FROM assinatura_eletronica WHERE fun_id = :fid ORDER BY ass_id DESC LIMIT 1");
            $stmt->execute([':fid' => $funId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Assinatura localizada com sucesso.',
                    'data' => [
                        'ass_id' => (int)$row['ass_id'],
                        'fun_id' => (int)$row['fun_id'],
                        'ass_status' => strtoupper((string)$row['ass_status']),
                        'ass_tentativas_falha' => (int)$row['ass_tentativas_falha']
                    ]
                ]);
                exit;
            }
        }
    }

    // 2. Intercepta POST assinaturas/desbloquear/{id} ou assinaturas/bloquear/{id}
    if ($method === 'POST' && preg_match('#^assinaturas/(desbloquear|bloquear)/(\d+)$#', $route, $matches)) {
        $action = $matches[1];
        $targetId = (int)$matches[2];
        $pdo = $getDb();

        if ($pdo && $targetId > 0) {
            // Busca o registro por fun_id ou ass_id
            $stmt = $pdo->prepare("SELECT ass_id, fun_id, ass_status FROM assinatura_eletronica WHERE fun_id = :id OR ass_id = :id ORDER BY ass_id DESC LIMIT 1");
            $stmt->execute([':id' => $targetId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $realAssId = (int)$row['ass_id'];
                $realFunId = (int)$row['fun_id'];

                if ($action === 'desbloquear') {
                    $uStmt = $pdo->prepare("UPDATE assinatura_eletronica SET ass_status = 'ATIVO', ass_tentativas_falha = 0, ass_data_bloqueio = NULL WHERE ass_id = :aid");
                    $uStmt->execute([':aid' => $realAssId]);

                    $fStmt = $pdo->prepare("UPDATE funcionarios SET fun_situacao = 'ATIVO' WHERE fun_id = :fid");
                    $fStmt->execute([':fid' => $realFunId]);
                } else if ($action === 'bloquear') {
                    $uStmt = $pdo->prepare("UPDATE assinatura_eletronica SET ass_status = 'BLOQUEADO', ass_data_bloqueio = NOW() WHERE ass_id = :aid");
                    $uStmt->execute([':aid' => $realAssId]);
                }

                // Atualiza a rota para chamar a API REST oficial com o ass_id correto
                $route = "assinaturas/{$action}/{$realAssId}";
            }
        }
    }

    // Repassa a requisição dinamicamente para a API
    $res = $api->request($method, $route, $data);
    echo json_encode($res);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro no processamento do proxy: ' . $e->getMessage()]);
}
