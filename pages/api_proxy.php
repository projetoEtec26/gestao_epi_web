<?php
declare(strict_types=1);

define('IS_API_PROXY', true);

date_default_timezone_set('America/Sao_Paulo');

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

    // Helper para conexão PDO com a base de dados da API em Nuvem (Aiven Cloud)
    $getDb = function(): ?PDO {
        $configData = require __DIR__ . '/../config/api.php';
        $dbHost = $configData['db_host'] ?? 'db-gestao-epi-gestaoepi.a.aivencloud.com';
        $dbPort = $configData['db_port'] ?? '10903';
        $dbName = $configData['db_name'] ?? 'defaultdb';
        $dbUser = $configData['db_user'] ?? 'avnadmin';
        $dbPass = $configData['db_pass'] ?? base64_decode('QVZOU19UMlduaFU3X0RmOE1KMkN2dVcw');

        try {
            $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $dbHost, $dbPort, $dbName);
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_TIMEOUT => 5,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
            ]);
            $pdo->exec("SET time_zone = '-03:00'");
            return $pdo;
        } catch (Throwable $t) {
            return null;
        }
    };

    // 0. Intercepta POST epis/import (Importação em lote de EPIs via CSV)
    if ($method === 'POST' && $route === 'epis/import') {
        if (!is_array($data) || empty($data)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Nenhum dado enviado para importação.']);
            exit;
        }
        
        $itens = isset($data['itens']) && is_array($data['itens']) ? $data['itens'] : $data;
        
        $pdo = $getDb();
        if (!$pdo) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Não foi possível conectar ao banco de dados para a importação.']);
            exit;
        }
        
        $sucessoCount = 0;
        $erros = [];

        $stmt = $pdo->prepare("
            INSERT INTO epis (
                epi_nome, epi_tipo_item, epi_ca, epi_vencimento_ca, epi_fabricante, 
                epi_validade_uso_dias, epi_status, epi_valor, epi_origem_preco, 
                epi_localizacao, epi_vida_util, epi_vida_util_unidade, epi_vida_util_tipo,
                epi_modelo, epi_identificacao, epi_ref_fornecedor, epi_exige_tamanho
            ) VALUES (
                :nome, :tipo, :ca, :venc_ca, :fabricante,
                :validade_dias, :status, :valor, :origem_preco,
                :localizacao, :vida_util, :vida_util_unidade, :vida_util_tipo,
                :modelo, :identificacao, :ref_fornecedor, :exige_tamanho
            )
        ");
        
        $stmtHist = $pdo->prepare("
            INSERT INTO historico_preco_epi (
                epi_id, hist_valor, hist_origem, hist_nota_fiscal, hist_fornecedor, hist_data_vigencia
            ) VALUES (
                :epi_id, :valor, :origem, 'IMPORTAÇÃO INICIAL', :fornecedor, CURDATE()
            )
        ");

        foreach ($itens as $index => $item) {
            try {
                $nome = trim((string)($item['epi_nome'] ?? $item['nome'] ?? ''));
                $fabricante = trim((string)($item['epi_fabricante'] ?? $item['fabricante'] ?? ''));
                
                if ($nome === '') {
                    $erros[] = "Linha " . ($index + 1) . ": Nome do EPI é obrigatório.";
                    continue;
                }
                if ($fabricante === '') {
                    $fabricante = 'NÃO INFORMADO';
                }

                $tipoItem = strtoupper(trim((string)($item['epi_tipo_item'] ?? $item['tipo_item'] ?? 'EPI_COM_CA')));
                if (!in_array($tipoItem, ['EPI_COM_CA', 'ITEM_SEGURANCA_SEM_CA', 'UNIFORME', 'OUTRO'], true)) {
                    $tipoItem = 'EPI_COM_CA';
                }

                $ca = trim((string)($item['epi_ca'] ?? $item['ca'] ?? ''));
                $ca = ($ca !== '' && strtolower($ca) !== 'isento' && strtolower($ca) !== 'sem c.a.' && strtolower($ca) !== 'null') ? $ca : null;

                $vencCa = trim((string)($item['epi_vencimento_ca'] ?? $item['vencimento_ca'] ?? $item['vencimento'] ?? ''));
                if ($vencCa !== '') {
                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $vencCa, $m)) {
                        $vencCa = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencCa)) {
                        $vencCa = null;
                    }
                } else {
                    $vencCa = null;
                }

                $valorRaw = (string)($item['epi_valor'] ?? $item['valor'] ?? $item['preco'] ?? '0');
                $valorStr = preg_replace('/[^0-9,.]/', '', $valorRaw);
                if (strpos($valorStr, ',') !== false && strpos($valorStr, '.') !== false) {
                    $valorStr = str_replace('.', '', $valorStr);
                    $valorStr = str_replace(',', '.', $valorStr);
                } else {
                    $valorStr = str_replace(',', '.', $valorStr);
                }
                $valor = (float)$valorStr;

                $validadeDias = isset($item['epi_validade_uso_dias']) && $item['epi_validade_uso_dias'] !== '' ? (int)$item['epi_validade_uso_dias'] : 365;
                $status = !empty($item['epi_status']) ? trim((string)$item['epi_status']) : 'ATIVO';
                $origemPreco = !empty($item['epi_origem_preco']) ? trim((string)$item['epi_origem_preco']) : 'COMPRA_DIRETA';
                $localizacao = !empty($item['epi_localizacao']) ? trim((string)$item['epi_localizacao']) : 'Estoque Geral';
                $vidaUtil = isset($item['epi_vida_util']) && $item['epi_vida_util'] !== '' ? (int)$item['epi_vida_util'] : 1;
                $vidaUtilUnidade = !empty($item['epi_vida_util_unidade']) ? trim((string)$item['epi_vida_util_unidade']) : 'ANOS';
                $vidaUtilTipo = !empty($item['epi_vida_util_tipo']) ? trim((string)$item['epi_vida_util_tipo']) : 'CONTROLADO';
                $modelo = !empty($item['epi_modelo']) ? trim((string)$item['epi_modelo']) : null;
                $identificacao = !empty($item['epi_identificacao']) ? trim((string)$item['epi_identificacao']) : null;
                $refFornecedor = !empty($item['epi_ref_fornecedor']) ? trim((string)$item['epi_ref_fornecedor']) : null;
                $exigeTamanho = !empty($item['epi_exige_tamanho']) ? 1 : 0;

                $stmt->execute([
                    ':nome' => $nome,
                    ':tipo' => $tipoItem,
                    ':ca' => $ca,
                    ':venc_ca' => $vencCa,
                    ':fabricante' => $fabricante,
                    ':validade_dias' => $validadeDias,
                    ':status' => $status,
                    ':valor' => $valor,
                    ':origem_preco' => $origemPreco,
                    ':localizacao' => $localizacao,
                    ':vida_util' => $vidaUtil,
                    ':vida_util_unidade' => $vidaUtilUnidade,
                    ':vida_util_tipo' => $vidaUtilTipo,
                    ':modelo' => $modelo,
                    ':identificacao' => $identificacao,
                    ':ref_fornecedor' => $refFornecedor,
                    ':exige_tamanho' => $exigeTamanho
                ]);

                $newId = (int)$pdo->lastInsertId();
                if ($newId > 0 && $stmtHist) {
                    try {
                        $stmtHist->execute([
                            ':epi_id' => $newId,
                            ':valor' => $valor,
                            ':origem' => $origemPreco,
                            ':fornecedor' => $fabricante
                        ]);
                    } catch (Throwable $th) {}
                }

                $sucessoCount++;
            } catch (Throwable $e) {
                $erros[] = "Linha " . ($index + 1) . ": Erro ao cadastrar item (" . $e->getMessage() . ")";
            }
        }

        echo json_encode([
            'success' => $sucessoCount > 0,
            'message' => "Importação concluída com sucesso! {$sucessoCount} equipamento(s) cadastrado(s) no catálogo.",
            'imported_count' => $sucessoCount,
            'errors' => $erros
        ]);
        exit;
    }

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
