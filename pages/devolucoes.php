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
    $opId = $_POST['client_operation_id'] ?? sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

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
            return $f['fun_situacao'] === 'ATIVO';
        });
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de colaboradores: ' . $e->getMessage();
}

$podeDevolver = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);

// Paleta de cores para avatares (PHP)
$coresAvatar = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899'];
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

                    <!-- ===== CAMPO DE BUSCA COM AUTOCOMPLETE ===== -->
                    <label class="form-label fw-semibold mb-1" style="font-size:12px;">
                        <i class="bi bi-search text-primary me-1"></i> Buscar Colaborador (Tempo Real) *
                    </label>
                    <div class="position-relative mb-3" id="wrapper-busca-colab">
                        <!-- Input estilizado -->
                        <div id="srch-box-border" style="
                            display:flex; align-items:center; gap:8px;
                            background:#fff; border:1.5px solid #d0d5dd;
                            border-radius:10px; padding:0 12px;
                            transition:border-color .2s, box-shadow .2s;">
                            <i class="bi bi-search" style="color:#3b82f6;font-size:15px;flex-shrink:0;"></i>
                            <input
                                type="text"
                                id="input-busca-colaborador"
                                autocomplete="off"
                                placeholder="Digite 2 letras do nome, CPF ou cargo..."
                                style="border:none;outline:none;flex:1;padding:10px 0;font-size:14px;background:transparent;"
                                oninput="buscarColaborador(this.value)"
                                onfocus="this.closest('#srch-box-border').style.borderColor='#3b82f6'; this.closest('#srch-box-border').style.boxShadow='0 0 0 3px rgba(59,130,246,.15)';"
                                onblur="this.closest('#srch-box-border').style.borderColor='#d0d5dd'; this.closest('#srch-box-border').style.boxShadow='none';"
                                onkeydown="teclarBusca(event)">
                            <button
                                type="button"
                                id="btn-limpar"
                                title="Limpar"
                                onclick="limparBusca()"
                                style="display:none;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 2px;">
                                &times;
                            </button>
                        </div>

                        <!-- Dropdown de resultados -->
                        <div id="dropdown-colab" style="
                            display:none;
                            position:absolute;top:calc(100% + 4px);left:0;right:0;
                            background:#fff;border:1.5px solid #e2e8f0;border-radius:12px;
                            box-shadow:0 12px 32px -4px rgba(0,0,0,.18),0 2px 8px -2px rgba(0,0,0,.08);
                            overflow:hidden;max-height:340px;overflow-y:auto;z-index:99999;">
                        </div>
                    </div>

                    <!-- Lista lateral de colaboradores -->
                    <div class="list-group overflow-y-auto" style="max-height:420px;" id="lista-func-devolucao">
                        <?php if (empty($funcionarios)): ?>
                            <div class="text-muted text-center py-3">Sem funcionários cadastrados.</div>
                        <?php else: ?>
                            <?php foreach ($funcionarios as $f):
                                $cpfRaw  = preg_replace('/\D/', '', $f['fun_cpf'] ?? '');
                                $cpfMask = strlen($cpfRaw) === 11
                                    ? substr($cpfRaw,0,3).'.***.***-'.substr($cpfRaw,9)
                                    : ($f['fun_cpf'] ?: '---');
                                $partes   = array_filter(explode(' ', $f['fun_nome'] ?? ''));
                                $iniciais = implode('', array_map(fn($w)=>strtoupper($w[0]), array_slice($partes,0,2)));
                                $corIdx   = abs(crc32($f['fun_nome']??'')) % count($coresAvatar);
                                $cor      = $coresAvatar[$corIdx];
                            ?>
                            <button type="button"
                                    class="list-group-item list-group-item-action border-0 border-bottom func-btn func-item-row"
                                    style="padding:10px 12px;"
                                    id="func-btn-<?= (int)$f['fun_id'] ?>"
                                    onclick="selecionarFuncionario(<?= (int)$f['fun_id'] ?>, '<?= htmlspecialchars(addslashes($f['fun_nome'])) ?>')"
                                    data-nome="<?= htmlspecialchars(mb_strtolower($f['fun_nome']??'')) ?>"
                                    data-cpf="<?= htmlspecialchars($f['fun_cpf']??'') ?>">
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:36px;height:36px;border-radius:50%;background:<?= $cor ?>;color:#fff;font-weight:700;font-size:13px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <?= htmlspecialchars($iniciais) ?>
                                    </div>
                                    <div style="line-height:1.3;">
                                        <div style="font-size:13px;font-weight:600;color:#1e293b;"><?= htmlspecialchars($f['fun_nome']??'') ?></div>
                                        <div style="font-size:11px;color:#64748b;"><?= htmlspecialchars($f['fun_cargo']??'') ?></div>
                                        <div style="font-size:11px;color:#e11d48;">CPF: <?= htmlspecialchars($cpfMask) ?></div>
                                    </div>
                                </div>
                            </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Coluna Direita: Controle de Posse e Histórico -->
            <div class="col-lg-8">
                <div class="card-custom h-100" id="painel-posse-vazio">
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted py-5">
                        <i class="bi bi-arrow-left-circle" style="font-size:48px;"></i>
                        <h5 class="mt-3 fw-bold">Nenhum funcionário selecionado</h5>
                        <p class="text-center" style="max-width:300px;">Selecione um colaborador da lista à esquerda para carregar os EPIs sob sua posse ou consultar seu histórico.</p>
                    </div>
                </div>

                <div class="card-custom h-100 d-none" id="painel-posse-ativo">
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--color-primary);" id="nome-func-selecionado">Nome do Colaborador</h5>
                            <span class="text-muted" style="font-size:12px;">Posse e devolução de equipamentos</span>
                        </div>
                    </div>

                    <!-- Abas -->
                    <ul class="nav nav-tabs" id="posseAbas" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="posse-atual-tab" data-bs-toggle="tab" data-bs-target="#tab-posse-atual" type="button" role="tab">
                                <i class="bi bi-box-seam me-1"></i>EPIs em Posse Atualmente
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#tab-historico-devolucoes" type="button" role="tab">
                                <i class="bi bi-clock-history me-1"></i>Histórico de Retornos
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="posseAbasConteudo">
                        <!-- Aba 1: Em Posse -->
                        <div class="tab-pane fade show active" id="tab-posse-atual" role="tabpanel">
                            <div class="table-responsive" style="max-height:380px;">
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

                        <!-- Aba 2: Histórico -->
                        <div class="tab-pane fade" id="tab-historico-devolucoes" role="tabpanel">
                            <div class="table-responsive" style="max-height:380px;">
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

// Estado do autocomplete
let resultados = [];
let idxFocado = -1;

// ─── Utilitários ──────────────────────────────────────────────
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
function destacar(texto, busca) {
    if (!busca || !texto) return escapar(texto);
    const tn = normalizar(texto), bn = normalizar(busca);
    const pos = tn.indexOf(bn);
    if (pos === -1) return escapar(texto);
    return escapar(texto.slice(0,pos))
         + `<strong style="color:#3b82f6;">${escapar(texto.slice(pos, pos+busca.length))}</strong>`
         + escapar(texto.slice(pos+busca.length));
}
function corAvatar(nome) {
    const cores = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#06b6d4','#ec4899'];
    let h = 0;
    for (let i=0;i<nome.length;i++) h = nome.charCodeAt(i)+((h<<5)-h);
    return cores[Math.abs(h)%cores.length];
}
function iniciaisAvatar(nome) {
    return (nome||'??').split(' ').filter(Boolean).slice(0,2).map(n=>n[0].toUpperCase()).join('');
}

// ─── Fechar dropdown ──────────────────────────────────────────
function fecharDropdown() {
    const dd = document.getElementById('dropdown-colab');
    if (dd) { dd.style.display='none'; dd.innerHTML=''; }
    idxFocado = -1;
    resultados = [];
}

// ─── Função principal de busca ────────────────────────────────
function buscarColaborador(termo) {
    idxFocado = -1;

    // Botão limpar
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) btnLimpar.style.display = termo.length > 0 ? 'block' : 'none';

    const tn = normalizar(termo);
    const cpfDigits = termo.replace(/\D/g,'');

    // Precisa de pelo menos 2 caracteres para abrir o dropdown
    if (tn.length < 2) {
        fecharDropdown();
        filtrarLista('', '');
        return;
    }

    // Filtrar
    resultados = LISTA_FUNCIONARIOS.filter(f => {
        const nome  = normalizar(f.fun_nome);
        const cargo = normalizar(f.fun_cargo);
        const depto = normalizar(f.fun_departamento);
        const mat   = normalizar(f.fun_matricula);
        const cpf   = String(f.fun_cpf||'').replace(/\D/g,'');

        const palavras = nome.split(/\s+/).filter(Boolean);
        return nome.includes(tn)
            || palavras.some(p => p.startsWith(tn))
            || cargo.includes(tn)
            || depto.includes(tn)
            || mat.includes(tn)
            || (cpfDigits.length > 0 && cpf.includes(cpfDigits));
    });

    filtrarLista(tn, cpfDigits);
    renderDropdown(termo);
}

// ─── Renderizar dropdown ──────────────────────────────────────
function renderDropdown(termo) {
    const dd = document.getElementById('dropdown-colab');
    if (!dd) return;

    if (resultados.length === 0) {
        dd.innerHTML = `
            <div style="padding:16px;text-align:center;color:#64748b;font-size:13px;">
                <i class="bi bi-search" style="margin-right:6px;"></i>
                Nenhum colaborador encontrado com "<strong>${escapar(termo)}</strong>"
            </div>`;
        dd.style.display = 'block';
        return;
    }

    const total = resultados.length;
    const itens = resultados.slice(0, 8);

    let html = `
        <div style="padding:8px 14px;background:#f8fafc;border-bottom:1px solid #e2e8f0;
                    display:flex;align-items:center;gap:8px;font-size:12px;color:#64748b;flex-wrap:wrap;">
            <span style="display:flex;align-items:center;gap:5px;">
                <i class="bi bi-people-fill" style="color:#3b82f6;"></i>
                <strong style="color:#1e293b;">${total}</strong>&nbsp;colaborador(es)
            </span>
            <span style="margin-left:auto;display:flex;align-items:center;gap:5px;">
                <span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 7px;font-size:11px;font-weight:600;">▲</span>
                <span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 7px;font-size:11px;font-weight:600;">▼</span>
                <span>para navegar</span>
                <span>•</span>
                <span style="background:#1e293b;color:#fff;border-radius:5px;padding:1px 10px;font-size:11px;font-weight:600;">Enter</span>
                <span>para escolher</span>
            </span>
        </div>`;

    itens.forEach((f, idx) => {
        const cor      = corAvatar(f.fun_nome);
        const iniciais = iniciaisAvatar(f.fun_nome);
        const nomeHL   = destacar(f.fun_nome, termo);
        const cpfM     = mascaraCpf(f.fun_cpf);
        const cargo    = escapar(f.fun_cargo || 'Sem Cargo');
        const depto    = escapar(f.fun_departamento || '');

        html += `
            <div class="dd-item"
                 id="dd-item-${idx}"
                 onclick="escolherColaborador(${f.fun_id}, '${f.fun_nome.replace(/'/g,"\\'")}')"
                 onmouseover="focarItem(${idx})"
                 style="display:flex;align-items:center;gap:12px;padding:10px 14px;
                        cursor:pointer;border-bottom:1px solid #f1f5f9;transition:background .12s;">
                <div style="width:40px;height:40px;border-radius:50%;background:${cor};color:#fff;
                            font-weight:700;font-size:14px;display:flex;align-items:center;
                            justify-content:center;flex-shrink:0;">${iniciais}</div>
                <div style="flex:1;min-width:0;line-height:1.35;">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                        <span style="font-weight:600;color:#1e293b;font-size:14px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">${nomeHL}</span>
                        <span style="background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;
                                     padding:1px 8px;font-size:11px;font-weight:600;white-space:nowrap;flex-shrink:0;">ID #${f.fun_id}</span>
                    </div>
                    <div style="font-size:12px;color:#64748b;margin-top:2px;">
                        <i class="bi bi-briefcase" style="font-size:11px;"></i> ${cargo}${depto ? ' <span style="color:#cbd5e1;">•</span> <i class="bi bi-building" style="font-size:11px;"></i> '+depto : ''}
                    </div>
                    <div style="font-size:12px;color:#e11d48;font-weight:500;margin-top:1px;">CPF: ${cpfM}</div>
                </div>
            </div>`;
    });

    dd.innerHTML = html;
    dd.style.display = 'block';
}

// ─── Focar item no dropdown ───────────────────────────────────
function focarItem(idx) {
    idxFocado = idx;
    document.querySelectorAll('.dd-item').forEach((el,i) => {
        el.style.background = i===idx ? '#eff6ff' : '';
    });
}

// ─── Teclas no campo de busca ─────────────────────────────────
function teclarBusca(e) {
    const dd = document.getElementById('dropdown-colab');
    if (!dd || dd.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        idxFocado = (idxFocado+1) % Math.min(resultados.length,8);
        focarItem(idxFocado);
        const el = document.getElementById('dd-item-'+idxFocado);
        if (el) el.scrollIntoView({block:'nearest'});
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        idxFocado = (idxFocado-1+Math.min(resultados.length,8)) % Math.min(resultados.length,8);
        focarItem(idxFocado);
        const el = document.getElementById('dd-item-'+idxFocado);
        if (el) el.scrollIntoView({block:'nearest'});
    } else if (e.key === 'Enter') {
        e.preventDefault();
        const item = idxFocado >= 0 ? resultados[idxFocado] : (resultados.length===1 ? resultados[0] : null);
        if (item) escolherColaborador(item.fun_id, item.fun_nome);
    } else if (e.key === 'Escape') {
        fecharDropdown();
    }
}

// ─── Escolher colaborador do dropdown ────────────────────────
function escolherColaborador(funId, nome) {
    const input = document.getElementById('input-busca-colaborador');
    if (input) input.value = nome;
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) btnLimpar.style.display = 'block';
    fecharDropdown();
    selecionarFuncionario(funId, nome);
}

// ─── Limpar busca ─────────────────────────────────────────────
function limparBusca() {
    const input = document.getElementById('input-busca-colaborador');
    if (input) { input.value=''; input.focus(); }
    const btnLimpar = document.getElementById('btn-limpar');
    if (btnLimpar) btnLimpar.style.display='none';
    fecharDropdown();
    filtrarLista('','');
}

// ─── Filtrar lista lateral ───────────────────────────────────
function filtrarLista(tn, cpfDigits) {
    document.querySelectorAll('.func-item-row').forEach(row => {
        const rNome = row.getAttribute('data-nome') || '';
        const rCpf  = String(row.getAttribute('data-cpf')||'').replace(/\D/g,'');
        if (!tn) {
            row.style.display = '';
        } else {
            const match = rNome.includes(tn) || (cpfDigits.length>0 && rCpf.includes(cpfDigits));
            row.style.display = match ? '' : 'none';
        }
    });
}

// ─── Fechar ao clicar fora ───────────────────────────────────
document.addEventListener('click', function(e) {
    const wrapper = document.getElementById('wrapper-busca-colab');
    if (wrapper && !wrapper.contains(e.target)) fecharDropdown();
});

// ─── Selecionar funcionário (ativa painel de posse) ───────────
function selecionarFuncionario(funId, nome) {
    document.querySelectorAll('.func-btn').forEach(b => b.classList.remove('active'));
    const btn = document.getElementById('func-btn-'+funId);
    if (btn) { btn.classList.add('active'); btn.scrollIntoView({behavior:'smooth',block:'nearest'}); }

    document.getElementById('painel-posse-vazio').classList.add('d-none');
    document.getElementById('painel-posse-ativo').classList.remove('d-none');
    document.getElementById('nome-func-selecionado').innerText = nome;

    carregarPosse(funId);
    carregarHistorico(funId);
}

// ─── Carregar EPIs em posse ───────────────────────────────────
function carregarPosse(funId) {
    const tbody = document.getElementById('lista-posse-atual');
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Buscando EPIs em posse...</td></tr>';

    fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                let html = '';
                let total = 0;
                res.data.forEach(entr => {
                    if (entr.entr_status !== 'FINALIZADA') return;
                    const dataEntr = new Date(entr.entr_data_entrega).toLocaleDateString('pt-BR');
                    entr.itens.forEach(item => {
                        if (item.item_status !== 'ENTREGUE') return;
                        total++;
                        const acao = PODE_DEVOLVER
                            ? `<button class="btn btn-sm btn-outline-danger py-1 px-2"
                                       onclick="abrirModalDevolucao(${item.item_id}, '${(item.item_epi_nome_snapshot||'').replace(/'/g,"\\'")}')">
                                   <i class="bi bi-arrow-counterclockwise"></i> Devolver
                               </button>`
                            : '<span class="text-muted" style="font-size:12px;">Sem Permissão</span>';
                        html += `<tr>
                            <td><div class="fw-semibold">${item.item_epi_nome_snapshot||'EPI'}</div>
                                <div class="text-muted" style="font-size:11px;">C.A. ${item.item_epi_ca_snapshot||'---'}</div></td>
                            <td class="fw-medium">${item.item_quantidade}</td>
                            <td>${item.item_tamanho||'---'}</td>
                            <td class="text-muted">${item.item_numero_lote||'---'}</td>
                            <td>${dataEntr}</td>
                            <td class="text-end">${acao}</td>
                        </tr>`;
                    });
                });
                tbody.innerHTML = total > 0 ? html
                    : '<tr><td colspan="6" class="text-center text-muted py-3">O colaborador não possui nenhum EPI sob sua posse no momento.</td></tr>';
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">O colaborador não possui EPIs em posse.</td></tr>';
            }
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Erro de conexão ao carregar EPIs.</td></tr>';
        });
}

// ─── Carregar histórico de devoluções ─────────────────────────
function carregarHistorico(funId) {
    const tbody = document.getElementById('lista-historico-devolucoes');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Buscando histórico...</td></tr>';

    fetch(`${PROXY_URL}?route=devolucoes/funcionario/${funId}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.length > 0) {
                let html = '';
                res.data.forEach(dev => {
                    const dataDev = new Date(dev.item_data_devolucao).toLocaleDateString('pt-BR');
                    const cls = dev.item_status === 'EXTRAVIADO' ? 'inativo' : 'ativo';
                    html += `<tr>
                        <td>${dataDev}</td>
                        <td><div class="fw-semibold">${dev.epi_nome}</div>
                            <div class="text-muted" style="font-size:11px;">C.A. ${dev.epi_ca||'Isento'}</div></td>
                        <td class="fw-medium">${dev.item_quantidade}</td>
                        <td class="text-muted" style="font-size:12px;">${dev.item_devolucao_motivo||'Sem motivo registrado'}</td>
                        <td><span class="status-badge ${cls}">${dev.item_status}</span></td>
                    </tr>`;
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

// ─── Abrir modal de devolução ─────────────────────────────────
function abrirModalDevolucao(itemId, epiNome) {
    document.getElementById('dev-item-id').value = itemId;
    document.getElementById('dev-epi-nome').value = epiNome;
    document.getElementById('dev-status').value = 'DEVOLVIDO';
    new bootstrap.Modal(document.getElementById('modalDevolverItem')).show();
}

function executarAcaoSubmenuEntrega(acao) {
    if (acao === 'nova_entrega' || acao === 'nova') {
        window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
    } else if (acao === 'devolucao' || acao === 'devolucoes') {
        if (window.location.pathname.indexOf('devolucoes.php') !== -1) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.location.href = '<?= APP_ROOT ?>pages/devolucoes.php';
        }
    } else {
        window.location.href = '<?= APP_ROOT ?>pages/entregas.php';
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
