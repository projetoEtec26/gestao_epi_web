<?php
declare(strict_types=1);

$page_title = 'Catálogo de EPIs';
$active_menu = 'epis';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

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
            'epi_exige_tamanho' => $exigeTamanho
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
            'epi_exige_tamanho' => $exigeTamanho
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
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Catálogo de EPIs</h3>
                <p class="text-muted">Gerencie a homologação, rastreabilidade e validade do Certificado de Aprovação (C.A.) dos EPIs.</p>
            </div>
            
            <?php if ($podeEditar): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                    <i class="bi bi-plus-lg me-1"></i> Novo Item
                </button>
            <?php endif; ?>
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

        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busca-input" class="form-control border-start-0" placeholder="Buscar por nome, fabricante ou CA...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filtro-tipo" class="form-select">
                        <option value="">Todos os Tipos</option>
                        <option value="EPI_COM_CA">EPI com C.A.</option>
                        <option value="ITEM_SEGURANCA_SEM_CA">Item de Segurança sem C.A.</option>
                        <option value="UNIFORME">Uniforme</option>
                        <option value="OUTRO">Outro</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtro-ca-status" class="form-select">
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
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initBuscaEFiltros();
});

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
 * Lógica do buscador e filtros no front-end
 */
function initBuscaEFiltros() {
    const busca = document.getElementById('busca-input');
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroCaStatus = document.getElementById('filtro-ca-status');
    const rows = document.querySelectorAll('.epi-row');
    
    function aplicarFiltros() {
        const query = busca.value.toLowerCase().trim();
        const tipo = filtroTipo.value;
        const caStatus = filtroCaStatus.value;
        
        rows.forEach(row => {
            const rNome = row.getAttribute('data-nome');
            const rFabricante = row.getAttribute('data-fabricante');
            const rCa = row.getAttribute('data-ca');
            const rTipo = row.getAttribute('data-tipo');
            const rCaStatus = row.getAttribute('data-castatus');
            
            const bateBusca = rNome.includes(query) || rFabricante.includes(query) || rCa.includes(query);
            const bateTipo = (tipo === '' || rTipo === tipo);
            const bateCaStatus = (caStatus === '' || rCaStatus === caStatus);
            
            if (bateBusca && bateTipo && bateCaStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    busca.addEventListener('input', aplicarFiltros);
    filtroTipo.addEventListener('change', aplicarFiltros);
    filtroCaStatus.addEventListener('change', aplicarFiltros);
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
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
