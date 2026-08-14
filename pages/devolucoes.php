<?php
declare(strict_types=1);

$page_title = 'Controle de Devoluções';
$active_menu = 'devolucoes';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;
$funcionarios = [];

// 1. Processa registro de devolução (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_devolucao') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $status = $_POST['item_status'] ?? 'DEVOLVIDO';
    $motivo = trim($_POST['item_devolucao_motivo'] ?? '');
    $condicao = $_POST['item_devolucao_condicao'] ?? 'USADO';
    $destino = $_POST['item_devolucao_destino'] ?? 'DESCARTE';
    $obs = trim($_POST['item_devolucao_obs'] ?? '');

    try {
        $response = $api->post('devolucoes', [
            'item_id' => $itemId,
            'item_status' => $status,
            'item_devolucao_motivo' => $motivo,
            'item_devolucao_condicao' => $condicao,
            'item_devolucao_destino' => $destino,
            'item_devolucao_obs' => $obs
        ]);

        if (isset($response['success']) && $response['success']) {
            $sucesso = 'Devolução do EPI registrada com sucesso no sistema!';
        } else {
            $erro = $response['message'] ?? 'Falha ao registrar devolução do EPI.';
        }
    } catch (Exception $e) {
        $erro = 'Erro de conexão: ' . $e->getMessage();
    }
}

// 2. Carrega lista de funcionários para seleção
try {
    $funcRes = $api->get('funcionarios');
    if (isset($funcRes['success']) && $funcRes['success']) {
        $funcionarios = array_filter($funcRes['data'], function($f) {
            return $f['fun_situacao'] === 'ATIVO'; // Apenas funcionários ativos devolvem EPIs
        });
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de colaboradores: ' . $e->getMessage();
}

$podeDevolver = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Controle de Devoluções</h3>
                <p class="text-muted">Gerencie a devolução, substituição, extravio e condições de retorno dos EPIs dos funcionários.</p>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== null): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div><?= htmlspecialchars($sucesso) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Coluna Esquerda: Seleção de Funcionário -->
            <div class="col-lg-4">
                <div class="card-custom h-100">
                    <h5 class="fw-bold mb-3" style="color: var(--color-primary);"><i class="bi bi-people me-2"></i>Selecione o Colaborador</h5>
                    <div class="mb-3">
                        <input type="text" id="busca-func" class="form-control" placeholder="Filtrar por nome ou CPF...">
                    </div>
                    
                    <div class="list-group overflow-y-auto" style="max-height: 480px;" id="lista-func-devolucao">
                        <?php if (empty($funcionarios)): ?>
                            <div class="text-muted text-center py-3">Sem funcionários cadastrados.</div>
                        <?php else: ?>
                            <?php foreach ($funcionarios as $f): ?>
                                <?php
                                $cpf = $f['fun_cpf'];
                                if (strlen($cpf) === 11) {
                                    $cpf = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                                }
                                ?>
                                <button type="button" class="list-group-item list-group-item-action border-slate py-3 func-btn" 
                                        onclick="selecionarFuncionario(<?= $f['fun_id'] ?>, '<?= htmlspecialchars($f['fun_nome']) ?>')"
                                        data-nome="<?= htmlspecialchars(strtolower($f['fun_nome'])) ?>"
                                        data-cpf="<?= htmlspecialchars($f['fun_cpf']) ?>">
                                    <div class="fw-semibold text-color-primary"><?= htmlspecialchars($f['fun_nome']) ?></div>
                                    <small class="text-muted d-block"><?= htmlspecialchars($f['fun_cargo']) ?> — CPF: <?= htmlspecialchars($cpf) ?></small>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Controle Operacional de Posse e Histórico -->
            <div class="col-lg-8">
                <div class="card-custom h-100" id="painel-posse-vazio">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                        <i class="bi bi-arrow-left-circle" style="font-size: 48px;"></i>
                        <h5 class="mt-3 fw-bold">Nenhum funcionário selecionado</h5>
                        <p class="text-center" style="max-width: 300px;">Selecione um colaborador da lista à esquerda para carregar os EPIs sob sua posse ou consultar seu histórico.</p>
                    </div>
                </div>

                <div class="card-custom h-100 d-none" id="painel-posse-ativo">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--color-primary);" id="nome-func-selecionado">Nome do Colaborador</h5>
                            <span class="text-muted" style="font-size: 12px;">Posse e devolução de equipamentos</span>
                        </div>
                    </div>

                    <!-- Abas de Posse vs Histórico -->
                    <ul class="nav nav-tabs" id="posseAbas" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="posse-atual-tab" data-bs-toggle="tab" data-bs-target="#tab-posse-atual" type="button" role="tab"><i class="bi bi-box-seam me-1"></i>EPIs em Posse Atualmente</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico-devolucoes" type="button" role="tab"><i class="bi bi-clock-history me-1"></i>Histórico de Retornos</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="posseAbasConteudo">
                        <!-- Aba 1: Em Posse (Permite Devolver) -->
                        <div class="tab-pane fade show active" id="tab-posse-atual" role="tabpanel">
                            <div class="table-responsive" style="max-height: 380px;">
                                <table class="table table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>EPI / C.A.</th>
                                            <th>Qtd</th>
                                            <th>Tamanho</th>
                                            <th>Lote</th>
                                            <th>Entregue em</th>
                                            <th class="text-end">Ação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lista-posse-atual">
                                        <!-- Dinâmico via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Aba 2: Histórico de Devoluções do Funcionário -->
                        <div class="tab-pane fade" id="tab-historico-devolucoes" role="tabpanel">
                            <div class="table-responsive" style="max-height: 380px;">
                                <table class="table table-hover border">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Data Retorno</th>
                                            <th>EPI / C.A.</th>
                                            <th>Qtd</th>
                                            <th>Motivo</th>
                                            <th>Situação</th>
                                        </tr>
                                    </thead>
                                    <tbody id="lista-historico-devolucoes">
                                        <!-- Dinâmico via AJAX -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Efetuar Devolução -->
<div class="modal fade" id="modalDevolverItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="devolucoes.php">
            <input type="hidden" name="acao" value="registrar_devolucao">
            <input type="hidden" id="dev-item-id" name="item_id">
            
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-arrow-counterclockwise me-2"></i>Registrar Devolução de EPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">EPI selecionado:</label>
                    <input type="text" class="form-control bg-light fw-semibold" id="dev-epi-nome" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Tipo de Retorno *</label>
                    <select class="form-select" name="item_status" id="dev-status">
                        <option value="DEVOLVIDO">Devolução física ao almoxarifado (DEVOLVIDO)</option>
                        <option value="EXTRAVIADO">Extravio / Perda do colaborador (EXTRAVIADO)</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Motivo do Retorno *</label>
                    <input type="text" class="form-control" name="item_devolucao_motivo" placeholder="Ex: Fim da vida útil / Desgaste natural" required>
                </div>
                
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Condição do EPI *</label>
                        <select class="form-select" name="item_devolucao_condicao">
                            <option value="USADO">Usado (Descarte)</option>
                            <option value="DANIFICADO">Danificado / Avariado</option>
                            <option value="NOVO">Novo (Reaproveitável)</option>
                        </select>
                    </div>
                    
                    <div class="col-6 mb-3">
                        <label class="form-label">Destino do Item *</label>
                        <select class="form-select" name="item_devolucao_destino">
                            <option value="DESCARTE">Coleta / Descarte Ecológico</option>
                            <option value="HIGIENIZACAO">Higienização e Manutenção</option>
                            <option value="ESTOQUE">Retorno ao Estoque Ativo</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Observações Complementares</label>
                    <textarea class="form-control" name="item_devolucao_obs" rows="2" placeholder="Descreva particularidades do estado do item..."></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Devolução</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';
const PODE_DEVOLVER = <?= $podeDevolver ? 'true' : 'false' ?>;

document.addEventListener('DOMContentLoaded', function() {
    initBuscaFunc();
});

/**
 * Filtro de funcionários na lista lateral
 */
function initBuscaFunc() {
    const busca = document.getElementById('busca-func');
    const buttons = document.querySelectorAll('.func-btn');
    
    busca.addEventListener('input', function() {
        const query = busca.value.toLowerCase().trim();
        
        buttons.forEach(btn => {
            const nome = btn.getAttribute('data-nome');
            const cpf = btn.getAttribute('data-cpf');
            
            if (nome.includes(query) || cpf.includes(query)) {
                btn.style.display = '';
            } else {
                btn.style.display = 'none';
            }
        });
    });
}

/**
 * Ativa o painel de posse do colaborador selecionado e dispara as chamadas AJAX
 */
function selecionarFuncionario(funId, nome) {
    // Altera classe ativa do botão lateral
    document.querySelectorAll('.func-btn').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    // Altera painéis visíveis
    document.getElementById('painel-posse-vazio').classList.add('d-none');
    
    const painelAtivo = document.getElementById('painel-posse-ativo');
    painelAtivo.classList.remove('d-none');
    
    document.getElementById('nome-func-selecionado').innerText = nome;

    // Dispara chamadas de carregamento de dados
    carregarEpiPosse(funId);
    carregarHistoricoDevolucoes(funId);
}

/**
 * Carrega itens que estão atualmente com o funcionário (ENTREGUE)
 */
function carregarEpiPosse(funId) {
    const tbody = document.getElementById('lista-posse-atual');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Buscando EPIs em posse...</td></tr>';

    fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                let html = '';
                let totalPosse = 0;
                
                res.data.forEach(entr => {
                    // Ignora entregas canceladas
                    if (entr.entr_status !== 'FINALIZADA') return;

                    const dataEntr = new Date(entr.entr_data_entrega).toLocaleDateString('pt-BR');
                    
                    entr.itens.forEach(item => {
                        // Apenas itens que estão em posse do colaborador (status ENTREGUE)
                        if (item.item_status === 'ENTREGUE') {
                            totalPosse++;
                            
                            let acaoHtml = '<span class="text-muted" style="font-size:12px;">Sem Permissão</span>';
                            if (PODE_DEVOLVER) {
                                acaoHtml = `
                                    <button class="btn btn-sm btn-outline-danger py-1 px-2" 
                                            onclick="prepararDevolucao(${item.item_id}, '${(item.item_epi_nome_snapshot || '').replace(/'/g, "\\'")}')">
                                        <i class="bi bi-arrow-counterclockwise"></i> Devolver
                                    </button>
                                `;
                            }

                            html += `
                                <tr>
                                    <td>
                                        <div class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</div>
                                        <div class="text-muted" style="font-size: 11px;">C.A. ${item.item_epi_ca_snapshot || '---'}</div>
                                    </td>
                                    <td class="fw-medium">${item.item_quantidade}</td>
                                    <td>${item.item_tamanho || '---'}</td>
                                    <td class="text-muted">${item.item_numero_lote || '---'}</td>
                                    <td>${dataEntr}</td>
                                    <td class="text-end">${acaoHtml}</td>
                                </tr>
                            `;
                        }
                    });
                });

                tbody.innerHTML = totalPosse > 0 ? html : '<tr><td colspan="6" class="text-center text-muted py-3">O colaborador não possui nenhum EPI sob sua posse no momento.</td></tr>';
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">O colaborador não possui EPIs em posse.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Erro de conexão ao carregar EPIs.</td></tr>';
        });
}

/**
 * Carrega histórico de devoluções realizadas pelo funcionário
 */
function carregarHistoricoDevolucoes(funId) {
    const tbody = document.getElementById('lista-historico-devolucoes');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Buscando histórico...</td></tr>';

    fetch(`${PROXY_URL}?route=devolucoes/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data && res.data.length > 0) {
                let html = '';
                
                res.data.forEach(dev => {
                    const dataDev = new Date(dev.item_data_devolucao).toLocaleDateString('pt-BR');
                    const statusClass = dev.item_status === 'EXTRAVIADO' ? 'inativo' : 'ativo';
                    
                    html += `
                        <tr>
                            <td>${dataDev}</td>
                            <td>
                                <div class="fw-semibold">${dev.epi_nome}</div>
                                <div class="text-muted" style="font-size: 11px;">C.A. ${dev.epi_ca || 'Isento'}</div>
                            </td>
                            <td class="fw-medium">${dev.item_quantidade}</td>
                            <td class="text-muted" style="font-size:12px;">${dev.item_devolucao_motivo || 'Sem motivo registrado'}</td>
                            <td>
                                <span class="status-badge ${statusClass}">${dev.item_status}</span>
                            </td>
                        </tr>
                    `;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Nenhum registro de devolução ou extravio encontrado para este colaborador.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Erro ao carregar histórico de retornos.</td></tr>';
        });
}

/**
 * Prepara e abre o modal de devolução
 */
function prepararDevolucao(itemId, epiNome) {
    document.getElementById('dev-item-id').value = itemId;
    document.getElementById('dev-epi-nome').value = epiNome;
    document.getElementById('dev-status').value = 'DEVOLVIDO';
    
    new bootstrap.Modal(document.getElementById('modalDevolverItem')).show();
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
