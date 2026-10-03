<?php
declare(strict_types=1);

$page_title = 'Usuários e Permissões';
$active_menu = 'usuarios';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'GESTOR']; // Apenas administradores do sistema possuem acesso

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;
$usuarios = [];

// Processamento de formulários CRUD (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar Usuário
    if ($acao === 'cadastrar') {
        $login = trim($_POST['usu_login'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $confSenha = $_POST['confirmar_senha'] ?? '';
        $perfil = $_POST['usu_perfil'] ?? '';
        $status = $_POST['usu_status'] ?? 'ATIVO';

        if ($senha !== $confSenha) {
            $erro = 'A confirmação de senha não coincide.';
        } else {
            try {
                $response = $api->post('usuarios', [
                    'usu_login' => $login,
                    'senha' => $senha,
                    'confirmar_senha' => $confSenha,
                    'usu_perfil' => $perfil,
                    'usu_status' => $status
                ]);

                if (isset($response['success']) && $response['success']) {
                    $sucesso = 'Operador "' . htmlspecialchars($login) . '" cadastrado com sucesso!';
                } else {
                    $erro = $response['message'] ?? 'Falha ao cadastrar usuário.';
                }
            } catch (Exception $e) {
                $erro = 'Erro de conexão: ' . $e->getMessage();
            }
        }
    }

    // 2. Editar Usuário
    if ($acao === 'editar') {
        $id = (int)($_POST['usu_id'] ?? 0);
        $login = trim($_POST['usu_login'] ?? '');
        $perfil = $_POST['usu_perfil'] ?? '';
        $status = $_POST['usu_status'] ?? 'ATIVO';

        try {
            $response = $api->put("usuarios/{$id}", [
                'usu_login' => $login,
                'usu_perfil' => $perfil,
                'usu_status' => $status
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Dados do operador atualizados com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao atualizar dados.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 3. Excluir/Inativar Usuário
    if ($acao === 'excluir') {
        $id = (int)($_POST['usu_id'] ?? 0);

        try {
            $response = $api->delete("usuarios/{$id}");

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Operador inativado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao inativar usuário.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 4. Redefinir Senha do Usuário (Forçada por Admin)
    if ($acao === 'redefinir_senha') {
        $id = (int)($_POST['usu_id'] ?? 0);
        $novaSenha = $_POST['nova_senha'] ?? '';
        $confSenha = $_POST['confirmar_senha'] ?? '';

        if ($novaSenha !== $confSenha) {
            $erro = 'A confirmação de senha não coincide.';
        } else {
            try {
                $response = $api->post("usuarios/{$id}/redefinir-senha", [
                    'senha_temporaria' => $novaSenha,
                    'confirmar_senha' => $confSenha
                ]);

                if (isset($response['success']) && $response['success']) {
                    $sucesso = 'Senha do operador redefinida com sucesso!';
                } else {
                    $erro = $response['message'] ?? 'Falha ao redefinir senha.';
                }
            } catch (Exception $e) {
                $erro = 'Erro de conexão: ' . $e->getMessage();
            }
        }
    }
}

// Carrega listagem de usuários
try {
    $listaRes = $api->get('usuarios');
    if (isset($listaRes['success']) && $listaRes['success']) {
        $usuarios = $listaRes['data'];
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de usuários da API: ' . $e->getMessage();
}

$perfisMap = [
    'ADMINISTRADOR' => 'Administrador',
    'RH_ADMINISTRATIVO' => 'RH Administrativo',
    'TECNICO_SST' => 'Técnico SST',
    'ALMOXARIFE_OPERADOR' => 'Almoxarife',
    'GESTOR' => 'Gestor'
];
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Usuários e Permissões</h3>
                <p class="text-muted">Cadastre e gerencie operadores do sistema e atribua níveis de acesso (RBAC) conforme o perfil funcional.</p>
            </div>
            
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                <i class="bi bi-person-plus me-1"></i> Novo Operador
            </button>
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
                        <input type="text" id="busca-input" class="form-control border-start-0" placeholder="Buscar por login ou perfil...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filtro-perfil" class="form-select">
                        <option value="">Todos os Perfis</option>
                        <?php foreach ($perfisMap as $key => $label): ?>
                            <option value="<?= $key ?>"><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos os Status</option>
                        <option value="ATIVO">Ativo</option>
                        <option value="INATIVO">Inativo</option>
                        <option value="BLOQUEADO">Bloqueado</option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-usuarios">
                    <thead>
                        <tr>
                            <th>Operador (Login)</th>
                            <th>Nível de Acesso (Perfil)</th>
                            <th>Última Conexão</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Nenhum operador do sistema cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usu): ?>
                                <?php
                                $situClass = strtolower($usu['usu_status'] ?? 'ativo');
                                $perfilLabel = $perfisMap[$usu['usu_perfil']] ?? $usu['usu_perfil'];
                                $ultimaConexao = !empty($usu['usu_ultimo_login']) ? formatarDataHoraBr($usu['usu_ultimo_login'], 'd/m/Y H:i') : 'Nunca conectado';
                                ?>
                                <tr class="usu-row" 
                                    data-login="<?= htmlspecialchars(strtolower($usu['usu_login'])) ?>"
                                    data-perfil="<?= htmlspecialchars($usu['usu_perfil']) ?>"
                                    data-status="<?= htmlspecialchars($usu['usu_status'] ?? 'ATIVO') ?>">
                                    
                                    <td class="fw-semibold">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight:600; font-size:12px;">
                                                <?= strtoupper(substr($usu['usu_login'], 0, 2)) ?>
                                            </div>
                                            <span><?= htmlspecialchars($usu['usu_login']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($perfilLabel) ?></td>
                                    <td class="text-muted"><?= $ultimaConexao ?></td>
                                    <td>
                                        <span class="status-badge <?= $situClass ?>"><?= htmlspecialchars($usu['usu_status'] ?? 'ATIVO') ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button class="btn btn-sm btn-light border text-primary py-1 px-2" onclick="prepararEdicao(<?= htmlspecialchars(json_encode($usu)) ?>)" title="Editar Perfil / Situação">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm btn-light border text-warning py-1 px-2" onclick="prepararRedefinir(<?= htmlspecialchars(json_encode($usu)) ?>)" title="Redefinir Senha do Operador">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            
                                            <?php if (($usu['usu_status'] ?? 'ATIVO') === 'ATIVO'): ?>
                                                <button class="btn btn-sm btn-light border text-danger py-1 px-2" onclick="confirmarExclusao(<?= $usu['usu_id'] ?>, '<?= htmlspecialchars($usu['usu_login']) ?>')" title="Inativar Operador">
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
        <form class="modal-content" method="POST" action="usuarios.php" novalidate>
            <input type="hidden" name="acao" value="cadastrar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-person-plus me-2"></i>Novo Operador do Sistema</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Usuário (Login) *</label>
                    <input type="text" class="form-control" name="usu_login" placeholder="Ex: almoxarife_joao" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Senha Provisória *</label>
                    <input type="password" class="form-control" name="senha" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Senha *</label>
                    <input type="password" class="form-control" name="confirmar_senha" placeholder="Digite a senha novamente" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Nível de Acesso (Perfil) *</label>
                        <select class="form-select" name="usu_perfil" required>
                            <?php foreach ($perfisMap as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                                        <label class="form-label">Situação</label>
                                        <select class="form-select" name="usu_status">
                                            <option value="ATIVO">Ativo</option>
                                            <option value="BLOQUEADO">Bloqueado</option>
                                        </select>
                                    </div>
                </div>
                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Operador</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="usuarios.php" novalidate>
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" id="edit-usu-id" name="usu_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-pencil me-2"></i>Editar Operador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nome de Usuário (Login) *</label>
                    <input type="text" class="form-control" id="edit-usu-login" name="usu_login" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Nível de Acesso (Perfil) *</label>
                        <select class="form-select" id="edit-usu-perfil" name="usu_perfil" required>
                            <?php foreach ($perfisMap as $key => $label): ?>
                                <option value="<?= $key ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Situação *</label>
                        <select class="form-select" id="edit-usu-status" name="usu_status" required>
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="BLOQUEADO">Bloqueado</option>
                        </select>
                    </div>
                </div>
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
        <form class="modal-content" method="POST" action="usuarios.php">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" id="excluir-usu-id" name="usu_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Confirmar Inativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja revogar os acessos do operador <strong id="excluir-usu-login"></strong>?</p>
                <p class="text-muted" style="font-size: 13px;">O operador terá sua conta inativada no sistema e não conseguirá mais realizar chamadas autenticadas na API.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Inativar Conta</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Redefinir Senha -->
<div class="modal fade" id="modalRedefinir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="usuarios.php" novalidate>
            <input type="hidden" name="acao" value="redefinir_senha">
            <input type="hidden" id="red-usu-id" name="usu_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-key-fill me-2"></i>Forçar Nova Senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nova Senha *</label>
                    <input type="password" class="form-control" name="nova_senha" placeholder="Mínimo 6 caracteres" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirmar Senha *</label>
                    <input type="password" class="form-control" name="confirmar_senha" placeholder="Repita a nova senha" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Alterar Senha</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    initBuscaEFiltros();
});

/**
 * Filtro de usuários
 */
function initBuscaEFiltros() {
    const busca = document.getElementById('busca-input');
    const filtroPerfil = document.getElementById('filtro-perfil');
    const filtroStatus = document.getElementById('filtro-status');
    const rows = document.querySelectorAll('.usu-row');
    
    function aplicarFiltros() {
        const query = busca.value.toLowerCase().trim();
        const perfil = filtroPerfil.value;
        const status = filtroStatus.value;
        
        rows.forEach(row => {
            const rLogin = row.getAttribute('data-login');
            const rPerfil = row.getAttribute('data-perfil');
            const rStatus = row.getAttribute('data-status');
            
            const bateBusca = rLogin.includes(query) || rPerfil.toLowerCase().includes(query);
            const batePerfil = (perfil === '' || rPerfil === perfil);
            const bateStatus = (status === '' || rStatus === status);
            
            if (bateBusca && batePerfil && bateStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    busca.addEventListener('input', aplicarFiltros);
    filtroPerfil.addEventListener('change', aplicarFiltros);
    filtroStatus.addEventListener('change', aplicarFiltros);
}

/**
 * Preenche modal de exclusão
 */
function confirmarExclusao(id, login) {
    document.getElementById('excluir-usu-id').value = id;
    document.getElementById('excluir-usu-login').innerText = login;
    
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

/**
 * Preenche modal de edição
 */
function prepararEdicao(usu) {
    document.getElementById('edit-usu-id').value = usu.usu_id;
    document.getElementById('edit-usu-login').value = usu.usu_login;
    document.getElementById('edit-usu-perfil').value = usu.usu_perfil;
    document.getElementById('edit-usu-status').value = usu.usu_status || 'ATIVO';
    
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

/**
 * Preenche modal de redefinição
 */
function prepararRedefinir(usu) {
    document.getElementById('red-usu-id').value = usu.usu_id;
    new bootstrap.Modal(document.getElementById('modalRedefinir')).show();
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
