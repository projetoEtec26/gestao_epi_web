<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Auditoria de Logs de Sistema</title>
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

        /* Distribuição Proporcional das Colunas (Idêntica ao Android) */
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

<?php
// === SIMULAÇÃO DE DADOS DO BANCO DE DADOS ===
// (Seus colegas podem substituir esta lista pela query oficial do MySQL)
$dataEmissao = date('d/m/Y H:i:s');
$loginUsuario = 'admin'; // Substituir pelo usuário logado
$perfilUsuario = 'ADMINISTRADOR';
$idExportacao = 'AUD-' . date('Ymd-His') . '-' . sprintf('%04d', 3);
$filtrosAplicados = 'Funcionário=Ronaldo de Lima Diniz;';

$logs = [
    [
        'datahora' => '22/08/2026 12:57',
        'usuario' => 'almoxarife',
        'perfil' => 'ALMOXARIFE_OPERADOR',
        'acao' => 'DEVOLUÇÃO',
        'entidade' => 'Itens_Entrega',
        'reg_id' => '29',
        'descricao' => 'EPI de ID 12 devolvido pelo funcionário de ID 6. Status: DEVOLVIDO. Motivo: SUBSTITUICAO. Condição: EM_BOAS_CONDICOES.'
    ],
    [
        'datahora' => '20/08/2026 21:18',
        'usuario' => 'almoxarife',
        'perfil' => 'ALMOXARIFE_OPERADOR',
        'acao' => 'DEVOLUÇÃO',
        'entidade' => 'Itens_Entrega',
        'reg_id' => '41',
        'descricao' => 'EPI de ID 6 devolvido pelo funcionário de ID 6. Status: DEVOLVIDO. Motivo: SUBSTITUICAO.'
    ],
    [
        'datahora' => '15/08/2026 00:27',
        'usuario' => 'almoxarife',
        'perfil' => 'ALMOXARIFE_OPERADOR',
        'acao' => 'DEVOLUÇÃO',
        'entidade' => 'Itens_Entrega',
        'reg_id' => '30',
        'descricao' => 'EPI de ID 7 devolvido pelo funcionário de ID 6. Status: DEVOLVIDO. Motivo: SUBSTITUICAO.'
    ]
];
?>

<div class="report-header">
    <h1>Gestão EPI — Relatório de Auditoria de Logs de Sistema</h1>
    <div class="metadata"><b>Emissão:</b> <?php echo $dataEmissao; ?> | <b>Emitido por:</b> <?php echo $loginUsuario; ?> (<?php echo $perfilUsuario; ?>)</div>
    <div class="metadata"><b>Filtros aplicados:</b> <?php echo $filtrosAplicados; ?> | <b>Exportação ID:</b> <?php echo $idExportacao; ?></div>
    <div class="metadata"><b>Total de registros exportados:</b> <?php echo count($logs); ?> | <b>Modelo do relatório:</b> v2</div>
</div>

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
        <?php foreach ($logs as $log): ?>
        <tr>
            <td class="col-data"><?php echo htmlspecialchars($log['datahora']); ?></td>
            <td class="col-usuario"><?php echo htmlspecialchars($log['usuario']); ?></td>
            <td class="col-perfil"><?php echo htmlspecialchars($log['perfil']); ?></td>
            <td class="col-acao"><?php echo htmlspecialchars($log['acao']); ?></td>
            <td class="col-entidade"><?php echo htmlspecialchars($log['entidade']); ?></td>
            <td class="col-reg-id"><?php echo htmlspecialchars($log['reg_id']); ?></td>
            <td class="col-descricao"><?php echo htmlspecialchars($log['descricao']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="report-footer">
    Página 1 — Exportação: <?php echo $idExportacao; ?> — Documento gerado automaticamente pelo sistema em <?php echo $dataEmissao; ?>
</div>

</body>
</html>
