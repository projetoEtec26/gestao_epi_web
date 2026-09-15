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
$allowedProfiles = ['ADMINISTRADOR', 'GESTOR', 'TECNICO_SST', 'RH_ADMINISTRATIVO'];
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

// Filtros com suporte flexível aos nomes de parâmetros da API e da URL
$rawInicio = $_GET['data_inicial'] ?? ($_GET['data_inicio'] ?? '');
$rawFim = $_GET['data_final'] ?? ($_GET['data_fim'] ?? '');

$dataInicio = trim(explode(' ', (string)$rawInicio)[0]);
$dataFim = trim(explode(' ', (string)$rawFim)[0]);

if (empty($dataInicio)) {
    $dataInicio = $dataAtual->format('Y-m-01');
}
if (empty($dataFim)) {
    $dataFim = $dataAtual->format('Y-m-d');
}

$funcionario = trim($_GET['funcionario_nome'] ?? ($_GET['funcionario'] ?? ''));
$departamento = trim($_GET['departamento'] ?? '');
$cargo = trim($_GET['cargo'] ?? '');
$motivo = trim($_GET['motivo'] ?? '');
$categoria = trim($_GET['categoria'] ?? '');
$comCa = trim($_GET['item_com_ca'] ?? ($_GET['com_ca'] ?? ''));
$limite = (int)($_GET['limite'] ?? 1000);

$queryParams = [
    'data_inicial' => $dataInicio . ' 00:00:00',
    'data_final' => $dataFim . ' 23:59:59',
    'status_entrega' => 'FINALIZADA',
    'ordenacao' => 'data_desc',
    'pagina' => 1,
    'limite' => $limite > 0 ? $limite : 1000
];

$filtrosFormatadosArray = [];
$filtrosFormatadosArray[] = 'Período: ' . (new DateTime($dataInicio))->format('d/m/Y') . ' a ' . (new DateTime($dataFim))->format('d/m/Y');

if ($funcionario !== '' && strtolower($funcionario) !== 'todos') {
    $queryParams['funcionario_nome'] = $funcionario;
    $filtrosFormatadosArray[] = 'Funcionário: ' . htmlspecialchars($funcionario);
}
if ($departamento !== '') {
    $queryParams['departamento'] = $departamento;
    $filtrosFormatadosArray[] = 'Setor: ' . htmlspecialchars($departamento);
}
if ($cargo !== '') {
    $queryParams['cargo'] = $cargo;
    $filtrosFormatadosArray[] = 'Cargo: ' . htmlspecialchars($cargo);
}
if ($motivo !== '') {
    $queryParams['motivo'] = $motivo;
    $filtrosFormatadosArray[] = 'Motivo: ' . htmlspecialchars($motivo);
}
if ($categoria !== '') {
    $queryParams['categoria'] = $categoria;
    $filtrosFormatadosArray[] = 'Categoria: ' . htmlspecialchars($categoria);
}
if ($comCa !== '') {
    $queryParams['item_com_ca'] = $comCa;
    $filtrosFormatadosArray[] = $comCa === '1' ? 'Com C.A.' : 'Sem C.A.';
}

$filtrosFormatados = implode(' | ', $filtrosFormatadosArray);

$registros = [];
$indicadores = [
    'total_entregas' => 0,
    'total_unidades' => 0,
    'funcionarios_atendidos' => 0,
    'epis_diferentes' => 0,
    'total_devolucoes' => 0,
    'total_substituicoes' => 0,
    'custo_total' => 0.00
];

$erro = null;

try {
    $queryString = http_build_query($queryParams);
    $response = $api->get('relatorios/epis/geral?' . $queryString);
    
    if (isset($response['success']) && $response['success'] && isset($response['data'])) {
        $dados = $response['data'];
        $registros = $dados['registros'] ?? $dados['itens'] ?? (is_array($dados) && isset($dados[0]) ? $dados : []);
        
        $indRetornados = $dados['indicadores'] ?? ($dados['kpis'] ?? null);
        if (is_array($indRetornados)) {
            $indicadores = array_merge($indicadores, $indRetornados);
        } else {
            // Calcular indicadores a partir dos registros se não vierem agrupados
            $funcSet = [];
            $epiSet = [];
            $totEntregas = count($registros);
            $totUnidades = 0;
            $totDevolucoes = 0;
            $totSubstituicoes = 0;
            $totCusto = 0.0;

            foreach ($registros as $r) {
                $qtd = (int)($r['quantidade'] ?? $r['item_quantidade'] ?? $r['ite_quantidade'] ?? 1);
                $totUnidades += $qtd;
                if (!empty($r['funcionario'] ?? $r['fun_nome'])) {
                    $funcSet[$r['funcionario'] ?? $r['fun_nome']] = true;
                }
                if (!empty($r['epi'] ?? $r['epi_nome'])) {
                    $epiSet[$r['epi'] ?? $r['epi_nome']] = true;
                }
                $mot = strtoupper((string)($r['motivo'] ?? $r['item_motivo_entrega'] ?? $r['entr_motivo'] ?? ''));
                if (str_contains($mot, 'SUBSTITU')) {
                    $totSubstituicoes += $qtd;
                }
                if (str_contains($mot, 'DEVOLU')) {
                    $totDevolucoes += $qtd;
                }
                $val = (float)($r['valor_total'] ?? $r['ite_custo_total'] ?? 0);
                $totCusto += $val;
            }

            $indicadores['total_entregas'] = $totEntregas;
            $indicadores['total_unidades'] = $totUnidades;
            $indicadores['funcionarios_atendidos'] = count($funcSet);
            $indicadores['epis_diferentes'] = count($epiSet);
            $indicadores['total_devolucoes'] = $totDevolucoes;
            $indicadores['total_substituicoes'] = $totSubstituicoes;
            $indicadores['custo_total'] = $totCusto;
        }
    } else {
        $erro = $response['message'] ?? 'Nenhum registro encontrado para os filtros selecionados.';
    }
} catch (\Throwable $e) {
    $erro = 'Erro de comunicação ao carregar relatório: ' . $e->getMessage();
}

// Log de auditoria
try {
    $api->post('logs/registrar-exportacao', [
        'quantidade' => count($registros),
        'filtros' => 'Relatório Geral de Fornecimento — ' . $filtrosFormatados . ' — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório Geral de Fornecimento de EPIs</title>
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
            letter-spacing: -0.2px;
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
            color: #0F172A;
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
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-nowrap { white-space: nowrap; }
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
        <i class="bi bi-file-earmark-bar-graph text-primary"></i>
        <span>Gestão EPI — Relatório Geral de Fornecimentos</span>
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
        <h1>Gestão EPI — Relatório Geral de Fornecimento de EPIs</h1>
        <p>Período: <?= (new DateTime($dataInicio))->format('d/m/Y') ?> a <?= (new DateTime($dataFim))->format('d/m/Y') ?> | Emitido em: <?= $dataEmissao ?> por: <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</p>
        <p>Filtros aplicados: <?= htmlspecialchars($filtrosFormatados) ?> | Total de Registros: <?= count($registros) ?></p>
    </div>

    <div class="kpi-container">
        <div class="kpi-card">
            <h3>Entregas</h3>
            <p><?= $indicadores['total_entregas'] ?? count($registros) ?></p>
        </div>
        <div class="kpi-card">
            <h3>Unidades</h3>
            <p><?= $indicadores['total_unidades'] ?? 0 ?> un</p>
        </div>
        <div class="kpi-card">
            <h3>Colaboradores</h3>
            <p><?= $indicadores['funcionarios_atendidos'] ?? 0 ?></p>
        </div>
        <div class="kpi-card">
            <h3>EPIs Distintos</h3>
            <p><?= $indicadores['epis_diferentes'] ?? 0 ?></p>
        </div>
        <div class="kpi-card kpi-green">
            <h3>Devoluções</h3>
            <p><?= $indicadores['total_devolucoes'] ?? 0 ?> un</p>
        </div>
        <div class="kpi-card kpi-orange">
            <h3>Substituições</h3>
            <p><?= $indicadores['total_substituicoes'] ?? 0 ?> un</p>
        </div>
        <?php if ($permiteVisualizarCustos && isset($indicadores['custo_total'])): ?>
        <div class="kpi-card kpi-red">
            <h3>Custo Total</h3>
            <p>R$ <?= number_format((float)$indicadores['custo_total'], 2, ',', '.') ?></p>
        </div>
        <?php endif; ?>
    </div>

    <h2>Registros Detalhados</h2>
    <?php if (empty($registros)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhum registro encontrado para os filtros selecionados.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 11%;">Data</th>
                <th style="width: 17%;">Funcionário</th>
                <th style="width: 13%;">Setor</th>
                <th style="width: 19%;">EPI</th>
                <th style="width: 7%;" class="text-center">C.A.</th>
                <th style="width: 6%;" class="text-center">Tam</th>
                <th style="width: 5%;" class="text-center">Qtd</th>
                <th style="width: 10%;">Motivo</th>
                <th style="width: <?= $permiteVisualizarCustos ? '8%' : '12%' ?>;">Responsável</th>
                <?php if ($permiteVisualizarCustos): ?>
                <th style="width: 10%;" class="text-right text-nowrap">Valor Total</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($registros as $reg): 
                $dataItem = $reg['entr_data_entrega'] ?? $reg['data'] ?? $reg['ent_data_retirada'] ?? '';
                $dataFmt = !empty($dataItem) ? (new DateTime($dataItem))->format('d/m/Y H:i') : '---';
                $funcNome = $reg['fun_nome'] ?? $reg['funcionario'] ?? '---';
                $setorNome = $reg['fun_departamento'] ?? $reg['setor'] ?? '---';
                $epiNome = $reg['epi_nome'] ?? $reg['epi'] ?? '---';
                $caNumero = !empty($reg['epi_ca']) ? $reg['epi_ca'] : (!empty($reg['ca']) ? $reg['ca'] : 'Isento');
                $tamItem = !empty($reg['item_tamanho']) ? $reg['item_tamanho'] : (!empty($reg['tamanho']) ? $reg['tamanho'] : (!empty($reg['ite_tamanho']) ? $reg['ite_tamanho'] : '---'));
                $qtdItem = (int)($reg['item_quantidade'] ?? $reg['quantidade'] ?? $reg['ite_quantidade'] ?? 1);
                $motivoItem = $reg['item_motivo_entrega'] ?? $reg['entr_motivo'] ?? $reg['motivo'] ?? 'FORNECIMENTO';
                $respItem = $reg['usu_login'] ?? $reg['responsavel'] ?? $reg['usuario_responsavel'] ?? '---';
                $valTotal = (float)($reg['valor_total'] ?? $reg['ite_custo_total'] ?? 0);
            ?>
            <tr>
                <td><?= htmlspecialchars($dataFmt) ?></td>
                <td><b><?= htmlspecialchars($funcNome) ?></b></td>
                <td><?= htmlspecialchars($setorNome) ?></td>
                <td><?= htmlspecialchars($epiNome) ?></td>
                <td class="text-center"><?= htmlspecialchars((string)$caNumero) ?></td>
                <td class="text-center"><?= htmlspecialchars($tamItem) ?></td>
                <td class="text-center"><b><?= $qtdItem ?></b></td>
                <td><?= htmlspecialchars($motivoItem) ?></td>
                <td><?= htmlspecialchars($respItem) ?></td>
                <?php if ($permiteVisualizarCustos): ?>
                <td class="text-right text-nowrap">R$&nbsp;<?= number_format($valTotal, 2, ',', '.') ?></td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer">
        <span>Gerado eletronicamente pelo aplicativo Gestão EPI — Documento de Auditoria e Controle</span>
        <span>Emissão: <?= $dataEmissao ?></span>
    </div>
</div>

</body>
</html>
