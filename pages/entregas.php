<?php
declare(strict_types=1);

$page_title = 'Histórico Geral de Entregas';
$active_menu = 'entregas';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$entregas = [];

try {
    $response = $api->get('entregas');
    if (isset($response['success']) && $response['success']) {
        $entregas = $response['data'];
    } else {
        $erro = $response['message'] ?? 'Falha ao buscar o histórico de entregas.';
    }
} catch (Exception $e) {
    $erro = 'Erro de conexão: ' . $e->getMessage();
}

$userProfile = $_SESSION['usuario']['usu_perfil'] ?? '';
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <!-- Toast de Notificação -->
        <div class="position-fixed top-0 end-0 p-3" style="z-index: 1080;">
            <div id="toastNotificacao" class="toast align-items-center text-white bg-primary border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body" id="toastMensagem">
                        <i class="bi bi-info-circle me-2"></i> Mensagem
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        </div>

        <!-- Cabeçalho da Página -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">
                    <i class="bi bi-clock-history me-2"></i>Histórico Geral de Entregas
                </h3>
                <p class="text-muted mb-0">Consulte o feed completo de fornecimento de EPIs com assinaturas eletrônicas e hashes de integridade.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <!-- Barra de Navegação Estilo Pill -->
                <div class="btn-group-toggle-view" role="group">
                    <a href="entregas.php" class="btn btn-view active">
                        <i class="bi bi-clock-history me-1"></i> Histórico
                    </a>
                    <a href="devolucoes.php" class="btn btn-view">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Devolução
                    </a>
                </div>

                <!-- Seletor de Modo de Visualização -->
                <div class="btn-group" role="group" aria-label="Modo de visualização">
                    <button type="button" class="btn btn-outline-primary active" id="btn-view-cards" onclick="alternarVisaoEntregas('cards')" title="Visão em Cards (Estilo App Mobile)">
                        <i class="bi bi-grid-fill me-1"></i> Cards (App)
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-view-table" onclick="alternarVisaoEntregas('tabela')" title="Visão em Tabela Tradicional">
                        <i class="bi bi-table me-1"></i> Tabela
                    </button>
                </div>

                <?php if (in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true)): ?>
                    <a href="nova_entrega.php" class="btn btn-primary px-3 py-2 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Nova Entrega
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busca-input" class="form-control border-start-0" placeholder="Buscar por colaborador, EPI, motivo, ID ou Hash...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select id="filtro-motivo" class="form-select">
                        <option value="">Todos os Motivos</option>
                        <option value="ADMISSAO">Admissão</option>
                        <option value="SUBSTITUICAO">Substituição</option>
                        <option value="VENCIMENTO">Vencimento Vida Útil</option>
                        <option value="PERDA">Perda</option>
                        <option value="DANO">Dano</option>
                        <option value="TROCA_FUNCAO">Troca de Função</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos os Status</option>
                        <option value="FINALIZADA">Finalizada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>
            </div>

            <!-- VISÃO 1: FEED EM CARDS (Estilo App Mobile Android - Conforme Solicitação) -->
            <div id="visao-cards-container">
                <?php if (empty($entregas)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>
                        <p class="mb-0">Nenhum registro de entrega de EPI localizado.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3" id="cards-grid">
                        <?php foreach ($entregas as $entr): ?>
                            <?php
                            $statusClass = strtolower($entr['entr_status']);
                            $validacaoSenha = $entr['entr_validacao_senha'] ?? 'PENDENTE';
                            
                            $itensNomes = [];
                            $totalItens = 0;
                            foreach ($entr['itens'] as $item) {
                                $itensNomes[] = ($item['item_epi_nome_snapshot'] ?? 'EPI') . " (" . ($item['item_quantidade'] ?? 1) . "x)";
                                $totalItens += (int)$item['item_quantidade'];
                            }
                            $itensString = implode(', ', $itensNomes);
                            $hashShort = !empty($entr['entr_hash_assinatura']) ? substr($entr['entr_hash_assinatura'], 0, 32) . '...' : 'Hash não disponível';
                            ?>
                            <div class="col-12 col-md-6 col-xl-4 entrega-item-wrapper entrega-card-item"
                                 data-id="<?= htmlspecialchars((string)$entr['entr_id']) ?>"
                                 data-nome="<?= htmlspecialchars(strtolower($entr['fun_nome'] ?? '')) ?>"
                                 data-epis="<?= htmlspecialchars(strtolower($itensString)) ?>"
                                 data-motivo="<?= htmlspecialchars($entr['entr_motivo']) ?>"
                                 data-status="<?= htmlspecialchars($entr['entr_status']) ?>"
                                 data-hash="<?= htmlspecialchars(strtolower($entr['entr_hash_assinatura'] ?? '')) ?>">
                                
                                <div class="card-entrega-app">
                                    <!-- Header do Card -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <a href="javascript:void(0)" onclick="exibirTermo(<?= htmlspecialchars(json_encode($entr)) ?>)" class="entrega-app-title">
                                                Entrega #<?= sprintf('%05d', (int)$entr['entr_id']) ?>
                                            </a>
                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i:s', strtotime($entr['entr_data_entrega'])) ?>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge-status-pill <?= $statusClass ?>">
                                                <?= htmlspecialchars($entr['entr_status']) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Colaborador -->
                                    <div class="mb-3 p-2 rounded bg-light border dark:bg-slate-800">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; font-size: 14px;">
                                                <i class="bi bi-person-fill"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <div class="fw-bold text-truncate" style="font-size: 13px;">
                                                    Colaborador: <?= htmlspecialchars($entr['fun_nome'] ?? '---') ?>
                                                </div>
                                                <div class="text-muted text-truncate" style="font-size: 11px;">
                                                    <?= htmlspecialchars($entr['fun_departamento'] ?? 'Geral') ?> <?= !empty($entr['fun_cargo']) ? '• ' . htmlspecialchars($entr['fun_cargo']) : '' ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Motivo & Validação -->
                                    <div class="row g-2 mb-3" style="font-size: 12px;">
                                        <div class="col-6">
                                            <span class="text-muted d-block" style="font-size: 10px;">MOTIVO:</span>
                                            <span class="badge bg-secondary bg-opacity-15 text-body border-0 px-2 py-1" style="font-size: 11px;">
                                                <i class="bi bi-tag me-1"></i><?= htmlspecialchars($entr['entr_motivo']) ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <span class="text-muted d-block" style="font-size: 10px;">ASSINATURA / PIN:</span>
                                            <span class="fw-semibold text-<?= $validacaoSenha === 'VALIDADA' ? 'success' : 'warning' ?>" style="font-size: 11px;">
                                                <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($validacaoSenha) ?>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- EPIs Fornecidos -->
                                    <div class="mb-3">
                                        <span class="text-muted d-block mb-1" style="font-size: 10px; font-weight: 700;">EPIS FORNECIDOS (<?= $totalItens ?>):</span>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($entr['itens'] as $item): ?>
                                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle py-1 px-2" style="font-size: 11px; font-weight: 500;">
                                                    <i class="bi bi-shield-check me-1"></i><?= htmlspecialchars($item['item_epi_nome_snapshot'] ?? 'EPI') ?>
                                                    <span class="opacity-75">(<?= (int)$item['item_quantidade'] ?>x)</span>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Hash SHA-256 (Exatamente como no App Android) -->
                                    <div class="hash-box-app mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="fw-bold text-secondary" style="font-size: 10px;">HASH DA ASSINATURA:</span>
                                            <button type="button" class="btn btn-xs text-primary p-0 border-0" onclick="copiarHash('<?= htmlspecialchars($entr['entr_hash_assinatura'] ?? '') ?>', this)" title="Copiar Hash">
                                                <i class="bi bi-clipboard me-1"></i>Copiar
                                            </button>
                                        </div>
                                        <div class="text-truncate" style="font-family: monospace; font-size: 10px; opacity: 0.9;" title="<?= htmlspecialchars($entr['entr_hash_assinatura'] ?? '') ?>">
                                            Hash: <?= htmlspecialchars($hashShort) ?>
                                        </div>
                                    </div>

                                    <!-- Ação principal -->
                                    <button type="button" class="btn btn-sm btn-outline-primary w-100 rounded-3" onclick="exibirTermo(<?= htmlspecialchars(json_encode($entr)) ?>)">
                                        <i class="bi bi-file-earmark-text me-1"></i> Ver Recibo de Assinatura
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- VISÃO 2: TABELA TRADICIONAL -->
            <div id="visao-tabela-container" class="table-responsive-custom" style="display: none;">
                <table class="table-custom" id="tabela-entregas">
                    <thead>
                        <tr>
                            <th>Cód / Data</th>
                            <th>Colaborador</th>
                            <th>EPIs Fornecidos</th>
                            <th>Qtd Itens</th>
                            <th>Motivo</th>
                            <th>Assinatura</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entregas)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum registro de entrega de EPI localizado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entregas as $entr): ?>
                                <?php
                                $statusClass = strtolower($entr['entr_status']);
                                $validacaoSenha = $entr['entr_validacao_senha'] ?? 'PENDENTE';
                                $validacaoClass = ($validacaoSenha === 'VALIDADA') ? 'ativo' : 'inativo';
                                
                                $itensNomes = [];
                                $totalItens = 0;
                                foreach ($entr['itens'] as $item) {
                                    $itensNomes[] = ($item['item_epi_nome_snapshot'] ?? 'EPI') . " (Lote: " . ($item['item_numero_lote'] ?? 'N/A') . ")";
                                    $totalItens += (int)$item['item_quantidade'];
                                }
                                $itensString = implode(', ', $itensNomes);
                                ?>
                                <tr class="entrega-item-wrapper entrega-row" 
                                    data-id="<?= htmlspecialchars((string)$entr['entr_id']) ?>"
                                    data-nome="<?= htmlspecialchars(strtolower($entr['fun_nome'] ?? '')) ?>"
                                    data-epis="<?= htmlspecialchars(strtolower($itensString)) ?>"
                                    data-motivo="<?= htmlspecialchars($entr['entr_motivo']) ?>"
                                    data-status="<?= htmlspecialchars($entr['entr_status']) ?>"
                                    data-hash="<?= htmlspecialchars(strtolower($entr['entr_hash_assinatura'] ?? '')) ?>">
                                    
                                    <td>
                                        <div class="fw-bold">#<?= sprintf('%05d', (int)$entr['entr_id']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= date('d/m/Y H:i', strtotime($entr['entr_data_entrega'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($entr['fun_nome'] ?? '---') ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($entr['fun_departamento'] ?? 'Setor') ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 320px; font-size: 13px;" title="<?= htmlspecialchars($itensString) ?>">
                                            <?= htmlspecialchars($itensString) ?>
                                        </div>
                                    </td>
                                    <td class="fw-medium text-center"><?= $totalItens ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($entr['entr_motivo']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $validacaoClass ?>"><i class="bi bi-fingerprint"></i> <?= htmlspecialchars($validacaoSenha) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($entr['entr_status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border py-1 px-2" onclick="exibirTermo(<?= htmlspecialchars(json_encode($entr)) ?>)" title="Ver Recibo de Assinatura">
                                            <i class="bi bi-file-earmark-text"></i> Termo
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Termo de Ciência e Assinatura Eletrônica -->
<div class="modal fade" id="modalTermo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-lock2 me-2"></i>Recibo de Entrega Eletrônica de EPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light" id="area-impressao-termo">
                <div class="card p-4 border shadow-sm bg-white" style="font-size: 13px; line-height: 1.6;">
                    
                    <!-- Cabeçalho Termo -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--color-primary);">Ficha de Entrega de EPI nº <span id="termo-id"></span></h5>
                            <span class="text-muted" style="font-size: 11px;">Emitido em: <span id="termo-data"></span></span>
                        </div>
                        <div class="text-end" style="font-size: 11px;">
                            <span class="status-badge ativo"><i class="bi bi-check-circle-fill"></i> ASSINADO DIGITALMENTE</span>
                        </div>
                    </div>

                    <!-- Dados do Funcionário -->
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-fill me-1"></i>Dados do Receptor</h6>
                    <div class="row g-2 mb-4 border p-3 rounded bg-light">
                        <div class="col-md-6"><strong>Colaborador:</strong> <span id="termo-nome"></span></div>
                        <div class="col-md-6"><strong>CPF:</strong> <span id="termo-cpf"></span></div>
                        <div class="col-md-6"><strong>Cargo:</strong> <span id="termo-cargo"></span></div>
                        <div class="col-md-6"><strong>Setor:</strong> <span id="termo-setor"></span></div>
                    </div>

                    <!-- Tabela de Itens Recebidos -->
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-seam-fill me-1"></i>Equipamentos Fornecidos</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>EPI / Classificação</th>
                                    <th>Fabricante</th>
                                    <th>C.A.</th>
                                    <th>Validade C.A.</th>
                                    <th>Qtd</th>
                                    <th>Tamanho</th>
                                    <th>Lote</th>
                                </tr>
                            </thead>
                            <tbody id="termo-tabela-itens">
                                <!-- Dinâmico -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Texto do Termo -->
                    <h6 class="fw-bold mb-2 text-secondary"><i class="bi bi-journal-text me-1"></i>Declaração de Ciência e Compromisso</h6>
                    <div class="border p-3 rounded mb-4 text-muted bg-light" style="max-height: 180px; overflow-y: auto; text-align: justify; font-size: 12px;" id="termo-texto">
                        <!-- Texto do Snapshot -->
                    </div>

                    <!-- Validação da Assinatura -->
                    <h6 class="fw-bold mb-2 text-secondary"><i class="bi bi-fingerprint me-1"></i>Dados da Assinatura Eletrônica (Auditoria Judicial)</h6>
                    <div class="border p-3 rounded bg-light" style="font-family: monospace; font-size: 11px;">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Método de Validação:</span>
                            <span class="fw-semibold">PIN Eletrônico (Senha Pessoal)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Status da Validação:</span>
                            <span class="fw-semibold text-success">SENHA VALIDADA NO SERVIDOR</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Assinado por (Usuário Responsável):</span>
                            <span class="fw-semibold" id="termo-responsavel"></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Data/Hora do Aceite Eletrônico:</span>
                            <span class="fw-semibold" id="termo-data-aceite"></span>
                        </div>
                        <div class="py-1">
                            <span class="text-muted d-block mb-1">Hash da Assinatura de Integridade (SHA-256):</span>
                            <span class="fw-bold text-color-primary text-break" id="termo-hash"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" onclick="imprimirTermo()"><i class="bi bi-printer me-1"></i> Imprimir Termo</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initBuscaEFiltros();
    
    // Recupera a preferência salva de visualização (Cards por padrão)
    const visaoSalva = localStorage.getItem('visao-entregas') || 'cards';
    alternarVisaoEntregas(visaoSalva);
});

/**
 * Alterna entre visão em Cards (Estilo App) e Tabela
 */
function alternarVisaoEntregas(modo) {
    const containerCards = document.getElementById('visao-cards-container');
    const containerTabela = document.getElementById('visao-tabela-container');
    const btnCards = document.getElementById('btn-view-cards');
    const btnTabela = document.getElementById('btn-view-table');

    if (modo === 'tabela') {
        containerCards.style.display = 'none';
        containerTabela.style.display = 'block';
        btnCards.classList.remove('active', 'btn-primary');
        btnCards.classList.add('btn-outline-primary');
        btnTabela.classList.remove('btn-outline-primary');
        btnTabela.classList.add('active', 'btn-primary');
        localStorage.setItem('visao-entregas', 'tabela');
    } else {
        containerCards.style.display = 'block';
        containerTabela.style.display = 'none';
        btnTabela.classList.remove('active', 'btn-primary');
        btnTabela.classList.add('btn-outline-primary');
        btnCards.classList.remove('btn-outline-primary');
        btnCards.classList.add('active', 'btn-primary');
        localStorage.setItem('visao-entregas', 'cards');
    }
}

/**
 * Filtros em tempo real no front-end para Cards e Tabela
 */
function initBuscaEFiltros() {
    const busca = document.getElementById('busca-input');
    const filtroMotivo = document.getElementById('filtro-motivo');
    const filtroStatus = document.getElementById('filtro-status');
    const items = document.querySelectorAll('.entrega-item-wrapper');
    
    function aplicarFiltros() {
        const query = busca.value.toLowerCase().trim();
        const motivo = filtroMotivo.value;
        const status = filtroStatus.value;
        
        items.forEach(item => {
            const rId = item.getAttribute('data-id') || '';
            const rNome = item.getAttribute('data-nome') || '';
            const rEpis = item.getAttribute('data-epis') || '';
            const rMotivo = item.getAttribute('data-motivo') || '';
            const rStatus = item.getAttribute('data-status') || '';
            const rHash = item.getAttribute('data-hash') || '';
            
            const bateBusca = rId.includes(query) || rNome.includes(query) || rEpis.includes(query) || rHash.includes(query);
            const bateMotivo = (motivo === '' || rMotivo === motivo);
            const bateStatus = (status === '' || rStatus === status);
            
            if (bateBusca && bateMotivo && bateStatus) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    }
    
    busca.addEventListener('input', aplicarFiltros);
    filtroMotivo.addEventListener('change', aplicarFiltros);
    filtroStatus.addEventListener('change', aplicarFiltros);
}

/**
 * Copiar Hash SHA-256 para a área de transferência
 */
function copiarHash(hash, btn) {
    if (!hash) return;
    navigator.clipboard.writeText(hash).then(() => {
        mostrarToast('Hash de integridade copiado para a área de transferência!');
    }).catch(() => {
        const input = document.createElement('input');
        input.value = hash;
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        mostrarToast('Hash copiado para a área de transferência!');
    });
}

function mostrarToast(msg) {
    const toastEl = document.getElementById('toastNotificacao');
    const msgEl = document.getElementById('toastMensagem');
    if (toastEl && msgEl) {
        msgEl.innerHTML = `<i class="bi bi-check-circle me-2"></i> ${msg}`;
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        toast.show();
    }
}

/**
 * Preenche o modal de recibo com os metadados e o texto do termo assinado
 */
function exibirTermo(entr) {
    document.getElementById('termo-id').innerText = entr.entr_id;
    document.getElementById('termo-data').innerText = new Date(entr.entr_data_entrega).toLocaleString('pt-BR');
    document.getElementById('termo-nome').innerText = entr.fun_nome || '---';
    
    let cpf = entr.fun_cpf || '';
    if (cpf.length === 11) {
        cpf = `***.***.***-${cpf.slice(-2)}`;
    }
    document.getElementById('termo-cpf').innerText = cpf;
    document.getElementById('termo-cargo').innerText = entr.fun_cargo || '---';
    document.getElementById('termo-setor').innerText = entr.fun_departamento || '---';
    
    let htmlItens = '';
    entr.itens.forEach(item => {
        let vencCa = '---';
        if (item.item_epi_validade_ca_snapshot) {
            const parts = item.item_epi_validade_ca_snapshot.split('-');
            vencCa = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : item.item_epi_validade_ca_snapshot;
        }
        
        htmlItens += `
            <tr>
                <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                <td>${item.item_epi_fabricante_snapshot || '---'}</td>
                <td>${item.item_epi_ca_snapshot || '---'}</td>
                <td>${vencCa}</td>
                <td>${item.item_quantidade}</td>
                <td>${item.item_tamanho || '---'}</td>
                <td>${item.item_numero_lote || '---'}</td>
            </tr>
        `;
    });
    document.getElementById('termo-tabela-itens').innerHTML = htmlItens;
    
    const textoTermo = entr.texto_termo_snapshot || `Declaro estar recebendo, gratuitamente e sem qualquer ônus, os Equipamentos de Proteção Individual — EPIs discriminados neste termo. Declaro estar ciente da obrigatoriedade de sua utilização durante a execução das atividades para as quais foram fornecidos, conforme as orientações da empresa e a legislação aplicável.`;
    document.getElementById('termo-texto').innerText = textoTermo;
    
    document.getElementById('termo-responsavel').innerText = `${entr.usu_login || 'Sistema'} (Perfil: ${entr.usu_perfil || 'Operador'})`;
    document.getElementById('termo-data-aceite').innerText = new Date(entr.data_hora_aceite || entr.entr_data_entrega).toLocaleString('pt-BR');
    document.getElementById('termo-hash').innerText = entr.entr_hash_assinatura || 'N/A';

    new bootstrap.Modal(document.getElementById('modalTermo')).show();
}

function imprimirTermo() {
    const area = document.getElementById('area-impressao-termo').innerHTML;
    const janela = window.open('', '_blank', 'width=800,height=600');
    
    janela.document.write('<html><head><title>Imprimir Termo de EPI</title>');
    janela.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
    janela.document.write('<style>body { font-family: sans-serif; padding: 20px; }</style>');
    janela.document.write('</head><body>');
    janela.document.write(area);
    janela.document.write('</body></html>');
    
    janela.document.close();
    janela.focus();
    
    setTimeout(() => {
        janela.print();
        janela.close();
    }, 500);
}



/**
 * Handler disparado pela barra lateral ao clicar no botão Histórico
 */
function executarAcaoSubmenuEntrega(acao) {
    if (acao === 'nova_entrega' || acao === 'nova') {
        window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
    } else if (acao === 'devolucao' || acao === 'devolucoes') {
        window.location.href = '<?= APP_ROOT ?>pages/devolucoes.php';
    } else {
        if (window.location.pathname.indexOf('entregas.php') !== -1) {
            const busca = document.getElementById('busca-input');
            const filtroMotivo = document.getElementById('filtro-motivo');
            const filtroStatus = document.getElementById('filtro-status');
            if (busca) busca.value = '';
            if (filtroMotivo) filtroMotivo.value = '';
            if (filtroStatus) filtroStatus.value = '';
            
            const event = new Event('input');
            if (busca) busca.dispatchEvent(event);

            window.scrollTo({ top: 0, behavior: 'smooth' });
            mostrarToast('Histórico de entregas ativado.');
        } else {
            window.location.href = '<?= APP_ROOT ?>pages/entregas.php';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
