<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validação de Sessão
if (!isset($_SESSION['token']) || $_SESSION['token'] === '' || !isset($_SESSION['usuario'])) {
    header('Location: ../login.php');
    exit;
}

$user = $_SESSION['usuario'];
$userProfile = $user['usu_perfil'] ?? '';
$userLogin = $user['usu_login'] ?? 'Sistema';

// Controle de Acesso RBAC
$allowedProfiles = ['ADMINISTRADOR', 'GESTOR', 'TECNICO_SST'];
if (!in_array($userProfile, $allowedProfiles, true)) {
    header('Location: 403.php');
    exit;
}

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

// Parâmetros de Filtro
$filtrosDescricoes = [];
$queryArray = [];

$usuarioFiltro = trim($_GET['usuario'] ?? '');
if ($usuarioFiltro !== '') {
    $queryArray[] = 'usuario=' . urlencode($usuarioFiltro);
    $filtrosDescricoes[] = 'Usuário=' . htmlspecialchars($usuarioFiltro);
}

$acaoFiltro = trim($_GET['acao'] ?? '');
if ($acaoFiltro !== '') {
    $queryArray[] = 'acao=' . urlencode($acaoFiltro);
    $filtrosDescricoes[] = 'Ação=' . htmlspecialchars($acaoFiltro);
}

$entidadeFiltro = trim($_GET['entidade'] ?? '');
if ($entidadeFiltro !== '') {
    $queryArray[] = 'entidade=' . urlencode($entidadeFiltro);
    $filtrosDescricoes[] = 'Entidade=' . htmlspecialchars($entidadeFiltro);
}

$dataInicio = trim($_GET['data_inicio'] ?? '');
if ($dataInicio !== '') {
    $queryArray[] = 'data_inicio=' . urlencode($dataInicio);
    $filtrosDescricoes[] = 'Data Início=' . (new DateTime($dataInicio))->format('d/m/Y');
}

$dataFim = trim($_GET['data_fim'] ?? '');
if ($dataFim !== '') {
    $queryArray[] = 'data_fim=' . urlencode($dataFim);
    $filtrosDescricoes[] = 'Data Fim=' . (new DateTime($dataFim))->format('d/m/Y');
}

$palavraChave = trim($_GET['palavra_chave'] ?? '');
if ($palavraChave !== '') {
    $queryArray[] = 'palavra_chave=' . urlencode($palavraChave);
    $filtrosDescricoes[] = 'Termo="' . htmlspecialchars($palavraChave) . '"';
}

$filtrosAplicados = !empty($filtrosDescricoes) ? implode('; ', $filtrosDescricoes) . ';' : 'Todos os registros;';

// Consulta os dados reais da API
$logs = [];
$endpoint = 'logs' . (!empty($queryArray) ? '?' . implode('&', $queryArray) : '');
$erro = null;

try {
    $response = $api->get($endpoint);
    if (isset($response['success']) && $response['success'] && is_array($response['data'])) {
        $logs = $response['data'];
    } else {
        $erro = $response['message'] ?? 'Falha ao recuperar logs da API.';
    }
} catch (\Throwable $e) {
    $erro = 'Erro na comunicação: ' . $e->getMessage();
}

$tzBrasil = new DateTimeZone('America/Sao_Paulo');
$dataEmissao = (new DateTime('now', $tzBrasil))->format('d/m/Y H:i:s');
$idExportacao = 'AUD-' . (new DateTime('now', $tzBrasil))->format('Ymd-His') . '-' . sprintf('%04d', count($logs));

// Grava log de auditoria da exportação no backend
try {
    $api->post('logs/registrar-exportacao', [
        'quantidade' => count($logs),
        'filtros' => 'Relatório Auditoria v2 — ' . $filtrosAplicados . ' — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório de Auditoria de Logs de Sistema</title>
    <style>
        /* Estilos Globais */
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #1F2937;
            background-color: #FFFFFF;
            font-size: 12px;
            line-height: 1.4;
        }

        /* Estilo da Página Paisagem (A4) */
        @page {
            size: A4 landscape;
            margin: 1.5cm;
        }

        /* Barra de Ações na Visualização em Tela */
        .action-bar {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-print {
            background-color: #305BD3;
            color: #FFFFFF;
        }
        .btn-print:hover {
            background-color: #2446A8;
        }
        .btn-close-window {
            background-color: #E2E8F0;
            color: #334155;
        }
        .btn-close-window:hover {
            background-color: #CBD5E1;
        }

        /* Cabeçalho do Relatório */
        .report-header {
            margin-bottom: 20px;
        }
        .report-header h1 {
            font-size: 18px;
            font-weight: bold;
            color: #1F2937;
            margin: 0 0 6px 0;
        }
        .report-header .metadata {
            font-size: 11px;
            color: #4B5563;
            margin: 2px 0;
        }

        /* Estrutura da Tabela */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            page-break-inside: auto;
        }
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        th, td {
            padding: 8px 10px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #E5E7EB;
            word-wrap: break-word;
            word-break: break-all;
        }

        /* Cabeçalho da Tabela */
        th {
            background-color: #F3F4F6;
            color: #000000;
            font-weight: bold;
            font-size: 11px;
            border-top: 1px solid #E5E7EB;
            border-bottom: 1px solid #E5E7EB;
        }

        /* Distribuição Proporcional das Colunas (Idêntica ao Android e ao Template) */
        .col-data { width: 13%; }
        .col-usuario { width: 12%; }
        .col-perfil { width: 17%; }
        .col-acao { width: 14%; }
        .col-entidade { width: 12%; }
        .col-reg-id { width: 5%; }
        .col-descricao { width: 27%; }

        /* Rodapé da Página */
        .report-footer {
            margin-top: 30px;
            border-top: 1px solid #4B5563;
            padding-top: 8px;
            font-size: 10px;
            color: #4B5563;
            text-align: left;
        }

        /* Otimizações para Impressão */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                margin: 0;
            }
            table {
                width: 100%;
            }
            th {
                background-color: #F3F4F6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <div>
        <strong>Visualização de Impressão (A4 Paisagem)</strong>
        <span style="color: #64748B; margin-left: 8px;">Modelo v2 Oficial</span>
    </div>
    <div style="display: flex; gap: 8px;">
        <button class="btn-action btn-print" onclick="window.print()">
            <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/><path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2H5zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4V3zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2H5zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1z"/></svg>
            Imprimir / Salvar PDF
        </button>
        <button class="btn-action btn-close-window" onclick="window.close()">Fechar</button>
    </div>
</div>

<div class="report-header">
    <h1>Gestão EPI — Relatório de Auditoria de Logs de Sistema</h1>
    <div class="metadata"><b>Emissão:</b> <?= $dataEmissao ?> | <b>Emitido por:</b> <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</div>
    <div class="metadata"><b>Filtros aplicados:</b> <?= $filtrosAplicados ?> | <b>Exportação ID:</b> <?= $idExportacao ?></div>
    <div class="metadata"><b>Total de registros exportados:</b> <?= count($logs) ?> | <b>Modelo do relatório:</b> v2</div>
</div>

<?php if ($erro !== null): ?>
    <div style="padding: 12px; background-color: #FEF2F2; border: 1px solid #F87171; color: #991B1B; border-radius: 6px; margin-top: 15px;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th class="col-data">Data/Hora</th>
            <th class="col-usuario">Usuário</th>
            <th class="col-perfil">Perfil</th>
            <th class="col-acao">Ação</th>
            <th class="col-entidade">Entidade</th>
            <th class="col-reg-id">Reg/ID</th>
            <th class="col-descricao">Descrição da Ocorrência</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($logs)): ?>
            <tr>
                <td colspan="7" style="text-align: center; color: #6B7280; padding: 24px;">Nenhum registro de auditoria localizado para os filtros informados.</td>
            </tr>
        <?php else: ?>
            <?php foreach ($logs as $log): ?>
                <?php
                // Formatação de data/hora
                $dataLog = '---';
                if (!empty($log['log_datahora'])) {
                    try {
                        $dt = new DateTime($log['log_datahora'], new DateTimeZone('UTC'));
                        $dt->setTimezone($tzBrasil);
                        $dataLog = $dt->format('d/m/Y H:i');
                    } catch (\Throwable $e) {
                        $dataLog = substr((string)$log['log_datahora'], 0, 16);
                    }
                }

                $usuarioNome = $log['usu_login'] ?? 'Sistema/Automat.';
                $perfilNome = $log['usu_perfil'] ?? $log['perfil'] ?? '---';
                $acaoNome = $log['log_acao'] ?? '---';
                $entidadeNome = $log['log_tabela'] ?? '---';
                $regId = $log['log_registro_id'] ?? '---';

                $ocorrencia = $log['log_ocorrencia'] ?? null;
                if (empty($ocorrencia)) {
                    $detalhesJson = json_decode($log['log_detalhes'] ?? '', true);
                    $ocorrencia = $detalhesJson['ocorrencia'] ?? 'Sem descrição.';
                }
                ?>
                <tr>
                    <td class="col-data"><?= htmlspecialchars((string)$dataLog) ?></td>
                    <td class="col-usuario"><?= htmlspecialchars((string)$usuarioNome) ?></td>
                    <td class="col-perfil"><?= htmlspecialchars((string)$perfilNome) ?></td>
                    <td class="col-acao"><?= htmlspecialchars((string)$acaoNome) ?></td>
                    <td class="col-entidade"><?= htmlspecialchars((string)$entidadeNome) ?></td>
                    <td class="col-reg-id"><?= htmlspecialchars((string)$regId) ?></td>
                    <td class="col-descricao"><?= htmlspecialchars((string)$ocorrencia) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="report-footer">
    Página 1 — Exportação: <?= $idExportacao ?> — Documento gerado automaticamente pelo sistema em <?= $dataEmissao ?>
</div>

</body>
</html>
