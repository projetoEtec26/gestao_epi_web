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

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

$tzBrasil = new DateTimeZone('America/Sao_Paulo');
$hoje = new DateTime('now', $tzBrasil);
$dataEmissao = $hoje->format('d/m/Y H:i');

$totalVencidos = 0;
$totalVencendo = 0;
$totalValidos = 0;
$episValidade = [];
$erro = null;

try {
    // Busca os dados da rota de CA ou lista de epis
    $response = $api->get('relatorios/ca-vencidos');
    
    // Se a rota específica não retornar itens, busca da listagem de epis
    if (!isset($response['success']) || !$response['success'] || empty($response['data'])) {
        $response = $api->get('epis');
    }

    if (isset($response['success']) && $response['success'] && is_array($response['data'])) {
        $lista = $response['data'];

        foreach ($lista as $epi) {
            $nome = $epi['epi_nome'] ?? $epi['nome'] ?? 'EPI sem nome';
            $ca = $epi['epi_ca'] ?? $epi['ca'] ?? '';
            $fabricante = $epi['epi_fabricante'] ?? $epi['fabricante'] ?? 'Fabricante Nacional';
            $dataVencStr = $epi['epi_validade_ca'] ?? $epi['vencimento_ca'] ?? $epi['validade_ca'] ?? '';

            if (empty($ca)) {
                // Item sem exigência de CA (uniforme ou proteção coletiva)
                continue;
            }

            $diasRestantes = null;
            $vencimentoFormatado = 'Não informada';
            $status = 'VÁLIDO';
            $acao = 'Em conformidade legal.';

            if (!empty($dataVencStr)) {
                try {
                    $dtVenc = new DateTime($dataVencStr, $tzBrasil);
                    $vencimentoFormatado = $dtVenc->format('d/m/Y');
                    
                    // Diferença de dias
                    $diff = (int)$hoje->diff($dtVenc)->format('%r%a');
                    $diasRestantes = $diff;

                    if ($diff < 0) {
                        $status = 'VENCIDO';
                        $acao = 'Bloqueio imediato de fornecimento e descarte/recolhimento do estoque.';
                        $totalVencidos++;
                    } elseif ($diff <= 30) {
                        $status = 'VENCENDO';
                        $acao = 'Iniciar cotação de novo lote e verificar prorrogação de laudo MTE.';
                        $totalVencendo++;
                    } else {
                        $status = 'VÁLIDO';
                        $acao = 'Em conformidade com NR-06.';
                        $totalValidos++;
                    }
                } catch (\Throwable $e) {
                    $vencimentoFormatado = $dataVencStr;
                }
            } else {
                $status = 'SEM_DATA';
                $acao = 'Atualizar cadastro do C.A. com a data de validade oficial do MTE.';
            }

            $episValidade[] = [
                'nome' => $nome,
                'ca' => $ca,
                'fabricante' => $fabricante,
                'vencimento_ca' => $vencimentoFormatado,
                'dias_restantes' => $diasRestantes,
                'status' => $status,
                'acao' => $acao
            ];
        }

        // Ordena para que os vencidos e vencendo fiquem no topo
        usort($episValidade, function($a, $b) {
            $prioridade = ['VENCIDO' => 1, 'VENCENDO' => 2, 'SEM_DATA' => 3, 'VÁLIDO' => 4];
            $pa = $prioridade[$a['status']] ?? 5;
            $pb = $prioridade[$b['status']] ?? 5;
            if ($pa !== $pb) return $pa <=> $pb;
            return ($a['dias_restantes'] ?? 9999) <=> ($b['dias_restantes'] ?? 9999);
        });

    } else {
        $erro = $response['message'] ?? 'Nenhum equipamento cadastrado para análise de C.A.';
    }
} catch (\Throwable $e) {
    $erro = 'Erro na comunicação ao analisar C.A.: ' . $e->getMessage();
}

// Log de Auditoria
try {
    $api->post('logs/registrar-exportacao', [
        'quantidade' => count($episValidade),
        'filtros' => 'Relatório de Validade de Certificados de Aprovação (C.A.) — Formato: PDF/Impressão'
    ]);
} catch (\Throwable $e) {}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão EPI — Relatório de Validade e Vencimento de C.A.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            color: #1E293B;
            line-height: 1.3;
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
            max-width: 900px;
            margin: 20px auto;
            padding: 20px 28px;
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
            font-size: 9.5px;
            opacity: 0.92;
        }
        .kpi-container {
            display: flex;
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
            font-size: 8px;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .kpi-card p {
            margin: 3px 0 0 0;
            font-size: 14px;
            font-weight: 700;
        }
        .kpi-card.kpi-red p { color: #EF4444; }
        .kpi-card.kpi-orange p { color: #F59E0B; }
        .kpi-card.kpi-green p { color: #10B981; }

        h2 {
            font-size: 11.5px;
            margin: 12px 0 6px 0;
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
            padding: 6px 8px;
            text-align: left;
            vertical-align: middle;
            font-size: 9px;
        }
        th {
            background-color: #F1F5F9;
            color: #0F172A;
            font-weight: 700;
            font-size: 8.5px;
            text-transform: uppercase;
        }
        .text-center { text-align: center; }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: 700;
        }
        .badge-vencido { background-color: #FEE2E2; color: #991B1B; }
        .badge-vencendo { background-color: #FEF3C7; color: #92400E; }
        .badge-valido { background-color: #DCFCE7; color: #166534; }
        .badge-semdata { background-color: #F1F5F9; color: #475569; }
        .footer {
            margin-top: 18px;
            border-top: 1px solid #E2E8F0;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 8px;
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
        <i class="bi bi-shield-check text-primary"></i>
        <span>Gestão EPI — Relatório de Validade e Vencimento de C.A.</span>
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
    <?php if ($erro && empty($episValidade)): ?>
        <div class="alert-error">
            <i class="bi bi-info-circle-fill me-1"></i> <?= htmlspecialchars($erro) ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <h1>Gestão EPI — Relatório de Validade e Vencimento de C.A.</h1>
        <p>Monitoramento de Conformidade do Certificado de Aprovação (MTE) | Emitido em: <?= $dataEmissao ?> por: <?= htmlspecialchars($userLogin) ?> (<?= htmlspecialchars($userProfile) ?>)</p>
    </div>

    <div class="kpi-container">
        <div class="kpi-card kpi-red">
            <h3>C.A. Vencidos (Crítico)</h3>
            <p><?= $totalVencidos ?> itens</p>
        </div>
        <div class="kpi-card kpi-orange">
            <h3>Vencendo em até 30 dias</h3>
            <p><?= $totalVencendo ?> itens</p>
        </div>
        <div class="kpi-card kpi-green">
            <h3>Em Conformidade (Válidos)</h3>
            <p><?= $totalValidos ?> itens</p>
        </div>
    </div>

    <h2>Equipamentos e Status do Certificado de Aprovação</h2>
    <?php if (empty($episValidade)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhum equipamento com Certificado de Aprovação encontrado.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 25%;">Equipamento (EPI)</th>
                <th style="width: 10%;" class="text-center">C.A.</th>
                <th style="width: 17%;">Fabricante</th>
                <th style="width: 13%;" class="text-center">Vencimento C.A.</th>
                <th style="width: 13%;" class="text-center">Situação</th>
                <th style="width: 22%;">Ação Preventiva / Recomendação</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($episValidade as $item): ?>
            <tr>
                <td><b><?= htmlspecialchars($item['nome']) ?></b></td>
                <td class="text-center"><b><?= htmlspecialchars((string)$item['ca']) ?></b></td>
                <td><?= htmlspecialchars($item['fabricante']) ?></td>
                <td class="text-center"><?= htmlspecialchars($item['vencimento_ca']) ?></td>
                <td class="text-center">
                    <?php if ($item['status'] === 'VENCIDO'): ?>
                        <span class="badge badge-vencido">VENCIDO (<?= abs((int)$item['dias_restantes']) ?>d atrás)</span>
                    <?php elseif ($item['status'] === 'VENCENDO'): ?>
                        <span class="badge badge-vencendo">VENCE EM <?= $item['dias_restantes'] ?> DIAS</span>
                    <?php elseif ($item['status'] === 'SEM_DATA'): ?>
                        <span class="badge badge-semdata">SEM DATA</span>
                    <?php else: ?>
                        <span class="badge badge-valido">REGULAR</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($item['acao']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="footer">
        <span>Gerado eletronicamente pelo aplicativo Gestão EPI — Setor de Segurança do Trabalho (SST)</span>
        <span>Emissão: <?= $dataEmissao ?></span>
    </div>
</div>

</body>
</html>
