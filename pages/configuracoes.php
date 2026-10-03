<?php
declare(strict_types=1);

$page_title = 'Configurações';
$active_menu = 'configuracoes';

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;

// Lógica de alteração da própria senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $senhaAtual = $_POST['senha_atual'] ?? '';
    $novaSenha = $_POST['nova_senha'] ?? '';
    $confirmarSenha = $_POST['confirmar_senha'] ?? '';

    if ($senhaAtual === '' || $novaSenha === '' || $confirmarSenha === '') {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve possuir pelo menos 6 caracteres.';
    } elseif (!preg_match('/[a-zA-Z]/', $novaSenha) || !preg_match('/[0-9]/', $novaSenha)) {
        $erro = 'A nova senha deve conter pelo menos uma letra e um número.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'A confirmação da nova senha não coincide.';
    } else {
        try {
            $response = $api->post('auth/alterar-senha-primeiro-acesso', [
                'senha_atual' => $senhaAtual,
                'nova_senha' => $novaSenha,
                'confirmar_senha' => $confirmarSenha
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Sua senha pessoal foi alterada com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao alterar senha. Verifique se a senha atual está correta.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão na redefinição: ' . $e->getMessage();
        }
    }
}

// Carrega as configurações da API vigentes
$configApiUrl = 'https://gestao-epi-api.onrender.com/';
try {
    $configData = require __DIR__ . '/../config/api.php';
    $configApiUrl = $configData['api_base_url'] ?? $configApiUrl;
} catch (\Throwable $e) {}

// Sincroniza os dados atualizados do usuário autenticado (incluindo status de aceite dos termos)
try {
    $meRes = $api->get('auth/me');
    if (isset($meRes['success']) && $meRes['success'] && is_array($meRes['data'])) {
        $_SESSION['usuario'] = array_merge($_SESSION['usuario'] ?? [], $meRes['data']);
    }
} catch (\Throwable $e) {}

$currentUser = $_SESSION['usuario'] ?? [
    'usu_id' => 0,
    'usu_login' => 'Operador',
    'usu_perfil' => 'ADMINISTRADOR',
    'usu_ultimo_login' => null,
    'usu_aceite_termos' => 0
];

$perfisMap = [
    'ADMINISTRADOR' => 'Administrador do Sistema',
    'RH_ADMINISTRATIVO' => 'RH Administrativo',
    'TECNICO_SST' => 'Técnico de Segurança do Trabalho (SST)',
    'ALMOXARIFE_OPERADOR' => 'Almoxarife / Operador de Estoque',
    'GESTOR' => 'Gestor / Fiscal de Contrato'
];
$perfilLabel = $perfisMap[$currentUser['usu_perfil']] ?? $currentUser['usu_perfil'];
$aceitouTermos = !empty($currentUser['usu_aceite_termos']);
?>

<style>
.settings-container {
    max-width: 720px;
    margin: 0 auto;
}

.settings-group-title {
    color: #2563eb;
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 0.5rem;
    margin-top: 1.5rem;
}

.settings-card {
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    transition: all 0.2s ease;
}

.settings-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.07);
}

.settings-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background-color: #eff6ff;
    color: #2563eb;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

.settings-logout-card {
    border: 2px solid #ef4444 !important;
    background-color: #ffffff;
    border-radius: 14px;
    padding: 16px 20px;
    box-shadow: 0 2px 6px rgba(239, 68, 68, 0.08);
    transition: all 0.2s ease;
}

.settings-logout-card:hover {
    background-color: #fef2f2;
}

.settings-logout-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background-color: #fef2f2;
    color: #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}

body.dark-mode .settings-group-title {
    color: #60a5fa !important;
}

body.dark-mode .settings-card {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}

body.dark-mode .settings-icon-box {
    background-color: #1e3a8a !important;
    color: #93c5fd !important;
}

body.dark-mode .settings-card h6 {
    color: #f8fafc !important;
}

body.dark-mode .settings-logout-card {
    background-color: #1e293b !important;
    border-color: #ef4444 !important;
}

body.dark-mode .settings-logout-icon {
    background-color: #450a0a !important;
    color: #fca5a5 !important;
}
</style>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="settings-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold m-0" style="color: var(--color-primary);">Configurações</h3>
                    <p class="text-muted small m-0">Gerencie suas credenciais de segurança, aparência e termos do ecossistema.</p>
                </div>
            </div>

            <?php if ($erro !== null): ?>
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($erro) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($sucesso !== null): ?>
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <div><?= htmlspecialchars($sucesso) ?></div>
                </div>
            <?php endif; ?>

            <!-- 1. Segurança de Acesso -->
            <div class="settings-group mb-3">
                <h6 class="settings-group-title">Segurança de Acesso</h6>
                <div class="settings-card">
                    <div class="d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="toggleSenhaForm()">
                        <div class="d-flex align-items-center gap-3">
                            <div class="settings-icon-box">
                                <i class="bi bi-shield-lock-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold m-0" style="color: #334155;">Alterar Senha do Operador</h6>
                                <small class="text-muted">Redefinir a senha do operador logado</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-down text-muted ms-2" id="icon-chevron-senha"></i>
                    </div>

                    <!-- Formulário de Alteração de Senha Accordion -->
                    <div id="form-alterar-senha" class="mt-3 pt-3 border-top" style="display: <?= ($erro !== null || $sucesso !== null) ? 'block' : 'none' ?>;">
                        <form method="POST" action="configuracoes.php" novalidate>
                            <input type="hidden" name="acao" value="alterar_senha">
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Senha Atual *</label>
                                <input type="password" class="form-control" name="senha_atual" placeholder="Digite sua senha pessoal de acesso vigente" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Nova Senha *</label>
                                <input type="password" class="form-control" name="nova_senha" placeholder="Digite a nova senha de acesso segura" required>
                                <small class="text-muted d-block mt-1" style="font-size: 11px;">A senha deve conter ao menos 6 caracteres, contendo pelo menos uma letra e um número.</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Confirmar Nova Senha *</label>
                                <input type="password" class="form-control" name="confirmar_senha" placeholder="Repita a nova senha de acesso" required>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" style="background-color: #2563eb; border: none; padding: 10px; font-weight: 500;">
                                <i class="bi bi-shield-check me-1"></i> Atualizar Minha Senha
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- 2. Aparência -->
            <div class="settings-group mb-3">
                <h6 class="settings-group-title">Aparência</h6>
                <div class="settings-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="settings-icon-box">
                                <i class="bi bi-gear-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold m-0" style="color: #334155;">Tema do Aplicativo</h6>
                                <small class="text-muted">Escolha entre modo claro e modo escuro</small>
                            </div>
                        </div>
                        <div>
                            <select id="select-tema-app" class="form-select form-select-sm" style="min-width: 110px; border-radius: 8px; font-weight: 500;" onchange="alterarTemaApp(this.value)">
                                <option value="light">Claro</option>
                                <option value="dark">Escuro</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Termos e Políticas (LGPD) -->
            <div class="settings-group mb-3">
                <h6 class="settings-group-title">Termos e Políticas (LGPD)</h6>
                <div class="settings-card">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2" style="cursor: pointer;" onclick="toggleTermosInfo()">
                        <div class="d-flex align-items-center gap-3">
                            <div class="settings-icon-box">
                                <i class="bi bi-lock-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold m-0" style="color: #334155;">Termos e Políticas (LGPD)</h6>
                                <small class="text-muted">Consulte os Termos e Políticas de Privacidade (LGPD).</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-chevron-down text-muted" id="icon-chevron-termos"></i>
                        </div>
                    </div>

                    <div id="info-termos-lgpd" class="mt-3 pt-3 border-top" style="display: none;">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div>
                                <?php if ($aceitouTermos): ?>
                                    <?php
                                    $dataAceiteMs = isset($currentUser['usu_data_aceite_termos']) ? (int)$currentUser['usu_data_aceite_termos'] : null;
                                    $strAceite = $dataAceiteMs !== null && $dataAceiteMs > 0
                                        ? 'Aceitos em ' . date('d/m/Y \à\s H:i', (int)($dataAceiteMs / 1000))
                                        : 'Termos de Uso já aceitos no sistema';
                                    ?>
                                    <div class="badge bg-success-subtle text-success border border-success-subtle p-2" style="font-weight: 500; font-size: 12px;">
                                        <i class="bi bi-patch-check-fill me-1"></i> <?= htmlspecialchars($strAceite) ?>
                                    </div>
                                <?php else: ?>
                                    <div class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle p-2" style="font-weight: 500; font-size: 12px;">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i> Você ainda não registrou o aceite dos Termos de Uso.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <a href="<?= APP_ROOT ?>pages/aceitar-termos.php" class="btn btn-outline-primary btn-sm px-3" style="border-radius: 8px;">
                                <i class="bi bi-file-text me-1"></i> Ver Termos de Uso
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4. Informações -->
            <div class="settings-group mb-3">
                <h6 class="settings-group-title">Informações</h6>
                <div class="settings-card">
                    <div class="d-flex align-items-center justify-content-between" style="cursor: pointer;" onclick="toggleSobreProjeto()">
                        <div class="d-flex align-items-center gap-3">
                            <div class="settings-icon-box">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold m-0" style="color: #334155;">Sobre o Projeto</h6>
                                <small class="text-muted">Descrição técnica e acadêmica do TCC</small>
                            </div>
                        </div>
                        <i class="bi bi-chevron-down text-muted ms-2" id="icon-chevron-sobre"></i>
                    </div>

                    <!-- Informações do Projeto e Perfil Expandível -->
                    <div id="info-sobre-projeto" class="mt-3 pt-3 border-top" style="display: none;">
                        <div class="p-3 bg-body-tertiary rounded-3 mb-3 border">
                            <h6 class="fw-bold text-primary mb-3" style="font-size: 13px;"><i class="bi bi-person-badge me-1"></i>Dados do Meu Perfil</h6>
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 56px; height: 56px; font-size: 22px; font-weight: 600;">
                                    <?= strtoupper(substr($currentUser['usu_login'], 0, 2)) ?>
                                </div>
                                <h6 class="fw-bold m-0"><?= htmlspecialchars($currentUser['usu_login']) ?></h6>
                                <span class="badge bg-primary-subtle text-primary mt-1" style="font-size:11px;"><?= htmlspecialchars($perfilLabel) ?></span>
                            </div>
                            <div class="d-flex flex-column gap-2 text-muted" style="font-size: 12px;">
                                <div class="d-flex justify-content-between border-bottom pb-1">
                                    <span>ID do Usuário:</span>
                                    <span class="fw-bold text-body">#<?= $currentUser['usu_id'] ?></span>
                                </div>
                                <div class="d-flex justify-content-between border-bottom pb-1">
                                    <span>Situação da Conta:</span>
                                    <span class="badge bg-success-subtle text-success">ATIVO</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Último Login:</span>
                                    <span class="fw-medium text-body"><?= !empty($currentUser['usu_ultimo_login']) ? formatarDataHoraBr($currentUser['usu_ultimo_login'], 'd/m/Y H:i') : 'Esta sessão' ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-body-tertiary rounded-3 border" style="font-size: 12px;">
                            <h6 class="fw-bold text-primary mb-2" style="font-size: 13px;"><i class="bi bi-cloud-check me-1"></i>Status da API &amp; Ecossistema</h6>
                            <p class="mb-1 text-muted">URL Base de Conexão:</p>
                            <code class="d-block p-2 bg-dark text-light rounded text-break mb-2" style="font-size: 11px;"><?= htmlspecialchars($configApiUrl) ?></code>
                            <p class="m-0 text-muted" style="font-size: 11px;">
                                <i class="bi bi-info-circle me-1"></i> Sistema Gestão de EPI Web - Versão 8.0 (2026). Descrição técnica e acadêmica desenvolvida para trabalho de conclusão de curso (TCC).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 5. ENCERRAR SESSÃO (SAIR) -->
            <div class="settings-group mt-4 mb-4">
                <a href="<?= APP_ROOT ?>logout.php" class="settings-logout-card text-decoration-none d-flex align-items-center gap-3">
                    <div class="settings-logout-icon">
                        <i class="bi bi-box-arrow-right"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold m-0" style="color: #ef4444; font-size: 0.95rem; letter-spacing: 0.5px;">ENCERRAR SESSÃO (SAIR)</h6>
                    </div>
                </a>
            </div>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const savedTheme = localStorage.getItem('theme-mode') || 'light';
    const select = document.getElementById('select-tema-app');
    if (select) {
        select.value = savedTheme;
    }
});

function alterarTemaApp(val) {
    const html = document.documentElement;
    const body = document.body;
    if (val === 'dark') {
        html.classList.add('dark-mode');
        body.classList.add('dark-mode');
        localStorage.setItem('theme-mode', 'dark');
    } else {
        html.classList.remove('dark-mode');
        body.classList.remove('dark-mode');
        localStorage.setItem('theme-mode', 'light');
    }
    const themeBtn = document.getElementById('theme-toggle-btn');
    if (themeBtn) {
        const themeIcon = themeBtn.querySelector('i');
        if (themeIcon) {
            themeIcon.className = (val === 'dark') ? 'bi bi-sun' : 'bi bi-moon-stars';
        }
    }
}

function toggleSenhaForm() {
    const form = document.getElementById('form-alterar-senha');
    const icon = document.getElementById('icon-chevron-senha');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        if (icon) icon.className = 'bi bi-chevron-up text-muted ms-2';
    } else {
        form.style.display = 'none';
        if (icon) icon.className = 'bi bi-chevron-down text-muted ms-2';
    }
}

function toggleTermosInfo() {
    const info = document.getElementById('info-termos-lgpd');
    const icon = document.getElementById('icon-chevron-termos');
    if (info.style.display === 'none' || info.style.display === '') {
        info.style.display = 'block';
        if (icon) icon.className = 'bi bi-chevron-up text-muted ms-2';
    } else {
        info.style.display = 'none';
        if (icon) icon.className = 'bi bi-chevron-down text-muted ms-2';
    }
}

function toggleSobreProjeto() {
    const info = document.getElementById('info-sobre-projeto');
    const icon = document.getElementById('icon-chevron-sobre');
    if (info.style.display === 'none' || info.style.display === '') {
        info.style.display = 'block';
        if (icon) icon.className = 'bi bi-chevron-up text-muted ms-2';
    } else {
        info.style.display = 'none';
        if (icon) icon.className = 'bi bi-chevron-down text-muted ms-2';
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

