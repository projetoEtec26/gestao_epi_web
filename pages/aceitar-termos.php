<?php
declare(strict_types=1);

$page_title = 'Termos de Uso';
$active_menu = 'configuracoes';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;

$usuarioId = (int)($currentUser['usu_id'] ?? 0);
if ($usuarioId <= 0) {
    $_SESSION['error_message'] = 'Sessão inválida. Faça login novamente.';
    header('Location: ' . APP_ROOT . 'logout.php');
    exit;
}

// Fluxo de aceite enviado pelo formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'aceitar_termos') {
    try {
        // Epoch em milissegundos, mesmo formato consumido pela API e pelo aplicativo Android
        $dataAceiteMs = (int)round(microtime(true) * 1000);
        $response = $api->post("usuarios/{$usuarioId}/aceitar-termos", [
            'aceite' => true,
            'data_aceite' => $dataAceiteMs
        ]);

        if (isset($response['success']) && $response['success']) {
            // Atualiza a sessão local para refletir o aceite imediatamente
            $_SESSION['usuario']['usu_aceite_termos'] = true;
            $_SESSION['usuario']['usu_data_aceite_termos'] = $dataAceiteMs;

            $_SESSION['success_message'] = 'Termos de Uso aceitos com sucesso!';
            header('Location: configuracoes.php');
            exit;
        }

        $erro = $response['message'] ?? 'Não foi possível registrar o aceite dos Termos de Uso.';
    } catch (Exception $e) {
        $erro = 'Erro de conexão ao registrar o aceite: ' . $e->getMessage();
    }
}

// Consulta o status atualizado do aceite diretamente no servidor (fonte da verdade)
$jaAceitou = (bool)($_SESSION['usuario']['usu_aceite_termos'] ?? false);
$dataAceiteMs = isset($_SESSION['usuario']['usu_data_aceite_termos']) ? (int)$_SESSION['usuario']['usu_data_aceite_termos'] : null;

try {
    $meRes = $api->get('auth/me');
    if (isset($meRes['success']) && $meRes['success'] && is_array($meRes['data'])) {
        if (isset($meRes['data']['usu_aceite_termos'])) {
            $jaAceitou = (bool)$meRes['data']['usu_aceite_termos'];
            $_SESSION['usuario']['usu_aceite_termos'] = $jaAceitou;
        }
        if (array_key_exists('usu_data_aceite_termos', $meRes['data'])) {
            $dataAceiteMs = $meRes['data']['usu_data_aceite_termos'] !== null ? (int)$meRes['data']['usu_data_aceite_termos'] : null;
            $_SESSION['usuario']['usu_data_aceite_termos'] = $dataAceiteMs;
        }

        // Auto-reparo: servidor tem flag de aceite mas sem data — reenvia o registro
        if ($jaAceitou && !$dataAceiteMs) {
            $dataAceiteMs = (int)round(microtime(true) * 1000);
            $api->post("usuarios/{$usuarioId}/aceitar-termos", [
                'aceite' => true,
                'data_aceite' => $dataAceiteMs
            ]);
            $_SESSION['usuario']['usu_data_aceite_termos'] = $dataAceiteMs;
        }
    }
} catch (\Throwable $e) {}

$dataAceiteLabel = null;
if ($jaAceitou && $dataAceiteMs !== null && $dataAceiteMs > 0) {
    $dataAceiteLabel = date('d/m/Y \à\s H:i', (int)($dataAceiteMs / 1000));
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>

    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Termos de Uso</h3>
                <p class="text-muted">Condições gerais de utilização da plataforma Gestão_EPI.</p>
            </div>
            <a href="configuracoes.php" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Voltar</a>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($jaAceitou): ?>
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <div>
                    <strong>Termos de Uso aceitos<?= $dataAceiteLabel ? " em {$dataAceiteLabel}" : '. Termos de Uso já foram aceitos.' ?></strong>
                </div>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Texto Integral dos Termos -->
            <div class="col-lg-8">
                <div class="card-custom">
                    <h5 class="fw-bold mb-4" style="color: var(--color-primary);"><i class="bi bi-file-text me-2"></i>Gestão_EPI — Termos de Uso</h5>

                    <div style="font-size: 14px;" class="d-flex flex-column gap-4 text-muted">
                        <div>
                            <h6 class="fw-bold text-dark mb-2">1. Bem-vindo ao Gestão_EPI</h6>
                            <p>Bem-vindo ao sistema Gestão_EPI. Ao utilizar esta plataforma, você concorda com os termos e condições descritos abaixo.</p>
                            <p>O sistema permite o gerenciamento de informações relacionadas a funcionários, EPIs, entregas, devoluções, assinatura eletrônica por senha/PIN, relatórios, dashboard e operações associadas ao controle de Equipamentos de Proteção Individual.</p>
                        </div>

                        <div>
                            <h6 class="fw-bold text-dark mb-2">2. Uso do Sistema</h6>
                            <ul class="mb-0">
                                <li>Registrar corretamente a entrega de EPIs;</li>
                                <li>Manter os dados atualizados;</li>
                                <li>Garantir a veracidade das informações registradas;</li>
                                <li>Utilizar a assinatura eletrônica apenas pelo responsável;</li>
                                <li>Não compartilhar senhas, PINs ou credenciais de acesso;</li>
                                <li>Gerar relatórios conforme necessidade operacional, gerencial ou de auditoria;</li>
                                <li>Utilizar o sistema somente para finalidades de controle de EPIs e segurança do trabalho.</li>
                            </ul>
                        </div>

                        <div>
                            <h6 class="fw-bold text-dark mb-2">3. Responsabilidade do Usuário</h6>
                            <p class="mb-0">O usuário é responsável pelas informações registradas no sistema, devendo utilizar suas credenciais de forma individual, segura e intransferível. Qualquer uso indevido poderá ser registrado em log de auditoria.</p>
                        </div>

                        <div>
                            <h6 class="fw-bold text-dark mb-2">4. Assinatura Eletrônica</h6>
                            <p class="mb-0">A confirmação de recebimento de EPIs é realizada por meio de assinatura eletrônica com senha/PIN individual. O QR Code presente no crachá do colaborador destina-se apenas à identificação rápida.</p>
                        </div>

                        <div>
                            <h6 class="fw-bold text-dark mb-2">5. Auditoria e Rastreabilidade</h6>
                            <p class="mb-0">O sistema registra logs de acesso, alterações, entregas, devoluções e tentativas de validação, garantindo rastreabilidade completa das operações realizadas.</p>
                        </div>

                        <div>
                            <h6 class="fw-bold text-dark mb-2">6. Alterações nos Termos</h6>
                            <p class="mb-0">Estes Termos de Uso podem ser atualizados conforme a evolução do sistema, requisitos legais ou necessidades operacionais.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Painel de Aceite -->
            <div class="col-lg-4">
                <div class="card-custom">
                    <h5 class="fw-bold mb-3" style="color: var(--color-primary);"><i class="bi bi-pen me-2"></i>Registro de Aceite</h5>

                    <?php if ($jaAceitou): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-patch-check-fill text-success" style="font-size: 48px;"></i>
                            <h6 class="fw-bold mt-3 mb-1">Termos de Uso aceitos</h6>
                            <p class="text-muted mb-0" style="font-size: 13px;">
                                <?= $dataAceiteLabel ? "Aceito em {$dataAceiteLabel}." : 'Termos de Uso já foram aceitos.' ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="aceitar-termos.php" novalidate>
                            <input type="hidden" name="acao" value="aceitar_termos">

                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="chk-aceite" required>
                                <label class="form-check-label" for="chk-aceite" style="font-size: 13px;">
                                    Declaro que li e aceito os Termos de Uso.
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary w-100" id="btn-aceitar" disabled>
                                <i class="bi bi-check-lg me-1"></i> Aceitar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chk = document.getElementById('chk-aceite');
    const btn = document.getElementById('btn-aceitar');
    if (chk && btn) {
        chk.addEventListener('change', function() {
            btn.disabled = !chk.checked;
        });
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
