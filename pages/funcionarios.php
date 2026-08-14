<?php
declare(strict_types=1);

$page_title = 'Funcionários';
$active_menu = 'funcionarios';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;

// Lida com formulários de alteração de dados (PHP POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar Funcionário
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['fun_nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['fun_cpf'] ?? '');
        $esocial = trim($_POST['fun_esocial'] ?? '');
        $departamento = trim($_POST['fun_departamento'] ?? '');
        $cargo = trim($_POST['fun_cargo'] ?? '');
        $dataAdmissao = $_POST['fun_dataadmissao'] ?? '';
        $situacao = $_POST['fun_situacao'] ?? 'ATIVO';

        try {
            $response = $api->post('funcionarios', [
                'fun_nome' => $nome,
                'fun_cpf' => $cpf,
                'fun_esocial' => $esocial,
                'fun_departamento' => $departamento,
                'fun_cargo' => $cargo,
                'fun_dataadmissao' => $dataAdmissao,
                'fun_situacao' => $situacao
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Funcionário ' . htmlspecialchars($nome) . ' cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar funcionário.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 2. Editar/Atualizar Funcionário
    if ($acao === 'editar') {
        $id = (int)($_POST['fun_id'] ?? 0);
        $nome = trim($_POST['fun_nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['fun_cpf'] ?? '');
        $esocial = trim($_POST['fun_esocial'] ?? '');
        $departamento = trim($_POST['fun_departamento'] ?? '');
        $cargo = trim($_POST['fun_cargo'] ?? '');
        $dataAdmissao = $_POST['fun_dataadmissao'] ?? '';
        $situacao = $_POST['fun_situacao'] ?? 'ATIVO';

        try {
            $response = $api->put("funcionarios/{$id}", [
                'fun_nome' => $nome,
                'fun_cpf' => $cpf,
                'fun_esocial' => $esocial,
                'fun_departamento' => $departamento,
                'fun_cargo' => $cargo,
                'fun_dataadmissao' => $dataAdmissao,
                'fun_situacao' => $situacao
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Dados do funcionário atualizados com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao atualizar dados.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 3. Excluir/Inativar Funcionário
    if ($acao === 'excluir') {
        $id = (int)($_POST['fun_id'] ?? 0);

        try {
            $response = $api->delete("funcionarios/{$id}");

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Funcionário inativado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao inativar funcionário.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 4. Cadastrar PIN de Assinatura
    if ($acao === 'cadastrar_pin') {
        $funId = (int)($_POST['fun_id'] ?? 0);
        $pin = trim($_POST['pin'] ?? '');

        try {
            $response = $api->post('assinaturas', [
                'fun_id' => $funId,
                'pin' => $pin
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'PIN de Assinatura Eletrônica cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar PIN.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 5. Redefinir PIN de Assinatura
    if ($acao === 'redefinir_pin') {
        $funId = (int)($_POST['fun_id'] ?? 0);
        $pin = trim($_POST['pin'] ?? '');

        try {
            $response = $api->post('assinaturas/redefinir', [
                'fun_id' => $funId,
                'pin' => $pin
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'PIN de Assinatura Eletrônica redefinido com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao redefinir PIN.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 6. Bloquear Assinatura
    if ($acao === 'bloquear_pin') {
        $assId = (int)($_POST['ass_id'] ?? 0);
        $motivo = trim($_POST['motivo_bloqueio'] ?? 'Bloqueio administrativo');

        try {
            $response = $api->post("assinaturas/bloquear/{$assId}", [
                'motivo_bloqueio' => $motivo
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Assinatura eletrônica bloqueada!';
            } else {
                $erro = $response['message'] ?? 'Falha ao bloquear.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 7. Desbloquear Assinatura
    if ($acao === 'desbloquear_pin') {
        $assId = (int)($_POST['ass_id'] ?? 0);

        try {
            $response = $api->post("assinaturas/desbloquear/{$assId}", []);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Assinatura eletrônica desbloqueada com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao desbloquear.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }
}

// Carrega listagem de funcionários
$funcionarios = [];
try {
    $listaRes = $api->get('funcionarios');
    if (isset($listaRes['success']) && $listaRes['success']) {
        $funcionarios = $listaRes['data'];
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de funcionários: ' . $e->getMessage();
}

$podeEditar = in_array($userProfile, ['ADMINISTRADOR', 'RH_ADMINISTRATIVO'], true);
$podeExcluir = ($userProfile === 'ADMINISTRADOR');
$podeGerenciarPin = in_array($userProfile, ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Funcionários</h3>
                <p class="text-muted">Gerencie o cadastro, PIN de segurança e histórico de posse de EPIs dos colaboradores.</p>
            </div>
            
            <div class="d-flex gap-2">
                <?php if ($podeEditar): ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                        <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                        <i class="bi bi-person-plus me-1"></i> Novo Funcionário
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

        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busca-input" class="form-control border-start-0" placeholder="Buscar por nome, CPF ou cargo...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filtro-setor" class="form-select">
                        <option value="">Todos os Setores</option>
                        <?php
                        $setores = array_unique(array_column($funcionarios, 'fun_departamento'));
                        sort($setores);
                        foreach ($setores as $setor) {
                            if (!empty($setor)) {
                                echo '<option value="' . htmlspecialchars($setor) . '">' . htmlspecialchars($setor) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos os Status</option>
                        <option value="ATIVO">Ativo</option>
                        <option value="INATIVO">Inativo</option>
                        <option value="AFASTADO">Afastado</option>
                        <option value="DEMITIDO">Demitido</option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-funcionarios">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Cargo / Setor</th>
                            <th>Admissão</th>
                            <th>Status PIN</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($funcionarios)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Nenhum funcionário cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($funcionarios as $func): ?>
                                <?php
                                $cpf = $func['fun_cpf'];
                                if (strlen($cpf) === 11) {
                                    $cpf = substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
                                }
                                $statusPin = $func['assinatura_status'] ?? 'PENDENTE';
                                $statusPinClass = strtolower(str_replace(' ', '-', $statusPin));
                                $situacaoClass = strtolower($func['fun_situacao']);
                                $dataAdmissao = $func['fun_dataadmissao'] ?? '';
                                $dataAdmissaoFormatada = !empty($dataAdmissao) ? date('d/m/Y', strtotime($dataAdmissao)) : '---';
                                ?>
                                <tr class="func-row" 
                                    data-nome="<?= htmlspecialchars(strtolower($func['fun_nome'])) ?>"
                                    data-cpf="<?= htmlspecialchars($func['fun_cpf']) ?>"
                                    data-cargo="<?= htmlspecialchars(strtolower($func['fun_cargo'])) ?>"
                                    data-setor="<?= htmlspecialchars($func['fun_departamento']) ?>"
                                    data-status="<?= htmlspecialchars($func['fun_situacao']) ?>">
                                    
                                    <td class="fw-semibold"><?= htmlspecialchars($func['fun_nome']) ?></td>
                                    <td class="text-muted"><?= htmlspecialchars($cpf) ?></td>
                                    <td>
                                        <div class="fw-medium"><?= htmlspecialchars($func['fun_cargo']) ?></div>
                                        <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($func['fun_departamento']) ?></div>
                                    </td>
                                    <td><?= $dataAdmissaoFormatada ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusPinClass ?>"><?= htmlspecialchars($statusPin) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $situacaoClass ?>"><?= htmlspecialchars($func['fun_situacao']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button class="btn btn-sm btn-light border py-1 px-2" onclick="verDetalhes(<?= $func['fun_id'] ?>)" title="Ver Ficha e Histórico">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            
                                            <?php if ($podeEditar): ?>
                                                <button class="btn btn-sm btn-light border text-primary py-1 px-2" onclick="prepararEdicao(<?= htmlspecialchars(json_encode($func)) ?>)" title="Editar dados">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($podeExcluir && $func['fun_situacao'] === 'ATIVO'): ?>
                                                <button class="btn btn-sm btn-light border text-danger py-1 px-2" onclick="confirmarExclusao(<?= $func['fun_id'] ?>, '<?= htmlspecialchars($func['fun_nome']) ?>')" title="Inativar Colaborador">
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
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php" novalidate>
            <input type="hidden" name="acao" value="cadastrar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-person-plus me-2"></i>Novo Funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" name="fun_nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CPF *</label>
                    <input type="text" class="form-control mask-cpf" name="fun_cpf" placeholder="000.000.000-00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código eSocial *</label>
                    <input type="text" class="form-control" name="fun_esocial" placeholder="Ex: ESO123456" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Departamento *</label>
                        <input type="text" class="form-control" name="fun_departamento" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Cargo *</label>
                        <input type="text" class="form-control" name="fun_cargo" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Admissão *</label>
                        <input type="date" class="form-control" name="fun_dataadmissao" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" name="fun_situacao">
                            <option value="ATIVO">Ativo</option>
                            <option value="AFASTADO">Afastado</option>
                        </select>
                    </div>
                </div>
                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Funcionário</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php" novalidate>
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" id="edit-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-pencil me-2"></i>Editar Funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" id="edit-fun-nome" name="fun_nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CPF *</label>
                    <input type="text" class="form-control mask-cpf" id="edit-fun-cpf" name="fun_cpf" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código eSocial *</label>
                    <input type="text" class="form-control" id="edit-fun-esocial" name="fun_esocial" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Departamento *</label>
                        <input type="text" class="form-control" id="edit-fun-dept" name="fun_departamento" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Cargo *</label>
                        <input type="text" class="form-control" id="edit-fun-cargo" name="fun_cargo" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Admissão *</label>
                        <input type="date" class="form-control" id="edit-fun-admissao" name="fun_dataadmissao" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" id="edit-fun-situacao" name="fun_situacao">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="AFASTADO">Afastado</option>
                            <option value="DEMITIDO">Demitido</option>
                        </select>
                    </div>
                </div>
                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" id="excluir-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Confirmar Inativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja inativar o funcionário <strong id="excluir-fun-nome"></strong>?</p>
                <p class="text-muted" style="font-size: 13px;">Esta ação fará a exclusão lógica do colaborador. O histórico de entregas de EPIs continuará registrado para auditoria.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Importar (Mock com Aviso) -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-earmark-arrow-up me-2"></i>Importar Funcionários</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning d-flex align-items-start" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2 mt-1"></i>
                    <div>
                        <strong>Pendência de Backend:</strong> O endpoint para recebimento de arquivos de importação em lote (`/funcionarios/importar`) não está implementado na API.
                        Esta funcionalidade visual foi mantida e será considerada operacional assim que o backend disponibilizar suporte.
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Selecione o arquivo (CSV ou Excel) *</label>
                    <input type="file" class="form-control" disabled>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" disabled>Processar Arquivo</button>
            </div>
        </div>
    </div>
</div>

<!-- 5. Modal de Ficha Detalhada (Histórico e PIN) -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-earmark-person me-2"></i>Ficha Individual do Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Ficha Cadastral e QR Code -->
                    <div class="col-md-4 border-end">
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px; font-weight: 600;">
                                <span id="det-iniciais"></span>
                            </div>
                            <h5 class="fw-bold m-0" id="det-nome">Nome</h5>
                            <span class="text-muted" id="det-cargo">Cargo</span>
                        </div>
                        
                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">CPF:</span>
                                <span class="fw-semibold" id="det-cpf"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">eSocial:</span>
                                <span class="fw-semibold" id="det-esocial"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Departamento:</span>
                                <span class="fw-semibold" id="det-setor"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Admissão:</span>
                                <span class="fw-semibold" id="det-admissao"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Situação Funcional:</span>
                                <span class="status-badge" id="det-situacao"></span>
                            </div>
                        </div>

                        <!-- Gerenciamento de PIN -->
                        <div class="card p-3 border-slate">
                            <h6 class="fw-bold mb-3"><i class="bi bi-key-fill me-1 text-primary"></i>PIN de Assinatura Eletrônica</h6>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Status do PIN:</span>
                                <span class="status-badge" id="det-pin-status"></span>
                            </div>
                            
                            <?php if ($podeGerenciarPin): ?>
                                <div class="d-grid gap-2" id="area-acoes-pin">
                                    <!-- Dinâmico via JS -->
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Abas de Histórico -->
                    <div class="col-md-8">
                        <ul class="nav nav-tabs" id="detAbas" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="entregas-tab" data-bs-toggle="tab" data-bs-target="#tab-entregas" type="button" role="tab"><i class="bi bi-journal-text me-1"></i>EPIs em Posse / Entregues</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="devolucoes-tab" data-bs-toggle="tab" data-bs-target="#tab-devolucoes" type="button" role="tab"><i class="bi bi-arrow-counterclockwise me-1"></i>Devoluções</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content pt-3" id="detAbasConteudo">
                            <!-- Aba Entregas -->
                            <div class="tab-pane fade show active" id="tab-entregas" role="tabpanel">
                                <div class="table-responsive" style="max-height: 380px;">
                                    <table class="table table-hover border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data</th>
                                                <th>EPI</th>
                                                <th>C.A.</th>
                                                <th>Qtd</th>
                                                <th>Tamanho</th>
                                                <th>Motivo</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-det-entregas">
                                            <!-- Dinâmico via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Aba Devoluções -->
                            <div class="tab-pane fade" id="tab-devolucoes" role="tabpanel">
                                <div class="table-responsive" style="max-height: 380px;">
                                    <table class="table table-hover border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data Devolução</th>
                                                <th>EPI</th>
                                                <th>C.A.</th>
                                                <th>Qtd</th>
                                                <th>Motivo</th>
                                                <th>Condição</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-det-devolucoes">
                                            <!-- Dinâmico via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar Ficha</button>
            </div>
        </div>
    </div>
</div>

<!-- Modais secundários de PIN -->
<!-- 6. Modal Cadastrar PIN -->
<div class="modal fade" id="modalCadastrarPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="cadastrar_pin">
            <input type="hidden" id="pin-cad-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Cadastrar PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Defina um PIN (4 a 10 dígitos) *</label>
                    <input type="password" class="form-control" name="pin" maxlength="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Modal Redefinir PIN -->
<div class="modal fade" id="modalRedefinirPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="redefinir_pin">
            <input type="hidden" id="pin-red-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat me-2"></i>Redefinir PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Defina o novo PIN *</label>
                    <input type="password" class="form-control" name="pin" maxlength="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Alterar PIN</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Modal Bloquear PIN -->
<div class="modal fade" id="modalBloquearPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="bloquear_pin">
            <input type="hidden" id="pin-bloq-ass-id" name="ass_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-lock me-2"></i>Bloquear PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Motivo do Bloqueio *</label>
                    <input type="text" class="form-control" name="motivo_bloqueio" placeholder="Ex: Suspeita de fraude" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Bloquear</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Modal Desbloquear PIN -->
<div class="modal fade" id="modalDesbloquearPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="desbloquear_pin">
            <input type="hidden" id="pin-desb-ass-id" name="ass_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-unlock me-2"></i>Desbloquear</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirma o desbloqueio administrativo da assinatura do funcionário?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Desbloquear</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';

document.addEventListener('DOMContentLoaded', function() {
    initBuscaEFiltros();
});

/**
 * Lógica do buscador em tempo real no front-end
 */
function initBuscaEFiltros() {
    const busca = document.getElementById('busca-input');
    const filtroSetor = document.getElementById('filtro-setor');
    const filtroStatus = document.getElementById('filtro-status');
    const rows = document.querySelectorAll('.func-row');
    
    function aplicarFiltros() {
        const query = busca.value.toLowerCase().trim();
        const setor = filtroSetor.value;
        const status = filtroStatus.value;
        
        rows.forEach(row => {
            const rNome = row.getAttribute('data-nome');
            const rCpf = row.getAttribute('data-cpf');
            const rCargo = row.getAttribute('data-cargo');
            const rSetor = row.getAttribute('data-setor');
            const rStatus = row.getAttribute('data-status');
            
            const bateBusca = rNome.includes(query) || rCpf.includes(query) || rCargo.includes(query);
            const bateSetor = (setor === '' || rSetor === setor);
            const bateStatus = (status === '' || rStatus === status);
            
            if (bateBusca && bateSetor && bateStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    busca.addEventListener('input', aplicarFiltros);
    filtroSetor.addEventListener('change', aplicarFiltros);
    filtroStatus.addEventListener('change', aplicarFiltros);
}

/**
 * Preenche o modal de edição com os dados do funcionário selecionado
 */
function prepararEdicao(func) {
    document.getElementById('edit-fun-id').value = func.fun_id;
    document.getElementById('edit-fun-nome').value = func.fun_nome;
    document.getElementById('edit-fun-cpf').value = formatCPF(func.fun_cpf);
    document.getElementById('edit-fun-esocial').value = func.fun_esocial;
    document.getElementById('edit-fun-dept').value = func.fun_departamento;
    document.getElementById('edit-fun-cargo').value = func.fun_cargo;
    document.getElementById('edit-fun-admissao').value = func.fun_dataadmissao;
    document.getElementById('edit-fun-situacao').value = func.fun_situacao;
    
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

/**
 * Preenche o modal de exclusão
 */
function confirmarExclusao(id, nome) {
    document.getElementById('excluir-fun-id').value = id;
    document.getElementById('excluir-fun-nome').innerText = nome;
    
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

/**
 * Mascara o CPF conforme as diretrizes da LGPD (exibe apenas os 3 primeiros e os 2 últimos dígitos)
 */
function mascararCPF(cpf) {
    if (!cpf) return '';
    const limpo = cpf.replace(/\D/g, '');
    if (limpo.length !== 11) return cpf;
    return limpo.substring(0, 3) + '.***.***-' + limpo.substring(9, 11);
}

/**
 * Mascara o código do eSocial conforme as diretrizes da LGPD (exibe as 3 primeiras letras e as 2 últimas)
 */
function mascararESocial(esocial) {
    if (!esocial) return '';
    const len = esocial.length;
    if (len <= 5) return '*****';
    return esocial.substring(0, 3) + '*****' + esocial.substring(len - 2);
}

/**
 * Puxa os dados consolidados do funcionário de forma assíncrona da API (AJAX)
 */
function verDetalhes(funId) {

    // Abre modal de loading fictício ou preenche com "Carregando..."
    document.getElementById('det-nome').innerText = 'Carregando...';
    document.getElementById('det-iniciais').innerText = '...';
    document.getElementById('det-cpf').innerText = '';
    document.getElementById('det-esocial').innerText = '';
    document.getElementById('det-setor').innerText = '';
    document.getElementById('det-cargo').innerText = '';
    document.getElementById('det-admissao').innerText = '';
    document.getElementById('det-situacao').className = 'status-badge';
    document.getElementById('det-situacao').innerText = '';
    
    document.getElementById('det-pin-status').className = 'status-badge';
    document.getElementById('det-pin-status').innerText = 'Carregando...';
    
    const areaAcoes = document.getElementById('area-acoes-pin');
    if (areaAcoes) areaAcoes.innerHTML = '';
    
    document.getElementById('lista-det-entregas').innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando histórico...</td></tr>';
    document.getElementById('lista-det-devolucoes').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando histórico...</td></tr>';

    const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    modal.show();

    // 1. Puxa dados do Funcionário via Proxy Genérico
    fetch(`${PROXY_URL}?route=funcionarios/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const f = res.data;
                document.getElementById('det-nome').innerText = f.fun_nome;
                document.getElementById('det-iniciais').innerText = f.fun_nome.split(' ').slice(0,2).map(n => n[0]).join('').toUpperCase();
                document.getElementById('det-cpf').innerText = mascararCPF(f.fun_cpf);
                document.getElementById('det-esocial').innerText = mascararESocial(f.fun_esocial);
                document.getElementById('det-setor').innerText = f.fun_departamento;
                document.getElementById('det-cargo').innerText = f.fun_cargo;
                
                // Formatação data admissão
                const parts = f.fun_dataadmissao.split('-');
                document.getElementById('det-admissao').innerText = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : f.fun_dataadmissao;
                
                // Situação Funcional
                const situacao = f.fun_situacao;
                const badge = document.getElementById('det-situacao');
                badge.className = `status-badge ${situacao.toLowerCase()}`;
                badge.innerText = situacao;

                // 2. Consulta assinatura eletrônica do funcionário
                consultarAssinatura(funId);
                
                // 3. Consulta histórico de entregas
                consultarEntregas(funId);
            }
        })
        .catch(err => {
            document.getElementById('det-nome').innerText = 'Erro ao carregar';
        });
}

function consultarAssinatura(funId) {
    const pinBadge = document.getElementById('det-pin-status');
    const areaAcoes = document.getElementById('area-acoes-pin');
    
    fetch(`${PROXY_URL}?route=assinaturas/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const ass = res.data;
                const status = ass.ass_status.toUpperCase();
                pinBadge.className = `status-badge ${status.toLowerCase()}`;
                pinBadge.innerText = status;

                if (areaAcoes) {
                    if (status === 'ATIVO') {
                        areaAcoes.innerHTML = `
                            <button class="btn btn-sm btn-outline-warning" onclick="prepararBloqueio(${ass.ass_id})"><i class="bi bi-lock me-1"></i>Bloquear Assinatura</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="prepararRedefinir(${funId})"><i class="bi bi-arrow-repeat me-1"></i>Redefinir PIN</button>
                        `;
                    } else if (status === 'BLOQUEADO') {
                        areaAcoes.innerHTML = `
                            <button class="btn btn-sm btn-outline-success" onclick="prepararDesbloqueio(${ass.ass_id})"><i class="bi bi-unlock me-1"></i>Desbloquear PIN</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="prepararRedefinir(${funId})"><i class="bi bi-arrow-repeat me-1"></i>Redefinir PIN</button>
                        `;
                    }
                }
            } else {
                pinBadge.className = 'status-badge inativo';
                pinBadge.innerText = 'NÃO CADASTRADO';
                if (areaAcoes) {
                    areaAcoes.innerHTML = `
                        <button class="btn btn-sm btn-primary" onclick="prepararCadastroPin(${funId})"><i class="bi bi-key me-1"></i>Cadastrar PIN</button>
                    `;
                }
            }
        })
        .catch(() => {
            pinBadge.className = 'status-badge inativo';
            pinBadge.innerText = 'ERRO';
        });
}

function consultarEntregas(funId) {
    const listEntregas = document.getElementById('lista-det-entregas');
    const listDevolucoes = document.getElementById('lista-det-devolucoes');

    fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                let htmlEntregas = '';
                let htmlDevolucoes = '';
                let totalEntregas = 0;
                let totalDevolucoes = 0;

                res.data.forEach(entrega => {
                    const dataFormat = new Date(entrega.entr_data_entrega).toLocaleString('pt-BR');
                    
                    entrega.itens.forEach(item => {
                        // Linha de entregas
                        totalEntregas++;
                        const isCancelado = item.item_status === 'CANCELADO';
                        const isDevolvido = item.item_status === 'DEVOLVIDO';
                        
                        let statusClass = 'ativo';
                        if (isCancelado) statusClass = 'inativo';
                        if (isDevolvido) statusClass = 'pendente'; // Amarelo/Aviso
                        
                        htmlEntregas += `
                            <tr>
                                <td>${dataFormat.slice(0, 10)}</td>
                                <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                                <td>${item.item_epi_ca_snapshot || '---'}</td>
                                <td>${item.item_quantidade}</td>
                                <td>${item.item_tamanho || '---'}</td>
                                <td class="text-muted" style="font-size: 13px;">${item.item_devolucao_motivo || entrega.entr_motivo || '---'}</td>
                                <td><span class="status-badge ${statusClass}">${item.item_status}</span></td>
                            </tr>
                        `;

                        // Linha de devoluções se o item foi devolvido
                        if (item.item_data_devolucao) {
                            totalDevolucoes++;
                            const dataDev = new Date(item.item_data_devolucao).toLocaleString('pt-BR');
                            htmlDevolucoes += `
                                <tr>
                                    <td>${dataDev.slice(0, 10)}</td>
                                    <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                                    <td>${item.item_epi_ca_snapshot || '---'}</td>
                                    <td>${item.item_quantidade}</td>
                                    <td>${item.item_devolucao_motivo || '---'}</td>
                                    <td><span class="badge bg-secondary">${item.item_devolucao_condicao || 'USADO'}</span></td>
                                </tr>
                            `;
                        }
                    });
                });

                listEntregas.innerHTML = totalEntregas > 0 ? htmlEntregas : '<tr><td colspan="7" class="text-center text-muted py-3">Sem registros de entregas para este colaborador.</td></tr>';
                listDevolucoes.innerHTML = totalDevolucoes > 0 ? htmlDevolucoes : '<tr><td colspan="6" class="text-center text-muted py-3">Nenhuma devolução realizada por este colaborador.</td></tr>';
            } else {
                listEntregas.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Sem registros.</td></tr>';
                listDevolucoes.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Sem registros.</td></tr>';
            }
        })
        .catch(() => {
            listEntregas.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Erro ao carregar dados.</td></tr>';
            listDevolucoes.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Erro ao carregar dados.</td></tr>';
        });
}

/* Funções de acionamento de modais secundários de PIN */
function prepararCadastroPin(funId) {
    document.getElementById('pin-cad-fun-id').value = funId;
    new bootstrap.Modal(document.getElementById('modalCadastrarPin')).show();
}

function prepararRedefinir(funId) {
    document.getElementById('pin-red-fun-id').value = funId;
    new bootstrap.Modal(document.getElementById('modalRedefinirPin')).show();
}

function prepararBloqueio(assId) {
    document.getElementById('pin-bloq-ass-id').value = assId;
    new bootstrap.Modal(document.getElementById('modalBloquearPin')).show();
}

function prepararDesbloqueio(assId) {
    document.getElementById('pin-desb-ass-id').value = assId;
    new bootstrap.Modal(document.getElementById('modalDesbloquearPin')).show();
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
