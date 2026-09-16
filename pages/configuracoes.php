<?php
declare(strict_types=1);

$page_title = 'Configurações do Perfil';
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
            // A API local / Render não possui uma rota dedicada para alteração de senha de usuário comum (apenas redefinição forçada de admin)
            // No entanto, podemos reusar a rota de primeiro acesso para alterar a própria senha!
            // Vamos testar chamando a rota auth/alterar-senha-primeiro-acesso
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
        $currentUser = $_SESSION['usuario'];
    }
} catch (\Throwable $e) {}

$perfisMap = [
    'ADMINISTRADOR' => 'Administrador do Sistema',
    'RH_ADMINISTRATIVO' => 'RH Administrativo',
    'TECNICO_SST' => 'Técnico de Segurança do Trabalho (SST)',
    'ALMOXARIFE_OPERADOR' => 'Almoxarife / Operador de Estoque',
    'GESTOR' => 'Gestor / Fiscal de Contrato'
];
$perfilLabel = $perfisMap[$currentUser['usu_perfil']] ?? $currentUser['usu_perfil'];
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Configurações e Perfil</h3>
                <p class="text-muted">Gerencie suas credenciais de segurança e consulte o apontamento de conexão do ecossistema.</p>
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
            <!-- Informações do Operador -->
            <div class="col-lg-5">
                <div class="card-custom mb-4">
                    <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-person-badge me-2"></i>Dados do Meu Perfil</h5>
                    
                    <div class="text-center mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 72px; height: 72px; font-size: 28px; font-weight: 600;">
                            <?= strtoupper(substr($currentUser['usu_login'], 0, 2)) ?>
                        </div>
                        <h5 class="fw-bold m-0"><?= htmlspecialchars($currentUser['usu_login']) ?></h5>
                        <span class="badge bg-primary-light text-primary mt-2" style="font-size:11px;"><?= htmlspecialchars($perfilLabel) ?></span>
                    </div>

                    <div class="d-flex flex-column gap-2" style="font-size: 13px;">
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">ID do Usuário:</span>
                            <span class="fw-semibold">#<?= $currentUser['usu_id'] ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom py-2">
                            <span class="text-muted">Situação da Conta:</span>
                            <span class="status-badge ativo">ATIVO</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span class="text-muted">Último Login:</span>
                            <span class="fw-semibold text-muted"><?= !empty($currentUser['usu_ultimo_login']) ? date('d/m/Y H:i', strtotime($currentUser['usu_ultimo_login'])) : 'Esta sessão' ?></span>
                        </div>
                    </div>

                    <hr>

                    <!-- Termos de Uso -->
                    <h6 class="fw-bold mb-3"><i class="bi bi-file-text me-1 text-primary"></i>Termos de Uso</h6>
                    <?php $aceitouTermos = !empty($currentUser['usu_aceite_termos']); ?>
                    <div class="alert <?= $aceitouTermos ? 'alert-success' : 'alert-warning' ?> d-flex align-items-center py-2" role="alert" style="font-size: 12px;">
                        <i class="bi <?= $aceitouTermos ? 'bi-patch-check-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
                        <div>
                            <?php if ($aceitouTermos): ?>
                                <?php
                                $dataAceiteMs = isset($currentUser['usu_data_aceite_termos']) ? (int)$currentUser['usu_data_aceite_termos'] : null;
                                echo $dataAceiteMs !== null && $dataAceiteMs > 0
                                    ? 'Aceitos em ' . date('d/m/Y \à\s H:i', (int)($dataAceiteMs / 1000)) . '.'
                                    : 'Termos de Uso já foram aceitos.';
                                ?>
                            <?php else: ?>
                                Você ainda não registrou o aceite dos Termos de Uso.
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="<?= APP_ROOT ?>pages/aceitar-termos.php" class="btn btn-outline-primary w-100 btn-sm">
                        <i class="bi bi-file-text me-1"></i> Ver Termos de Uso
                    </a>
                </div>

                <!-- Conexão de API e Ambiente -->
                <div class="card-custom">
                    <h5 class="fw-bold mb-3" style="color: var(--color-primary);"><i class="bi bi-cloud-connect me-2"></i>Status da API / Conexão</h5>
                    
                    <div class="mb-3" style="font-size: 13px;">
                        <span class="text-muted d-block mb-1">URL Base de Conexão:</span>
                        <code class="d-block p-3 bg-dark text-light rounded text-break" style="font-size: 11px;"><?= htmlspecialchars($configApiUrl) ?></code>
                    </div>

                    <div class="alert alert-info d-flex align-items-start m-0" role="alert" style="font-size: 12px;">
                        <i class="bi bi-info-circle-fill me-2 mt-1"></i>
                        <div>
                            Para alterar o apontamento da API (ex: migrar de Render (nuvem) para XAMPP (localhost)), acesse e modifique o arquivo de configuração <code>config/api.php</code> na raiz da aplicação.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form de Alteração de Senha -->
            <div class="col-lg-7">
                <div class="card-custom h-100">
                    <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-shield-lock me-2"></i>Alterar Minha Senha</h5>
                    
                    <form method="POST" action="configuracoes.php" novalidate>
                        <input type="hidden" name="acao" value="alterar_senha">
                        
                        <div class="mb-3">
                            <label class="form-label">Senha Atual *</label>
                            <input type="password" class="form-control" name="senha_atual" placeholder="Digite sua senha pessoal de acesso vigente" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nova Senha *</label>
                            <input type="password" class="form-control" name="nova_senha" placeholder="Digite a nova senha de acesso segura" required>
                            <small class="text-muted">A senha deve conter ao menos 6 caracteres, contendo pelo menos uma letra e um número.</small>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Confirmar Nova Senha *</label>
                            <input type="password" class="form-control" name="confirmar_senha" placeholder="Repita a nova senha de acesso" required>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Atualizar Minha Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
