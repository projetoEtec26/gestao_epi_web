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
/* Estilização fiel da Tela de Devolução do App Android (Print 1) */
.card-devolucao-android {
    background-color: #172033;
    border: 1px solid #28374f;
    border-radius: 16px;
    color: #e2e8f0;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.35);
    position: relative;
}

.android-title-bar {
    font-size: 1.1rem;
    font-weight: 700;
    color: #38bdf8;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Campos de entrada com efeito Android Outlined Material */
.android-field-wrapper {
    position: relative;
    margin-bottom: 18px;
}

.android-input-box {
    display: flex;
    align-items: center;
    background: #111827;
    border: 1.5px solid #334155;
    border-radius: 10px;
    padding: 0 14px;
    transition: all 0.2s ease;
}

.android-input-box:focus-within {
    border-color: #38bdf8;
    box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.15);
}

.android-input-box input, .android-input-box select {
    background: transparent;
    border: none;
    outline: none;
    color: #f8fafc;
    width: 100%;
    padding: 12px 0;
    font-size: 14px;
}

.android-input-box select option {
    background: #1e293b;
    color: #f8fafc;
}

.android-field-label {
    font-size: 12px;
    font-weight: 600;
    color: #94a3b8;
    margin-bottom: 6px;
    display: block;
}

.android-field-label span {
    color: #ef4444;
}

.android-user-badge {
    background: #2563eb;
    color: #fff;
    width: 44px;
    height: 44px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

/* Header de Funcionário Selecionado */
.android-func-header {
    margin-top: 15px;
    margin-bottom: 16px;
}

.android-func-name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #38bdf8;
    margin-bottom: 2px;
}

.android-func-subtext {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 500;
}

/* Lista de EPIs em posse com Checkbox e Ícones */
.android-epi-list {
    max-height: 380px;
    overflow-y: auto;
    padding-right: 4px;
    margin-bottom: 20px;
}

.android-epi-list::-webkit-scrollbar {
    width: 6px;
}
.android-epi-list::-webkit-scrollbar-thumb {
    background: #334155;
    border-radius: 4px;
}

.android-epi-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 12px 14px;
    background: transparent;
    border-bottom: 1px solid #1f2d42;
    transition: background 0.15s ease;
    cursor: pointer;
    user-select: none;
}

.android-epi-item:hover {
    background: rgba(255, 255, 255, 0.03);
}

.android-checkbox {
    width: 22px;
    height: 22px;
    accent-color: #2563eb;
    cursor: pointer;
    flex-shrink: 0;
}

.android-epi-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #38bdf8;
    flex-shrink: 0;
}

.android-epi-details {
    flex: 1;
    min-width: 0;
}

.android-epi-nome {
    font-size: 14px;
    font-weight: 600;
    color: #f1f5f9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.android-epi-ca {
    font-size: 12px;
    color: #64748b;
    margin-top: 1px;
}

/* Botão Principal CONFIRMAR DEVOLUÇÃO */
.btn-confirmar-devolucao {
    background-color: #2563eb;
    color: #ffffff;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    padding: 14px;
    border-radius: 10px;
    border: none;
    width: 100%;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
    transition: all 0.2s ease;
}

.btn-confirmar-devolucao:hover {
    background-color: #1d4ed8;
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.6);
    color: #ffffff;
}

.btn-confirmar-devolucao:disabled {
    background-color: #334155;
    color: #64748b;
    box-shadow: none;
    cursor: not-allowed;
}

</style>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-4 gap-2 flex-wrap flex-md-nowrap">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Entregas &amp; Devoluções</h3>
                <p class="text-muted mb-0">Gerencie a devolução, substituição e condições de retorno dos EPIs dos funcionários.</p>
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

        <div class="row justify-content-center g-4">
            <!-- CARTÃO PRINCIPAL: Devolução de EPI (Fiel ao App Android - Print 1) -->
            <div class="col-xl-7 col-lg-9 col-md-11">
                <div class="card-devolucao-android">
                    
                    <div class="android-title-bar">
                        <i class="bi bi-arrow-counterclockwise text-primary"></i> Devolução de EPI
                    </div>

                    <form method="POST" action="devolucoes.php" id="form-devolucao-android">
                        <input type="hidden" name="acao" value="registrar_devolucao">
                        <input type="hidden" name="fun_id_selecionado" id="fun_id_selecionado" value="">

                        <!-- ===== CAMPO DE BUSCA DE FUNCIONÁRIO (COM AUTOCOMPLETE) ===== -->
                        <div class="android-field-wrapper">
                            <label class="android-field-label">Buscar Funcionário... <span>*</span></label>
                            <div class="d-flex align-items-center gap-2">
                                <div class="android-input-box position-relative flex-grow-1" id="wrapper-busca-colab">
                                    <input type="text"
                                           id="input-busca-colaborador"
                                           autocomplete="off"
                                           placeholder="Digite o nome ou CPF..."
                                           oninput="buscarColaborador(this.value)"
                                           onkeydown="teclarBusca(event)">
                                    <button type="button" 
                                            id="btn-limpar" 
                                            onclick="limparBusca()" 
                                            style="display:none;background:none;border:none;color:#94a3b8;font-size:18px;cursor:pointer;padding:0 4px;">
                                        &times;
                                    </button>
                                    
                                    <!-- Dropdown Autocomplete -->
                                    <div id="dropdown-colab" style="
                                        display:none;
                                        position:absolute;top:calc(100% + 6px);left:0;right:0;
                                        background:#1e293b;border:1.5px solid #334155;border-radius:12px;
                                        box-shadow:0 12px 32px rgba(0,0,0,0.5);
                                        overflow:hidden;max-height:300px;overflow-y:auto;z-index:99999;">
                                    </div>
                                </div>
                                <div class="android-user-badge" title="Selecionar Colaborador">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </div>
                        </div>

                        <!-- ===== CABEÇALHO DO FUNCIONÁRIO SELECIONADO ===== -->
                        <div class="android-func-header d-none" id="header-func-info">
                            <div class="android-func-name" id="display-func-nome">Funcionário: Selecionado</div>
                            <div class="android-func-subtext">EPIs atualmente em posse</div>
                        </div>

                        <div class="text-center py-4 text-muted" id="msg-selecione-func" style="font-size: 14px;">
                            <i class="bi bi-person-bounding-box d-block mb-2" style="font-size: 32px; color: #475569;"></i>
                            Selecione um colaborador acima para visualizar os EPIs em posse.
                        </div>

                        <!-- ===== LISTA DE EPIs EM POSSE COM CHECKBOXES ===== -->
                        <div class="android-epi-list d-none" id="container-epi-list">
                            <!-- Injetado via JavaScript -->
                        </div>

                        <!-- ===== CAMPOS DE SELEÇÃO INFERIORES ===== -->
                        <div id="campos-formulario-devolucao" class="opacity-50 pointer-events-none">
                            <div class="android-field-wrapper">
                                <label class="android-field-label">Motivo da Devolução <span>*</span></label>
                                <div class="android-input-box">
                                    <select name="item_devolucao_motivo" id="select-motivo" required>
                                        <option value="" disabled selected>Selecione o motivo...</option>
                                        <option value="Devolução física ao almoxarifado">Devolução física ao almoxarifado</option>
                                        <option value="Fim da vida útil / Desgaste natural">Fim da vida útil / Desgaste natural</option>
                                        <option value="Troca periódica de EPI">Troca periódica de EPI</option>
                                        <option value="Danificado / Avariado">Danificado / Avariado</option>
                                        <option value="Extravio / Perda do colaborador">Extravio / Perda do colaborador</option>
                                        <option value="Demissão / Desligamento">Demissão / Desligamento</option>
                                        <option value="Outro motivo">Outro motivo</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <div class="android-field-wrapper mb-0">
                                        <label class="android-field-label">Condição do Item Devolvido</label>
                                        <div class="android-input-box">
                                            <select name="item_devolucao_condicao" id="select-condicao">
                                                <option value="DANIFICADO" selected>DANIFICADO</option>
                                                <option value="USADO">USADO (Descarte)</option>
                                                <option value="NOVO">NOVO (Reaproveitável)</option>
                                                <option value="BOM_ESTADO">BOM ESTADO</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="android-field-wrapper mb-0">
                                        <label class="android-field-label">Destino do Item</label>
                                        <div class="android-input-box">
                                            <select name="item_devolucao_destino" id="select-destino">
                                                <option value="MANUTENCAO" selected>MANUTENÇÃO / HIGIENIZAÇÃO</option>
                                                <option value="ESTOQUE">RETORNO AO ESTOQUE</option>
                                                <option value="DESCARTE">COLETA / DESCARTE ECOLÓGICO</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- ===== BOTÃO DE AÇÃO PRINCIPAL ===== -->
                            <button type="submit" class="btn-confirmar-devolucao" id="btn-submit-devolucao" disabled>
                                CONFIRMAR DEVOLUÇÃO
                            </button>
                        </div>
                    </form>



                </div>
            </div>
        </div>

        <!-- Seção Adicional: Consulta de Histórico de Retornos do Funcionário -->
        <div class="row justify-content-center mt-5">
            <div class="col-xl-7 col-lg-9 col-md-11">
                <div class="card-custom">
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <h6 class="fw-bold m-0 text-secondary">
                            <i class="bi bi-clock-history me-1"></i> Histórico Recente de Retornos do Colaborador
                        </h6>
                    </div>
                    <div class="table-responsive" style="max-height: 250px;">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr style="font-size: 12px;">
                                    <th>Data Retorno</th>
                                    <th>EPI / C.A.</th>
                                    <th>Qtd</th>
                                    <th>Motivo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="lista-historico-devolucoes">
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3" style="font-size: 13px;">
                                        Selecione um colaborador acima para consultar o histórico.
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

// Lista de funcionários injetada pelo PHP
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

// Retorna ícone do Bootstrap correspondente ao tipo de EPI (conforme Print 1)
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
        dd.innerHTML = `<div style="padding:12px;text-align:center;color:#94a3b8;font-size:13px;">Nenhum colaborador encontrado com "${escapar(termo)}"</div>`;
        dd.style.display = 'block';
        return;
    }

    let html = '';
    resultados.slice(0, 7).forEach((f, idx) => {
        html += `
            <div class="dd-item" id="dd-item-${idx}" 
                 onclick="escolherColaborador(${f.fun_id}, '${f.fun_nome.replace(/'/g,"\\'")}')"
                 style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #334155;color:#f8fafc;font-size:13px;"
                 onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">
                <div class="fw-semibold">${escapar(f.fun_nome)}</div>
                <div style="font-size:11px;color:#94a3b8;">${escapar(f.fun_cargo || 'Sem cargo')} • CPF: ${mascaraCpf(f.fun_cpf)}</div>
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
    
    const container = document.getElementById('campos-formulario-devolucao');
    container.classList.add('opacity-50', 'pointer-events-none');
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
                            <label class="android-epi-item">
                                <input type="checkbox" name="item_ids[]" value="${item.item_id}" class="android-checkbox item-checkbox" onchange="atualizarEstadoBotao()">
                                <div class="android-epi-icon">${icone}</div>
                                <div class="android-epi-details">
                                    <div class="android-epi-nome">${escapar(epiNome)}</div>
                                    <div class="android-epi-ca">${caStr}${loteStr}</div>
                                </div>
                            </label>`;
                    });
                });

                if (count > 0) {
                    container.innerHTML = html;
                    document.getElementById('campos-formulario-devolucao').classList.remove('opacity-50', 'pointer-events-none');
                } else {
                    container.innerHTML = '<div class="text-center text-muted py-4">Nenhum EPI atualmente sob posse deste funcionário.</div>';
                    document.getElementById('campos-formulario-devolucao').classList.add('opacity-50', 'pointer-events-none');
                    document.getElementById('btn-submit-devolucao').disabled = true;
                }
            } else {
                container.innerHTML = '<div class="text-center text-muted py-4">Nenhum EPI em posse localizado.</div>';
                document.getElementById('campos-formulario-devolucao').classList.add('opacity-50', 'pointer-events-none');
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
