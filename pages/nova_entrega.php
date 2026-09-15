<?php
declare(strict_types=1);

$page_title = 'Nova Entrega de EPI — Contingência';
$active_menu = 'nova_entrega';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$funcionarios = [];
$episCatalogo = [];
$termoVigente = null;
$erro = null;

try {
    // 1. Carrega lista de funcionários
    $resFunc = $api->get('funcionarios');
    if (isset($resFunc['success']) && $resFunc['success']) {
        $funcionarios = $resFunc['data'];
    }

    // 2. Carrega catálogo de EPIs
    $resEpis = $api->get('epis');
    if (isset($resEpis['success']) && $resEpis['success']) {
        $episCatalogo = $resEpis['data'];
    }

    // 3. Carrega termo de responsabilidade vigente
    $resTermo = $api->get('termos-politicas/vigente');
    if (isset($resTermo['success']) && $resTermo['success'] && !empty($resTermo['data'])) {
        $termoVigente = $resTermo['data'];
    }
} catch (\Throwable $e) {
    $erro = 'Erro ao carregar dados iniciais: ' . $e->getMessage();
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <!-- Header da Página -->
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h3 class="fw-bold m-0" style="color: var(--color-primary);">Nova Entrega de EPI</h3>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size: 11px;">
                        <i class="bi bi-shield-check me-1"></i> Contingência Operacional
                    </span>
                </div>
                <p class="text-muted mb-0" style="font-size: 13px;">
                    Registro oficial de fornecimento de EPIs, substituições com devolução vinculada e coleta de assinatura eletrônica por PIN.
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div class="btn-group-toggle-view" role="group">
                    <a href="entregas.php" class="btn btn-view">
                        <i class="bi bi-clock-history me-1"></i> Histórico
                    </a>
                    <a href="devolucoes.php" class="btn btn-view">
                        <i class="bi bi-arrow-counterclockwise me-1"></i> Devolução
                    </a>
                </div>
                <a href="nova_entrega.php" class="btn btn-primary px-3 py-2 fw-semibold rounded-3 shadow-sm">
                    <i class="bi bi-plus-lg me-1"></i> Nova Entrega
                </a>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Coluna Esquerda: Formulário de Entrega -->
            <div class="col-lg-8">
                
                <!-- PASSO 1: Seleção do Colaborador -->
                <div class="card-custom mb-4" style="position: relative; z-index: 1050;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0 text-color-primary">
                            <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; line-height: 14px; font-size: 11px;">1</span>
                            Identificação do Colaborador Receptor
                        </h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-scan-qr" onclick="abrirLeitorQrCode()">
                            <i class="bi bi-qr-code-scan me-1"></i> Ler QR Code / Crachá
                        </button>
                    </div>

                    <div class="row g-3 align-items-end">
                        <div class="col-md-9 position-relative">
                            <label for="input-busca-colaborador" class="form-label fw-semibold" style="font-size: 12px;">
                                <i class="bi bi-search text-primary me-1"></i> Buscar Colaborador (Tempo Real) *
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0 text-primary">
                                    <i class="bi bi-person-bounding-box"></i>
                                </span>
                                <input type="text" 
                                       id="input-busca-colaborador" 
                                       class="form-control border-start-0 border-end-0 py-2" 
                                       placeholder="Digite o nome (ex: Ron...), CPF ou cargo..." 
                                       autocomplete="off"
                                       oninput="aoDigitarBuscaColaborador(this.value)"
                                       onfocus="aoFocarBuscaColaborador()"
                                       onkeydown="aoTeclarBuscaColaborador(event)">
                                <button class="btn btn-outline-secondary border-start-0 d-none" 
                                        type="button" 
                                        id="btn-limpar-busca-colab" 
                                        onclick="limparSelecaoColaborador()" 
                                        title="Limpar seleção">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <input type="hidden" id="select-funcionario" value="">

                            <!-- Dropdown Flutuante de Autocomplete -->
                            <div id="dropdown-autocomplete-colab" 
                                 class="shadow-lg mt-1 p-0 border autocomplete-dropdown-container" 
                                 style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; width: 100%; max-height: 320px; overflow-y: auto; z-index: 999999; border-radius: 12px;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="button" class="btn btn-light border w-100 py-2" onclick="limparSelecaoColaborador()">
                                <i class="bi bi-x-circle me-1"></i> Limpar
                            </button>
                        </div>
                    </div>

                    <!-- Card de Detalhes do Colaborador Selecionado -->
                    <div id="card-detalhes-colab" class="mt-3 p-3 rounded bg-light border d-none">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <h6 class="fw-bold mb-1" id="info-colab-nome" style="color: var(--color-primary);">---</h6>
                                <div class="text-muted" style="font-size: 12px;">
                                    <span id="info-colab-cargo" class="fw-medium">---</span> • 
                                    <span id="info-colab-setor">---</span> • 
                                    <span>CPF: <span id="info-colab-cpf">---</span></span> • 
                                    <span>Matrícula: <span id="info-colab-matricula">---</span></span>
                                </div>
                            </div>
                            <div id="info-colab-pin-badge">
                                <span class="badge bg-secondary"><i class="bi bi-hourglass-split"></i> Verificando PIN...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PASSO 2: EPIs Atualmente em Posse (Para Substituição Rápida) -->
                <div id="card-epis-em-posse" class="card-custom mb-4 d-none" style="position: relative; z-index: 1000;">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold m-0 text-color-primary">
                            <i class="bi bi-box-seam me-1"></i> EPIs Atualmente em Posse do Colaborador
                        </h6>
                        <span class="badge bg-light text-muted border" id="badge-total-posse">0 itens</span>
                    </div>
                    <p class="text-muted mb-2" style="font-size: 12px;">
                        Clique em <strong>"Substituir"</strong> para trocar um item desgastado gerando a devolução vinculada automaticamente.
                    </p>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0" style="font-size: 12px;">
                            <thead class="table-light">
                                <tr>
                                    <th>Equipamento (EPI)</th>
                                    <th>C.A.</th>
                                    <th>Tamanho</th>
                                    <th>Data Entrega</th>
                                    <th>Situação</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-itens-posse">
                                <tr><td colspan="6" class="text-center text-muted py-2">Carregando itens...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- PASSO 3: Seleção e Adição de Novos EPIs ao Carrinho -->
                <div class="card-custom mb-4" style="position: relative; z-index: 950;">
                    <h6 class="fw-bold mb-3 text-color-primary">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; line-height: 14px; font-size: 11px;">2</span>
                        Adicionar Equipamentos (EPIs) ao Carrinho
                    </h6>

                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label for="input-busca-epi-item" class="form-label fw-semibold mb-1" style="font-size: 12px;">
                                <i class="bi bi-search text-primary me-1"></i> Buscar Equipamento (Tempo Real) *
                            </label>
                            <div id="srch-box-border-epi-item" style="
                                display:flex; align-items:center; gap:8px;
                                background:#fff; border:1.5px solid #d0d5dd;
                                border-radius:10px; padding:0 12px;
                                transition:border-color .2s, box-shadow .2s;">
                                <i class="bi bi-shield-check" style="color:#3b82f6;font-size:15px;flex-shrink:0;"></i>
                                <input type="text" 
                                       id="input-busca-epi-item" 
                                       autocomplete="off"
                                       placeholder="Digite o nome (ex: Bot...), fabricante ou C.A...." 
                                       style="border:none;outline:none;flex:1;padding:9px 0;font-size:14px;background:transparent;"
                                       oninput="aoDigitarBuscaEpiEntrega(this.value)"
                                       onfocus="this.closest('#srch-box-border-epi-item').style.borderColor='#3b82f6'; this.closest('#srch-box-border-epi-item').style.boxShadow='0 0 0 3px rgba(59,130,246,.15)'; aoFocarBuscaEpiEntrega();"
                                       onblur="this.closest('#srch-box-border-epi-item').style.borderColor='#d0d5dd'; this.closest('#srch-box-border-epi-item').style.boxShadow='none';"
                                       onkeydown="aoTeclarBuscaEpiEntrega(event)">
                                <button type="button" 
                                        id="btn-limpar-busca-epi-item" 
                                        title="Limpar seleção" 
                                        onclick="limparSelecaoEpiEntrega()" 
                                        style="display:none;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 2px;">
                                    &times;
                                </button>
                            </div>
                            <input type="hidden" id="select-epi-item" value="">

                            <!-- Dropdown Flutuante de Autocomplete / Sugestões em Tempo Real -->
                            <div id="dropdown-autocomplete-epi" 
                                 style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; width: 100%; max-height: 340px; overflow-y: auto; z-index: 99999; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 12px 32px -4px rgba(0,0,0,0.18), 0 2px 8px -2px rgba(0,0,0,0.08) !important;">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12px;">Motivo do Fornecimento *</label>
                            <select id="input-motivo-item" class="form-select">
                                <option value="ADMISSAO">Admissão</option>
                                <option value="SUBSTITUICAO">Substituição</option>
                                <option value="PERIODICA">Periódica / Vida Útil</option>
                                <option value="EXTRAORDINARIA">Extraordinária</option>
                                <option value="DANIFICADO">Danificado / Avaria</option>
                                <option value="PERDA">Perda / Extravio</option>
                                <option value="TROCA_FUNCAO">Troca de Função</option>
                                <option value="OUTROS">Outros</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fw-semibold" style="font-size: 12px;">Quantidade *</label>
                            <input type="number" id="input-qtd-item" class="form-control" value="1" min="1" max="50">
                        </div>

                        <!-- Campos Condicionais: Tamanho e Lote -->
                        <div class="col-md-3" id="box-tamanho-item">
                            <label class="form-label fw-semibold" style="font-size: 12px;">Tamanho</label>
                            <select id="input-tamanho-item" class="form-select">
                                <option value="Único">Único</option>
                                <option value="PP">PP</option>
                                <option value="P">P</option>
                                <option value="M">M</option>
                                <option value="G">G</option>
                                <option value="GG">GG</option>
                                <option value="XG">XG</option>
                                <option value="36">36</option>
                                <option value="37">37</option>
                                <option value="38">38</option>
                                <option value="39">39</option>
                                <option value="40">40</option>
                                <option value="41">41</option>
                                <option value="42">42</option>
                                <option value="43">43</option>
                                <option value="44">44</option>
                                <option value="45">45</option>
                            </select>
                        </div>

                        <div class="col-md-3" id="box-lote-item">
                            <label class="form-label fw-semibold" style="font-size: 12px;">Lote de Fabricação</label>
                            <input type="text" id="input-lote-item" class="form-control" placeholder="Ex: LT-2026-A">
                        </div>

                        <div class="col-md-6 d-flex align-items-end">
                            <button type="button" class="btn btn-primary w-100" onclick="adicionarItemAoCarrinho()">
                                <i class="bi bi-cart-plus me-1"></i> Adicionar ao Carrinho de Entrega
                            </button>
                        </div>
                    </div>

                    <!-- Alerta de CA Vencido -->
                    <div id="alerta-ca-vencido" class="alert alert-warning mt-3 mb-0 d-none" style="font-size: 12px;">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        <strong>Atenção:</strong> O Certificado de Aprovação (C.A.) deste equipamento está vencido no catálogo oficial do MTE.
                    </div>
                </div>

                <!-- Tabela de Itens no Carrinho -->
                <div class="card-custom mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold m-0 text-color-primary">
                            <i class="bi bi-cart-check me-1"></i> Itens no Carrinho de Fornecimento
                        </h6>
                        <span class="badge bg-primary" id="badge-itens-carrinho">0 itens</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle" style="font-size: 12.5px;">
                            <thead class="table-light">
                                <tr>
                                    <th>EPI</th>
                                    <th>C.A.</th>
                                    <th>Tam / Lote</th>
                                    <th>Qtd</th>
                                    <th>Motivo</th>
                                    <th>Devolução Vinculada</th>
                                    <th class="text-end">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="tabela-carrinho-corpo">
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhum equipamento adicionado ao carrinho ainda.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Coluna Direita: Termo, Assinatura por PIN e Finalização -->
            <div class="col-lg-4">
                <div class="card-custom sticky-top" style="top: 20px;">
                    <h6 class="fw-bold mb-3 text-color-primary">
                        <span class="badge bg-primary text-white rounded-circle me-1" style="width: 22px; height: 22px; line-height: 14px; font-size: 11px;">3</span>
                        Termo Legal & Assinatura
                    </h6>

                    <!-- Termo de Responsabilidade Snapshot -->
                    <label class="form-label fw-semibold" style="font-size: 12px;">Termo de Responsabilidade Vigente</label>
                    <div class="border rounded p-2 mb-3 bg-light text-muted" style="max-height: 180px; overflow-y: auto; font-size: 11px; line-height: 1.4; text-align: justify;" id="texto-termo-container">
                        <?= htmlspecialchars($termoVigente['ter_texto'] ?? 'Declaro estar recebendo, gratuitamente e sem qualquer ônus, os Equipamentos de Proteção Individual — EPIs discriminados neste termo. Declaro estar ciente da obrigatoriedade de sua utilização durante a execução das atividades laborais, conforme NR-06 e Art. 158 da CLT.') ?>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="check-aceite-termo" checked>
                        <label class="form-check-label text-muted" for="check-aceite-termo" style="font-size: 11.5px;">
                            O colaborador declara ciência e recebimento integral dos EPIs discriminados.
                        </label>
                    </div>

                    <hr class="my-3">

                    <!-- Assinatura por PIN -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-color-primary" style="font-size: 12px;">
                            <i class="bi bi-key-fill me-1"></i> Digite a Senha / PIN do Colaborador *
                        </label>
                        <input type="password" id="input-pin-assinatura" class="form-control text-center fw-bold fs-5" 
                               maxlength="10" placeholder="••••••••" autocomplete="off" style="letter-spacing: 4px;">
                        <div class="form-text" style="font-size: 11px;">
                            Senha/PIN de 4 a 10 caracteres alfanuméricos cadastrada pelo funcionário para validação digital da entrega.
                        </div>
                    </div>

                    <!-- Botão Finalizar Entrega -->
                    <button type="button" id="btn-finalizar-entrega" class="btn btn-success btn-lg w-100 py-3 fw-bold shadow-sm" onclick="processarEnvioEntrega()">
                        <i class="bi bi-shield-lock-fill me-2"></i> Assinar e Finalizar Entrega
                    </button>

                    <div class="text-center mt-3">
                        <small class="text-muted" style="font-size: 10.5px;">
                            <i class="bi bi-cpu me-1"></i> Operação com integridade SHA-256 e idempotência via API REST.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Devolução Vinculada (Substituição) -->
<div class="modal fade" id="modalDevolucaoVinculada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-arrow-repeat me-1"></i> Configurar Devolução Vinculada (Substituição)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-dev-item-orig-id">
                <input type="hidden" id="modal-dev-epi-id">
                
                <div class="alert alert-info py-2" style="font-size: 12px;">
                    Item em devolução: <strong id="modal-dev-epi-nome">---</strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 12px;">Motivo da Devolução / Troca *</label>
                    <select id="modal-dev-motivo" class="form-select">
                        <option value="SUBSTITUICAO_DESGASTE">Substituição por Desgaste Normal</option>
                        <option value="DANIFICADO">Avaria / Quebra no Trabalho</option>
                        <option value="FALHA_EQUIPAMENTO">Falha / Defeito do Equipamento</option>
                        <option value="VENCIMENTO_VIDA_UTIL">Vencimento da Vida Útil</option>
                        <option value="TAMANHO_INADEQUADO">Tamanho Inadequado</option>
                        <option value="OUTROS">Outros Motivos</option>
                    </select>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 12px;">Condição do Item Usado *</label>
                        <select id="modal-dev-condicao" class="form-select">
                            <option value="RUIM">Ruim / Gasto</option>
                            <option value="INUTILIZAVEL">Inutilizável</option>
                            <option value="REGULAR">Regular</option>
                            <option value="BOM">Bom</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold" style="font-size: 12px;">Destino do Item Antigo *</label>
                        <select id="modal-dev-destino" class="form-select">
                            <option value="DESCARTE">Descarte Oficial</option>
                            <option value="HIGIENIZACAO">Higienização / Manutenção</option>
                            <option value="ESTOQUE">Retorno ao Estoque</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size: 12px;">Observações da Devolução</label>
                    <textarea id="modal-dev-obs" class="form-control" rows="2" placeholder="Descreva eventuais avarias ou particularidades..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="confirmarSubstituicaoVinculada()">
                    <i class="bi bi-check2 me-1"></i> Confirmar e Adicionar Novo EPI
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Devolução Vinculada / Substituição de EPI -->
<div class="modal fade" id="modalDevolucaoVinculada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-arrow-repeat me-1"></i> Substituir Equipamento (Devolução Vinculada)
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="modal-dev-item-orig-id">
                <input type="hidden" id="modal-dev-epi-id">
                
                <div class="alert alert-warning py-2 mb-3" style="font-size: 12px;">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Substituindo: <strong id="modal-dev-epi-nome">---</strong>. O item em posse será registrado para devolução vinculada e a substituição será adicionada ao carrinho.
                </div>

                <div class="row g-2">
                    <div class="col-md-6 mb-2">
                        <label class="form-label" style="font-size: 11px;">Motivo da Devolução *</label>
                        <select id="modal-dev-motivo" class="form-select form-select-sm">
                            <option value="SUBSTITUICAO" selected>Substituição por Desgaste</option>
                            <option value="DANIFICADO">Danificado / Avariado</option>
                            <option value="VENCIDO">Validade C.A. Vencida</option>
                            <option value="TAMANHO_INCORRETO">Tamanho Inadequado</option>
                            <option value="OUTRO">Outro Motivo</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label" style="font-size: 11px;">Condição do Item Devolvido *</label>
                        <select id="modal-dev-condicao" class="form-select form-select-sm">
                            <option value="DESCARTADO" selected>Descartado (Inutilizado)</option>
                            <option value="DESINFETADO">Higienizado / Reutilizável</option>
                            <option value="EM_MANUTENCAO">Enviado para Manutenção</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label" style="font-size: 11px;">Destino do Item Devolvido *</label>
                        <select id="modal-dev-destino" class="form-select form-select-sm">
                            <option value="DESCARTE" selected>Lixo / Descarte Definitivo</option>
                            <option value="ESTOQUE_SEGUNDA_MAO">Estoque Reutilizável / Reserva</option>
                            <option value="FORNECEDOR">Devolvido ao Fornecedor / Garantia</option>
                        </select>
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label" style="font-size: 11px;">Observação / Justificativa</label>
                        <textarea id="modal-dev-obs" class="form-control form-control-sm" rows="2" placeholder="Detalhes do estado do equipamento devolvido..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm fw-bold" onclick="confirmarSubstituicaoVinculada()">
                    <i class="bi bi-arrow-repeat me-1"></i> Confirmar Substituição
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cadastro / Redefinição de PIN Rápido -->
<div class="modal fade" id="modalPinRapido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" style="color: var(--color-primary); font-size: 13px;">
                    <i class="bi bi-key-fill me-1"></i> Cadastrar / Redefinir PIN
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-2" style="font-size: 11px;">
                    Defina uma senha/PIN de 4 a 10 caracteres alfanuméricos para o colaborador assinar eletronicamente.
                </p>
                <div class="mb-2">
                    <label class="form-label" style="font-size: 11px;">Novo PIN (4 a 10 caracteres alfanuméricos)</label>
                    <input type="password" id="modal-pin-novo" class="form-control text-center fs-5" maxlength="10" placeholder="••••••••" autocomplete="off">
                </div>
                <div class="mb-2">
                    <label class="form-label" style="font-size: 11px;">Confirmar PIN</label>
                    <input type="password" id="modal-pin-confirma" class="form-control text-center fs-5" maxlength="10" placeholder="••••••••" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm border" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary btn-sm" onclick="salvarPinRapido()">Salvar PIN</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Leitor de QR Code / Câmera -->
<div class="modal fade" id="modalQrCode" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h6 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-qr-code-scan me-1"></i> Leitor de QR Code do Colaborador
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" onclick="fecharLeitorQrCode()"></button>
            </div>
            <div class="modal-body text-center">
                <div id="qr-reader" style="width: 100%; min-height: 250px;"></div>
                <div class="mt-2 text-muted" style="font-size: 11px;">
                    Aponte a câmera para o QR Code do crachá do colaborador ou digite o código abaixo:
                </div>
                <div class="input-group mt-2">
                    <input type="text" id="input-qr-manual" class="form-control" placeholder="Cole ou digite o código do QR...">
                    <button class="btn btn-primary" type="button" onclick="processarCodigoQr(document.getElementById('input-qr-manual').value)">Buscar</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Sucesso da Entrega com Botão de Impressão -->
<div class="modal fade" id="modalSucessoEntrega" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-3">
            <div class="modal-body">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 4rem;"></i>
                </div>
                <h4 class="fw-bold text-success mb-1">Entrega Finalizada com Sucesso!</h4>
                <p class="text-muted mb-3" style="font-size: 13px;">
                    O fornecimento de EPIs e a assinatura eletrônica por PIN foram homologados e registrados com integridade.
                </p>

                <div class="p-3 bg-light rounded border text-start mb-4" style="font-size: 12px;">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Nº da Entrega:</span>
                        <strong class="text-primary" id="sucesso-entrega-id">#---</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Colaborador:</span>
                        <strong id="sucesso-colab-nome">---</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Data/Hora:</span>
                        <span id="sucesso-data-hora">---</span>
                    </div>
                    <div class="mb-1">
                        <span class="text-muted d-block">Hash SHA-256 da Assinatura:</span>
                        <code class="text-break" style="font-size: 10px;" id="sucesso-hash">---</code>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-primary btn-lg" onclick="imprimirReciboEntregaGerada()">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Recibo / Termo Assinado
                    </button>
                    <button type="button" class="btn btn-outline-secondary" onclick="reiniciarFluxoEntrega()">
                        <i class="bi bi-plus-circle me-1"></i> Realizar Outra Entrega
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts de Suporte para QR Code e Lógica de Entrega -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
const PROXY_URL = 'api_proxy.php';

// Base de Colaboradores para Busca em Tempo Real
let listaFuncionarios = <?= json_encode(array_values(array_map(function($f) {
    return [
        'fun_id' => (int)$f['fun_id'],
        'fun_nome' => $f['fun_nome'] ?? '',
        'fun_cpf' => $f['fun_cpf'] ?? '',
        'fun_matricula' => $f['fun_matricula'] ?? ('MAT-' . $f['fun_id']),
        'fun_cargo' => $f['fun_cargo'] ?? 'Operacional',
        'fun_departamento' => $f['fun_departamento'] ?? 'Geral'
    ];
}, $funcionarios)), JSON_UNESCAPED_UNICODE) ?>;

// Fallback dinâmico caso a lista esteja vazia no carregamento
if (!listaFuncionarios || listaFuncionarios.length === 0) {
    listaFuncionarios = [];
    fetch(`${PROXY_URL}?route=funcionarios`)
        .then(r => r.json())
        .then(res => {
            if (res.success && Array.isArray(res.data)) {
                res.data.forEach(f => {
                    listaFuncionarios.push({
                        fun_id: parseInt(f.fun_id),
                        fun_nome: f.fun_nome || '',
                        fun_cpf: f.fun_cpf || '',
                        fun_matricula: f.fun_matricula || ('MAT-' + f.fun_id),
                        fun_cargo: f.fun_cargo || 'Operacional',
                        fun_departamento: f.fun_departamento || 'Geral'
                    });
                });
            }
        })
        .catch(() => {});
}

// Base de EPIs do Catálogo para Autocomplete
let listaEpisCatalogo = <?= json_encode(array_values(array_map(function($e) {
    $caDesc = !empty($e['epi_ca']) ? 'C.A. ' . $e['epi_ca'] : 'Sem C.A.';
    $vencCa = !empty($e['epi_validade_ca']) ? date('d/m/Y', strtotime($e['epi_validade_ca'])) : '---';
    $isVencido = (!empty($e['epi_validade_ca']) && strtotime($e['epi_validade_ca']) < time());
    return [
        'epi_id' => (int)$e['epi_id'],
        'epi_nome' => $e['epi_nome'] ?? '',
        'epi_ca' => $e['epi_ca'] ?? '',
        'epi_fabricante' => $e['epi_fabricante'] ?? '',
        'epi_validade_ca' => $e['epi_validade_ca'] ?? '',
        'is_vencido' => $isVencido ? 1 : 0,
        'epi_tipo_item' => $e['epi_tipo_item'] ?? 'EPI_COM_CA'
    ];
}, $episCatalogo)), JSON_UNESCAPED_UNICODE) ?>;

// Estado da Entrega
let colaboradorSelecionado = null;
let carrinhoEpi = [];
let devolucoesVinculadas = [];
let html5QrScanner = null;
let ultimaEntregaConcluidaId = null;

// Estado do Autocomplete de Colaborador
let resultadosAutocomplete = [];
let indexFocadoAutocomplete = -1;

// Estado do Autocomplete de EPI
let resultadosAutocompleteEpi = [];
let indexFocadoAutocompleteEpi = -1;
let epiSelecionadoEntrega = null;

/**
 * Normaliza strings para busca sem acentos e minúsculas
 */
function normalizarTexto(txt) {
    return String(txt || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

/**
 * Escapa HTML para exibição segura
 */
function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Destaca trechos pesquisados no autocomplete
 */
function destacarTrecho(texto, query) {
    if (!texto) return '';
    if (!query || !query.trim()) return htmlEscape(texto);
    const termoClean = query.trim();
    const regex = new RegExp(`(${termoClean.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
    return htmlEscape(texto).replace(regex, '<mark class="bg-warning-subtle text-dark p-0 rounded-1">$1</mark>');
}

/**
 * Disparado ao digitar no campo de busca do colaborador
 */
function aoDigitarBuscaColaborador(termo) {
    indexFocadoAutocomplete = -1;
    const btnLimpar = document.getElementById('btn-limpar-busca-colab');

    if (termo && termo.length > 0) {
        btnLimpar.classList.remove('d-none');
    } else {
        btnLimpar.classList.add('d-none');
    }

    const termoNorm = normalizarTexto(termo);
    const termoCpfLimpo = String(termo || '').replace(/\D/g, '');

    if (termoNorm === '') {
        // Se vazio, mostra os primeiros colaboradores cadastrados
        resultadosAutocomplete = listaFuncionarios.slice(0, 8);
    } else {
        // Filtra estritamente por "COMEÇA COM" (startsWith) no nome, nas palavras do nome, cargo ou CPF
        resultadosAutocomplete = listaFuncionarios.filter(f => {
            const nomeNorm = normalizarTexto(f.fun_nome);
            const cargoNorm = normalizarTexto(f.fun_cargo);
            const deptoNorm = normalizarTexto(f.fun_departamento);
            const matNorm = normalizarTexto(f.fun_matricula);
            const cpfLimpo = String(f.fun_cpf || '').replace(/\D/g, '');

            const matchNome = nomeNorm.includes(termoNorm);
            const matchCargo = cargoNorm.includes(termoNorm);
            const matchDepto = deptoNorm.includes(termoNorm);
            const matchMat = matNorm.includes(termoNorm);
            const matchCpf = (termoCpfLimpo.length > 0 && cpfLimpo.includes(termoCpfLimpo)) || String(f.fun_cpf || '').includes(termoNorm);

            return matchNome || matchCargo || matchDepto || matchMat || matchCpf;
        });
    }

    renderizarDropdownAutocomplete(termo);
}

/**
 * Ao focar no campo de busca
 */
function aoFocarBuscaColaborador() {
    const input = document.getElementById('input-busca-colaborador');
    aoDigitarBuscaColaborador(input.value);
}

/**
 * Renderiza os itens do autocomplete
 */
function renderizarDropdownAutocomplete(termoDigitado) {
    const dropdown = document.getElementById('dropdown-autocomplete-colab');
    if (!dropdown) return;

    if (resultadosAutocomplete.length === 0) {
        dropdown.innerHTML = `
            <div class="p-3 text-center text-muted" style="font-size: 13px;">
                <i class="bi bi-search me-1 text-secondary"></i> Nenhum colaborador encontrado com "<strong>${htmlEscape(termoDigitado)}</strong>"
            </div>`;
        dropdown.style.display = 'block';
        return;
    }

    let html = `
        <div class="px-3 py-2 bg-light border-bottom text-muted d-flex justify-content-between align-items-center" style="font-size: 11px;">
            <span><i class="bi bi-people me-1"></i> ${resultadosAutocomplete.length} colaborador(es) encontrado(s)</span>
            <span><kbd>▲</kbd> <kbd>▼</kbd> para navegar • <kbd>Enter</kbd> para escolher</span>
        </div>
        <div class="list-group list-group-flush">
    `;

    resultadosAutocomplete.forEach((f, idx) => {
        const cpfFmt = formatarCpf(f.fun_cpf);
        const iniciais = (f.fun_nome || 'CO')
            .split(' ')
            .filter(n => n.length > 0)
            .slice(0, 2)
            .map(n => n[0].toUpperCase())
            .join('');

        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action p-2 border-0 border-bottom d-flex align-items-center gap-2 autocomplete-item" 
               id="auto-item-${idx}"
               onclick="selecionarColaborador(${f.fun_id})"
               onmouseover="destacarItemAutocomplete(${idx})">
                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 36px; height: 36px; font-size: 13px;">
                    ${iniciais}
                </div>
                <div class="flex-grow-1 min-w-0" style="line-height: 1.25;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold text-body text-truncate" style="font-size: 13px;">${destacarTrecho(f.fun_nome, termoDigitado)}</span>
                        <span class="badge bg-light text-secondary border ms-1" style="font-size: 10px;">ID #${f.fun_id}</span>
                    </div>
                    <div class="text-muted d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                        <span class="text-primary-emphasis"><i class="bi bi-briefcase me-1"></i>${htmlEscape(f.fun_cargo)}</span>
                        <span>•</span>
                        <span><i class="bi bi-building me-1"></i>${htmlEscape(f.fun_departamento)}</span>
                        <span>•</span>
                        <code>CPF: ${cpfFmt}</code>
                    </div>
                </div>
            </a>
        `;
    });

    html += `</div>`;
    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
}

/**
 * Destaca visualmente o item no dropdown
 */
function destacarItemAutocomplete(idx) {
    indexFocadoAutocomplete = idx;
    const items = document.querySelectorAll('.autocomplete-item');
    items.forEach((el, i) => {
        if (i === idx) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

/**
 * Navegação por teclado no autocomplete
 */
function aoTeclarBuscaColaborador(e) {
    const dropdown = document.getElementById('dropdown-autocomplete-colab');
    if (!dropdown || dropdown.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (resultadosAutocomplete.length > 0) {
            indexFocadoAutocomplete = (indexFocadoAutocomplete + 1) % resultadosAutocomplete.length;
            destacarItemAutocomplete(indexFocadoAutocomplete);
            const el = document.getElementById(`auto-item-${indexFocadoAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (resultadosAutocomplete.length > 0) {
            indexFocadoAutocomplete = (indexFocadoAutocomplete - 1 + resultadosAutocomplete.length) % resultadosAutocomplete.length;
            destacarItemAutocomplete(indexFocadoAutocomplete);
            const el = document.getElementById(`auto-item-${indexFocadoAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indexFocadoAutocomplete >= 0 && resultadosAutocomplete[indexFocadoAutocomplete]) {
            selecionarColaborador(resultadosAutocomplete[indexFocadoAutocomplete].fun_id);
        } else if (resultadosAutocomplete.length === 1) {
            selecionarColaborador(resultadosAutocomplete[0].fun_id);
        }
    } else if (e.key === 'Escape') {
        fecharDropdownAutocomplete();
    }
}

function fecharDropdownAutocomplete() {
    const dropdown = document.getElementById('dropdown-autocomplete-colab');
    if (dropdown) dropdown.style.display = 'none';
    indexFocadoAutocomplete = -1;
}

/* ===== AUTOCOMPLETE DE EPIs / EQUIPAMENTOS ===== */
function aoDigitarBuscaEpiEntrega(termo) {
    indexFocadoAutocompleteEpi = -1;
    const btnLimpar = document.getElementById('btn-limpar-busca-epi-item');
    if (btnLimpar) {
        btnLimpar.style.display = (termo && termo.length > 0) ? 'block' : 'none';
    }

    const termoNorm = normalizarTexto(termo);
    const termoCaLimpo = String(termo || '').replace(/\D/g, '');

    if (termoNorm === '') {
        resultadosAutocompleteEpi = listaEpisCatalogo.slice(0, 8);
    } else {
        resultadosAutocompleteEpi = listaEpisCatalogo.filter(e => {
            const nomeNorm = normalizarTexto(e.epi_nome);
            const fabNorm = normalizarTexto(e.epi_fabricante);
            const caLimpo = String(e.epi_ca || '').replace(/\D/g, '');

            const matchNome = nomeNorm.includes(termoNorm);
            const matchFab = fabNorm.includes(termoNorm);
            const matchCa = (termoCaLimpo.length > 0 && caLimpo.includes(termoCaLimpo)) || String(e.epi_ca || '').includes(termo);

            return matchNome || matchFab || matchCa;
        });
    }

    renderizarDropdownAutocompleteEpi(termo);
}

function aoFocarBuscaEpiEntrega() {
    const input = document.getElementById('input-busca-epi-item');
    aoDigitarBuscaEpiEntrega(input ? input.value : '');
}

function renderizarDropdownAutocompleteEpi(termoDigitado) {
    const dropdown = document.getElementById('dropdown-autocomplete-epi');
    if (!dropdown) return;

    if (resultadosAutocompleteEpi.length === 0) {
        dropdown.innerHTML = `
            <div class="p-3 text-center text-muted" style="font-size: 13px;">
                <i class="bi bi-search me-1 text-secondary"></i> Nenhum equipamento encontrado com "<strong>${htmlEscape(termoDigitado)}</strong>"
            </div>`;
        dropdown.style.display = 'block';
        return;
    }

    const itensExibir = resultadosAutocompleteEpi.slice(0, 8);
    let html = `
        <div class="px-3 py-2 bg-light border-bottom text-muted d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 11px;">
            <span><i class="bi bi-box-seam me-1 text-primary"></i> <strong class="text-dark">${resultadosAutocompleteEpi.length}</strong> equipamento(s) encontrado(s)</span>
            <span class="d-inline-flex align-items-center gap-1">
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 7px; font-size:11px; font-weight:600;">▲</span>
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 7px; font-size:11px; font-weight:600;">▼</span>
                <span style="color:#64748b;">para navegar</span>
                <span style="color:#64748b;">•</span>
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 10px; font-size:11px; font-weight:600;">Enter</span>
                <span style="color:#64748b;">para escolher</span>
            </span>
        </div>
        <div class="list-group list-group-flush">
    `;

    itensExibir.forEach((e, idx) => {
        const nomeDestacado = destacarTrecho(e.epi_nome, termoDigitado);
        const fab = htmlEscape(e.epi_fabricante || 'Fabricante não informado');
        const caDesc = e.epi_ca ? `C.A. ${e.epi_ca}` : 'Sem C.A.';

        const iniciais = (e.epi_nome || 'EP')
            .split(' ')
            .filter(n => n.length > 0)
            .slice(0, 2)
            .map(n => n[0].toUpperCase())
            .join('');

        let statusCaHtml = '';
        if (e.epi_validade_ca) {
            const dataVenc = new Date(e.epi_validade_ca);
            const hoje = new Date();
            const dataFmt = e.epi_validade_ca.split('-').reverse().join('/');
            if (dataVenc < hoje) {
                statusCaHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 10px;"><i class="bi bi-exclamation-triangle me-1"></i>C.A. Vencido (${dataFmt})</span>`;
            } else {
                statusCaHtml = `<span class="text-muted ms-1" style="font-size: 10px;"><i class="bi bi-calendar-check me-1"></i>Val: ${dataFmt}</span>`;
            }
        }

        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action p-2 px-3 border-0 border-bottom d-flex align-items-center gap-2 autocomplete-item auto-epi-entrega-item" 
               id="auto-epi-entrega-${idx}"
               onclick="selecionarEpiEntrega(${e.epi_id})"
               onmouseover="destacarItemEpiEntrega(${idx})">
                <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 38px; height: 38px; font-size: 13px; background: #dbeafe; color: #1d4ed8;">
                    ${iniciais}
                </div>
                <div class="flex-grow-1 min-w-0" style="line-height: 1.3;">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="fw-semibold text-body text-truncate" style="font-size: 13px;">${nomeDestacado}</span>
                        <span class="badge bg-light text-secondary border flex-shrink-0" style="font-size: 10px; padding: 3px 8px; border-radius: 6px;">ID #${e.epi_id}</span>
                    </div>
                    <div class="text-muted d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                        <span><i class="bi bi-building me-1 text-secondary"></i>${fab}</span> • 
                        <span><i class="bi bi-shield me-1 text-secondary"></i>${caDesc}</span>
                        ${statusCaHtml ? ' • ' + statusCaHtml : ''}
                    </div>
                </div>
            </a>
        `;
    });

    html += `</div>`;
    dropdown.innerHTML = html;
    dropdown.style.display = 'block';
}

function destacarItemEpiEntrega(idx) {
    indexFocadoAutocompleteEpi = idx;
    const items = document.querySelectorAll('.auto-epi-entrega-item');
    items.forEach((el, i) => {
        if (i === idx) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function aoTeclarBuscaEpiEntrega(e) {
    const dropdown = document.getElementById('dropdown-autocomplete-epi');
    if (!dropdown || dropdown.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (resultadosAutocompleteEpi.length > 0) {
            indexFocadoAutocompleteEpi = (indexFocadoAutocompleteEpi + 1) % Math.min(resultadosAutocompleteEpi.length, 8);
            destacarItemEpiEntrega(indexFocadoAutocompleteEpi);
            const el = document.getElementById(`auto-epi-entrega-${indexFocadoAutocompleteEpi}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (resultadosAutocompleteEpi.length > 0) {
            indexFocadoAutocompleteEpi = (indexFocadoAutocompleteEpi - 1 + Math.min(resultadosAutocompleteEpi.length, 8)) % Math.min(resultadosAutocompleteEpi.length, 8);
            destacarItemEpiEntrega(indexFocadoAutocompleteEpi);
            const el = document.getElementById(`auto-epi-entrega-${indexFocadoAutocompleteEpi}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indexFocadoAutocompleteEpi >= 0 && resultadosAutocompleteEpi[indexFocadoAutocompleteEpi]) {
            selecionarEpiEntrega(resultadosAutocompleteEpi[indexFocadoAutocompleteEpi].epi_id);
        } else if (resultadosAutocompleteEpi.length === 1) {
            selecionarEpiEntrega(resultadosAutocompleteEpi[0].epi_id);
        }
    } else if (e.key === 'Escape') {
        fecharDropdownAutocompleteEpi();
    }
}

function fecharDropdownAutocompleteEpi() {
    const dropdown = document.getElementById('dropdown-autocomplete-epi');
    if (dropdown) dropdown.style.display = 'none';
    indexFocadoAutocompleteEpi = -1;
}

function selecionarEpiEntrega(epiId) {
    const epi = listaEpisCatalogo.find(x => parseInt(x.epi_id) === parseInt(epiId));
    if (!epi) return;

    epiSelecionadoEntrega = epi;
    document.getElementById('select-epi-item').value = epi.epi_id;

    const input = document.getElementById('input-busca-epi-item');
    if (input) {
        input.value = `${epi.epi_nome} — C.A. ${epi.epi_ca || 'Isento'}`;
    }

    const btnLimpar = document.getElementById('btn-limpar-busca-epi-item');
    if (btnLimpar) btnLimpar.style.display = 'block';

    const alertaCa = document.getElementById('alerta-ca-vencido');
    if (alertaCa) {
        if (epi.is_vencido) {
            alertaCa.classList.remove('d-none');
        } else {
            alertaCa.classList.add('d-none');
        }
    }

    fecharDropdownAutocompleteEpi();
}

function limparSelecaoEpiEntrega() {
    epiSelecionadoEntrega = null;
    document.getElementById('select-epi-item').value = '';
    const input = document.getElementById('input-busca-epi-item');
    if (input) {
        input.value = '';
        input.focus();
    }
    const btnLimpar = document.getElementById('btn-limpar-busca-epi-item');
    if (btnLimpar) btnLimpar.style.display = 'none';
    const alertaCa = document.getElementById('alerta-ca-vencido');
    if (alertaCa) alertaCa.classList.add('d-none');
    fecharDropdownAutocompleteEpi();
}

// Fecha dropdowns se clicar fora
document.addEventListener('click', function(e) {
    const inputColab = document.getElementById('input-busca-colaborador');
    const wrapperColab = inputColab ? inputColab.closest('.position-relative') : null;
    if (wrapperColab && !wrapperColab.contains(e.target)) {
        fecharDropdownAutocomplete();
    }

    const inputEpi = document.getElementById('input-busca-epi-item');
    const wrapperEpi = inputEpi ? inputEpi.closest('.position-relative') : null;
    if (wrapperEpi && !wrapperEpi.contains(e.target)) {
        fecharDropdownAutocompleteEpi();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const inputColab = document.getElementById('input-busca-colaborador');
    if (inputColab) {
        inputColab.addEventListener('input', function() {
            aoDigitarBuscaColaborador(this.value);
        });
        inputColab.addEventListener('keyup', function() {
            aoDigitarBuscaColaborador(this.value);
        });
    }

    const ddColab = document.getElementById('dropdown-autocomplete-colab');
    if (ddColab) {
        ddColab.addEventListener('mousedown', function(e) {
            const item = e.target.closest('.autocomplete-item');
            if (item) {
                e.preventDefault();
                const match = item.id ? item.id.match(/\d+$/) : null;
                if (match && resultadosAutocomplete[match[0]]) {
                    selecionarColaborador(resultadosAutocomplete[match[0]].fun_id);
                }
            }
        });
    }
});

/**
 * Gera um UUID v4 para idempotência da operação idêntico ao aplicativo Android
 */
function gerarUUIDv4() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

/**
 * Seleciona o colaborador e consulta status de PIN e EPIs em posse
 */
async function selecionarColaborador(funId) {
    if (!funId) {
        limparSelecaoColaborador();
        return;
    }

    const f = listaFuncionarios.find(x => parseInt(x.fun_id) === parseInt(funId));
    if (!f) {
        limparSelecaoColaborador();
        return;
    }

    colaboradorSelecionado = {
        id: parseInt(f.fun_id),
        nome: f.fun_nome,
        cpf: f.fun_cpf,
        matricula: f.fun_matricula,
        cargo: f.fun_cargo,
        setor: f.fun_departamento
    };

    // Preenche campo de busca e esconde dropdown
    const input = document.getElementById('input-busca-colaborador');
    if (input) {
        input.value = `${colaboradorSelecionado.nome} — ${colaboradorSelecionado.cargo}`;
    }
    document.getElementById('select-funcionario').value = colaboradorSelecionado.id;
    document.getElementById('btn-limpar-busca-colab').classList.remove('d-none');
    fecharDropdownAutocomplete();

    // Atualiza Card de Informações
    document.getElementById('info-colab-nome').innerText = colaboradorSelecionado.nome;
    document.getElementById('info-colab-cargo').innerText = colaboradorSelecionado.cargo;
    document.getElementById('info-colab-setor').innerText = colaboradorSelecionado.setor;
    document.getElementById('info-colab-cpf').innerText = formatarCpf(colaboradorSelecionado.cpf);
    document.getElementById('info-colab-matricula').innerText = colaboradorSelecionado.matricula;
    document.getElementById('card-detalhes-colab').classList.remove('d-none');

    // 1. Verifica Assinatura / PIN
    verificarStatusPin(funId);

    // 2. Carrega EPIs atualmente em posse
    carregarEpisEmPosse(funId);
}

function limparSelecaoColaborador() {
    colaboradorSelecionado = null;
    const input = document.getElementById('input-busca-colaborador');
    if (input) {
        input.value = '';
        input.focus();
    }
    document.getElementById('select-funcionario').value = '';
    document.getElementById('btn-limpar-busca-colab').classList.add('d-none');
    fecharDropdownAutocomplete();
    document.getElementById('card-detalhes-colab').classList.add('d-none');
    document.getElementById('card-epis-em-posse').classList.add('d-none');
    carrinhoEpi = [];
    devolucoesVinculadas = [];
    renderizarCarrinho();
}

function formatarCpf(cpf) {
    if (!cpf) return '---';
    const limpo = String(cpf).replace(/\D/g, '');
    if (limpo.length === 11) {
        return limpo.substr(0, 3) + '.***.***-' + limpo.substr(9, 2);
    }
    return cpf;
}

/**
 * Consulta API para verificar se o colaborador possui PIN ativo, bloqueado ou sem cadastro
 */
async function verificarStatusPin(funId) {
    const badge = document.getElementById('info-colab-pin-badge');
    badge.innerHTML = '<span class="badge bg-secondary"><i class="bi bi-hourglass-split"></i> Verificando PIN...</span>';

    try {
        let status = '';
        let assId = null;

        const resAss = await fetch(`${PROXY_URL}?route=assinaturas/funcionario/${funId}`).then(r => r.json()).catch(() => null);
        if (resAss && resAss.success && resAss.data) {
            status = (resAss.data.ass_status || '').toUpperCase();
            assId = resAss.data.ass_id;
        }

        if (!status) {
            const resFunc = await fetch(`${PROXY_URL}?route=funcionarios/${funId}`).then(r => r.json()).catch(() => null);
            if (resFunc && resFunc.success && resFunc.data) {
                const f = resFunc.data;
                status = (f.assinatura_status || (f.ass_senha_hash ? 'ATIVO' : '')).toUpperCase();
                assId = f.ass_id || f.fun_id;
            }
        }

        if (status === 'ATIVO') {
            badge.innerHTML = `
                <span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i> PIN Ativo e Válido</span>
                <button type="button" class="btn btn-xs btn-outline-primary ms-1" style="font-size: 11px;" onclick="abrirModalPinRapido()">Redefinir</button>
            `;
        } else if (status === 'BLOQUEADO') {
            const idParaDesbloqueio = assId || funId;
            badge.innerHTML = `
                <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i> PIN Bloqueado</span>
                <button type="button" class="btn btn-xs btn-outline-danger ms-1" onclick="desbloquearPinColaborador(${idParaDesbloqueio})">Desbloquear</button>
            `;
        } else {
            badge.innerHTML = `
                <span class="badge bg-warning text-dark"><i class="bi bi-key me-1"></i> Sem PIN Cadastrado</span>
                <button type="button" class="btn btn-sm btn-primary ms-1 py-0 px-2" style="font-size: 11px;" onclick="abrirModalPinRapido()">Cadastrar PIN</button>
            `;
        }
    } catch (e) {
        badge.innerHTML = '<span class="badge bg-secondary">Erro ao verificar PIN</span>';
    }
}

/**
 * Carrega EPIs em posse do colaborador para permitir substituição direta
 */
async function carregarEpisEmPosse(funId) {
    const card = document.getElementById('card-epis-em-posse');
    const corpo = document.getElementById('tabela-itens-posse');
    corpo.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2"><div class="spinner-border spinner-border-sm me-1"></div> Buscando histórico...</td></tr>';
    card.classList.remove('d-none');

    try {
        const res = await fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`).then(r => r.json());
        if (res.success && res.data) {
            let entregas = Array.isArray(res.data) ? res.data : (res.data.entregas || []);
            let itensEmPosse = [];

            entregas.forEach(ent => {
                const itens = ent.itens || [];
                itens.forEach(it => {
                    const status = (it.item_status || it.ite_status_item || 'ENTREGUE').toUpperCase();
                    if (status === 'ENTREGUE' || status === 'EM USO') {
                        itensEmPosse.push({
                            item_id: it.item_id || it.ite_id,
                            entr_id: ent.entr_id,
                            epi_id: it.epi_id,
                            epi_nome: it.item_epi_nome_snapshot || it.epi_nome || 'EPI',
                            epi_ca: it.item_epi_ca_snapshot || it.epi_ca || 'Isento',
                            tamanho: it.item_tamanho || it.ite_tamanho || 'Único',
                            data_entrega: ent.entr_data_entrega
                        });
                    }
                });
            });

            document.getElementById('badge-total-posse').innerText = `${itensEmPosse.length} itens`;

            if (itensEmPosse.length === 0) {
                corpo.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Colaborador não possui nenhum EPI ativo em posse no momento.</td></tr>';
                return;
            }

            let html = '';
            itensEmPosse.forEach(item => {
                const dt = item.data_entrega ? new Date(item.data_entrega).toLocaleDateString('pt-BR') : '---';
                html += `
                    <tr>
                        <td class="fw-semibold">${item.epi_nome}</td>
                        <td>${item.epi_ca}</td>
                        <td>${item.tamanho}</td>
                        <td>${dt}</td>
                        <td><span class="badge bg-success-subtle text-success border border-success-subtle">Em Uso</span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-warning py-0 px-2" style="font-size: 11px;" 
                                    onclick="abrirModalDevolucaoVinculada(${item.item_id}, ${item.epi_id}, '${item.epi_nome.replace(/'/g, "\\'")}')">
                                <i class="bi bi-arrow-repeat me-1"></i> Substituir
                            </button>
                        </td>
                    </tr>
                `;
            });
            corpo.innerHTML = html;
        } else {
            corpo.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-2">Sem histórico de posse.</td></tr>';
        }
    } catch (e) {
        corpo.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-2">Erro ao obter itens em posse.</td></tr>';
    }
}

/**
 * Atualiza campos dinâmicos ao selecionar EPI no catálogo
 */
function aoSelecionarEpiCatalogo(epiId) {
    const alertaCa = document.getElementById('alerta-ca-vencido');
    if (!epiId) {
        alertaCa.classList.add('d-none');
        return;
    }
    const select = document.getElementById('select-epi-item');
    const opt = select.options[select.selectedIndex];
    const isVencido = opt.getAttribute('data-vencido') === '1';

    if (isVencido) {
        alertaCa.classList.remove('d-none');
    } else {
        alertaCa.classList.add('d-none');
    }
}

/**
 * Adiciona um item normal ao carrinho de entrega
 */
function adicionarItemAoCarrinho(devolucaoVinculadaObj = null) {
    if (!colaboradorSelecionado) {
        alert('Selecione primeiro o colaborador receptor!');
        return;
    }

    const epiId = document.getElementById('select-epi-item').value;
    if (!epiId) {
        alert('Por favor, busque e selecione um equipamento do catálogo!');
        return;
    }

    const epi = epiSelecionadoEntrega || listaEpisCatalogo.find(x => parseInt(x.epi_id) === parseInt(epiId));
    if (!epi) {
        alert('Equipamento inválido selecionado.');
        return;
    }

    const motivo = document.getElementById('input-motivo-item').value;
    const qtd = parseInt(document.getElementById('input-qtd-item').value) || 1;
    const tamanho = document.getElementById('input-tamanho-item').value;
    const lote = document.getElementById('input-lote-item').value.trim();

    carrinhoEpi.push({
        epi_id: parseInt(epi.epi_id),
        epi_nome: epi.epi_nome,
        epi_ca: epi.epi_ca || 'Isento',
        quantidade: qtd,
        tamanho: tamanho,
        lote: lote || null,
        motivo_especifico: motivo,
        tipo_item: (epi.epi_ca && epi.epi_ca !== 'Isento') ? 'EPI_COM_CA' : 'ITEM_SEGURANCA_SEM_CA',
        devolucao_vinculada: devolucaoVinculadaObj
    });

    renderizarCarrinho();
    limparSelecaoEpiEntrega();
    document.getElementById('input-lote-item').value = '';
}

/**
 * Renderiza o carrinho de entrega
 */
function renderizarCarrinho() {
    const corpo = document.getElementById('tabela-carrinho-corpo');
    const badge = document.getElementById('badge-itens-carrinho');
    badge.innerText = `${carrinhoEpi.length} itens`;

    if (carrinhoEpi.length === 0) {
        corpo.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Nenhum equipamento adicionado ao carrinho ainda.</td></tr>';
        return;
    }

    let html = '';
    carrinhoEpi.forEach((item, index) => {
        let devInfo = '<span class="text-muted">---</span>';
        if (item.devolucao_vinculada) {
            devInfo = `<span class="badge bg-warning text-dark border"><i class="bi bi-arrow-repeat"></i> Substituição (Item #${item.devolucao_vinculada.item_entrega_original_id})</span>`;
        }

        html += `
            <tr>
                <td class="fw-bold text-primary">${item.epi_nome}</td>
                <td>${item.epi_ca}</td>
                <td>${item.tamanho} ${item.lote ? `<small class="text-muted d-block">Lote: ${item.lote}</small>` : ''}</td>
                <td class="fw-bold text-center">${item.quantidade}</td>
                <td><span class="badge bg-light text-dark border">${item.motivo_especifico}</span></td>
                <td>${devInfo}</td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" onclick="removerItemCarrinho(${index})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    corpo.innerHTML = html;
}

function removerItemCarrinho(index) {
    carrinhoEpi.splice(index, 1);
    renderizarCarrinho();
}

/**
 * Modal de Devolução Vinculada
 */
function abrirModalDevolucaoVinculada(itemId, epiId, epiNome) {
    document.getElementById('modal-dev-item-orig-id').value = itemId;
    document.getElementById('modal-dev-epi-id').value = epiId;
    document.getElementById('modal-dev-epi-nome').innerText = `${epiNome} (Item #${itemId})`;
    
    // Pré-seleciona o EPI no autocomplete do formulário
    if (epiId && typeof selecionarEpiEntrega === 'function') {
        selecionarEpiEntrega(epiId);
    }
    const inputMotivo = document.getElementById('input-motivo-item');
    if (inputMotivo) {
        inputMotivo.value = 'SUBSTITUICAO';
    }

    const modalEl = document.getElementById('modalDevolucaoVinculada');
    if (modalEl) {
        new bootstrap.Modal(modalEl).show();
    }
}

function confirmarSubstituicaoVinculada() {
    const origItemId = parseInt(document.getElementById('modal-dev-item-orig-id').value);
    const origEpiId = parseInt(document.getElementById('modal-dev-epi-id').value);
    const rawEpiNome = document.getElementById('modal-dev-epi-nome').innerText;
    const origEpiNome = rawEpiNome.split(' (Item #')[0];
    const motivoDev = document.getElementById('modal-dev-motivo').value;
    const condicaoDev = document.getElementById('modal-dev-condicao').value;
    const destinoDev = document.getElementById('modal-dev-destino').value;
    const obsDev = document.getElementById('modal-dev-obs').value.trim();

    const devolucaoObj = {
        item_entrega_original_id: origItemId,
        motivo_devolucao: motivoDev,
        condicao: condicaoDev,
        destino: destinoDev,
        justificativa: obsDev || null,
        data_devolucao: new Date().toISOString()
    };

    const modalInst = bootstrap.Modal.getInstance(document.getElementById('modalDevolucaoVinculada'));
    if (modalInst) {
        modalInst.hide();
    }

    // Busca o EPI no catálogo
    let epi = epiSelecionadoEntrega;
    if (!epi) {
        const selectVal = document.getElementById('select-epi-item') ? document.getElementById('select-epi-item').value : null;
        const targetId = selectVal ? parseInt(selectVal) : origEpiId;
        epi = listaEpisCatalogo.find(x => parseInt(x.epi_id) === targetId);
    }

    const tamanhoInput = document.getElementById('input-tamanho-item');
    const tamanho = tamanhoInput ? tamanhoInput.value : 'Único';

    if (epi) {
        carrinhoEpi.push({
            epi_id: parseInt(epi.epi_id),
            epi_nome: epi.epi_nome,
            epi_ca: epi.epi_ca || 'Isento',
            quantidade: 1,
            tamanho: tamanho,
            lote: null,
            motivo_especifico: 'SUBSTITUICAO',
            tipo_item: (epi.epi_ca && epi.epi_ca !== 'Isento') ? 'EPI_COM_CA' : 'ITEM_SEGURANCA_SEM_CA',
            devolucao_vinculada: devolucaoObj
        });
    } else {
        carrinhoEpi.push({
            epi_id: origEpiId,
            epi_nome: origEpiNome,
            epi_ca: 'Isento',
            quantidade: 1,
            tamanho: tamanho,
            lote: null,
            motivo_especifico: 'SUBSTITUICAO',
            tipo_item: 'EPI_COM_CA',
            devolucao_vinculada: devolucaoObj
        });
    }

    renderizarCarrinho();
}

/**
 * Modal de PIN Rápido
 */
function abrirModalPinRapido() {
    if (!colaboradorSelecionado) {
        alert('Selecione primeiro o colaborador!');
        return;
    }
    document.getElementById('modal-pin-novo').value = '';
    document.getElementById('modal-pin-confirma').value = '';
    new bootstrap.Modal(document.getElementById('modalPinRapido')).show();
}

async function salvarPinRapido() {
    const pin = document.getElementById('modal-pin-novo').value.trim();
    const conf = document.getElementById('modal-pin-confirma').value.trim();

    if (!pin || pin.length < 4 || pin.length > 10) {
        alert('A senha/PIN deve conter de 4 a 10 caracteres alfanuméricos!');
        return;
    }
    if (pin !== conf) {
        alert('A confirmação do PIN não confere!');
        return;
    }

    try {
        const payload = {
            fun_id: colaboradorSelecionado.id,
            pin: pin,
            ass_senha: pin
        };
        let res = await fetch(`${PROXY_URL}?route=assinaturas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(r => r.json());

        // Se falhou indicando que o funcionário já possui PIN, tenta redefinir
        if (!res.success) {
            const resRedefinir = await fetch(`${PROXY_URL}?route=assinaturas/redefinir`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json());

            if (resRedefinir.success) {
                res = resRedefinir;
            }
        }

        if (res.success) {
            alert('PIN salvo com sucesso!');
            bootstrap.Modal.getInstance(document.getElementById('modalPinRapido')).hide();
            verificarStatusPin(colaboradorSelecionado.id);
            document.getElementById('input-pin-assinatura').value = pin;
        } else {
            alert('Erro ao salvar PIN: ' + (res.message || 'Tente novamente.'));
        }
    } catch (e) {
        alert('Falha na comunicação com a API: ' + e.message);
    }
}

async function desbloquearPinColaborador(assId) {
    if (!confirm('Deseja desbloquear a assinatura eletrônica deste colaborador?')) return;
    try {
        const response = await fetch(`${PROXY_URL}?route=assinaturas/desbloquear/${assId}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        });

        const rawText = await response.text();
        console.log('Desbloqueio response status:', response.status, 'body:', rawText);

        let res;
        try {
            res = JSON.parse(rawText);
        } catch (jsonErr) {
            alert('Resposta inesperada do servidor. Verifique se sua sessão está ativa.');
            return;
        }

        if (res.logged_out || res.status_code === 401) {
            alert('Sua sessão expirou. Faça login novamente.');
            window.location.href = '../login.php';
            return;
        }

        if (res.success) {
            alert('PIN desbloqueado com sucesso!');
            verificarStatusPin(colaboradorSelecionado.id);
        } else {
            alert('Falha ao desbloquear: ' + (res.message || 'Erro desconhecido'));
        }
    } catch (e) {
        console.error('Erro no desbloqueio:', e);
        alert('Erro ao conectar: ' + e.message);
    }
}

/**
 * Processamento e Envio Final da Entrega
 */
async function processarEnvioEntrega() {
    if (!colaboradorSelecionado) {
        alert('Por favor, selecione o colaborador receptor!');
        return;
    }
    if (carrinhoEpi.length === 0) {
        alert('Adicione ao menos um EPI ao carrinho de fornecimento!');
        return;
    }
    if (!document.getElementById('check-aceite-termo').checked) {
        alert('É obrigatório assinalar o aceite e ciência do termo de responsabilidade!');
        return;
    }

    const pin = document.getElementById('input-pin-assinatura').value.trim();
    if (!pin) {
        alert('Por favor, digite o PIN do colaborador para assinar eletronicamente!');
        document.getElementById('input-pin-assinatura').focus();
        return;
    }

    const btn = document.getElementById('btn-finalizar-entrega');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Autenticando Assinatura e Registrando...';

    const clientOperationId = gerarUUIDv4();
    const usuarioLogadoId = <?= json_encode((int)($currentUser['usu_id'] ?? 5)) ?>;

    const payload = {
        fun_id: colaboradorSelecionado.id,
        usu_id: usuarioLogadoId,
        pin: pin,
        ass_senha: pin,
        entr_motivo: carrinhoEpi[0].motivo_especifico || 'FORNECIMENTO',
        motivo: carrinhoEpi[0].motivo_especifico || 'FORNECIMENTO',
        metodo_aceite: 'PIN',
        client_operation_id: clientOperationId,
        origem: 'WEB_CONTINGENCIA',
        itens: carrinhoEpi.map(item => {
            const itemObj = {
                epi_id: item.epi_id,
                quantidade: item.quantidade,
                item_quantidade: item.quantidade,
                tamanho: item.tamanho,
                item_tamanho: item.tamanho,
                lote: item.lote,
                item_numero_lote: item.lote,
                motivo_especifico: item.motivo_especifico,
                item_motivo_entrega: item.motivo_especifico,
                tipo_item: item.tipo_item
            };

            if (item.devolucao_vinculada) {
                const origId = item.devolucao_vinculada.item_entrega_original_id;
                itemObj.devolucao_vinculada = {
                    item_id_anterior: origId,
                    item_entrega_original_id: origId,
                    quantidade_devolvida: 1,
                    motivo: item.devolucao_vinculada.motivo_devolucao || 'SUBSTITUICAO',
                    motivo_devolucao: item.devolucao_vinculada.motivo_devolucao || 'SUBSTITUICAO',
                    condicao: item.devolucao_vinculada.condicao || 'DESCARTADO',
                    destino: item.devolucao_vinculada.destino || 'DESCARTE',
                    observacao: item.devolucao_vinculada.justificativa || null,
                    justificativa: item.devolucao_vinculada.justificativa || null,
                    data_devolucao: item.devolucao_vinculada.data_devolucao || new Date().toISOString()
                };
            }
            return itemObj;
        })
    };

    try {
        const response = await fetch(`${PROXY_URL}?route=entregas`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const rawText = await response.text();
        let res;
        try {
            res = JSON.parse(rawText);
        } catch (jsonErr) {
            res = { success: false, message: 'A API respondeu em um formato inesperado. Verifique se sua sessão está ativa.' };
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i> Assinar e Finalizar Entrega';

        if (res.logged_out || res.status_code === 401) {
            alert('Sua sessão expirou. Você será redirecionado para a tela de login.');
            window.location.href = '../login.php';
            return;
        }

        if (res.success && res.data) {
            ultimaEntregaConcluidaId = res.data.entrega_id;
            document.getElementById('sucesso-entrega-id').innerText = '#' + res.data.entrega_id;
            document.getElementById('sucesso-colab-nome').innerText = colaboradorSelecionado.nome;
            document.getElementById('sucesso-data-hora').innerText = new Date().toLocaleString('pt-BR');
            document.getElementById('sucesso-hash').innerText = res.data.entr_hash_assinatura || '---';

            new bootstrap.Modal(document.getElementById('modalSucessoEntrega')).show();
        } else {
            alert('Falha ao finalizar entrega:\n' + (res.message || 'Verifique o PIN digitado ou tente novamente.'));
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-shield-lock-fill me-2"></i> Assinar e Finalizar Entrega';
        alert('Erro de comunicação: ' + e.message);
    }
}

/**
 * Impressão do Recibo da Entrega Gerada
 */
function imprimirReciboEntregaGerada() {
    if (!ultimaEntregaConcluidaId) return;
    const url = `ficha_colaborador.php?id=${colaboradorSelecionado.id}`;
    window.open(url, '_blank');
}

function reiniciarFluxoEntrega() {
    bootstrap.Modal.getInstance(document.getElementById('modalSucessoEntrega')).hide();
    limparSelecaoColaborador();
    document.getElementById('input-pin-assinatura').value = '';
}

/**
 * Leitor de QR Code
 */
function abrirLeitorQrCode() {
    new bootstrap.Modal(document.getElementById('modalQrCode')).show();
    setTimeout(() => {
        html5QrScanner = new Html5QrcodeScanner("qr-reader", { fps: 10, qrbox: 250 });
        html5QrScanner.render(onScanSuccess);
    }, 400);
}

function fecharLeitorQrCode() {
    if (html5QrScanner) {
        try { html5QrScanner.clear(); } catch(e){}
    }
}

function onScanSuccess(decodedText) {
    fecharLeitorQrCode();
    bootstrap.Modal.getInstance(document.getElementById('modalQrCode')).hide();
    processarCodigoQr(decodedText);
}

function processarCodigoQr(codigo) {
    if (!codigo) return;
    codigo = codigo.trim();

    // Tenta encontrar por ID, CPF ou Matrícula na lista em memória
    const limpo = codigo.replace(/\D/g, '');
    const encontrado = listaFuncionarios.find(f => 
        String(f.fun_id) === codigo ||
        f.fun_cpf === codigo ||
        (limpo.length > 0 && f.fun_cpf.replace(/\D/g, '') === limpo) ||
        f.fun_matricula === codigo
    );

    if (encontrado) {
        selecionarColaborador(encontrado.fun_id);
    } else {
        // Consulta na API por rota de qrcode
        fetch(`${PROXY_URL}?route=funcionarios/qrcode/${encodeURIComponent(codigo)}`)
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                // Adiciona à lista se não estiver
                if (!listaFuncionarios.some(f => f.fun_id === res.data.fun_id)) {
                    listaFuncionarios.push({
                        fun_id: res.data.fun_id,
                        fun_nome: res.data.fun_nome,
                        fun_cpf: res.data.fun_cpf || '',
                        fun_matricula: res.data.fun_matricula || ('MAT-' + res.data.fun_id),
                        fun_cargo: res.data.fun_cargo || 'Operacional',
                        fun_departamento: res.data.fun_departamento || 'Geral'
                    });
                }
                selecionarColaborador(res.data.fun_id);
            } else {
                alert('Colaborador não identificado pelo QR Code.');
            }
        })
        .catch(() => alert('QR Code não reconhecido.'));
    }
}

function executarAcaoSubmenuEntrega(acao) {
    if (acao === 'nova_entrega' || acao === 'nova') {
        if (window.location.pathname.indexOf('nova_entrega.php') !== -1) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
        }
    } else if (acao === 'devolucao' || acao === 'devolucoes') {
        window.location.href = '<?= APP_ROOT ?>pages/devolucoes.php';
    } else {
        window.location.href = '<?= APP_ROOT ?>pages/entregas.php';
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
