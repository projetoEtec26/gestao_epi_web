<?php
declare(strict_types=1);

$page_title = 'Entregas & Devoluções';
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

// 1. Processa registro de devolução (POST) - Suporta devolução individual ou em lote (múltiplos EPIs)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'registrar_devolucao') {
    $itemIdsRaw = $_POST['item_ids'] ?? ($_POST['item_id'] ?? []);
    if (!is_array($itemIdsRaw)) {
        $itemIdsRaw = array_filter(explode(',', (string)$itemIdsRaw));
    }
    
    $status = $_POST['item_status'] ?? 'DEVOLVIDO';
    $motivo = trim($_POST['item_devolucao_motivo'] ?? '');
    $condicao = $_POST['item_devolucao_condicao'] ?? 'USADO';
    $destino = $_POST['item_devolucao_destino'] ?? 'DESCARTE';
    $obs = trim($_POST['item_devolucao_obs'] ?? '');
    
    if (empty($itemIdsRaw)) {
        $erro = 'Por favor, selecione ao menos um EPI para realizar a devolução.';
    } elseif (empty($motivo)) {
        $erro = 'O motivo da devolução é obrigatório.';
    } else {
        $sucessoCount = 0;
        $errosList = [];

        foreach ($itemIdsRaw as $id) {
            $itemId = (int)$id;
            if ($itemId <= 0) continue;

            $opId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', 
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), 
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, 
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );

            try {
                $response = $api->post('devolucoes', [
                    'item_id'                => $itemId,
                    'item_status'            => $status,
                    'item_devolucao_motivo'  => $motivo,
                    'item_devolucao_condicao'=> $condicao,
                    'item_devolucao_destino' => $destino,
                    'item_devolucao_obs'     => $obs,
                    'client_operation_id'    => $opId,
                    'origem'                 => 'WEB_CONTINGENCIA'
                ]);

                if (isset($response['success']) && $response['success']) {
                    $sucessoCount++;
                } else {
                    $errosList[] = $response['message'] ?? "Falha ao devolver o item #{$itemId}.";
                }
            } catch (Exception $e) {
                $errosList[] = "Erro de conexão no item #{$itemId}: " . $e->getMessage();
            }
        }

        if ($sucessoCount > 0) {
            $sucesso = "Devolução de {$sucessoCount} EPI(s) registrada(s) com sucesso no sistema!";
        }
        if (!empty($errosList)) {
            $erro = implode(' | ', $errosList);
        }
    }
}

// 2. Carrega lista de funcionários para seleção
try {
    $funcRes = $api->get('funcionarios');
    if (isset($funcRes['success']) && $funcRes['success']) {
        $funcionarios = array_filter($funcRes['data'], function($f) {
            return $f['fun_situacao'] === 'ATIVO';
        });
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de colaboradores: ' . $e->getMessage();
}

$podeDevolver = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);
?>

<style>
/* Estilização Web oficial (Light Mode Design System) conforme modelo da aplicação Web */
.web-card-container {
    background-color: var(--color-card-bg, #ffffff);
    border: 1px solid var(--color-border, #e2e8f0);
    border-radius: 12px;
    padding: 24px;
    box-shadow: var(--shadow-sm, 0 1px 3px rgba(0,0,0,0.05));
}

.web-search-box {
    display: flex;
    align-items: center;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 10px;
    padding: 0 14px;
    transition: all 0.2s ease;
}

.web-search-box:focus-within {
    border-color: var(--color-primary, #2563eb);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.web-search-box input {
    border: none;
    outline: none;
    background: transparent;
    padding: 11px 0;
    font-size: 14px;
    color: var(--color-text-primary, #0f172a);
    width: 100%;
}

.web-epi-row {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: all 0.15s ease;
    cursor: pointer;
    user-select: none;
}

.web-epi-row:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
}

.web-epi-icon-box {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #eff6ff;
    color: var(--color-primary, #2563eb);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.web-checkbox {
    width: 20px;
    height: 20px;
    accent-color: var(--color-primary, #2563eb);
    cursor: pointer;
    flex-shrink: 0;
}

.btn-web-primary {
    background-color: var(--color-primary, #2563eb);
    color: #ffffff;
    font-weight: 600;
    font-size: 14px;
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    transition: all 0.2s ease;
}

.btn-web-primary:hover {
    background-color: var(--color-primary-hover, #1d4ed8);
    color: #ffffff;
    box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
}

.btn-web-primary:disabled {
    background-color: #94a3b8;
    color: #ffffff;
    box-shadow: none;
    cursor: not-allowed;
}

.web-dd-item {
    padding: 10px 14px;
    cursor: pointer;
    border-bottom: 1px solid #f1f5f9;
    color: #0f172a;
    font-size: 13px;
    transition: background 0.12s;
}

.web-dd-item:hover {
    background-color: #f1f5f9;
}
</style>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <!-- Top Bar Header (Padrão Web) -->
        <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap flex-md-nowrap">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Entregas &amp; Devoluções</h3>
                <p class="text-muted mb-0">Gerencie a devolução, substituição e condições de retorno dos EPIs dos colaboradores.</p>
            </div>
            <div class="d-flex align-items-center gap-2 text-nowrap flex-nowrap">
                <div class="btn-group-toggle-view" role="group">
                    <a href="entregas.php" class="btn btn-view">
                        <i class="bi bi-clock-history me-1"></i> Histórico
                    </a>
                    <a href="devolucoes.php" class="btn btn-view active">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Devolução
                    </a>
                </div>
                <?php if ($podeDevolver): ?>
                    <a href="nova_entrega.php" class="btn btn-primary text-nowrap px-3 py-2 fw-semibold rounded-3 shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Nova Entrega
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== null): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($sucesso) ?></div>
            </div>
        <?php endif; ?>

        <form method="POST" action="devolucoes.php" id="form-devolucao-web">
            <input type="hidden" name="acao" value="registrar_devolucao">
            <input type="hidden" name="fun_id_selecionado" id="fun_id_selecionado" value="">

            <div class="row g-4">
                <!-- COLUNA ESQUERDA: Seleção de Funcionário & Lista de EPIs em Posse -->
                <div class="col-lg-6">
                    <div class="web-card-container h-100 d-flex flex-column">
                        <h5 class="fw-bold mb-3" style="color: var(--color-primary);">
                            <i class="bi bi-person-check me-2"></i>Devolução de EPI
                        </h5>

                        <!-- CAMPO DE BUSCA DE FUNCIONÁRIO COM AUTOCOMPLETE -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold" style="font-size: 13px; color: #475569;">
                                Buscar Colaborador (Tempo Real) <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative" id="wrapper-busca-colab">
                                <div class="web-search-box">
                                    <i class="bi bi-search text-primary me-2" style="font-size: 15px;"></i>
                                    <input type="text"
                                           id="input-busca-colaborador"
                                           autocomplete="off"
                                           placeholder="Digite nome (ex: Ron...), CPF ou cargo..."
                                           oninput="buscarColaborador(this.value)"
                                           onkeydown="teclarBusca(event)">
                                    <button type="button" 
                                            id="btn-limpar" 
                                            onclick="limparBusca()" 
                                            style="display:none;background:none;border:none;color:#94a3b8;font-size:18px;cursor:pointer;padding:0 4px;">
                                        &times;
                                    </button>
                                </div>
                                
                                <!-- Dropdown Autocomplete -->
                                <div id="dropdown-colab" style="
                                    display:none;
                                    position:absolute;top:calc(100% + 4px);left:0;right:0;
                                    background:#ffffff;border:1.5px solid #e2e8f0;border-radius:10px;
                                    box-shadow:0 10px 25px rgba(0,0,0,0.1);
                                    overflow:hidden;max-height:280px;overflow-y:auto;z-index:99999;">
                                </div>
                            </div>
                        </div>

                        <!-- SEÇÃO DE COLABORADOR SELECIONADO -->
                        <div class="d-none border-bottom pb-2 mb-3" id="header-func-info">
                            <div class="fw-bold fs-6" style="color: var(--color-primary);" id="display-func-nome">
                                Funcionário: Selecionado
                            </div>
                            <div class="text-muted" style="font-size: 12px;">
                                Marque os EPIs que estão sendo devolvidos pelo colaborador:
                            </div>
                        </div>

                        <div class="text-center py-5 text-muted flex-grow-1 d-flex flex-column align-items-center justify-content-center" id="msg-selecione-func">
                            <i class="bi bi-person-bounding-box mb-2" style="font-size: 40px; color: #cbd5e1;"></i>
                            <div class="fw-semibold">Nenhum colaborador selecionado</div>
                            <div style="font-size: 13px; max-width: 280px;">Pesquise um funcionário no campo acima para carregar a lista de EPIs sob sua posse.</div>
                        </div>

                        <!-- LISTA DE EPIs EM POSSE COM CHECKBOXES -->
                        <div class="overflow-y-auto d-none flex-grow-1 pe-1" id="container-epi-list" style="max-height: 360px;">
                            <!-- Injetado via JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- COLUNA DIREITA: Formulário de Devolução & Parâmetros -->
                <div class="col-lg-6">
                    <div class="web-card-container h-100 d-flex flex-column justify-content-between" id="campos-formulario-devolucao">
                        <div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 13px;">
                                    Motivo do Retorno <span class="text-danger">*</span>
                                </label>
                                <select class="form-select py-2" name="item_devolucao_motivo" id="select-motivo" required style="font-size: 13.5px; border-radius: 8px;">
                                    <option value="Devolução física ao almoxarifado (DEVOLVIDO)" selected>Devolução física ao almoxarifado (DEVOLVIDO)</option>
                                    <option value="Fim da vida útil / Desgaste natural">Fim da vida útil / Desgaste natural</option>
                                    <option value="Troca periódica de EPI">Troca periódica de EPI</option>
                                    <option value="Danificado / Avariado">Danificado / Avariado</option>
                                    <option value="Extravio / Perda do colaborador (EXTRAVIADO)">Extravio / Perda do colaborador (EXTRAVIADO)</option>
                                    <option value="Demissão / Desligamento">Demissão / Desligamento do funcionário</option>
                                    <option value="Outro motivo">Outro motivo</option>
                                </select>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">
                                        Condição do EPI <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select py-2" name="item_devolucao_condicao" id="select-condicao" style="font-size: 13px; border-radius: 8px;">
                                        <option value="USADO" selected>Usado (Descarte)</option>
                                        <option value="DANIFICADO">Danificado / Avariado</option>
                                        <option value="NOVO">Novo (Reaproveitável)</option>
                                        <option value="BOM_ESTADO">Bom Estado</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">
                                        Destino do Item <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select py-2" name="item_devolucao_destino" id="select-destino" style="font-size: 13px; border-radius: 8px;">
                                        <option value="DESCARTE" selected>Coleta / Descarte Ecológico</option>
                                        <option value="HIGIENIZACAO">Higienização e Manutenção</option>
                                        <option value="ESTOQUE">Retorno ao Estoque Ativo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold" style="font-size: 13px;">
                                    Observações Complementares
                                </label>
                                <textarea class="form-control" name="item_devolucao_obs" rows="2" style="font-size: 13px; border-radius: 8px;" placeholder="Descreva particularidades do estado do item..."></textarea>
                            </div>

                        <!-- BOTÃO DE AÇÃO PRINCIPAL -->
                        <div class="pt-3 border-top text-end">
                            <button type="submit" class="btn-web-primary w-100 py-3" id="btn-submit-devolucao" disabled>
                                <i class="bi bi-check2-circle me-1 fs-5"></i> CONFIRMAR DEVOLUÇÃO
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- SEÇÃO INFERIOR: Histórico Recente de Devoluções -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="web-card-container">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0" style="color: var(--color-primary);">
                            <i class="bi bi-clock-history me-1"></i> Histórico Recente de Retornos do Colaborador
                        </h6>
                    </div>
                    <div class="table-responsive" style="max-height: 280px;">
                        <table class="table table-hover align-middle mb-0 border">
                            <thead class="table-light">
                                <tr style="font-size: 13px;">
                                    <th>Data Retorno</th>
                                    <th>EPI / C.A.</th>
                                    <th>Qtd</th>
                                    <th>Motivo</th>
                                    <th>Situação</th>
                                </tr>
                            </thead>
                            <tbody id="lista-historico-devolucoes">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4" style="font-size: 13px;">
                                        Selecione um colaborador acima para consultar o histórico de devoluções.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';
const PODE_DEVOLVER = <?= $podeDevolver ? 'true' : 'false' ?>;

const LISTA_FUNCIONARIOS = <?= json_encode(array_values(array_map(function($f) {
    return [
        'fun_id'         => (int)$f['fun_id'],
        'fun_nome'       => $f['fun_nome'] ?? '',
        'fun_cpf'        => $f['fun_cpf'] ?? '',
        'fun_matricula'  => $f['fun_matricula'] ?? '',
        'fun_cargo'      => $f['fun_cargo'] ?? '',
        'fun_departamento'=> $f['fun_departamento'] ?? ($f['fun_setor'] ?? '')
    ];
}, $funcionarios)), JSON_UNESCAPED_UNICODE) ?>;

let resultados = [];
let idxFocado = -1;

function normalizar(str) {
    return String(str || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
}
function escapar(str) {
    return String(str || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}
function mascaraCpf(cpf) {
    const d = String(cpf||'').replace(/\D/g,'');
    return d.length===11 ? `${d.slice(0,3)}.***.***-${d.slice(9)}` : (cpf||'---');
}

function obterIconeEpi(nomeEpi) {
    const n = normalizar(nomeEpi);
    if (n.includes('botina') || n.includes('bota') || n.includes('calcado') || n.includes('sapato')) {
        return '<i class="bi bi-boot-fill"></i>';
    }
    if (n.includes('avental') || n.includes('raspa') || n.includes('vestuario') || n.includes('traje') || n.includes('capa')) {
        return '<i class="bi bi-person-badge-fill"></i>';
    }
    if (n.includes('oculos') || n.includes('viseira') || n.includes('mascara') || n.includes('facial')) {
        return '<i class="bi bi-eyeglasses"></i>';
    }
    if (n.includes('luva') || n.includes('protecao')) {
        return '<i class="bi bi-hand-index-thumb-fill"></i>';
    }
    return '<i class="bi bi-shield-fill-check"></i>';
}

function fecharDropdown() {
    const dd = document.getElementById('dropdown-colab');
    if (dd) { dd.style.display='none'; dd.innerHTML=''; }
    idxFocado = -1;
    resultados = [];
}

function buscarColaborador(termo) {
    idxFocado = -1;
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) btnLimpar.style.display = termo.length > 0 ? 'inline-block' : 'none';

    const tn = normalizar(termo);
    const cpfDigits = termo.replace(/\D/g,'');

    if (tn.length < 2) {
        fecharDropdown();
        return;
    }

    resultados = LISTA_FUNCIONARIOS.filter(f => {
        const nome  = normalizar(f.fun_nome);
        const cargo = normalizar(f.fun_cargo);
        const cpf   = String(f.fun_cpf||'').replace(/\D/g,'');
        return nome.includes(tn) || cargo.includes(tn) || (cpfDigits.length > 0 && cpf.includes(cpfDigits));
    });

    renderDropdown(termo);
}

function renderDropdown(termo) {
    const dd = document.getElementById('dropdown-colab');
    if (!dd) return;

    if (resultados.length === 0) {
        dd.innerHTML = `<div style="padding:12px;text-align:center;color:#64748b;font-size:13px;">Nenhum colaborador encontrado com "${escapar(termo)}"</div>`;
        dd.style.display = 'block';
        return;
    }

    let html = '';
    resultados.slice(0, 7).forEach((f, idx) => {
        html += `
            <div class="web-dd-item" id="dd-item-${idx}" 
                 onclick="escolherColaborador(${f.fun_id}, '${f.fun_nome.replace(/'/g,"\\'")}')">
                <div class="fw-semibold text-dark">${escapar(f.fun_nome)}</div>
                <div style="font-size:11px;color:#64748b;">${escapar(f.fun_cargo || 'Sem cargo')} • CPF: ${mascaraCpf(f.fun_cpf)}</div>
            </div>`;
    });

    dd.innerHTML = html;
    dd.style.display = 'block';
}

function teclarBusca(e) {
    const dd = document.getElementById('dropdown-colab');
    if (!dd || dd.style.display === 'none') return;
    if (e.key === 'Enter') {
        e.preventDefault();
        if (resultados.length > 0) {
            escolherColaborador(resultados[0].fun_id, resultados[0].fun_nome);
        }
    } else if (e.key === 'Escape') {
        fecharDropdown();
    }
}

function escolherColaborador(funId, nome) {
    document.getElementById('input-busca-colaborador').value = nome;
    document.getElementById('fun_id_selecionado').value = funId;
    fecharDropdown();
    selecionarFuncionario(funId, nome);
}

function limparBusca() {
    document.getElementById('input-busca-colaborador').value = '';
    document.getElementById('fun_id_selecionado').value = '';
    document.getElementById('btn-limpar').style.display = 'none';
    document.getElementById('header-func-info').classList.add('d-none');
    document.getElementById('container-epi-list').classList.add('d-none');
    document.getElementById('msg-selecione-func').classList.remove('d-none');
    
    document.getElementById('btn-submit-devolucao').disabled = true;

    fecharDropdown();
}

function selecionarFuncionario(funId, nome) {
    document.getElementById('header-func-info').classList.remove('d-none');
    document.getElementById('msg-selecione-func').classList.add('d-none');
    document.getElementById('display-func-nome').innerText = 'Funcionário: ' + nome;

    carregarPosse(funId);
    carregarHistorico(funId);
}

function carregarPosse(funId) {
    const container = document.getElementById('container-epi-list');
    container.classList.remove('d-none');
    container.innerHTML = '<div class="text-center py-4 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Buscando EPIs em posse...</div>';

    fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                let html = '';
                let count = 0;
                res.data.forEach(entr => {
                    if (entr.entr_status !== 'FINALIZADA') return;
                    entr.itens.forEach(item => {
                        if (item.item_status !== 'ENTREGUE') return;
                        count++;
                        const epiNome = item.item_epi_nome_snapshot || 'EPI';
                        const icone = obterIconeEpi(epiNome);
                        const caStr = item.item_epi_ca_snapshot ? `C.A. ${item.item_epi_ca_snapshot}` : 'Sem C.A.';
                        const loteStr = item.item_numero_lote ? ` | Lote: ${item.item_numero_lote}` : '';

                        html += `
                            <label class="web-epi-row">
                                <input type="checkbox" name="item_ids[]" value="${item.item_id}" class="web-checkbox item-checkbox" onchange="atualizarEstadoBotao()">
                                <div class="web-epi-icon-box">${icone}</div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-dark" style="font-size:14px;">${escapar(epiNome)}</div>
                                    <div class="text-muted" style="font-size:12px;">${caStr}${loteStr}</div>
                                </div>
                            </label>`;
                    });
                });

                if (count > 0) {
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-center text-muted py-4">Nenhum EPI atualmente sob posse deste funcionário.</div>';
                    document.getElementById('btn-submit-devolucao').disabled = true;
                }
            } else {
                container.innerHTML = '<div class="text-center text-muted py-4">Nenhum EPI em posse localizado.</div>';
                document.getElementById('btn-submit-devolucao').disabled = true;
            }
            atualizarEstadoBotao();
        })
        .catch(() => {
            container.innerHTML = '<div class="text-center text-danger py-4">Erro ao carregar EPIs do funcionário.</div>';
        });
}

function atualizarEstadoBotao() {
    const checkboxes = document.querySelectorAll('.item-checkbox:checked');
    const btn = document.getElementById('btn-submit-devolucao');
    if (checkboxes.length > 0 && PODE_DEVOLVER) {
        btn.disabled = false;
    } else {
        btn.disabled = true;
    }
}

function carregarHistorico(funId) {
    const tbody = document.getElementById('lista-historico-devolucoes');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div> Buscando histórico...</td></tr>';

    fetch(`${PROXY_URL}?route=devolucoes/funcionario/${funId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.length > 0) {
                let html = '';
                res.data.forEach(dev => {
                    const dataDev = new Date(dev.item_data_devolucao).toLocaleDateString('pt-BR');
                    const badge = dev.item_status === 'EXTRAVIADO' ? 'bg-danger' : 'bg-success';
                    html += `<tr>
                        <td>${dataDev}</td>
                        <td><div class="fw-semibold">${escapar(dev.epi_nome)}</div><div class="text-muted" style="font-size:11px;">C.A. ${dev.epi_ca||'Isento'}</div></td>
                        <td>${dev.item_quantidade}</td>
                        <td class="text-muted" style="font-size:12px;">${escapar(dev.item_devolucao_motivo||'---')}</td>
                        <td><span class="badge ${badge}">${dev.item_status}</span></td>
                    </tr>`;
                });
                tbody.innerHTML = html;
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3" style="font-size:13px;">Nenhum retorno registrado.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3" style="font-size:13px;">Erro ao carregar histórico.</td></tr>';
        });
}

document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('wrapper-busca-colab');
    if (wrapper && !wrapper.contains(e.target)) fecharDropdown();
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
