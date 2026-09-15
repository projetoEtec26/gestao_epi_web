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

// Controle de Acesso Restrito - Apenas Gestão e Admins podem ver dados financeiros
$allowedProfiles = ['ADMINISTRADOR', 'GESTOR'];
if (!in_array($userProfile, $allowedProfiles, true)) {
    header('Location: 403.php');
    exit;
}

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

$tzBrasil = new DateTimeZone('America/Sao_Paulo');
$dataAtual = new DateTime('now', $tzBrasil);
$dataEmissao = $dataAtual->format('d/m/Y H:i');

$dataInicioRaw = trim($_GET['data_inicial'] ?? $_GET['data_inicio'] ?? $dataAtual->format('Y-m-01'));
$dataFimRaw = trim($_GET['data_final'] ?? $_GET['data_fim'] ?? $dataAtual->format('Y-m-d'));

$dataInicio = explode(' ', $dataInicioRaw)[0];
$dataFim = explode(' ', $dataFimRaw)[0];

$setor = trim($_GET['setor'] ?? $_GET['departamento'] ?? '');
$funcionario = trim($_GET['funcionario'] ?? '');

$queryParams = [
    'data_inicial' => $dataInicio . ' 00:00:00',
    'data_final' => $dataFim . ' 23:59:59'
];
if ($setor !== '') $queryParams['departamento'] = $setor;
if ($funcionario !== '') $queryParams['funcionario'] = $funcionario;

$custoBruto = 0.0;
$estornos = 0.0;
$descartes = 0.0;
$custoLiquido = 0.0;

$movimentacoes = [];
$custoPorColaborador = [];
$custoPorSetor = [];
$erro = null;

try {
    $queryString = http_build_query($queryParams);
    $response = $api->get('relatorios/epis/geral?' . $queryString);
    
    if (isset($response['success']) && $response['success'] && isset($response['data'])) {
        $dados = $response['data'];
        $itens = $dados['registros'] ?? $dados['itens'] ?? (is_array($dados) && isset($dados[0]) ? $dados : []);

        $colabTotais = [];
        $setorTotais = [];

        foreach ($itens as $item) {
            $val = (float)($item['valor_total'] ?? $item['ite_custo_total'] ?? 0);
            $status = strtoupper((string)($item['status'] ?? $item['ite_status_item'] ?? $item['ent_status'] ?? 'EM USO'));
            $motivo = strtoupper((string)($item['motivo'] ?? $item['ent_motivo'] ?? $item['item_motivo_entrega'] ?? ''));
            $colabNome = $item['funcionario'] ?? $item['fun_nome'] ?? 'Não identificado';
            $setorNome = $item['setor'] ?? $item['fun_departamento'] ?? 'Geral';
            $qtd = (int)($item['quantidade'] ?? $item['ite_quantidade'] ?? $item['item_quantidade'] ?? 1);
            $epiNome = $item['epi_nome'] ?? $item['epi'] ?? 'EPI';
            $caNum = !empty($item['epi_ca'] ?? $item['ca'] ?? null) ? ($item['epi_ca'] ?? $item['ca']) : 'Isento';

            $dataRaw = $item['entr_data_entrega'] ?? $item['data'] ?? $item['ent_data_retirada'] ?? $item['data_entrega'] ?? '';
            $dataFmt = '---';
            if (!empty($dataRaw)) {
                try {
                    $dt = new DateTime((string)$dataRaw);
                    $dataFmt = $dt->format('d/m/Y');
                } catch (\Throwable $e) {}
            }

            $custoBruto += $val;

            if (str_contains($status, 'DEVOLVIDO') || str_contains($motivo, 'DEVOLUCAO')) {
                $estornos += $val;
            } elseif (str_contains($status, 'DESCARTE') || str_contains($status, 'DANIFICADO') || str_contains($motivo, 'DANIFICADO')) {
                $descartes += $val;
            }

            // Agrupamento por colaborador
            if (!isset($colabTotais[$colabNome])) {
                $colabTotais[$colabNome] = ['nome' => $colabNome, 'qtd' => 0, 'total' => 0.0];
            }
            $colabTotais[$colabNome]['qtd'] += $qtd;
            $colabTotais[$colabNome]['total'] += $val;

            // Agrupamento por setor
            if (!isset($setorTotais[$setorNome])) {
                $setorTotais[$setorNome] = ['setor' => $setorNome, 'qtd' => 0, 'total' => 0.0];
            }
            $setorTotais[$setorNome]['qtd'] += $qtd;
            $setorTotais[$setorNome]['total'] += $val;

            $movimentacoes[] = [
                'data' => $dataFmt,
                'colaborador' => $colabNome,
                'epi' => $epiNome,
                'ca' => $caNum,
                'quantidade' => $qtd,
                'motivo' => !empty($motivo) ? $motivo : 'FORNECIMENTO',
                'status' => $status,
                'valor_total' => $val
            ];
        }

        $custoLiquido = $custoBruto - $estornos;

        // Ordena por maior custo
        usort($colabTotais, fn($a, $b) => $b['total'] <=> $a['total']);
        usort($setorTotais, fn($a, $b) => $b['total'] <=> $a['total']);

        $custoPorColaborador = array_slice($colabTotais, 0, 8);
        $custoPorSetor = array_slice($setorTotais, 0, 8);
    } else {
        $erro = $response['message'] ?? 'Nenhum registro financeiro encontrado no período.';
    }
} catch (\Throwable $e) {
    $erro = 'Erro ao processar dados financeiros: ' . $e->getMessage();
}

// Log de Auditoria
try {
    $api->post('logs/registrar-exportacao', [
        'quantidade' => count($movimentacoes),
        'filtros' => 'Relatório Financeiro de EPIs — Período: ' . (new DateTime($dataInicio))->format('d/m/Y') . ' a ' . (new DateTime($dataFim))->format('d/m/Y') . ' — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório Financeiro de EPIs</title>
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
            margin-bottom: 12px;
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
            gap: 10px;
            margin-bottom: 14px;
        }
        .kpi-card {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 8px 12px;
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
            margin: 3px 0 0 0;
            font-size: 14px;
            font-weight: 700;
            color: #305BD3;
        }
        .kpi-card.kpi-green p { color: #10B981; }
        .kpi-card.kpi-red p { color: #EF4444; }

        h2 {
            font-size: 11.5px;
            margin: 12px 0 6px 0;
            color: #305BD3;
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
            font-size: 8.5px;
        }
        th {
            background-color: #F1F5F9;
            color: #0F172A;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-nowrap { white-space: nowrap; }
        
        .managerial-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 10px;
        }
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

<div class="action-bar no-print flex-wrap gap-2">
    <div class="brand">
        <i class="bi bi-cash-stack text-primary"></i>
        <span>Gestão EPI — Relatório Financeiro e Custos</span>
    </div>
    <form method="GET" action="relatorio_financeiro.php" class="d-flex align-items-center gap-2 m-0 flex-wrap">
        <span class="text-white fw-medium" style="font-size:11px;">Início:</span>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>" class="form-control form-control-sm py-1 px-2" style="width: auto; font-size: 11px;">
        
        <span class="text-white fw-medium" style="font-size:11px;">Fim:</span>
        <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>" class="form-control form-control-sm py-1 px-2" style="width: auto; font-size: 11px;">
        
        <input type="text" name="setor" value="<?= htmlspecialchars($setor) ?>" placeholder="Setor" class="form-control form-control-sm py-1 px-2" style="width: 110px; font-size: 11px;">
        <input type="text" name="funcionario" value="<?= htmlspecialchars($funcionario) ?>" placeholder="Funcionário" class="form-control form-control-sm py-1 px-2" style="width: 120px; font-size: 11px;">
        
        <button type="submit" class="btn btn-sm btn-primary py-1 px-3" style="font-size: 11px;"><i class="bi bi-search me-1"></i> Filtrar</button>
    </form>
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
    <?php if ($erro && empty($movimentacoes)): ?>
        <div class="alert-error">
            <i class="bi bi-info-circle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <h1>Gestão EPI — Relatório Financeiro de EPIs</h1>
        <p>Período: <?= (new DateTime($dataInicio))->format('d/m/Y') ?> a <?= (new DateTime($dataFim))->format('d/m/Y') ?> | Emitido em: <?= $dataEmissao ?> por: <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</p>
    </div>

    <div class="kpi-container">
        <div class="kpi-card">
            <h3>Custo Bruto Fornecido</h3>
            <p>R$ <?= number_format($custoBruto, 2, ',', '.') ?></p>
        </div>
        <div class="kpi-card kpi-green">
            <h3>Estornos / Devoluções</h3>
            <p>R$ <?= number_format($estornos, 2, ',', '.') ?></p>
        </div>
        <div class="kpi-card kpi-red">
            <h3>Descartes / Inservíveis</h3>
            <p>R$ <?= number_format($descartes, 2, ',', '.') ?></p>
        </div>
        <div class="kpi-card">
            <h3>Custo Líquido Efetivo</h3>
            <p>R$ <?= number_format($custoLiquido, 2, ',', '.') ?></p>
        </div>
    </div>

    <h2>Histórico Financeiro Detalhado</h2>
    <?php if (empty($movimentacoes)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhuma movimentação com valor encontrada no período selecionado.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">Data</th>
                <th style="width: 22%;">Colaborador</th>
                <th style="width: 24%;">EPI</th>
                <th style="width: 10%;" class="text-center">C.A. / Lote</th>
                <th style="width: 6%;" class="text-center">Qtd</th>
                <th style="width: 12%;">Motivo</th>
                <th style="width: 8%;">Status</th>
                <th style="width: 10%;" class="text-right text-nowrap">Valor Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimentacoes as $mov): ?>
            <tr>
                <td><?= htmlspecialchars($mov['data']) ?></td>
                <td><b><?= htmlspecialchars($mov['colaborador']) ?></b></td>
                <td><?= htmlspecialchars($mov['epi']) ?></td>
                <td class="text-center"><?= htmlspecialchars((string)$mov['ca']) ?></td>
                <td class="text-center"><b><?= $mov['quantidade'] ?></b></td>
                <td><?= htmlspecialchars($mov['motivo']) ?></td>
                <td><?= htmlspecialchars($mov['status']) ?></td>
                <td class="text-right text-nowrap"><b>R$&nbsp;<?= number_format($mov['valor_total'], 2, ',', '.') ?></b></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="managerial-grid">
        <div>
            <h2>Análise Gerencial: Custo por Colaborador</h2>
            <table>
                <thead>
                    <tr>
                        <th>Colaborador</th>
                        <th class="text-center" style="width: 20%;">Qtd Itens</th>
                        <th class="text-right text-nowrap" style="width: 30%;">Custo Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($custoPorColaborador)): ?>
                        <tr><td colspan="3" class="text-center" style="color: #94A3B8;">Sem dados</td></tr>
                    <?php else: ?>
                        <?php foreach ($custoPorColaborador as $c): 
                            $pctC = $custoBruto > 0 ? min(100, round(($c['total'] / $custoBruto) * 100, 1)) : 0;
                        ?>
                        <tr>
                            <td>
                                <b><?= htmlspecialchars($c['nome']) ?></b>
                                <div style="background: #E2E8F0; height: 3px; border-radius: 2px; margin-top: 2px; overflow: hidden;">
                                    <div style="background: #305BD3; height: 100%; width: <?= $pctC ?>%;"></div>
                                </div>
                            </td>
                            <td class="text-center"><?= $c['qtd'] ?> un</td>
                            <td class="text-right text-nowrap">R$&nbsp;<?= number_format($c['total'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div>
            <h2>Análise Gerencial: Custo por Setor / Departamento</h2>
            <table>
                <thead>
                    <tr>
                        <th>Setor</th>
                        <th class="text-center" style="width: 20%;">Qtd Itens</th>
                        <th class="text-right text-nowrap" style="width: 30%;">Custo Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($custoPorSetor)): ?>
                        <tr><td colspan="3" class="text-center" style="color: #94A3B8;">Sem dados</td></tr>
                    <?php else: ?>
                        <?php foreach ($custoPorSetor as $s): 
                            $pctS = $custoBruto > 0 ? min(100, round(($s['total'] / $custoBruto) * 100, 1)) : 0;
                        ?>
                        <tr>
                            <td>
                                <b><?= htmlspecialchars($s['setor']) ?></b>
                                <div style="background: #E2E8F0; height: 3px; border-radius: 2px; margin-top: 2px; overflow: hidden;">
                                    <div style="background: #10B981; height: 100%; width: <?= $pctS ?>%;"></div>
                                </div>
                            </td>
                            <td class="text-center"><?= $s['qtd'] ?> un</td>
                            <td class="text-right text-nowrap">R$&nbsp;<?= number_format($s['total'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <span>Gerado eletronicamente pelo aplicativo Gestão EPI</span>
        <span>Emissão: <?= $dataEmissao ?></span>
    </div>
</div>

</body>
</html>
