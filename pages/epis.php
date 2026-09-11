<?php
declare(strict_types=1);

$page_title = 'Catálogo de EPIs';
$active_menu = 'epis';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR']; // RH possui acesso somente consulta (API bloqueia escrita)

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;

// Lida com formulários de cadastro e edição (PHP POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar EPI
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['epi_nome'] ?? '');
        $tipoItem = $_POST['epi_tipo_item'] ?? 'EPI_COM_CA';
        $ca = trim($_POST['epi_ca'] ?? '');
        $vencimentoCa = $_POST['epi_vencimento_ca'] ?? '';
        $fabricante = trim($_POST['epi_fabricante'] ?? '');
        $validadeUsoDias = (int)($_POST['epi_validade_uso_dias'] ?? 0);
        $status = $_POST['epi_status'] ?? 'ATIVO';
        
        // Limpa valor monetário da máscara: R$ 123,45 -> 123.45
        $valorStr = preg_replace('/[^0-9,.]/', '', $_POST['epi_valor'] ?? '0,00');
        $valorStr = str_replace('.', '', $valorStr);
        $valorStr = str_replace(',', '.', $valorStr);
        $valor = (float)$valorStr;
        
        $origemPreco = $_POST['epi_origem_preco'] ?? 'COMPRA_DIRETA';
        $localizacao = trim($_POST['epi_localizacao'] ?? '');
        $vidaUtilTipo = $_POST['epi_vida_util_tipo'] ?? 'CONTROLADO';
        $vidaUtil = isset($_POST['epi_vida_util']) && $_POST['epi_vida_util'] !== '' ? (int)$_POST['epi_vida_util'] : null;
        $vidaUtilUnidade = $_POST['epi_vida_util_unidade'] ?? null;
        
        $modelo = trim($_POST['epi_modelo'] ?? '');
        $identificacao = trim($_POST['epi_identificacao'] ?? '');
        $refFornecedor = trim($_POST['epi_ref_fornecedor'] ?? '');
        $exigeTamanho = isset($_POST['epi_exige_tamanho']) ? 1 : 0;

        $payload = [
            'epi_nome' => $nome,
            'epi_tipo_item' => $tipoItem,
            'epi_ca' => $ca !== '' ? $ca : null,
            'epi_vencimento_ca' => $vencimentoCa !== '' ? $vencimentoCa : null,
            'epi_fabricante' => $fabricante,
            'epi_validade_uso_dias' => $validadeUsoDias,
            'epi_status' => $status,
            'epi_valor' => $valor,
            'epi_origem_preco' => $origemPreco,
            'epi_localizacao' => $localizacao !== '' ? $localizacao : null,
            'epi_vida_util_tipo' => $vidaUtilTipo,
            'epi_vida_util' => $vidaUtil,
            'epi_vida_util_unidade' => $vidaUtilUnidade !== '' ? $vidaUtilUnidade : null,
            'epi_modelo' => $modelo !== '' ? $modelo : null,
            'epi_identificacao' => $identificacao !== '' ? $identificacao : null,
            'epi_ref_fornecedor' => $refFornecedor !== '' ? $refFornecedor : null,
            'epi_exige_tamanho' => $exigeTamanho,
            'epi_vida_util_obs' => trim($_POST['epi_vida_util_obs'] ?? '') !== '' ? trim($_POST['epi_vida_util_obs']) : null
        ];

        try {
            $response = $api->post('epis', $payload);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'EPI "' . htmlspecialchars($nome) . '" cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar item no catálogo.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 2. Editar/Atualizar EPI
    if ($acao === 'editar') {
        $id = (int)($_POST['epi_id'] ?? 0);
        $nome = trim($_POST['epi_nome'] ?? '');
        $tipoItem = $_POST['epi_tipo_item'] ?? 'EPI_COM_CA';
        $ca = trim($_POST['epi_ca'] ?? '');
        $vencimentoCa = $_POST['epi_vencimento_ca'] ?? '';
        $fabricante = trim($_POST['epi_fabricante'] ?? '');
        $validadeUsoDias = (int)($_POST['epi_validade_uso_dias'] ?? 0);
        $status = $_POST['epi_status'] ?? 'ATIVO';
        
        $valorStr = preg_replace('/[^0-9,.]/', '', $_POST['epi_valor'] ?? '0,00');
        $valorStr = str_replace('.', '', $valorStr);
        $valorStr = str_replace(',', '.', $valorStr);
        $valor = (float)$valorStr;
        
        $origemPreco = $_POST['epi_origem_preco'] ?? 'COMPRA_DIRETA';
        $localizacao = trim($_POST['epi_localizacao'] ?? '');
        $vidaUtilTipo = $_POST['epi_vida_util_tipo'] ?? 'CONTROLADO';
        $vidaUtil = isset($_POST['epi_vida_util']) && $_POST['epi_vida_util'] !== '' ? (int)$_POST['epi_vida_util'] : null;
        $vidaUtilUnidade = $_POST['epi_vida_util_unidade'] ?? null;
        
        $modelo = trim($_POST['epi_modelo'] ?? '');
        $identificacao = trim($_POST['epi_identificacao'] ?? '');
        $refFornecedor = trim($_POST['epi_ref_fornecedor'] ?? '');
        $exigeTamanho = isset($_POST['epi_exige_tamanho']) ? 1 : 0;
        $histFornecedor = trim($_POST['hist_fornecedor'] ?? '');

        $payload = [
            'epi_nome' => $nome,
            'epi_tipo_item' => $tipoItem,
            'epi_ca' => $ca !== '' ? $ca : null,
            'epi_vencimento_ca' => $vencimentoCa !== '' ? $vencimentoCa : null,
            'epi_fabricante' => $fabricante,
            'epi_validade_uso_dias' => $validadeUsoDias,
            'epi_status' => $status,
            'epi_valor' => $valor,
            'epi_origem_preco' => $origemPreco,
            'epi_localizacao' => $localizacao !== '' ? $localizacao : null,
            'epi_vida_util_tipo' => $vidaUtilTipo,
            'epi_vida_util' => $vidaUtil,
            'epi_vida_util_unidade' => $vidaUtilUnidade !== '' ? $vidaUtilUnidade : null,
            'epi_modelo' => $modelo !== '' ? $modelo : null,
            'epi_identificacao' => $identificacao !== '' ? $identificacao : null,
            'epi_ref_fornecedor' => $refFornecedor !== '' ? $refFornecedor : null,
            'epi_exige_tamanho' => $exigeTamanho,
            'epi_vida_util_obs' => trim($_POST['epi_vida_util_obs'] ?? '') !== '' ? trim($_POST['epi_vida_util_obs']) : null,
            'hist_fornecedor' => $histFornecedor !== '' ? $histFornecedor : null
        ];

        try {
            $response = $api->put("epis/{$id}", $payload);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'EPI atualizado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao atualizar item no catálogo.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 3. Excluir/Inativar EPI
    if ($acao === 'excluir') {
        $id = (int)($_POST['epi_id'] ?? 0);

        try {
            $response = $api->delete("epis/{$id}");

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'EPI inativado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao inativar item.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }
}

// Carrega listagem de EPIs
$epis = [];
try {
    $listaRes = $api->get('epis');
    if (isset($listaRes['success']) && $listaRes['success']) {
        $epis = $listaRes['data'];
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de EPIs: ' . $e->getMessage();
}

$podeEditar = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST'], true);
$podeExcluir = in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST'], true);
$podeVerCustos = in_array($userProfile, ['ADMINISTRADOR', 'GESTOR'], true);

// Dados para o Painel de Monitoramento de C.A. (remove campos financeiros de perfis sem permissão)
$episParaPainel = $epis;
if (!$podeVerCustos) {
    $episParaPainel = array_map(static function (array $e): array {
        unset($e['epi_valor'], $e['epi_origem_preco']);
        return $e;
    }, $epis);
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Catálogo de EPIs</h3>
                <p class="text-muted">Gerencie a homologação, rastreabilidade e validade do Certificado de Aprovação (C.A.) dos EPIs.</p>
            </div>

            <div class="d-flex gap-2">
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-outline-secondary btn-view active" id="btn-visao-catalogo" onclick="alternarVisao('catalogo')">
                        <i class="bi bi-box-seam me-1"></i> Catálogo
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-view" id="btn-visao-painel" onclick="alternarVisao('painel')">
                        <i class="bi bi-shield-exclamation me-1"></i> Monitoramento de C.A.
                    </button>
                </div>

                <?php if ($podeEditar): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                        <i class="bi bi-plus-lg me-1"></i> Novo Item
                    </button>
                <?php endif; ?>
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

        <div id="visao-catalogo">
        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-5 position-relative">
                    <label for="busca-input" class="form-label fw-semibold" style="font-size: 12px;">
                        <i class="bi bi-search text-primary me-1"></i> Buscar Equipamento (Tempo Real) *
                    </label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0 text-primary">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        <input type="text" 
                               id="busca-input" 
                               class="form-control border-start-0 border-end-0 py-2" 
                               placeholder="Digite o nome (ex: Prot...), fabricante ou C.A...." 
                               autocomplete="off"
                               oninput="aoDigitarBuscaEpi(this.value)"
                               onfocus="aoFocarBuscaEpi()"
                               onkeydown="aoTeclarBuscaEpi(event)">
                        <button class="btn btn-outline-secondary border-start-0 d-none" 
                                type="button" 
                                id="btn-limpar-busca" 
                                onclick="limparBuscaEpi()" 
                                title="Limpar busca">
                            <i class="bi bi-x-lg"></i>
                        </button>
                    </div>
                    <!-- Dropdown Flutuante de Autocomplete / Sugestões em Tempo Real -->
                    <div id="autocomplete-lista-epis" 
                         class="shadow-lg mt-1 p-0 border" 
                         style="display: none; position: absolute; top: 100%; left: 0; right: 0; width: 100%; max-height: 320px; overflow-y: auto; z-index: 99999; border-radius: 10px; background: #ffffff; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2) !important;">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filtro-tipo" class="form-select" onchange="aplicarFiltrosEpi()">
                        <option value="">Todos os Tipos</option>
                        <option value="EPI_COM_CA">EPI com C.A.</option>
                        <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                        <option value="UNIFORME">Uniforme</option>
                        <option value="OUTRO">Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtro-ca-status" class="form-select" onchange="aplicarFiltrosEpi()">
                        <option value="">Todos os Status C.A.</option>
                        <option value="vigente">Vigente</option>
                        <option value="vencido">Vencido / Próximo</option>
                        <option value="isento">Isento de C.A.</option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-epis">
                    <thead>
                        <tr>
                            <th>Item / Classificação</th>
                            <th>Fabricante</th>
                            <th>C.A.</th>
                            <th>Validade C.A.</th>
                            <th>Vida Útil</th>
                            <?php if ($podeVerCustos): ?>
                                <th>Preço Padrão</th>
                            <?php endif; ?>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($epis)): ?>
                            <tr>
                                <td colspan="<?= $podeVerCustos ? 8 : 7 ?>" class="text-center text-muted py-4">Nenhum item cadastrado no catálogo.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($epis as $epi): ?>
                                <?php
                                $tipoLabel = '';
                                if ($epi['epi_tipo_item'] === 'EPI_COM_CA') $tipoLabel = 'EPI com C.A.';
                                elseif ($epi['epi_tipo_item'] === 'ITEM_SEGURANCA_SEM_CA') $tipoLabel = 'Item sem C.A.';
                                else $tipoLabel = ucfirst(strtolower($epi['epi_tipo_item']));

                                $ca = $epi['epi_ca'] ?? 'Isento';
                                $vencimentoCa = '---';
                                $caStatus = 'isento';
                                $caStatusLabel = 'Isento';
                                
                                if ($epi['epi_tipo_item'] === 'EPI_COM_CA' && !empty($epi['epi_vencimento_ca'])) {
                                    $vencimentoCa = date('d/m/Y', strtotime($epi['epi_vencimento_ca']));
                                    $hoje = new DateTime();
                                    $venc = new DateTime($epi['epi_vencimento_ca']);
                                    $diff = $hoje->diff($venc);
                                    
                                    if ($venc < $hoje) {
                                        $caStatus = 'vencido';
                                        $caStatusLabel = 'Vencido';
                                    } elseif ($diff->days <= 30) {
                                        $caStatus = 'a-vencer';
                                        $caStatusLabel = 'A vencer';
                                    } else {
                                        $caStatus = 'ativo';
                                        $caStatusLabel = 'Vigente';
                                    }
                                }

                                $vidaUtil = 'Não controlada';
                                if ($epi['epi_vida_util_tipo'] === 'CONTROLADO') {
                                    $vidaUtil = $epi['epi_vida_util'] . ' ' . strtolower($epi['epi_vida_util_unidade'] ?? 'dias');
                                }
                                ?>
                                <tr class="epi-row" 
                                    data-id="<?= (int)$epi['epi_id'] ?>"
                                    data-nome="<?= htmlspecialchars(strtolower($epi['epi_nome'])) ?>"
                                    data-fabricante="<?= htmlspecialchars(strtolower($epi['epi_fabricante'])) ?>"
                                    data-ca="<?= htmlspecialchars($epi['epi_ca'] ?? '') ?>"
                                    data-tipo="<?= htmlspecialchars($epi['epi_tipo_item']) ?>"
                                    data-castatus="<?= htmlspecialchars($caStatus) ?>"
                                    data-situacao="<?= htmlspecialchars($epi['epi_status']) ?>">
                                    
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($epi['epi_nome']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($tipoLabel) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($epi['epi_fabricante']) ?></td>
                                    <td class="fw-medium"><?= htmlspecialchars($ca) ?></td>
                                    <td>
                                        <?php if ($epi['epi_tipo_item'] === 'EPI_COM_CA'): ?>
                                            <span class="status-badge <?= $caStatus ?>"><?= $vencimentoCa ?> (<?= $caStatusLabel ?>)</span>
                                        <?php else: ?>
                                            <span class="text-muted">---</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted"><?= htmlspecialchars($vidaUtil) ?></td>
                                    <?php if ($podeVerCustos): ?>
                                        <td class="fw-bold text-success"><?= formatarValorMonetario((float)$epi['epi_valor']) ?></td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="status-badge <?= strtolower($epi['epi_status']) ?>"><?= htmlspecialchars($epi['epi_status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button class="btn btn-sm btn-light border py-1 px-2" onclick="verFichaEpi(<?= htmlspecialchars(json_encode($epi)) ?>)" title="Ver Detalhes e Rastreabilidade">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <?php if ($podeEditar): ?>
                                                <button class="btn btn-sm btn-light border text-primary py-1 px-2" onclick="prepararEdicao(<?= htmlspecialchars(json_encode($epi)) ?>)" title="Editar dados">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($podeExcluir && $epi['epi_status'] === 'ATIVO'): ?>
                                                <button class="btn btn-sm btn-light border text-danger py-1 px-2" onclick="confirmarExclusao(<?= $epi['epi_id'] ?>, '<?= htmlspecialchars($epi['epi_nome']) ?>')" title="Inativar Item">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div><!-- /visao-catalogo -->

        <!-- ================= PAINEL DE MONITORAMENTO DE C.A. ================= -->
        <div id="visao-painel-ca" class="d-none">
            <div class="card-custom">
                <h5 class="fw-bold mb-1 text-color-primary"><i class="bi bi-shield-exclamation me-2"></i>Painel de Monitoramento de C.A.</h5>
                <p class="text-muted" style="font-size: 13px;">Validação de conformidade legal de uso do EPI: acompanhamento da validade dos Certificados de Aprovação junto ao Ministério do Trabalho.</p>

                <div class="d-flex flex-wrap gap-2 mb-4" id="ca-chips"></div>

                <div id="ca-lista" class="d-flex flex-column gap-3"></div>
            </div>
        </div>
    </div>


</div>

<!-- ================= MODAIS DE AÇÃO ================= -->

<!-- 1. Modal Cadastrar -->
<div class="modal fade" id="modalCadastrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php" novalidate>
            <input type="hidden" name="acao" value="cadastrar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-box-seam me-2"></i>Novo Item no Catálogo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Item *</label>
                        <input type="text" class="form-control" name="epi_nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Classificação / Tipo de Item *</label>
                        <select class="form-select" name="epi_tipo_item" id="cad-tipo-item" onchange="toggleCaFields('cad')">
                            <option value="EPI_COM_CA">EPI com C.A.</option>
                            <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                            <option value="UNIFORME">Uniforme</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                </div>

                <!-- Campos de CA (Apenas se for EPI_COM_CA) -->
                <div class="row" id="cad-grupo-ca">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Certificado de Aprovação (C.A.) *</label>
                        <input type="text" class="form-control" name="epi_ca" id="cad-input-ca" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vencimento do C.A. *</label>
                        <input type="date" class="form-control" name="epi_vencimento_ca" id="cad-input-venc-ca" required>
                    </div>
                </div>

                <!-- Campos de Rastreabilidade (Apenas se for SEM CA / Uniforme / Outro) -->
                <div class="row d-none" id="cad-grupo-rastreabilidade">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" name="epi_modelo" placeholder="Ex: Modelo A1">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Identificação Interna</label>
                        <input type="text" class="form-control" name="epi_identificacao" placeholder="Ex: ID-001">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ref. Fornecedor</label>
                        <input type="text" class="form-control" name="epi_ref_fornecedor" placeholder="Ex: RF-99">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fabricante *</label>
                        <input type="text" class="form-control" name="epi_fabricante" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Localização no Estoque</label>
                        <input type="text" class="form-control" name="epi_localizacao" placeholder="Ex: Prateleira B2">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço Unitário (Padrão) *</label>
                        <input type="text" class="form-control mask-money" name="epi_valor" placeholder="R$ 0,00" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Origem do Preço *</label>
                        <select class="form-select" name="epi_origem_preco">
                            <option value="COMPRA_DIRETA">Compra Direta</option>
                            <option value="LICITACAO">Licitação</option>
                            <option value="CONTRATO_ANUAL">Contrato Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="epi_exige_tamanho" id="cad-exige-tamanho">
                            <label class="form-check-label fw-medium" for="cad-exige-tamanho">
                                Exige especificação de Tamanho
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Vida Útil -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Controle de Vida Útil</label>
                        <select class="form-select" name="epi_vida_util_tipo" id="cad-vida-util-tipo" onchange="toggleVidaUtil('cad')">
                            <option value="CONTROLADO">Controlado</option>
                            <option value="ILIMITADO">Ilimitado / Não controlado</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="cad-grupo-vida-util-valor">
                        <label class="form-label">Período de Vida Útil *</label>
                        <input type="number" class="form-control" name="epi_vida_util" id="cad-input-vida-util" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3" id="cad-grupo-vida-util-unidade">
                        <label class="form-label">Unidade de Período *</label>
                        <select class="form-select" name="epi_vida_util_unidade" id="cad-input-vida-util-unidade" required>
                            <option value="DIAS">Dias</option>
                            <option value="MESES">Meses</option>
                            <option value="ANOS">Anos</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Validade Recomendada de Uso (Dias) *</label>
                        <input type="number" class="form-control" name="epi_validade_uso_dias" value="365" min="0" required>
                        <small class="text-muted">Prazo recomendado de descarte após entrega ( NR-6 ).</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Observações SST</label>
                        <textarea class="form-control" name="epi_vida_util_obs" rows="2" placeholder="Observações de uso, higienização ou alertas..."></textarea>
                    </div>
                </div>

                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Item</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php" novalidate>
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" id="edit-epi-id" name="epi_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-pencil me-2"></i>Editar EPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nome do Item *</label>
                        <input type="text" class="form-control" id="edit-epi-nome" name="epi_nome" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Classificação / Tipo de Item *</label>
                        <select class="form-select" name="epi_tipo_item" id="edit-tipo-item" onchange="toggleCaFields('edit')">
                            <option value="EPI_COM_CA">EPI com C.A.</option>
                            <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                            <option value="UNIFORME">Uniforme</option>
                            <option value="OUTRO">Outro</option>
                        </select>
                    </div>
                </div>

                <!-- Campos de CA -->
                <div class="row" id="edit-grupo-ca">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Certificado de Aprovação (C.A.) *</label>
                        <input type="text" class="form-control" name="epi_ca" id="edit-input-ca" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Vencimento do C.A. *</label>
                        <input type="date" class="form-control" name="epi_vencimento_ca" id="edit-input-venc-ca" required>
                    </div>
                </div>

                <!-- Campos de Rastreabilidade -->
                <div class="row d-none" id="edit-grupo-rastreabilidade">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" name="epi_modelo" id="edit-epi-modelo">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Identificação Interna</label>
                        <input type="text" class="form-control" name="epi_identificacao" id="edit-epi-identificacao">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Ref. Fornecedor</label>
                        <input type="text" class="form-control" name="epi_ref_fornecedor" id="edit-epi-ref-fornecedor">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fabricante *</label>
                        <input type="text" class="form-control" id="edit-epi-fabricante" name="epi_fabricante" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Localização no Estoque</label>
                        <input type="text" class="form-control" id="edit-epi-localizacao" name="epi_localizacao">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Preço Unitário (Padrão) *</label>
                        <input type="text" class="form-control mask-money" id="edit-epi-valor" name="epi_valor" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Origem do Preço *</label>
                        <select class="form-select" id="edit-epi-origem" name="epi_origem_preco">
                            <option value="COMPRA_DIRETA">Compra Direta</option>
                            <option value="LICITACAO">Licitação</option>
                            <option value="CONTRATO_ANUAL">Contrato Anual</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="epi_exige_tamanho" id="edit-exige-tamanho">
                            <label class="form-check-label fw-medium" for="edit-exige-tamanho">
                                Exige especificação de Tamanho
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Vida Útil -->
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tipo de Controle de Vida Útil</label>
                        <select class="form-select" name="epi_vida_util_tipo" id="edit-vida-util-tipo" onchange="toggleVidaUtil('edit')">
                            <option value="CONTROLADO">Controlado</option>
                            <option value="ILIMITADO">Ilimitado / Não controlado</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3" id="edit-grupo-vida-util-valor">
                        <label class="form-label">Período de Vida Útil *</label>
                        <input type="number" class="form-control" name="epi_vida_util" id="edit-input-vida-util" min="1" required>
                    </div>
                    <div class="col-md-4 mb-3" id="edit-grupo-vida-util-unidade">
                        <label class="form-label">Unidade de Período *</label>
                        <select class="form-select" name="epi_vida_util_unidade" id="edit-input-vida-util-unidade" required>
                            <option value="DIAS">Dias</option>
                            <option value="MESES">Meses</option>
                            <option value="ANOS">Anos</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Validade Recomendada de Uso (Dias) *</label>
                        <input type="number" class="form-control" id="edit-epi-validade-uso" name="epi_validade_uso_dias" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" id="edit-epi-status" name="epi_status">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="VENCIDO">Vencido</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Nota Fiscal / Histórico</label>
                        <input type="text" class="form-control" name="hist_nota_fiscal" placeholder="Ref. Nota Fiscal (opcional)">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Fornecedor Vigente</label>
                        <input type="text" class="form-control" name="hist_fornecedor" placeholder="Fornecedor do reajuste (opcional)">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Observações SST</label>
                        <textarea class="form-control" id="edit-epi-obs" name="epi_vida_util_obs" rows="2"></textarea>
                    </div>
                </div>

                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Reajuste/Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="epis.php">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" id="excluir-epi-id" name="epi_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Confirmar Inativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja descontinuar o item <strong id="excluir-epi-nome"></strong>?</p>
                <p class="text-muted" style="font-size: 13px;">O item passará para a situação de INATIVO. Registros históricos e entregas em posse dos colaboradores continuarão inalterados.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Ficha e Rastreabilidade do EPI -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-eye me-2"></i>Especificações Técnicas e Rastreabilidade</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-2">
                    <h5 class="fw-bold mb-3" id="det-nome" style="color: var(--color-primary);">Nome do Item</h5>
                    
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Tipo de Item:</span>
                        <span class="fw-semibold" id="det-tipo"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Certificado de Aprovação (C.A.):</span>
                        <span class="fw-semibold" id="det-ca"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Validade do C.A.:</span>
                        <span class="fw-semibold" id="det-venc-ca"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Fabricante:</span>
                        <span class="fw-semibold" id="det-fabricante"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Exige especificação de tamanho:</span>
                        <span class="fw-semibold" id="det-exige-tam"></span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Localização Física no Estoque:</span>
                        <span class="fw-semibold text-muted" id="det-localizacao"></span>
                    </div>

                    <!-- Bloco Rastreabilidade Alternativo -->
                    <div class="p-3 bg-light rounded border my-3 d-none" id="det-bloco-rastreabilidade">
                        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-tag-fill me-1 text-primary"></i>Dados de Rastreabilidade (NR-6 / Uniformes)</h6>
                        <div class="row g-2">
                            <div class="col-6" style="font-size: 13px;"><span class="text-muted">Modelo:</span> <strong id="det-rastre-modelo">---</strong></div>
                            <div class="col-6" style="font-size: 13px;"><span class="text-muted">Identificação/Lote:</span> <strong id="det-rastre-ident">---</strong></div>
                            <div class="col-12" style="font-size: 13px;"><span class="text-muted">Referência Fornecedor:</span> <strong id="det-rastre-forn">---</strong></div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Controle de Vida Útil:</span>
                        <span class="fw-semibold" id="det-vida-util"></span>
                    </div>
                    
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <span class="text-muted">Validade Recomendada de Uso:</span>
                        <span class="fw-semibold" id="det-validade-uso"></span>
                    </div>

                    <?php if ($podeVerCustos): ?>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Preço Homologado Vigente:</span>
                            <span class="fw-bold text-success" id="det-preco"></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Origem da Cotação:</span>
                            <span class="fw-semibold" id="det-origem-preco"></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="py-2">
                        <span class="text-muted d-block mb-1">Requisitos / Recomendações SST:</span>
                        <p class="text-muted border p-3 rounded" style="font-size: 13px;" id="det-obs">Nenhuma recomendação cadastrada.</p>
                    </div>

                    <!-- Nota sobre reajustes históricos -->
                    <div class="alert alert-info d-flex align-items-center mt-2" role="alert" style="font-size: 12px;">
                        <i class="bi-info-circle-fill me-2"></i>
                        <div>O histórico de reajustes e cotações de preços pode ser auditado diretamente na aba de <strong>Auditoria</strong> do menu administrativo.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
<!-- Modal Histórico de Preços -->
<div class="modal fade" id="modalHistoricoPrecos" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-clock-history me-2"></i>Histórico e Cotação de Preços dos EPIs
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Consulte a cotação atual, origem dos preços e valores homologados para os equipamentos de proteção.</p>
                <div class="table-responsive border rounded" style="max-height: 350px; overflow-y: auto;">
                    <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Equipamento (EPI)</th>
                                <th>Fabricante / C.A.</th>
                                <th>Origem da Cotação</th>
                                <th>Preço Homologado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($epis)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">Nenhum EPI cadastrado.</td></tr>
                            <?php else: ?>
                                <?php foreach ($epis as $e): ?>
                                    <?php
                                    $val = (float)($e['epi_valor'] ?? 0);
                                    $valFmt = 'R$ ' . number_format($val, 2, ',', '.');
                                    $origem = $e['epi_origem_preco'] ?? 'COMPRA_DIRETA';
                                    ?>
                                    <tr>
                                        <td class="fw-semibold"><?= htmlspecialchars($e['epi_nome']) ?></td>
                                        <td>
                                            <div><?= htmlspecialchars($e['epi_fabricante'] ?? '---') ?></div>
                                            <small class="text-muted">C.A. <?= htmlspecialchars($e['epi_ca'] ?? 'N/A') ?></small>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($origem) ?></span></td>
                                        <td class="fw-bold text-success"><?= $valFmt ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>



<!-- ================= JAVASCRIPT ================= -->
<script>
// Base de Catálogo para Busca e Autocomplete
let listaEpisCadastrados = <?= json_encode(array_values(array_map(function($e) {
    return [
        'epi_id' => (int)$e['epi_id'],
        'epi_nome' => $e['epi_nome'] ?? '',
        'epi_ca' => $e['epi_ca'] ?? '',
        'epi_fabricante' => $e['epi_fabricante'] ?? '',
        'epi_tipo_item' => $e['epi_tipo_item'] ?? 'EPI_COM_CA',
        'epi_vencimento_ca' => $e['epi_vencimento_ca'] ?? '',
        'epi_status' => $e['epi_status'] ?? 'ATIVO'
    ];
}, $epis)), JSON_UNESCAPED_UNICODE) ?>;

let sugestoesEpisAtuais = [];
let indexFocadoEpiAutocomplete = -1;

function normalizarTexto(str) {
    return String(str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function destacarTrecho(texto, query) {
    if (!texto) return '';
    if (!query) return htmlEscape(texto);
    const textoNorm = normalizarTexto(texto);
    const queryNorm = normalizarTexto(query);
    const idx = textoNorm.indexOf(queryNorm);
    if (idx === -1) return htmlEscape(texto);

    const antes = texto.substring(0, idx);
    const meio = texto.substring(idx, idx + query.length);
    const depois = texto.substring(idx + query.length);
    return `${htmlEscape(antes)}<strong class="text-primary">${htmlEscape(meio)}</strong>${htmlEscape(depois)}`;
}

function aoDigitarBuscaEpi(termo) {
    indexFocadoEpiAutocomplete = -1;
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (termo && termo.length > 0) {
        btnLimpar.classList.remove('d-none');
    } else {
        btnLimpar.classList.add('d-none');
    }
    aplicarFiltrosEpi();
}

function aoFocarBuscaEpi() {
    const busca = document.getElementById('busca-input');
    if (busca && busca.value.trim().length >= 1) {
        aplicarFiltrosEpi();
    }
}

function limparBuscaEpi() {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = '';
        busca.focus();
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.classList.add('d-none');
    fecharAutocompleteEpi();
    aplicarFiltrosEpi();
}

function fecharAutocompleteEpi() {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (autoList) {
        autoList.style.display = 'none';
        autoList.classList.add('d-none');
        autoList.innerHTML = '';
    }
    indexFocadoEpiAutocomplete = -1;
    sugestoesEpisAtuais = [];
}

function aplicarFiltrosEpi() {
    const busca = document.getElementById('busca-input');
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroCaStatus = document.getElementById('filtro-ca-status');
    const rows = document.querySelectorAll('.epi-row');

    const rawQuery = busca ? busca.value.trim() : '';
    const queryNorm = normalizarTexto(rawQuery);
    const queryCleanCa = rawQuery.replace(/\D/g, '');
    const tipo = filtroTipo ? filtroTipo.value : '';
    const caStatus = filtroCaStatus ? filtroCaStatus.value : '';

    let visiveis = 0;

    rows.forEach(row => {
        const rNome = normalizarTexto(row.getAttribute('data-nome'));
        const rFabricante = normalizarTexto(row.getAttribute('data-fabricante'));
        const rCa = row.getAttribute('data-ca') || '';
        const rCaLimpo = rCa.replace(/\D/g, '');
        const rTipo = row.getAttribute('data-tipo') || '';
        const rCaStatus = row.getAttribute('data-castatus') || '';

        // "COMEÇA COM" (startsWith) estritamente no início do nome do EPI, do fabricante ou do C.A.
        let bateBusca = false;
        if (!queryNorm) {
            bateBusca = true;
        } else {
            const comecaNome = rNome.startsWith(queryNorm);
            const comecaFab = rFabricante.startsWith(queryNorm);
            const comecaCa = (queryCleanCa.length > 0 && rCaLimpo.startsWith(queryCleanCa)) || rCa.startsWith(rawQuery);

            bateBusca = comecaNome || comecaFab || comecaCa;
        }

        const bateTipo = (tipo === '' || rTipo === tipo);
        const bateCaStatus = (caStatus === '' || rCaStatus === caStatus);

        if (bateBusca && bateTipo && bateCaStatus) {
            row.style.display = '';
            visiveis++;
        } else {
            row.style.display = 'none';
        }
    });

    renderizarAutocompleteEpi(rawQuery, queryNorm, queryCleanCa, tipo, caStatus);
}

function renderizarAutocompleteEpi(rawQuery, queryNorm, queryCleanCa, tipo, caStatus) {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (!autoList) return;

    if (!queryNorm || queryNorm.length < 1) {
        fecharAutocompleteEpi();
        return;
    }

    // Filtra catálogo por nome, fabricante ou C.A.
    sugestoesEpisAtuais = listaEpisCadastrados.filter(e => {
        const eTipo = e.epi_tipo_item || '';
        if (tipo !== '' && eTipo !== tipo) return false;

        const nomeNorm = normalizarTexto(e.epi_nome);
        const fabNorm = normalizarTexto(e.epi_fabricante);
        const caLimpo = String(e.epi_ca || '').replace(/\D/g, '');

        const matchNome = nomeNorm.includes(queryNorm);
        const matchFab = fabNorm.includes(queryNorm);
        const matchCa = (queryCleanCa.length > 0 && caLimpo.includes(queryCleanCa)) || String(e.epi_ca || '').includes(rawQuery);

        return matchNome || matchFab || matchCa;
    });

    indexFocadoEpiAutocomplete = -1;

    if (sugestoesEpisAtuais.length === 0) {
        autoList.innerHTML = `
            <div class="p-3 text-center text-muted" style="font-size: 13px;">
                <i class="bi bi-search me-1 text-secondary"></i> Nenhum equipamento encontrado com "<strong>${htmlEscape(rawQuery)}</strong>"
            </div>`;
        autoList.style.display = 'block';
        autoList.classList.remove('d-none');
        return;
    }

    const itensExibir = sugestoesEpisAtuais.slice(0, 8);
    let html = `
        <div class="px-3 py-2 bg-light border-bottom text-muted d-flex justify-content-between align-items-center" style="font-size: 11px;">
            <span><i class="bi bi-box-seam me-1 text-primary"></i> ${sugestoesEpisAtuais.length} equipamento(s) encontrado(s)</span>
            <span><kbd>▲</kbd> <kbd>▼</kbd> para navegar • <kbd>Enter</kbd> para escolher</span>
        </div>
        <div class="list-group list-group-flush">
    `;

    itensExibir.forEach((e, idx) => {
        const nomeDestacado = destacarTrecho(e.epi_nome, rawQuery);
        const fab = htmlEscape(e.epi_fabricante || 'Fabricante não informado');
        const caDesc = e.epi_ca ? `C.A. ${e.epi_ca}` : 'Sem C.A.';

        const iniciais = (e.epi_nome || 'EP')
            .split(' ')
            .filter(n => n.length > 0)
            .slice(0, 2)
            .map(n => n[0].toUpperCase())
            .join('');

        let statusCaHtml = '';
        if (e.epi_vencimento_ca) {
            const dataVenc = new Date(e.epi_vencimento_ca);
            const hoje = new Date();
            const dataFmt = e.epi_vencimento_ca.split('-').reverse().join('/');
            if (dataVenc < hoje) {
                statusCaHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-1" style="font-size: 10px;"><i class="bi bi-exclamation-triangle me-1"></i>C.A. Vencido (${dataFmt})</span>`;
            } else {
                statusCaHtml = `<span class="text-muted ms-1" style="font-size: 10px;"><i class="bi bi-calendar-check me-1"></i>Val: ${dataFmt}</span>`;
            }
        }

        let tipoLabel = 'EPI';
        if (e.epi_tipo_item === 'UNIFORME') tipoLabel = 'Uniforme';
        else if (e.epi_tipo_item === 'ITEM_SEGURANCA_SEM_CA') tipoLabel = 'Item sem C.A.';

        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action p-2 border-0 border-bottom d-flex align-items-center gap-2 autocomplete-item auto-epi-item" 
               id="auto-epi-${idx}"
               onclick="selecionarEpiAutocomplete('${e.epi_nome.replace(/'/g, "\\'")}', ${e.epi_id})"
               onmouseover="destacarItemEpi(${idx})">
                <div class="rounded-circle bg-primary-subtle text-primary fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 36px; height: 36px; font-size: 13px; letter-spacing: 0.5px;">
                    ${iniciais}
                </div>
                <div class="flex-grow-1 min-w-0" style="line-height: 1.25;">
                    <div class="d-flex align-items-center justify-content-between">
                        <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;">${nomeDestacado}</span>
                        <span class="badge bg-light text-secondary border ms-1 flex-shrink-0" style="font-size: 10px;">ID #${e.epi_id}</span>
                    </div>
                    <div class="text-muted d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                        <span><i class="bi bi-building me-1"></i>${fab}</span> • 
                        <span><i class="bi bi-shield me-1"></i>${caDesc} (${tipoLabel})</span>
                        ${statusCaHtml ? ' • ' + statusCaHtml : ''}
                    </div>
                </div>
            </a>
        `;
    });

    html += `</div>`;
    autoList.innerHTML = html;
    autoList.style.display = 'block';
    autoList.classList.remove('d-none');
}

function destacarItemEpi(idx) {
    indexFocadoEpiAutocomplete = idx;
    const items = document.querySelectorAll('.auto-epi-item');
    items.forEach((el, i) => {
        if (i === idx) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function aoTeclarBuscaEpi(e) {
    const autoList = document.getElementById('autocomplete-lista-epis');
    if (!autoList || autoList.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (sugestoesEpisAtuais.length > 0) {
            indexFocadoEpiAutocomplete = (indexFocadoEpiAutocomplete + 1) % Math.min(sugestoesEpisAtuais.length, 8);
            destacarItemEpi(indexFocadoEpiAutocomplete);
            const el = document.getElementById(`auto-epi-${indexFocadoEpiAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (sugestoesEpisAtuais.length > 0) {
            indexFocadoEpiAutocomplete = (indexFocadoEpiAutocomplete - 1 + Math.min(sugestoesEpisAtuais.length, 8)) % Math.min(sugestoesEpisAtuais.length, 8);
            destacarItemEpi(indexFocadoEpiAutocomplete);
            const el = document.getElementById(`auto-epi-${indexFocadoEpiAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indexFocadoEpiAutocomplete >= 0 && sugestoesEpisAtuais[indexFocadoEpiAutocomplete]) {
            selecionarEpiAutocomplete(sugestoesEpisAtuais[indexFocadoEpiAutocomplete].epi_nome, sugestoesEpisAtuais[indexFocadoEpiAutocomplete].epi_id);
        } else if (sugestoesEpisAtuais.length === 1) {
            selecionarEpiAutocomplete(sugestoesEpisAtuais[0].epi_nome, sugestoesEpisAtuais[0].epi_id);
        }
    } else if (e.key === 'Escape') {
        fecharAutocompleteEpi();
    }
}

function selecionarEpiAutocomplete(nome, epiId) {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = nome;
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.classList.remove('d-none');
    fecharAutocompleteEpi();
    aplicarFiltrosEpi();

    if (epiId) {
        const row = document.querySelector(`.epi-row[data-id="${epiId}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('table-active');
            setTimeout(() => row.classList.remove('table-active'), 2500);
        }
    }
}

// Fecha autocomplete se clicar fora
document.addEventListener('click', function(e) {
    const busca = document.getElementById('busca-input');
    const wrapper = busca ? busca.closest('.position-relative') : null;
    if (wrapper && !wrapper.contains(e.target)) {
        fecharAutocompleteEpi();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    restaurarEstadoFiltros();
    
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        form.addEventListener('submit', function() {
            salvarEstadoFiltros();
        });
    });
});

function salvarEstadoFiltros() {
    const estado = {
        busca: document.getElementById('busca-input')?.value || '',
        filtroTipo: document.getElementById('filtro-tipo')?.value || '',
        filtroCaStatus: document.getElementById('filtro-ca-status')?.value || '',
        scrollY: window.scrollY
    };
    sessionStorage.setItem('epis_estado_filtros', JSON.stringify(estado));
}

function restaurarEstadoFiltros() {
    const estadoSalvo = sessionStorage.getItem('epis_estado_filtros');
    if (!estadoSalvo) return;
    
    sessionStorage.removeItem('epis_estado_filtros');
    
    try {
        const estado = JSON.parse(estadoSalvo);
        
        if (estado.busca) document.getElementById('busca-input').value = estado.busca;
        if (estado.filtroTipo) document.getElementById('filtro-tipo').value = estado.filtroTipo;
        if (estado.filtroCaStatus) document.getElementById('filtro-ca-status').value = estado.filtroCaStatus;
        
        if (estado.busca || estado.filtroTipo || estado.filtroCaStatus) {
            aplicarFiltrosEpi();
        }
        
        if (estado.scrollY > 0) {
            setTimeout(() => window.scrollTo(0, estado.scrollY), 100);
        }
    } catch (e) {}
}

/**
 * Controla os inputs obrigatórios de C.A. e dados de rastreabilidade dependendo do Tipo de Item selecionado
 */
function toggleCaFields(prefix) {
    const tipo = document.getElementById(`${prefix}-tipo-item`).value;
    const grupoCa = document.getElementById(`${prefix}-grupo-ca`);
    const grupoRastre = document.getElementById(`${prefix}-grupo-rastreabilidade`);
    
    const inputCa = document.getElementById(`${prefix}-input-ca`);
    const inputVenc = document.getElementById(`${prefix}-input-venc-ca`);
    
    if (tipo === 'EPI_COM_CA') {
        grupoCa.classList.remove('d-none');
        grupoRastre.classList.add('d-none');
        
        inputCa.required = true;
        inputVenc.required = true;
    } else {
        grupoCa.classList.add('d-none');
        grupoRastre.classList.remove('d-none');
        
        inputCa.required = false;
        inputVenc.required = false;
        inputCa.value = '';
        inputVenc.value = '';
    }
}

/**
 * Controla os inputs de Vida Útil
 */
function toggleVidaUtil(prefix) {
    const tipo = document.getElementById(`${prefix}-vida-util-tipo`).value;
    const grupoValor = document.getElementById(`${prefix}-grupo-vida-util-valor`);
    const grupoUnidade = document.getElementById(`${prefix}-grupo-vida-util-unidade`);
    
    const inputVal = document.getElementById(`${prefix}-input-vida-util`);
    const inputUni = document.getElementById(`${prefix}-input-vida-util-unidade`);
    
    if (tipo === 'CONTROLADO') {
        grupoValor.style.display = '';
        grupoUnidade.style.display = '';
        
        inputVal.required = true;
        inputUni.required = true;
    } else {
        grupoValor.style.display = 'none';
        grupoUnidade.style.display = 'none';
        
        inputVal.required = false;
        inputUni.required = false;
        inputVal.value = '';
    }
}

/**
 * Preenche o modal de exclusão do EPI
 */
function confirmarExclusao(id, nome) {
    document.getElementById('excluir-epi-id').value = id;
    document.getElementById('excluir-epi-nome').innerText = nome;
    
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

/**
 * Preenche o modal de edição
 */
function prepararEdicao(epi) {
    document.getElementById('edit-epi-id').value = epi.epi_id;
    document.getElementById('edit-epi-nome').value = epi.epi_nome;
    document.getElementById('edit-tipo-item').value = epi.epi_tipo_item;
    
    document.getElementById('edit-input-ca').value = epi.epi_ca || '';
    document.getElementById('edit-input-venc-ca').value = epi.epi_vencimento_ca || '';
    
    document.getElementById('edit-epi-modelo').value = epi.epi_modelo || '';
    document.getElementById('edit-epi-identificacao').value = epi.epi_identificacao || '';
    document.getElementById('edit-epi-ref-fornecedor').value = epi.epi_ref_fornecedor || '';
    
    document.getElementById('edit-epi-fabricante').value = epi.epi_fabricante;
    document.getElementById('edit-epi-localizacao').value = epi.epi_localizacao || '';
    
    // Formata preço para a máscara
    const valorFloat = parseFloat(epi.epi_valor);
    document.getElementById('edit-epi-valor').value = valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
    document.getElementById('edit-epi-origem').value = epi.epi_origem_preco;
    document.getElementById('edit-exige-tamanho').checked = (parseInt(epi.epi_exige_tamanho) === 1);
    
    document.getElementById('edit-vida-util-tipo').value = epi.epi_vida_util_tipo;
    document.getElementById('edit-input-vida-util').value = epi.epi_vida_util || '';
    document.getElementById('edit-input-vida-util-unidade').value = epi.epi_vida_util_unidade || 'DIAS';
    
    document.getElementById('edit-epi-validade-uso').value = epi.epi_validade_uso_dias;
    document.getElementById('edit-epi-status').value = epi.epi_status;
    document.getElementById('edit-epi-obs').value = epi.epi_vida_util_obs || '';

    // Roda os toggles iniciais
    toggleCaFields('edit');
    toggleVidaUtil('edit');

    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

/**
 * Exibe especificações e dados de rastreabilidade do EPI
 */
function verFichaEpi(epi) {
    document.getElementById('det-nome').innerText = epi.epi_nome;
    
    const tiposMap = {
        'EPI_COM_CA': 'EPI com Certificado de Aprovação',
        'ITEM_SEGURANCA_SEM_CA': 'Item de Segurança sem C.A.',
        'UNIFORME': 'Uniforme',
        'OUTRO': 'Outro tipo de item'
    };
    document.getElementById('det-tipo').innerText = tiposMap[epi.epi_tipo_item] ?? epi.epi_tipo_item;
    
    document.getElementById('det-ca').innerText = epi.epi_ca || 'Isento de C.A.';
    
    let vencCa = '---';
    if (epi.epi_tipo_item === 'EPI_COM_CA' && epi.epi_vencimento_ca) {
        const parts = epi.epi_vencimento_ca.split('-');
        vencCa = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : epi.epi_vencimento_ca;
    }
    document.getElementById('det-venc-ca').innerText = vencCa;
    document.getElementById('det-fabricante').innerText = epi.epi_fabricante;
    document.getElementById('det-exige-tam').innerText = parseInt(epi.epi_exige_tamanho) === 1 ? 'Sim, obrigatório' : 'Não exigido';
    document.getElementById('det-localizacao').innerText = epi.epi_localizacao || 'Sem especificação';

    // Rastreabilidade alternativos (NR-6 / Uniformes)
    const blocoRastre = document.getElementById('det-bloco-rastreabilidade');
    if (epi.epi_tipo_item !== 'EPI_COM_CA') {
        blocoRastre.classList.remove('d-none');
        document.getElementById('det-rastre-modelo').innerText = epi.epi_modelo || '---';
        document.getElementById('det-rastre-ident').innerText = epi.epi_identificacao || epi.epi_numero_lote || '---';
        document.getElementById('det-rastre-forn').innerText = epi.epi_ref_fornecedor || '---';
    } else {
        blocoRastre.classList.add('d-none');
    }

    // Vida útil
    let vidaUtil = 'Ilimitada / Não controlada';
    if (epi.epi_vida_util_tipo === 'CONTROLADO') {
        vidaUtil = `${epi.epi_vida_util} ${epi.epi_vida_util_unidade}`;
    }
    document.getElementById('det-vida-util').innerText = vidaUtil;
    
    document.getElementById('det-validade-uso').innerText = `${epi.epi_validade_uso_dias} dias recomendados de descarte`;
    
    // Custos (exibição protegida)
    const precoNode = document.getElementById('det-preco');
    if (precoNode) {
        const valorFloat = parseFloat(epi.epi_valor);
        precoNode.innerText = valorFloat.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        
        const origensMap = {
            'COMPRA_DIRETA': 'Compra Direta',
            'LICITACAO': 'Processo Licitatório',
            'CONTRATO_ANUAL': 'Contrato Corporativo Anual'
        };
        document.getElementById('det-origem-preco').innerText = origensMap[epi.epi_origem_preco] ?? epi.epi_origem_preco;
    }
    
    document.getElementById('det-obs').innerText = epi.epi_vida_util_obs || 'Nenhuma recomendação SST cadastrada.';

    new bootstrap.Modal(document.getElementById('modalDetalhes')).show();
}

/* ===================== PAINEL DE MONITORAMENTO DE C.A. ===================== */

const PODE_EDITAR_EPI = <?= $podeEditar ? 'true' : 'false' ?>;
let caFiltroAtivo = 'todos';
let episClassificados = [];

/**
 * Alterna entre a visão de Catálogo e o Painel de Monitoramento de C.A.
 */
function alternarVisao(visao) {
    const ehCatalogo = visao === 'catalogo';

    document.getElementById('visao-catalogo').classList.toggle('d-none', !ehCatalogo);
    document.getElementById('visao-painel-ca').classList.toggle('d-none', ehCatalogo);

    const btnCatalogo = document.getElementById('btn-visao-catalogo');
    const btnPainel = document.getElementById('btn-visao-painel');
    btnCatalogo.classList.toggle('active', ehCatalogo);
    btnPainel.classList.toggle('active', !ehCatalogo);
    btnCatalogo.classList.toggle('btn-secondary', !ehCatalogo);
    btnPainel.classList.toggle('btn-secondary', ehCatalogo);

    if (!ehCatalogo) renderizarPainelCa();
}

/**
 * Classifica cada EPI pelo status do C.A. (critério idêntico ao aplicativo)
 */
function classificarCaEpi(epi) {
    const hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    const ca = (epi.epi_ca || '').toString().trim();

    if (ca === '' || ca.toUpperCase() === 'SEM C.A.') {
        return { status: 'sem-ca', score: 4 };
    }
    if (!epi.epi_vencimento_ca) {
        return { status: 'valido', score: 3 };
    }

    const vencimento = new Date(String(epi.epi_vencimento_ca).split(' ')[0] + 'T00:00:00');
    if (isNaN(vencimento.getTime())) return { status: 'valido', score: 3 };

    if (vencimento < hoje) return { status: 'vencido', score: 1 };

    const diasRestantes = Math.ceil((vencimento - hoje) / (1000 * 60 * 60 * 24));
    return diasRestantes <= 30 ? { status: 'vencendo', score: 2 } : { status: 'valido', score: 3 };
}

function badgeCa(status, epi) {
    switch (status) {
        case 'vencido': return '<span class="badge bg-danger">CRÍTICO / VENCIDO</span>';
        case 'vencendo': {
            const vencimento = new Date(String(epi.epi_vencimento_ca).split(' ')[0] + 'T00:00:00');
            const diasRestantes = Math.ceil((vencimento - new Date()) / (1000 * 60 * 60 * 24));
            return `<span class="badge bg-warning text-dark">ATENÇÃO / VENCENDO (${diasRestantes}d)</span>`;
        }
        case 'valido': return '<span class="badge bg-success">VÁLIDO</span>';
        default: return '<span class="badge bg-info text-dark">SEM C.A.</span>';
    }
}

function montarCardsCa() {
    const lista = document.getElementById('ca-lista');

    const filtrados = episClassificados.filter(e => caFiltroAtivo === 'todos' || e.status === caFiltroAtivo);

    if (!filtrados.length) {
        lista.innerHTML = '<p class="text-muted text-center py-4 m-0">Nenhum EPI encontrado para este filtro de monitoramento.</p>';
        return;
    }

    lista.innerHTML = filtrados.map(e => {
        const vidaUtil = e.epi.epi_vida_util_tipo === 'CONTROLADO'
            ? `${e.epi.epi_vida_util} ${lowerUnidade(e.epi.epi_vida_util_unidade)}`
            : 'Ilimitada';

        let rastreabilidade;
        if (e.status === 'sem-ca') {
            const temDados = e.epi.epi_modelo || e.epi.epi_identificacao || e.epi.epi_numero_lote || e.epi.epi_ref_fornecedor;
            rastreabilidade = temDados
                ? `Lote: ${e.epi.epi_identificacao || e.epi.epi_numero_lote || '---'} | Modelo: ${e.epi.epi_modelo || '---'}`
                : '⚠ Sem dados de rastreabilidade';
        } else {
            rastreabilidade = `C.A.: <strong>${e.epi.epi_ca}</strong> | Vencimento C.A.: ${formatarDataBR(e.epi.epi_vencimento_ca)}`;
        }

        return `
            <div class="border rounded-3 p-3 d-flex flex-wrap justify-content-between align-items-center gap-2 ${e.status === 'vencido' ? 'border-danger-subtle bg-danger-subtle bg-opacity-10' : ''}">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong>${e.epi.epi_nome}</strong>
                        ${badgeCa(e.status, e.epi)}
                    </div>
                    <div style="font-size: 12px;" class="text-muted">
                        Fabricante: ${e.epi.epi_fabricante || '---'}
                        | Vida útil: ${vidaUtil}
                        | Localização: ${e.epi.epi_localizacao || '---'}
                        <br>${rastreabilidade}
                    </div>
                </div>
                <button class="btn btn-sm btn-light border" onclick='abrirEpiDoPainel(${jsonParaAtributo(e.epi)})'>
                    <i class="bi bi-eye"></i> Detalhes
                </button>
            </div>`;
    }).join('');
}

function lowerUnidade(unidade) {
    const mapa = { DIAS: 'dias', MESES: 'meses', ANOS: 'anos' };
    return mapa[unidade] || (unidade || '').toLowerCase();
}

function formatarDataBR(valor) {
    if (!valor) return '---';
    const partes = String(valor).split(' ')[0].split('-');
    return partes.length === 3 ? `${partes[2]}/${partes[1]}/${partes[0]}` : valor;
}

// Serializa com aspas simples para uso seguro dentro do atributo onclick
function jsonParaAtributo(obj) {
    return JSON.stringify(obj).replace(/'/g, '&#39;');
}

function abrirEpiDoPainel(epi) {
    if (PODE_EDITAR_EPI) {
        prepararEdicao(epi);
    } else {
        verFichaEpi(epi);
    }
}

function renderizarPainelCa() {
    // Recarrega os dados atuais da tabela (renderizados no servidor)
    if (!episClassificados.length) {
        const dadosTabela = <?= json_encode(array_values($episParaPainel)) ?>;
        episClassificados = dadosTabela
            .map(epi => ({ epi, ...classificarCaEpi(epi) }))
            .sort((a, b) => a.score - b.score || a.epi.epi_nome.localeCompare(b.epi.epi_nome, 'pt-BR', { sensitivity: 'base' }));
    }

    // Chips com contagem por categoria
    const contagem = { todos: episClassificados.length, vencido: 0, vencendo: 0, valido: 0, 'sem-ca': 0 };
    episClassificados.forEach(e => contagem[e.status]++);

    const chipsDef = [
        { id: 'todos', label: 'Todos', icone: 'list', classe: 'outline-primary' },
        { id: 'vencido', label: 'Vencidos', icone: 'exclamation-triangle-fill', classe: 'outline-danger' },
        { id: 'vencendo', label: 'Vencendo (30 dias)', icone: 'hourglass-split', classe: 'outline-warning' },
        { id: 'valido', label: 'Válidos', icone: 'check-circle-fill', classe: 'outline-success' },
        { id: 'sem-ca', label: 'Sem C.A.', icone: 'tag', classe: 'outline-info' }
    ];

    document.getElementById('ca-chips').innerHTML = chipsDef.map(c => `
        <button type="button" class="btn btn-sm btn-${c.classe} ${caFiltroAtivo === c.id ? 'active' : ''}" onclick="filtrarPainelCa('${c.id}')">
            <i class="bi bi-${c.icone} me-1"></i>${c.label} (${contagem[c.id] ?? 0})
        </button>`).join('');

    montarCardsCa();
}

function filtrarPainelCa(filtro) {
    caFiltroAtivo = filtro;
    renderizarPainelCa();
}

function executarAcaoSubmenuEpi(acao) {
    if (acao === 'novo' || acao === 'novo_epi') {
        const modalEl = document.getElementById('modalCadastrar');
        if (modalEl) (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
        return;
    }

    if (acao === 'controle_ca' || acao === 'ca') {
        if (typeof alternarVisao === 'function') alternarVisao('painel');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (acao === 'historico_precos' || acao === 'precos') {
        const modalEl = document.getElementById('modalHistoricoPrecos');
        if (modalEl) (bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl)).show();
    } else {
        if (typeof alternarVisao === 'function') alternarVisao('catalogo');
        if (typeof limparBuscaEpi === 'function') limparBuscaEpi();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    if (window.history && window.history.pushState) {
        const urlNova = window.location.protocol + "//" + window.location.host + window.location.pathname + '?acao=' + acao;
        window.history.pushState({ path: urlNova }, '', urlNova);
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const visaoParam = urlParams.get('visao');
    const acaoParam = urlParams.get('acao');

    if (acaoParam) {
        executarAcaoSubmenuEpi(acaoParam);
    } else if (visaoParam === 'painel') {
        alternarVisao('painel');
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
