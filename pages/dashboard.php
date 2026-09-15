<?php
declare(strict_types=1);

$page_title = 'Dashboard';
$active_menu = 'dashboard';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();

// Carrega os dados do dashboard em paralelo através da API
$resumo = [];
$custos = [];
$topEpis = [];
$pendencias = [];
$erroApi = null;

try {
    // 1. Resumo Geral
    $resumoRes = $api->get('dashboard/resumo');
    if (isset($resumoRes['success']) && $resumoRes['success']) {
        $resumo = $resumoRes['data'];
    }

    // 2. Custos Consolidados (Apenas Administrador e Gestor possuem permissão)
    if (in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true)) {
        $custosRes = $api->get('dashboard/custos');
        if (isset($custosRes['success']) && $custosRes['success']) {
            $custos = $custosRes['data'];
        }
    }

    // 3. Top EPIs mais entregues
    $topRes = $api->get('dashboard/top-epis');
    if (isset($topRes['success']) && $topRes['success']) {
        $topEpis = $topRes['data'];
    }

    // 4. Pendências Operacionais
    $pendRes = $api->get('dashboard/pendencias');
    if (isset($pendRes['success']) && $pendRes['success']) {
        $pendencias = $pendRes['data'];
    }

    // Garante a contagem precisa de Colaboradores sem PIN / Bloqueados / Pendentes
    $funcsRes = $api->get('funcionarios');
    if (isset($funcsRes['success']) && $funcsRes['success'] && is_array($funcsRes['data'])) {
        $semPinCount = 0;
        foreach ($funcsRes['data'] as $f) {
            $pinStatus = strtoupper((string)($f['assinatura_status'] ?? 'PENDENTE'));
            $funcStatus = strtoupper((string)($f['fun_situacao'] ?? 'ATIVO'));
            $isPinPendente = ($pinStatus === 'PENDENTE' || $pinStatus === 'INATIVO' || $pinStatus === 'NÃO CADASTRADO' || empty($f['assinatura_status']));
            $isAfastado = ($funcStatus === 'AFASTADO');
            $isBloqueado = ($pinStatus === 'BLOQUEADO');
            if ($isPinPendente || $isAfastado || $isBloqueado || $funcStatus !== 'ATIVO') {
                $semPinCount++;
            }
        }
        $pendencias['assinaturas_bloqueadas'] = $semPinCount;
    }
} catch (Exception $e) {
    $erroApi = 'Não foi possível carregar os dados consolidados do painel: ' . $e->getMessage();
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Painel Geral</h3>
                <p class="text-muted">Indicadores e consolidação operacional do ecossistema de EPIs.</p>
            </div>
            <button onclick="window.location.reload();" class="btn btn-light border" title="Atualizar dados">
                <i class="bi bi-arrow-clockwise"></i> Atualizar
            </button>
        </div>

        <?php if ($erroApi !== null): ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erroApi) ?></div>
            </div>
        <?php endif; ?>

        <!-- KPI Cards Grid -->
        <div class="kpi-grid">
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Funcionários Ativos</h3>
                    <p><?= $resumo['funcionarios_ativos'] ?? 0 ?></p>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>EPIs Ativos</h3>
                    <p><?= $resumo['epis_ativos'] ?? 0 ?></p>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-box-seam-fill"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Entregas Realizadas</h3>
                    <p><?= $resumo['entregas_realizadas'] ?? 0 ?></p>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-journal-check"></i>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-info">
                    <h3>Assinaturas Ativas</h3>
                    <p><?= $resumo['assinaturas_ativas'] ?? 0 ?></p>
                </div>
                <div class="kpi-icon">
                    <i class="bi bi-fingerprint"></i>
                </div>
            </div>
        </div>

        <!-- Seção de Custos (Exibido apenas para Admin e Gestor) -->
        <?php if (in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true)): ?>
            <div class="card-custom">
                <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-currency-dollar me-2"></i>Consolidação Financeira de EPIs</h5>
                <div class="row g-4">
                    <div class="col-md-4 border-end border-slate">
                        <div class="p-2 text-center text-md-start">
                            <span class="text-muted d-block mb-1" style="font-size: 13px; text-transform: uppercase;">Custo Total Acumulado</span>
                            <h2 class="fw-bold text-success m-0"><?= isset($custos['custo_total_acumulado']) ? formatarValorMonetario((float)$custos['custo_total_acumulado']) : 'R$ 0,00' ?></h2>
                            <small class="text-muted">Soma histórica de EPIs finalizados e assinados</small>
                        </div>
                    </div>
                    <div class="col-md-4 border-end border-slate">
                        <div class="p-2 text-center text-md-start">
                            <span class="text-muted d-block mb-1" style="font-size: 13px; text-transform: uppercase;">Custo Médio por Item</span>
                            <h2 class="fw-bold text-primary m-0"><?= isset($custos['custo_medio_por_item']) ? formatarValorMonetario((float)$custos['custo_medio_por_item']) : 'R$ 0,00' ?></h2>
                            <small class="text-muted">Média ponderada baseada no snapshot de preços</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-2 text-center text-md-start">
                            <span class="text-muted d-block mb-1" style="font-size: 13px; text-transform: uppercase;">Atualizações de Preços</span>
                            <h2 class="fw-bold text-dark m-0" style="color: var(--color-text-primary) !important;"><?= $custos['total_atualizacoes_preco'] ?? 0 ?></h2>
                            <small class="text-muted">Logs de reajustes na tabela de preços</small>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Gráficos Integrados -->
        <div class="row g-4">
            <!-- Gráfico: Top 5 EPIs mais fornecidos -->
            <div class="col-lg-7">
                <div class="card-custom h-100">
                    <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-bar-chart-line-fill me-2"></i>EPIs mais Entregues</h5>
                    <div style="height: 300px; position: relative;">
                        <?php if (empty($topEpis)): ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                Nenhum registro de entrega encontrado.
                            </div>
                        <?php else: ?>
                            <canvas id="chartTopEpis"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Gráfico/Lista: Pendências SST e Conformidade -->
            <div class="col-lg-5">
                <div class="card-custom h-100">
                    <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-shield-fill-exclamation me-2"></i>Pendências e Riscos SST</h5>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 border rounded text-center style-card-kpi-sst" 
                                 style="background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2) !important; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#modalCaVencidos"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.15)';"
                                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';"
                                 title="Clique para ver os EPIs com C.A. vencidos">
                                <span class="text-danger d-block mb-1 fw-semibold" style="font-size: 12px; text-transform: uppercase;">C.A. Vencidos</span>
                                <h3 class="fw-bold text-danger m-0"><?= $pendencias['ca_vencidos'] ?? 0 ?></h3>
                                <small class="text-danger opacity-75 d-block mt-1" style="font-size: 10px; font-weight: 500;"><i class="bi bi-search me-1"></i>Ver detalhes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded text-center style-card-kpi-sst" 
                                 style="background-color: rgba(245, 158, 11, 0.05); border-color: rgba(245, 158, 11, 0.2) !important; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#modalCaAVencer"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(245, 158, 11, 0.15)';"
                                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';"
                                 title="Clique para ver os EPIs com C.A. a vencer em 30 dias">
                                <span class="text-warning d-block mb-1 fw-semibold" style="font-size: 12px; text-transform: uppercase; color: #d97706 !important;">C.A. a Vencer (30d)</span>
                                <h3 class="fw-bold m-0" style="color: #d97706 !important;"><?= $pendencias['ca_a_vencer_30_dias'] ?? 0 ?></h3>
                                <small class="opacity-75 d-block mt-1" style="font-size: 10px; font-weight: 500; color: #d97706 !important;"><i class="bi bi-search me-1"></i>Ver detalhes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded text-center style-card-kpi-sst" 
                                 style="background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2) !important; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#modalPinBloqueados"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(239, 68, 68, 0.15)';"
                                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';"
                                 title="Clique para ver os colaboradores com PIN bloqueado ou pendente">
                                <span class="text-danger d-block mb-1 fw-semibold" style="font-size: 12px; text-transform: uppercase;">Func. sem PIN</span>
                                <h3 class="fw-bold text-danger m-0" id="card-count-pin-bloqueados"><?= $pendencias['assinaturas_bloqueadas'] ?? 0 ?></h3>
                                <small class="text-danger opacity-75 d-block mt-1" style="font-size: 10px; font-weight: 500;"><i class="bi bi-search me-1"></i>Ver detalhes</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 border rounded text-center style-card-kpi-sst" 
                                 style="background-color: rgba(6, 182, 212, 0.05); border-color: rgba(6, 182, 212, 0.2) !important; cursor: pointer; transition: transform 0.2s ease, box-shadow 0.2s ease;"
                                 data-bs-toggle="modal" 
                                 data-bs-target="#modalEpisEmPosse"
                                 onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(6, 182, 212, 0.15)';"
                                 onmouseout="this.style.transform='none'; this.style.boxShadow='none';"
                                 title="Clique para ver quais EPIs estão em posse e por quem">
                                <span class="text-info d-block mb-1 fw-semibold" style="font-size: 12px; text-transform: uppercase; color: #0284c7 !important;">EPIs em Posse</span>
                                <h3 class="fw-bold m-0" style="color: #0284c7 !important;"><?= $pendencias['epis_pendentes_devolucao'] ?? 0 ?></h3>
                                <small class="opacity-75 d-block mt-1" style="font-size: 10px; font-weight: 500; color: #0284c7 !important;"><i class="bi bi-search me-1"></i>Ver detalhes</small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4" style="height: 180px; position: relative;">
                        <?php if (empty($pendencias)): ?>
                            <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                Sem indicadores de pendências.
                            </div>
                        <?php else: ?>
                            <canvas id="chartPendencias"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODAIS DETALHADOS DE PENDÊNCIAS E RISCOS SST ================= -->

<!-- 1. Modal C.A. Vencidos -->
<div class="modal fade" id="modalCaVencidos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-shield-x me-2"></i>Equipamentos com C.A. Vencido (<span id="count-ca-vencidos"><?= $pendencias['ca_vencidos'] ?? 0 ?></span>)
                    </h5>
                    <p class="text-muted small m-0">Lista de EPIs com Certificado de Aprovação vencido no Ministério do Trabalho.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-ca-vencidos">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>Carregando dados...
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="epis.php?acao=controle_ca" class="btn btn-primary rounded-3">Ir para Controle C.A.</a>
            </div>
        </div>
    </div>
</div>

<!-- 2. Modal C.A. a Vencer (30d) -->
<div class="modal fade" id="modalCaAVencer" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" style="color: #d97706 !important;">
                        <i class="bi bi-hourglass-split me-2"></i>Equipamentos com C.A. a Vencer nos próximos 30 dias (<span id="count-ca-a-vencer"><?= $pendencias['ca_a_vencer_30_dias'] ?? 0 ?></span>)
                    </h5>
                    <p class="text-muted small m-0">EPIs cujo Certificado de Aprovação expira em até 30 dias.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-ca-a-vencer">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>Carregando dados...
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="epis.php?acao=controle_ca" class="btn btn-primary rounded-3">Ir para Controle C.A.</a>
            </div>
        </div>
    </div>
</div>

<!-- 3. Modal PIN Bloqueados / Senha Pendente -->
<div class="modal fade" id="modalPinBloqueados" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="bi bi-shield-lock-fill me-2"></i>Funcionários com PIN Bloqueado ou Senha Pendente (<span id="count-pin-bloqueados"><?= $pendencias['assinaturas_bloqueadas'] ?? 0 ?></span>)
                    </h5>
                    <p class="text-muted small m-0">Colaboradores que necessitam de cadastro, redefinição ou desbloqueio de senha/PIN.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-pin-bloqueados">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>Carregando dados...
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="funcionarios.php?acao=pin" class="btn btn-primary rounded-3">Gerenciar Senhas/PINs</a>
            </div>
        </div>
    </div>
</div>

<!-- 4. Modal EPIs em Posse -->
<div class="modal fade" id="modalEpisEmPosse" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-2">
                <div>
                    <h5 class="modal-title fw-bold" style="color: #0284c7 !important;">
                        <i class="bi bi-box-seam me-2"></i>EPIs Atualmente em Posse por Colaborador (<span id="count-epis-em-posse"><?= $pendencias['epis_pendentes_devolucao'] ?? 0 ?></span>)
                    </h5>
                    <p class="text-muted small m-0">Relação completa de equipamentos fornecidos e em utilização ativa por colaborador.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2" id="body-modal-epis-em-posse">
                <div class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>Carregando dados...
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light border rounded-3" data-bs-dismiss="modal">Fechar</button>
                <a href="entregas.php" class="btn btn-primary rounded-3">Ir para Histórico de Entregas</a>
            </div>
        </div>
    </div>
</div>

<script>
const PROXY_URL = 'api_proxy.php';
let cacheEpisData = null;
let cacheFuncData = null;
let cacheEntregasData = null;

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

async function obterEpis() {
    if (cacheEpisData) return cacheEpisData;
    const res = await fetch(`${PROXY_URL}?route=epis`).then(r => r.json());
    if (res.success && Array.isArray(res.data)) {
        cacheEpisData = res.data;
        return cacheEpisData;
    }
    return [];
}

async function obterFuncionarios() {
    if (cacheFuncData) return cacheFuncData;
    const res = await fetch(`${PROXY_URL}?route=funcionarios`).then(r => r.json());
    if (res.success && Array.isArray(res.data)) {
        cacheFuncData = res.data;
        return cacheFuncData;
    }
    return [];
}

async function obterEntregas() {
    if (cacheEntregasData) return cacheEntregasData;
    const res = await fetch(`${PROXY_URL}?route=entregas`).then(r => r.json());
    if (res.success && Array.isArray(res.data)) {
        cacheEntregasData = res.data;
        return cacheEntregasData;
    }
    return [];
}

async function carregarModalCaVencidos() {
    const container = document.getElementById('body-modal-ca-vencidos');
    const badgeCount = document.getElementById('count-ca-vencidos');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>Carregando C.A.s vencidos...</div>';

    try {
        const epis = await obterEpis();
        const hojeStr = new Date().toISOString().split('T')[0];
        const vencidos = epis.filter(e => e.epi_tipo_item === 'EPI_COM_CA' && e.epi_vencimento_ca && e.epi_vencimento_ca.split(' ')[0] < hojeStr);

        if (badgeCount) badgeCount.textContent = vencidos.length;

        if (vencidos.length === 0) {
            container.innerHTML = '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum EPI com C.A. vencido cadastrado. Todos estão conformes!</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Item / Classificação</th>
                            <th>Fabricante</th>
                            <th>C.A.</th>
                            <th>Vencimento</th>
                            <th>Situação</th>
                        </tr>
                    </thead>
                    <tbody>`;
        vencidos.forEach(epi => {
            const parts = (epi.epi_vencimento_ca || '').split(' ')[0].split('-');
            const vencFmt = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '---';
            html += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(epi.epi_nome)}</div>
                        <small class="text-muted">${escapeHtml(epi.epi_tipo_item)}</small>
                    </td>
                    <td>${escapeHtml(epi.epi_fabricante || '---')}</td>
                    <td class="fw-semibold text-primary">${escapeHtml(epi.epi_ca || '---')}</td>
                    <td class="fw-bold text-danger">${vencFmt}</td>
                    <td><span class="badge bg-danger">Vencido</span></td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger text-center py-3 m-0">Falha ao carregar dados dos EPIs com C.A. vencido.</div>';
    }
}

async function carregarModalCaAVencer() {
    const container = document.getElementById('body-modal-ca-a-vencer');
    const badgeCount = document.getElementById('count-ca-a-vencer');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-warning me-2" role="status"></div>Carregando EPIs a vencer...</div>';

    try {
        const epis = await obterEpis();
        const hoje = new Date(); hoje.setHours(0,0,0,0);
        const aVencer = [];

        epis.forEach(e => {
            if (e.epi_tipo_item === 'EPI_COM_CA' && e.epi_vencimento_ca) {
                const venc = new Date(e.epi_vencimento_ca.split(' ')[0] + 'T00:00:00');
                if (!isNaN(venc.getTime()) && venc >= hoje) {
                    const dias = Math.ceil((venc - hoje) / (1000 * 60 * 60 * 24));
                    if (dias <= 30) {
                        aVencer.push({ ...e, diasRestantes: dias });
                    }
                }
            }
        });

        aVencer.sort((a, b) => a.diasRestantes - b.diasRestantes);
        if (badgeCount) badgeCount.textContent = aVencer.length;

        if (aVencer.length === 0) {
            container.innerHTML = '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Nenhum EPI prestes a vencer nos próximos 30 dias.</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Item / Classificação</th>
                            <th>Fabricante</th>
                            <th>C.A.</th>
                            <th>Data Vencimento</th>
                            <th>Dias Restantes</th>
                        </tr>
                    </thead>
                    <tbody>`;
        aVencer.forEach(epi => {
            const parts = (epi.epi_vencimento_ca || '').split(' ')[0].split('-');
            const vencFmt = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '---';
            html += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(epi.epi_nome)}</div>
                        <small class="text-muted">${escapeHtml(epi.epi_tipo_item)}</small>
                    </td>
                    <td>${escapeHtml(epi.epi_fabricante || '---')}</td>
                    <td class="fw-semibold text-primary">${escapeHtml(epi.epi_ca || '---')}</td>
                    <td class="fw-semibold text-dark">${vencFmt}</td>
                    <td><span class="badge bg-warning text-dark">Vence em ${epi.diasRestantes} dia(s)</span></td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger text-center py-3 m-0">Falha ao carregar dados dos EPIs a vencer.</div>';
    }
}

async function carregarModalPinBloqueados() {
    const container = document.getElementById('body-modal-pin-bloqueados');
    const badgeCount = document.getElementById('count-pin-bloqueados');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-danger me-2" role="status"></div>Carregando colaboradores...</div>';

    try {
        const funcs = await obterFuncionarios();
        const pendentes = [];

        funcs.forEach(f => {
            const pinStatus = (f.assinatura_status || 'PENDENTE').toUpperCase();
            const funcStatus = (f.fun_situacao || 'ATIVO').toUpperCase();

            const isPinPendente = (pinStatus === 'PENDENTE' || pinStatus === 'INATIVO' || pinStatus === 'NÃO CADASTRADO' || !f.assinatura_status);
            const isAfastado = (funcStatus === 'AFASTADO');
            const isBloqueado = (pinStatus === 'BLOQUEADO');

            if (isPinPendente || isAfastado || isBloqueado || funcStatus !== 'ATIVO') {
                let badgeLabel = 'Senha pendente';
                let badgeClass = 'bg-warning text-dark';

                if (isBloqueado) {
                    badgeLabel = 'PIN Bloqueado';
                    badgeClass = 'bg-danger';
                } else if (isAfastado) {
                    badgeLabel = 'Afastado';
                    badgeClass = 'bg-warning text-dark';
                } else if (funcStatus !== 'ATIVO') {
                    badgeLabel = f.fun_situacao;
                    badgeClass = 'bg-secondary';
                }

                pendentes.push({ ...f, badgeLabel, badgeClass });
            }
        });

        if (badgeCount) badgeCount.textContent = pendentes.length;
        const cardCount = document.getElementById('card-count-pin-bloqueados');
        if (cardCount) cardCount.textContent = pendentes.length;

        if (pendentes.length === 0) {
            container.innerHTML = '<div class="alert alert-success text-center py-4 rounded-3 m-0"><i class="bi bi-check-circle-fill me-2 fs-5"></i>Todos os colaboradores possuem PIN/Senha cadastrado e ativo!</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Colaborador</th>
                            <th>Matrícula / CPF</th>
                            <th>Cargo / Departamento</th>
                            <th>Status PIN/Senha</th>
                        </tr>
                    </thead>
                    <tbody>`;
        pendentes.forEach(f => {
            const cpfRaw = f.fun_cpf || '';
            const cpfM = (cpfRaw.length === 11) ? (cpfRaw.substring(0, 3) + '.***.***-' + cpfRaw.substring(9)) : cpfRaw;
            const matr = f.fun_matricula || ('MAT-' + String(f.fun_id).padStart(5, '0'));

            html += `
                <tr>
                    <td>
                        <div class="fw-bold text-primary">${escapeHtml(f.fun_nome)}</div>
                        <small class="text-muted">ID: #${f.fun_id}</small>
                    </td>
                    <td>
                        <div>Matrícula: ${escapeHtml(matr)}</div>
                        <small class="text-muted">CPF: ${escapeHtml(cpfM)}</small>
                    </td>
                    <td>
                        <div>${escapeHtml(f.fun_cargo || '---')}</div>
                        <small class="text-muted">${escapeHtml(f.fun_departamento || '---')}</small>
                    </td>
                    <td><span class="badge ${f.badgeClass}">${escapeHtml(f.badgeLabel)}</span></td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger text-center py-3 m-0">Falha ao carregar dados dos colaboradores.</div>';
    }
}

async function carregarModalEpisEmPosse() {
    const container = document.getElementById('body-modal-epis-em-posse');
    const badgeCount = document.getElementById('count-epis-em-posse');
    if (!container) return;

    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-info me-2" role="status"></div>Carregando EPIs em posse...</div>';

    try {
        const funcs = await obterFuncionarios();
        const emPosse = [];

        if (funcs && funcs.length > 0) {
            const entregasResp = await Promise.all(
                funcs.map(f => fetch(`${PROXY_URL}?route=entregas/funcionario/${f.fun_id}`).then(r => r.json()).catch(() => null))
            );

            entregasResp.forEach((res, idx) => {
                if (!res || !res.success || !Array.isArray(res.data)) return;
                const funcObj = funcs[idx];
                res.data.forEach(entr => {
                    const statusEntr = (entr.entr_status || '').toUpperCase();
                    if (statusEntr !== 'FINALIZADA' && statusEntr !== 'ENTREGUE') return;

                    const itens = entr.itens || [];
                    if (!Array.isArray(itens)) return;

                    itens.forEach(item => {
                        const statusItem = (item.item_status || '').toUpperCase();
                        if (statusItem !== 'ENTREGUE' && statusItem !== 'EM_POSSE') return;

                        const parts = (entr.entr_data_entrega || '').split(' ')[0].split('-');
                        const dataFmt = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : '---';

                        emPosse.push({
                            fun_nome: entr.fun_nome || funcObj.fun_nome || 'Colaborador',
                            fun_cargo: entr.fun_cargo || funcObj.fun_cargo || '---',
                            fun_departamento: entr.fun_departamento || funcObj.fun_departamento || '---',
                            epi_nome: item.item_epi_nome_snapshot || item.epi_nome || 'EPI',
                            epi_ca: item.item_epi_ca_snapshot || item.epi_ca || 'Isento',
                            data_entrega: dataFmt,
                            quantidade: item.item_quantidade || 1,
                            tamanho: item.item_tamanho || null
                        });
                    });
                });
            });
        }

        if (badgeCount) badgeCount.textContent = emPosse.length;

        if (emPosse.length === 0) {
            container.innerHTML = '<div class="alert alert-info text-center py-4 rounded-3 m-0"><i class="bi bi-info-circle-fill me-2 fs-5"></i>Nenhum registro de EPI em posse no momento.</div>';
            return;
        }

        let html = `
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 13.5px;">
                    <thead class="table-light">
                        <tr>
                            <th>Colaborador (Em Posse de)</th>
                            <th>Equipamento (EPI)</th>
                            <th>C.A.</th>
                            <th>Data Entrega</th>
                            <th>Qtd / Tamanho</th>
                        </tr>
                    </thead>
                    <tbody>`;
        emPosse.forEach(item => {
            const tamStr = item.tamanho ? ` (${escapeHtml(item.tamanho)})` : '';
            html += `
                <tr>
                    <td>
                        <div class="fw-bold text-primary">${escapeHtml(item.fun_nome)}</div>
                        <small class="text-muted">${escapeHtml(item.fun_cargo)} | Setor: ${escapeHtml(item.fun_departamento)}</small>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">${escapeHtml(item.epi_nome)}</div>
                    </td>
                    <td class="fw-semibold text-secondary">${escapeHtml(item.epi_ca)}</td>
                    <td>${item.data_entrega}</td>
                    <td><span class="badge bg-light text-dark border">${item.quantidade} un${tamStr}</span></td>
                </tr>`;
        });
        html += '</tbody></table></div>';
        container.innerHTML = html;
    } catch (e) {
        container.innerHTML = '<div class="alert alert-danger text-center py-3 m-0">Falha ao carregar EPIs em posse.</div>';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const modal1 = document.getElementById('modalCaVencidos');
    const modal2 = document.getElementById('modalCaAVencer');
    const modal3 = document.getElementById('modalPinBloqueados');
    const modal4 = document.getElementById('modalEpisEmPosse');

    if (modal1) modal1.addEventListener('show.bs.modal', carregarModalCaVencidos);
    if (modal2) modal2.addEventListener('show.bs.modal', carregarModalCaAVencer);
    if (modal3) modal3.addEventListener('show.bs.modal', carregarModalPinBloqueados);
    if (modal4) modal4.addEventListener('show.bs.modal', carregarModalEpisEmPosse);

    // 1. Renderiza Gráfico de Top EPIs
    <?php if (!empty($topEpis)): ?>
    const ctxTop = document.getElementById('chartTopEpis').getContext('2d');
    const topLabels = <?= json_encode(array_column($topEpis, 'epi_nome')) ?>;
    const topValues = <?= json_encode(array_column($topEpis, 'total_entregue')) ?>;
    
    new Chart(ctxTop, {
        type: 'bar',
        data: {
            labels: topLabels.map(l => l.length > 25 ? l.slice(0, 25) + '...' : l),
            datasets: [{
                label: 'Unidades Entregues',
                data: topValues,
                backgroundColor: 'rgba(48, 91, 211, 0.85)',
                borderColor: '#305BD3',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { grid: { display: false } }
            }
        }
    });
    <?php endif; ?>

    // 2. Renderiza Gráfico de Rosca de Pendências
    <?php if (!empty($pendencias)): ?>
    const ctxPend = document.getElementById('chartPendencias').getContext('2d');
    new Chart(ctxPend, {
        type: 'doughnut',
        data: {
            labels: ['CA Vencidos', 'CA a Vencer (30d)', 'Func. sem PIN'],
            datasets: [{
                data: [
                    <?= $pendencias['ca_vencidos'] ?? 0 ?>,
                    <?= $pendencias['ca_a_vencer_30_dias'] ?? 0 ?>,
                    <?= $pendencias['assinaturas_bloqueadas'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#ef4444',
                    '#f59e0b',
                    '#64748b'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { boxWidth: 12, font: { family: 'Outfit' } }
                }
            },
            cutout: '65%',
            onClick: (e, elements) => {
                if (elements && elements.length > 0) {
                    const idx = elements[0].index;
                    const modals = ['modalCaVencidos', 'modalCaAVencer', 'modalPinBloqueados'];
                    if (modals[idx]) {
                        const mEl = document.getElementById(modals[idx]);
                        if (mEl) (bootstrap.Modal.getInstance(mEl) || new bootstrap.Modal(mEl)).show();
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
