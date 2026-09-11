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
$allowedProfiles = ['ADMINISTRADOR', 'GESTOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'];
if (!in_array($userProfile, $allowedProfiles, true)) {
    header('Location: 403.php');
    exit;
}

$permiteVisualizarCustos = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true);

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

$tzBrasil = new DateTimeZone('America/Sao_Paulo');
$dataAtual = new DateTime('now', $tzBrasil);
$dataEmissao = $dataAtual->format('d/m/Y H:i');

$epiId = isset($_GET['epi_id']) ? (int)$_GET['epi_id'] : 0;
$dataInicio = trim($_GET['data_inicio'] ?? $dataAtual->format('Y-m-01'));
$dataFim = trim($_GET['data_fim'] ?? $dataAtual->format('Y-m-d'));
$setor = trim($_GET['setor'] ?? '');

$modoEspecifico = false;
$nomeEpiSelecionado = 'Geral de EPIs';
$caEpiSelecionado = '';

$indicadores = [
    'total_unidades' => 0,
    'total_em_uso' => 0,
    'total_devolvidos' => 0,
    'custo_total' => 0.00
];
$registros = [];
$erro = null;

try {
    if ($epiId > 0) {
        // Busca detalhes do EPI selecionado
        $resEpi = $api->get('epis/' . $epiId);
        if (isset($resEpi['success']) && $resEpi['success'] && !empty($resEpi['data'])) {
            $modoEspecifico = true;
            $nomeEpiSelecionado = $resEpi['data']['epi_nome'] ?? 'EPI #' . $epiId;
            $caEpiSelecionado = $resEpi['data']['epi_ca'] ?? '';
        }
    }

    $queryParams = [
        'data_inicio' => $dataInicio,
        'data_fim' => $dataFim
    ];
    if ($setor !== '') $queryParams['departamento'] = $setor;
    if ($modoEspecifico) $queryParams['epi_id'] = $epiId;

    $queryString = http_build_query($queryParams);
    $response = $api->get('relatorios/epis/geral?' . $queryString);

    if (isset($response['success']) && $response['success'] && isset($response['data'])) {
        $dados = $response['data'];
        $itens = $dados['registros'] ?? $dados['itens'] ?? (is_array($dados) && isset($dados[0]) ? $dados : []);

        $totUnidades = 0;
        $totEmUso = 0;
        $totDevolvidos = 0;
        $totCusto = 0.0;

        foreach ($itens as $item) {
            // Se estiver em modo específico, filtra caso a API não tenha filtrado no backend
            if ($modoEspecifico && !empty($nomeEpiSelecionado)) {
                $itemEpiNome = $item['epi'] ?? $item['epi_nome'] ?? '';
                if ($itemEpiNome !== '' && stripos($itemEpiNome, $nomeEpiSelecionado) === false) {
                    continue;
                }
            }

            $qtd = (int)($item['quantidade'] ?? $item['ite_quantidade'] ?? 1);
            $val = (float)($item['valor_total'] ?? $item['ite_custo_total'] ?? 0);
            $status = strtoupper((string)($item['status'] ?? $item['ite_status_item'] ?? $item['ent_status'] ?? 'EM USO'));
            $motivo = strtoupper((string)($item['motivo'] ?? $item['ent_motivo'] ?? ''));

            $totUnidades += $qtd;
            $totCusto += $val;

            if (str_contains($status, 'DEVOLVIDO') || str_contains($motivo, 'DEVOLU')) {
                $totDevolvidos += $qtd;
            } else {
                $totEmUso += $qtd;
            }

            $registros[] = [
                'data' => !empty($item['data'] ?? $item['ent_data_retirada']) ? (new DateTime($item['data'] ?? $item['ent_data_retirada']))->format('d/m/Y H:i') : '---',
                'funcionario' => $item['funcionario'] ?? $item['fun_nome'] ?? '---',
                'setor' => $item['setor'] ?? $item['fun_departamento'] ?? '---',
                'epi' => $item['epi'] ?? $item['epi_nome'] ?? $nomeEpiSelecionado,
                'ca' => $item['ca'] ?? $item['epi_ca'] ?? $caEpiSelecionado,
                'tamanho' => $item['tamanho'] ?? $item['ite_tamanho'] ?? 'Único',
                'quantidade' => $qtd,
                'motivo' => $item['motivo'] ?? $item['ent_motivo'] ?? 'FORNECIMENTO',
                'responsavel' => $item['responsavel'] ?? $item['usuario_responsavel'] ?? 'almoxarifado',
                'valor_total' => $val
            ];
        }

        $indicadores['total_unidades'] = $totUnidades;
        $indicadores['total_em_uso'] = $totEmUso;
        $indicadores['total_devolvidos'] = $totDevolvidos;
        $indicadores['custo_total'] = $totCusto;
    } else {
        $erro = $response['message'] ?? 'Nenhum registro de consumo encontrado para os critérios selecionados.';
    }
} catch (\Throwable $e) {
    $erro = 'Erro ao consultar relatório de consumo: ' . $e->getMessage();
}

// Log de Auditoria
try {
    $api->post('logs/registrar-exportacao', [
        'quantidade' => count($registros),
        'filtros' => 'Relatório de Consumo — EPI: ' . $nomeEpiSelecionado . ' — Período: ' . (new DateTime($dataInicio))->format('d/m/Y') . ' a ' . (new DateTime($dataFim))->format('d/m/Y') . ' — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório de Consumo e Distribuição por EPI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 9.5px;
            margin: 0;
            color: #1E293B;
            line-height: 1.25;
            background-color: #FFFFFF;
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
        .header {
            background-color: #305BD3;
            color: #FFFFFF;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
        }
        .header p {
            margin: 3px 0 0 0;
            font-size: 9px;
            opacity: 0.92;
        }
        .kpi-container {
            display: flex;
            flex-wrap: nowrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        .kpi-card {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 6px 10px;
            flex: 1;
            text-align: center;
        }
        .kpi-card h3 {
            margin: 0;
            font-size: 7.5px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-card p {
            margin: 2px 0 0 0;
            font-size: 13px;
            font-weight: 700;
            color: #305BD3;
        }
        .kpi-card.kpi-green p { color: #10B981; }
        .kpi-card.kpi-orange p { color: #F59E0B; }
        .kpi-card.kpi-red p { color: #EF4444; }

        h2 {
            font-size: 11.5px;
            margin: 10px 0 6px 0;
            color: #1E293B;
            font-weight: 700;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        tr {
            page-break-inside: avoid;
        }
        tr:nth-child(even) {
            background-color: #F8FAFC;
        }
        th, td {
            border: 1px solid #E2E8F0;
            padding: 5px 7px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #305BD3;
            color: #FFFFFF;
            font-weight: 600;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .footer {
            margin-top: 16px;
            border-top: 1px solid #E2E8F0;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 7.5px;
            color: #94A3B8;
        }
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
            .header { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <div class="brand">
        <i class="bi bi-box-seam text-primary"></i>
        <span>Gestão EPI — Relatório de Consumo por Equipamento</span>
    </div>
    <div class="buttons">
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-action btn-close-view" onclick="window.close(); if(window.opener){window.opener.focus();}else{location.href='relatorios.php';}">
            <i class="bi bi-arrow-left"></i> Voltar
        </button>
    </div>
</div>

<div class="document-container">
    <?php if ($erro && empty($registros)): ?>
        <div class="alert-error">
            <i class="bi bi-info-circle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <h1>Gestão EPI — <?= $modoEspecifico ? 'Relatório de Consumo do Equipamento: ' . htmlspecialchars($nomeEpiSelecionado) . (!empty($caEpiSelecionado) ? ' (C.A. ' . htmlspecialchars((string)$caEpiSelecionado) . ')' : '') : 'Relatório Geral de Consumo de EPIs' ?></h1>
        <p>Período: <?= (new DateTime($dataInicio))->format('d/m/Y') ?> a <?= (new DateTime($dataFim))->format('d/m/Y') ?> | Emitido em: <?= $dataEmissao ?> por: <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</p>
    </div>

    <div class="kpi-container">
        <?php if ($modoEspecifico): ?>
            <div class="kpi-card">
                <h3>Total Fornecido</h3>
                <p><?= $indicadores['total_unidades'] ?> un</p>
            </div>
            <div class="kpi-card kpi-green">
                <h3>Atualmente em Uso</h3>
                <p><?= $indicadores['total_em_uso'] ?> un</p>
            </div>
            <div class="kpi-card kpi-orange">
                <h3>Devolvidos / Trocados</h3>
                <p><?= $indicadores['total_devolvidos'] ?> un</p>
            </div>
        <?php else: ?>
            <div class="kpi-card">
                <h3>Unidades Totais</h3>
                <p><?= $indicadores['total_unidades'] ?> un</p>
            </div>
            <div class="kpi-card kpi-green">
                <h3>Devoluções</h3>
                <p><?= $indicadores['total_devolvidos'] ?> un</p>
            </div>
        <?php endif; ?>

        <?php if ($permiteVisualizarCustos && isset($indicadores['custo_total'])): ?>
        <div class="kpi-card kpi-red">
            <h3>Custo do Consumo</h3>
            <p>R$ <?= number_format($indicadores['custo_total'], 2, ',', '.') ?></p>
        </div>
        <?php endif; ?>
    </div>

    <h2>Detalhamento das Saídas / Distribuição</h2>
    <?php if (empty($registros)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhum registro de fornecimento encontrado para os critérios informados.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Data</th>
                <th style="width: 20%;">Funcionário</th>
                <th style="width: 15%;">Setor</th>
                <th style="width: 18%;">EPI</th>
                <th style="width: 8%;" class="text-center">C.A.</th>
                <th style="width: 6%;" class="text-center">Tam</th>
                <th style="width: 5%;" class="text-center">Qtd</th>
                <th style="width: 8%;">Motivo</th>
                <?php if ($permiteVisualizarCustos): ?>
                <th style="width: 8%;" class="text-right">Valor Total</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $reg): ?>
            <tr>
                <td><?= htmlspecialchars($reg['data']) ?></td>
                <td><b><?= htmlspecialchars($reg['funcionario']) ?></b></td>
                <td><?= htmlspecialchars($reg['setor']) ?></td>
                <td><?= htmlspecialchars($reg['epi']) ?></td>
                <td class="text-center"><?= htmlspecialchars((string)$reg['ca']) ?></td>
                <td class="text-center"><?= htmlspecialchars($reg['tamanho']) ?></td>
                <td class="text-center"><b><?= $reg['quantidade'] ?></b></td>
                <td><?= htmlspecialchars($reg['motivo']) ?></td>
                <?php if ($permiteVisualizarCustos): ?>
                <td class="text-right">R$ <?= number_format($reg['valor_total'], 2, ',', '.') ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer">
        <span>Gerado eletronicamente pelo aplicativo Gestão EPI</span>
        <span>Emissão: <?= $dataEmissao ?></span>
    </div>
</div>

</body>
</html>
