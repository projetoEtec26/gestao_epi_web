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
$allowedProfiles = ['ADMINISTRADOR', 'GESTOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'RH_ADMINISTRATIVO'];
if (!in_array($userProfile, $allowedProfiles, true)) {
    header('Location: 403.php');
    exit;
}

require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

$funcionarioId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');

$colaborador = null;
$movimentacoes = [];
$erro = null;

$tzBrasil = new DateTimeZone('America/Sao_Paulo');
$dataEmissao = (new DateTime('now', $tzBrasil))->format('d/m/Y H:i:s');
$geradoPor = htmlspecialchars($userLogin . ' (' . $userProfile . ')');

if ($funcionarioId > 0) {
    try {
        // Busca dados cadastrais do funcionário
        $resFunc = $api->get('funcionarios/' . $funcionarioId);
        if (isset($resFunc['success']) && $resFunc['success'] && !empty($resFunc['data'])) {
            $colaborador = $resFunc['data'];
        } else {
            $erro = $resFunc['message'] ?? 'Colaborador não encontrado.';
        }

        // Busca histórico de entregas/posses do colaborador
        if ($colaborador) {
            $resEntregas = $api->get('entregas/funcionario/' . $funcionarioId);
            if (isset($resEntregas['success']) && $resEntregas['success'] && is_array($resEntregas['data'])) {
                foreach ($resEntregas['data'] as $ent) {
                    $dtEntregaFormatada = !empty($ent['ent_data_retirada']) 
                        ? (new DateTime($ent['ent_data_retirada']))->format('d/m/Y') 
                        : '---';
                    $dtDevFormatada = !empty($ent['ite_data_devolucao']) 
                        ? (new DateTime($ent['ite_data_devolucao']))->format('d/m/Y') 
                        : '---';

                    $movimentacoes[] = [
                        'epi' => $ent['epi_nome'] ?? 'EPI sem identificação',
                        'ca' => !empty($ent['epi_ca']) ? 'C.A. ' . $ent['epi_ca'] : 'Sem C.A.',
                        'tamanho' => $ent['ite_tamanho'] ?? $ent['epi_tamanho_padrao'] ?? 'Único',
                        'quantidade' => (int)($ent['ite_quantidade'] ?? 1),
                        'data_entrega' => $dtEntregaFormatada,
                        'motivo' => $ent['ent_motivo'] ?? 'FORNECIMENTO',
                        'status' => strtoupper($ent['ite_status_item'] ?? $ent['ent_status'] ?? 'EM USO'),
                        'data_devolucao' => $dtDevFormatada,
                        'operador' => $ent['usuario_responsavel'] ?? 'almoxarifado'
                    ];
                }
            }
        }
    } catch (\Throwable $e) {
        $erro = 'Erro de comunicação ao obter dados: ' . $e->getMessage();
    }
} else {
    $erro = 'Nenhum colaborador foi selecionado para emissão da ficha.';
}

// Registro em auditoria
if ($colaborador) {
    try {
        $api->post('logs/registrar-exportacao', [
            'quantidade' => count($movimentacoes),
            'filtros' => 'Ficha Individual de EPI — Colaborador: ' . ($colaborador['fun_nome'] ?? '') . ' (ID ' . $funcionarioId . ') — Formato: PDF/Impressão'
        ]);
    } catch (\Throwable $e) {}
}

$periodoIni = !empty($dataInicio) ? (new DateTime($dataInicio))->format('d/m/Y') : 'Início das atividades';
$periodoFim = !empty($dataFim) ? (new DateTime($dataFim))->format('d/m/Y') : (new DateTime('now', $tzBrasil))->format('d/m/Y');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha Individual de Fornecimento de EPI — <?= $colaborador ? htmlspecialchars($colaborador['fun_nome']) : 'Documento' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        @page {
            size: A4 portrait;
            margin: 12mm 15mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            color: #1E293B;
            line-height: 1.35;
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
            padding: 24px 32px;
            background: #FFFFFF;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border-radius: 8px;
        }
        .header {
            border-bottom: 2.5px solid #1E3A8A;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header h1 {
            color: #1E3A8A;
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 700;
        }
        .header .subtitle {
            font-size: 9.5px;
            color: #64748B;
            margin: 0;
        }
        .employee-card {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 14px;
        }
        .employee-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr;
            gap: 8px 14px;
        }
        .employee-item label {
            display: block;
            font-size: 8px;
            font-weight: 700;
            color: #64748B;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .employee-item span {
            font-size: 10.5px;
            font-weight: 600;
            color: #0F172A;
        }
        h2 {
            font-size: 11px;
            margin: 14px 0 6px 0;
            color: #1E3A8A;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            padding: 5px 8px;
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
            font-weight: 600;
        }
        .badge-em-uso { background-color: #DCFCE7; color: #166534; }
        .badge-devolvido { background-color: #F1F5F9; color: #475569; }
        .badge-substituido { background-color: #FEF3C7; color: #92400E; }
        .term {
            background-color: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 12px 14px;
            margin-top: 16px;
            font-size: 8.5px;
            color: #334155;
            page-break-inside: avoid;
        }
        .term h3 {
            margin: 0 0 6px 0;
            font-size: 9.5px;
            color: #1E3A8A;
            font-weight: 700;
        }
        .term p {
            margin: 4px 0;
            text-align: justify;
        }
        .term .signature-box {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #CBD5E1;
            font-size: 8.5px;
            color: #0F172A;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer {
            margin-top: 16px;
            font-size: 8px;
            color: #94A3B8;
            text-align: right;
            border-top: 1px solid #E2E8F0;
            padding-top: 6px;
        }
        .alert-error {
            background-color: #FEF2F2;
            border: 1px solid #F87171;
            color: #991B1B;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 12px;
        }
        @media print {
            .no-print, .action-bar {
                display: none !important;
            }
            .document-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
                max-width: 100%;
            }
            body { margin: 0; }
            th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .employee-card, .term { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <div class="brand">
        <i class="bi bi-shield-check text-primary"></i>
        <span>Gestão EPI — Emissão de Ficha Individual de EPI (NR-06)</span>
    </div>
    <div class="buttons">
        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <i class="bi bi-printer-fill"></i> Imprimir / Salvar PDF
        </button>
        <button type="button" class="btn-action btn-close-view" onclick="window.close(); if(window.opener){window.opener.focus();}else{location.href='funcionarios.php';}">
            <i class="bi bi-arrow-left"></i> Voltar
        </button>
    </div>
</div>

<div class="document-container">
    <?php if ($erro && !$colaborador): ?>
        <div class="alert-error">
            <strong><i class="bi bi-exclamation-triangle-fill me-1"></i> Atenção:</strong> <?= htmlspecialchars($erro) ?>
        </div>
    <?php else: ?>

    <div class="header">
        <h1>Gestão EPI — Ficha Individual de Fornecimento de EPI</h1>
        <p class="subtitle">Em conformidade com a Norma Regulamentadora NR-06 (Portaria GM n.º 3.214/1978 e atualizações) e Art. 158 da Consolidação das Leis do Trabalho (CLT)</p>
    </div>

    <div class="employee-card">
        <div class="employee-grid">
            <div class="employee-item">
                <label>Colaborador</label>
                <span><?= htmlspecialchars($colaborador['fun_nome'] ?? 'Não informado') ?></span>
            </div>
            <div class="employee-item">
                <label>Matrícula</label>
                <span><?= htmlspecialchars($colaborador['fun_matricula'] ?? 'FUNC-' . sprintf('%04d', $funcionarioId)) ?></span>
            </div>
            <div class="employee-item">
                <label>Data Admissão</label>
                <span><?= !empty($colaborador['fun_data_admissao']) ? (new DateTime($colaborador['fun_data_admissao']))->format('d/m/Y') : 'Não informada' ?></span>
            </div>
            <div class="employee-item">
                <label>Cargo / Função</label>
                <span><?= htmlspecialchars($colaborador['fun_cargo'] ?? 'Não informado') ?></span>
            </div>
            <div class="employee-item">
                <label>Departamento / Setor</label>
                <span><?= htmlspecialchars($colaborador['fun_departamento'] ?? 'Operacional') ?></span>
            </div>
            <div class="employee-item">
                <label>Período Consulta</label>
                <span><?= $periodoIni ?> a <?= $periodoFim ?></span>
            </div>
        </div>
    </div>

    <h2>Histórico de Fornecimentos e Movimentações</h2>
    <?php if (empty($movimentacoes)): ?>
        <p style="color: #64748B; font-style: italic; padding: 12px 0;">Nenhum fornecimento registrado para este colaborador até o momento.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th style="width: 22%;">EPI</th>
                <th style="width: 11%;">C.A. / Lote</th>
                <th style="width: 7%;" class="text-center">Tam</th>
                <th style="width: 6%;" class="text-center">Qtd</th>
                <th style="width: 12%;">Data Fornecimento</th>
                <th style="width: 14%;">Motivo</th>
                <th style="width: 10%;">Status</th>
                <th style="width: 10%;">Data Devolução</th>
                <th style="width: 8%;">Responsável</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimentacoes as $mov): ?>
            <tr>
                <td><b><?= htmlspecialchars($mov['epi']) ?></b></td>
                <td><?= htmlspecialchars($mov['ca']) ?></td>
                <td class="text-center"><?= htmlspecialchars($mov['tamanho']) ?></td>
                <td class="text-center"><b><?= htmlspecialchars((string)$mov['quantidade']) ?></b></td>
                <td><?= htmlspecialchars($mov['data_entrega']) ?></td>
                <td><?= htmlspecialchars($mov['motivo']) ?></td>
                <td>
                    <?php if ($mov['status'] === 'EM USO' || $mov['status'] === 'ENTREGUE'): ?>
                        <span class="badge badge-em-uso">EM USO</span>
                    <?php elseif ($mov['status'] === 'SUBSTITUIDO' || $mov['status'] === 'SUBSTITUÍDO'): ?>
                        <span class="badge badge-substituido">SUBSTITUÍDO</span>
                    <?php else: ?>
                        <span class="badge badge-devolvido"><?= htmlspecialchars($mov['status']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($mov['data_devolucao']) ?></td>
                <td><?= htmlspecialchars($mov['operador']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <div class="term">
        <h3>Termo de Responsabilidade e Compliance Legal (Art. 158 da CLT & NR-06)</h3>
        <p>Declaro ter recebido os Equipamentos de Proteção Individual (EPI) indicados nesta ficha, nas datas descritas, em perfeito estado de conservação e uso. Comprometo-me a utilizá-los estritamente no exercício das minhas atividades laborais, zelar pela sua guarda, higienização e conservação, e solicitar a substituição imediata em caso de dano, desgaste ou extravio.</p>
        <p>Estou ciente de que a recusa injustificada ao uso dos EPIs fornecidos constitui ato faltoso passível das sanções disciplinares previstas no Artigo 482 da CLT.</p>
        <div class="signature-box">
            <span><i class="bi bi-shield-lock-fill text-success me-1"></i> Assinatura Eletrônica validada via PIN/Senha pessoal intransferível no aplicativo Gestão_EPI</span>
            <span>Data: <?= $dataEmissao ?></span>
        </div>
    </div>

    <div class="footer">
        Documento emitido por <?= $geradoPor ?> em <?= $dataEmissao ?> — Sistema Gestão EPI
    </div>

    <?php endif; ?>
</div>

</body>
</html>
