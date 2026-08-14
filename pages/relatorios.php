<?php
declare(strict_types=1);

$page_title = 'Relatórios e Conformidade';
$active_menu = 'relatorios';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'GESTOR']; // Almoxarife e RH não possuem acesso

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;

// Lista de Funcionários para o filtro do relatório de entregas
$funcionarios = [];
try {
    $funcRes = $api->get('funcionarios');
    if (isset($funcRes['success']) && $funcRes['success']) {
        $funcionarios = $funcRes['data'];
    }
} catch (\Throwable $e) {}

$podeVerCustos = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true);
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body no-print">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Relatórios Gerenciais</h3>
                <p class="text-muted">Gere relatórios de auditoria, custos consolidados, conformidade e vencimentos de Certificados de Aprovação (C.A.).</p>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna de Opções de Relatório -->
            <div class="col-lg-3">
                <div class="card-custom">
                    <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-text me-1 text-primary"></i>Tipo de Relatório</h6>
                    <div class="list-group" id="lista-tipos-relatorios">
                        <button type="button" class="list-group-item list-group-item-action py-3 active" onclick="mostrarPainelRelatorio('entregas')">
                            <i class="bi bi-journal-text me-2"></i>Entregas Gerais
                        </button>
                        <button type="button" class="list-group-item list-group-item-action py-3" onclick="mostrarPainelRelatorio('epis-vencidos')">
                            <i class="bi bi-clock-history me-2"></i>EPIs Vencidos em Posse
                        </button>
                        <button type="button" class="list-group-item list-group-item-action py-3" onclick="mostrarPainelRelatorio('ca-vencidos')">
                            <i class="bi bi-shield-exclamation me-2"></i>Vencimentos de C.A.
                        </button>
                        <?php if ($podeVerCustos): ?>
                            <button type="button" class="list-group-item list-group-item-action py-3" onclick="mostrarPainelRelatorio('custos')">
                                <i class="bi bi-currency-dollar me-2"></i>Relatório de Custos
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Coluna de Configuração e Resultados -->
            <div class="col-lg-9">
                <!-- Relatório 1: Entregas Gerais -->
                <div class="card-custom painel-relatorio" id="painel-entregas">
                    <h5 class="fw-bold mb-3 text-color-primary">Histórico Geral de Entregas</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label" style="font-size:12px;">Filtrar por Colaborador</label>
                            <select id="entregas-func-id" class="form-select">
                                <option value="">Todos os Funcionários</option>
                                <?php foreach ($funcionarios as $f): 
                                    $cpfMasc = $f['fun_cpf'];
                                    if (strlen($cpfMasc) === 11) {
                                        $cpfMasc = substr($cpfMasc, 0, 3) . '.***.***-' . substr($cpfMasc, 9, 2);
                                    }
                                ?>
                                    <option value="<?= $f['fun_id'] ?>"><?= htmlspecialchars($f['fun_nome']) ?> (CPF: <?= htmlspecialchars($cpfMasc) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-primary w-100" onclick="gerarRelatorioEntregas()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                        </div>
                    </div>
                </div>

                <!-- Relatório 2: EPIs Vencidos em Posse -->
                <div class="card-custom painel-relatorio d-none" id="painel-epis-vencidos">
                    <h5 class="fw-bold mb-3 text-color-primary">EPIs com Validade de Uso Expirada</h5>
                    <p class="text-muted" style="font-size: 13px;">Lista colaboradores que estão portando EPIs cujo prazo recomendado de uso/descarte recomendado pela NR-6 foi ultrapassado.</p>
                    <div class="d-grid gap-2 col-md-4 mb-4">
                        <button class="btn btn-primary" onclick="gerarRelatorioEpisVencidos()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                    </div>
                </div>

                <!-- Relatório 3: C.A. Vencidos -->
                <div class="card-custom painel-relatorio d-none" id="painel-ca-vencidos">
                    <h5 class="fw-bold mb-3 text-color-primary">EPIs com C.A. Vencido no Catálogo</h5>
                    <p class="text-muted" style="font-size: 13px;">Identifica equipamentos de proteção cuja validade do Certificado de Aprovação (C.A.) no Ministério do Trabalho expirou, impossibilitando novos fornecimentos.</p>
                    <div class="d-grid gap-2 col-md-4 mb-4">
                        <button class="btn btn-primary" onclick="gerarRelatorioCaVencidos()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                    </div>
                </div>

                <!-- Relatório 4: Custos Consolidados -->
                <div class="card-custom painel-relatorio d-none" id="painel-custos">
                    <h5 class="fw-bold mb-3 text-color-primary">Demonstrativo Mensal de Custos</h5>
                    <p class="text-muted" style="font-size: 13px;">Consolidação financeira detalhada de EPIs homologados por centro de custos, departamentos e valores médios mensais.</p>
                    <div class="d-grid gap-2 col-md-4 mb-4">
                        <button class="btn btn-primary" onclick="gerarRelatorioCustos()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                    </div>
                </div>

                <!-- Painel de Resultados Comum -->
                <div class="card-custom mt-4 d-none" id="bloco-resultados">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0" id="titulo-resultados">Resultados do Relatório</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-outline-success" onclick="exportarCSV()"><i class="bi bi-file-earmark-excel me-1"></i> CSV / Excel</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="imprimirRelatorio()"><i class="bi bi-printer me-1"></i> Imprimir PDF</button>
                        </div>
                    </div>
                    
                    <div class="table-responsive" style="max-height: 500px;" id="tabela-resultados-wrapper">
                        <!-- Gerado Dinamicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= ESTRUTURA PARA IMPRESSÃO (MIDIA PRINT) ================= -->
<div class="d-none print-only" id="area-impressao-relatorio">
    <div style="font-family: sans-serif; padding: 20px;">
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
            <div>
                <h4 class="fw-bold m-0" style="color: #305BD3;">GESTAO_EPI — Relatório Corporativo</h4>
                <small class="text-muted" id="print-data-emissao"></small>
            </div>
            <div class="text-end">
                <span class="fw-bold" id="print-titulo-relatorio">Relatório</span>
            </div>
        </div>
        <div id="print-tabela-conteudo"></div>
        <div class="border-top pt-3 mt-4 text-center text-muted" style="font-size: 11px;">
            Documento emitido digitalmente para fins de auditoria interna e conformidade jurídica (NR-6 / eSocial).
        </div>
    </div>
</div>

<style>
/* CSS para controlar visualização exclusiva de impressão */
@media print {
    .no-print, #sidebar, #topbar {
        display: none !important;
    }
    .print-only {
        display: block !important;
    }
    #main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
}
</style>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';

let relatorioAtivo = 'entregas';
let dadosAtivos = []; // Cache dos dados carregados
let colunasAtivas = []; // Nomes das colunas para exportação
let filtrosAtivosString = '';
let nomeFuncionarioFiltrado = ''; // Nome do colaborador quando filtrado individualmente

document.addEventListener('DOMContentLoaded', function() {
    // Inicialização comum
});

/**
 * Controla a alternância de abas/paineis
 */
function mostrarPainelRelatorio(tipo) {
    relatorioAtivo = tipo;
    
    // Altera active na lista lateral
    document.querySelectorAll('#lista-tipos-relatorios button').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    // Esconde todos os painéis e exibe o correto
    document.querySelectorAll('.painel-relatorio').forEach(p => p.classList.add('d-none'));
    document.getElementById(`painel-${tipo}`).classList.remove('d-none');

    // Oculta resultados anteriores
    document.getElementById('bloco-resultados').classList.add('d-none');
}

/**
 * RELATÓRIO 1: Histórico Geral de Entregas
 */
function gerarRelatorioEntregas() {
    const funcId = document.getElementById('entregas-func-id').value;
    let endpoint = 'relatorios/entregas';
    filtrosAtivosString = 'Filtro: Todos os funcionários';
    
    if (funcId !== '') {
        endpoint = `relatorios/entregas/funcionario/${funcId}`;
        const s = document.getElementById('entregas-func-id');
        filtrosAtivosString = `Filtro: ${s.options[s.selectedIndex].text}`;
    }

    exibirLoading();

    fetch(`${PROXY_URL}?route=${endpoint}`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            let listaEntregas = [];
            if (Array.isArray(res.data)) {
                listaEntregas = res.data;
                nomeFuncionarioFiltrado = '';
            } else if (res.data && Array.isArray(res.data.entregas)) {
                listaEntregas = res.data.entregas;
                nomeFuncionarioFiltrado = res.data.funcionario ? res.data.funcionario.fun_nome : '';
            }

            dadosAtivos = listaEntregas;
            colunasAtivas = ['Data', 'Colaborador', 'EPI', 'C.A.', 'Quantidade', 'Motivo', 'Responsável'];
            
            let html = `
                <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Colaborador</th>
                            <th>EPI</th>
                            <th>C.A.</th>
                            <th>Qtd</th>
                            <th>Motivo</th>
                            <th>Responsável</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            listaEntregas.forEach(row => {
                const dataFormat = new Date(row.entr_data_entrega).toLocaleDateString('pt-BR');
                const itens = row.itens || [];
                itens.forEach(item => {
                    html += `
                        <tr>
                            <td>${dataFormat}</td>
                            <td class="fw-semibold">${row.fun_nome || nomeFuncionarioFiltrado || 'Não Informado'}</td>
                            <td class="fw-semibold">${item.item_epi_nome_snapshot || item.epi_nome || 'EPI'}</td>
                            <td>${item.item_epi_ca_snapshot || item.epi_ca || '---'}</td>
                            <td>${item.item_quantidade || 1}</td>
                            <td><span class="badge bg-light text-dark border">${row.entr_motivo}</span></td>
                            <td class="text-muted">${row.usu_login}</td>
                        </tr>
                    `;
                });
            });

            html += '</tbody></table>';
            renderizarResultados('Relatório Geral de Entregas', html);
        } else {
            exibirErro(res.message || 'Sem dados para exibir.');
        }
    })
    .catch(() => exibirErro('Erro na chamada ao servidor.'));
}

/**
 * RELATÓRIO 2: EPIs Vencidos em Posse
 */
function gerarRelatorioEpisVencidos() {
    filtrosAtivosString = 'Filtro: EPIs vencidos em posse dos colaboradores';
    exibirLoading();

    fetch(`${PROXY_URL}?route=relatorios/epis-vencidos`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            dadosAtivos = res.data;
            colunasAtivas = ['EPI', 'Fabricante', 'C.A.', 'Vencimento C.A.', 'Vida Útil Recomendada', 'Status'];
            
            let html = `
                <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>EPI</th>
                            <th>Fabricante</th>
                            <th>C.A.</th>
                            <th>Vencimento do C.A.</th>
                            <th>Vida Útil Recomendada</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            res.data.forEach(row => {
                const dataFormat = new Date(row.epi_vencimento_ca).toLocaleDateString('pt-BR');
                html += `
                    <tr>
                        <td class="fw-semibold">${row.epi_nome}</td>
                        <td>${row.epi_fabricante || '---'}</td>
                        <td class="fw-bold">${row.epi_ca || 'Isento'}</td>
                        <td class="text-danger fw-semibold">${dataFormat}</td>
                        <td>${row.epi_validade_uso_dias || 0} dias</td>
                        <td><span class="status-badge vencido">${row.epi_status}</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            renderizarResultados('EPIs com Validade de Uso Expirada', html);
        } else {
            exibirErro(res.message || 'Nenhum EPI vencido em posse no momento.');
        }
    })
    .catch(() => exibirErro('Erro ao processar chamada.'));
}

/**
 * RELATÓRIO 3: C.A. Vencidos
 */
function gerarRelatorioCaVencidos() {
    filtrosAtivosString = 'Filtro: EPIs com Certificado de Aprovação vencidos';
    exibirLoading();

    fetch(`${PROXY_URL}?route=relatorios/ca-vencidos`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            dadosAtivos = res.data;
            colunasAtivas = ['EPI', 'Fabricante', 'C.A. Número', 'Vencimento C.A.', 'Preço Vigente', 'Situação'];
            
            let html = `
                <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>EPI</th>
                            <th>Fabricante</th>
                            <th>C.A. Número</th>
                            <th>Vencimento do C.A.</th>
                            <th>Preço Vigente</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            res.data.forEach(row => {
                const dataFormat = new Date(row.epi_vencimento_ca).toLocaleDateString('pt-BR');
                const valorFloat = row.epi_valor ? parseFloat(row.epi_valor) : null;
                const valorFormatado = (valorFloat !== null && !isNaN(valorFloat)) ? valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) : '---';
                html += `
                    <tr>
                        <td class="fw-semibold">${row.epi_nome}</td>
                        <td>${row.epi_fabricante}</td>
                        <td class="fw-bold">${row.epi_ca}</td>
                        <td class="text-danger fw-semibold">${dataFormat}</td>
                        <td>${valorFormatado}</td>
                        <td><span class="status-badge vencido">${row.epi_status}</span></td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            renderizarResultados('EPIs com C.A. Vencido no Catálogo', html);
        } else {
            exibirErro(res.message || 'Nenhum Certificado de Aprovação vencido.');
        }
    })
    .catch(() => exibirErro('Erro na chamada.'));
}

/**
 * RELATÓRIO 4: Custos Consolidados
 */
function gerarRelatorioCustos() {
    filtrosAtivosString = 'Filtro: Demonstrativo mensal de custos corporativos';
    exibirLoading();

    fetch(`${PROXY_URL}?route=relatorios/custo-mensal`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            dadosAtivos = res.data;
            colunasAtivas = ['Ano / Mês', 'Total Unidades Fornecidas', 'Custo Total'];
            
            let html = `
                <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Ano / Mês</th>
                            <th class="text-center">Quantidade Entregue</th>
                            <th class="text-end">Valor Total de Consumo</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            res.data.forEach(row => {
                const custoTotal = parseFloat(row.custo_total);
                const valorFloat = isNaN(custoTotal) ? 0 : custoTotal;
                html += `
                    <tr>
                        <td class="fw-bold">${row.mes}</td>
                        <td class="text-center">${row.total_itens_entregues}</td>
                        <td class="text-end fw-bold text-success">${valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</td>
                    </tr>
                `;
            });

            html += '</tbody></table>';
            renderizarResultados('Demonstrativo Mensal de Custos', html);
        } else {
            exibirErro(res.message || 'Sem movimentações ou custos computados.');
        }
    })
    .catch(() => exibirErro('Erro na cotação financeira.'));
}

/* Helpers de renderização dos resultados na UI */
function exibirLoading() {
    document.getElementById('bloco-resultados').classList.remove('d-none');
    document.getElementById('titulo-resultados').innerText = 'Processando...';
    document.getElementById('tabela-resultados-wrapper').innerHTML = `
        <div class="d-flex align-items-center justify-content-center py-5 gap-2">
            <div class="spinner-border text-primary" role="status"></div>
            <span class="text-muted fw-medium">Carregando dados da API...</span>
        </div>
    `;
}

function exibirErro(msg) {
    document.getElementById('bloco-resultados').classList.remove('d-none');
    document.getElementById('titulo-resultados').innerText = 'Erro';
    document.getElementById('tabela-resultados-wrapper').innerHTML = `
        <div class="alert alert-warning m-0 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div>${msg}</div>
        </div>
    `;
}

function renderizarResultados(titulo, tabelaHtml) {
    document.getElementById('bloco-resultados').classList.remove('d-none');
    document.getElementById('titulo-resultados').innerText = titulo;
    document.getElementById('tabela-resultados-wrapper').innerHTML = tabelaHtml;
}

/**
 * EXPORTAR EXCEL/CSV: Constrói string CSV e gera download
 */
function exportarCSV() {
    if (dadosAtivos.length === 0) return;

    let csvContent = "data:text/csv;charset=utf-8,\uFEFF"; // BOM para acentuação no Excel BR
    
    // 1. Cabeçalho
    csvContent += colunasAtivas.join(";") + "\n";
    
    // 2. Linhas dependendo do tipo de relatório ativo
    dadosAtivos.forEach(row => {
        if (relatorioAtivo === 'entregas') {
            const itens = row.itens || [];
            itens.forEach(item => {
                const linha = [
                    new Date(row.entr_data_entrega).toLocaleDateString('pt-BR'),
                    row.fun_nome || nomeFuncionarioFiltrado || 'Não Informado',
                    item.item_epi_nome_snapshot || item.epi_nome || 'EPI',
                    item.item_epi_ca_snapshot || item.epi_ca || 'Isento',
                    item.item_quantidade || 1,
                    row.entr_motivo,
                    row.usu_login
                ];
                const linhaSanit = linha.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`);
                csvContent += linhaSanit.join(";") + "\n";
            });
        } else {
            let linha = [];
            if (relatorioAtivo === 'epis-vencidos') {
                linha = [
                    row.epi_nome,
                    row.epi_fabricante || '---',
                    row.epi_ca || 'Isento',
                    new Date(row.epi_vencimento_ca).toLocaleDateString('pt-BR'),
                    row.epi_validade_uso_dias || 0,
                    row.epi_status
                ];
            } else if (relatorioAtivo === 'ca-vencidos') {
                linha = [
                    row.epi_nome,
                    row.epi_fabricante,
                    row.epi_ca,
                    new Date(row.epi_vencimento_ca).toLocaleDateString('pt-BR'),
                    row.epi_valor ? row.epi_valor.toString().replace('.', ',') : '---',
                    row.epi_status
                ];
            } else if (relatorioAtivo === 'custos') {
                linha = [
                    row.mes,
                    row.total_itens_entregues,
                    (row.custo_total ?? 0).toString().replace('.', ',')
                ];
            }
            const linhaSanit = linha.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`);
            csvContent += linhaSanit.join(";") + "\n";
        }
    });

    // Dispara download
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `relatorio_${relatorioAtivo}_${Date.now()}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    // Grava log de auditoria
    registrarExportacaoAuditoria('CSV');
}

/**
 * IMPRIMIR PDF: Dispara a visualização de impressão nativa
 */
function imprimirRelatorio() {
    const titulo = document.getElementById('titulo-resultados').innerText;
    const tabela = document.getElementById('tabela-resultados-wrapper').innerHTML;
    
    document.getElementById('print-titulo-relatorio').innerText = titulo;
    document.getElementById('print-data-emissao').innerText = `Emitido em: ${new Date().toLocaleString('pt-BR')}`;
    document.getElementById('print-tabela-conteudo').innerHTML = tabela;

    // Dispara impressão nativa
    window.print();

    // Grava log de auditoria
    registrarExportacaoAuditoria('PDF');
}

/**
 * Registra exportação na API de auditoria
 */
function registrarExportacaoAuditoria(formato) {
    const payload = {
        quantidade: dadosAtivos.length,
        filtros: `${filtrosAtivosString} — Formato: ${formato}`
    };

    fetch(`${PROXY_URL}?route=logs/registrar-exportacao`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .catch(() => {}); // Silencia falhas secundárias de rede no registro
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
