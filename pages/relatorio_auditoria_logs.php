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

$filtrosDescricoes = [];
$queryArray = [];

$usuarioFiltro = trim($_GET['usuario'] ?? '');
if ($usuarioFiltro !== '') {
    $queryArray[] = 'usuario=' . urlencode($usuarioFiltro);
    $filtrosDescricoes[] = 'Usuário: ' . htmlspecialchars($usuarioFiltro);
}

$acaoFiltro = trim($_GET['acao'] ?? '');
if ($acaoFiltro !== '') {
    $queryArray[] = 'acao=' . urlencode($acaoFiltro);
    $filtrosDescricoes[] = 'Ação: ' . htmlspecialchars($acaoFiltro);
}

$entidadeFiltro = trim($_GET['entidade'] ?? '');
if ($entidadeFiltro !== '') {
    $queryArray[] = 'entidade=' . urlencode($entidadeFiltro);
    $filtrosDescricoes[] = 'Entidade: ' . htmlspecialchars($entidadeFiltro);
}

$dataInicio = trim($_GET['data_inicio'] ?? '');
if ($dataInicio !== '') {
    $queryArray[] = 'data_inicio=' . urlencode($dataInicio);
    $filtrosDescricoes[] = 'Data Início: ' . (new DateTime($dataInicio))->format('d/m/Y');
}

$dataFim = trim($_GET['data_fim'] ?? '');
if ($dataFim !== '') {
    $queryArray[] = 'data_fim=' . urlencode($dataFim);
    $filtrosDescricoes[] = 'Data Fim: ' . (new DateTime($dataFim))->format('d/m/Y');
}

$palavraChave = trim($_GET['palavra_chave'] ?? '');
if ($palavraChave !== '') {
    $queryArray[] = 'palavra_chave=' . urlencode($palavraChave);
    $filtrosDescricoes[] = 'Termo: "' . htmlspecialchars($palavraChave) . '"';
}

$filtrosAplicados = !empty($filtrosDescricoes) ? implode('; ', $filtrosDescricoes) . ';' : 'Todos os registros;';

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
        'filtros' => 'Relatório Auditoria Logs v2 — ' . $filtrosAplicados . ' — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório de Auditoria de Logs de Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 1.2cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            color: #1F2937;
            background-color: #FFFFFF;
            font-size: 9.5px;
            line-height: 1.35;
        }
        .action-bar {
            background: #0F172A;
            color: #FFFFFF;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #3B82F6;
        }
        .action-bar .brand {
            font-size: 14px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .action-bar .buttons {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .btn-action:hover {
            opacity: 0.9;
        }
        .btn-print {
            background-color: #2563EB;
            color: #FFFFFF;
        }
        .btn-close-view {
            background-color: #475569;
            color: #FFFFFF;
        }
        .document-container {
            max-width: 1100px;
            margin: 20px auto;
            padding: 20px 24px;
            background: #FFFFFF;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-radius: 8px;
        }
        .report-header {
            margin-bottom: 14px;
            border-bottom: 2px solid #305BD3;
            padding-bottom: 8px;
        }
        .report-header h1 {
            font-size: 16px;
            font-weight: 700;
            color: #305BD3;
            margin: 0 0 4px 0;
        }
        .report-header .metadata {
            font-size: 9.5px;
            color: #4B5563;
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        tr {
            page-break-inside: avoid;
        }
        th, td {
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
            border-bottom: 1px solid #E5E7EB;
            word-wrap: break-word;
        }
        th {
            background-color: #F3F4F6;
            color: #111827;
            font-weight: 700;
            font-size: 9px;
            text-transform: uppercase;
            border-top: 1px solid #E5E7EB;
            border-bottom: 1px solid #D1D5DB;
        }
        .col-data { width: 13%; }
        .col-usuario { width: 12%; }
        .col-perfil { width: 14%; }
        .col-acao { width: 12%; }
        .col-entidade { width: 12%; }
        .col-reg-id { width: 6%; text-align: center; }
        .col-descricao { width: 31%; }

        .report-footer {
            margin-top: 24px;
            border-top: 1px solid #E5E7EB;
            padding-top: 8px;
            font-size: 8px;
            color: #6B7280;
            display: flex;
            justify-content: space-between;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 600;
        }
        .badge-insert { background: #DCFCE7; color: #166534; }
        .badge-update { background: #FEF3C7; color: #92400E; }
        .badge-delete { background: #FEE2E2; color: #991B1B; }
        .badge-export { background: #E0E7FF; color: #3730A3; }
        .badge-auth { background: #F3E8FF; color: #6B21A8; }
        .alert-error {
            background-color: #FEF2F2;
            border: 1px solid #F87171;
            color: #991B1B;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 11px;
        }
        @media print {
            .no-print, .action-bar { display: none !important; }
            .document-container { box-shadow: none; margin: 0; padding: 0; max-width: 100%; }
            body { margin: 0; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <div class="brand">
        <i class="bi bi-fingerprint text-primary"></i>
        <span>Gestão EPI — Relatório Oficial de Auditoria de Logs de Sistema</span>
    </div>
    <div class="buttons">
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-action btn-close-view" onclick="window.close(); if(window.opener){window.opener.focus();}else{location.href='auditoria.php';}">
            <i class="bi bi-arrow-left"></i> Voltar
        </button>
    </div>
</div>

<div class="document-container">
    <?php if ($erro && empty($logs)): ?>
        <div class="alert-error">
            <i class="bi bi-info-circle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="report-header">
        <h1>Gestão EPI — Relatório de Auditoria de Logs de Sistema</h1>
        <div class="metadata"><b>Emissão:</b> <?= $dataEmissao ?> | <b>Emitido por:</b> <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</div>
        <div class="metadata"><b>Filtros aplicados:</b> <?= $filtrosAplicados ?> | <b>Exportação ID:</b> <?= $idExportacao ?></div>
        <div class="metadata"><b>Total de registros exportados:</b> <?= count($logs) ?> | <b>Modelo do relatório:</b> v2 (Conformidade Legal)</div>
    </div>

    <?php if (empty($logs)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhum registro de log retornado para os critérios especificados.</p>
    <?php else: ?>
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
            <?php foreach ($logs as $log): 
                $dtRaw = $log['aud_data_hora'] ?? $log['datahora'] ?? '';
                $dtFmt = !empty($dtRaw) ? (new DateTime($dtRaw))->format('d/m/Y H:i') : '---';
                $usu = $log['usu_login'] ?? $log['usuario'] ?? 'Sistema';
                $perfil = $log['usu_perfil'] ?? $log['perfil'] ?? '---';
                $acao = strtoupper((string)($log['aud_acao'] ?? $log['acao'] ?? 'LOG'));
                $entidade = $log['aud_entidade'] ?? $log['entidade'] ?? '---';
                $regId = $log['aud_registro_id'] ?? $log['reg_id'] ?? '-';
                $desc = $log['aud_descricao'] ?? $log['descricao'] ?? '';

                $badgeClass = 'badge-auth';
                if ($acao === 'INSERT' || $acao === 'CRIACAO' || $acao === 'ENTREGA') $badgeClass = 'badge-insert';
                elseif ($acao === 'UPDATE' || $acao === 'ALTERACAO' || $acao === 'DEVOLUCAO') $badgeClass = 'badge-update';
                elseif ($acao === 'DELETE' || $acao === 'EXCLUSAO') $badgeClass = 'badge-delete';
                elseif (str_contains($acao, 'EXPORT')) $badgeClass = 'badge-export';
            ?>
            <tr>
                <td class="col-data"><?= htmlspecialchars($dtFmt) ?></td>
                <td class="col-usuario"><b><?= htmlspecialchars($usu) ?></b></td>
                <td class="col-perfil"><?= htmlspecialchars($perfil) ?></td>
                <td class="col-acao"><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($acao) ?></span></td>
                <td class="col-entidade"><?= htmlspecialchars($entidade) ?></td>
                <td class="col-reg-id">#<?= htmlspecialchars((string)$regId) ?></td>
                <td class="col-descricao"><?= htmlspecialchars($desc) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="report-footer">
        <span>Exportação ID: <?= $idExportacao ?> — Documento gerado automaticamente pelo ecossistema Gestão EPI</span>
        <span>Data: <?= $dataEmissao ?></span>
    </div>
</div>

</body>
</html>
