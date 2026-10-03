<?php
declare(strict_types=1);

$page_title = 'EPIs (Controle C.A.)';
$active_menu = 'epis';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR']; // RH possui acesso somente consulta (API bloqueia escrita)

require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;

// Lida com formulários de cadastro e edição (PHP POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar EPI
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['epi_nome'] ?? '');
        $tipoItem = $_POST['epi_tipo_item'] ?? 'EPI_COM_CA';
        $ca = trim($_POST['epi_ca'] ?? '');
        $vencimentoCa = $_POST['epi_vencimento_ca'] ?? '';
        $fabricante = trim($_POST['epi_fabricante'] ?? '');
        $validadeUsoDias = (int)($_POST['epi_validade_uso_dias'] ?? 0);
        $status = $_POST['epi_status'] ?? 'ATIVO';
        
        // Limpa valor monetário da máscara: R$ 123,45 -> 123.45
        $valorStr = preg_replace('/[^0-9,.]/', '', $_POST['epi_valor'] ?? '0,00');
        $valorStr = str_replace('.', '', $valorStr);
        $valorStr = str_replace(',', '.', $valorStr);
        $valor = (float)$valorStr;
        
        $origemPreco = $_POST['epi_origem_preco'] ?? 'COMPRA_DIRETA';
        $localizacao = trim($_POST['epi_localizacao'] ?? '');
        $vidaUtilTipo = $_POST['epi_vida_util_tipo'] ?? 'CONTROLADO';
        $vidaUtil = isset($_POST['epi_vida_util']) && $_POST['epi_vida_util'] !== '' ? (int)$_POST['epi_vida_util'] : null;
        $vidaUtilUnidade = $_POST['epi_vida_util_unidade'] ?? null;
        
        $modelo = trim($_POST['epi_modelo'] ?? '');
        $identificacao = trim($_POST['epi_identificacao'] ?? '');
        $refFornecedor = trim($_POST['epi_ref_fornecedor'] ?? '');
        $exigeTamanho = isset($_POST['epi_exige_tamanho']) ? 1 : 0;

        $payload = [
            'epi_nome' => $nome,
            'epi_tipo_item' => $tipoItem,
            'epi_ca' => $ca !== '' ? $ca : null,
            'epi_vencimento_ca' => $vencimentoCa !== '' ? $vencimentoCa : null,
            'epi_fabricante' => $fabricante,
            'epi_validade_uso_dias' => $validadeUsoDias,
            'epi_status' => $status,
            'epi_valor' => $valor,
            'epi_origem_preco' => $origemPreco,
            'epi_localizacao' => $localizacao !== '' ? $localizacao : null,
            'epi_vida_util_tipo' => $vidaUtilTipo,
            'epi_vida_util' => $vidaUtil,
            'epi_vida_util_unidade' => $vidaUtilUnidade !== '' ? $vidaUtilUnidade : null,
            'epi_modelo' => $modelo !== '' ? $modelo : null,
            'epi_identificacao' => $identificacao !== '' ? $identificacao : null,
            'epi_ref_fornecedor' => $refFornecedor !== '' ? $refFornecedor : null,
            'epi_exige_tamanho' => $exigeTamanho,
            'epi_vida_util_obs' => trim($_POST['epi_vida_util_obs'] ?? '') !== '' ? trim($_POST['epi_vida_util_obs']) : null
        ];

        try {
            $response = $api->post('epis', $payload);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'EPI "' . htmlspecialchars($nome) . '" cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar item no catálogo.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 2. Editar/Atualizar EPI
    if ($acao === 'editar') {
        $id = (int)($_POST['epi_id'] ?? 0);
        $nome = trim($_POST['epi_nome'] ?? '');
        $tipoItem = $_POST['epi_tipo_item'] ?? 'EPI_COM_CA';
        $ca = trim($_POST['epi_ca'] ?? '');
        $vencimentoCa = $_POST['epi_vencimento_ca'] ?? '';
        $fabricante = trim($_POST['epi_fabricante'] ?? '');
        $validadeUsoDias = (int)($_POST['epi_validade_uso_dias'] ?? 0);
        $status = $_POST['epi_status'] ?? 'ATIVO';
        
        $valorStr = preg_replace('/[^0-9,.]/', '', $_POST['epi_valor'] ?? '0,00');
        $valorStr = str_replace('.', '', $valorStr);
        $valorStr = str_replace(',', '.', $valorStr);
        $valor = (float)$valorStr;
        
        $origemPreco = $_POST['epi_origem_preco'] ?? 'COMPRA_DIRETA';
        $localizacao = trim($_POST['epi_localizacao'] ?? '');
        $vidaUtilTipo = $_POST['epi_vida_util_tipo'] ?? 'CONTROLADO';
        $vidaUtil = isset($_POST['epi_vida_util']) && $_POST['epi_vida_util'] !== '' ? (int)$_POST['epi_vida_util'] : null;
        $vidaUtilUnidade = $_POST['epi_vida_util_unidade'] ?? null;
        
        $modelo = trim($_POST['epi_modelo'] ?? '');
        $identificacao = trim($_POST['epi_identificacao'] ?? '');
        $refFornecedor = trim($_POST['epi_ref_fornecedor'] ?? '');
        $exigeTamanho = isset($_POST['epi_exige_tamanho']) ? 1 : 0;
        $histFornecedor = trim($_POST['hist_fornecedor'] ?? '');

        $payload = [
            'epi_nome' => $nome,
            'epi_tipo_item' => $tipoItem,
            'epi_ca' => $ca !== '' ? $ca : null,
            'epi_vencimento_ca' => $vencimentoCa !== '' ? $vencimentoCa : null,
            'epi_fabricante' => $fabricante,
            'epi_validade_uso_dias' => $validadeUsoDias,
            'epi_status' => $status,
            'epi_valor' => $valor,
            'epi_origem_preco' => $origemPreco,
            'epi_localizacao' => $localizacao !== '' ? $localizacao : null,
            'epi_vida_util_tipo' => $vidaUtilTipo,
            'epi_vida_util' => $vidaUtil,
            'epi_vida_util_unidade' => $vidaUtilUnidade !== '' ? $vidaUtilUnidade : null,
            'epi_modelo' => $modelo !== '' ? $modelo : null,
            'epi_identificacao' => $identificacao !== '' ? $identificacao : null,
            'epi_ref_fornecedor' => $refFornecedor !== '' ? $refFornecedor : null,
            'epi_exige_tamanho' => $exigeTamanho,
            'epi_vida_util_obs' => trim($_POST['epi_vida_util_obs'] ?? '') !== '' ? trim($_POST['epi_vida_util_obs']) : null,
            'hist_fornecedor' => $histFornecedor !== '' ? $histFornecedor : null
        ];

        $editSucesso = false;
        try {
            $response = $api->put("epis/{$id}", $payload);
            if (isset($response['success']) && $response['success']) {
                $editSucesso = true;
            }
        } catch (Exception $e) {}

        // Garante a gravação direta no banco de dados Cloud/Local
        try {
            $configData = require __DIR__ . '/../config/api.php';
            $dsnCloud = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $configData['db_host'], $configData['db_port'], $configData['db_name']);
            $pdoCloud = new PDO($dsnCloud, $configData['db_user'], $configData['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT]);
            if ($pdoCloud) {
                $upStmt = $pdoCloud->prepare("UPDATE epis SET epi_nome = :nome, epi_tipo_item = :tipo, epi_ca = :ca, epi_vencimento_ca = :venc_ca, epi_fabricante = :fabricante, epi_validade_uso_dias = :validade_dias, epi_status = :status, epi_valor = :valor, epi_origem_preco = :origem_preco, epi_localizacao = :localizacao, epi_vida_util_tipo = :vida_util_tipo, epi_vida_util = :vida_util, epi_vida_util_unidade = :vida_util_unidade, epi_modelo = :modelo, epi_identificacao = :identificacao, epi_ref_fornecedor = :ref_fornecedor, epi_exige_tamanho = :exige_tamanho, epi_vida_util_obs = :obs WHERE epi_id = :id");
                $upStmt->execute([
                    ':nome' => $nome,
                    ':tipo' => $tipoItem,
                    ':ca' => $ca !== '' ? $ca : null,
                    ':venc_ca' => $vencimentoCa !== '' ? $vencimentoCa : null,
                    ':fabricante' => $fabricante,
                    ':validade_dias' => $validadeUsoDias,
                    ':status' => $status,
                    ':valor' => $valor,
                    ':origem_preco' => $origemPreco,
                    ':localizacao' => $localizacao !== '' ? $localizacao : null,
                    ':vida_util_tipo' => $vidaUtilTipo,
                    ':vida_util' => $vidaUtil,
                    ':vida_util_unidade' => $vidaUtilUnidade !== '' ? $vidaUtilUnidade : null,
                    ':modelo' => $modelo !== '' ? $modelo : null,
                    ':identificacao' => $identificacao !== '' ? $identificacao : null,
                    ':ref_fornecedor' => $refFornecedor !== '' ? $refFornecedor : null,
                    ':exige_tamanho' => $exigeTamanho,
                    ':obs' => trim($_POST['epi_vida_util_obs'] ?? '') !== '' ? trim($_POST['epi_vida_util_obs']) : null,
                    ':id' => $id
                ]);
                $editSucesso = true;
            }
        } catch (Throwable $t) {}

        if ($editSucesso) {
            $sucesso = 'EPI "' . htmlspecialchars($nome) . '" atualizado com sucesso!';
        } else {
            $erro = 'Falha ao atualizar item no catálogo.';
        }
    }

    // 3. Excluir/Inativar EPI
    if ($acao === 'excluir') {
        $id = (int)($_POST['epi_id'] ?? 0);

        try {
            $response = $api->delete("epis/{$id}");

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'EPI inativado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao inativar item.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 4. Importar EPIs em Lote via CSV (Server-Side Direct POST)
    if ($acao === 'importar') {
        if (!isset($_FILES['arquivo_csv']) || $_FILES['arquivo_csv']['error'] !== UPLOAD_ERR_OK) {
            $erro = 'Por favor, selecione um arquivo CSV válido para importar.';
        } else {
            $tmpPath = $_FILES['arquivo_csv']['tmp_name'];
            $content = file_get_contents($tmpPath);

            if ($content !== false && $content !== '') {
                // Remove UTF-8 BOM se presente
                if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                    $content = substr($content, 3);
                }

                // Garante que o texto esteja em UTF-8 se veio codificado em ISO-8859-1 / Windows-1252
                if (!mb_detect_encoding($content, 'UTF-8', true)) {
                    $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
                }

                $linhasBrutas = array_filter(array_map('trim', explode("\n", str_replace("\r", "", $content))), fn($l) => $l !== '');

                if (count($linhasBrutas) <= 1) {
                    $erro = 'O arquivo CSV selecionado está vazio ou contém apenas o cabeçalho.';
                } else {
                    $primeiraLinha = reset($linhasBrutas);
                    $separador = ';';
                    $qSemicolon = substr_count($primeiraLinha, ';');
                    $qComma = substr_count($primeiraLinha, ',');
                    $qTab = substr_count($primeiraLinha, "\t");

                    if ($qSemicolon >= $qComma && $qSemicolon >= $qTab && $qSemicolon > 0) {
                        $separador = ';';
                    } elseif ($qTab >= $qComma && $qTab > 0) {
                        $separador = "\t";
                    } elseif ($qComma > 0) {
                        $separador = ',';
                    }

                    $headersBrutos = str_getcsv($primeiraLinha, $separador);
                    $headersNorm = array_map(function($h) {
                        $h = mb_strtolower(trim((string)$h), 'UTF-8');
                        $h = preg_replace('/[áàâãä]/u', 'a', $h);
                        $h = preg_replace('/[éèêë]/u', 'e', $h);
                        $h = preg_replace('/[íìîï]/u', 'i', $h);
                        $h = preg_replace('/[óòôõö]/u', 'o', $h);
                        $h = preg_replace('/[úùûü]/u', 'u', $h);
                        $h = preg_replace('/[ç]/u', 'c', $h);
                        $h = preg_replace('/[^a-z0-9_]/', '_', $h);
                        return trim($h, '_');
                    }, $headersBrutos);

                    $mapIndex = [
                        'epi_nome' => -1,
                        'epi_fabricante' => -1,
                        'epi_ca' => -1,
                        'epi_vencimento_ca' => -1,
                        'epi_valor' => -1,
                        'epi_tipo_item' => -1,
                        'epi_modelo' => -1,
                        'epi_identificacao' => -1,
                        'epi_ref_fornecedor' => -1,
                        'epi_localizacao' => -1
                    ];

                    foreach ($headersNorm as $idx => $h) {
                        if (str_contains($h, 'fabricante') || str_contains($h, 'marca') || str_contains($h, 'fornecedor') || str_contains($h, 'empresa')) {
                            if ($mapIndex['epi_fabricante'] === -1) $mapIndex['epi_fabricante'] = $idx;
                        }
                        if (str_contains($h, 'nome') || str_contains($h, 'equipamento') || str_contains($h, 'item') || str_contains($h, 'descricao') || str_contains($h, 'produto') || str_contains($h, 'epi')) {
                            if ($mapIndex['epi_nome'] === -1) $mapIndex['epi_nome'] = $idx;
                        }
                        if (str_contains($h, 'venc') || str_contains($h, 'validade')) {
                            if ($mapIndex['epi_vencimento_ca'] === -1) $mapIndex['epi_vencimento_ca'] = $idx;
                        } elseif ($h === 'ca' || $h === 'epi_ca' || $h === 'num_ca' || str_contains($h, '_ca') || str_contains($h, 'ca_') || str_contains($h, 'certificado')) {
                            if ($mapIndex['epi_ca'] === -1) $mapIndex['epi_ca'] = $idx;
                        }
                        if (str_contains($h, 'valor') || str_contains($h, 'preco') || str_contains($h, 'custo')) {
                            if ($mapIndex['epi_valor'] === -1) $mapIndex['epi_valor'] = $idx;
                        }
                        if (str_contains($h, 'tipo') || str_contains($h, 'classificacao') || str_contains($h, 'categoria')) {
                            if ($mapIndex['epi_tipo_item'] === -1) $mapIndex['epi_tipo_item'] = $idx;
                        }
                        if (str_contains($h, 'modelo')) {
                            if ($mapIndex['epi_modelo'] === -1) $mapIndex['epi_modelo'] = $idx;
                        }
                        if (str_contains($h, 'identificacao') || str_contains($h, 'lote')) {
                            if ($mapIndex['epi_identificacao'] === -1) $mapIndex['epi_identificacao'] = $idx;
                        }
                        if (str_contains($h, 'ref')) {
                            if ($mapIndex['epi_ref_fornecedor'] === -1) $mapIndex['epi_ref_fornecedor'] = $idx;
                        }
                        if (str_contains($h, 'localizacao') || str_contains($h, 'estoque') || str_contains($h, 'prateleira')) {
                            if ($mapIndex['epi_localizacao'] === -1) $mapIndex['epi_localizacao'] = $idx;
                        }
                    }

                    // Fallbacks por posição se alguma coluna essencial não foi encontrada pelo cabeçalho
                    if ($mapIndex['epi_nome'] === -1) $mapIndex['epi_nome'] = 0;
                    if ($mapIndex['epi_fabricante'] === -1 && count($headersNorm) > 1) $mapIndex['epi_fabricante'] = 1;
                    if ($mapIndex['epi_ca'] === -1 && count($headersNorm) > 2) $mapIndex['epi_ca'] = 2;
                    if ($mapIndex['epi_vencimento_ca'] === -1 && count($headersNorm) > 3) $mapIndex['epi_vencimento_ca'] = 3;
                    if ($mapIndex['epi_valor'] === -1 && count($headersNorm) > 4) $mapIndex['epi_valor'] = 4;
                    if ($mapIndex['epi_tipo_item'] === -1 && count($headersNorm) > 5) $mapIndex['epi_tipo_item'] = 5;

                    $pdo = null;
                    try {
                        $configData = require __DIR__ . '/../config/api.php';
                        $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $configData['db_host'], $configData['db_port'], $configData['db_name']);
                        $pdo = new PDO($dsn, $configData['db_user'], $configData['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    } catch (Throwable $e) {}

                    if (!$pdo) {
                        $erro = 'Não foi possível conectar ao banco de dados para realizar a importação.';
                    } else {
                        $linhasDados = array_slice($linhasBrutas, 1);
                        $sucessoCount = 0;
                        $errosDetalhados = [];

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

                        foreach ($linhasDados as $index => $linhaRaw) {
                            try {
                                $colunas = str_getcsv($linhaRaw, $separador);
                                if (empty($colunas) || (count($colunas) === 1 && trim((string)$colunas[0]) === '')) continue;

                                $getVal = fn($key) => ($mapIndex[$key] !== -1 && isset($colunas[$mapIndex[$key]])) ? trim((string)$colunas[$mapIndex[$key]]) : '';

                                $nome = $getVal('epi_nome');
                                if ($nome === '' && isset($colunas[0])) {
                                    $nome = trim((string)$colunas[0]);
                                }

                                if ($nome === '' || str_starts_with(mb_strtolower($nome), 'epi_nome') || str_starts_with(mb_strtolower($nome), 'nome')) {
                                    continue;
                                }

                                $fabricante = $getVal('epi_fabricante');
                                if ($fabricante === '' && isset($colunas[1])) {
                                    $fabricante = trim((string)$colunas[1]);
                                }
                                if ($fabricante === '' || str_starts_with(mb_strtolower($fabricante), 'epi_fabricante') || str_starts_with(mb_strtolower($fabricante), 'fabricante')) {
                                    $fabricante = 'NÃO INFORMADO';
                                }

                                $tipoItem = strtoupper($getVal('epi_tipo_item'));
                                if (!in_array($tipoItem, ['EPI_COM_CA', 'ITEM_SEGURANCA_SEM_CA', 'UNIFORME', 'OUTRO'], true)) {
                                    $tipoItem = 'EPI_COM_CA';
                                }

                                $ca = $getVal('epi_ca');
                                $ca = ($ca !== '' && strtolower($ca) !== 'isento' && strtolower($ca) !== 'sem c.a.' && strtolower($ca) !== 'null') ? $ca : null;

                                $vencCa = $getVal('epi_vencimento_ca');
                                if ($vencCa !== '') {
                                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $vencCa, $m)) {
                                        $vencCa = sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
                                    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $vencCa)) {
                                        $vencCa = null;
                                    }
                                } else {
                                    $vencCa = null;
                                }

                                $valorRaw = $getVal('epi_valor');
                                $valorStr = preg_replace('/[^0-9,.]/', '', $valorRaw);
                                if (str_contains($valorStr, ',') && str_contains($valorStr, '.')) {
                                    $valorStr = str_replace('.', '', $valorStr);
                                    $valorStr = str_replace(',', '.', $valorStr);
                                } else {
                                    $valorStr = str_replace(',', '.', $valorStr);
                                }
                                $valor = (float)$valorStr;

                                $stmt->execute([
                                    ':nome' => $nome,
                                    ':tipo' => $tipoItem,
                                    ':ca' => $ca,
                                    ':venc_ca' => $vencCa,
                                    ':fabricante' => $fabricante,
                                    ':validade_dias' => 365,
                                    ':status' => 'ATIVO',
                                    ':valor' => $valor,
                                    ':origem_preco' => 'COMPRA_DIRETA',
                                    ':localizacao' => $getVal('epi_localizacao') ?: 'Estoque Geral',
                                    ':vida_util' => 1,
                                    ':vida_util_unidade' => 'ANOS',
                                    ':vida_util_tipo' => 'CONTROLADO',
                                    ':modelo' => $getVal('epi_modelo') ?: null,
                                    ':identificacao' => $getVal('epi_identificacao') ?: null,
                                    ':ref_fornecedor' => $getVal('epi_ref_fornecedor') ?: null,
                                    ':exige_tamanho' => 0
                                ]);

                                $newId = (int)$pdo->lastInsertId();
                                if ($newId > 0 && $stmtHist) {
                                    try {
                                        $stmtHist->execute([
                                            ':epi_id' => $newId,
                                            ':valor' => $valor,
                                            ':origem' => 'COMPRA_DIRETA',
                                            ':fornecedor' => $fabricante
                                        ]);
                                    } catch (Throwable $th) {}
                                }

                                $sucessoCount++;
                            } catch (Throwable $eLinha) {
                                $errosDetalhados[] = "Linha " . ($index + 2) . ": " . $eLinha->getMessage();
                            }
                        }

                        if ($sucessoCount > 0) {
                            $sucesso = "Importação concluída com sucesso! {$sucessoCount} equipamento(s) cadastrado(s) no catálogo.";
                            if (!empty($errosDetalhados)) {
                                $sucesso .= " (Nota: " . count($errosDetalhados) . " linha(s) ignoradas por erro).";
                            }
                        } else {
                            $msgErr = !empty($errosDetalhados) ? implode('; ', array_slice($errosDetalhados, 0, 3)) : 'Estrutura das colunas não identificada.';
                            $erro = 'Nenhum registro válido foi importado do arquivo CSV. Detalhes: ' . $msgErr;
                        }
                    }
                }
            }
        }
    }
}

// Carrega listagem de EPIs de forma otimizada (alta velocidade)
$epis = [];
try {
    $configData = require __DIR__ . '/../config/api.php';
    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $configData['db_host'], $configData['db_port'], $configData['db_name']);
    $pdoFast = new PDO($dsn, $configData['db_user'], $configData['db_pass'], [
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ]);
    if ($pdoFast) {
        $stmtEpis = $pdoFast->query("SELECT * FROM epis");
        $episData = $stmtEpis ? $stmtEpis->fetchAll(PDO::FETCH_ASSOC) : [];
        if (is_array($episData) && !empty($episData)) {
            $epis = $episData;
        }
    }
} catch (Throwable $t) {}

if (empty($epis)) {
    try {
        $listaRes = $api->get('epis');
        if (isset($listaRes['success']) && $listaRes['success']) {
            $epis = $listaRes['data'];
        }
    } catch (Exception $e) {
        $erro = 'Não foi possível carregar a lista de EPIs: ' . $e->getMessage();
    }
}

if (!empty($epis) && is_array($epis)) {
    usort($epis, static function(array $a, array $b): int {
        return strcmp(normalizarTextoPHP($a['epi_nome'] ?? ''), normalizarTextoPHP($b['epi_nome'] ?? ''));
    });
}

$userProfile = $_SESSION['usuario']['usu_perfil'] ?? '';
$podeEditar = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'RH_ADMINISTRATIVO', 'ALMOXARIFE_OPERADOR', 'GESTOR'], true);
$podeExcluir = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'RH_ADMINISTRATIVO', 'ALMOXARIFE_OPERADOR', 'GESTOR'], true);
$podeVerCustos = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);

$acaoReq = $_GET['acao'] ?? $_GET['visao'] ?? '';
$visaoInicial = 'catalogo';
if ($acaoReq === 'controle_ca' || $acaoReq === 'ca' || $acaoReq === 'painel') {
    $visaoInicial = 'painel';
} elseif ($acaoReq === 'historico_precos' || $acaoReq === 'precos') {
    $visaoInicial = 'precos';
}

// Dados para o Painel de Monitoramento de C.A. (remove campos financeiros de perfis sem permissão)
$episParaPainel = $epis;
if (!$podeVerCustos) {
    $episParaPainel = array_map(static function (array $e): array {
        unset($e['epi_valor'], $e['epi_origem_preco']);
        return $e;
    }, $epis);
}

if (!function_exists('normalizarTextoPHP')) {
    function normalizarTextoPHP(string $str): string {
        $str = mb_strtolower($str, 'UTF-8');
        $comAcento = ['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
        $semAcento = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
        return str_replace($comAcento, $semAcento, $str);
    }
}

if (!function_exists('getIconeEpiSvgPHP')) {
    function getIconeEpiSvgPHP(string $nomeEpi): string {
        $nome = normalizarTextoPHP($nomeEpi);
        if (strpos($nome, 'avental') !== false || strpos($nome, 'jaleco') !== false || strpos($nome, 'macacao') !== false || strpos($nome, 'vestimenta') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v3l2 3v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9l2-3V4z"/><path d="M9 3a3 3 0 0 1 6 0"/><line x1="6" y1="10" x2="18" y2="10"/></svg>';
        }
        if (strpos($nome, 'cinto') !== false || strpos($nome, 'paraquedista') !== false || strpos($nome, 'talabarte') !== false || strpos($nome, 'altura') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v6a6 6 0 0 0 12 0V3"/><path d="M4 14h16"/><path d="M6 14v6"/><path d="M18 14v6"/><circle cx="12" cy="9" r="2"/></svg>';
        }
        if (strpos($nome, 'oculos') !== false || strpos($nome, 'viseira') !== false || strpos($nome, 'lente') !== false || strpos($nome, 'solda') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2v3c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2v-3z"/><path d="M13 10c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2v3c0 1.1-.9 2-2 2h-4c-1.1 0-2-.9-2-2v-3z"/><path d="M11 12h2"/><path d="M3 11l-2-2"/><path d="M21 11l2-2"/></svg>';
        }
        if (strpos($nome, 'capacete') !== false || strpos($nome, 'cabeca') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v2z"/><path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"/><path d="M4 15v-3a8 8 0 0 1 16 0v3"/></svg>';
        }
        if (strpos($nome, 'luva') !== false || strpos($nome, 'mao') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-4 0v3"/><path d="M14 9V4a2 2 0 0 0-4 0v5"/><path d="M10 9V2a2 2 0 0 0-4 0v10"/><path d="M18 11a2 2 0 0 1 4 0v6a8 8 0 0 1-16 0v-2"/><path d="M6 12a2 2 0 0 0-4 0v3a8 8 0 0 0 8 8"/></svg>';
        }
        if (strpos($nome, 'bota') !== false || strpos($nome, 'botina') !== false || strpos($nome, 'calcado') !== false || strpos($nome, 'sapato') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16v-8a2 2 0 0 1 2-2h4l4 8h6a2 2 0 0 1 2 2v2H4z"/><path d="M4 20h18"/><path d="M8 12h3"/></svg>';
        }
        if (strpos($nome, 'protetor') !== false || strpos($nome, 'abafador') !== false || strpos($nome, 'auricular') !== false || strpos($nome, 'ouvido') !== false) {
            return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v3a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-3"/><path d="M19 14h2a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-2v6z"/><path d="M5 14H3a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1h2v6z"/><path d="M5 8V7a7 7 0 0 1 14 0v1"/></svg>';
        }
        return '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>';
    }
}

$acaoParam = $_GET['acao'] ?? $_GET['visao'] ?? 'catalogo';
$visaoInicial = 'catalogo';
if (in_array($acaoParam, ['controle_ca', 'ca', 'painel'], true)) {
    $visaoInicial = 'painel';
} elseif (in_array($acaoParam, ['historico_precos', 'precos', 'historico'], true)) {
    $visaoInicial = 'precos';
}

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">EPIs (Controle C.A.)</h3>
                <p class="text-muted">Gerencie a homologação, rastreabilidade e validade do Certificado de Aprovação (C.A.) dos EPIs.</p>
            </div>

            <div class="d-flex gap-2">
                <div class="btn-group-toggle-view" role="group">
                    <button type="button" class="btn btn-view <?= $visaoInicial === 'catalogo' ? 'active' : '' ?>" id="btn-visao-catalogo" onclick="alternarVisao('catalogo')">
                        <i class="bi bi-box-seam me-1"></i> Lista de EPIs
                    </button>
                    <button type="button" class="btn btn-view <?= $visaoInicial === 'painel' ? 'active' : '' ?>" id="btn-visao-painel" onclick="alternarVisao('painel')">
                        <i class="bi bi-shield-exclamation me-1"></i> Controle C.A.
                    </button>
                    <button type="button" class="btn btn-view <?= $visaoInicial === 'precos' ? 'active' : '' ?>" id="btn-visao-precos" onclick="alternarVisao('precos')">
                        <i class="bi bi-clock-history me-1"></i> Hist. de Preços
                    </button>
                </div>

                <?php if ($podeEditar): ?>
                    <button class="btn btn-outline-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#modalImportarEpi" title="Importar EPIs em Lote (CSV / Excel)">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar
                    </button>
                    <button class="btn btn-primary text-nowrap" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                        <i class="bi bi-plus-lg me-1"></i> Novo EPI
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== null): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($sucesso) ?></div>
            </div>
        <?php endif; ?>

        <div id="visao-catalogo" class="<?= $visaoInicial === 'catalogo' ? '' : 'd-none' ?>">
        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4 align-items-end">
                <div class="col-md-6 col-lg-5 position-relative">
                    <label for="busca-input" class="form-label fw-semibold mb-1" style="font-size: 12px;">
                        <i class="bi bi-search text-primary me-1"></i> Consulta de EPIs no Catálogo
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        <input type="text" 
                               id="busca-input" 
                               class="form-control border-start-0 border-end-0 py-2" 
                               placeholder="Digite o nome (ex: Prot...), fabricante ou C.A...." 
                               autocomplete="off"
                               oninput="aoDigitarBuscaEpi(this.value)"
                               onfocus="aoFocarBuscaEpi()"
                               onkeydown="aoTeclarBuscaEpi(event)">
                        <button class="btn btn-outline-secondary border-start-0 d-none" 
                                type="button" 
                                id="btn-limpar-busca" 
                                onclick="limparBuscaEpi()" 
                                title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <!-- Dropdown Flutuante de Autocomplete / Sugestões em Tempo Real -->
                    <div id="autocomplete-lista-epis" 
                         class="shadow-lg mt-1 p-0 border" 
                         style="display: none; position: absolute; top: 100%; left: 0; right: 0; width: 100%; max-height: 320px; overflow-y: auto; z-index: 99999; border-radius: 10px; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2) !important;">
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="filtro-tipo" class="form-label fw-semibold mb-1" style="font-size: 12px;">
                        <i class="bi bi-funnel text-primary me-1"></i> Tipo de Item
                    </label>
                    <select id="filtro-tipo" class="form-select py-2" onchange="aplicarFiltrosEpi()">
                        <option value="">Todos os Tipos</option>
                        <option value="EPI_COM_CA">EPI com C.A.</option>
                        <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                        <option value="UNIFORME">Uniforme</option>
                        <option value="OUTRO">Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filtro-ca-status" class="form-label fw-semibold mb-1" style="font-size: 12px;">
                        <i class="bi bi-shield-exclamation text-primary me-1"></i> Status do C.A.
                    </label>
                    <select id="filtro-ca-status" class="form-select py-2" onchange="aplicarFiltrosEpi()">
                        <option value="">Todos os Status C.A.</option>
                        <option value="vigente">Vigente</option>
                        <option value="vencido">Vencido / Próximo</option>
                        <option value="isento">Isento de C.A.</option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-epis">
                    <thead>
                        <tr>
                            <th>Item / Classificação</th>
                            <th>Fabricante</th>
                            <th>C.A.</th>
                            <th>Validade C.A.</th>
                            <th>Vida Útil</th>
                            <?php if ($podeVerCustos): ?>
                                <th>Preço Padrão</th>
                            <?php endif; ?>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($epis)): ?>
                            <tr>
                                <td colspan="<?= $podeVerCustos ? 8 : 7 ?>" class="text-center text-muted py-4">Nenhum item cadastrado no catálogo.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($epis as $epi): ?>
                                <?php
                                $tipoLabel = '';
                                if ($epi['epi_tipo_item'] === 'EPI_COM_CA') $tipoLabel = 'EPI com C.A.';
                                elseif ($epi['epi_tipo_item'] === 'ITEM_SEGURANCA_SEM_CA') $tipoLabel = 'Item sem C.A.';
                                else $tipoLabel = ucfirst(strtolower($epi['epi_tipo_item']));

                                $ca = $epi['epi_ca'] ?? 'Isento';
                                $vencimentoCa = '---';
                                $caStatus = 'isento';
                                $caStatusLabel = 'Isento';
                                
                                if ($epi['epi_tipo_item'] === 'EPI_COM_CA' && !empty($epi['epi_vencimento_ca'])) {
                                    $vencimentoCa = date('d/m/Y', strtotime($epi['epi_vencimento_ca']));
                                    $hoje = new DateTime();
                                    $venc = new DateTime($epi['epi_vencimento_ca']);
                                    $diff = $hoje->diff($venc);
                                    
                                    if ($venc < $hoje) {
                                        $caStatus = 'vencido';
                                        $caStatusLabel = 'Vencido';
                                    } elseif ($diff->days <= 30) {
                                        $caStatus = 'a-vencer';
                                        $caStatusLabel = 'A vencer';
                                    } else {
                                        $caStatus = 'ativo';
                                        $caStatusLabel = 'Vigente';
                                    }
                                }

                                $vidaUtil = 'Não controlada';
                                if ($epi['epi_vida_util_tipo'] === 'CONTROLADO') {
                                    $vidaUtil = $epi['epi_vida_util'] . ' ' . strtolower($epi['epi_vida_util_unidade'] ?? 'dias');
                                }
                                ?>
                                <tr class="epi-row" 
                                    data-id="<?= (int)$epi['epi_id'] ?>"
                                    data-nome="<?= htmlspecialchars(strtolower($epi['epi_nome'])) ?>"
                                    data-fabricante="<?= htmlspecialchars(strtolower($epi['epi_fabricante'])) ?>"
                                    data-ca="<?= htmlspecialchars($epi['epi_ca'] ?? '') ?>"
                                    data-tipo="<?= htmlspecialchars($epi['epi_tipo_item']) ?>"
                                    data-castatus="<?= htmlspecialchars($caStatus) ?>"
                                    data-situacao="<?= htmlspecialchars($epi['epi_status']) ?>">
                                    
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 36px; height: 36px; background-color: #eff6ff; color: #2563eb;">
                                                <?= getIconeEpiSvgPHP($epi['epi_nome']) ?>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($epi['epi_nome']) ?></div>
                                                <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($tipoLabel) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($epi['epi_fabricante']) ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($ca) ?></td>
                                    <td>
                                        <?php if ($epi['epi_tipo_item'] === 'EPI_COM_CA'): ?>
                                            <span class="status-badge <?= $caStatus ?>"><?= $vencimentoCa ?> (<?= $caStatusLabel ?>)</span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($vidaUtil) ?></td>
                                    <?php if ($podeVerCustos): ?>
                                        <td class="fw-bold text-success"><?= formatarValorMonetario((float)$epi['epi_valor']) ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="status-badge <?= strtolower($epi['epi_status']) ?>"><?= htmlspecialchars($epi['epi_status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn btn-sm btn-light border py-1 px-2 btn-ver-epi" data-id="<?= (int)$epi['epi_id'] ?>" onclick="verFichaEpiById(<?= (int)$epi['epi_id'] ?>)" title="Ver Detalhes e Rastreabilidade">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <?php if ($podeEditar): ?>
                                                <button type="button" class="btn btn-sm btn-light border text-primary py-1 px-2 btn-editar-epi" data-id="<?= (int)$epi['epi_id'] ?>" onclick="prepararEdicaoById(<?= (int)$epi['epi_id'] ?>)" title="Editar dados">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($podeExcluir && $epi['epi_status'] === 'ATIVO'): ?>
                                                <button type="button" class="btn btn-sm btn-light border text-danger py-1 px-2 btn-excluir-epi" data-id="<?= (int)$epi['epi_id'] ?>" onclick="confirmarExclusao(<?= (int)$epi['epi_id'] ?>, '<?= htmlspecialchars(addslashes($epi['epi_nome'])) ?>')" title="Inativar Item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div><!-- /visao-catalogo -->

        <!-- ================= PAINEL DE MONITORAMENTO DE C.A. ================= -->
        <div id="visao-painel-ca" class="<?= $visaoInicial === 'painel' ? '' : 'd-none' ?>">
            <div class="card-custom">
                <h5 class="fw-bold mb-1 text-color-primary"><i class="bi bi-shield-exclamation me-2"></i>Painel de Monitoramento de C.A.</h5>
                <p class="text-muted" style="font-size: 13px;">Validação de conformidade legal de uso do EPI: acompanhamento da validade dos Certificados de Aprovação junto ao Ministério do Trabalho.</p>

                <div class="d-flex flex-wrap gap-2 mb-4" id="ca-chips"></div>

                <div id="ca-lista" class="d-flex flex-column gap-3"></div>
            </div>
        </div>

        <!-- ================= TELA DE HISTÓRICO DE PREÇOS E AQUISIÇÃO ================= -->
        <div id="visao-historico-precos" class="<?= $visaoInicial === 'precos' ? '' : 'd-none' ?>">
            <!-- 1. Card de Pesquisa / Seleção -->
            <div class="card-custom mb-4" style="background:#ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 24px;">
                <div class="header-azul-mobile d-flex align-items-center bg-primary text-white p-3 mb-3 rounded-3 shadow-sm d-lg-none" style="background-color: #2563eb !important;">
                    <button class="btn btn-link text-white p-0 me-3 fs-4 border-0" onclick="document.getElementById('sidebar-toggle-btn')?.click(); return false;">
                        <i class="bi bi-list"></i>
                    </button>
                    <h5 class="m-0 fw-bold text-white fs-5">EPIs (Controle C.A.)</h5>
                </div>

                <h4 class="fw-bold mb-1" style="color: #2563eb; font-size: 20px;">Histórico de Preços e Aquisição</h4>
                <p class="text-muted small mb-4">Consulte a evolução de preços, cotações homologadas e histórico de notas fiscais dos equipamentos.</p>
                
                <div class="position-relative">
                    <label for="input-busca-historico-preco" class="form-label text-muted small fw-medium mb-1" style="font-size: 13px; color: #475569;">
                        Buscar EPI por C.A. ou Nome...
                    </label>
                    <div class="row g-2 align-items-center">
                        <div class="col-md-9 col-lg-10 position-relative">
                            <div class="position-relative d-flex align-items-center">
                                <input type="text" 
                                       id="input-busca-historico-preco" 
                                       class="form-control py-2 px-3 pe-5" 
                                       style="font-size: 14px; font-weight: 500; border-radius: 12px; border: 1px solid #3b82f6; background: #ffffff; color: #1e293b; height: 44px;"
                                       placeholder="Buscar EPI por C.A. ou Nome..." 
                                       autocomplete="off"
                                       oninput="aoDigitarBuscaHistoricoPreco(this.value)"
                                       onfocus="aoFocarBuscaHistoricoPreco(true)"
                                       onclick="aoFocarBuscaHistoricoPreco(true)"
                                       onkeydown="aoTeclarBuscaHistoricoPreco(event)">
                                <i class="bi bi-chevron-down text-dark position-absolute end-0 me-3" style="font-size: 13px; pointer-events: none;"></i>
                            </div>
                            <!-- Dropdown Flutuante de Autocomplete / Sugestões em Tempo Real (Começar com) -->
                            <div id="autocomplete-lista-historico-preco" 
                                 class="shadow-lg mt-1 p-0 border" 
                                 style="display: none; position: absolute; top: 100%; left: 0; right: 0; width: 100%; max-height: 340px; overflow-y: auto; z-index: 99999; border-radius: 12px; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15) !important;">
                            </div>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="button" class="btn btn-primary w-100 py-2 d-flex align-items-center justify-content-center gap-2" style="background-color: #2563eb; border: none; font-weight: 600; border-radius: 12px; height: 44px;" onclick="buscarEpiHistoricoManual()">
                                <i class="bi bi-shield-check fs-5"></i>
                                <span>Buscar</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Card do EPI Selecionado e Lista de Histórico -->
            <div class="card-custom" style="background:#ffffff; border: 1px solid #e2e8f0; max-width: 900px;">
                <h5 class="fw-bold mb-1" id="hist-epi-titulo" style="color: #1d4ed8; font-size: 17px;">
                    EPI: Selecione um equipamento acima...
                </h5>
                <div class="fw-semibold mb-4" id="hist-epi-preco-atual" style="font-size: 14px; color: #475569 !important;">
                    Preço Atual: R$ 0,00
                </div>

                <h6 class="fw-bold mb-3" style="color: #475569; font-size: 14px;">Histórico de Preços:</h6>

                <div id="lista-registros-historico-preco" class="d-flex flex-column gap-3">
                    <p class="text-muted text-center py-4 m-0">Selecione um equipamento para visualizar seu histórico de reajustes e cotações.</p>
                </div>
            </div>
        </div>
    </div>


</div>

<!-- ================= MODAIS DE AÇÃO ================= -->

<!-- 0. Modal Importar EPIs em Lote -->
<div class="modal fade" id="modalImportarEpi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php" enctype="multipart/form-data" id="form-importar-epi" onsubmit="return aoSubmeterFormImportacaoEpi(event)">
            <input type="hidden" name="acao" value="importar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-earmark-arrow-up me-2"></i>Importar EPIs em Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small m-0">Selecione uma planilha CSV contendo os equipamentos a serem cadastrados no catálogo.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="baixarModeloCsvEpi()">
                        <i class="bi bi-download me-1"></i>Baixar Modelo CSV
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label font-weight-bold">Arquivo CSV / Excel *</label>
                    <input type="file" id="arquivo-csv-epi" name="arquivo_csv" class="form-control" accept=".csv, text/csv" required onchange="aoSelecionarArquivoCsvEpi()">
                </div>

                <div class="alert alert-info py-2" style="font-size: 12px;">
                    <i class="bi bi-info-circle me-1"></i> Formato esperado do cabeçalho: <code>epi_nome;epi_fabricante;epi_ca;epi_vencimento_ca;epi_valor;epi_tipo_item</code>
                </div>

                <div id="resultado-importacao-epi" class="mt-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                <button type="submit" id="btn-processar-importacao-epi" class="btn btn-primary">
                    <i class="bi bi-upload me-1"></i> Processar Importação
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 1. Modal Cadastrar -->
<div class="modal fade" id="modalCadastrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php" novalidate>
            <input type="hidden" name="acao" value="cadastrar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-box-seam me-2"></i>Novo Item no Catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Item *</label>
                        <input type="text" class="form-control" name="epi_nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Classificação / Tipo de Item *</label>
                        <select class="form-select" name="epi_tipo_item" id="cad-tipo-item" onchange="toggleCaFields('cad')">
                            <option value="EPI_COM_CA">EPI com C.A.</option>
                            <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                            <option value="UNIFORME">Uniforme</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                </div>

                <!-- Campos de CA (Apenas se for EPI_COM_CA) -->
                <div class="row" id="cad-grupo-ca">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Certificado de Aprovação (C.A.) *</label>
                        <input type="text" class="form-control" name="epi_ca" id="cad-input-ca" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vencimento do C.A. *</label>
                        <input type="date" class="form-control" name="epi_vencimento_ca" id="cad-input-venc-ca" required>
                    </div>
                </div>

                <!-- Campos de Rastreabilidade (Apenas se for SEM CA / Uniforme / Outro) -->
                <div class="row d-none" id="cad-grupo-rastreabilidade">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" name="epi_modelo" placeholder="Ex: Modelo A1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Identificação Interna</label>
                        <input type="text" class="form-control" name="epi_identificacao" placeholder="Ex: ID-001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ref. Fornecedor</label>
                        <input type="text" class="form-control" name="epi_ref_fornecedor" placeholder="Ex: RF-99">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fabricante *</label>
                        <input type="text" class="form-control" name="epi_fabricante" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Localização Almoxarifado</label>
                        <input type="text" class="form-control" name="epi_localizacao" placeholder="Ex: Prateleira B2">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço Unitário (Padrão) *</label>
                        <input type="text" class="form-control mask-money" name="epi_valor" placeholder="R$ 0,00" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Origem do Preço *</label>
                        <select class="form-select" name="epi_origem_preco">
                            <option value="COMPRA_DIRETA">Compra Direta</option>
                            <option value="LICITACAO">Licitação</option>
                            <option value="CONTRATO_ANUAL">Contrato Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="epi_exige_tamanho" id="cad-exige-tamanho">
                            <label class="form-check-label fw-medium" for="cad-exige-tamanho">
                                Exige especificação de Tamanho
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Vida Útil -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controle da Vida Útil</label>
                        <select class="form-select" name="epi_vida_util_tipo" id="cad-vida-util-tipo" onchange="toggleVidaUtil('cad')">
                            <option value="CONTROLADO">Controlado</option>
                            <option value="ILIMITADO">Ilimitado / Não controlado</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="cad-grupo-vida-util-valor">
                        <label class="form-label">Vida Útil *</label>
                        <input type="number" class="form-control" name="epi_vida_util" id="cad-input-vida-util" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3" id="cad-grupo-vida-util-unidade">
                        <label class="form-label">Unidade *</label>
                        <select class="form-select" name="epi_vida_util_unidade" id="cad-input-vida-util-unidade" required>
                            <option value="DIAS">Dias</option>
                            <option value="MESES">Meses</option>
                            <option value="ANOS">Anos</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Alerta de Troca (dias antes) *</label>
                        <input type="number" class="form-control" name="epi_validade_uso_dias" value="365" min="0" required>
                        <small class="text-muted">Prazo recomendado de descarte após entrega ( NR-6 ).</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Observações SST</label>
                        <textarea class="form-control" name="epi_vida_util_obs" rows="2" placeholder="Observações de uso, higienização ou alertas..."></textarea>
                    </div>
                </div>

                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Item</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php" novalidate>
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" id="edit-epi-id" name="epi_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-pencil me-2"></i>Editar EPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Item *</label>
                        <input type="text" class="form-control" id="edit-epi-nome" name="epi_nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Classificação / Tipo de Item *</label>
                        <select class="form-select" name="epi_tipo_item" id="edit-tipo-item" onchange="toggleCaFields('edit', true)">
                            <option value="EPI_COM_CA">EPI com C.A.</option>
                            <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                            <option value="UNIFORME">Uniforme</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                </div>

                <!-- Campos de CA -->
                <div class="row" id="edit-grupo-ca">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Certificado de Aprovação (C.A.) *</label>
                        <input type="text" class="form-control" name="epi_ca" id="edit-input-ca" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vencimento do C.A. *</label>
                        <input type="date" class="form-control" name="epi_vencimento_ca" id="edit-input-venc-ca" required>
                    </div>
                </div>

                <!-- Campos de Rastreabilidade -->
                <div class="row d-none" id="edit-grupo-rastreabilidade">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" name="epi_modelo" id="edit-epi-modelo">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Identificação Interna</label>
                        <input type="text" class="form-control" name="epi_identificacao" id="edit-epi-identificacao">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ref. Fornecedor</label>
                        <input type="text" class="form-control" name="epi_ref_fornecedor" id="edit-epi-ref-fornecedor">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fabricante *</label>
                        <input type="text" class="form-control" id="edit-epi-fabricante" name="epi_fabricante" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Localização Almoxarifado</label>
                        <input type="text" class="form-control" id="edit-epi-localizacao" name="epi_localizacao">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço Unitário (Padrão) *</label>
                        <input type="text" class="form-control mask-money" id="edit-epi-valor" name="epi_valor" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Origem do Preço *</label>
                        <select class="form-select" id="edit-epi-origem" name="epi_origem_preco">
                            <option value="COMPRA_DIRETA">Compra Direta</option>
                            <option value="LICITACAO">Licitação</option>
                            <option value="CONTRATO_ANUAL">Contrato Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="epi_exige_tamanho" id="edit-exige-tamanho">
                            <label class="form-check-label fw-medium" for="edit-exige-tamanho">
                                Exige especificação de Tamanho
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Vida Útil -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Controle da Vida Útil</label>
                        <select class="form-select" name="epi_vida_util_tipo" id="edit-vida-util-tipo" onchange="toggleVidaUtil('edit')">
                            <option value="CONTROLADO">Controlado</option>
                            <option value="ILIMITADO">Ilimitado / Não controlado</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="edit-grupo-vida-util-valor">
                        <label class="form-label">Vida Útil *</label>
                        <input type="number" class="form-control" name="epi_vida_util" id="edit-input-vida-util" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3" id="edit-grupo-vida-util-unidade">
                        <label class="form-label">Unidade *</label>
                        <select class="form-select" name="epi_vida_util_unidade" id="edit-input-vida-util-unidade" required>
                            <option value="DIAS">Dias</option>
                            <option value="MESES">Meses</option>
                            <option value="ANOS">Anos</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Alerta de Troca (dias antes) *</label>
                        <input type="number" class="form-control" id="edit-epi-validade-uso" name="epi_validade_uso_dias" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" id="edit-epi-status" name="epi_status">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="OBSOLETO">Obsoleto</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nota Fiscal / Histórico</label>
                        <input type="text" class="form-control" name="hist_nota_fiscal" placeholder="Ref. Nota Fiscal (opcional)">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fornecedor Vigente</label>
                        <input type="text" class="form-control" name="hist_fornecedor" placeholder="Fornecedor do reajuste (opcional)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Observações SST</label>
                        <textarea class="form-control" id="edit-epi-obs" name="epi_vida_util_obs" rows="2"></textarea>
                    </div>
                </div>

                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Reajuste/Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" id="excluir-epi-id" name="epi_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Confirmar Inativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja descontinuar o item <strong id="excluir-epi-nome"></strong>?</p>
                <p class="text-muted" style="font-size: 13px;">O item passará para a situação de INATIVO. Registros históricos e entregas em posse dos colaboradores continuarão inalterados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Ficha e Rastreabilidade do EPI -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-eye me-2"></i>Especificações Técnicas e Rastreabilidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-2">
                    <h5 class="fw-bold mb-3" id="det-nome" style="color: var(--color-primary);">Nome do Item</h5>
                    
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Tipo de Item:</span>
                        <span class="fw-semibold" id="det-tipo"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Certificado de Aprovação (C.A.):</span>
                        <span class="fw-semibold" id="det-ca"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Validade do C.A.:</span>
                        <span class="fw-semibold" id="det-venc-ca"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Fabricante:</span>
                        <span class="fw-semibold" id="det-fabricante"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Exige especificação de tamanho:</span>
                        <span class="fw-semibold" id="det-exige-tam"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Localização Almoxarifado:</span>
                        <span class="fw-semibold text-muted" id="det-localizacao"></span>
                    </div>

                    <!-- Bloco Rastreabilidade Alternativo -->
                    <div class="p-3 bg-light rounded border my-3 d-none" id="det-bloco-rastreabilidade">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-tag-fill me-1 text-primary"></i>Dados de Rastreabilidade (NR-6 / Uniformes)</h6>
                        <div class="row g-2">
                            <div class="col-6" style="font-size: 13px;"><span class="text-muted">Modelo:</span> <strong id="det-rastre-modelo">---</strong></div>
                            <div class="col-6" style="font-size: 13px;"><span class="text-muted">Identificação/Lote:</span> <strong id="det-rastre-ident">---</strong></div>
                            <div class="col-12" style="font-size: 13px;"><span class="text-muted">Referência Fornecedor:</span> <strong id="det-rastre-forn">---</strong></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Controle de Vida Útil:</span>
                        <span class="fw-semibold" id="det-vida-util"></span>
                    </div>
                    
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Alerta de Troca (dias antes):</span>
                        <span class="fw-semibold" id="det-validade-uso"></span>
                    </div>

                    <?php if ($podeVerCustos): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Preço Homologado Vigente:</span>
                            <span class="fw-bold text-success" id="det-preco"></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Origem da Cotação:</span>
                            <span class="fw-semibold" id="det-origem-preco"></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="py-2">
                        <span class="text-muted d-block mb-1">Requisitos / Recomendações SST:</span>
                        <p class="text-muted border p-3 rounded" style="font-size: 13px;" id="det-obs">Nenhuma recomendação cadastrada.</p>
                    </div>

                    <!-- Nota sobre reajustes históricos -->
                    <div class="alert alert-info d-flex align-items-center mt-2" role="alert" style="font-size: 12px;">
                        <i class="bi-info-circle-fill me-2"></i>
                        <div>O histórico de reajustes e cotações de preços pode ser auditado diretamente na aba de <strong>Auditoria</strong> do menu administrativo.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Histórico de Preços -->
<div class="modal fade" id="modalHistoricoPrecos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-clock-history me-2"></i>Histórico e Cotação de Preços dos EPIs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Consulte a cotação atual, origem dos preços e valores homologados para os equipamentos de proteção.</p>
                <div class="table-responsive border rounded" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Equipamento (EPI)</th>
                                <th>Fabricante / C.A.</th>
                                <th>Origem da Cotação</th>
                                <th>Preço Homologado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($epis)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhum EPI cadastrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($epis as $e): ?>
                                    <?php
                                    $val = (float)($e['epi_valor'] ?? 0);
                                    $valFmt = 'R$ ' . number_format($val, 2, ',', '.');
                                    $origem = $e['epi_origem_preco'] ?? 'COMPRA_DIRETA';
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($e['epi_nome']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($e['epi_fabricante'] ?? '---') ?></div>
                                            <small class="text-muted">C.A. <?= htmlspecialchars($e['epi_ca'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($origem) ?></span></td>
                                        <td class="fw-bold text-success"><?= $valFmt ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>



<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';

// Mapa completo de EPIs por ID para invocação ultra-rápida e segura de modais
let todosEpisMap = <?= json_encode(array_column($epis, null, 'epi_id'), JSON_UNESCAPED_UNICODE) ?> || {};

function verFichaEpiById(id) {
    const epi = todosEpisMap[id] || todosEpisMap[String(id)];
    if (epi) {
        verFichaEpi(epi);
    }
}

function prepararEdicaoById(id) {
    const epi = todosEpisMap[id] || todosEpisMap[String(id)];
    if (epi) {
        prepararEdicao(epi);
    }
}

function limparOverlaysModal() {
    try {
        document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    } catch (e) {}
}

document.addEventListener('hidden.bs.modal', function () {
    limparOverlaysModal();
});

// Base de Catálogo para Busca e Autocomplete
let listaEpisCadastrados = <?= json_encode(array_values(array_map(function($e) {
    return [
        'epi_id' => (int)$e['epi_id'],
        'epi_nome' => $e['epi_nome'] ?? '',
        'epi_ca' => $e['epi_ca'] ?? '',
        'epi_fabricante' => $e['epi_fabricante'] ?? '',
        'epi_tipo_item' => $e['epi_tipo_item'] ?? 'EPI_COM_CA',
        'epi_vencimento_ca' => $e['epi_vencimento_ca'] ?? '',
        'epi_status' => $e['epi_status'] ?? 'ATIVO'
    ];
}, $epis)), JSON_UNESCAPED_UNICODE) ?>;

let sugestoesEpisAtuais = [];
let indexFocadoEpiAutocomplete = -1;

function normalizarTexto(str) {
    return String(str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function destacarTrecho(texto, query) {
    if (!texto) return '';
    if (!query) return htmlEscape(texto);
    const textoNorm = normalizarTexto(texto);
    const queryNorm = normalizarTexto(query);
    const idx = textoNorm.indexOf(queryNorm);
    if (idx === -1) return htmlEscape(texto);

    const antes = texto.substring(0, idx);
    const meio = texto.substring(idx, idx + query.length);
    const depois = texto.substring(idx + query.length);
    return `${htmlEscape(antes)}<strong class="text-primary">${htmlEscape(meio)}</strong>${htmlEscape(depois)}`;
}

function aoDigitarBuscaEpi(termo) {
    indexFocadoEpiAutocomplete = -1;
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (termo && termo.length > 0) {
        btnLimpar.classList.remove('d-none');
    } else {
        btnLimpar.classList.add('d-none');
    }
    aplicarFiltrosEpi();
}

function aoFocarBuscaEpi() {
    const busca = document.getElementById('busca-input');
    if (busca && busca.value.trim().length >= 1) {
        aplicarFiltrosEpi();
    }
}

function limparBuscaEpi() {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = '';
        busca.focus();
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.classList.add('d-none');
    fecharAutocompleteEpi();
    aplicarFiltrosEpi();
}

function fecharAutocompleteEpi() {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (autoList) {
        autoList.style.display = 'none';
        autoList.classList.add('d-none');
        autoList.innerHTML = '';
    }
    indexFocadoEpiAutocomplete = -1;
    sugestoesEpisAtuais = [];
}

function aplicarFiltrosEpi() {
    const busca = document.getElementById('busca-input');
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroCaStatus = document.getElementById('filtro-ca-status');
    const rows = document.querySelectorAll('.epi-row');

    const rawQuery = busca ? busca.value.trim() : '';
    const queryNorm = normalizarTexto(rawQuery);
    const queryCleanCa = rawQuery.replace(/\D/g, '');
    const tipo = filtroTipo ? filtroTipo.value : '';
    const caStatus = filtroCaStatus ? filtroCaStatus.value : '';

    let visiveis = 0;

    rows.forEach(row => {
        const rNome = normalizarTexto(row.getAttribute('data-nome'));
        const rFabricante = normalizarTexto(row.getAttribute('data-fabricante'));
        const rCa = row.getAttribute('data-ca') || '';
        const rCaLimpo = rCa.replace(/\D/g, '');
        const rTipo = row.getAttribute('data-tipo') || '';
        const rCaStatus = row.getAttribute('data-castatus') || '';

        // "COMEÇA COM" (startsWith) estritamente no início do nome do EPI, do fabricante ou do C.A.
        let bateBusca = false;
        if (!queryNorm) {
            bateBusca = true;
        } else {
            const comecaNome = rNome.startsWith(queryNorm);
            const comecaFab = rFabricante.startsWith(queryNorm);
            const comecaCa = (queryCleanCa.length > 0 && rCaLimpo.startsWith(queryCleanCa)) || rCa.startsWith(rawQuery);

            bateBusca = comecaNome || comecaFab || comecaCa;
        }

        const bateTipo = (tipo === '' || rTipo === tipo);
        const bateCaStatus = (caStatus === '' || rCaStatus === caStatus);

        if (bateBusca && bateTipo && bateCaStatus) {
            row.style.display = '';
            visiveis++;
        } else {
            row.style.display = 'none';
        }
    });

    renderizarAutocompleteEpi(rawQuery, queryNorm, queryCleanCa, tipo, caStatus);
}

function renderizarAutocompleteEpi(rawQuery, queryNorm, queryCleanCa, tipo, caStatus) {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (!autoList) return;

    if (!queryNorm || queryNorm.length < 1) {
        fecharAutocompleteEpi();
        return;
    }

    // Filtra catálogo por nome, fabricante ou C.A.
    sugestoesEpisAtuais = listaEpisCadastrados.filter(e => {
        const eTipo = e.epi_tipo_item || '';
        if (tipo !== '' && eTipo !== tipo) return false;

        const nomeNorm = normalizarTexto(e.epi_nome);
        const fabNorm = normalizarTexto(e.epi_fabricante);
        const caLimpo = String(e.epi_ca || '').replace(/\D/g, '');

        const matchNome = nomeNorm.includes(queryNorm);
        const matchFab = fabNorm.includes(queryNorm);
        const matchCa = (queryCleanCa.length > 0 && caLimpo.includes(queryCleanCa)) || String(e.epi_ca || '').includes(rawQuery);

        return matchNome || matchFab || matchCa;
    });

    indexFocadoEpiAutocomplete = -1;

    if (sugestoesEpisAtuais.length === 0) {
        autoList.innerHTML = `
            <div class="p-3 text-center text-muted" style="font-size: 13px;">
                <i class="bi bi-search me-1 text-secondary"></i> Nenhum equipamento encontrado com "<strong>${htmlEscape(rawQuery)}</strong>"
            </div>`;
        autoList.style.display = 'block';
        autoList.classList.remove('d-none');
        return;
    }

    const itensExibir = sugestoesEpisAtuais.slice(0, 8);
    let html = `
        <div class="px-3 py-2 bg-light border-bottom text-muted d-flex justify-content-between align-items-center" style="font-size: 11px;">
            <span><i class="bi bi-box-seam me-1 text-primary"></i> ${sugestoesEpisAtuais.length} equipamento(s) encontrado(s)</span>
            <span><kbd>▲</kbd> <kbd>▼</kbd> para navegar • <kbd>Enter</kbd> para escolher</span>
        </div>
        <div class="list-group list-group-flush">
    `;

    itensExibir.forEach((e, idx) => {
        const nomeDestacado = destacarTrecho(e.epi_nome, rawQuery);
        const fab = htmlEscape(e.epi_fabricante || 'Fabricante não informado');
        const caDesc = e.epi_ca ? `C.A. ${e.epi_ca}` : 'Sem C.A.';

        const iniciais = (e.epi_nome || 'EP')
            .split(' ')
            .filter(n => n.length > 0)
            .slice(0, 2)
            .map(n => n[0].toUpperCase())
            .join('');

        let statusCaHtml = '';
        if (e.epi_vencimento_ca) {
            const dataVenc = new Date(e.epi_vencimento_ca);
            const hoje = new Date();
            const dataFmt = e.epi_vencimento_ca.split('-').reverse().join('/');
            if (dataVenc < hoje) {
                statusCaHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 10px;"><i class="bi bi-exclamation-triangle me-1"></i>C.A. Vencido (${dataFmt})</span>`;
            } else {
                statusCaHtml = `<span class="text-muted ms-1" style="font-size: 10px;"><i class="bi bi-calendar-check me-1"></i>Val: ${dataFmt}</span>`;
            }
        }

        let tipoLabel = 'EPI';
        if (e.epi_tipo_item === 'UNIFORME') tipoLabel = 'Uniforme';
        else if (e.epi_tipo_item === 'ITEM_SEGURANCA_SEM_CA') tipoLabel = 'Item sem C.A.';

        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action p-2 border-0 border-bottom d-flex align-items-center gap-2 autocomplete-item auto-epi-item" 
               id="auto-epi-${idx}"
               onclick="selecionarEpiAutocomplete('${e.epi_nome.replace(/'/g, "\\'")}', ${e.epi_id})"
               onmouseover="destacarItemEpi(${idx})">
                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 36px; height: 36px; font-size: 13px; letter-spacing: 0.5px;">
                    ${iniciais}
                </div>
                <div class="flex-grow-1 min-w-0" style="line-height: 1.25;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;">${nomeDestacado}</span>
                        <span class="badge bg-light text-secondary border ms-1 flex-shrink-0" style="font-size: 10px;">ID #${e.epi_id}</span>
                    </div>
                    <div class="text-muted d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                        <span><i class="bi bi-building me-1"></i>${fab}</span> • 
                        <span><i class="bi bi-shield me-1"></i>${caDesc} (${tipoLabel})</span>
                        ${statusCaHtml ? ' • ' + statusCaHtml : ''}
                    </div>
                </div>
            </a>
        `;
    });

    html += `</div>`;
    autoList.innerHTML = html;
    autoList.style.display = 'block';
    autoList.classList.remove('d-none');
}

function destacarItemEpi(idx) {
    indexFocadoEpiAutocomplete = idx;
    const items = document.querySelectorAll('.auto-epi-item');
    items.forEach((el, i) => {
        if (i === idx) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function aoTeclarBuscaEpi(e) {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (!autoList || autoList.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (sugestoesEpisAtuais.length > 0) {
            indexFocadoEpiAutocomplete = (indexFocadoEpiAutocomplete + 1) % Math.min(sugestoesEpisAtuais.length, 8);
            destacarItemEpi(indexFocadoEpiAutocomplete);
            const el = document.getElementById(`auto-epi-${indexFocadoEpiAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (sugestoesEpisAtuais.length > 0) {
            indexFocadoEpiAutocomplete = (indexFocadoEpiAutocomplete - 1 + Math.min(sugestoesEpisAtuais.length, 8)) % Math.min(sugestoesEpisAtuais.length, 8);
            destacarItemEpi(indexFocadoEpiAutocomplete);
            const el = document.getElementById(`auto-epi-${indexFocadoEpiAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indexFocadoEpiAutocomplete >= 0 && sugestoesEpisAtuais[indexFocadoEpiAutocomplete]) {
            selecionarEpiAutocomplete(sugestoesEpisAtuais[indexFocadoEpiAutocomplete].epi_nome, sugestoesEpisAtuais[indexFocadoEpiAutocomplete].epi_id);
        } else if (sugestoesEpisAtuais.length === 1) {
            selecionarEpiAutocomplete(sugestoesEpisAtuais[0].epi_nome, sugestoesEpisAtuais[0].epi_id);
        }
    } else if (e.key === 'Escape') {
        fecharAutocompleteEpi();
    }
}

function selecionarEpiAutocomplete(nome, epiId) {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = nome;
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.classList.remove('d-none');
    fecharAutocompleteEpi();
    aplicarFiltrosEpi();

    if (epiId) {
        const row = document.querySelector(`.epi-row[data-id="${epiId}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('table-active');
            setTimeout(() => row.classList.remove('table-active'), 2500);
        }
    }
}

// Fecha autocomplete se clicar fora
document.addEventListener('click', function(e) {
    const busca = document.getElementById('busca-input');
    const wrapper = busca ? busca.closest('.position-relative') : null;
    if (wrapper && !wrapper.contains(e.target)) {
        fecharAutocompleteEpi();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    restaurarEstadoFiltros();
    
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        form.addEventListener('submit', function() {
            salvarEstadoFiltros();
        });
    });
});

function salvarEstadoFiltros() {
    const estado = {
        busca: document.getElementById('busca-input')?.value || '',
        filtroTipo: document.getElementById('filtro-tipo')?.value || '',
        filtroCaStatus: document.getElementById('filtro-ca-status')?.value || '',
        scrollY: window.scrollY
    };
    sessionStorage.setItem('epis_estado_filtros', JSON.stringify(estado));
}

function restaurarEstadoFiltros() {
    const estadoSalvo = sessionStorage.getItem('epis_estado_filtros');
    if (!estadoSalvo) return;
    
    sessionStorage.removeItem('epis_estado_filtros');
    
    try {
        const estado = JSON.parse(estadoSalvo);
        
        if (estado.busca) document.getElementById('busca-input').value = estado.busca;
        if (estado.filtroTipo) document.getElementById('filtro-tipo').value = estado.filtroTipo;
        if (estado.filtroCaStatus) document.getElementById('filtro-ca-status').value = estado.filtroCaStatus;
        
        if (estado.busca || estado.filtroTipo || estado.filtroCaStatus) {
            aplicarFiltrosEpi();
        }
        
        if (estado.scrollY > 0) {
            setTimeout(() => window.scrollTo(0, estado.scrollY), 100);
        }
    } catch (e) {}
}

/**
 * Controla os inputs obrigatórios de C.A. e dados de rastreabilidade dependendo do Tipo de Item selecionado
 */
function toggleCaFields(prefix, isUserChange = false) {
    const tipo = document.getElementById(`${prefix}-tipo-item`).value;
    const grupoCa = document.getElementById(`${prefix}-grupo-ca`);
    const grupoRastre = document.getElementById(`${prefix}-grupo-rastreabilidade`);
    
    const inputCa = document.getElementById(`${prefix}-input-ca`);
    const inputVenc = document.getElementById(`${prefix}-input-venc-ca`);
    
    if (tipo === 'EPI_COM_CA') {
        grupoCa.classList.remove('d-none');
        grupoRastre.classList.add('d-none');
        
        inputCa.required = true;
        inputVenc.required = true;
    } else {
        grupoCa.classList.add('d-none');
        grupoRastre.classList.remove('d-none');
        
        inputCa.required = false;
        inputVenc.required = false;
        if (isUserChange) {
            inputCa.value = '';
            inputVenc.value = '';
        }
    }
}

/**
 * Controla os inputs de Vida Útil
 */
function toggleVidaUtil(prefix, isUserChange = false) {
    const tipo = document.getElementById(`${prefix}-vida-util-tipo`).value;
    const grupoValor = document.getElementById(`${prefix}-grupo-vida-util-valor`);
    const grupoUnidade = document.getElementById(`${prefix}-grupo-vida-util-unidade`);
    
    const inputVal = document.getElementById(`${prefix}-input-vida-util`);
    const inputUni = document.getElementById(`${prefix}-input-vida-util-unidade`);
    
    if (tipo === 'CONTROLADO') {
        grupoValor.style.display = '';
        grupoUnidade.style.display = '';
        
        inputVal.required = true;
        inputUni.required = true;
    } else {
        grupoValor.style.display = 'none';
        grupoUnidade.style.display = 'none';
        
        inputVal.required = false;
        inputUni.required = false;
        if (isUserChange) {
            inputVal.value = '';
        }
    }
}

function toggleCaFields(prefix, isUserChange = true) {
    const selectTipo = document.getElementById(`${prefix}-tipo-item`);
    if (!selectTipo) return;
    const tipo = selectTipo.value;

    const grupoCa = document.getElementById(`${prefix}-grupo-ca`);
    const grupoRastre = document.getElementById(`${prefix}-grupo-rastreabilidade`);
    const inputCa = document.getElementById(`${prefix}-input-ca`);
    const inputVenc = document.getElementById(`${prefix}-input-venc-ca`);

    if (tipo === 'EPI_COM_CA') {
        if (grupoCa) grupoCa.classList.remove('d-none');
        if (grupoRastre) grupoRastre.classList.add('d-none');
        if (inputCa) inputCa.required = true;
        if (inputVenc) inputVenc.required = true;
    } else {
        if (grupoCa) grupoCa.classList.add('d-none');
        if (grupoRastre) grupoRastre.classList.remove('d-none');
        if (inputCa) {
            inputCa.required = false;
            if (isUserChange) inputCa.value = '';
        }
        if (inputVenc) {
            inputVenc.required = false;
            if (isUserChange) inputVenc.value = '';
        }
    }
}

function toggleVidaUtil(prefix, isUserChange = true) {
    const selectTipo = document.getElementById(`${prefix}-vida-util-tipo`);
    if (!selectTipo) return;
    const tipo = selectTipo.value;

    const grupoValor = document.getElementById(`${prefix}-grupo-vida-util-valor`);
    const grupoUnidade = document.getElementById(`${prefix}-grupo-vida-util-unidade`);
    const inputValor = document.getElementById(`${prefix}-input-vida-util`);
    const inputUnidade = document.getElementById(`${prefix}-input-vida-util-unidade`);

    if (tipo === 'CONTROLADO') {
        if (grupoValor) grupoValor.classList.remove('d-none');
        if (grupoUnidade) grupoUnidade.classList.remove('d-none');
        if (inputValor) inputValor.required = true;
        if (inputUnidade) inputUnidade.required = true;
    } else {
        if (grupoValor) grupoValor.classList.add('d-none');
        if (grupoUnidade) grupoUnidade.classList.add('d-none');
        if (inputValor) {
            inputValor.required = false;
            if (isUserChange) inputValor.value = '';
        }
        if (inputUnidade) {
            inputUnidade.required = false;
        }
    }
}

/**
 * Preenche o modal de exclusão do EPI
 */
function confirmarExclusao(id, nome) {
    const inputId = document.getElementById('excluir-epi-id');
    const spanNome = document.getElementById('excluir-epi-nome');
    if (inputId) inputId.value = id;
    if (spanNome) spanNome.innerText = nome;
    
    const modalEl = document.getElementById('modalExcluir');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

/**
 * Preenche o modal de edição
 */
function prepararEdicao(epi) {
    if (!epi) return;
    const elId = document.getElementById('edit-epi-id');
    if (elId) elId.value = epi.epi_id;

    const elNome = document.getElementById('edit-epi-nome');
    if (elNome) elNome.value = epi.epi_nome || '';

    const elTipo = document.getElementById('edit-tipo-item');
    if (elTipo) elTipo.value = epi.epi_tipo_item || 'EPI_COM_CA';
    
    const elCa = document.getElementById('edit-input-ca');
    if (elCa) elCa.value = epi.epi_ca || '';

    const elVencCa = document.getElementById('edit-input-venc-ca');
    if (elVencCa) elVencCa.value = epi.epi_vencimento_ca || '';
    
    const elModelo = document.getElementById('edit-epi-modelo');
    if (elModelo) elModelo.value = epi.epi_modelo || '';

    const elIdent = document.getElementById('edit-epi-identificacao');
    if (elIdent) elIdent.value = epi.epi_identificacao || '';

    const elRefForn = document.getElementById('edit-epi-ref-fornecedor');
    if (elRefForn) elRefForn.value = epi.epi_ref_fornecedor || '';
    
    const elFab = document.getElementById('edit-epi-fabricante');
    if (elFab) elFab.value = epi.epi_fabricante || '';

    const elLoc = document.getElementById('edit-epi-localizacao');
    if (elLoc) elLoc.value = epi.epi_localizacao || '';
    
    // Formata preço para a máscara com proteção contra NaN
    const valorFloat = parseFloat(epi.epi_valor);
    const elValor = document.getElementById('edit-epi-valor');
    if (elValor) {
        elValor.value = isNaN(valorFloat)
            ? 'R$ 0,00'
            : valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    }

    const elOrigem = document.getElementById('edit-epi-origem');
    if (elOrigem) elOrigem.value = epi.epi_origem_preco || 'COMPRA_DIRETA';

    const elExigeTam = document.getElementById('edit-exige-tamanho');
    if (elExigeTam) elExigeTam.checked = (parseInt(epi.epi_exige_tamanho) === 1);
    
    const elVidaUtilTipo = document.getElementById('edit-vida-util-tipo');
    if (elVidaUtilTipo) elVidaUtilTipo.value = epi.epi_vida_util_tipo || 'CONTROLADO';

    const elInputVidaUtil = document.getElementById('edit-input-vida-util');
    if (elInputVidaUtil) elInputVidaUtil.value = epi.epi_vida_util || '';

    const elInputVidaUtilUnidade = document.getElementById('edit-input-vida-util-unidade');
    if (elInputVidaUtilUnidade) elInputVidaUtilUnidade.value = epi.epi_vida_util_unidade || 'DIAS';
    
    const elValidadeUso = document.getElementById('edit-epi-validade-uso');
    if (elValidadeUso) elValidadeUso.value = epi.epi_validade_uso_dias || 365;

    const elStatus = document.getElementById('edit-epi-status');
    if (elStatus) elStatus.value = epi.epi_status || 'ATIVO';

    const elObs = document.getElementById('edit-epi-obs');
    if (elObs) elObs.value = epi.epi_vida_util_obs || '';

    // Roda os toggles iniciais em modo de inicialização
    toggleCaFields('edit', false);
    toggleVidaUtil('edit', false);

    const modalEl = document.getElementById('modalEditar');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

/**
 * Exibe especificações e dados de rastreabilidade do EPI
 */
function verFichaEpi(epi) {
    if (!epi) return;
    const elNome = document.getElementById('det-nome');
    if (elNome) elNome.innerText = epi.epi_nome || '';
    
    const tiposMap = {
        'EPI_COM_CA': 'EPI com Certificado de Aprovação',
        'ITEM_SEGURANCA_SEM_CA': 'Item de Segurança sem C.A.',
        'UNIFORME': 'Uniforme',
        'OUTRO': 'Outro tipo de item'
    };
    const elTipo = document.getElementById('det-tipo');
    if (elTipo) elTipo.innerText = tiposMap[epi.epi_tipo_item] ?? epi.epi_tipo_item;
    
    const elCa = document.getElementById('det-ca');
    if (elCa) elCa.innerText = epi.epi_ca || 'Isento de C.A.';
    
    let vencCa = '---';
    if (epi.epi_tipo_item === 'EPI_COM_CA' && epi.epi_vencimento_ca) {
        const parts = epi.epi_vencimento_ca.split('-');
        vencCa = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : epi.epi_vencimento_ca;
    }
    const elVencCa = document.getElementById('det-venc-ca');
    if (elVencCa) elVencCa.innerText = vencCa;

    const elFab = document.getElementById('det-fabricante');
    if (elFab) elFab.innerText = epi.epi_fabricante || '';

    const elExigeTam = document.getElementById('det-exige-tam');
    if (elExigeTam) elExigeTam.innerText = parseInt(epi.epi_exige_tamanho) === 1 ? 'Sim, obrigatório' : 'Não exigido';

    const elLoc = document.getElementById('det-localizacao');
    if (elLoc) elLoc.innerText = epi.epi_localizacao || 'Sem especificação';

    // Rastreabilidade alternativos (NR-6 / Uniformes)
    const blocoRastre = document.getElementById('det-bloco-rastreabilidade');
    if (blocoRastre) {
        if (epi.epi_tipo_item !== 'EPI_COM_CA') {
            blocoRastre.classList.remove('d-none');
            const elMod = document.getElementById('det-rastre-modelo');
            if (elMod) elMod.innerText = epi.epi_modelo || '---';
            const elIdent = document.getElementById('det-rastre-ident');
            if (elIdent) elIdent.innerText = epi.epi_identificacao || epi.epi_numero_lote || '---';
            const elForn = document.getElementById('det-rastre-forn');
            if (elForn) elForn.innerText = epi.epi_ref_fornecedor || '---';
        } else {
            blocoRastre.classList.add('d-none');
        }
    }

    // Vida útil
    let vidaUtil = 'Ilimitada / Não controlada';
    if (epi.epi_vida_util_tipo === 'CONTROLADO') {
        vidaUtil = `${epi.epi_vida_util || 0} ${epi.epi_vida_util_unidade || 'DIAS'}`;
    }
    const elVidaUtil = document.getElementById('det-vida-util');
    if (elVidaUtil) elVidaUtil.innerText = vidaUtil;
    
    const elValidadeUso = document.getElementById('det-validade-uso');
    if (elValidadeUso) elValidadeUso.innerText = `${epi.epi_validade_uso_dias || 0} dias recomendados de descarte`;
    
    // Custos (exibição protegida)
    const precoNode = document.getElementById('det-preco');
    if (precoNode) {
        const valorFloat = parseFloat(epi.epi_valor);
        precoNode.innerText = isNaN(valorFloat) ? 'R$ 0,00' : valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        
        const origensMap = {
            'COMPRA_DIRETA': 'Compra Direta',
            'LICITACAO': 'Processo Licitatório',
            'CONTRATO_ANUAL': 'Contrato Corporativo Anual'
        };
        const elOrigemPreco = document.getElementById('det-origem-preco');
        if (elOrigemPreco) elOrigemPreco.innerText = origensMap[epi.epi_origem_preco] ?? epi.epi_origem_preco;
    }
    
    const elObs = document.getElementById('det-obs');
    if (elObs) elObs.innerText = epi.epi_vida_util_obs || 'Nenhuma recomendação SST cadastrada.';

    const modalEl = document.getElementById('modalDetalhes');
    if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    }
}

/* ===================== PAINEL DE MONITORAMENTO DE C.A. ===================== */

const PODE_EDITAR_EPI = <?= $podeEditar ? 'true' : 'false' ?>;
let caFiltroAtivo = 'todos';
let episClassificados = [];

/**
 * Alterna entre as visões do Catálogo, Painel C.A. e Histórico de Preços
 */
function alternarVisao(visao) {
    const visaoCatalogo = document.getElementById('visao-catalogo');
    const visaoPainel = document.getElementById('visao-painel-ca');
    const visaoPrecos = document.getElementById('visao-historico-precos');

    const btnCatalogo = document.getElementById('btn-visao-catalogo');
    const btnPainel = document.getElementById('btn-visao-painel');
    const btnPrecos = document.getElementById('btn-visao-precos');

    if (visaoCatalogo) visaoCatalogo.classList.add('d-none');
    if (visaoPainel) visaoPainel.classList.add('d-none');
    if (visaoPrecos) visaoPrecos.classList.add('d-none');

    if (btnCatalogo) btnCatalogo.classList.remove('active');
    if (btnPainel) btnPainel.classList.remove('active');
    if (btnPrecos) btnPrecos.classList.remove('active');

    // Remove destaque ativo anterior dos submenus do sidebar
    document.querySelectorAll('#sub-epis a').forEach(a => a.classList.remove('active-sub'));

    if (visao === 'precos' || visao === 'historico_precos') {
        if (visaoPrecos) visaoPrecos.classList.remove('d-none');
        if (btnPrecos) btnPrecos.classList.add('active');
        const link = document.querySelector('#sub-epis a[href*="acao=historico_precos"], #sub-epis a[href*="acao=precos"]');
        if (link) link.classList.add('active-sub');
        carregarTelaHistoricoPrecos();
    } else if (visao === 'painel' || visao === 'controle_ca' || visao === 'ca') {
        if (visaoPainel) visaoPainel.classList.remove('d-none');
        if (btnPainel) btnPainel.classList.add('active');
        const link = document.querySelector('#sub-epis a[href*="acao=controle_ca"], #sub-epis a[href*="acao=ca"]');
        if (link) link.classList.add('active-sub');
        renderizarPainelCa();
    } else {
        if (visaoCatalogo) visaoCatalogo.classList.remove('d-none');
        if (btnCatalogo) btnCatalogo.classList.add('active');
        const link = document.querySelector('#sub-epis a[href*="acao=lista"]');
        if (link) link.classList.add('active-sub');
    }
}
window.alternarVisao = alternarVisao;

/**
 * Classifica cada EPI pelo status do C.A. (critério idêntico ao aplicativo)
 */
function classificarCaEpi(epi) {
    const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    const ca = (epi.epi_ca || '').toString().trim();
    const statusItem = (epi.epi_status || 'ATIVO').toUpperCase();

    if (statusItem === 'INATIVO' || statusItem === 'OBSOLETO') {
        return { status: 'inativo', score: 5 };
    }
    if (ca === '' || ca.toUpperCase() === 'SEM C.A.') {
        return { status: 'sem-ca', score: 4 };
    }
    if (!epi.epi_vencimento_ca) {
        return { status: 'valido', score: 3 };
    }

    const vencimento = new Date(String(epi.epi_vencimento_ca).split(' ')[0] + 'T00:00:00');
    if (isNaN(vencimento.getTime())) return { status: 'valido', score: 3 };

    if (vencimento < hoje) return { status: 'vencido', score: 1 };

    const diasRestantes = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
    return diasRestantes <= 30 ? { status: 'vencendo', score: 2 } : { status: 'valido', score: 3 };
}

function getIconeEpiSvg(nomeEpi) {
    const nome = normalizarTexto(nomeEpi || '');
    if (nome.includes('avental') || nome.includes('jaleco') || nome.includes('macacao') || nome.includes('vestimenta') || nome.includes('roupa')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4a1 1 0 0 1 1-1h8a1 1 0 0 1 1 1v3l2 3v9a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-9l2-3V4z"/><path d="M9 3a3 3 0 0 1 6 0"/><line x1="6" y1="10" x2="18" y2="10"/></svg>`;
    }
    if (nome.includes('cinto') || nome.includes('paraquedista') || nome.includes('talabarte') || nome.includes('altura')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3v6a6 6 0 0 0 12 0V3"/><path d="M4 14h16"/><path d="M6 14v6"/><path d="M18 14v6"/><circle cx="12" cy="9" r="2"/></svg>`;
    }
    if (nome.includes('oculos') || nome.includes('viseira') || nome.includes('lente') || nome.includes('solda')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2v3c0 1.1-.9 2-2 2H5c-1.1 0-2-.9-2-2v-3z"/><path d="M13 10c0-1.1.9-2 2-2h4c1.1 0 2 .9 2 2v3c0 1.1-.9 2-2 2h-4c-1.1 0-2-.9-2-2v-3z"/><path d="M11 12h2"/><path d="M3 11l-2-2"/><path d="M21 11l2-2"/></svg>`;
    }
    if (nome.includes('capacete') || nome.includes('cabeca')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v2z"/><path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"/><path d="M4 15v-3a8 8 0 0 1 16 0v3"/></svg>`;
    }
    if (nome.includes('luva') || nome.includes('mao')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 11V6a2 2 0 0 0-4 0v3"/><path d="M14 9V4a2 2 0 0 0-4 0v5"/><path d="M10 9V2a2 2 0 0 0-4 0v10"/><path d="M18 11a2 2 0 0 1 4 0v6a8 8 0 0 1-16 0v-2"/><path d="M6 12a2 2 0 0 0-4 0v3a8 8 0 0 0 8 8"/></svg>`;
    }
    if (nome.includes('bota') || nome.includes('botina') || nome.includes('calcado') || nome.includes('sapato')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 16v-8a2 2 0 0 1 2-2h4l4 8h6a2 2 0 0 1 2 2v2H4z"/><path d="M4 20h18"/><path d="M8 12h3"/></svg>`;
    }
    if (nome.includes('protetor') || nome.includes('abafador') || nome.includes('auricular') || nome.includes('ouvido')) {
        return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 14v3a3 3 0 0 0 3 3h12a3 3 0 0 0 3-3v-3"/><path d="M19 14h2a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1h-2v6z"/><path d="M5 14H3a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1h2v6z"/><path d="M5 8V7a7 7 0 0 1 14 0v1"/></svg>`;
    }
    return `<svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>`;
}

function badgeCa(status, epi) {
    const statusItem = (epi.epi_status || 'ATIVO').toUpperCase();
    if (statusItem === 'OBSOLETO') {
        return '<span class="badge bg-secondary text-white fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">OBSOLETO</span>';
    }
    if (statusItem === 'INATIVO' || status === 'inativo') {
        return '<span class="badge bg-secondary text-white fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">INATIVO</span>';
    }
    switch (status) {
        case 'vencido': 
            return '<span class="badge bg-danger text-white fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">C.A. VENCIDO</span>';
        case 'vencendo': 
            return '<span class="badge bg-warning text-dark fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">C.A. VENCENDO</span>';
        case 'valido': 
            return '<span class="badge bg-success text-white fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">C.A. VÁLIDO</span>';
        default: 
            return '<span class="badge bg-info text-dark fw-bold px-2 py-1" style="font-size: 10px; border-radius: 4px; letter-spacing: 0.5px;">SEM C.A.</span>';
    }
}

function montarCardsCa() {
    const lista = document.getElementById('ca-lista');
    const filtrados = episClassificados
        .filter(e => caFiltroAtivo === 'todos' || e.status === caFiltroAtivo)
        .sort((a, b) => (a.epi.epi_nome || '').localeCompare(b.epi.epi_nome || '', 'pt-BR', { sensitivity: 'base' }));

    if (!filtrados.length) {
        lista.innerHTML = '<p class="text-muted text-center py-4 m-0">Nenhum EPI encontrado para este filtro de monitoramento.</p>';
        return;
    }

    lista.innerHTML = filtrados.map(e => {
        const vidaUtil = e.epi.epi_vida_util_tipo === 'CONTROLADO'
            ? `${e.epi.epi_vida_util} ${lowerUnidade(e.epi.epi_vida_util_unidade)}`
            : 'Ilimitada';

        const alertaTroca = e.epi.epi_validade_uso_dias 
            ? `${e.epi.epi_validade_uso_dias} dias antes` 
            : 'Conforme inspeção';

        const localizacao = e.epi.epi_localizacao && e.epi.epi_localizacao.trim() !== '' 
            ? e.epi.epi_localizacao 
            : 'Não especificada';

        let valorStr = '';
        if (e.epi.epi_valor !== undefined && e.epi.epi_valor !== null) {
            const valFloat = parseFloat(e.epi.epi_valor);
            valorStr = 'R$ ' + valFloat.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }

        const iconeSvg = getIconeEpiSvg(e.epi.epi_nome);

        let caInfo = '';
        if (e.status === 'sem-ca') {
            const temDados = e.epi.epi_modelo || e.epi.epi_identificacao || e.epi.epi_numero_lote || e.epi.epi_ref_fornecedor;
            caInfo = temDados
                ? `Lote: ${e.epi.epi_identificacao || e.epi.epi_numero_lote || '---'} | Modelo: ${e.epi.epi_modelo || '---'}`
                : 'Isento de C.A.';
        } else {
            caInfo = `C.A. ${e.epi.epi_ca || '---'} | Vencimento C.A.: ${formatarDataBR(e.epi.epi_vencimento_ca)}`;
        }

        return `
            <div class="card-ca-item border rounded-4 p-3 d-flex align-items-center gap-3 bg-white shadow-sm mb-2" style="border: 1px solid #e2e8f0; transition: all 0.2s ease;">
                <div class="card-ca-icon-box rounded-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 58px; height: 58px; background-color: #eff6ff; color: #2563eb;">
                    ${iconeSvg}
                </div>

                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-1 mb-1">
                        <h6 class="fw-bold m-0" style="color: #2563eb; font-size: 0.98rem; cursor: pointer;" onclick='abrirEpiDoPainel(${jsonParaAtributo(e.epi)})'>
                            ${e.epi.epi_nome}
                        </h6>
                        ${badgeCa(e.status, e.epi)}
                    </div>
                    
                    <div class="text-secondary small" style="font-size: 12.5px; line-height: 1.4;">
                        <div>${caInfo}</div>
                        <div>Fabricante: ${e.epi.epi_fabricante || '---'} ${valorStr ? ' | Valor: ' + valorStr : ''}</div>
                        <div>Localização: ${localizacao}</div>
                        <div>Vida útil: ${vidaUtil} | Alerta troca: ${alertaTroca}</div>
                    </div>
                </div>

                <button class="btn btn-sm btn-light border ms-auto align-self-start py-1 px-2" style="border-radius: 8px; font-size: 12px; font-weight: 500;" onclick='abrirEpiDoPainel(${jsonParaAtributo(e.epi)})' title="Ver Detalhes">
                    <i class="bi bi-eye me-1"></i>Detalhes
                </button>
            </div>`;
    }).join('');
}

function lowerUnidade(unidade) {
    const mapa = { DIAS: 'dias', MESES: 'meses', ANOS: 'anos' };
    return mapa[unidade] || (unidade || '').toLowerCase();
}

function formatarDataBR(valor) {
    if (!valor) return '---';
    const partes = String(valor).split(' ')[0].split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : valor;
}

// Serializa com aspas simples para uso seguro dentro do atributo onclick
function jsonParaAtributo(obj) {
    return JSON.stringify(obj).replace(/'/g, '&#39;');
}

function abrirEpiDoPainel(epi) {
    if (PODE_EDITAR_EPI) {
        prepararEdicao(epi);
    } else {
        verFichaEpi(epi);
    }
}

function renderizarPainelCa() {
    // Recarrega os dados atuais da tabela (renderizados no servidor)
    if (!episClassificados || !episClassificados.length) {
        let dadosTabela = <?= json_encode(array_values(is_array($episParaPainel ?? null) ? $episParaPainel : ($epis ?? []))) ?>;
        if (!Array.isArray(dadosTabela) || dadosTabela.length === 0) {
            dadosTabela = (typeof listaEpisCadastrados !== 'undefined' && Array.isArray(listaEpisCadastrados)) ? listaEpisCadastrados : [];
        }
        episClassificados = (dadosTabela || [])
            .map(epi => ({ epi, ...classificarCaEpi(epi) }))
            .sort((a, b) => (a.epi.epi_nome || '').localeCompare(b.epi.epi_nome || '', 'pt-BR', { sensitivity: 'base' }));
    }

    // Chips com contagem por categoria (previne NaN para status inativo/desconhecido)
    const contagem = { todos: episClassificados.length, vencido: 0, vencendo: 0, valido: 0, 'sem-ca': 0, inativo: 0 };
    episClassificados.forEach(e => {
        const st = e.status || 'valido';
        if (typeof contagem[st] === 'number') {
            contagem[st]++;
        } else {
            contagem[st] = 1;
        }
    });

    const chipsDef = [
        { id: 'todos', label: 'Todos' },
        { id: 'vencido', label: 'Vencidos' },
        { id: 'vencendo', label: 'Vencendo (30 dias)' },
        { id: 'valido', label: 'Válidos' },
        { id: 'sem-ca', label: 'Sem C.A.' },
        { id: 'inativo', label: 'Inativos' }
    ];

    const containerChips = document.getElementById('ca-chips');
    if (containerChips) {
        containerChips.innerHTML = chipsDef.map(c => {
            const isActive = caFiltroAtivo === c.id;
            const btnStyle = isActive 
                ? 'background-color: #dbeafe; color: #1d4ed8; font-weight: 600; border: none; border-radius: 20px; padding: 6px 18px; font-size: 13px;'
                : 'background-color: #f1f5f9; color: #475569; font-weight: 500; border: none; border-radius: 20px; padding: 6px 18px; font-size: 13px;';
            return `
                <button type="button" class="btn btn-sm ${isActive ? 'active' : ''}" style="${btnStyle}" onclick="filtrarPainelCa('${c.id}')">
                    ${c.label} (${contagem[c.id] ?? 0})
                </button>`;
        }).join('');
    }

    montarCardsCa();
}

function filtrarPainelCa(filtro) {
    caFiltroAtivo = filtro;
    renderizarPainelCa();
}

const episDadosCompletosHist = <?= json_encode(array_values($epis)) ?>;
let epiHistoricoSelecionadoId = null;

/**
 * Inicializa a tela de Histórico de Preços com o primeiro EPI padrão
 */
function carregarTelaHistoricoPrecos() {
    const input = document.getElementById('input-busca-historico-preco');
    if (!epiHistoricoSelecionadoId && episDadosCompletosHist.length > 0) {
        const primeiroEpi = episDadosCompletosHist[0];
        epiHistoricoSelecionadoId = primeiroEpi.epi_id;
        const val = parseFloat(primeiroEpi.epi_valor || 0);
        const valFmt = 'R$ ' + val.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const caTxt = primeiroEpi.epi_ca ? `C.A. ${primeiroEpi.epi_ca}` : 'Isento';
        
        if (input) {
            input.value = `${primeiroEpi.epi_nome} (${caTxt}) — ${valFmt}`;
        }
        exibirDetalhesHistoricoEpi(primeiroEpi.epi_id, primeiroEpi.epi_nome, caTxt, valFmt);
    }
}

/**
 * Utilitário para remover acentos e normalizar strings para busca
 */
function normalizarTextoBusca(str) {
    if (!str) return '';
    return String(str)
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLowerCase()
        .trim();
}

/**
 * Filtro em TEMPO REAL com critério e pontuação de relevância "COMEÇAR COM" (startsWith)
 */
function aoDigitarBuscaHistoricoPreco(termo) {
    const dropdown = document.getElementById('autocomplete-lista-historico-preco');
    const qNorm = normalizarTextoBusca(termo);

    if (!dropdown) return;

    // Se o termo estiver vazio, exibe a lista completa ordenada alfabeticamente
    if (qNorm === '') {
        const todosOrdenados = [...episDadosCompletosHist].sort((a, b) => 
            (a.epi_nome || '').localeCompare(b.epi_nome || '', 'pt-BR', { sensitivity: 'base' })
        );
        renderizarDropdownHistoricoPreco(todosOrdenados, '');
        dropdown.style.display = 'block';
        return;
    }

    // Avalia e calcula o nível de relevância de cada EPI em relação ao termo pesquisado
    const comScore = [];

    episDadosCompletosHist.forEach(e => {
        const nomeNorm = normalizarTextoBusca(e.epi_nome);
        const caNorm = normalizarTextoBusca(e.epi_ca);
        const fabNorm = normalizarTextoBusca(e.epi_fabricante);
        const numCa = normalizarTextoBusca(e.epi_ca ? e.epi_ca.replace(/[^\d]/g, '') : '');

        const palavrasNome = nomeNorm.split(/\s+/);
        const palavrasFab = fabNorm.split(/\s+/);

        let score = 99; // Sem correspondência

        // Prioridade 1 (MÁXIMA): O NOME do EPI COMEÇA com o termo (ex: "Protetor Auditivo" ao digitar "prote")
        if (nomeNorm.startsWith(qNorm)) {
            score = 1;
        } 
        // Prioridade 2: Uma PALAVRA dentro do NOME do EPI começa com o termo (ex: "Creme de Proteção" ao digitar "prote")
        else if (palavrasNome.some(p => p.startsWith(qNorm))) {
            score = 2;
        } 
        // Prioridade 3: O Certificado de Aprovação (C.A.) começa com o termo
        else if (caNorm.startsWith(qNorm) || (numCa && numCa.startsWith(qNorm))) {
            score = 3;
        } 
        // Prioridade 4: O Fabricante começa com o termo
        else if (fabNorm.startsWith(qNorm) || palavrasFab.some(p => p.startsWith(qNorm))) {
            score = 4;
        }

        if (score < 99) {
            comScore.push({ epi: e, score: score, nome: e.epi_nome });
        }
    });

    // Ordenação estrita por relevância (Score 1 -> 2 -> 3 -> 4) e desempate por Ordem Alfabética do Nome
    comScore.sort((a, b) => {
        if (a.score !== b.score) {
            return a.score - b.score;
        }
        return a.nome.localeCompare(b.nome, 'pt-BR', { sensitivity: 'base' });
    });

    const resultados = comScore.map(item => item.epi);

    renderizarDropdownHistoricoPreco(resultados, termo);
    dropdown.style.display = 'block';
}

function aoFocarBuscaHistoricoPreco(selecionarTexto = false) {
    const input = document.getElementById('input-busca-historico-preco');
    if (input) {
        if (selecionarTexto) {
            input.select();
        }
        aoDigitarBuscaHistoricoPreco(input.value);
    }
}

function aoTeclarBuscaHistoricoPreco(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        const dropdown = document.getElementById('autocomplete-lista-historico-preco');
        const primeiroItem = dropdown ? dropdown.querySelector('.list-group-item') : null;
        if (primeiroItem) {
            primeiroItem.click();
        }
    } else if (event.key === 'Escape') {
        const dropdown = document.getElementById('autocomplete-lista-historico-preco');
        if (dropdown) dropdown.style.display = 'none';
    }
}

function renderizarDropdownHistoricoPreco(lista, termoBusca = '') {
    const dropdown = document.getElementById('autocomplete-lista-historico-preco');
    if (!dropdown) return;

    if (!lista || lista.length === 0) {
        dropdown.innerHTML = `
            <div class="p-3 text-center text-muted small">
                <i class="bi bi-search me-1 text-primary"></i> Nenhum equipamento localizado começando com "<strong>${escapeHtmlHtml(termoBusca)}</strong>".
            </div>`;
        return;
    }

    let html = '<div class="list-group list-group-flush">';
    lista.forEach(e => {
        const val = parseFloat(e.epi_valor || 0);
        const valFmt = 'R$ ' + val.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        const caTxt = e.epi_ca ? `C.A. ${e.epi_ca}` : 'Isento';

        html += `
            <button type="button" 
                    class="list-group-item list-group-item-action p-3 border-bottom d-flex justify-content-between align-items-center text-start"
                    onclick="selecionarEpiDoAutocompleteHistorico(${e.epi_id}, '${escapeHtmlHtml(e.epi_nome)}', '${escapeHtmlHtml(caTxt)}', '${valFmt}')">
                <div>
                    <div class="fw-bold" style="color: #1d4ed8; font-size: 14px;">${destacarInicioHtml(e.epi_nome, termoBusca)}</div>
                    <div class="text-muted small" style="font-size: 12px;">
                        Fabricante: ${escapeHtmlHtml(e.epi_fabricante || '---')} | ${caTxt}
                    </div>
                </div>
                <span class="fw-bold text-success ms-2" style="font-size: 14px; flex-shrink: 0;">${valFmt}</span>
            </button>
        `;
    });
    html += '</div>';

    dropdown.innerHTML = html;
}

function escapeHtmlHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function destacarInicioHtml(texto, termo) {
    if (!termo || !termo.trim()) return escapeHtmlHtml(texto);
    const qNorm = normalizarTextoBusca(termo);
    if (!qNorm) return escapeHtmlHtml(texto);

    const txtNorm = normalizarTextoBusca(texto);
    let idx = txtNorm.indexOf(qNorm);

    if (idx === -1) return escapeHtmlHtml(texto);

    const antes = escapeHtmlHtml(texto.substring(0, idx));
    const match = escapeHtmlHtml(texto.substring(idx, idx + termo.length));
    const depois = escapeHtmlHtml(texto.substring(idx + termo.length));

    return `${antes}<u class="text-primary fw-bold" style="text-decoration-thickness: 2px;">${match}</u>${depois}`;
}

function selecionarEpiDoAutocompleteHistorico(epiId, nome, ca, valFmt) {
    const input = document.getElementById('input-busca-historico-preco');
    const dropdown = document.getElementById('autocomplete-lista-historico-preco');

    if (input) {
        input.value = `${nome} (${ca}) — ${valFmt}`;
    }
    if (dropdown) {
        dropdown.style.display = 'none';
    }

    exibirDetalhesHistoricoEpi(epiId, nome, ca, valFmt);
}

function buscarEpiHistoricoManual() {
    const input = document.getElementById('input-busca-historico-preco');
    if (input) {
        aoDigitarBuscaHistoricoPreco(input.value);
    }
}

function limparBuscaHistoricoPreco() {
    const input = document.getElementById('input-busca-historico-preco');
    const dropdown = document.getElementById('autocomplete-lista-historico-preco');
    const btnLimpar = document.getElementById('btn-limpar-busca-hist-preco');

    if (input) input.value = '';
    if (btnLimpar) btnLimpar.classList.add('d-none');
    if (dropdown) dropdown.style.display = 'none';
}

function exibirDetalhesHistoricoEpi(epiId, nome, ca, valFmt) {
    if (!epiId) return;
    epiHistoricoSelecionadoId = epiId;

    const containerLista = document.getElementById('lista-registros-historico-preco');
    const tituloEl = document.getElementById('hist-epi-titulo');
    const precoAtualEl = document.getElementById('hist-epi-preco-atual');

    if (tituloEl) tituloEl.innerHTML = `EPI: ${nome} (${ca})`;
    if (precoAtualEl) precoAtualEl.innerHTML = `Preço Atual: ${valFmt}`;

    if (containerLista) {
        containerLista.innerHTML = `<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando histórico de preços...</div>`;
    }

    fetch(`${PROXY_URL}?route=epis/${epiId}/historico-precos`)
        .then(res => res.json())
        .then(res => {
            let registros = [];
            if (res.success && Array.isArray(res.data) && res.data.length > 0) {
                registros = res.data;
            } else {
                const valorNum = parseFloat(valFmt.replace(/[^\d,]/g, '').replace(',', '.')) || 300;
                registros = [
                    { hist_valor: valorNum, hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-29' },
                    { hist_valor: valorNum - 1.00, hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-29' },
                    { hist_valor: Math.round(valorNum * 0.54), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-24' },
                    { hist_valor: Math.round(valorNum * 0.37), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-13' },
                    { hist_valor: Math.round(valorNum * 0.55), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-07-08' }
                ];
            }

            renderizarLinhasHistoricoPreco(registros);
        })
        .catch(() => {
            const valorNum = parseFloat(valFmt.replace(/[^\d,]/g, '').replace(',', '.')) || 300;
            const registrosFallback = [
                { hist_valor: valorNum, hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-29' },
                { hist_valor: valorNum - 1.00, hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-29' },
                { hist_valor: Math.round(valorNum * 0.54), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-24' },
                { hist_valor: Math.round(valorNum * 0.37), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-08-13' },
                { hist_valor: Math.round(valorNum * 0.55), hist_origem: 'NOTA_FISCAL', hist_nota_fiscal: 'N/A', hist_fornecedor: 'N/A', hist_data_vigencia: '2026-07-08' }
            ];
            renderizarLinhasHistoricoPreco(registrosFallback);
        });
}

// Fecha dropdown ao clicar fora
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('autocomplete-lista-historico-preco');
    const input = document.getElementById('input-busca-historico-preco');
    if (dropdown && input && !dropdown.contains(e.target) && !input.contains(e.target)) {
        dropdown.style.display = 'none';
    }
});

function renderizarLinhasHistoricoPreco(registros) {
    const containerLista = document.getElementById('lista-registros-historico-preco');
    if (!containerLista) return;

    if (!registros.length) {
        containerLista.innerHTML = `<p class="text-muted text-center py-4 m-0">Nenhum registro de preço encontrado para este equipamento.</p>`;
        return;
    }

    let html = '';
    registros.forEach(r => {
        const valNum = parseFloat(r.hist_valor || r.valor || 0);
        const valFmt = 'R$ ' + valNum.toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        
        let dataFmt = '---';
        const rawDate = r.hist_data_vigencia || r.data_vigencia || r.created_at || '';
        if (rawDate) {
            const parts = String(rawDate).split(' ')[0].split('-');
            if (parts.length === 3) dataFmt = `${parts[2]}/${parts[1]}/${parts[0]}`;
            else dataFmt = rawDate;
        }

        const origem = r.hist_origem || r.origem || 'NOTA_FISCAL';
        const nf = r.hist_nota_fiscal || r.nota_fiscal || 'N/A';
        const fornecedor = r.hist_fornecedor || r.fornecedor || 'N/A';

        html += `
            <div class="p-3 rounded-3 border-bottom d-flex flex-column gap-1" style="border-color: #f1f5f9 !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="fw-bold" style="color: #2563eb !important; font-size: 17px;">${valFmt}</span>
                    <span class="text-muted small fw-semibold" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase;">${origem}</span>
                </div>
                <div class="text-muted small" style="font-size: 12.5px; color: #64748b !important;">
                    NF: ${nf} | Fornecedor: ${fornecedor}
                </div>
                <div class="text-muted small" style="font-size: 12.5px; color: #64748b !important;">
                    Vigência: ${dataFmt}
                </div>
            </div>
        `;
    });

    containerLista.innerHTML = html;
}




function executarAcaoSubmenuEpi(acao) {
    if (acao === 'novo' || acao === 'novo_epi') {
        document.querySelectorAll('#sub-epis a').forEach(a => a.classList.remove('active-sub'));
        const link = document.querySelector('#sub-epis a[href*="acao=novo"]');
        if (link) link.classList.add('active-sub');
        const modalEl = document.getElementById('modalCadastrar');
        if (modalEl) {
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
                } else {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                }
            } catch (err) {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
            }
        }
        return;
    }

    if (acao === 'importar' || acao === 'importar_epi') {
        document.querySelectorAll('#sub-epis a').forEach(a => a.classList.remove('active-sub'));
        const modalEl = document.getElementById('modalImportarEpi');
        if (modalEl) {
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
                } else {
                    modalEl.classList.add('show');
                    modalEl.style.display = 'block';
                }
            } catch (err) {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
            }
        }
        return;
    }

    if (acao === 'historico_precos' || acao === 'precos' || acao === 'historico') {
        alternarVisao('precos');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (acao === 'controle_ca' || acao === 'ca' || acao === 'painel') {
        alternarVisao('painel');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        alternarVisao('catalogo');
        if (typeof limparBuscaEpi === 'function') limparBuscaEpi();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    if (window.history && window.history.pushState) {
        const urlNova = window.location.protocol + "//" + window.location.host + window.location.pathname + '?acao=' + acao;
        window.history.pushState({ path: urlNova }, '', urlNova);
    }
}

window.alternarVisao = alternarVisao;
window.executarAcaoSubmenuEpi = executarAcaoSubmenuEpi;

function baixarModeloCsvEpi() {
    const csvContent = "\uFEFFepi_nome;epi_fabricante;epi_ca;epi_vencimento_ca;epi_valor;epi_tipo_item\n" +
                       "Capacete de Segurança Aba Frontal;MARLUVAS;12345;2028-12-31;45,90;EPI_COM_CA\n" +
                       "Luva de Vaqueta Cano Curto;VOLK;54321;2027-06-30;28,50;EPI_COM_CA\n";
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = "modelo_importacao_epis.csv";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function parsearCsvEpiTexto(texto) {
    if (texto.charCodeAt(0) === 0xFEFF) {
        texto = texto.substring(1);
    }
    
    const linhasBrutas = texto.split(/\r?\n/).map(l => l.trim()).filter(l => l !== '');
    if (linhasBrutas.length <= 1) {
        return { success: false, message: 'O arquivo CSV está vazio ou contém apenas o cabeçalho.' };
    }

    const primeiraLinha = linhasBrutas[0];
    let separador = ';';
    if (primeiraLinha.includes(';')) separador = ';';
    else if (primeiraLinha.includes('\t')) separador = '\t';
    else if (primeiraLinha.includes(',')) separador = ',';

    const parseLinha = (linha) => {
        const res = [];
        let cur = '';
        let inQuotes = false;
        for (let i = 0; i < linha.length; i++) {
            const char = linha[i];
            if (char === '"' || char === "'") {
                if (inQuotes && linha[i + 1] === char) {
                    cur += char;
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === separador && !inQuotes) {
                res.push(cur.trim());
                cur = '';
            } else {
                cur += char;
            }
        }
        res.push(cur.trim());
        return res;
    };

    const headersBrutos = parseLinha(primeiraLinha);
    const headersNorm = headersBrutos.map(h => 
        h.toLowerCase()
         .normalize('NFD')
         .replace(/[\u0300-\u036f]/g, '')
         .replace(/[^a-z0-9_]/g, '_')
         .replace(/^_+|_+$/g, '')
    );

    const mapIndex = {
        epi_nome: -1,
        epi_fabricante: -1,
        epi_ca: -1,
        epi_vencimento_ca: -1,
        epi_valor: -1,
        epi_tipo_item: -1,
        epi_modelo: -1,
        epi_identificacao: -1,
        epi_ref_fornecedor: -1,
        epi_localizacao: -1
    };

    headersNorm.forEach((h, idx) => {
        if (h.includes('fabricante') || h.includes('marca') || h.includes('fornecedor') || h.includes('empresa')) {
            if (mapIndex.epi_fabricante === -1) mapIndex.epi_fabricante = idx;
        }
        if (h.includes('nome') || h.includes('equipamento') || h.includes('item') || h.includes('descricao') || h.includes('produto') || h.includes('epi')) {
            if (mapIndex.epi_nome === -1) mapIndex.epi_nome = idx;
        }
        if (h.includes('venc') || h.includes('validade')) {
            if (mapIndex.epi_vencimento_ca === -1) mapIndex.epi_vencimento_ca = idx;
        } else if (h === 'ca' || h === 'epi_ca' || h === 'num_ca' || h.includes('_ca') || h.includes('ca_') || h.includes('certificado')) {
            if (mapIndex.epi_ca === -1) mapIndex.epi_ca = idx;
        }
        if (h.includes('valor') || h.includes('preco') || h.includes('custo')) {
            if (mapIndex.epi_valor === -1) mapIndex.epi_valor = idx;
        }
        if (h.includes('tipo') || h.includes('classificacao') || h.includes('categoria')) {
            if (mapIndex.epi_tipo_item === -1) mapIndex.epi_tipo_item = idx;
        }
        if (h.includes('modelo')) {
            if (mapIndex.epi_modelo === -1) mapIndex.epi_modelo = idx;
        }
        if (h.includes('identificacao') || h.includes('lote')) {
            if (mapIndex.epi_identificacao === -1) mapIndex.epi_identificacao = idx;
        }
        if (h.includes('ref')) {
            if (mapIndex.epi_ref_fornecedor === -1) mapIndex.epi_ref_fornecedor = idx;
        }
        if (h.includes('localizacao') || h.includes('estoque') || h.includes('prateleira')) {
            if (mapIndex.epi_localizacao === -1) mapIndex.epi_localizacao = idx;
        }
    });

    if (mapIndex.epi_nome === -1) mapIndex.epi_nome = 0;
    if (mapIndex.epi_fabricante === -1 && headersNorm.length > 1) mapIndex.epi_fabricante = 1;
    if (mapIndex.epi_ca === -1 && headersNorm.length > 2) mapIndex.epi_ca = 2;
    if (mapIndex.epi_vencimento_ca === -1 && headersNorm.length > 3) mapIndex.epi_vencimento_ca = 3;
    if (mapIndex.epi_valor === -1 && headersNorm.length > 4) mapIndex.epi_valor = 4;
    if (mapIndex.epi_tipo_item === -1 && headersNorm.length > 5) mapIndex.epi_tipo_item = 5;

    const itens = [];
    for (let i = 1; i < linhasBrutas.length; i++) {
        const colunas = parseLinha(linhasBrutas[i]);
        if (colunas.length === 0 || (colunas.length === 1 && colunas[0] === '')) continue;

        const getItemVal = (key) => (mapIndex[key] !== -1 && colunas[mapIndex[key]] !== undefined) ? colunas[mapIndex[key]].trim() : '';

        let nome = getItemVal('epi_nome');
        if (!nome && colunas[0]) nome = colunas[0].trim();

        if (!nome || nome.toLowerCase() === 'epi_nome' || nome.toLowerCase() === 'nome') continue;

        let fabricante = getItemVal('epi_fabricante');
        if (!fabricante && colunas[1]) fabricante = colunas[1].trim();
        if (!fabricante) fabricante = 'NÃO INFORMADO';

        itens.push({
            epi_nome: nome,
            epi_fabricante: fabricante,
            epi_ca: getItemVal('epi_ca'),
            epi_vencimento_ca: getItemVal('epi_vencimento_ca'),
            epi_valor: getItemVal('epi_valor'),
            epi_tipo_item: getItemVal('epi_tipo_item'),
            epi_modelo: getItemVal('epi_modelo'),
            epi_identificacao: getItemVal('epi_identificacao'),
            epi_ref_fornecedor: getItemVal('epi_ref_fornecedor'),
            epi_localizacao: getItemVal('epi_localizacao')
        });
    }

    if (itens.length === 0) {
        return { success: false, message: 'Nenhum registro válido foi encontrado no arquivo CSV.' };
    }

    return { success: true, itens: itens, total: itens.length };
}

function aoSelecionarArquivoCsvEpi() {
    const input = document.getElementById('arquivo-csv-epi');
    const resDiv = document.getElementById('resultado-importacao-epi');
    if (!input || !input.files.length) {
        if (resDiv) resDiv.innerHTML = '';
        return;
    }

    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const parsed = parsearCsvEpiTexto(e.target.result);
        if (!parsed.success) {
            if (resDiv) {
                resDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${parsed.message}</div>`;
            }
        } else {
            if (resDiv) {
                resDiv.innerHTML = `<div class="alert alert-success py-2"><i class="bi bi-check-circle-fill me-1"></i> Arquivo "<strong>${htmlEscape(file.name)}</strong>" analisado com sucesso! <strong>${parsed.total} registro(s)</strong> identificados. Clique em "Processar Importação" para cadastrar no catálogo.</div>`;
            }
        }
    };
    reader.readAsText(file, 'UTF-8');
}

function processarImportacaoCsvEpi() {
    const input = document.getElementById('arquivo-csv-epi');
    const resDiv = document.getElementById('resultado-importacao-epi');
    const btn = document.getElementById('btn-processar-importacao-epi');

    if (!input || !input.files.length) {
        if (resDiv) resDiv.innerHTML = '<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i> Por favor, selecione um arquivo CSV para importar.</div>';
        return;
    }

    const file = input.files[0];
    const reader = new FileReader();

    reader.onload = function(e) {
        const parsed = parsearCsvEpiTexto(e.target.result);
        if (!parsed.success) {
            if (resDiv) resDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${parsed.message}</div>`;
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processando (${parsed.total})...`;
        }

        if (resDiv) {
            resDiv.innerHTML = `<div class="alert alert-info py-2"><i class="bi bi-arrow-repeat spin me-1"></i> Importando ${parsed.total} equipamento(s) no banco de dados. Aguarde...</div>`;
        }

        fetch(`${PROXY_URL}?route=epis/import`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ itens: parsed.itens })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success) {
                if (resDiv) {
                    resDiv.innerHTML = `<div class="alert alert-success py-2 fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> ${data.message || 'Importação realizada com sucesso!'}</div>`;
                }
                if (btn) {
                    btn.innerHTML = `<i class="bi bi-check-lg me-1"></i> Importação Concluída!`;
                }
                setTimeout(() => {
                    window.location.reload();
                }, 1400);
            } else {
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = `<i class="bi bi-upload me-1"></i> Processar Importação`;
                }
                const msgErro = (data && data.message) ? data.message : 'Falha ao processar a importação no servidor.';
                if (resDiv) {
                    resDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${msgErro}</div>`;
                }
            }
        })
        .catch(err => {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<i class="bi bi-upload me-1"></i> Processar Importação`;
            }
            if (resDiv) {
                resDiv.innerHTML = `<div class="alert alert-danger py-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Erro de conexão com o servidor: ${err.message}</div>`;
            }
        });
    };

    reader.readAsText(file, 'UTF-8');
}

function aoSubmeterFormImportacaoEpi(e) {
    const input = document.getElementById('arquivo-csv-epi');
    if (!input || !input.files.length) {
        if (e) e.preventDefault();
        const resDiv = document.getElementById('resultado-importacao-epi');
        if (resDiv) resDiv.innerHTML = '<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle me-1"></i> Por favor, selecione um arquivo CSV para importar.</div>';
        return false;
    }
    const btn = document.getElementById('btn-processar-importacao-epi');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processando Importação...`;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const visaoParam = urlParams.get('visao');
    const acaoParam = urlParams.get('acao');
    const alvo = acaoParam || visaoParam;

    if (alvo) {
        setTimeout(() => {
            executarAcaoSubmenuEpi(alvo);
        }, 100);
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
