<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$user = $_SESSION['usuario'] ?? null;
$login = $user['usu_login'] ?? 'Usuário';
$perfilLabel = '';

if ($user !== null) {
    $perfisMap = [
        'ADMINISTRADOR' => 'Administrador',
        'RH_ADMINISTRATIVO' => 'RH Administrativo',
        'TECNICO_SST' => 'Técnico SST',
        'ALMOXARIFE_OPERADOR' => 'Almoxarife',
        'GESTOR' => 'Gestor'
    ];
    $perfilLabel = $perfisMap[$user['usu_perfil']] ?? $user['usu_perfil'];
}
?>
<header id="topbar">
    <div class="left-section">
        <button id="sidebar-toggle-btn" class="toggle-sidebar-btn" title="Alternar barra lateral">
            <i class="bi bi-list"></i>
        </button>
        <h5 class="m-0 font-weight-semibold d-none d-sm-block text-muted">Gestão de EPIs Corporativos</h5>
    </div>
    
    <div class="right-section">
        <!-- Alternar Tema (Light/Dark) -->
        <button id="theme-toggle-btn" class="theme-switch" title="Alternar Modo Escuro">
            <i class="bi bi-moon-stars"></i>
        </button>
        
        <!-- Dropdown do Usuário -->
        <div class="dropdown">
            <a class="d-flex align-items-center text-decoration-none dropdown-toggle text-dark" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="d-flex flex-column text-end me-2">
                    <span class="fw-semibold text-color-primary" style="font-size: 14px; color: var(--color-text-primary);"><?= htmlspecialchars($login) ?></span>
                    <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($perfilLabel) ?></span>
                </div>
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600; font-size: 14px;">
                    <?= strtoupper(substr($login, 0, 2)) ?>
                </div>
            </a>
            
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg mt-2" aria-labelledby="userDropdown" style="border-radius: 12px; background-color: var(--color-card-bg);">
                <li>
                    <a class="dropdown-item py-2" href="<?= APP_ROOT ?>pages/configuracoes.php" style="color: var(--color-text-primary);">
                        <i class="bi bi-person me-2"></i> Meu Perfil
                    </a>
                </li>
                <li><hr class="dropdown-divider" style="border-color: var(--color-border);"></li>
                <li>
                    <a class="dropdown-item py-2 text-danger" href="<?= APP_ROOT ?>logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i> Sair
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>
