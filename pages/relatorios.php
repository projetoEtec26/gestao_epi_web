<?php
declare(strict_types=1);

$page_title = 'Relatórios e Conformidade';
$active_menu = 'relatorios';
// RH_ADMINISTRATIVO possui acesso apenas ao Relatório Geral e ao relatório individual por colaborador
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'GESTOR', 'RH_ADMINISTRATIVO'];

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

// Lista de Usuários/Operadores para o filtro de auditoria
$usuariosOperadores = [];
try {
    $usuRes = $api->get('usuarios');
    if (isset($usuRes['success']) && $usuRes['success']) {
        $usuariosOperadores = $usuRes['data'];
    }
} catch (\Throwable $e) {}

$podeVerCustos = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true);
$podeVerEntregasGerais = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'GESTOR'], true);
$podeVerAuditoria = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR', 'TECNICO_SST'], true);

$tipoParam = $_GET['tipo'] ?? '';
$tipoInicial = 'geral';
if ($tipoParam === 'financeiro' || $tipoParam === 'custos') {
    $tipoInicial = 'custos';
} elseif ($tipoParam === 'epi' || $tipoParam === 'epis-vencidos') {
    $tipoInicial = 'epis-vencidos';
} elseif ($tipoParam === 'ca-vencidos') {
    $tipoInicial = 'ca-vencidos';
} elseif ($tipoParam === 'funcionario' || $tipoParam === 'entregas') {
    $tipoInicial = 'entregas';
} elseif ($tipoParam === 'geral') {
    $tipoInicial = 'geral';
} elseif ($tipoParam === 'auditoria') {
    $tipoInicial = 'auditoria';
} elseif ($podeVerEntregasGerais && empty($tipoParam)) {
    $tipoInicial = 'geral';
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body no-print">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Relatórios Gerenciais</h3>
                <p class="text-muted m-0">Gere relatórios de auditoria, custos consolidados, conformidade e vencimentos de Certificados de Aprovação (C.A.).</p>
            </div>

            <div class="btn-group-toggle-view" role="group" id="lista-tipos-relatorios">
                <button type="button" data-tipo="geral" data-painel="geral" class="btn btn-view <?= $tipoInicial === 'geral' ? 'active' : '' ?>" onclick="mostrarPainelRelatorio('geral', this)">
                    <i class="bi bi-clipboard-data me-1"></i> Rel. Geral EPIs
                </button>
                <?php if ($podeVerCustos): ?>
                    <button type="button" data-tipo="financeiro" data-painel="custos" class="btn btn-view <?= $tipoInicial === 'custos' ? 'active' : '' ?>" onclick="mostrarPainelRelatorio('custos', this)">
                        <i class="bi bi-currency-dollar me-1"></i> Rel. Financeiro
                    </button>
                <?php endif; ?>
                <?php if ($podeVerEntregasGerais): ?>
                    <button type="button" data-tipo="epi" data-painel="epis-vencidos" class="btn btn-view <?= $tipoInicial === 'epis-vencidos' ? 'active' : '' ?>" onclick="mostrarPainelRelatorio('epis-vencidos', this)">
                        <i class="bi bi-shield-check me-1"></i> Rel. EPI
                    </button>
                    <button type="button" data-tipo="funcionario" data-painel="entregas" class="btn btn-view <?= $tipoInicial === 'entregas' ? 'active' : '' ?>" onclick="mostrarPainelRelatorio('entregas', this)">
                        <i class="bi bi-person-badge me-1"></i> Rel. Funcionário
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <!-- Coluna Principal de Resultados (100% de Largura) -->
            <div class="col-12">
                <!-- Relatório 0: Relatório Geral de Fornecimento de EPIs -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'geral' ? '' : 'd-none' ?>" id="painel-geral">
                    <h5 class="fw-bold mb-3 text-color-primary">Relatório Geral de Fornecimento de EPIs</h5>
                    <p class="text-muted" style="font-size: 13px;">Consolidação gerencial de todos os fornecimentos de EPIs realizados no período, com indicadores, agrupamentos e registros detalhados.</p>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Data Início *</label>
                            <input type="date" id="geral-data-inicio" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Data Fim *</label>
                            <input type="date" id="geral-data-fim" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Funcionário</label>
                            <input type="hidden" id="geral-funcionario-id" value="">
                            <div class="position-relative" id="wrapper-busca-rel-geral">
                                <div id="srch-rel-geral-border" style="
                                    display:flex;align-items:center;gap:6px;
                                    background:#fff;border:1.5px solid #d0d5dd;
                                    border-radius:8px;padding:0 10px;
                                    height:38px;
                                    transition:border-color .2s,box-shadow .2s;">
                                    <i class="bi bi-search" style="color:#3b82f6;font-size:14px;flex-shrink:0;"></i>
                                    <input type="text"
                                           id="geral-funcionario"
                                           autocomplete="off"
                                           placeholder="Nome ou &quot;todos&quot;"
                                           style="border:none;outline:none;flex:1;padding:6px 0;font-size:13px;background:transparent;"
                                           oninput="buscarRelGeral(this.value)"
                                           onfocus="this.closest('[id=srch-rel-geral-border]').style.borderColor='#3b82f6';this.closest('[id=srch-rel-geral-border]').style.boxShadow='0 0 0 3px rgba(59,130,246,.15)';"
                                           onblur="setTimeout(()=>{this.closest('[id=srch-rel-geral-border]').style.borderColor='#d0d5dd';this.closest('[id=srch-rel-geral-border]').style.boxShadow='none';},150)"
                                           onkeydown="teclarRelGeral(event)">
                                    <button type="button" id="btn-limpar-rel-geral" title="Limpar"
                                            onclick="limparRelGeral()"
                                            style="display:none;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:16px;line-height:1;padding:0 2px;">&times;</button>
                                </div>
                                <div id="dropdown-rel-geral" style="
                                    display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
                                    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
                                    box-shadow:0 12px 32px -4px rgba(0,0,0,.18),0 2px 8px -2px rgba(0,0,0,.08);
                                    overflow:hidden;max-height:300px;overflow-y:auto;z-index:99999;"></div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Setor / Departamento</label>
                            <input type="text" id="geral-departamento" class="form-control" placeholder="Ex: Manutenção">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Cargo / Função</label>
                            <input type="text" id="geral-cargo" class="form-control" placeholder="Ex: Eletricista">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Motivo da Entrega</label>
                            <select id="geral-motivo" class="form-select">
                                <option value="">Todos os Motivos</option>
                                <option value="ADMISSAO">Admissão</option>
                                <option value="SUBSTITUICAO">Substituição</option>
                                <option value="VENCIMENTO">Vencimento</option>
                                <option value="PERDA">Perda</option>
                                <option value="DANO">Dano / Avaria</option>
                                <option value="TROCA_FUNCAO">Troca de Função</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Categoria do Item</label>
                            <select id="geral-categoria" class="form-select">
                                <option value="">Todas</option>
                                <option value="EPI_COM_CA">EPI com C.A.</option>
                                <option value="ITEM_SEGURANCA_SEM_CA">Item sem C.A.</option>
                                <option value="UNIFORME">Uniforme</option>
                                <option value="OUTRO">Outro</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size:12px;">Item com C.A.?</label>
                            <select id="geral-com-ca" class="form-select">
                                <option value="">Todos</option>
                                <option value="1">Com C.A.</option>
                                <option value="0">Sem C.A.</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2 align-items-end">
                            <button class="btn btn-primary w-100" onclick="carregarRelatorioGeral(1)" title="Consultar"><i class="bi bi-play-fill me-1"></i> Consultar</button>
                            <button class="btn btn-outline-primary" onclick="abrirModeloOficial('geral')" title="Abrir Modelo Oficial A4"><i class="bi bi-printer"></i></button>
                        </div>
                    </div>

                    <!-- Resumo Gerencial Consolidado -->
                    <div id="geral-resumo" class="d-none mt-4">
                        <h6 class="fw-bold mb-2"><i class="bi bi-graph-up-arrow me-1 text-primary"></i>Resumo Gerencial Consolidado</h6>
                        <div class="row g-3" id="geral-kpis"></div>
                    </div>
                </div>

                <!-- Relatório 1: Entregas Gerais (Rel. Funcionário) -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'entregas' ? '' : 'd-none' ?>" id="painel-entregas">
                    <h5 class="fw-bold mb-3 text-color-primary">Histórico Geral de Entregas</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-5">
                            <label class="form-label" style="font-size:12px;">Filtrar por Colaborador</label>
                            <input type="hidden" id="entregas-func-id" value="">
                            <div class="position-relative" id="wrapper-busca-rel-entregas">
                                <div id="srch-rel-entregas-border" style="
                                    display:flex;align-items:center;gap:8px;
                                    background:#fff;border:1.5px solid #d0d5dd;
                                    border-radius:10px;padding:0 12px;
                                    transition:border-color .2s,box-shadow .2s;">
                                    <i class="bi bi-search" style="color:#3b82f6;font-size:15px;flex-shrink:0;"></i>
                                    <input type="text"
                                           id="input-busca-rel-entregas"
                                           autocomplete="off"
                                           placeholder="Todos os Funcionários"
                                           style="border:none;outline:none;flex:1;padding:10px 0;font-size:14px;background:transparent;"
                                           oninput="buscarRelEntregas(this.value)"
                                           onfocus="this.closest('[id=srch-rel-entregas-border]').style.borderColor='#3b82f6';this.closest('[id=srch-rel-entregas-border]').style.boxShadow='0 0 0 3px rgba(59,130,246,.15)';"
                                           onblur="setTimeout(()=>{this.closest('[id=srch-rel-entregas-border]').style.borderColor='#d0d5dd';this.closest('[id=srch-rel-entregas-border]').style.boxShadow='none';},150)"
                                           onkeydown="teclarRelEntregas(event)">
                                    <button type="button" id="btn-limpar-rel-entregas" title="Limpar"
                                            onclick="limparRelEntregas()"
                                            style="display:none;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 2px;">&times;</button>
                                </div>
                                <div id="dropdown-rel-entregas" style="
                                    display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
                                    background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
                                    box-shadow:0 12px 32px -4px rgba(0,0,0,.18),0 2px 8px -2px rgba(0,0,0,.08);
                                    overflow:hidden;max-height:340px;overflow-y:auto;z-index:99999;"></div>
                            </div>
                        </div>
                        <div class="col-md-7 d-flex gap-2 align-items-end">
                            <button class="btn btn-primary w-100" onclick="gerarRelatorioEntregas()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                            <button class="btn btn-outline-primary text-nowrap" onclick="abrirModeloOficial('ficha')"><i class="bi bi-file-earmark-person me-1"></i> Ficha NR-06</button>
                        </div>
                    </div>
                </div>

                <!-- Relatório 2: EPIs Vencidos em Posse (Rel. EPI) -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'epis-vencidos' ? '' : 'd-none' ?>" id="painel-epis-vencidos">
                    <h5 class="fw-bold mb-3 text-color-primary">EPIs com Validade de Uso Expirada</h5>
                    <p class="text-muted" style="font-size: 13px;">Lista colaboradores que estão portando EPIs cujo prazo recomendado de uso/descarte recomendado pela NR-6 foi ultrapassado.</p>
                    <div class="d-flex gap-2 col-md-8 mb-4">
                        <button class="btn btn-primary" onclick="gerarRelatorioEpisVencidos()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                        <button class="btn btn-outline-primary" onclick="abrirModeloOficial('ca')"><i class="bi bi-printer me-1"></i> Imprimir Modelo Oficial</button>
                    </div>
                </div>

                <!-- Relatório 3: C.A. Vencidos -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'ca-vencidos' ? '' : 'd-none' ?>" id="painel-ca-vencidos">
                    <h5 class="fw-bold mb-3 text-color-primary">EPIs com C.A. Vencido no Catálogo</h5>
                    <p class="text-muted" style="font-size: 13px;">Identifica equipamentos de proteção cuja validade do Certificado de Aprovação (C.A.) no Ministério do Trabalho expirou, impossibilitando novos fornecimentos.</p>
                    <div class="d-flex gap-2 col-md-8 mb-4">
                        <button class="btn btn-primary" onclick="gerarRelatorioCaVencidos()"><i class="bi bi-play-fill me-1"></i> Carregar Relatório</button>
                        <button class="btn btn-outline-primary" onclick="abrirModeloOficial('ca')"><i class="bi bi-printer me-1"></i> Imprimir Modelo Oficial</button>
                    </div>
                </div>

                <!-- Relatório 4: Custos Consolidados (Rel. Financeiro) -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'custos' ? '' : 'd-none' ?>" id="painel-custos">
                    <h5 class="fw-bold mb-2 text-color-primary"><i class="bi bi-currency-dollar me-2 text-success"></i>Demonstrativo Financeiro e Custos com EPIs</h5>
                    <p class="text-muted" style="font-size: 13px;">Consolidação financeira detalhada de investimentos em EPIs por centro de custos, departamentos e valores médios com gráficos analíticos.</p>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Data Início *</label>
                            <input type="date" id="custos-data-inicio" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Data Fim *</label>
                            <input type="date" id="custos-data-fim" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Setor / Departamento</label>
                            <input type="text" id="custos-departamento" class="form-control" placeholder="Ex: Manutenção">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size:12px;">Funcionário / Colaborador</label>
                            <input type="text" id="custos-funcionario" class="form-control" placeholder="Ex: João Silva">
                        </div>
                        <div class="col-md-12 d-flex gap-2 justify-content-end mt-3">
                            <button class="btn btn-primary px-4" onclick="gerarRelatorioCustos()"><i class="bi bi-play-fill me-1"></i> Consultar Relatório Financeiro</button>
                            <button class="btn btn-outline-primary text-nowrap" onclick="abrirModeloOficial('custos')"><i class="bi bi-printer me-1"></i> Imprimir Demonstrativo A4</button>
                        </div>
                    </div>

                    <!-- Resumo Gerencial com KPIs e Gráficos Financeiros -->
                    <div id="custos-resumo-kpis" class="d-none mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-3"><i class="bi bi-calculator me-1 text-primary"></i>Resumo Financeiro do Período</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="card p-3 border-0 bg-light text-center">
                                    <small class="text-muted fw-bold text-uppercase" style="font-size:11px;">Custo Bruto Fornecido</small>
                                    <h4 class="fw-bold text-primary m-0 mt-1" id="kpi-custo-bruto">R$&nbsp;0,00</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card p-3 border-0 bg-light text-center">
                                    <small class="text-muted fw-bold text-uppercase" style="font-size:11px;">Estornos / Devoluções</small>
                                    <h4 class="fw-bold text-success m-0 mt-1" id="kpi-estornos">R$&nbsp;0,00</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card p-3 border-0 bg-light text-center">
                                    <small class="text-muted fw-bold text-uppercase" style="font-size:11px;">Descartes / Inservíveis</small>
                                    <h4 class="fw-bold text-danger m-0 mt-1" id="kpi-descartes">R$&nbsp;0,00</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card p-3 border-0 bg-light text-center">
                                    <small class="text-muted fw-bold text-uppercase" style="font-size:11px;">Custo Líquido Efetivo</small>
                                    <h4 class="fw-bold text-dark m-0 mt-1" id="kpi-custo-liquido">R$&nbsp;0,00</h4>
                                </div>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-1 text-primary"></i>Análise Gráfica de Custos</h6>
                        <div class="row g-3 mb-2">
                            <div class="col-md-6">
                                <div class="card border p-3">
                                    <h6 class="fw-bold text-muted mb-3" style="font-size: 13px;"><i class="bi bi-pie-chart me-1 text-primary"></i> Distribuição de Custos por Setor</h6>
                                    <div style="height: 240px; position: relative;">
                                        <canvas id="chartCustosSetor"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border p-3">
                                    <h6 class="fw-bold text-muted mb-3" style="font-size: 13px;"><i class="bi bi-bar-chart-line me-1 text-primary"></i> Top EPIs por Impacto Financeiro</h6>
                                    <div style="height: 240px; position: relative;">
                                        <canvas id="chartCustosEpis"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Relatório 5: Auditoria de Logs de Sistema (Modelo v2) -->
                <div class="card-custom painel-relatorio <?= $tipoInicial === 'auditoria' ? '' : 'd-none' ?>" id="painel-auditoria">
                    <h5 class="fw-bold mb-2 text-color-primary">Relatório de Auditoria de Logs de Sistema</h5>
                    <p class="text-muted" style="font-size: 13px;">Geração e exportação do relatório oficial de auditoria, rastreando operações, usuários, entidades e descrições detalhadas (Modelo v2 em A4 Paisagem).</p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px;">Operador / Usuário</label>
                            <select id="auditoria-usuario" class="form-select">
                                <option value="">Todos os Operadores</option>
                                <?php foreach ($usuariosOperadores as $op): ?>
                                    <option value="<?= htmlspecialchars($op['usu_login']) ?>"><?= htmlspecialchars($op['usu_login']) ?> (<?= htmlspecialchars($op['usu_perfil']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size: 12px;">Ação</label>
                            <select id="auditoria-acao" class="form-select">
                                <option value="">Todas</option>
                                <option value="LOGIN">Login</option>
                                <option value="CADASTRO">Cadastro</option>
                                <option value="ALTERAÇÃO">Alteração</option>
                                <option value="EXCLUSÃO">Exclusão</option>
                                <option value="DEVOLUÇÃO">Devolução</option>
                                <option value="BLOQUEIO">Bloqueio PIN</option>
                                <option value="DESBLOQUEIO">Desbloqueio PIN</option>
                                <option value="EXPORTAÇÃO">Exportação</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size: 12px;">Módulo / Entidade</label>
                            <select id="auditoria-entidade" class="form-select">
                                <option value="">Todos</option>
                                <option value="EPIs">EPIs</option>
                                <option value="Funcionarios">Funcionários</option>
                                <option value="Entrega_EPIs">Entregas</option>
                                <option value="Itens_Entrega">Itens / Posse</option>
                                <option value="Assinaturas_Eletronicas">Assinaturas PIN</option>
                                <option value="Usuarios">Usuários</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" style="font-size: 12px;">Data Início</label>
                            <input type="date" id="auditoria-data-inicio" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" style="font-size: 12px;">Data Fim</label>
                            <input type="date" id="auditoria-data-fim" class="form-control">
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <input type="text" id="auditoria-palavra-chave" class="form-control" placeholder="Buscar por palavra-chave na descrição da ocorrência...">
                        </div>
                        <div class="col-md-5 d-flex gap-2">
                            <button class="btn btn-primary w-100" onclick="gerarRelatorioAuditoria()"><i class="bi bi-search me-1"></i> Consultar</button>
                            <button class="btn btn-outline-primary w-100" onclick="abrirImpressaoAuditoriaV2()"><i class="bi bi-printer me-1"></i> Imprimir (v2)</button>
                        </div>
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

                    <!-- Agrupamentos exclusivos do Relatório Geral -->
                    <ul class="nav nav-pills mb-3 d-none" id="geral-abas-agrupamento">
                        <li class="nav-item"><button class="nav-link active py-1 px-3" style="font-size:12px;" type="button" onclick="mostrarAgrupamento('registros', this)">Registros Detalhados</button></li>
                        <li class="nav-item"><button class="nav-link py-1 px-3" style="font-size:12px;" type="button" onclick="mostrarAgrupamento('por_epi', this)">Por EPI</button></li>
                        <li class="nav-item"><button class="nav-link py-1 px-3" style="font-size:12px;" type="button" onclick="mostrarAgrupamento('por_setor', this)">Por Setor</button></li>
                        <li class="nav-item"><button class="nav-link py-1 px-3" style="font-size:12px;" type="button" onclick="mostrarAgrupamento('por_motivo', this)">Por Motivo</button></li>
                        <li class="nav-item"><button class="nav-link py-1 px-3" style="font-size:12px;" type="button" onclick="mostrarAgrupamento('por_funcionario', this)">Por Funcionário</button></li>
                    </ul>

                    <div class="table-responsive" style="max-height: 500px;" id="tabela-resultados-wrapper">
                        <!-- Gerado Dinamicamente -->
                    </div>

                    <!-- Paginação server-side do Relatório Geral -->
                    <div class="d-flex justify-content-between align-items-center mt-3 d-none" id="geral-paginacao">
                        <span class="text-muted" style="font-size: 13px;" id="geral-paginacao-info"></span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary" id="geral-btn-anterior" onclick="carregarRelatorioGeral(geralPaginaAtual - 1)"><i class="bi bi-chevron-left me-1"></i> Anterior</button>
                            <button class="btn btn-sm btn-outline-secondary" id="geral-btn-proxima" onclick="carregarRelatorioGeral(geralPaginaAtual + 1)">Próxima <i class="bi bi-chevron-right ms-1"></i></button>
                        </div>
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

@page {
    size: A4 landscape;
}
</style>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';

let relatorioAtivo = '<?= $tipoInicial ?>';
let dadosAtivos = []; // Cache dos dados carregados
let colunasAtivas = []; // Nomes das colunas para exportação
let filtrosAtivosString = '';
let nomeFuncionarioFiltrado = ''; // Nome do colaborador quando filtrado individualmente

// Estado do Relatório Geral de Fornecimento de EPIs
let geralPaginaAtual = 1;
let geralUltimaResposta = null;
const GERAL_LIMITE_PAGINA = 25;

/**
 * Controla a alternância de abas/paineis
 */
function mostrarPainelRelatorio(tipo, btn) {
    relatorioAtivo = tipo;

    // Altera active na lista lateral de tipos de relatórios
    document.querySelectorAll('#lista-tipos-relatorios button').forEach(b => b.classList.remove('active'));
    if (btn) {
        btn.classList.add('active');
    } else {
        const targetBtn = document.querySelector(`#lista-tipos-relatorios button[data-painel="${tipo}"], #lista-tipos-relatorios button[data-tipo="${tipo}"]`);
        if (targetBtn) targetBtn.classList.add('active');
    }

    // Esconde todos os painéis e exibe o correto
    document.querySelectorAll('.painel-relatorio').forEach(p => p.classList.add('d-none'));
    const targetPainel = document.getElementById(`painel-${tipo}`);
    if (targetPainel) targetPainel.classList.remove('d-none');

    // Oculta resultados anteriores
    const blocoRes = document.getElementById('bloco-resultados');
    if (blocoRes) blocoRes.classList.add('d-none');

    // Sincroniza o destaque ativo no submenu da sidebar
    const mapaPainelTipo = {
        'custos': 'financeiro',
        'epis-vencidos': 'epi',
        'entregas': 'funcionario',
        'geral': 'geral'
    };
    const tipoSub = mapaPainelTipo[tipo];
    if (tipoSub) {
        document.querySelectorAll('#sub-relatorios a').forEach(a => a.classList.remove('active-sub'));
        const subLink = document.querySelector(`#sub-relatorios a[href*="tipo=${tipoSub}"]`);
        if (subLink) subLink.classList.add('active-sub');
    }
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
        filtrosAtivosString = `Filtro: ${relEntregasNomeSelecionado || 'Funcionário ID '+funcId}`;
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

let chartInstanceSetor = null;
let chartInstanceEpis = null;

/**
 * RELATÓRIO 4: Custos Consolidados com Filtro de Datas e Gráficos
 */
function gerarRelatorioCustos() {
    const dataInicio = document.getElementById('custos-data-inicio')?.value || '';
    const dataFim = document.getElementById('custos-data-fim')?.value || '';
    const setor = document.getElementById('custos-departamento')?.value.trim() || '';
    const funcionario = document.getElementById('custos-funcionario')?.value.trim() || '';

    if (!dataInicio || !dataFim) {
        alert('Informe as datas de início e fim para a consulta financeira.');
        return;
    }
    if (new Date(dataInicio) > new Date(dataFim)) {
        alert('A data inicial não pode ser maior que a data final.');
        return;
    }

    const params = new URLSearchParams();
    params.append('data_inicial', `${dataInicio} 00:00:00`);
    params.append('data_final', `${dataFim} 23:59:59`);
    if (setor) params.append('departamento', setor);
    if (funcionario) params.append('funcionario', funcionario);

    filtrosAtivosString = `Filtro Financeiro: ${dataInicio} a ${dataFim}` + (setor ? `; Setor=${setor}` : '') + (funcionario ? `; Func=${funcionario}` : '');

    exibirLoading();

    fetch(`${PROXY_URL}?route=relatorios/epis/geral&${params.toString()}`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            const registros = res.data.registros || res.data.itens || (Array.isArray(res.data) ? res.data : []);
            dadosAtivos = registros;

            let custoBruto = 0;
            let estornos = 0;
            let descartes = 0;

            const setoresMap = {};
            const episMap = {};

            registros.forEach(r => {
                const val = parseFloat(r.valor_total || r.ite_custo_total || 0);
                const status = String(r.status || r.ite_status_item || r.entr_motivo || '').toUpperCase();
                const mot = String(r.item_motivo_entrega || r.entr_motivo || '').toUpperCase();
                const setNome = r.fun_departamento || r.setor || 'Geral';
                const epiNome = r.epi_nome || r.epi || 'EPI';

                custoBruto += val;
                if (status.includes('DEVOLVIDO') || mot.includes('DEVOLUCAO')) {
                    estornos += val;
                } else if (status.includes('DESCARTE') || status.includes('DANIFICADO') || mot.includes('DANO') || mot.includes('PERDA')) {
                    descartes += val;
                }

                setoresMap[setNome] = (setoresMap[setNome] || 0) + val;
                episMap[epiNome] = (episMap[epiNome] || 0) + val;
            });

            const custoLiquido = custoBruto - estornos;

            document.getElementById('kpi-custo-bruto').innerText = custoBruto.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0');
            document.getElementById('kpi-estornos').innerText = estornos.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0');
            document.getElementById('kpi-descartes').innerText = descartes.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0');
            document.getElementById('kpi-custo-liquido').innerText = custoLiquido.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0');

            const blocoKpis = document.getElementById('custos-resumo-kpis');
            if (blocoKpis) blocoKpis.classList.remove('d-none');

            // Atualiza Gráficos
            renderizarGraficosFinanceiros(setoresMap, episMap);

            // Renderiza Tabela no bloco de resultados
            renderizarTabelaRegistrosGeral(registros);
            document.getElementById('bloco-resultados').classList.remove('d-none');
            document.getElementById('titulo-resultados').innerText = 'Demonstrativo Financeiro de Custos com EPIs';
        } else {
            exibirErro(res.message || 'Nenhum registro financeiro encontrado no período.');
        }
    })
    .catch(err => {
        exibirErro('Erro ao carregar dados financeiros: ' + err.message);
    });
}

window.carregarRelatorioCustos = gerarRelatorioCustos;

function renderizarGraficosFinanceiros(setoresMap, episMap) {
    const setoresSorted = Object.entries(setoresMap)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 6);
    
    const labelsSetor = setoresSorted.map(item => item[0]);
    const valoresSetor = setoresSorted.map(item => item[1]);

    const episSorted = Object.entries(episMap)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 6);
    
    const labelsEpi = episSorted.map(item => item[0]);
    const valoresEpi = episSorted.map(item => item[1]);

    const ctxSetor = document.getElementById('chartCustosSetor');
    if (ctxSetor && typeof Chart !== 'undefined') {
        if (chartInstanceSetor) chartInstanceSetor.destroy();
        chartInstanceSetor = new Chart(ctxSetor, {
            type: 'doughnut',
            data: {
                labels: labelsSetor.length ? labelsSetor : ['Sem dados'],
                datasets: [{
                    data: valoresSetor.length ? valoresSetor : [0],
                    backgroundColor: ['#305BD3', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' }
                }
            }
        });
    }

    const ctxEpi = document.getElementById('chartCustosEpis');
    if (ctxEpi && typeof Chart !== 'undefined') {
        if (chartInstanceEpis) chartInstanceEpis.destroy();
        chartInstanceEpis = new Chart(ctxEpi, {
            type: 'bar',
            data: {
                labels: labelsEpi.length ? labelsEpi : ['Sem dados'],
                datasets: [{
                    label: 'Investimento (R$)',
                    data: valoresEpi.length ? valoresEpi : [0],
                    backgroundColor: '#305BD3',
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { ticks: { callback: v => 'R$ ' + v } }
                }
            }
        });
    }
}

/**
 * RELATÓRIO 5: Auditoria de Logs de Sistema (Modelo v2)
 */
function montarQueryAuditoria() {
    const params = new URLSearchParams();
    const usuario = document.getElementById('auditoria-usuario')?.value.trim() || '';
    const acao = document.getElementById('auditoria-acao')?.value.trim() || '';
    const entidade = document.getElementById('auditoria-entidade')?.value.trim() || '';
    const dataInicio = document.getElementById('auditoria-data-inicio')?.value || '';
    const dataFim = document.getElementById('auditoria-data-fim')?.value || '';
    const palavraChave = document.getElementById('auditoria-palavra-chave')?.value.trim() || '';

    const descricoes = [];
    if (usuario) { params.append('usuario', usuario); descricoes.push(`Usuário=${usuario}`); }
    if (acao) { params.append('acao', acao); descricoes.push(`Ação=${acao}`); }
    if (entidade) { params.append('entidade', entidade); descricoes.push(`Entidade=${entidade}`); }
    if (dataInicio) { params.append('data_inicio', dataInicio); descricoes.push(`Data Início=${dataInicio}`); }
    if (dataFim) { params.append('data_fim', dataFim); descricoes.push(`Data Fim=${dataFim}`); }
    if (palavraChave) { params.append('palavra_chave', palavraChave); descricoes.push(`Termo="${palavraChave}"`); }

    filtrosAtivosString = descricoes.length ? descricoes.join('; ') + ';' : 'Todos os registros;';
    return params.toString();
}

function gerarRelatorioAuditoria() {
    const queryString = montarQueryAuditoria();
    const endpoint = 'logs' + (queryString ? '?' + queryString : '');

    exibirLoading();

    fetch(`${PROXY_URL}?route=${endpoint}`)
    .then(res => res.json())
    .then(res => {
        if (res.success && res.data) {
            dadosAtivos = res.data;
            colunasAtivas = ['Data/Hora', 'Usuário', 'Perfil', 'Ação', 'Entidade', 'Reg/ID', 'Descrição da Ocorrência'];

            let html = `
                <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 12px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 13%;">Data/Hora</th>
                            <th style="width: 12%;">Usuário</th>
                            <th style="width: 16%;">Perfil</th>
                            <th style="width: 13%;">Ação</th>
                            <th style="width: 12%;">Entidade</th>
                            <th style="width: 6%;">Reg/ID</th>
                            <th style="width: 28%;">Descrição da Ocorrência</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            if (dadosAtivos.length === 0) {
                html += `<tr><td colspan="7" class="text-center text-muted py-4">Nenhum log correspondente aos filtros foi localizado.</td></tr>`;
            } else {
                dadosAtivos.forEach(row => {
                    let dataFormat = '---';
                    if (row.log_datahora) {
                        try {
                            const dt = new Date(String(row.log_datahora).replace(' ', 'T') + 'Z');
                            dataFormat = isNaN(dt.getTime()) ? row.log_datahora : dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                        } catch (e) {
                            dataFormat = row.log_datahora;
                        }
                    }

                    let ocorrencia = row.log_ocorrencia || '';
                    if (!ocorrencia && row.log_detalhes) {
                        try {
                            const det = JSON.parse(row.log_detalhes);
                            ocorrencia = det.ocorrencia || 'Sem descrição.';
                        } catch (e) {
                            ocorrencia = row.log_detalhes;
                        }
                    }

                    const perfilFormatado = row.usu_perfil || row.perfil || '---';

                    html += `
                        <tr>
                            <td class="text-muted fw-medium">${dataFormat}</td>
                            <td class="fw-semibold">${row.usu_login || 'Sistema'}</td>
                            <td><span class="badge bg-light text-dark border">${perfilFormatado}</span></td>
                            <td><span class="fw-bold">${row.log_acao || '---'}</span></td>
                            <td>${row.log_tabela || '---'}</td>
                            <td>${row.log_registro_id || '---'}</td>
                            <td><div class="text-truncate" style="max-width: 320px;" title="${(ocorrencia || '').replace(/"/g, '&quot;')}">${ocorrencia}</div></td>
                        </tr>
                    `;
                });
            }

            html += '</tbody></table>';
            renderizarResultados('Relatório de Auditoria de Logs de Sistema (Modelo v2)', html);
        } else {
            exibirErro(res.message || 'Sem registros de auditoria localizados.');
        }
    })
    .catch(() => exibirErro('Erro na chamada à API de auditoria.'));
}

function abrirImpressaoAuditoriaV2() {
    const queryString = montarQueryAuditoria();
    const url = 'relatorio_auditoria_impressao.php' + (queryString ? '?' + queryString : '');
    window.open(url, '_blank');
}

function abrirModeloOficial(tipo) {
    if (tipo === 'geral') {
        const query = montarQueryGeral(1000);
        window.open('relatorio_geral.php' + (query ? '?' + query : ''), '_blank');
    } else if (tipo === 'ficha') {
        const funcId = document.getElementById('entregas-func-id').value;
        if (!funcId) {
            alert('Selecione um colaborador no filtro para emitir sua Ficha Individual (NR-06)!');
            return;
        }
        window.open(`ficha_colaborador.php?id=${funcId}`, '_blank');
    } else if (tipo === 'ca') {
        window.open('relatorio_validade_ca.php', '_blank');
    } else if (tipo === 'custos') {
        const dataInicio = document.getElementById('custos-data-inicio')?.value || '';
        const dataFim = document.getElementById('custos-data-fim')?.value || '';
        const setor = document.getElementById('custos-departamento')?.value || '';
        const func = document.getElementById('custos-funcionario')?.value || '';
        const params = new URLSearchParams();
        if (dataInicio) params.append('data_inicio', dataInicio);
        if (dataFim) params.append('data_fim', dataFim);
        if (setor) params.append('setor', setor);
        if (func) params.append('funcionario', func);
        const query = params.toString();
        window.open('relatorio_financeiro.php' + (query ? '?' + query : ''), '_blank');
    } else if (tipo === 'consumo') {
        window.open('relatorio_consumo_epi.php', '_blank');
    }
}

/* ===================== RELATÓRIO GERAL DE FORNECIMENTO DE EPIs ===================== */

const MOTIVOS_MAP = {
    'ADMISSAO': 'Admissão',
    'SUBSTITUICAO': 'Substituição',
    'VENCIMENTO': 'Vencimento',
    'PERDA': 'Perda',
    'DANO': 'Dano / Avaria',
    'TROCA_FUNCAO': 'Troca de Função',
    'OUTROS': 'Outros'
};

function traduzirMotivo(motivo) {
    if (!motivo) return '---';
    const chave = String(motivo).toUpperCase();
    return MOTIVOS_MAP[chave] || motivo;
}

function formatarDataHoraBR(valor) {
    if (!valor) return '---';
    const d = new Date(String(valor).replace(' ', 'T'));
    if (isNaN(d.getTime())) return '---';
    return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// Define período padrão (últimos 30 dias) na primeira abertura do painel
(function inicializarDatasGeral() {
    document.addEventListener('DOMContentLoaded', function() {
        const hoje = new Date();
        const inicio = new Date();
        inicio.setDate(hoje.getDate() - 30);
        const toInput = d => d.toISOString().split('T')[0];
        const campoInicio = document.getElementById('geral-data-inicio');
        const campoFim = document.getElementById('geral-data-fim');
        if (campoInicio && !campoInicio.value) campoInicio.value = toInput(inicio);
        if (campoFim && !campoFim.value) campoFim.value = toInput(hoje);

        const custInicio = document.getElementById('custos-data-inicio');
        const custFim = document.getElementById('custos-data-fim');
        if (custInicio && !custInicio.value) custInicio.value = toInput(inicio);
        if (custFim && !custFim.value) custFim.value = toInput(hoje);
    });
})();

function montarQueryGeral(limite) {
    const dataInicio = document.getElementById('geral-data-inicio').value;
    const dataFim = document.getElementById('geral-data-fim').value;

    if (!dataInicio || !dataFim) {
        alert('Informe as datas de início e fim do período.');
        return null;
    }
    if (new Date(dataInicio) > new Date(dataFim)) {
        alert('A data inicial não pode ser maior que a data final.');
        return null;
    }

    const params = new URLSearchParams();
    params.append('data_inicial', `${dataInicio} 00:00:00`);
    params.append('data_final', `${dataFim} 23:59:59`);
    params.append('status_entrega', 'FINALIZADA');
    params.append('ordenacao', 'data_desc');
    params.append('pagina', geralPaginaAtual);
    params.append('limite', limite || GERAL_LIMITE_PAGINA);

    const campos = [
        ['funcionario_nome', 'geral-funcionario'],
        ['departamento', 'geral-departamento'],
        ['cargo', 'geral-cargo'],
        ['motivo', 'geral-motivo'],
        ['categoria', 'geral-categoria'],
        ['item_com_ca', 'geral-com-ca']
    ];
    campos.forEach(([param, id]) => {
        const valor = document.getElementById(id).value.trim();
        if (valor !== '') params.append(param, valor);
    });

    return params.toString();
}

async function carregarRelatorioGeral(pagina, limiteOverride) {
    geralPaginaAtual = Math.max(1, pagina || 1);
    const queryString = montarQueryGeral(limiteOverride);
    if (queryString === null) return;

    exibirLoading();
    document.getElementById('titulo-resultados').innerText = 'Relatório Geral de Fornecimento de EPIs';

    try {
        const res = await fetch(`${PROXY_URL}?route=relatorios/epis/geral&${queryString}`).then(r => r.json());
        if (!res.success || !res.data) {
            throw new Error(res.message || 'Não foi possível gerar o relatório.');
        }

        geralUltimaResposta = res.data;
        const dados = res.data;

        // KPIs do Resumo Gerencial Consolidado
        renderizarKpisGeral(dados.indicadores || {}, dados.permite_visualizar_custos);

        // Abas de agrupamento + registros detalhados
        renderizarConteudoAbasGeral(dados);
        document.getElementById('geral-abas-agrupamento').classList.remove('d-none');
        mostrarAgrupamento('registros', document.querySelector('#geral-abas-agrupamento .nav-link'));

        atualizarPaginacaoGeral(dados.paginacao || {});
        filtrosAtivosString = `Relatório Geral — Período: ${document.getElementById('geral-data-inicio').value} a ${document.getElementById('geral-data-fim').value}`;
    } catch (e) {
        exibirErro(e.message || 'Erro na consulta do relatório geral.');
    }
}

function renderizarKpisGeral(indicadores, permiteCustos) {
    const kpis = [
        { label: 'Entregas Realizadas', valor: indicadores.total_entregas ?? 0, icone: 'journal-check', cor: 'text-primary' },
        { label: 'Unidades Fornecidas', valor: indicadores.total_unidades ?? 0, icone: 'box-seam', cor: 'text-primary' },
        { label: 'Funcionários Atendidos', valor: indicadores.funcionarios_atendidos ?? 0, icone: 'people', cor: 'text-primary' },
        { label: 'EPIs Diferentes', valor: indicadores.epis_diferentes ?? 0, icone: 'layers', cor: 'text-primary' },
        { label: 'Devoluções Vinculadas', valor: indicadores.total_devolucoes ?? 0, icone: 'arrow-counterclockwise', cor: 'text-warning' },
        { label: 'Substituições Realizadas', valor: indicadores.total_substituicoes ?? 0, icone: 'arrow-repeat', cor: 'text-warning' }
    ];

    if (permiteCustos) {
        const custo = parseFloat(indicadores.custo_total ?? 0);
        kpis.push({
            label: 'Custo Total Histórico',
            valor: isNaN(custo) ? 'R$ 0,00' : custo.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }),
            icone: 'currency-dollar',
            cor: 'text-success'
        });
    }

    document.getElementById('geral-resumo').classList.remove('d-none');
    document.getElementById('geral-kpis').innerHTML = kpis.map(k => `
        <div class="col-md-3 col-6">
            <div class="border rounded-3 p-3 h-100 bg-light">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <i class="bi bi-${k.icone} ${k.cor}"></i>
                    <small class="text-muted" style="font-size: 11px;">${k.label}</small>
                </div>
                <span class="fw-bold fs-5">${k.valor}</span>
            </div>
        </div>
    `).join('');
}

function renderizarConteudoAbasGeral(dados) {
    const wrapper = document.getElementById('tabela-resultados-wrapper');
    wrapper.dataset.agrupamentos = JSON.stringify(dados.agrupamentos || {});
    wrapper.dataset.registros = JSON.stringify(dados.registros || []);
}

function mostrarAgrupamento(aba, btn) {
    document.querySelectorAll('#geral-abas-agrupamento .nav-link').forEach(b => b.classList.remove('active'));
    if (btn) btn.classList.add('active');

    const wrapper = document.getElementById('tabela-resultados-wrapper');
    let agrupamentos = {};
    let registros = [];
    try {
        agrupamentos = JSON.parse(wrapper.dataset.agrupamentos || '{}');
        registros = JSON.parse(wrapper.dataset.registros || '[]');
    } catch (e) {}

    if (aba === 'registros') {
        renderizarTabelaRegistrosGeral(registros);
        return;
    }

    let html = '<div class="row g-3">';
    const cards = [];

    if (aba === 'por_epi') {
        (agrupamentos.por_epi || []).forEach(g => {
            const custo = g.custo_total !== null && g.custo_total !== undefined
                ? `<br><span class="text-success fw-semibold" style="font-size:12px;">Custo total: ${parseFloat(g.custo_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</span>`
                : '';
            cards.push(`
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold mb-1">${g.epi_nome}</div>
                        <small class="text-muted">${g.quantidade} un fornecidas (${g.funcionarios} colaboradores)</small>${custo}
                    </div>
                </div>`);
        });
    } else if (aba === 'por_setor') {
        (agrupamentos.por_setor || []).forEach(g => {
            cards.push(`
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold mb-1">${g.setor}</div>
                        <small class="text-muted">${g.unidades} un em ${g.entregas} entregas (${g.funcionarios} colaboradores)</small>
                    </div>
                </div>`);
        });
    } else if (aba === 'por_motivo') {
        (agrupamentos.por_motivo || []).forEach(g => {
            const percentual = parseFloat(g.percentual ?? 0).toLocaleString('pt-BR', { maximumFractionDigits: 2 });
            cards.push(`
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold mb-1">${traduzirMotivo(g.motivo)}</div>
                        <small class="text-muted">${g.quantidade} un fornecidas (${percentual}%)</small>
                        <div class="progress mt-2" style="height: 6px;">
                            <div class="progress-bar" style="width: ${Math.min(100, parseFloat(g.percentual ?? 0))}%;"></div>
                        </div>
                    </div>
                </div>`);
        });
    } else if (aba === 'por_funcionario') {
        (agrupamentos.por_funcionario || []).forEach(g => {
            cards.push(`
                <div class="col-md-4">
                    <div class="border rounded-3 p-3 h-100">
                        <div class="fw-semibold mb-1">${g.funcionario}</div>
                        <small class="text-muted">${g.unidades} un recebidas (${g.itens} itens/EPIs distintos)</small>
                    </div>
                </div>`);
        });
    }

    html += cards.length ? cards.join('') : '<div class="col-12"><p class="text-muted text-center py-4 m-0">Nenhum dado agrupado encontrado.</p></div>';
    html += '</div>';
    wrapper.innerHTML = html;
}

function renderizarTabelaRegistrosGeral(registros) {
    const permiteCustos = geralUltimaResposta?.permite_visualizar_custos === true;
    dadosAtivos = registros;
    colunasAtivas = ['Data', 'Funcionário', 'Setor', 'EPI', 'C.A.', 'Tam', 'Qtd', 'Motivo'];
    if (permiteCustos) colunasAtivas.push('Valor Total');

    let html = `
        <table class="table table-striped border align-middle" id="tabela-relatorio-gerado" style="font-size: 13px;">
            <thead class="table-light">
                <tr>
                    <th>Data</th>
                    <th>Funcionário</th>
                    <th>Setor</th>
                    <th>EPI</th>
                    <th>C.A.</th>
                    <th>Tam</th>
                    <th>Qtd</th>
                    <th>Motivo</th>
                    ${permiteCustos ? '<th class="text-end text-nowrap">Valor Total</th>' : ''}
                </tr>
            </thead>
            <tbody>`;

    if (!registros.length) {
        html += `<tr><td colspan="${colunasAtivas.length}" class="text-center text-muted py-4">Nenhum fornecimento de EPI foi encontrado para o período e filtros informados.</td></tr>`;
    }

    registros.forEach(r => {
        const valorTotal = r.valor_total !== null && r.valor_total !== undefined
            ? `<td class="text-end fw-semibold text-nowrap">${parseFloat(r.valor_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0')}</td>`
            : (permiteCustos ? '<td class="text-end text-muted text-nowrap">---</td>' : '');
        html += `
            <tr>
                <td>${formatarDataHoraBR(r.entr_data_entrega)}</td>
                <td class="fw-semibold">${r.fun_nome || '---'}</td>
                <td>${r.fun_departamento || '---'}</td>
                <td class="fw-semibold">${r.epi_nome || '---'}</td>
                <td>${r.epi_ca || 'Isento'}</td>
                <td>${r.item_tamanho || '---'}</td>
                <td>${r.item_quantidade ?? 1}</td>
                <td><span class="badge bg-light text-dark border">${traduzirMotivo(r.item_motivo_entrega || r.entr_motivo)}</span></td>
                ${valorTotal}
            </tr>`;
    });

    html += '</tbody></table>';
    document.getElementById('tabela-resultados-wrapper').innerHTML = html;
}

function atualizarPaginacaoGeral(paginacao) {
    const container = document.getElementById('geral-paginacao');
    const total = parseInt(paginacao.total_registros ?? 0);
    const totalPaginas = parseInt(paginacao.total_paginas ?? 0);

    if (totalPaginas <= 0) {
        container.classList.add('d-none');
        return;
    }

    container.classList.remove('d-none');
    const primeiro = ((geralPaginaAtual - 1) * GERAL_LIMITE_PAGINA) + 1;
    const ultimo = Math.min(geralPaginaAtual * GERAL_LIMITE_PAGINA, total);
    document.getElementById('geral-paginacao-info').innerText = `Exibindo ${primeiro}–${ultimo} de ${total} registros`;
    document.getElementById('geral-btn-anterior').disabled = geralPaginaAtual <= 1;
    document.getElementById('geral-btn-proxima').disabled = geralPaginaAtual >= totalPaginas;
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
async function exportarCSV() {
    if (relatorioAtivo === 'geral') {
        await exportarCSVRelatorioGeral();
        return;
    }

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
            } else if (relatorioAtivo === 'auditoria') {
                let dataFormat = row.log_datahora || '';
                try {
                    const dt = new Date(String(row.log_datahora).replace(' ', 'T') + 'Z');
                    if (!isNaN(dt.getTime())) {
                        dataFormat = dt.toLocaleDateString('pt-BR') + ' ' + dt.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
                    }
                } catch(e) {}

                let ocorrencia = row.log_ocorrencia || '';
                if (!ocorrencia && row.log_detalhes) {
                    try {
                        const det = JSON.parse(row.log_detalhes);
                        ocorrencia = det.ocorrencia || '';
                    } catch(e) {}
                }

                linha = [
                    dataFormat,
                    row.usu_login || 'Sistema',
                    row.usu_perfil || row.perfil || '---',
                    row.log_acao || '',
                    row.log_tabela || '',
                    row.log_registro_id || '',
                    ocorrencia
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
 * Exportação CSV dedicada ao Relatório Geral (reconsulta com limite ampliado)
 */
async function exportarCSVRelatorioGeral() {
    const paginaAtual = geralPaginaAtual;
    geralPaginaAtual = 1;
    const queryString = montarQueryGeral(10000);
    if (queryString === null) { geralPaginaAtual = paginaAtual; return; }

    try {
        const res = await fetch(`${PROXY_URL}?route=relatorios/epis/geral&${queryString}`).then(r => r.json());
        if (!res.success || !res.data) throw new Error(res.message || 'Falha ao exportar.');
        const registros = res.data.registros || [];
        const permiteCustos = res.data.permite_visualizar_custos === true;

        let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
        const cabecalho = ['Data', 'Funcionário', 'Setor', 'EPI', 'C.A.', 'Tam', 'Qtd', 'Motivo'];
        if (permiteCustos) cabecalho.push('Valor Total');
        csvContent += cabecalho.join(";") + "\n";

        registros.forEach(r => {
            const linha = [
                formatarDataHoraBR(r.entr_data_entrega),
                r.fun_nome || '',
                r.fun_departamento || '',
                r.epi_nome || '',
                r.epi_ca || 'Isento',
                r.item_tamanho || '',
                r.item_quantidade ?? 1,
                traduzirMotivo(r.item_motivo_entrega || r.entr_motivo)
            ];
            if (permiteCustos) linha.push((r.valor_total ?? 0).toString().replace('.', ','));
            csvContent += linha.map(v => `"${(v || '').toString().replace(/"/g, '""')}"`).join(";") + "\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", `relatorio_geral_fornecimento_${Date.now()}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        filtrosAtivosString += ' — Exportação completa do período';
        registrarExportacaoAuditoria('CSV');
    } catch (e) {
        alert(e.message);
    } finally {
        geralPaginaAtual = paginaAtual;
    }
}

/**
 * IMPRIMIR PDF: Dispara a visualização de impressão nativa
 */
function imprimirRelatorio() {
    if (relatorioAtivo === 'geral') {
        imprimirRelatorioGeral();
        return;
    }
    if (relatorioAtivo === 'auditoria') {
        abrirImpressaoAuditoriaV2();
        return;
    }

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
 * Impressão dedicada ao Relatório Geral (layout paisagem com resumo e agrupamentos)
 */
async function imprimirRelatorioGeral() {
    const paginaAtual = geralPaginaAtual;
    geralPaginaAtual = 1;
    const queryString = montarQueryGeral(10000);
    if (queryString === null) { geralPaginaAtual = paginaAtual; return; }

    try {
        const res = await fetch(`${PROXY_URL}?route=relatorios/epis/geral&${queryString}`).then(r => r.json());
        if (!res.success || !res.data) throw new Error(res.message || 'Falha ao gerar impressão.');
        const dados = res.data;
        const permiteCustos = dados.permite_visualizar_custos === true;
        const ind = dados.indicadores || {};

        let kpisHtml = `
            <div class="row g-2 mb-4" style="font-size: 12px;">
                <div class="col border rounded p-2"><strong>${ind.total_entregas ?? 0}</strong><br>Entregas Realizadas</div>
                <div class="col border rounded p-2"><strong>${ind.total_unidades ?? 0}</strong><br>Unidades Fornecidas</div>
                <div class="col border rounded p-2"><strong>${ind.funcionarios_atendidos ?? 0}</strong><br>Funcionários Atendidos</div>
                <div class="col border rounded p-2"><strong>${ind.epis_diferentes ?? 0}</strong><br>EPIs Diferentes</div>
                <div class="col border rounded p-2"><strong>${ind.total_devolucoes ?? 0}</strong><br>Devoluções</div>
                <div class="col border rounded p-2"><strong>${ind.total_substituicoes ?? 0}</strong><br>Substituições</div>
                ${permiteCustos ? `<div class="col border rounded p-2"><strong style="color:#10B981;">${(parseFloat(ind.custo_total ?? 0)).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })}</strong><br>Custo Total</div>` : ''}
            </div>`;

        let tabelaHtml = `
            <table class="table table-striped table-sm border align-middle" style="font-size: 11px;">
                <thead class="table-light">
                    <tr>
                        <th>Data</th><th>Funcionário</th><th>Setor</th><th>EPI</th><th>C.A.</th>
                        <th>Tam</th><th>Qtd</th><th>Motivo</th><th>Responsável</th>
                        ${permiteCustos ? '<th class="text-end text-nowrap">Valor Total</th>' : ''}
                    </tr>
                </thead>
                <tbody>`;
        (dados.registros || []).forEach(r => {
            tabelaHtml += `
                <tr>
                    <td>${formatarDataHoraBR(r.entr_data_entrega)}</td>
                    <td>${r.fun_nome || '---'}</td>
                    <td>${r.fun_departamento || '---'}</td>
                    <td>${r.epi_nome || '---'}</td>
                    <td>${r.epi_ca || 'Isento'}</td>
                    <td>${r.item_tamanho || '---'}</td>
                    <td>${r.item_quantidade ?? 1}</td>
                    <td>${traduzirMotivo(r.item_motivo_entrega || r.entr_motivo)}</td>
                    <td>${r.usu_login || '---'}</td>
                    ${permiteCustos ? `<td class="text-end text-nowrap">${r.valor_total !== null && r.valor_total !== undefined ? parseFloat(r.valor_total).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }).replace(/\s/g, '\u00a0') : '---'}</td>` : ''}
                </tr>`;
        });
        tabelaHtml += '</tbody></table>';

        document.getElementById('print-titulo-relatorio').innerText = 'Relatório Geral de Fornecimento de EPIs';
        document.getElementById('print-data-emissao').innerText = `Emitido em: ${new Date().toLocaleString('pt-BR')}`;
        document.getElementById('print-tabela-conteudo').innerHTML = kpisHtml + tabelaHtml;

        filtrosAtivosString += ' — Impressão completa do período';
        window.print();
        registrarExportacaoAuditoria('PDF');
    } catch (e) {
        alert(e.message);
    } finally {
        geralPaginaAtual = paginaAtual;
    }
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

// ========== AUTOCOMPLETE DO FILTRO DE COLABORADOR (Relatório Entregas) ==========
const FUNC_LIST_REL = <?= json_encode(array_values(array_map(function($f){
    return [
        'fun_id'          => (int)$f['fun_id'],
        'fun_nome'        => $f['fun_nome'] ?? '',
        'fun_cpf'         => $f['fun_cpf'] ?? '',
        'fun_cargo'       => $f['fun_cargo'] ?? '',
        'fun_departamento'=> $f['fun_departamento'] ?? ($f['fun_setor'] ?? '')
    ];
}, $funcionarios)), JSON_UNESCAPED_UNICODE) ?>;

let relEntregasResultados = [];
let relEntregasIdx = -1;
let relEntregasNomeSelecionado = '';

function _rlNorm(s){return String(s||'').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').trim();}
function _rlEsc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');}
function _rlCpf(c){const d=String(c||'').replace(/\D/g,'');return d.length===11?d.slice(0,3)+'.***.***-'+d.slice(9):(c||'---');}
function _rlHL(t,b){
    if(!b||!t)return _rlEsc(t);
    const tn=_rlNorm(t),bn=_rlNorm(b),p=tn.indexOf(bn);
    if(p===-1)return _rlEsc(t);
    return _rlEsc(t.slice(0,p))+'<strong style="color:#3b82f6;">'+_rlEsc(t.slice(p,p+b.length))+'</strong>'+_rlEsc(t.slice(p+b.length));
}
function _rlCor(n){const c=['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899'];let h=0;for(let i=0;i<n.length;i++)h=n.charCodeAt(i)+((h<<5)-h);return c[Math.abs(h)%c.length];}
function _rlIni(n){return(n||'??').split(' ').filter(Boolean).slice(0,2).map(w=>w[0].toUpperCase()).join('');}

function fecharDdRelEntregas(){
    const dd=document.getElementById('dropdown-rel-entregas');
    if(dd){dd.style.display='none';dd.innerHTML='';}
    relEntregasIdx=-1;relEntregasResultados=[];
}

function buscarRelEntregas(termo){
    relEntregasIdx=-1;
    const btn=document.getElementById('btn-limpar-rel-entregas');
    if(btn) btn.style.display=termo.length>0?'block':'none';

    // Limpar o ID selecionado quando o user digita algo novo
    document.getElementById('entregas-func-id').value='';
    relEntregasNomeSelecionado='';

    const tn=_rlNorm(termo);
    const cpfD=termo.replace(/\D/g,'');
    if(tn.length<2){fecharDdRelEntregas();return;}

    relEntregasResultados=FUNC_LIST_REL.filter(f=>{
        const n=_rlNorm(f.fun_nome),ca=_rlNorm(f.fun_cargo),dp=_rlNorm(f.fun_departamento),cp=String(f.fun_cpf||'').replace(/\D/g,'');
        return n.includes(tn)||n.split(/\s+/).some(p=>p.startsWith(tn))||ca.includes(tn)||dp.includes(tn)||(cpfD.length>0&&cp.includes(cpfD));
    });
    renderDdRelEntregas(termo);
}

function renderDdRelEntregas(termo){
    const dd=document.getElementById('dropdown-rel-entregas');
    if(!dd)return;
    if(relEntregasResultados.length===0){
        dd.innerHTML='<div style="padding:16px;text-align:center;color:#64748b;font-size:13px;"><i class="bi bi-search" style="margin-right:6px;"></i>Nenhum colaborador encontrado com "<strong>'+_rlEsc(termo)+'</strong>"</div>';
        dd.style.display='block';return;
    }
    const total=relEntregasResultados.length;
    const itens=relEntregasResultados.slice(0,8);
    let html='<div style="padding:8px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:8px;font-size:12px;color:#64748b;flex-wrap:wrap;"><span style="display:flex;align-items:center;gap:5px;"><i class="bi bi-people-fill" style="color:#3b82f6;"></i><strong style="color:#1e293b;">'+total+'</strong>&nbsp;colaborador(es)</span><span style="margin-left:auto;display:flex;align-items:center;gap:5px;"><span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 7px;font-size:11px;font-weight:600;">▲</span><span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 7px;font-size:11px;font-weight:600;">▼</span><span>para navegar</span><span>•</span><span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 10px;font-size:11px;font-weight:600;">Enter</span><span>para escolher</span></span></div>';
    itens.forEach((f,idx)=>{
        const cor=_rlCor(f.fun_nome),ini=_rlIni(f.fun_nome),nHL=_rlHL(f.fun_nome,termo),cpf=_rlCpf(f.fun_cpf),cargo=_rlEsc(f.fun_cargo||'Sem Cargo'),depto=_rlEsc(f.fun_departamento||'');
        html+='<div class="dd-rel-item" id="dd-rel-'+idx+'" onclick="escolherRelEntregas('+f.fun_id+',\''+f.fun_nome.replace(/'/g,"\\'")+'\')" onmouseover="focarRelItem('+idx+')" style="display:flex;align-items:center;gap:12px;padding:10px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .12s;"><div style="width:40px;height:40px;border-radius:50%;background:'+cor+';color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'+ini+'</div><div style="flex:1;min-width:0;line-height:1.35;"><div style="display:flex;align-items:center;justify-content:space-between;gap:8px;"><span style="font-weight:600;color:#1e293b;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+nHL+'</span><span style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;padding:1px 8px;font-size:11px;font-weight:600;white-space:nowrap;flex-shrink:0;">ID #'+f.fun_id+'</span></div><div style="font-size:12px;color:#64748b;margin-top:2px;"><i class="bi bi-briefcase" style="font-size:11px;"></i> '+cargo+(depto?' <span style="color:#cbd5e1;">•</span> <i class="bi bi-building" style="font-size:11px;"></i> '+depto:'')+'</div><div style="font-size:12px;color:#e11d48;font-weight:500;margin-top:1px;">CPF: '+cpf+'</div></div></div>';
    });
    // Opção "Todos os Funcionários"
    html+='<div class="dd-rel-item" onclick="limparRelEntregas()" style="display:flex;align-items:center;gap:12px;padding:10px 14px;cursor:pointer;background:#f8fafc;border-top:1.5px solid #e2e8f0;"><div style="width:40px;height:40px;border-radius:50%;background:#94a3b8;color:#fff;font-weight:700;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-people"></i></div><div style="font-weight:600;color:#1e293b;font-size:14px;">Todos os Funcionários</div></div>';
    dd.innerHTML=html;
    dd.style.display='block';
}

function focarRelItem(idx){
    relEntregasIdx=idx;
    document.querySelectorAll('.dd-rel-item').forEach((el,i)=>{el.style.background=i===idx?'#eff6ff':'';});
}

function teclarRelEntregas(e){
    const dd=document.getElementById('dropdown-rel-entregas');
    if(!dd||dd.style.display==='none')return;
    if(e.key==='ArrowDown'){e.preventDefault();relEntregasIdx=(relEntregasIdx+1)%Math.min(relEntregasResultados.length,8);focarRelItem(relEntregasIdx);const el=document.getElementById('dd-rel-'+relEntregasIdx);if(el)el.scrollIntoView({block:'nearest'});}
    else if(e.key==='ArrowUp'){e.preventDefault();relEntregasIdx=(relEntregasIdx-1+Math.min(relEntregasResultados.length,8))%Math.min(relEntregasResultados.length,8);focarRelItem(relEntregasIdx);const el=document.getElementById('dd-rel-'+relEntregasIdx);if(el)el.scrollIntoView({block:'nearest'});}
    else if(e.key==='Enter'){e.preventDefault();const item=relEntregasIdx>=0?relEntregasResultados[relEntregasIdx]:(relEntregasResultados.length===1?relEntregasResultados[0]:null);if(item)escolherRelEntregas(item.fun_id,item.fun_nome);}
    else if(e.key==='Escape'){fecharDdRelEntregas();}
}

function escolherRelEntregas(funId, nome){
    document.getElementById('entregas-func-id').value=funId;
    document.getElementById('input-busca-rel-entregas').value=nome;
    relEntregasNomeSelecionado=nome;
    document.getElementById('btn-limpar-rel-entregas').style.display='block';
    fecharDdRelEntregas();
}

function limparRelEntregas(){
    document.getElementById('entregas-func-id').value='';
    const inp=document.getElementById('input-busca-rel-entregas');
    if(inp){inp.value='';inp.focus();}
    relEntregasNomeSelecionado='';
    document.getElementById('btn-limpar-rel-entregas').style.display='none';
    fecharDdRelEntregas();
}

// ========== AUTOCOMPLETE DO FILTRO DE COLABORADOR (Relatório Geral EPIs) ==========
let relGeralResultados = [];
let relGeralIdx = -1;
let relGeralNomeSelecionado = '';

function fecharDdRelGeral(){
    const dd = document.getElementById('dropdown-rel-geral');
    if(dd){ dd.style.display = 'none'; dd.innerHTML = ''; }
    relGeralIdx = -1; relGeralResultados = [];
}

function buscarRelGeral(termo){
    relGeralIdx = -1;
    const btn = document.getElementById('btn-limpar-rel-geral');
    if(btn) btn.style.display = termo.length > 0 ? 'block' : 'none';

    const hiddenId = document.getElementById('geral-funcionario-id');
    if(hiddenId) hiddenId.value = '';
    relGeralNomeSelecionado = '';

    const tn = _rlNorm(termo);
    const cpfD = termo.replace(/\D/g, '');
    if(tn.length < 2){ fecharDdRelGeral(); return; }

    relGeralResultados = FUNC_LIST_REL.filter(f => {
        const n = _rlNorm(f.fun_nome), ca = _rlNorm(f.fun_cargo), dp = _rlNorm(f.fun_departamento), cp = String(f.fun_cpf||'').replace(/\D/g,'');
        return n.includes(tn) || n.split(/\s+/).some(p => p.startsWith(tn)) || ca.includes(tn) || dp.includes(tn) || (cpfD.length > 0 && cp.includes(cpfD));
    });
    renderDdRelGeral(termo);
}

function renderDdRelGeral(termo){
    const dd = document.getElementById('dropdown-rel-geral');
    if(!dd) return;
    if(relGeralResultados.length === 0){
        dd.innerHTML = '<div style="padding:12px;text-align:center;color:#64748b;font-size:12px;"><i class="bi bi-search" style="margin-right:6px;"></i>Nenhum colaborador encontrado com "<strong>'+_rlEsc(termo)+'</strong>"</div>';
        dd.style.display = 'block'; return;
    }
    const total = relGeralResultados.length;
    const itens = relGeralResultados.slice(0, 8);
    let html = '<div style="padding:6px 12px;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;gap:6px;font-size:11px;color:#64748b;"><i class="bi bi-people-fill" style="color:#3b82f6;"></i><strong style="color:#1e293b;">'+total+'</strong>&nbsp;colaborador(es) encontrado(s)</div>';
    itens.forEach((f, idx) => {
        const cor = _rlCor(f.fun_nome), ini = _rlIni(f.fun_nome), nHL = _rlHL(f.fun_nome, termo), cargo = _rlEsc(f.fun_cargo||'Sem Cargo'), depto = _rlEsc(f.fun_departamento||'');
        const nomeEsc = f.fun_nome.replace(/'/g, "\\'");
        const deptoEsc = (f.fun_departamento || '').replace(/'/g, "\\'");
        const cargoEsc = (f.fun_cargo || '').replace(/'/g, "\\'");

        html += '<div class="dd-rel-item" id="dd-rel-g-'+idx+'" onclick="escolherRelGeral('+f.fun_id+',\''+nomeEsc+'\',\''+deptoEsc+'\',\''+cargoEsc+'\')" onmouseover="focarRelGeralItem('+idx+')" style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .12s;"><div style="width:34px;height:34px;border-radius:50%;background:'+cor+';color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">'+ini+'</div><div style="flex:1;min-width:0;line-height:1.3;"><div style="display:flex;align-items:center;justify-content:space-between;gap:6px;"><span style="font-weight:600;color:#1e293b;font-size:13px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+nHL+'</span><span style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:4px;padding:1px 6px;font-size:10px;font-weight:600;">#'+f.fun_id+'</span></div><div style="font-size:11px;color:#64748b;"><i class="bi bi-briefcase" style="font-size:10px;"></i> '+cargo+(depto?' <span style="color:#cbd5e1;">•</span> '+depto:'')+'</div></div></div>';
    });
    html += '<div class="dd-rel-item" onclick="limparRelGeral()" style="display:flex;align-items:center;gap:10px;padding:8px 12px;cursor:pointer;background:#f8fafc;border-top:1px solid #e2e8f0;"><div style="width:34px;height:34px;border-radius:50%;background:#94a3b8;color:#fff;font-weight:700;font-size:14px;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><i class="bi bi-people"></i></div><div style="font-weight:600;color:#1e293b;font-size:13px;">Todos os Funcionários</div></div>';
    dd.innerHTML = html;
    dd.style.display = 'block';
}

function focarRelGeralItem(idx){
    relGeralIdx = idx;
    document.querySelectorAll('#dropdown-rel-geral .dd-rel-item').forEach((el, i) => { el.style.background = i === idx ? '#eff6ff' : ''; });
}

function teclarRelGeral(e){
    const dd = document.getElementById('dropdown-rel-geral');
    if(!dd || dd.style.display === 'none') return;
    if(e.key === 'ArrowDown'){ e.preventDefault(); relGeralIdx = (relGeralIdx + 1) % Math.min(relGeralResultados.length, 8); focarRelGeralItem(relGeralIdx); const el = document.getElementById('dd-rel-g-' + relGeralIdx); if(el) el.scrollIntoView({block:'nearest'}); }
    else if(e.key === 'ArrowUp'){ e.preventDefault(); relGeralIdx = (relGeralIdx - 1 + Math.min(relGeralResultados.length, 8)) % Math.min(relGeralResultados.length, 8); focarRelGeralItem(relGeralIdx); const el = document.getElementById('dd-rel-g-' + relGeralIdx); if(el) el.scrollIntoView({block:'nearest'}); }
    else if(e.key === 'Enter'){ e.preventDefault(); const item = relGeralIdx >= 0 ? relGeralResultados[relGeralIdx] : (relGeralResultados.length === 1 ? relGeralResultados[0] : null); if(item) escolherRelGeral(item.fun_id, item.fun_nome, item.fun_departamento, item.fun_cargo); }
    else if(e.key === 'Escape'){ fecharDdRelGeral(); }
}

function escolherRelGeral(funId, nome, depto, cargo){
    const hiddenId = document.getElementById('geral-funcionario-id');
    if(hiddenId) hiddenId.value = funId;
    const inp = document.getElementById('geral-funcionario');
    if(inp) inp.value = nome;
    relGeralNomeSelecionado = nome;

    // Autopreenchimento dos campos Setor/Departamento e Cargo/Função
    const campoDepto = document.getElementById('geral-departamento');
    if (campoDepto && depto !== undefined) {
        campoDepto.value = depto || '';
    }
    const campoCargo = document.getElementById('geral-cargo');
    if (campoCargo && cargo !== undefined) {
        campoCargo.value = cargo || '';
    }

    const btn = document.getElementById('btn-limpar-rel-geral');
    if(btn) btn.style.display = 'block';
    fecharDdRelGeral();
}

function limparRelGeral(){
    const hiddenId = document.getElementById('geral-funcionario-id');
    if(hiddenId) hiddenId.value = '';
    const inp = document.getElementById('geral-funcionario');
    if(inp){ inp.value = ''; inp.focus(); }

    const campoDepto = document.getElementById('geral-departamento');
    if (campoDepto) campoDepto.value = '';
    const campoCargo = document.getElementById('geral-cargo');
    if (campoCargo) campoCargo.value = '';

    relGeralNomeSelecionado = '';
    const btn = document.getElementById('btn-limpar-rel-geral');
    if(btn) btn.style.display = 'none';
    fecharDdRelGeral();
}

document.addEventListener('click',function(e){
    const w1 = document.getElementById('wrapper-busca-rel-entregas');
    if(w1 && !w1.contains(e.target)) fecharDdRelEntregas();
    const w2 = document.getElementById('wrapper-busca-rel-geral');
    if(w2 && !w2.contains(e.target)) fecharDdRelGeral();
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
