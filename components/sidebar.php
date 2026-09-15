<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$user = $_SESSION['usuario'] ?? null;
$perfil = $user['usu_perfil'] ?? '';
$active = $active_menu ?? '';

if (!defined('APP_ROOT')) {
    $config = require __DIR__ . '/../config/api.php';
    define('APP_ROOT', $config['app_root_url'] ?? '/gestao_epi_web_2/');
}

// Função auxiliar para verificar permissão do menu
if (!function_exists('hasPermission')) {
    function hasPermission(string $menuName, string $perfil): bool {
        $permissions = [
            'dashboard'   => ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'],
            'funcionarios'=> ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'],
            'epis'        => ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'],
            'entregas'    => ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'],
            'relatorios'  => ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'GESTOR'],
            'usuarios'    => ['ADMINISTRADOR'],
            'auditoria'   => ['ADMINISTRADOR'],
            'pendencias'  => ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'],
            'configuracoes'=> ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR']
        ];

        return in_array($perfil, $permissions[$menuName] ?? [], true);
    }
}
?>
<div id="sidebar">
    <div class="brand">
        <i class="bi bi-shield-check"></i>
        <span>Gestão de EPI</span>
        <small style="display:block;font-size:10px;opacity:.6;margin-top:-2px;">Web UI Dashborativo</small>
    </div>
    
    <ul class="nav-menu">
        <?php if (hasPermission('dashboard', $perfil)): ?>
            <li class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>">
                <a href="<?= APP_ROOT ?>pages/dashboard.php">
                    <span class="icon-box"><i class="bi bi-house-door<?= $active === 'dashboard' ? '-fill' : '' ?>"></i></span>
                    <span>Dashboard</span>
                </a>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('funcionarios', $perfil)): ?>
            <li class="nav-item has-submenu <?= $active === 'funcionarios' ? 'active open' : '' ?>" id="menu-item-funcionarios">
                <div class="nav-item-submenu-header" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                    <a href="<?= APP_ROOT ?>pages/funcionarios.php" onclick="onFuncionariosMenuClick(event)" style="flex-grow:1; border:none; background:transparent;">
                        <span class="icon-box"><i class="bi bi-people"></i></span>
                        <span>Funcionários</span>
                    </a>
                    <button type="button" class="submenu-toggle-btn" onclick="toggleSubmenu(event, 'sub-func')" title="Expandir submenus" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:6px 10px; font-size:15px; border-radius:6px; transition:all 0.2s ease;">
                        <i class="bi <?= $active === 'funcionarios' ? 'bi-dash-lg' : 'bi-plus-lg' ?>" id="icon-sub-func"></i>
                    </button>
                </div>
                <ul class="sidebar-submenu-list <?= $active === 'funcionarios' ? 'open' : '' ?>" id="sub-func" style="list-style:none; padding-left:28px; margin:4px 0 8px 0; display:<?= $active === 'funcionarios' ? 'block' : 'none' ?>;">
                    <li>
                        <a href="<?= APP_ROOT ?>pages/funcionarios.php?acao=lista" onclick="onSubmenuItemClick(event, 'lista')" class="<?= isset($_GET['acao']) && $_GET['acao'] === 'lista' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-person-lines-fill me-1" style="font-size:14px;"></i>
                            <span>Lista Funcionários</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/funcionarios.php?acao=pin" onclick="onSubmenuItemClick(event, 'pin')" class="<?= isset($_GET['acao']) && $_GET['acao'] === 'pin' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-shield-lock-fill me-1" style="font-size:14px;"></i>
                            <span>Senha / PIN</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/funcionarios.php?acao=pendencias" onclick="onSubmenuItemClick(event, 'pendencias')" class="<?= isset($_GET['acao']) && $_GET['acao'] === 'pendencias' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-exclamation-triangle-fill me-1" style="font-size:14px;"></i>
                            <span>Pendências</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/funcionarios.php?acao=novo" onclick="onSubmenuItemClick(event, 'novo')" class="<?= isset($_GET['acao']) && $_GET['acao'] === 'novo' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-person-plus-fill me-1" style="font-size:14px;"></i>
                            <span>Novo Funcionário</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('epis', $perfil)): ?>
            <li class="nav-item has-submenu <?= $active === 'epis' ? 'active open' : '' ?>" id="menu-item-epis">
                <div class="nav-item-submenu-header" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                    <a href="<?= APP_ROOT ?>pages/epis.php" onclick="onEpisMenuClick(event)" style="flex-grow:1; border:none; background:transparent;">
                        <span class="icon-box">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="helmet-icon">
                                <path d="M2 18a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1H3a1 1 0 0 0-1 1v2z"/>
                                <path d="M10 10V5a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v5"/>
                                <path d="M4 15v-3a8 8 0 0 1 16 0v3"/>
                            </svg>
                        </span>
                        <span>EPIs (Controle C.A.)</span>
                    </a>
                    <button type="button" class="submenu-toggle-btn" onclick="toggleSubmenu(event, 'sub-epis')" title="Expandir submenus" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:6px 10px; font-size:15px; border-radius:6px; transition:all 0.2s ease;">
                        <i class="bi <?= $active === 'epis' ? 'bi-dash-lg' : 'bi-plus-lg' ?>" id="icon-sub-epis"></i>
                    </button>
                </div>
                <ul class="sidebar-submenu-list <?= $active === 'epis' ? 'open' : '' ?>" id="sub-epis" style="list-style:none; padding-left:28px; margin:4px 0 8px 0; display:<?= $active === 'epis' ? 'block' : 'none' ?>;">
                    <li>
                        <a href="<?= APP_ROOT ?>pages/epis.php?acao=lista" onclick="onSubmenuItemEpiClick(event, 'lista')" class="<?= (isset($_GET['acao']) && $_GET['acao'] === 'lista') || (!isset($_GET['acao']) && $active === 'epis') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-journal-text me-1" style="font-size:14px;"></i>
                            <span>Lista de EPIs</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/epis.php?acao=controle_ca" onclick="onSubmenuItemEpiClick(event, 'controle_ca')" class="<?= isset($_GET['acao']) && ($_GET['acao'] === 'controle_ca' || $_GET['acao'] === 'ca') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-exclamation-triangle-fill me-1" style="font-size:14px;"></i>
                            <span>Controle C.A.</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/epis.php?acao=historico_precos" onclick="onSubmenuItemEpiClick(event, 'historico_precos')" class="<?= isset($_GET['acao']) && ($_GET['acao'] === 'historico_precos' || $_GET['acao'] === 'precos') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-clock-history me-1" style="font-size:14px;"></i>
                            <span>Hist. de Preços</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/epis.php?acao=novo" onclick="onSubmenuItemEpiClick(event, 'novo')" class="<?= isset($_GET['acao']) && $_GET['acao'] === 'novo' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-plus-lg me-1" style="font-size:14px;"></i>
                            <span>Novo EPI</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('entregas', $perfil)): ?>
            <li class="nav-item has-submenu <?= in_array($active, ['entregas', 'devolucoes', 'nova_entrega'], true) ? 'active open' : '' ?>" id="menu-item-entregas">
                <div class="nav-item-submenu-header" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                    <a href="<?= APP_ROOT ?>pages/nova_entrega.php" onclick="onEntregasMenuClick(event)" style="flex-grow:1; border:none; background:transparent;">
                        <span class="icon-box"><i class="bi bi-truck"></i></span>
                        <span>Entregas & Devoluções</span>
                    </a>
                    <button type="button" class="submenu-toggle-btn" onclick="toggleSubmenu(event, 'sub-entregas')" title="Expandir submenus" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:6px 10px; font-size:15px; border-radius:6px; transition:all 0.2s ease;">
                        <i class="bi <?= in_array($active, ['entregas', 'devolucoes', 'nova_entrega'], true) ? 'bi-dash-lg' : 'bi-plus-lg' ?>" id="icon-sub-entregas"></i>
                    </button>
                </div>
                <ul class="sidebar-submenu-list <?= in_array($active, ['entregas', 'devolucoes', 'nova_entrega'], true) ? 'open' : '' ?>" id="sub-entregas" style="list-style:none; padding-left:28px; margin:4px 0 8px 0; display:<?= in_array($active, ['entregas', 'devolucoes', 'nova_entrega'], true) ? 'block' : 'none' ?>;">
                    <li>
                        <a href="<?= APP_ROOT ?>pages/entregas.php" onclick="onSubmenuItemEntregaClick(event, 'historico')" class="<?= $active === 'entregas' && (!isset($_GET['acao']) || $_GET['acao'] === 'historico') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-clock-history me-1" style="font-size:14px;"></i>
                            <span>Histórico</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/devolucoes.php" onclick="onSubmenuItemEntregaClick(event, 'devolucao')" class="<?= $active === 'devolucoes' || (isset($_GET['acao']) && $_GET['acao'] === 'devolucao') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-arrow-counterclockwise me-1" style="font-size:14px;"></i>
                            <span>Devolução</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/nova_entrega.php" onclick="onSubmenuItemEntregaClick(event, 'nova_entrega')" class="<?= $active === 'nova_entrega' || (isset($_GET['acao']) && $_GET['acao'] === 'nova_entrega') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-plus-lg me-1" style="font-size:14px;"></i>
                            <span>Nova Entrega</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('relatorios', $perfil)): ?>
            <li class="nav-item has-submenu <?= in_array($active, ['relatorios', 'relatorio_geral', 'relatorio_financeiro', 'relatorio_consumo_epi', 'relatorio_validade_ca', 'relatorio_auditoria_logs', 'relatorio_auditoria_impressao'], true) ? 'active open' : '' ?>" id="menu-item-relatorios">
                <div class="nav-item-submenu-header" style="display:flex; align-items:center; justify-content:space-between; width:100%;">
                    <a href="<?= APP_ROOT ?>pages/relatorios.php?tipo=geral" onclick="onRelatoriosMenuClick(event)" style="flex-grow:1; border:none; background:transparent;">
                        <span class="icon-box"><i class="bi bi-file-earmark-bar-graph"></i></span>
                        <span>Relatórios</span>
                    </a>
                    <button type="button" class="submenu-toggle-btn" onclick="toggleSubmenu(event, 'sub-relatorios')" title="Expandir submenus" style="background:transparent; border:none; cursor:pointer; color:#94a3b8; padding:6px 10px; font-size:15px; border-radius:6px; transition:all 0.2s ease;">
                        <i class="bi <?= in_array($active, ['relatorios', 'relatorio_geral', 'relatorio_financeiro', 'relatorio_consumo_epi', 'relatorio_validade_ca', 'relatorio_auditoria_logs', 'relatorio_auditoria_impressao'], true) ? 'bi-dash-lg' : 'bi-plus-lg' ?>" id="icon-sub-relatorios"></i>
                    </button>
                </div>
                <ul class="sidebar-submenu-list <?= in_array($active, ['relatorios', 'relatorio_geral', 'relatorio_financeiro', 'relatorio_consumo_epi', 'relatorio_validade_ca', 'relatorio_auditoria_logs', 'relatorio_auditoria_impressao'], true) ? 'open' : '' ?>" id="sub-relatorios" style="list-style:none; padding-left:28px; margin:4px 0 8px 0; display:<?= in_array($active, ['relatorios', 'relatorio_geral', 'relatorio_financeiro', 'relatorio_consumo_epi', 'relatorio_validade_ca', 'relatorio_auditoria_logs', 'relatorio_auditoria_impressao'], true) ? 'block' : 'none' ?>;">
                    <li>
                        <a href="<?= APP_ROOT ?>pages/relatorios.php?tipo=geral" onclick="onSubmenuItemRelatorioClick(event, 'geral')" class="<?= (isset($_GET['tipo']) && $_GET['tipo'] === 'geral') || (!isset($_GET['tipo']) && $active === 'relatorios') ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-clipboard-data me-1" style="font-size:14px;"></i>
                            <span>Rel. Geral EPIs</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/relatorios.php?tipo=financeiro" onclick="onSubmenuItemRelatorioClick(event, 'financeiro')" class="<?= isset($_GET['tipo']) && $_GET['tipo'] === 'financeiro' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-currency-dollar me-1" style="font-size:14px;"></i>
                            <span>Rel. Financeiro</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/relatorios.php?tipo=epi" onclick="onSubmenuItemRelatorioClick(event, 'epi')" class="<?= isset($_GET['tipo']) && $_GET['tipo'] === 'epi' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-shield-check me-1" style="font-size:14px;"></i>
                            <span>Rel. EPI</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?= APP_ROOT ?>pages/relatorios.php?tipo=funcionario" onclick="onSubmenuItemRelatorioClick(event, 'funcionario')" class="<?= isset($_GET['tipo']) && $_GET['tipo'] === 'funcionario' ? 'active-sub' : '' ?>" style="display:flex; align-items:center; gap:8px; padding:6px 10px; font-size:13px; color:#94a3b8; text-decoration:none; border-radius:6px;">
                            <i class="bi bi-person-badge me-1" style="font-size:14px;"></i>
                            <span>Rel. Funcionário</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('usuarios', $perfil)): ?>
            <li class="nav-item <?= $active === 'usuarios' ? 'active' : '' ?>">
                <a href="<?= APP_ROOT ?>pages/usuarios.php">
                    <span class="icon-box"><i class="bi bi-person-gear"></i></span>
                    <span>Usuários e Permissões</span>
                </a>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('auditoria', $perfil)): ?>
            <li class="nav-item <?= $active === 'auditoria' ? 'active' : '' ?>">
                <a href="<?= APP_ROOT ?>pages/auditoria.php">
                    <span class="icon-box"><i class="bi bi-file-earmark-text"></i></span>
                    <span>Auditoria de Logs</span>
                </a>
            </li>
        <?php endif; ?>

        <?php if (hasPermission('pendencias', $perfil)): ?>
            <li class="nav-item <?= $active === 'pendencias' ? 'active' : '' ?>">
                <a href="<?= APP_ROOT ?>pages/entregas.php?status=pendente">
                    <span class="icon-box"><i class="bi bi-file-earmark-check"></i></span>
                    <span>Pendências de Envio</span>
                </a>
            </li>
        <?php endif; ?>
        
        <?php if (hasPermission('configuracoes', $perfil)): ?>
            <li class="nav-item <?= $active === 'configuracoes' ? 'active' : '' ?>">
                <a href="<?= APP_ROOT ?>pages/configuracoes.php">
                    <span class="icon-box"><i class="bi bi-gear"></i></span>
                    <span>Configurações</span>
                </a>
            </li>
        <?php endif; ?>
        
        <li class="nav-item mt-auto logout-item">
            <a href="<?= APP_ROOT ?>logout.php">
                <span class="icon-box"><i class="bi bi-power"></i></span>
                <span>Sair</span>
            </a>
        </li>
    </ul>
</div>

<script>
function toggleSubmenu(e, targetId) {
    if (e) {
        e.preventDefault();
        e.stopPropagation();
    }
    const list = document.getElementById(targetId);
    const icon = document.getElementById('icon-' + targetId);
    const parentLi = list ? list.closest('.has-submenu') : null;
    
    if (list) {
        const isHidden = (window.getComputedStyle(list).display === 'none');
        if (isHidden) {
            list.style.display = 'block';
            list.classList.add('open');
            if (parentLi) parentLi.classList.add('open');
            if (icon) icon.className = 'bi bi-dash-lg';
        } else {
            list.style.display = 'none';
            list.classList.remove('open');
            if (parentLi) parentLi.classList.remove('open');
            if (icon) icon.className = 'bi bi-plus-lg';
        }
    }
}

function onFuncionariosMenuClick(e) {
    toggleSubmenu(null, 'sub-func');
    if (window.location.pathname.indexOf('funcionarios.php') !== -1 || window.location.search.indexOf('route=funcionarios') !== -1) {
        if (e) e.preventDefault();
        if (typeof executarAcaoSubmenu === 'function') {
            executarAcaoSubmenu('lista');
        }
    }
}

function onSubmenuItemClick(e, acao) {
    if (window.location.pathname.indexOf('funcionarios.php') !== -1 || window.location.search.indexOf('route=funcionarios') !== -1) {
        if (e) e.preventDefault();
        if (typeof executarAcaoSubmenu === 'function') {
            executarAcaoSubmenu(acao);
        } else if (typeof executarAcaoSpeedDial === 'function') {
            executarAcaoSpeedDial(acao);
        }
    }
}

function onEpisMenuClick(e) {
    toggleSubmenu(null, 'sub-epis');
    if (window.location.pathname.indexOf('epis.php') !== -1) {
        if (e) e.preventDefault();
        if (typeof executarAcaoSubmenuEpi === 'function') {
            executarAcaoSubmenuEpi('catalogo');
        } else if (typeof alternarVisao === 'function') {
            alternarVisao('catalogo');
        }
    }
}

function onSubmenuItemEpiClick(e, acao) {
    if (window.location.pathname.indexOf('epis.php') !== -1) {
        if (e) e.preventDefault();
        if (typeof executarAcaoSubmenuEpi === 'function') {
            executarAcaoSubmenuEpi(acao);
        }
    }
}

function onEntregasMenuClick(e) {
    toggleSubmenu(null, 'sub-entregas');
    if (window.location.pathname.indexOf('nova_entrega.php') !== -1) {
        if (e) e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        if (e) e.preventDefault();
        window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
    }
}

function onSubmenuItemEntregaClick(e, acao) {
    if (typeof executarAcaoSubmenuEntrega === 'function') {
        if (e) e.preventDefault();
        executarAcaoSubmenuEntrega(acao);
    } else {
        if (e) e.preventDefault();
        if (acao === 'historico') {
            window.location.href = '<?= APP_ROOT ?>pages/entregas.php';
        } else if (acao === 'devolucao' || acao === 'devolucoes') {
            window.location.href = '<?= APP_ROOT ?>pages/devolucoes.php';
        } else if (acao === 'nova_entrega' || acao === 'nova') {
            window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
        }
    }
}

function onRelatoriosMenuClick(e) {
    const list = document.getElementById('sub-relatorios');
    const icon = document.getElementById('icon-sub-relatorios');
    const parentLi = document.getElementById('menu-item-relatorios');
    if (list) {
        list.style.display = 'block';
        list.classList.add('open');
        if (parentLi) parentLi.classList.add('open');
        if (icon) icon.className = 'bi bi-dash-lg';
    }

    if (window.location.pathname.indexOf('relatorios.php') !== -1) {
        if (e) e.preventDefault();
        if (typeof mostrarPainelRelatorio === 'function') {
            const btn = document.querySelector('#lista-tipos-relatorios button[data-tipo="geral"], #lista-tipos-relatorios button[data-painel="geral"]');
            mostrarPainelRelatorio('geral', btn);
        }
    }
}

function onSubmenuItemRelatorioClick(e, tipo) {
    const mapaTipoPainel = {
        'financeiro': 'custos',
        'epi': 'epis-vencidos',
        'funcionario': 'entregas',
        'geral': 'geral'
    };

    if (window.location.pathname.indexOf('relatorios.php') !== -1) {
        if (e) e.preventDefault();
        const painelAlvo = mapaTipoPainel[tipo] || tipo;
        if (typeof mostrarPainelRelatorio === 'function') {
            const btn = document.querySelector(`#lista-tipos-relatorios button[data-tipo="${tipo}"], #lista-tipos-relatorios button[data-painel="${painelAlvo}"]`);
            mostrarPainelRelatorio(painelAlvo, btn);
        } else {
            window.location.href = '<?= APP_ROOT ?>pages/relatorios.php?tipo=' + tipo;
        }
        document.querySelectorAll('#sub-relatorios a').forEach(a => a.classList.remove('active-sub'));
        if (e && e.currentTarget) {
            e.currentTarget.classList.add('active-sub');
        }
    }
}
</script>


