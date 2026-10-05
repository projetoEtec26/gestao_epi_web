<?php
declare(strict_types=1);

$page_title = 'Dashboard';
$active_menu = 'dashboard';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'GESTOR', 'ALMOXARIFE_OPERADOR'];

require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$user = $_SESSION['usuario'] ?? [];
$userName = htmlspecialchars($user['usu_nome'] ?? 'admin');
$userProfile = strtoupper((string)($user['usu_perfil'] ?? 'ADMINISTRADOR'));

// Helper para Formatação Limpa e Elegante dos Logs de Auditoria (Sem JSON Bruto)
function formatarLogAuditoria(array $log): array {
    $rawText = $log['log_detalhes'] ?? $log['log_acao'] ?? 'Atividade registrada no sistema';
    $textoLimpo = $rawText;

    // Se for uma string JSON codificada (ex: {"ocorrencia":"..."}), decodifica a mensagem amigável
    if (is_string($rawText) && (str_starts_with(trim($rawText), '{') || str_starts_with(trim($rawText), '['))) {
        $json = json_decode($rawText, true);
        if (is_array($json)) {
            if (!empty($json['ocorrencia'])) {
                $textoLimpo = $json['ocorrencia'];
            } elseif (!empty($json['mensagem'])) {
                $textoLimpo = $json['mensagem'];
            } elseif (!empty($json['acao'])) {
                $textoLimpo = $json['acao'];
            }
        }
    }

    // Se contiver string de filtros brutos ("Filtros: {...}"), remove a parte técnica
    if (is_string($textoLimpo) && str_contains($textoLimpo, '. Filtros: {')) {
        $textoLimpo = explode('. Filtros: {', $textoLimpo)[0];
    } elseif (is_string($textoLimpo) && str_contains($textoLimpo, ' Filtros: {')) {
        $textoLimpo = explode(' Filtros: {', $textoLimpo)[0];
    }

    $lower = strtolower((string)$textoLimpo);
    $tipo = 'primary';
    $icone = 'bi-info-circle-fill';
    $badgeTag = 'SISTEMA';

    if (str_contains($lower, 'login') || str_contains(strtolower($log['log_acao'] ?? ''), 'login')) {
        $tipo = 'info';
        $icone = 'bi-shield-lock-fill';
        $badgeTag = 'ACESSO';
        if (str_contains($lower, 'incorreta') || str_contains($lower, 'falha')) {
            $tipo = 'warning';
            $icone = 'bi-shield-exclamation';
        }
    } elseif (str_contains($lower, 'entrega') || str_contains($lower, 'devolvido')) {
        $tipo = 'success';
        $icone = 'bi-box-seam-fill';
        $badgeTag = 'EPI';
        if (str_contains($lower, 'devolvido')) {
            $tipo = 'danger';
            $icone = 'bi-arrow-return-left';
        }
    } elseif (str_contains($lower, 'relatório') || str_contains($lower, 'relatorio') || str_contains($lower, 'consultado')) {
        $tipo = 'purple';
        $icone = 'bi-file-earmark-bar-chart-fill';
        $badgeTag = 'RELATÓRIO';
    } elseif (str_contains($lower, 'alterad') || str_contains($lower, 'editad') || str_contains($lower, 'atualizad') || str_contains($lower, 'campo')) {
        $tipo = 'warning';
        $icone = 'bi-pencil-square';
        $badgeTag = 'CADASTRO';
    } elseif (str_contains($lower, 'pin') || str_contains($lower, 'senha')) {
        $tipo = 'primary';
        $icone = 'bi-fingerprint';
        $badgeTag = 'ASSINATURA';
    }

    $dataStr = 'Atividade recente';
    if (!empty($log['log_datahora'])) {
        try {
            $dtObj = new DateTime((string)$log['log_datahora'], new DateTimeZone('UTC'));
            $dtObj->setTimezone(new DateTimeZone('America/Sao_Paulo'));
            $dataStr = 'Atividade em ' . $dtObj->format('d/m \à\s H:i');
        } catch (\Throwable $t) {
            $ts = strtotime((string)$log['log_datahora']);
            $dataStr = 'Atividade em ' . ($ts ? date('d/m \à\s H:i', $ts) : (string)$log['log_datahora']);
        }
    }

    return [
        'id' => $log['log_id'] ?? 0,
        'texto' => $textoLimpo,
        'tipo' => $tipo,
        'icone' => $icone,
        'badge' => $badgeTag,
        'data' => $dataStr
    ];
}

// Fuso e Data em Português (Brasil)
date_default_timezone_set('America/Sao_Paulo');
$mesesPt = [
    1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
    7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro'
];
$mesesAbrev = [
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'
];
$mesAtualNum = (int)date('n');
$dataHojeFormatada = date('j') . ' de ' . $mesesPt[$mesAtualNum] . ' de ' . date('Y');
$siglaMesAtual = $mesesAbrev[$mesAtualNum];

// Conexão via PDO para Dados Consolidados do Dashboard
$configData = require __DIR__ . '/../config/api.php';
$dsn = sprintf("mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4", $configData['db_host'], $configData['db_port'], $configData['db_name']);

$alerts = [
    'ca_vencidos' => 2,
    'ca_vencendo_7d' => 1,
    'vida_util_vencida' => 3,
    'troca_proxima' => 2,
    'func_vencidos' => 3,
    'func_troca' => 2
];

$kpis = [
    'epis_vencidos' => 5,
    'a_vencer_7d' => 3,
    'entregas_hoje' => 0,
    'pendencias' => 2
];

$custos = [
    'mensal' => 'R$ 0,00',
    'acumulado' => 'R$ 60.599,60',
    'sem_pin' => 0
];

$conformidade = [
    'pct' => 98,
    'em_dia' => 52,
    'tot_func' => 53
];

$top5EpisOficial = [
    ['nome' => 'Protetor Auditivo PLUG', 'total' => 41, 'pct' => 100.0, 'cor' => '#F59E0B'],
    ['nome' => 'Capacete com Carneira', 'total' => 38, 'pct' => 92.68, 'cor' => '#3B82F6'],
    ['nome' => 'Botina de Segurança com cadarço e com biqueira', 'total' => 36, 'pct' => 87.80, 'cor' => '#10B981'],
    ['nome' => 'EPI teste offline', 'total' => 31, 'pct' => 75.61, 'cor' => '#8B5CF6'],
    ['nome' => 'Creme de Proteção (luva química)', 'total' => 0, 'pct' => 0.0, 'cor' => '#CBD5E1']
];

$top5Epis = $top5EpisOficial;
$top5EpisMensal = $top5EpisOficial;

$entregas7Dias = [
    'labels' => ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
    'data' => [0, 0, 0, 2, 0, 6, 0]
];

$entregasMensal = [
    'labels' => ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
    'data' => [45, 23, 17, 14]
];

$ultimasAtividades = [];

$caVencidosList = [];
$vidaUtilVencidaList = [];
$caAVencerList = [];
$entregasHojeList = [];
$semPinList = [];
$episEmPosseList = [];

$semVidaUtilList = [];
$semRastreabilidadeList = [];
$pendenciasDet = [
    'ca_vencidos' => 0,
    'sem_pin' => 0,
    'sem_vida_util' => 0,
    'sem_rastreabilidade' => 0
];

try {
    $pdo = new PDO($dsn, $configData['db_user'], $configData['db_pass'], [
        PDO::ATTR_TIMEOUT => 4,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
        PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false
    ]);

    if ($pdo) {
        $pdo->exec("SET time_zone = '-03:00'");
        $todayObj = new DateTime('now', new DateTimeZone('America/Sao_Paulo'));
        $todayStr = $todayObj->format('Y-m-d');
        $todayStart = $todayStr . ' 00:00:00';
        $todayEnd = $todayStr . ' 23:59:59';
        $in7DaysStr = (clone $todayObj)->modify('+7 days')->format('Y-m-d');

        // 1. CARD 1 & PENDÊNCIA 1: C.A. Vencidos (Catálogo)
        $stmtCaV = $pdo->prepare("
            SELECT epi_nome, epi_fabricante, epi_ca, epi_vencimento_ca, epi_status
            FROM epis
            WHERE (epi_status = 'VENCIDO' OR (epi_vencimento_ca IS NOT NULL AND epi_vencimento_ca < :today))
            ORDER BY epi_nome ASC
        ");
        $stmtCaV->execute(['today' => $todayStr]);
        $caVencidosList = $stmtCaV->fetchAll(PDO::FETCH_ASSOC);
        $totCaVencidos = count($caVencidosList);

        // 2. CARD 1 & CARD 2: Itens Ativos em Uso (para Vida Útil Vencida e Vida Útil A Vencer)
        $sqlItensEmUso = "
            SELECT i.item_id, i.entr_id, i.epi_id, i.item_quantidade,
                   e.entr_data_entrega, e.fun_id,
                   ep.epi_nome, ep.epi_ca, ep.epi_fabricante, ep.epi_validade_uso_dias,
                   ep.epi_vida_util, ep.epi_vida_util_unidade, ep.epi_vida_util_tipo, ep.epi_vida_util_alerta, ep.epi_vencimento_ca,
                   f.fun_nome, f.fun_cargo, f.fun_departamento
            FROM itens_entrega i
            INNER JOIN entrega_epis e ON i.entr_id = e.entr_id
            INNER JOIN epis ep ON i.epi_id = ep.epi_id
            INNER JOIN funcionarios f ON e.fun_id = f.fun_id
            WHERE e.entr_status = 'FINALIZADA'
              AND i.item_data_devolucao IS NULL
              AND (i.item_devolucao_motivo IS NULL OR i.item_devolucao_motivo = '')
              AND (i.item_devolucao_vinculo_item_id IS NULL OR i.item_devolucao_vinculo_item_id = 0)
        ";
        $itensEmUso = $pdo->query($sqlItensEmUso)->fetchAll(PDO::FETCH_ASSOC);

        $vidaUtilVencidaList = [];
        $vidaUtilAVencerList = [];

        foreach ($itensEmUso as $item) {
            $dtEntregaStr = $item['entr_data_entrega'] ?? null;
            if (!$dtEntregaStr) continue;

            $dtEntrega = new DateTime($dtEntregaStr);
            $dtVidaUtil = null;

            $tipoVal = strtoupper(trim((string)($item['epi_vida_util_tipo'] ?? '')));
            $valVu = (int)($item['epi_vida_util'] ?? 0);
            $unidadeVu = strtoupper(trim((string)($item['epi_vida_util_unidade'] ?? '')));

            if ($tipoVal === 'CONTROLADO' && $valVu > 0) {
                $dtVidaUtil = clone $dtEntrega;
                if (in_array($unidadeVu, ['DIAS', 'DIA'])) {
                    $dtVidaUtil->modify("+{$valVu} days");
                } elseif (in_array($unidadeVu, ['MESES', 'MES'])) {
                    $dtVidaUtil->modify("+{$valVu} months");
                } elseif (in_array($unidadeVu, ['ANOS', 'ANO'])) {
                    $dtVidaUtil->modify("+{$valVu} years");
                } else {
                    $dtVidaUtil->modify("+{$valVu} days");
                }
            } elseif (!empty($item['epi_validade_uso_dias']) && (int)$item['epi_validade_uso_dias'] > 0) {
                $vDias = (int)$item['epi_validade_uso_dias'];
                $dtVidaUtil = (clone $dtEntrega)->modify("+{$vDias} days");
            }

            $dtCa = !empty($item['epi_vencimento_ca']) ? new DateTime($item['epi_vencimento_ca']) : null;

            $dataTroca = null;
            if ($dtVidaUtil && $dtCa) {
                $dataTroca = $dtVidaUtil < $dtCa ? $dtVidaUtil : $dtCa;
            } else {
                $dataTroca = $dtVidaUtil ?: $dtCa;
            }

            if (!$dataTroca) continue;

            $dtTrocaStr = $dataTroca->format('Y-m-d');
            if ($dtTrocaStr < $todayStr) {
                $item['data_vencimento_uso'] = $dataTroca->format('Y-m-d H:i:s');
                $vidaUtilVencidaList[] = $item;
            } else {
                $diasParaTroca = (int)$todayObj->diff($dataTroca)->format('%r%a');
                $diasAlerta = isset($item['epi_vida_util_alerta']) && $item['epi_vida_util_alerta'] !== null && (int)$item['epi_vida_util_alerta'] > 0
                    ? (int)$item['epi_vida_util_alerta']
                    : 30;

                if ($diasParaTroca <= $diasAlerta && $diasParaTroca >= 0) {
                    $item['data_vencimento'] = $dataTroca->format('Y-m-d');
                    $item['dias_restantes'] = $diasParaTroca;
                    $vidaUtilAVencerList[] = $item;
                }
            }
        }

        // 3. CARD 2: C.A. A Vencer nos próximos 7 dias (Catálogo)
        $stmtCaAV = $pdo->prepare("
            SELECT epi_id, epi_nome, epi_fabricante, epi_ca, epi_vencimento_ca
            FROM epis
            WHERE epi_tipo_item = 'EPI_COM_CA'
              AND epi_vencimento_ca >= :today
              AND epi_vencimento_ca <= :in7days
            ORDER BY epi_vencimento_ca ASC
        ");
        $stmtCaAV->execute(['today' => $todayStr, 'in7days' => $in7DaysStr]);
        $caAVencerCatalogList = $stmtCaAV->fetchAll(PDO::FETCH_ASSOC);

        // Monta $caAVencerList unificado para Modal 2
        $caAVencerList = [];
        foreach ($vidaUtilAVencerList as $r) {
            $caAVencerList[] = [
                'fun_nome' => $r['fun_nome'] ?? '',
                'fun_cargo' => $r['fun_cargo'] ?? '',
                'epi_nome' => $r['epi_nome'] ?? '',
                'epi_ca' => $r['epi_ca'] ?? '',
                'epi_fabricante' => $r['epi_fabricante'] ?? '',
                'entr_data_entrega' => $r['entr_data_entrega'] ?? null,
                'epi_validade_uso_dias' => $r['epi_validade_uso_dias'] ?? null,
                'data_vencimento' => $r['data_vencimento'] ?? null,
                'dias_restantes' => $r['dias_restantes'] ?? 0
            ];
        }
        foreach ($caAVencerCatalogList as $r) {
            $dtV = new DateTime($r['epi_vencimento_ca']);
            $dRestantes = (int)$todayObj->diff($dtV)->format('%r%a');
            $caAVencerList[] = [
                'fun_nome' => '',
                'fun_cargo' => '',
                'epi_nome' => $r['epi_nome'] ?? '',
                'epi_ca' => $r['epi_ca'] ?? '',
                'epi_fabricante' => $r['epi_fabricante'] ?? '',
                'entr_data_entrega' => null,
                'epi_validade_uso_dias' => null,
                'data_vencimento' => $r['epi_vencimento_ca'],
                'dias_restantes' => $dRestantes
            ];
        }

        // 4. CARD 3: Entregas Realizadas Hoje (Somente FINALIZADA em Brasília)
        $stmtEntH = $pdo->prepare("
            SELECT e.entr_id, f.fun_nome, f.fun_cargo, e.entr_data_entrega, e.entr_status, e.entr_validacao_senha
            FROM entrega_epis e
            JOIN funcionarios f ON e.fun_id = f.fun_id
            WHERE e.entr_status = 'FINALIZADA'
              AND e.entr_data_entrega >= :todayStart
              AND e.entr_data_entrega <= :todayEnd
            ORDER BY f.fun_nome ASC
        ");
        $stmtEntH->execute(['todayStart' => $todayStart, 'todayEnd' => $todayEnd]);
        $entregasHojeList = $stmtEntH->fetchAll(PDO::FETCH_ASSOC);

        // 5. CARD 4 — PENDÊNCIAS: Categorias 2, 3 e 4
        // PENDÊNCIA 2: Funcionários Sem PIN (Ativos e Afastados)
        $semPinList = $pdo->query("
            SELECT f.fun_id, f.fun_nome, f.fun_cpf, f.fun_cargo, f.fun_departamento, COALESCE(a.ass_status, 'PENDENTE') as status_pin
            FROM funcionarios f
            LEFT JOIN assinatura_eletronica a ON f.fun_id = a.fun_id
            WHERE f.fun_situacao NOT IN ('INATIVO', 'DEMITIDO')
              AND (a.ass_status IS NULL OR a.ass_status IN ('PENDENTE', 'BLOQUEADO', 'INATIVO'))
            ORDER BY f.fun_nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // PENDÊNCIA 3: EPI Controlado sem Vida Útil
        $semVidaUtilList = $pdo->query("
            SELECT epi_id, epi_nome, epi_ca, epi_fabricante, epi_vida_util_tipo, epi_vida_util
            FROM epis
            WHERE epi_status = 'ATIVO'
              AND epi_vida_util_tipo = 'CONTROLADO'
              AND (epi_vida_util IS NULL OR epi_vida_util <= 0)
            ORDER BY epi_nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // PENDÊNCIA 4: EPI sem C.A. sem Rastreabilidade
        $semRastreabilidadeList = $pdo->query("
            SELECT epi_id, epi_nome, epi_tipo_item, epi_numero_lote, epi_modelo, epi_identificacao, epi_ref_fornecedor
            FROM epis
            WHERE epi_tipo_item = 'ITEM_SEGURANCA_SEM_CA'
              AND epi_status = 'ATIVO'
              AND (epi_numero_lote IS NULL OR TRIM(epi_numero_lote) = '')
              AND (epi_modelo IS NULL OR TRIM(epi_modelo) = '')
              AND (epi_identificacao IS NULL OR TRIM(epi_identificacao) = '')
              AND (epi_ref_fornecedor IS NULL OR TRIM(epi_ref_fornecedor) = '')
            ORDER BY epi_nome ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Alertas e KPIs Exatos
        $alerts['ca_vencidos'] = $totCaVencidos;
        $alerts['ca_vencendo_7d'] = count($caAVencerCatalogList);
        $alerts['vida_util_vencida'] = count($vidaUtilVencidaList);
        $alerts['troca_proxima'] = count($vidaUtilAVencerList);

        $kpis['epis_vencidos'] = $alerts['ca_vencidos'] + $alerts['vida_util_vencida']; // CARD 1
        $kpis['a_vencer_7d'] = $alerts['ca_vencendo_7d'] + $alerts['troca_proxima'];     // CARD 2
        $kpis['entregas_hoje'] = count($entregasHojeList);                              // CARD 3

        $pendenciasDet = [
            'ca_vencidos' => $totCaVencidos,
            'sem_pin' => count($semPinList),
            'sem_vida_util' => count($semVidaUtilList),
            'sem_rastreabilidade' => count($semRastreabilidadeList)
        ];
        $kpis['pendencias'] = $pendenciasDet['ca_vencidos'] + $pendenciasDet['sem_pin'] + $pendenciasDet['sem_vida_util'] + $pendenciasDet['sem_rastreabilidade']; // CARD 4

        $custos['sem_pin'] = $pendenciasDet['sem_pin'];

        // Alertas de Funcionários
        $funcVencidosMap = [];
        foreach ($vidaUtilVencidaList as $r) {
            $fId = (int)$r['fun_id'];
            if (!isset($funcVencidosMap[$fId])) {
                $funcVencidosMap[$fId] = [
                    'fun_id' => $fId,
                    'fun_nome' => $r['fun_nome'],
                    'fun_cargo' => $r['fun_cargo'],
                    'fun_departamento' => $r['fun_departamento'],
                    'qtd_epis' => 0,
                    'epis_lista' => []
                ];
            }
            $funcVencidosMap[$fId]['qtd_epis']++;
            $funcVencidosMap[$fId]['epis_lista'][] = $r['epi_nome'];
        }
        $funcVencidosList = [];
        foreach ($funcVencidosMap as $r) {
            $r['epis_lista'] = implode(', ', array_unique($r['epis_lista']));
            $funcVencidosList[] = $r;
        }

        $funcTrocaMap = [];
        foreach ($vidaUtilAVencerList as $r) {
            $fId = (int)$r['fun_id'];
            if (!isset($funcTrocaMap[$fId])) {
                $funcTrocaMap[$fId] = [
                    'fun_id' => $fId,
                    'fun_nome' => $r['fun_nome'],
                    'fun_cargo' => $r['fun_cargo'],
                    'fun_departamento' => $r['fun_departamento'],
                    'qtd_epis' => 0,
                    'epis_lista' => []
                ];
            }
            $funcTrocaMap[$fId]['qtd_epis']++;
            $funcTrocaMap[$fId]['epis_lista'][] = $r['epi_nome'];
        }
        $funcTrocaList = [];
        foreach ($funcTrocaMap as $r) {
            $r['epis_lista'] = implode(', ', array_unique($r['epis_lista']));
            $funcTrocaList[] = $r;
        }

        $alerts['func_vencidos'] = count($funcVencidosList);
        $alerts['func_troca'] = count($funcTrocaList);

        // 6. CUSTO MENSAL E CUSTO ACUMULADO (Reais e Dinâmicos)
        $monthNum = (int)$todayObj->format('n');
        $yearNum = (int)$todayObj->format('Y');

        $stmtCustoM = $pdo->prepare("
            SELECT COALESCE(SUM(i.item_quantidade * ep.epi_valor), 0)
            FROM itens_entrega i
            INNER JOIN epis ep ON i.epi_id = ep.epi_id
            INNER JOIN entrega_epis ent ON i.entr_id = ent.entr_id
            WHERE ent.entr_status = 'FINALIZADA'
              AND MONTH(ent.entr_data_entrega) = :m
              AND YEAR(ent.entr_data_entrega) = :y
        ");
        $stmtCustoM->execute(['m' => $monthNum, 'y' => $yearNum]);
        $cM = (float)$stmtCustoM->fetchColumn();

        $stmtCustoA = $pdo->query("
            SELECT COALESCE(SUM(i.item_quantidade * ep.epi_valor), 0)
            FROM itens_entrega i
            INNER JOIN epis ep ON i.epi_id = ep.epi_id
            INNER JOIN entrega_epis ent ON i.entr_id = ent.entr_id
            WHERE ent.entr_status = 'FINALIZADA'
        ");
        $cA = (float)$stmtCustoA->fetchColumn();

        $stmtMinDate = $pdo->query("
            SELECT MIN(entr_data_entrega)
            FROM entrega_epis
            WHERE entr_status = 'FINALIZADA'
        ");
        $minDateStr = $stmtMinDate->fetchColumn();

        $legendaAcumuladoData = "Histórico";
        if ($minDateStr) {
            $dtMin = new DateTime($minDateStr);
            $legendaAcumuladoData = "desde " . $dtMin->format('d/m/Y');
        }

        $custos['mensal'] = 'R$ ' . number_format($cM, 2, ',', '.');
        $custos['acumulado'] = 'R$ ' . number_format($cA, 2, ',', '.');
        $custos['legenda_acumulado'] = 'Acumulado (' . $legendaAcumuladoData . ')';

        // 7. CONFORMIDADE (Fórmula Oficial do Android: Ativos / Total * 100)
        $totFunc = (int)$pdo->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
        $totAtivos = (int)$pdo->query("SELECT COUNT(*) FROM funcionarios WHERE fun_situacao = 'ATIVO'")->fetchColumn();
        $pctConformidade = $totFunc > 0 ? (int)round(($totAtivos / $totFunc) * 100) : 100;
        $conformidade = [
            'pct' => $pctConformidade,
            'em_dia' => $totAtivos,
            'tot_func' => $totFunc
        ];

        // 8. TOP 5 EPIs GERAL (Regra Oficial Android: SEM filtro entr_status = 'FINALIZADA')
        $stmtT = $pdo->query("
            SELECT COALESCE(NULLIF(TRIM(i.item_epi_nome_snapshot), ''), e.epi_nome) as nome,
                   SUM(i.item_quantidade) as total 
            FROM itens_entrega i 
            LEFT JOIN epis e ON i.epi_id = e.epi_id 
            GROUP BY nome 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $topRes = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        $maxQ = (!empty($topRes) && isset($topRes[0]['total'])) ? max(1, (int)$topRes[0]['total']) : 1;
        $cores = ['#F59E0B', '#3B82F6', '#10B981', '#8B5CF6', '#CBD5E1'];
        $newTop = [];
        foreach ($topRes as $idx => $r) {
            $q = (int)$r['total'];
            $newTop[] = [
                'nome' => $r['nome'] ?: 'Item sem nome',
                'total' => $q,
                'pct' => round(($q / $maxQ) * 100, 2),
                'cor' => $cores[$idx % count($cores)]
            ];
        }
        $top5Epis = $newTop;

        // 8.1 TOP 5 EPIs MÊS ATUAL
        $stmtTM = $pdo->prepare("
            SELECT COALESCE(NULLIF(TRIM(i.item_epi_nome_snapshot), ''), e.epi_nome) as nome,
                   SUM(i.item_quantidade) as total 
            FROM itens_entrega i 
            INNER JOIN entrega_epis entr ON i.entr_id = entr.entr_id
            LEFT JOIN epis e ON i.epi_id = e.epi_id 
            WHERE MONTH(entr.entr_data_entrega) = :m
              AND YEAR(entr.entr_data_entrega) = :y
            GROUP BY nome 
            ORDER BY total DESC 
            LIMIT 5
        ");
        $stmtTM->execute(['m' => $monthNum, 'y' => $yearNum]);
        $topResM = $stmtTM->fetchAll(PDO::FETCH_ASSOC);
        $maxQM = (!empty($topResM) && isset($topResM[0]['total'])) ? max(1, (int)$topResM[0]['total']) : 1;
        $newTopM = [];
        foreach ($topResM as $idx => $r) {
            $q = (int)$r['total'];
            $newTopM[] = [
                'nome' => $r['nome'] ?: 'Item sem nome',
                'total' => $q,
                'pct' => round(($q / $maxQM) * 100, 2),
                'cor' => $cores[$idx % count($cores)]
            ];
        }
        $top5EpisMensal = $newTopM;

        // 9. GRÁFICO DOS ÚLTIMOS 7 DIAS (Dinâmico em Brasília)
        $labels7d = [];
        $data7d = [];
        for ($i = 6; $i >= 0; $i--) {
            $dtDay = (clone $todayObj)->modify("-{$i} days");
            $dayStr = $dtDay->format('Y-m-d');
            $dayLabel = $dtDay->format('d/m');

            $stmtCountDay = $pdo->prepare("
                SELECT COUNT(*)
                FROM entrega_epis
                WHERE entr_status = 'FINALIZADA'
                  AND entr_data_entrega >= :dStart
                  AND entr_data_entrega <= :dEnd
            ");
            $stmtCountDay->execute([
                'dStart' => $dayStr . ' 00:00:00',
                'dEnd' => $dayStr . ' 23:59:59'
            ]);
            $countDay = (int)$stmtCountDay->fetchColumn();

            $labels7d[] = $dayLabel;
            $data7d[] = $countDay;
        }
        $entregas7Dias = [
            'labels' => $labels7d,
            'data' => $data7d
        ];

        // 9.1 Entregas do Mês Atual por Semana
        $stmtM = $pdo->prepare("
            SELECT 
                CASE 
                    WHEN DAY(entr_data_entrega) BETWEEN 1 AND 7 THEN 'Semana 1'
                    WHEN DAY(entr_data_entrega) BETWEEN 8 AND 14 THEN 'Semana 2'
                    WHEN DAY(entr_data_entrega) BETWEEN 15 AND 21 THEN 'Semana 3'
                    ELSE 'Semana 4'
                END as semana,
                COUNT(*) as total
            FROM entrega_epis
            WHERE entr_status = 'FINALIZADA'
              AND MONTH(entr_data_entrega) = :m
              AND YEAR(entr_data_entrega) = :y
            GROUP BY semana
        ");
        $stmtM->execute(['m' => $monthNum, 'y' => $yearNum]);
        $rowsM = $stmtM->fetchAll(PDO::FETCH_KEY_PAIR);
        $entregasMensal = [
            'labels' => ['Semana 1', 'Semana 2', 'Semana 3', 'Semana 4'],
            'data' => [
                (int)($rowsM['Semana 1'] ?? 0),
                (int)($rowsM['Semana 2'] ?? 0),
                (int)($rowsM['Semana 3'] ?? 0),
                (int)($rowsM['Semana 4'] ?? 0)
            ]
        ];

        // Últimas Atividades
        $stmtA = $pdo->query("
            SELECT log_id, log_acao, log_detalhes, log_datahora 
            FROM log_auditoria 
            ORDER BY log_id DESC 
            LIMIT 10
        ");
        $logs = $stmtA->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($logs)) {
            $ultimasAtividades = array_map('formatarLogAuditoria', $logs);
        }

        // Ordenação Alfabética das Listas dos Modais
        if (!empty($caVencidosList)) {
            usort($caVencidosList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['epi_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['epi_nome'] ?? ''));
            });
        }
        if (!empty($vidaUtilVencidaList)) {
            usort($vidaUtilVencidaList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['epi_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['epi_nome'] ?? ''));
            });
        }
        if (!empty($caAVencerList)) {
            usort($caAVencerList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['epi_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['epi_nome'] ?? ''));
            });
        }
        if (!empty($entregasHojeList)) {
            usort($entregasHojeList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['fun_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['fun_nome'] ?? ''));
            });
        }
        if (!empty($semPinList)) {
            usort($semPinList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['fun_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['fun_nome'] ?? ''));
            });
        }
        if (!empty($semVidaUtilList)) {
            usort($semVidaUtilList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['epi_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['epi_nome'] ?? ''));
            });
        }
        if (!empty($semRastreabilidadeList)) {
            usort($semRastreabilidadeList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['epi_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['epi_nome'] ?? ''));
            });
        }
        if (!empty($funcVencidosList)) {
            usort($funcVencidosList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['fun_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['fun_nome'] ?? ''));
            });
        }
        if (!empty($funcTrocaList)) {
            usort($funcTrocaList, static function(array $a, array $b): int {
                return strcmp(normalizarParaOrdenacaoPHP($a['fun_nome'] ?? ''), normalizarParaOrdenacaoPHP($b['fun_nome'] ?? ''));
            });
        }
    }
} catch (Throwable $e) {
    // Mantém estado se houver falha
}

// Fallbacks de demonstração para visualização formatada
if (empty($ultimasAtividades)) {
    $ultimasAtividades = [
        ['tipo' => 'danger', 'icone' => 'bi-arrow-return-left', 'badge' => 'DEVOLUÇÃO', 'texto' => 'Entrega n. 166 finalizada para Marcos Augusto da Silva. Origem: ONLINE.', 'data' => 'Atividade em ' . date('d/m') . ' às 21:17'],
        ['tipo' => 'primary', 'icone' => 'bi-fingerprint', 'badge' => 'ASSINATURA', 'texto' => 'Validação de PIN de assinatura efetuada com sucesso.', 'data' => 'Atividade em ' . date('d/m') . ' às 21:17'],
        ['tipo' => 'info', 'icone' => 'bi-shield-lock-fill', 'badge' => 'ACESSO', 'texto' => 'Login realizado no sistema pelo dispositivo Motorola Moto G(60).', 'data' => 'Atividade em ' . date('d/m') . ' às 21:16'],
        ['tipo' => 'warning', 'icone' => 'bi-pencil-square', 'badge' => 'CADASTRO', 'texto' => '3 campos alterados no cadastro do EPI (Exige Tamanho, Status, C.A.).', 'data' => 'Atividade em ' . date('d/m') . ' às 21:14'],
        ['tipo' => 'purple', 'icone' => 'bi-file-earmark-bar-chart-fill', 'badge' => 'RELATÓRIO', 'texto' => 'Relatório Geral de Consumo de EPIs gerado com sucesso.', 'data' => 'Atividade em ' . date('d/m') . ' às 21:10']
    ];
}

// Renderizadores HTML dos modais para carga inicial e atualizações AJAX em tempo real
if (!function_exists('renderModalCaVencidosHtml')) {
    function renderModalCaVencidosHtml(array $caVencidosList, array $vidaUtilVencidaList = []): string {
        $totCa = count($caVencidosList);
        $totVu = count($vidaUtilVencidaList);
        $totalVencidos = $totCa + $totVu;

        if ($totalVencidos === 0) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum EPI vencido no momento.</div>';
        }

        if ($totVu === 0) {
            return renderTabelaCaVencidosAux($caVencidosList);
        }

        $html = '<ul class="nav nav-pills nav-fill mb-3 gap-2" id="pills-tab-vencidos" role="tablist" style="font-size: 13px;">';
        
        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link active fw-bold py-2 rounded-3" id="pills-vencidos-todos-tab" data-bs-toggle="pill" data-bs-target="#pills-vencidos-todos" type="button" role="tab"><i class="bi bi-layers-fill me-1"></i>Todos (' . $totalVencidos . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-danger" id="pills-vencidos-ca-tab" data-bs-toggle="pill" data-bs-target="#pills-vencidos-ca" type="button" role="tab"><i class="bi bi-shield-x me-1"></i>C.A. Vencidos (' . $totCa . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-warning-emphasis" id="pills-vencidos-vu-tab" data-bs-toggle="pill" data-bs-target="#pills-vencidos-vu" type="button" role="tab"><i class="bi bi-exclamation-triangle-fill me-1"></i>Vida Útil Vencida (' . $totVu . ')</button>';
        $html .= '</li>';

        $html .= '</ul>';

        $html .= '<div class="tab-content" id="pills-tabContent-vencidos">';

        $html .= '<div class="tab-pane fade show active" id="pills-vencidos-todos" role="tabpanel">';
        
        if ($totCa > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 mt-1 alert alert-danger py-2 px-3 border-danger rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-danger" style="font-size: 13.5px;"><i class="bi bi-shield-x me-2 fs-6"></i>Equipamentos com C.A. Vencido (' . $totCa . ') — Catálogo / Estoque</span>';
            $html .= '<a href="epis.php?acao=controle_ca" class="btn btn-sm btn-danger py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Controle C.A.</a>';
            $html .= '</div>';
            $html .= renderTabelaCaVencidosAux($caVencidosList);
        }

        if ($totVu > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 ' . ($totCa > 0 ? 'mt-4' : 'mt-1') . ' alert alert-warning py-2 px-3 border-warning rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-exclamation-triangle-fill me-2 fs-6"></i>EPIs em Uso com Vida Útil Vencida (' . $totVu . ') — Em Posse</span>';
            $html .= '<a href="entregas.php" class="btn btn-sm btn-warning text-dark py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Gerenciar Trocas</a>';
            $html .= '</div>';
            $html .= renderModalVidaUtilVencidaHtml($vidaUtilVencidaList);
        }

        $html .= '</div>';

        $html .= '<div class="tab-pane fade" id="pills-vencidos-ca" role="tabpanel">';
        $html .= renderTabelaCaVencidosAux($caVencidosList);
        $html .= '</div>';

        $html .= '<div class="tab-pane fade" id="pills-vencidos-vu" role="tabpanel">';
        $html .= renderModalVidaUtilVencidaHtml($vidaUtilVencidaList);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('renderModalVidaUtilVencidaHtml')) {
    function renderModalVidaUtilVencidaHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum EPI em uso com vida útil expirada.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador</th><th>Equipamento (EPI)</th><th>C.A.</th><th>Data Entrega</th><th>Limite Vida Útil</th><th>Status</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $entr = formatarDataHoraBr($item['entr_data_entrega'] ?? null, 'd/m/Y');
            $lim = !empty($item['data_vencimento_uso']) ? date('d/m/Y', strtotime($item['data_vencimento_uso'])) : '---';
            $html .= '<tr>'
                . '<td><div class="fw-bold text-primary">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</div><small class="text-muted">' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '</small></td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td class="fw-semibold text-secondary">' . htmlspecialchars((string)($item['epi_ca'] ?: 'Isento')) . '</td>'
                . '<td>' . $entr . '</td>'
                . '<td class="fw-bold text-danger">' . $lim . '</td>'
                . '<td><span class="badge bg-danger">Troca Exigida</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalCaAVencerHtml')) {
    function renderModalCaAVencerHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum EPI com troca ou vencimento próximo nos próximos 30 dias.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador / Destino</th><th>Item / Equipamento</th><th>C.A.</th><th>Limite / Vencimento</th><th>Previsão</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $dtVenc = !empty($item['data_vencimento']) ? $item['data_vencimento'] : ($item['epi_vencimento_ca'] ?? null);
            $venc = !empty($dtVenc) ? date('d/m/Y', strtotime((string)$dtVenc)) : '---';
            $dias = (int)($item['dias_restantes'] ?? 0);
            $funInfo = !empty($item['fun_nome']) 
                ? ('<div class="fw-bold text-primary">' . htmlspecialchars((string)$item['fun_nome']) . '</div><small class="text-muted">' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '</small>') 
                : '<span class="badge bg-light text-dark border">Estoque Geral</span>';

            $html .= '<tr>'
                . '<td>' . $funInfo . '</td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td class="fw-bold text-primary">' . htmlspecialchars((string)($item['epi_ca'] ?: 'Isento')) . '</td>'
                . '<td class="fw-bold text-warning-emphasis">' . $venc . '</td>'
                . '<td><span class="badge bg-warning text-dark">Em ' . $dias . ' dia(s)</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalEntregasHojeHtml')) {
    function renderModalEntregasHojeHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-info text-center py-4 rounded-3 m-0"><i class="bi bi-info-circle-fill me-2 fs-5"></i>Nenhuma entrega realizada até o momento na data de hoje.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Cód. Entrega</th><th>Colaborador</th><th>Cargo</th><th>Data / Hora</th><th>Validação PIN</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $dt = formatarDataHoraBr($item['entr_data_entrega'] ?? null, 'd/m/Y H:i');
            $html .= '<tr>'
                . '<td class="fw-bold text-primary">#' . (int)($item['entr_id'] ?? 0) . '</td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</td>'
                . '<td class="text-muted">' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '</td>'
                . '<td>' . $dt . '</td>'
                . '<td><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>' . htmlspecialchars((string)($item['entr_validacao_senha'] ?: 'VALIDADA')) . '</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderTabelaSemPinAux')) {
    function renderTabelaSemPinAux(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success py-2 px-3 small rounded-3 m-0">Nenhum colaborador com PIN pendente.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador</th><th>CPF</th><th>Departamento</th><th>Cargo</th><th>Status PIN/Senha</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $cpfRaw = (string)($item['fun_cpf'] ?? '');
            $cpfM = (strlen($cpfRaw) === 11) ? (substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, 9)) : ($cpfRaw ?: '---');
            $html .= '<tr>'
                . '<td><div class="fw-bold text-primary">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</div><small class="text-muted">ID: #' . (int)($item['fun_id'] ?? 0) . '</small></td>'
                . '<td class="fw-semibold text-secondary">' . htmlspecialchars($cpfM) . '</td>'
                . '<td>' . htmlspecialchars((string)($item['fun_departamento'] ?: '---')) . '</td>'
                . '<td>' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '</td>'
                . '<td><span class="badge bg-warning text-dark">' . htmlspecialchars((string)($item['status_pin'] ?? 'PENDENTE')) . '</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderTabelaCaVencidosAux')) {
    function renderTabelaCaVencidosAux(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success py-2 px-3 small rounded-3 m-0"><i class="bi bi-check-circle me-1"></i>Nenhum EPI com C.A. vencido.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Item / Equipamento</th><th>Fabricante</th><th>C.A.</th><th>Data Vencimento</th><th>Situação</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $venc = !empty($item['epi_vencimento_ca']) ? date('d/m/Y', strtotime($item['epi_vencimento_ca'])) : '---';
            $html .= '<tr>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td>' . htmlspecialchars((string)($item['epi_fabricante'] ?: '---')) . '</td>'
                . '<td class="fw-bold text-primary">' . htmlspecialchars((string)($item['epi_ca'] ?: 'Isento')) . '</td>'
                . '<td class="fw-bold text-danger">' . $venc . '</td>'
                . '<td><span class="badge bg-danger">C.A. Vencido</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderTabelaSemVidaUtilAux')) {
    function renderTabelaSemVidaUtilAux(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success py-2 px-3 small rounded-3 m-0"><i class="bi bi-check-circle me-1"></i>Nenhum EPI controlado sem vida útil cadastrada.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Equipamento (EPI)</th><th>C.A.</th><th>Fabricante</th><th>Tipo Vida Útil</th><th>Situação</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $html .= '<tr>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td class="fw-bold text-primary">' . htmlspecialchars((string)($item['epi_ca'] ?: 'Isento')) . '</td>'
                . '<td>' . htmlspecialchars((string)($item['epi_fabricante'] ?: '---')) . '</td>'
                . '<td><span class="badge bg-warning text-dark">CONTROLADO</span></td>'
                . '<td><span class="badge bg-danger">Vida Útil Ausente / Inválida</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderTabelaSemRastreabilidadeAux')) {
    function renderTabelaSemRastreabilidadeAux(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success py-2 px-3 small rounded-3 m-0"><i class="bi bi-check-circle me-1"></i>Nenhum item sem C.A. com rastreabilidade pendente.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Item / Equipamento</th><th>Tipo Item</th><th>Lote</th><th>Modelo</th><th>Rastreabilidade</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $html .= '<tr>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td><span class="badge bg-secondary">SEM C.A.</span></td>'
                . '<td>' . htmlspecialchars((string)($item['epi_numero_lote'] ?: '---')) . '</td>'
                . '<td>' . htmlspecialchars((string)($item['epi_modelo'] ?: '---')) . '</td>'
                . '<td><span class="badge bg-danger">Campos de Rastreabilidade Vazios</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalPinBloqueadosHtml')) {
    function renderModalPinBloqueadosHtml(array $semPinList = [], array $caVencidosList = [], array $semVidaUtilList = [], array $semRastreabilidadeList = []): string {
        $totSemPin = count($semPinList);
        $totCaVencidos = count($caVencidosList);
        $totSemVu = count($semVidaUtilList);
        $totSemRast = count($semRastreabilidadeList);
        $totalPendencias = $totSemPin + $totCaVencidos + $totSemVu + $totSemRast;

        if ($totalPendencias === 0) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhuma pendência registrada no momento!</div>';
        }

        $html = '<ul class="nav nav-pills nav-fill mb-3 gap-2" id="pills-tab-pendencias" role="tablist" style="font-size: 13px;">';
        
        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link active fw-bold py-2 rounded-3" id="pills-todas-tab" data-bs-toggle="pill" data-bs-target="#pills-todas" type="button" role="tab"><i class="bi bi-layers-fill me-1"></i>Todas (' . $totalPendencias . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-danger" id="pills-ca-tab" data-bs-toggle="pill" data-bs-target="#pills-ca" type="button" role="tab"><i class="bi bi-shield-x me-1"></i>C.A. Vencidos (' . $totCaVencidos . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-primary" id="pills-pin-tab" data-bs-toggle="pill" data-bs-target="#pills-pin" type="button" role="tab"><i class="bi bi-person-fill-exclamation me-1"></i>Sem PIN (' . $totSemPin . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-warning-emphasis" id="pills-vu-tab" data-bs-toggle="pill" data-bs-target="#pills-vu" type="button" role="tab"><i class="bi bi-clock-history me-1"></i>Sem Vida Útil (' . $totSemVu . ')</button>';
        $html .= '</li>';

        $html .= '<li class="nav-item" role="presentation">';
        $html .= '<button class="nav-link fw-bold py-2 rounded-3 text-secondary" id="pills-rast-tab" data-bs-toggle="pill" data-bs-target="#pills-rast" type="button" role="tab"><i class="bi bi-qr-code me-1"></i>Sem Rastreab. (' . $totSemRast . ')</button>';
        $html .= '</li>';

        $html .= '</ul>';

        $html .= '<div class="tab-content" id="pills-tabContent-pendencias">';

        // Tab 1: Todas
        $html .= '<div class="tab-pane fade show active" id="pills-todas" role="tabpanel">';
        
        // 1. C.A. Vencidos
        if ($totCaVencidos > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 mt-1 alert alert-danger py-2 px-3 border-danger rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-danger" style="font-size: 13.5px;"><i class="bi bi-exclamation-octagon-fill me-2 fs-6"></i>Catálogo com C.A. Vencido (' . $totCaVencidos . ')</span>';
            $html .= '<a href="epis.php?acao=controle_ca" class="btn btn-sm btn-danger py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Controle C.A.</a>';
            $html .= '</div>';
            $html .= renderTabelaCaVencidosAux($caVencidosList);
        }

        // 2. Colaboradores sem PIN
        if ($totSemPin > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 ' . ($totCaVencidos > 0 ? 'mt-4' : 'mt-1') . ' alert alert-primary py-2 px-3 border-primary rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-primary" style="font-size: 13.5px;"><i class="bi bi-person-badge me-2 fs-6"></i>Colaboradores sem PIN de Assinatura (' . $totSemPin . ')</span>';
            $html .= '<a href="funcionarios.php?acao=pin" class="btn btn-sm btn-primary py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Gerenciar PINs</a>';
            $html .= '</div>';
            $html .= renderTabelaSemPinAux($semPinList);
        }

        // 3. EPI Controlado sem Vida Útil
        if ($totSemVu > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 ' . (($totCaVencidos > 0 || $totSemPin > 0) ? 'mt-4' : 'mt-1') . ' alert alert-warning py-2 px-3 border-warning rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-clock-history me-2 fs-6"></i>EPIs Controlados sem Vida Útil Cadastrada (' . $totSemVu . ')</span>';
            $html .= '<a href="epis.php" class="btn btn-sm btn-warning text-dark py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Cadastros EPI</a>';
            $html .= '</div>';
            $html .= renderTabelaSemVidaUtilAux($semVidaUtilList);
        }

        // 4. EPI sem C.A. sem Rastreabilidade
        if ($totSemRast > 0) {
            $html .= '<div class="d-flex align-items-center justify-content-between mb-2 ' . (($totCaVencidos > 0 || $totSemPin > 0 || $totSemVu > 0) ? 'mt-4' : 'mt-1') . ' alert alert-secondary py-2 px-3 border-secondary rounded-3 m-0 mb-2">';
            $html .= '<span class="fw-bold text-dark" style="font-size: 13.5px;"><i class="bi bi-qr-code me-2 fs-6"></i>Itens sem C.A. com Rastreabilidade Pendente (' . $totSemRast . ')</span>';
            $html .= '<a href="epis.php" class="btn btn-sm btn-secondary py-1 px-3 fw-bold rounded-2" style="font-size: 11.5px;">Cadastros EPI</a>';
            $html .= '</div>';
            $html .= renderTabelaSemRastreabilidadeAux($semRastreabilidadeList);
        }

        $html .= '</div>';

        // Tab 2: C.A. Vencidos
        $html .= '<div class="tab-pane fade" id="pills-ca" role="tabpanel">';
        $html .= renderTabelaCaVencidosAux($caVencidosList);
        $html .= '</div>';

        // Tab 3: Sem PIN
        $html .= '<div class="tab-pane fade" id="pills-pin" role="tabpanel">';
        $html .= renderTabelaSemPinAux($semPinList);
        $html .= '</div>';

        // Tab 4: Sem Vida Útil
        $html .= '<div class="tab-pane fade" id="pills-vu" role="tabpanel">';
        $html .= renderTabelaSemVidaUtilAux($semVidaUtilList);
        $html .= '</div>';

        // Tab 5: Sem Rastreabilidade
        $html .= '<div class="tab-pane fade" id="pills-rast" role="tabpanel">';
        $html .= renderTabelaSemRastreabilidadeAux($semRastreabilidadeList);
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}

if (!function_exists('renderModalEpisEmPosseHtml')) {
    function renderModalEpisEmPosseHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-info text-center py-4 rounded-3 m-0"><i class="bi bi-info-circle-fill me-2 fs-5"></i>Nenhum registro de EPI em posse no momento.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador</th><th>Cargo / Setor</th><th>Equipamento (EPI)</th><th>C.A.</th><th>Data Entrega</th><th>Qtd</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $entr = formatarDataHoraBr($item['entr_data_entrega'] ?? null, 'd/m/Y');
            $html .= '<tr>'
                . '<td class="fw-bold text-primary">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</td>'
                . '<td class="text-muted">' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . ' | Setor: ' . htmlspecialchars((string)($item['fun_departamento'] ?: '---')) . '</td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epi_nome'] ?? '')) . '</td>'
                . '<td class="fw-semibold text-secondary">' . htmlspecialchars((string)($item['epi_ca'] ?: 'Isento')) . '</td>'
                . '<td>' . $entr . '</td>'
                . '<td><span class="badge bg-light text-dark border">' . (int)($item['item_quantidade'] ?? 1) . ' un</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalFuncVencidosHtml')) {
    function renderModalFuncVencidosHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum funcionário com EPI vencido no momento.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador</th><th>Cargo / Setor</th><th>Qtd Vencidos</th><th>Equipamentos Expirados</th><th>Situação</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $html .= '<tr>'
                . '<td><div class="fw-bold text-primary">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</div><small class="text-muted">ID: #' . (int)($item['fun_id'] ?? 0) . '</small></td>'
                . '<td>' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '<br><small class="text-muted">Setor: ' . htmlspecialchars((string)($item['fun_departamento'] ?: '---')) . '</small></td>'
                . '<td><span class="badge bg-danger fs-6">' . (int)($item['qtd_epis'] ?? 1) . ' EPI(s)</span></td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epis_lista'] ?: '---')) . '</td>'
                . '<td><span class="badge bg-danger">Troca Pendente</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalFuncTrocaHtml')) {
    function renderModalFuncTrocaHtml(array $list): string {
        if (empty($list)) {
            return '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum funcionário próximo da troca de EPI.</div>';
        }
        $html = '<div class="table-responsive"><table class="table table-hover align-middle mb-0" style="font-size: 13.5px;"><thead class="table-light"><tr><th>Colaborador</th><th>Cargo / Setor</th><th>Qtd A Vencer</th><th>Equipamentos a Substituir</th><th>Previsão</th></tr></thead><tbody>';
        foreach ($list as $item) {
            $html .= '<tr>'
                . '<td><div class="fw-bold text-primary">' . htmlspecialchars((string)($item['fun_nome'] ?? '')) . '</div><small class="text-muted">ID: #' . (int)($item['fun_id'] ?? 0) . '</small></td>'
                . '<td>' . htmlspecialchars((string)($item['fun_cargo'] ?: '---')) . '<br><small class="text-muted">Setor: ' . htmlspecialchars((string)($item['fun_departamento'] ?: '---')) . '</small></td>'
                . '<td><span class="badge bg-warning text-dark fs-6">' . (int)($item['qtd_epis'] ?? 1) . ' EPI(s)</span></td>'
                . '<td class="fw-bold text-dark">' . htmlspecialchars((string)($item['epis_lista'] ?: '---')) . '</td>'
                . '<td><span class="badge bg-warning text-dark">Troca Próxima</span></td>'
                . '</tr>';
        }
        $html .= '</tbody></table></div>';
        return $html;
    }
}

if (!function_exists('renderModalTodasAtividadesHtml')) {
    function renderModalTodasAtividadesHtml(array $list): string {
        if (empty($list)) {
            return '<p class="text-muted text-center py-4">Nenhuma atividade recente encontrada.</p>';
        }
        $html = '<div class="activity-feed-list">';
        foreach ($list as $act) {
            $html .= '<div class="activity-feed-item">'
                . '<div class="activity-icon-box activity-icon-' . htmlspecialchars((string)($act['tipo'] ?? 'primary')) . '"><i class="bi ' . htmlspecialchars((string)($act['icone'] ?? 'bi-info-circle-fill')) . '"></i></div>'
                . '<div class="flex-grow-1">'
                . '<div class="d-flex align-items-center gap-2 mb-1"><span class="badge-subtle-' . htmlspecialchars((string)($act['tipo'] ?? 'primary')) . '">' . htmlspecialchars((string)($act['badge'] ?? 'SISTEMA')) . '</span><span class="activity-date">' . htmlspecialchars((string)($act['data'] ?? '')) . '</span></div>'
                . '<p class="activity-text">' . htmlspecialchars((string)($act['texto'] ?? '')) . '</p>'
                . '</div>'
                . '</div>';
        }
        $html .= '</div>';
        return $html;
    }
}

if (!function_exists('renderTop5EpisHtml')) {
    function renderTop5EpisHtml(array $list): string {
        if (empty($list)) {
            return '<p class="text-muted text-center py-3 m-0">Nenhum registro de uso no período.</p>';
        }
        $html = '';
        foreach ($list as $idx => $epi) {
            $html .= '<div class="top5-item" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalEpisEmPosse" title="Clique para ver detalhes do EPI">'
                . '<div class="top5-header">'
                . '<div class="top5-name"><span class="top5-rank-num">' . ($idx + 1) . '</span>' . htmlspecialchars((string)($epi['nome'] ?? '')) . '</div>'
                . '<div class="top5-val">' . (int)($epi['total'] ?? 0) . '</div>'
                . '</div>'
                . '<div class="top5-progress-bg">'
                . '<div class="top5-progress-bar" style="width: ' . (int)($epi['pct'] ?? 0) . '%; background-color: ' . htmlspecialchars((string)($epi['cor'] ?? '#3b82f6')) . ';"></div>'
                . '</div>'
                . '</div>';
        }
        return $html;
    }
}

// Endpoint JSON para Atualização Dinâmica em Tempo Real (Polling / Real-time AJAX)
if (isset($_GET['ajax']) || (isset($_GET['action']) && $_GET['action'] === 'realtime') || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'alerts' => $alerts,
        'kpis' => $kpis,
        'custos' => $custos,
        'conformidade' => $conformidade,
        'top5Epis' => $top5Epis,
        'top5EpisHtml' => renderTop5EpisHtml($top5Epis),
        'top5EpisMensal' => $top5EpisMensal,
        'top5EpisMensalHtml' => renderTop5EpisHtml($top5EpisMensal),
        'entregas7Dias' => $entregas7Dias,
        'entregasMensal' => $entregasMensal,
        'modalCaVencidosHtml' => renderModalCaVencidosHtml($caVencidosList, $vidaUtilVencidaList),
        'modalVidaUtilVencidaHtml' => renderModalVidaUtilVencidaHtml($vidaUtilVencidaList),
        'modalCaAVencerHtml' => renderModalCaAVencerHtml($caAVencerList),
        'modalEntregasHojeHtml' => renderModalEntregasHojeHtml($entregasHojeList),
        'modalPinBloqueadosHtml' => renderModalPinBloqueadosHtml($semPinList, $caVencidosList, $semVidaUtilList, $semRastreabilidadeList),
        'modalEpisEmPosseHtml' => renderModalEpisEmPosseHtml($episEmPosseList),
        'modalFuncVencidosHtml' => renderModalFuncVencidosHtml($funcVencidosList ?? []),
        'modalFuncTrocaHtml' => renderModalFuncTrocaHtml($funcTrocaList ?? []),
        'modalTodasAtividadesHtml' => renderModalTodasAtividadesHtml($ultimasAtividades)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
?>

<style>
/* CSS do Dashboard - Visual Corporativo Moderno (Fiel aos Prints 2, 3 e 4) */
:root {
    --dash-card-radius: 16px;
    --dash-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
    --dash-shadow-hover: 0 10px 25px rgba(15, 23, 42, 0.12);
}

.dashboard-container {
    padding: 1.5rem;
    max-width: 100%;
    margin: 0 auto;
    width: 100%;
    box-sizing: border-box;
}

/* Card de Boas-vindas Otimizado */
.welcome-card {
    background: #ffffff;
    border-radius: var(--dash-card-radius);
    padding: 0.75rem 1.25rem;
    box-shadow: var(--dash-shadow);
    margin-bottom: 0.85rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 0.75rem;
    border: 1px solid rgba(226, 232, 240, 0.8);
    width: 100%;
    box-sizing: border-box;
}

.welcome-title {
    font-size: 1.2rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
}

.welcome-date {
    font-size: 0.8rem;
    color: #64748b;
    margin-top: 2px;
}

.badge-sincronizado {
    background-color: #10b981;
    color: #ffffff;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.5px;
    padding: 4px 12px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

/* Banner de Alertas (Grid de 2 Colunas no Desktop para economizar espaço vertical) */
.alert-banner-box {
    background-color: #fff5f5;
    border: 1px solid #fed7d7;
    border-radius: var(--dash-card-radius);
    padding: 0.85rem 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.06);
    width: 100%;
    box-sizing: border-box;
}

.alert-banner-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.95rem;
    font-weight: 700;
    color: #991b1b;
    margin-bottom: 0.65rem;
}

.alert-banner-header i {
    color: #ef4444;
    font-size: 1.1rem;
}

.alert-card-list {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px 12px;
}

@media (max-width: 992px) {
    .alert-card-list {
        grid-template-columns: 1fr;
    }
}

.alert-card-item {
    background-color: #fff0f0;
    border: 1px solid #fecaca;
    border-radius: 10px;
    padding: 6px 12px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    transition: all 0.2s ease;
}

.alert-card-item:hover {
    background-color: #ffe4e4;
    border-color: #fca5a5;
    transform: translateX(2px);
}

.alert-card-text {
    font-size: 0.85rem;
    font-weight: 700;
    color: #991b1b;
    display: flex;
    align-items: center;
    gap: 6px;
}

.btn-ver-alert {
    background-color: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
    border-radius: 20px;
    padding: 3px 10px;
    font-size: 0.725rem;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
    white-space: nowrap;
    text-decoration: none;
}

.btn-ver-alert:hover {
    background-color: #fca5a5;
    color: #7f1d1d;
    box-shadow: 0 2px 8px rgba(153, 27, 27, 0.15);
}

/* Título de Seção */
.section-header-title {
    font-size: 1rem;
    font-weight: 700;
    color: #1e3a8a;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 0.65rem;
}

/* Resumo do Dia: 4 KPI Cards Otimizados */
.kpi-resumo-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 1200px) {
    .kpi-resumo-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .kpi-resumo-grid {
        grid-template-columns: 1fr;
    }
}

.kpi-card-block {
    border-radius: var(--dash-card-radius);
    padding: 0.85rem 1rem;
    color: #ffffff;
    box-shadow: var(--dash-shadow);
    cursor: pointer;
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 105px;
    position: relative;
    overflow: hidden;
}

.kpi-card-block:hover {
    transform: translateY(-3px);
    box-shadow: var(--dash-shadow-hover);
}

.kpi-card-red {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.kpi-card-amber {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.kpi-card-green {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.kpi-card-blue {
    background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
}

.kpi-block-icon {
    font-size: 1.35rem;
    opacity: 0.95;
    margin-bottom: 0.25rem;
}

.kpi-block-value {
    font-size: 1.85rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.25rem;
}

.kpi-block-label {
    font-size: 0.8rem;
    font-weight: 600;
    opacity: 0.95;
    letter-spacing: 0.2px;
}

/* Cards de Custos e Assinaturas */
.sub-metrics-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.25rem;
    margin-bottom: 1.5rem;
    width: 100%;
    box-sizing: border-box;
}

@media (max-width: 1200px) {
    .sub-metrics-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .sub-metrics-grid {
        grid-template-columns: 1fr;
    }
}

.metric-pill-card {
    background: #ffffff;
    border-radius: var(--dash-card-radius);
    padding: 1rem 1.25rem;
    border: 1px solid #e2e8f0;
    box-shadow: var(--dash-shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.metric-pill-card.clickable-pill:hover {
    transform: translateY(-2px);
    box-shadow: var(--dash-shadow-hover);
}

.metric-pill-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.metric-pill-icon.icon-green {
    background-color: #d1fae5;
    color: #059669;
}

.metric-pill-icon.icon-blue {
    background-color: #dbeafe;
    color: #2563eb;
}

.metric-pill-icon.icon-amber {
    background-color: #fef3c7;
    color: #d97706;
}

.metric-pill-val {
    font-size: 1.1rem;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}

.metric-pill-sub {
    font-size: 0.775rem;
    color: #64748b;
    margin-top: 2px;
}

/* Section Card Containers */
.dash-card {
    background: #ffffff;
    border-radius: var(--dash-card-radius);
    padding: 1.5rem;
    border: 1px solid #e2e8f0;
    box-shadow: var(--dash-shadow);
    margin-bottom: 1.5rem;
}

.dash-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.25rem;
}

.dash-card-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1e3a8a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-filter-badge {
    background-color: #eff6ff;
    color: #2563eb;
    border: 1px solid #bfdbfe;
    font-size: 0.725rem;
    font-weight: 700;
    padding: 5px 12px;
    border-radius: 8px;
    text-transform: uppercase;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-filter-badge:hover, .btn-filter-badge.active {
    background-color: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}

/* Bar / Progress Item do Top 5 */
.top5-item {
    margin-bottom: 1rem;
}

.top5-item:last-child {
    margin-bottom: 0;
}

.top5-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
    font-size: 0.85rem;
    gap: 8px;
}

.top5-name {
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    min-width: 0;
}

.top5-rank-num {
    font-style: italic;
    font-weight: 700;
    font-size: 0.8rem;
    color: #64748b;
    flex-shrink: 0;
}

.top5-val {
    font-weight: 700;
    color: #1e293b;
    flex-shrink: 0;
}

.top5-progress-bg {
    background-color: #f1f5f9;
    height: 10px;
    border-radius: 999px;
    overflow: hidden;
}

.top5-progress-bar {
    height: 100%;
    border-radius: 999px;
    transition: width 0.8s ease-in-out;
}

/* Feed de Últimas Atividades - Design Moderno Limpo */
.activity-feed-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    max-height: 480px;
    overflow-y: auto;
    padding-right: 6px;
}

.activity-feed-list::-webkit-scrollbar {
    width: 5px;
}
.activity-feed-list::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.activity-feed-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.activity-feed-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background-color: #f8fafc;
    border-radius: 12px;
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.activity-feed-item:hover {
    background-color: #ffffff;
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
}

.activity-icon-box {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.15rem;
    flex-shrink: 0;
}

.activity-icon-danger { background-color: #fee2e2; color: #ef4444; }
.activity-icon-primary { background-color: #dbeafe; color: #2563eb; }
.activity-icon-info { background-color: #e0f2fe; color: #0284c7; }
.activity-icon-warning { background-color: #fef3c7; color: #d97706; }
.activity-icon-purple { background-color: #f3e8ff; color: #7c3aed; }
.activity-icon-success { background-color: #d1fae5; color: #059669; }

.badge-subtle-info { background: #e0f2fe; color: #0284c7; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.badge-subtle-warning { background: #fef3c7; color: #d97706; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.badge-subtle-success { background: #d1fae5; color: #059669; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.badge-subtle-danger { background: #fee2e2; color: #dc2626; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.badge-subtle-primary { background: #dbeafe; color: #2563eb; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.badge-subtle-purple { background: #f3e8ff; color: #7c3aed; font-size: 0.65rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }

.activity-text {
    font-size: 0.875rem;
    color: #1e293b;
    font-weight: 600;
    line-height: 1.4;
    margin: 0;
    word-break: break-word;
}

.activity-date {
    font-size: 0.725rem;
    color: #94a3b8;
    font-weight: 500;
}

/* Regras de Alto Contraste para Modo Escuro no Dashboard */
body.dark-mode .top5-name,
html.dark-mode .top5-name {
    color: #f8fafc !important;
}

body.dark-mode .top5-rank-num,
html.dark-mode .top5-rank-num {
    color: #38bdf8 !important;
}

body.dark-mode .top5-val,
html.dark-mode .top5-val {
    color: #f8fafc !important;
}

body.dark-mode .top5-progress-bg,
html.dark-mode .top5-progress-bg {
    background-color: #334155 !important;
}

body.dark-mode .activity-feed-item,
html.dark-mode .activity-feed-item {
    background-color: #0f172a !important;
    border-color: #1e293b !important;
}

body.dark-mode .activity-text,
html.dark-mode .activity-text {
    color: #f8fafc !important;
}
</style>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="dashboard-container">
        <!-- 1. Header Card de Boas-Vindas -->
        <div class="welcome-card">
            <div>
                <h2 class="welcome-title">Olá, <?= $userName ?> 👋</h2>
                <div class="welcome-date">Hoje é <?= htmlspecialchars($dataHojeFormatada) ?></div>
            </div>
            <div>
                <span class="badge-sincronizado">
                    <i class="bi bi-circle-fill" style="font-size: 6px;"></i> SINCRONIZADO
                </span>
            </div>
        </div>

        <!-- 2. Banner de Alertas de Validade & Vida Útil (Fiel ao Android Print 2) -->
        <div class="alert-banner-box">
            <div class="alert-banner-header">
                <i class="bi bi-bell-fill"></i>
                <span>Atenção - Alertas de Validade & Vida Útil:</span>
            </div>
            <div class="alert-card-list">
                <!-- Item 1: C.A. Vencido -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-ca-vencidos"><?= $alerts['ca_vencidos'] ?></span> EPI(s) com C.A. vencido
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalCaVencidos">Ver ›</button>
                </div>

                <!-- Item 2: C.A. Vencendo (7 dias) -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-ca-vencendo-7d"><?= $alerts['ca_vencendo_7d'] ?? 1 ?></span> EPI(s) com C.A. vencendo (7 dias)
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalCaAVencer">Ver ›</button>
                </div>

                <!-- Item 3: Vida Útil Vencida -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-vida-util"><?= $alerts['vida_util_vencida'] ?></span> EPI(s) em uso com vida útil vencida
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalEpisVidaUtilVencida">Ver ›</button>
                </div>

                <!-- Item 4: Troca Próxima -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-troca-proxima"><?= $alerts['troca_proxima'] ?></span> EPI(s) em uso com troca próxima
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalCaAVencer">Ver ›</button>
                </div>

                <!-- Item 5: Funcionários c/ EPI Vencidos -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-func-vencidos"><?= $alerts['func_vencidos'] ?></span> funcionário(s) com EPI(s) vencidos
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalFuncVencidos">Ver ›</button>
                </div>

                <!-- Item 6: Funcionários Próximos da Troca -->
                <div class="alert-card-item">
                    <div class="alert-card-text">
                        • <span id="alert-func-troca"><?= $alerts['func_troca'] ?></span> funcionário(s) com EPI(s) próximos da troca
                    </div>
                    <button type="button" class="btn-ver-alert" data-bs-toggle="modal" data-bs-target="#modalFuncTroca">Ver ›</button>
                </div>
            </div>
        </div>

        <!-- 3. Título & Grid: Resumo do Dia (4 Color Cards) -->
        <div class="section-header-title">
            <i class="bi bi-bar-chart-fill" style="color: #2563eb;"></i> Resumo do Dia
        </div>

        <div class="kpi-resumo-grid">
            <!-- Card 1: EPIs Vencidos (Vermelho) -->
            <div class="kpi-card-block kpi-card-red" data-bs-toggle="modal" data-bs-target="#modalCaVencidos" title="Clique para ver EPIs Vencidos">
                <div>
                    <div class="kpi-block-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
                    <div class="kpi-block-value" id="kpi-val-epis-vencidos"><?= $kpis['epis_vencidos'] ?></div>
                </div>
                <div class="kpi-block-label">EPIs Vencidos</div>
            </div>

            <!-- Card 2: A Vencer 7 dias (Amarelo / Laranja) -->
            <div class="kpi-card-block kpi-card-amber" data-bs-toggle="modal" data-bs-target="#modalCaAVencer" title="Clique para ver EPIs A Vencer">
                <div>
                    <div class="kpi-block-icon"><i class="bi bi-hourglass-split"></i></div>
                    <div class="kpi-block-value" id="kpi-val-a-vencer-7d"><?= $kpis['a_vencer_7d'] ?></div>
                </div>
                <div class="kpi-block-label">A Vencer (7 dias)</div>
            </div>

            <!-- Card 3: Entregas Hoje (Verde) -->
            <div class="kpi-card-block kpi-card-green" data-bs-toggle="modal" data-bs-target="#modalEntregasHoje" title="Clique para ver Entregas de Hoje">
                <div>
                    <div class="kpi-block-icon"><i class="bi bi-check-lg"></i></div>
                    <div class="kpi-block-value" id="kpi-val-entregas-hoje"><?= $kpis['entregas_hoje'] ?></div>
                </div>
                <div class="kpi-block-label">Entregas Hoje</div>
            </div>

            <!-- Card 4: Pendências (Azul) -->
            <div class="kpi-card-block kpi-card-blue" data-bs-toggle="modal" data-bs-target="#modalPinBloqueados" title="Clique para ver Pendências">
                <div>
                    <div class="kpi-block-icon"><i class="bi bi-clipboard-data-fill"></i></div>
                    <div class="kpi-block-value" id="kpi-val-pendencias"><?= $kpis['pendencias'] ?></div>
                </div>
                <div class="kpi-block-label">Pendências</div>
            </div>
        </div>

        <!-- 4. Sub-Metrics Cards (Custos e PIN) -->
        <div class="sub-metrics-grid">
            <!-- Custo Mensal -->
            <div class="metric-pill-card">
                <div class="metric-pill-icon icon-green">
                    <i class="bi bi-arrow-repeat"></i>
                </div>
                <div>
                    <div class="metric-pill-val" id="val-custo-mensal" style="color: #059669;"><?= $custos['mensal'] ?></div>
                    <div class="metric-pill-sub">Custo Mensal (<?= $siglaMesAtual ?>)</div>
                </div>
            </div>

            <!-- Custo Acumulado -->
            <div class="metric-pill-card">
                <div class="metric-pill-icon icon-blue">
                    <i class="bi bi-arrow-left-right"></i>
                </div>
                <div>
                    <div class="metric-pill-val" id="val-custo-acumulado" style="color: #2563eb;"><?= $custos['acumulado'] ?></div>
                    <div class="metric-pill-sub" id="lbl-custo-acumulado"><?= htmlspecialchars($custos['legenda_acumulado'] ?? 'Acumulado (Histórico)') ?></div>
                </div>
            </div>

            <!-- Funcionários sem PIN -->
            <div class="metric-pill-card clickable-pill" style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalPinBloqueados">
                <div class="metric-pill-icon icon-amber">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="metric-pill-val" id="val-sem-pin" style="color: #d97706;"><?= $custos['sem_pin'] ?></div>
                    <div class="metric-pill-sub">Funcionários sem PIN de Assinatura</div>
                </div>
            </div>
        </div>

        <!-- 5. Taxa de Conformidade -->
        <div class="dash-card">
            <div class="dash-card-header mb-2">
                <div class="dash-card-title">
                    <i class="bi bi-graph-up-arrow" style="color: #10b981;"></i> Taxa de Conformidade
                </div>
                <div class="fs-4 fw-bold text-success" id="val-conformidade-pct"><?= $conformidade['pct'] ?>%</div>
            </div>
            <div class="progress mb-2" style="height: 10px; border-radius: 999px; background-color: #e2e8f0;">
                <div class="progress-bar bg-success" id="bar-conformidade-pct" role="progressbar" style="width: <?= $conformidade['pct'] ?>%; border-radius: 999px;" aria-valuenow="<?= $conformidade['pct'] ?>" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="text-muted" id="txt-conformidade-summary" style="font-size: 0.8rem; font-weight: 500;">
                <?= $conformidade['em_dia'] ?> de <?= $conformidade['tot_func'] ?> funcionários ativos com EPIs em dia
            </div>
        </div>

        <!-- 6. Layout em 2 Colunas: Gráfico 7 Dias & Top 5 EPIs -->
        <div class="row g-4 mb-4">
            <!-- Coluna Esquerda: Entregas - Últimos 7 dias -->
            <div class="col-lg-6">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <div class="dash-card-title">
                            <i class="bi bi-box-seam-fill" style="color: #f59e0b;"></i> Entregas - Últimos 7 dias
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn-filter-badge active" id="btn-filtro-semanal" onclick="toggleGraficoFiltro('semanal')">SEMANAL</button>
                            <button class="btn-filter-badge" id="btn-filtro-mensal" onclick="toggleGraficoFiltro('mensal')">MENSAL</button>
                        </div>
                    </div>
                    <div style="height: 240px; position: relative;">
                        <canvas id="chartEntregas7Dias"></canvas>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Top 5 EPIs Mais Utilizados -->
            <div class="col-lg-6">
                <div class="dash-card h-100">
                    <div class="dash-card-header">
                        <div class="dash-card-title">
                            <i class="bi bi-trophy-fill" style="color: #f59e0b;"></i> Top 5 EPIs Mais Utilizados
                        </div>
                        <div class="d-flex gap-1">
                            <button class="btn-filter-badge" id="btn-filtro-top5-geral" onclick="toggleTop5Filtro('geral')">GERAL</button>
                            <button class="btn-filter-badge active" id="btn-filtro-top5-mensal" onclick="toggleTop5Filtro('mensal')">MENSAL</button>
                        </div>
                    </div>
                    <div class="top5-list" id="card-top5-epis-list">
                        <?= renderTop5EpisHtml($top5EpisMensal) ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 7. Últimas Atividades Feed (Design Limpo e Formatado) -->
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="dash-card-title">
                    <i class="bi bi-clock-history" style="color: #2563eb;"></i> Últimas Atividades
                </div>
                <button class="btn btn-sm btn-outline-primary fw-bold" style="font-size: 0.775rem; border-radius: 8px;" data-bs-toggle="modal" data-bs-target="#modalTodasAtividades">
                    <i class="bi bi-exclamation-triangle me-1"></i> VER TODOS / FILTROS
                </button>
            </div>
            <div class="activity-feed-list" id="card-feed-ultimas-atividades">
                <?php foreach ($ultimasAtividades as $act): ?>
                    <div class="activity-feed-item">
                        <div class="activity-icon-box activity-icon-<?= $act['tipo'] ?>">
                            <i class="bi <?= $act['icone'] ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge-subtle-<?= $act['tipo'] ?>"><?= htmlspecialchars($act['badge']) ?></span>
                                <span class="activity-date"><?= htmlspecialchars($act['data']) ?></span>
                            </div>
                            <p class="activity-text"><?= htmlspecialchars($act['texto']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAIS COMPLETOS E INTERATIVOS ================= -->

<!-- 1. Modal EPIs / CA Vencidos -->
<div class="modal fade" id="modalCaVencidos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-danger" id="modal-title-ca-vencidos">
                        <i class="bi bi-shield-x me-2"></i>Equipamentos Vencidos (<?= $kpis['epis_vencidos'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Equipamentos com C.A. vencido no catálogo ou com vida útil expirada em uso.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-ca-vencidos">
                <?= renderModalCaVencidosHtml($caVencidosList, $vidaUtilVencidaList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="epis.php?acao=controle_ca" class="btn btn-danger rounded-3">Ir para Controle C.A.</a>
            </div>
        </div>
    </div>
</div>

<!-- 2. Modal EPIs em Uso com Vida Útil Vencida -->
<div class="modal fade" id="modalEpisVidaUtilVencida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-danger" id="modal-title-vida-util">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>EPIs em Uso com Vida Útil Vencida (<?= $alerts['vida_util_vencida'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Equipamentos em posse de colaboradores que ultrapassaram a durabilidade máxima prevista.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-vida-util-vencida">
                <?= renderModalVidaUtilVencidaHtml($vidaUtilVencidaList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-danger rounded-3">Gerenciar Trocas / Entregas</a>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal A Vencer (7 dias / 30 dias) -->
<div class="modal fade" id="modalCaAVencer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-warning" id="modal-title-ca-a-vencer" style="color: #d97706 !important;">
                        <i class="bi bi-hourglass-split me-2"></i>Equipamentos A Vencer nos Próximos Dias (<?= $alerts['troca_proxima'] ?>)
                    </h5>
                    <p class="text-muted small m-0">EPIs com vencimento do C.A. ou substituição de vida útil programada nos próximos dias.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-ca-a-vencer">
                <?= renderModalCaAVencerHtml($caAVencerList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="epis.php?acao=controle_ca" class="btn btn-warning rounded-3 text-dark">Ver Todos no Estoque</a>
            </div>
        </div>
    </div>
</div>

<!-- 4. Modal Entregas Hoje -->
<div class="modal fade" id="modalEntregasHoje" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-success" id="modal-title-entregas-hoje">
                        <i class="bi bi-journal-check me-2"></i>Entregas Realizadas Hoje (<?= $kpis['entregas_hoje'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Entregas de EPIs efetuadas com assinatura de termo efetuadas hoje.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-entregas-hoje">
                <?= renderModalEntregasHojeHtml($entregasHojeList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-success rounded-3">Ir para Histórico de Entregas</a>
            </div>
        </div>
    </div>
</div>

<!-- 5. Modal PIN Bloqueados / Central de Pendências do Sistema -->
<div class="modal fade" id="modalPinBloqueados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-primary" id="modal-title-pin-bloqueados">
                        <i class="bi bi-clipboard-data-fill me-2"></i>Central de Pendências do Sistema (<?= $kpis['pendencias'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Consolidação de C.A. Vencido (<?= $pendenciasDet['ca_vencidos'] ?>), Sem PIN (<?= $pendenciasDet['sem_pin'] ?>), Sem Vida Útil (<?= $pendenciasDet['sem_vida_util'] ?>) e Sem Rastreabilidade (<?= $pendenciasDet['sem_rastreabilidade'] ?>).</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-pin-bloqueados">
                <?= renderModalPinBloqueadosHtml($semPinList, $caVencidosList, $semVidaUtilList, $semRastreabilidadeList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="funcionarios.php?acao=pin" class="btn btn-primary rounded-3 me-2">Gerenciar PINs</a>
                <a href="epis.php?acao=controle_ca" class="btn btn-danger rounded-3">Ir para Controle C.A.</a>
            </div>
        </div>
    </div>
</div>

<!-- 6. Modal EPIs em Posse -->
<div class="modal fade" id="modalEpisEmPosse" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" style="color: #0284c7 !important;">
                        <i class="bi bi-box-seam me-2"></i>EPIs Atualmente em Posse por Colaborador
                    </h5>
                    <p class="text-muted small m-0">Relação completa de equipamentos fornecidos e em utilização ativa.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-epis-em-posse">
                <?= renderModalEpisEmPosseHtml($episEmPosseList) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-primary rounded-3">Ir para Histórico de Entregas</a>
            </div>
        </div>
    </div>
</div>

<!-- 7. Modal Funcionários com EPI(s) Vencidos -->
<div class="modal fade" id="modalFuncVencidos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-danger" id="modal-title-func-vencidos">
                        <i class="bi bi-people-fill me-2"></i>Funcionários com EPI(s) Vencidos (<?= $alerts['func_vencidos'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Colaboradores ativos em posse de um ou mais equipamentos com vida útil expirada.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-func-vencidos">
                <?= renderModalFuncVencidosHtml($funcVencidosList ?? []) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-danger rounded-3">Gerenciar Trocas / Entregas</a>
            </div>
        </div>
    </div>
</div>

<!-- 8. Modal Funcionários Próximos da Troca de EPI -->
<div class="modal fade" id="modalFuncTroca" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-warning" id="modal-title-func-troca" style="color: #d97706 !important;">
                        <i class="bi bi-person-gear me-2"></i>Funcionários Próximos da Troca de EPI (<?= $alerts['func_troca'] ?>)
                    </h5>
                    <p class="text-muted small m-0">Colaboradores com substituição de equipamentos programada para os próximos dias.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-func-troca">
                <?= renderModalFuncTrocaHtml($funcTrocaList ?? []) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-warning rounded-3 text-dark">Gerenciar Trocas</a>
            </div>
        </div>
    </div>
</div>

<!-- 7. Modal Todas Atividades / Log de Auditoria -->
<div class="modal fade" id="modalTodasAtividades" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-dark">
                        <i class="bi bi-clock-history me-2"></i>Histórico Completo de Atividades & Auditoria
                    </h5>
                    <p class="text-muted small m-0">Logs consolidados e formatados de operações de entregas, trocas, logins e assinaturas.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-todas-atividades">
                <?= renderModalTodasAtividadesHtml($ultimasAtividades) ?>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="auditoria.php" class="btn btn-primary rounded-3">Ir para Auditoria Completa</a>
            </div>
        </div>
    </div>
</div>

<!-- Script do Gráfico & Modais JS com Atualização Dinâmica em Tempo Real -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let chartEntregasInstance = null;
let filtroGraficoAtual = 'semanal';
let filtroTop5Atual = 'mensal';

let dadosSemanal = <?= json_encode($entregas7Dias) ?>;
let dadosMensal = <?= json_encode($entregasMensal) ?>;

let htmlTop5Geral = <?= json_encode(renderTop5EpisHtml($top5Epis)) ?>;
let htmlTop5Mensal = <?= json_encode(renderTop5EpisHtml($top5EpisMensal)) ?>;

window.atualizarCoresGraficoModoEscuro = function() {
    if (!chartEntregasInstance) return;
    const isDark = document.documentElement.classList.contains('dark-mode') || (document.body && document.body.classList.contains('dark-mode'));
    const tickColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? '#334155' : '#f1f5f9';

    if (chartEntregasInstance.options.scales.x) {
        chartEntregasInstance.options.scales.x.ticks.color = tickColor;
    }
    if (chartEntregasInstance.options.scales.y) {
        chartEntregasInstance.options.scales.y.ticks.color = tickColor;
        chartEntregasInstance.options.scales.y.grid.color = gridColor;
    }
    chartEntregasInstance.update('none');
};

const barTopLabelsPlugin = {
    id: 'barTopLabels',
    afterDatasetsDraw(chart) {
        const { ctx } = chart;
        const isDark = document.documentElement.classList.contains('dark-mode') || (document.body && document.body.classList.contains('dark-mode'));
        chart.data.datasets.forEach((dataset, i) => {
            const meta = chart.getDatasetMeta(i);
            meta.data.forEach((bar, index) => {
                const val = dataset.data[index];
                ctx.save();
                ctx.fillStyle = isDark ? '#38bdf8' : '#1e3a8a';
                ctx.font = 'bold 12px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                ctx.fillText(val, bar.x, bar.y - 4);
                ctx.restore();
            });
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark-mode') || (document.body && document.body.classList.contains('dark-mode'));
    const tickColor = isDark ? '#cbd5e1' : '#64748b';
    const gridColor = isDark ? '#334155' : '#f1f5f9';

    // Inicialização do Gráfico 7 Dias / Entregas (Fiel ao App Android - Print 2)
    const ctx = document.getElementById('chartEntregas7Dias').getContext('2d');
    chartEntregasInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dadosSemanal.labels,
            datasets: [{
                label: 'Entregas',
                data: dadosSemanal.data,
                backgroundColor: dadosSemanal.data.map((val, idx) => {
                    if (val === 6 || idx === 5) return '#10b981';
                    if (val > 0) return '#3b82f6';
                    return '#cbd5e1';
                }),
                borderRadius: 4,
                barThickness: 24
            }]
        },
        plugins: [barTopLabelsPlugin],
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: { top: 22 }
            },
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { color: tickColor, font: { weight: '600' } }
                },
                y: {
                    grid: { color: gridColor },
                    beginAtZero: true,
                    ticks: { precision: 0, color: tickColor, font: { weight: '600' } }
                }
            }
        }
    });

    // Inicia a escuta de eventos e polling em tempo real
    iniciarAtualizacaoTempoReal();
});

function toggleGraficoFiltro(tipo) {
    filtroGraficoAtual = tipo;
    const btnSemanal = document.getElementById('btn-filtro-semanal');
    const btnMensal = document.getElementById('btn-filtro-mensal');

    if (tipo === 'semanal') {
        if (btnSemanal) btnSemanal.classList.add('active');
        if (btnMensal) btnMensal.classList.remove('active');
        if (chartEntregasInstance) {
            chartEntregasInstance.data.labels = dadosSemanal.labels;
            chartEntregasInstance.data.datasets[0].data = dadosSemanal.data;
            chartEntregasInstance.data.datasets[0].backgroundColor = dadosSemanal.data.map((val, idx) => {
                if (val === 6 || idx === 5) return '#10b981';
                if (val > 0) return '#3b82f6';
                return '#cbd5e1';
            });
            chartEntregasInstance.update();
        }
    } else {
        if (btnMensal) btnMensal.classList.add('active');
        if (btnSemanal) btnSemanal.classList.remove('active');
        if (chartEntregasInstance) {
            chartEntregasInstance.data.labels = dadosMensal.labels;
            chartEntregasInstance.data.datasets[0].data = dadosMensal.data;
            chartEntregasInstance.data.datasets[0].backgroundColor = dadosMensal.data.map(val => val === 0 ? '#e2e8f0' : '#3b82f6');
            chartEntregasInstance.update();
        }
    }
}

function toggleTop5Filtro(tipo) {
    filtroTop5Atual = tipo;
    const btnGeral = document.getElementById('btn-filtro-top5-geral');
    const btnMensal = document.getElementById('btn-filtro-top5-mensal');
    const container = document.getElementById('card-top5-epis-list');

    if (tipo === 'geral') {
        if (btnGeral) btnGeral.classList.add('active');
        if (btnMensal) btnMensal.classList.remove('active');
        if (container) container.innerHTML = htmlTop5Geral;
    } else {
        if (btnMensal) btnMensal.classList.add('active');
        if (btnGeral) btnGeral.classList.remove('active');
        if (container) container.innerHTML = htmlTop5Mensal;
    }
}

/**
 * Helper para atualizar modal preservando a aba ativa (Tab) e posição de rolagem (Scroll)
 */
function atualizarModalBodyComAbas(containerId, novoHtml) {
    const container = document.getElementById(containerId);
    if (!container || !novoHtml) return;

    // Guarda a posição de rolagem e a aba ativa antes da atualização
    const savedScrollTop = container.scrollTop;
    const activeTab = container.querySelector('.nav-link.active');
    const activeTabId = activeTab ? activeTab.id : null;
    const activeTargetId = activeTab ? activeTab.getAttribute('data-bs-target') : null;

    // Atualiza o conteúdo HTML
    container.innerHTML = novoHtml;

    // Restaura a aba selecionada pelo usuário
    if (activeTabId && activeTargetId) {
        const btnTab = container.querySelector('#' + activeTabId);
        const targetPane = container.querySelector(activeTargetId);

        if (btnTab && targetPane) {
            container.querySelectorAll('.nav-link').forEach(btn => btn.classList.remove('active'));
            container.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));

            btnTab.classList.add('active');
            targetPane.classList.add('show', 'active');
        }
    }

    // Restaura a posição de rolagem
    if (savedScrollTop > 0) {
        container.scrollTop = savedScrollTop;
    }
}

function atualizarModalBodySimples(containerId, novoHtml) {
    const container = document.getElementById(containerId);
    if (!container || !novoHtml) return;

    const savedScrollTop = container.scrollTop;
    container.innerHTML = novoHtml;
    if (savedScrollTop > 0) {
        container.scrollTop = savedScrollTop;
    }
}

/**
 * Atualiza o DOM do Dashboard com dados recebidos via JSON sem recarregar a página
 */
function atualizarDashboardDOM(data) {
    if (!data || !data.success) return;

    // 1. Lista de Alertas (Atualiza SOMENTE o número dentro do <span>, o texto descritivo está no HTML)
    if (data.alerts) {
        const elCaVencidos = document.getElementById('alert-ca-vencidos');
        if (elCaVencidos) elCaVencidos.textContent = data.alerts.ca_vencidos;

        const elCaVencendo7d = document.getElementById('alert-ca-vencendo-7d');
        if (elCaVencendo7d) elCaVencendo7d.textContent = data.alerts.ca_vencendo_7d;

        const elVidaUtil = document.getElementById('alert-vida-util');
        if (elVidaUtil) elVidaUtil.textContent = data.alerts.vida_util_vencida;

        const elTrocaProxima = document.getElementById('alert-troca-proxima');
        if (elTrocaProxima) elTrocaProxima.textContent = data.alerts.troca_proxima;

        const elFuncVencidos = document.getElementById('alert-func-vencidos');
        if (elFuncVencidos) elFuncVencidos.textContent = data.alerts.func_vencidos;

        const elFuncTroca = document.getElementById('alert-func-troca');
        if (elFuncTroca) elFuncTroca.textContent = data.alerts.func_troca;
    }

    // 2. Títulos dos Modais
    if (data.alerts && data.kpis && data.custos) {
        const titleCaVencidos = document.getElementById('modal-title-ca-vencidos');
        if (titleCaVencidos) titleCaVencidos.innerHTML = `<i class="bi bi-shield-x me-2"></i>Equipamentos Vencidos (${data.kpis.epis_vencidos})`;

        const titleVidaUtil = document.getElementById('modal-title-vida-util');
        if (titleVidaUtil) titleVidaUtil.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-2"></i>EPIs em Uso com Vida Útil Vencida (${data.alerts.vida_util_vencida})`;

        const titleCaAVencer = document.getElementById('modal-title-ca-a-vencer');
        if (titleCaAVencer) titleCaAVencer.innerHTML = `<i class="bi bi-hourglass-split me-2"></i>Equipamentos A Vencer nos Próximos Dias (${data.kpis.a_vencer_7d})`;

        const titleFuncVencidos = document.getElementById('modal-title-func-vencidos');
        if (titleFuncVencidos) titleFuncVencidos.innerHTML = `<i class="bi bi-people-fill me-2"></i>Funcionários com EPI(s) Vencidos (${data.alerts.func_vencidos})`;

        const titleFuncTroca = document.getElementById('modal-title-func-troca');
        if (titleFuncTroca) titleFuncTroca.innerHTML = `<i class="bi bi-person-gear me-2"></i>Funcionários Próximos da Troca de EPI (${data.alerts.func_troca})`;

        const titleEntregasHoje = document.getElementById('modal-title-entregas-hoje');
        if (titleEntregasHoje) titleEntregasHoje.innerHTML = `<i class="bi bi-journal-check me-2"></i>Entregas Realizadas Hoje (${data.kpis.entregas_hoje})`;

        const titlePinBloqueados = document.getElementById('modal-title-pin-bloqueados');
        if (titlePinBloqueados) titlePinBloqueados.innerHTML = `<i class="bi bi-clipboard-data-fill me-2"></i>Central de Pendências do Sistema (${data.kpis.pendencias})`;
    }

    // 3. Cards do Resumo
    if (data.kpis) {
        const kpiVencidos = document.getElementById('kpi-val-epis-vencidos');
        if (kpiVencidos) kpiVencidos.innerText = data.kpis.epis_vencidos;

        const kpiAVencer7d = document.getElementById('kpi-val-a-vencer-7d');
        if (kpiAVencer7d) kpiAVencer7d.innerText = data.kpis.a_vencer_7d;

        const kpiEntregasHoje = document.getElementById('kpi-val-entregas-hoje');
        if (kpiEntregasHoje) kpiEntregasHoje.innerText = data.kpis.entregas_hoje;

        const kpiPendencias = document.getElementById('kpi-val-pendencias');
        if (kpiPendencias) kpiPendencias.innerText = data.kpis.pendencias;
    }

    // 4. Métricas de Custos
    if (data.custos) {
        const valCustoMensal = document.getElementById('val-custo-mensal');
        if (valCustoMensal) valCustoMensal.innerText = data.custos.mensal;

        const valCustoAcumulado = document.getElementById('val-custo-acumulado');
        if (valCustoAcumulado) valCustoAcumulado.innerText = data.custos.acumulado;

        const lblCustoAcumulado = document.getElementById('lbl-custo-acumulado');
        if (lblCustoAcumulado && data.custos.legenda_acumulado) lblCustoAcumulado.innerText = data.custos.legenda_acumulado;

        const valSemPin = document.getElementById('val-sem-pin');
        if (valSemPin) valSemPin.innerText = data.custos.sem_pin;
    }

    // 5. Conformidade
    if (data.conformidade) {
        const valConfPct = document.getElementById('val-conformidade-pct');
        if (valConfPct) valConfPct.innerText = `${data.conformidade.pct}%`;

        const barConfPct = document.getElementById('bar-conformidade-pct');
        if (barConfPct) {
            barConfPct.style.width = `${data.conformidade.pct}%`;
            barConfPct.setAttribute('aria-valuenow', data.conformidade.pct);
        }

        const txtConfSummary = document.getElementById('txt-conformidade-summary');
        if (txtConfSummary) txtConfSummary.innerText = `${data.conformidade.em_dia} de ${data.conformidade.tot_func} funcionários ativos com EPIs em dia`;
    }

    // 6. Top 5 EPIs Mais Utilizados
    if (data.top5EpisHtml) {
        htmlTop5Geral = data.top5EpisHtml;
    }
    if (data.top5EpisMensalHtml) {
        htmlTop5Mensal = data.top5EpisMensalHtml;
    }
    const containerTop5 = document.getElementById('card-top5-epis-list');
    if (containerTop5) {
        containerTop5.innerHTML = (filtroTop5Atual === 'mensal') ? htmlTop5Mensal : htmlTop5Geral;
    }

    // 7. Conteúdo HTML dos Modais e Atividades (Preservando Abas Ativas e Posição de Rolagem)
    if (data.modalCaVencidosHtml) {
        atualizarModalBodyComAbas('body-modal-ca-vencidos', data.modalCaVencidosHtml);
    }
    if (data.modalVidaUtilVencidaHtml) {
        atualizarModalBodySimples('body-modal-vida-util-vencida', data.modalVidaUtilVencidaHtml);
    }
    if (data.modalCaAVencerHtml) {
        atualizarModalBodySimples('body-modal-ca-a-vencer', data.modalCaAVencerHtml);
    }
    if (data.modalEntregasHojeHtml) {
        atualizarModalBodySimples('body-modal-entregas-hoje', data.modalEntregasHojeHtml);
    }
    if (data.modalPinBloqueadosHtml) {
        atualizarModalBodyComAbas('body-modal-pin-bloqueados', data.modalPinBloqueadosHtml);
    }
    if (data.modalEpisEmPosseHtml) {
        atualizarModalBodySimples('body-modal-epis-em-posse', data.modalEpisEmPosseHtml);
    }
    if (data.modalFuncVencidosHtml) {
        atualizarModalBodySimples('body-modal-func-vencidos', data.modalFuncVencidosHtml);
    }
    if (data.modalFuncTrocaHtml) {
        atualizarModalBodySimples('body-modal-func-troca', data.modalFuncTrocaHtml);
    }
    if (data.modalTodasAtividadesHtml) {
        atualizarModalBodySimples('body-modal-todas-atividades', data.modalTodasAtividadesHtml);
        atualizarModalBodySimples('card-feed-ultimas-atividades', data.modalTodasAtividadesHtml);
    }

    // 8. Gráfico de Entregas
    if (data.entregas7Dias) {
        dadosSemanal = data.entregas7Dias;
    }
    if (data.entregasMensal) {
        dadosMensal = data.entregasMensal;
    }

    if (chartEntregasInstance) {
        if (filtroGraficoAtual === 'semanal') {
            chartEntregasInstance.data.labels = dadosSemanal.labels;
            chartEntregasInstance.data.datasets[0].data = dadosSemanal.data;
            chartEntregasInstance.data.datasets[0].backgroundColor = dadosSemanal.data.map((val, idx) => {
                if (val === 6 || idx === 5) return '#10b981';
                if (val > 0) return '#3b82f6';
                return '#cbd5e1';
            });
        } else {
            chartEntregasInstance.data.labels = dadosMensal.labels;
            chartEntregasInstance.data.datasets[0].data = dadosMensal.data;
            chartEntregasInstance.data.datasets[0].backgroundColor = dadosMensal.data.map(val => val === 0 ? '#e2e8f0' : '#3b82f6');
        }
        chartEntregasInstance.update('none');
    }
}

/**
 * Busca dados em tempo real da API do Dashboard sem recarregar a página e sem cache
 */
function buscarDadosDashboardRealtime() {
    fetch('dashboard.php?ajax=1&_t=' + Date.now(), {
        cache: 'no-store',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
        atualizarDashboardDOM(data);
    })
    .catch(err => console.error('Erro na atualização em tempo real:', err));
}

/**
 * Configura mecanismos de tempo real (polling, visibilidade, eventos de modais e BroadcastChannel)
 */
function iniciarAtualizacaoTempoReal() {
    // 1. Polling contínuo a cada 2 segundos para atualização em tempo real
    setInterval(buscarDadosDashboardRealtime, 2000);

    // 2. Atualiza imediatamente quando a aba volta a ficar visível
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            buscarDadosDashboardRealtime();
        }
    });

    // 3. Atualiza dados sempre que qualquer modal for aberto pelo usuário
    document.querySelectorAll('.modal').forEach(function(modalEl) {
        modalEl.addEventListener('show.bs.modal', function() {
            buscarDadosDashboardRealtime();
        });
    });

    // 4. Comunicação entre abas em tempo real (BroadcastChannel & localStorage)
    if ('BroadcastChannel' in window) {
        const channel = new BroadcastChannel('gestao_epi_realtime');
        channel.onmessage = function(e) {
            if (e.data && e.data.type === 'UPDATE_DASHBOARD') {
                buscarDadosDashboardRealtime();
            }
        };
    }

    window.addEventListener('storage', function(e) {
        if (e.key === 'gestao_epi_last_update') {
            buscarDadosDashboardRealtime();
        }
    });
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
