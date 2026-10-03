// Javascript Principal - Gestão EPI Web

document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initSidebarToggle();
    initInputsMasks();

    // Notifica automaticamente o Dashboard sempre que qualquer alteração/sucesso for realizada no sistema
    if (document.querySelector('.alert-success')) {
        window.notificarAtualizacaoSistema();
    }
});

/**
 * Inicializa e gerencia a preferência do Modo Escuro (Dark Mode)
 */
function initDarkMode() {
    const html = document.documentElement;
    const body = document.body;
    const themeBtn = document.getElementById('theme-toggle-btn');
    const themeIcon = themeBtn ? themeBtn.querySelector('i') : null;
    const selectTema = document.getElementById('select-tema-app');
    
    // Recupera a preferência salva no localStorage ou Cookie
    const cookieMatch = document.cookie.match(/theme-mode=([^;]+)/);
    const savedTheme = localStorage.getItem('theme-mode') || (cookieMatch ? cookieMatch[1] : 'light');
    
    function applyTheme(theme) {
        if (theme === 'dark') {
            html.classList.add('dark-mode');
            html.style.backgroundColor = '#0f172a';
            html.style.color = '#f8fafc';
            if (body) body.classList.add('dark-mode');
            localStorage.setItem('theme-mode', 'dark');
            document.cookie = 'theme-mode=dark; path=/; max-age=31536000; SameSite=Lax';
            if (themeIcon) themeIcon.className = 'bi bi-sun';
            if (selectTema) selectTema.value = 'dark';
        } else {
            html.classList.remove('dark-mode');
            html.style.backgroundColor = '';
            html.style.color = '';
            if (body) body.classList.remove('dark-mode');
            localStorage.setItem('theme-mode', 'light');
            document.cookie = 'theme-mode=light; path=/; max-age=31536000; SameSite=Lax';
            if (themeIcon) themeIcon.className = 'bi bi-moon-stars';
            if (selectTema) selectTema.value = 'light';
        }
    }

    // Aplica o tema sincronizado
    applyTheme(savedTheme);
    
    // Evento de clique no botão do topbar
    if (themeBtn) {
        themeBtn.addEventListener('click', function() {
            const isDark = html.classList.contains('dark-mode') || (body && body.classList.contains('dark-mode'));
            applyTheme(isDark ? 'light' : 'dark');
        });
    }
}

/**
 * Controla o recolhimento e ativação da barra lateral (Sidebar)
 */
function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebar-toggle-btn');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth > 991) {
                // Desktop: colapsa
                document.body.classList.toggle('sidebar-collapsed');
                // Salva estado
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            } else {
                // Mobile: ativa
                document.body.classList.toggle('sidebar-active');
            }
        });
    }
    
    // Fecha a sidebar mobile se clicar fora ou no conteúdo principal
    const mainContent = document.getElementById('main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function() {
            if (window.innerWidth <= 991 && document.body.classList.contains('sidebar-active')) {
                document.body.classList.remove('sidebar-active');
            }
        });
    }
    
    // Carrega estado da sidebar desktop salva
    if (window.innerWidth > 991) {
        const isCollapsedSaved = localStorage.getItem('sidebar-collapsed');
        if (isCollapsedSaved === 'true') {
            document.body.classList.add('sidebar-collapsed');
        } else {
            document.body.classList.remove('sidebar-collapsed');
        }
    }
}

/**
 * Lógica para máscaras de campos como CPF e Datas
 */
function initInputsMasks() {
    // Máscara de CPF (apenas números, insere pontuação)
    const cpfInputs = document.querySelectorAll('.mask-cpf');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            
            if (value.length > 9) {
                value = value.replace(/^(\d{3})(\d{3})(\d{3})(\d{1,2})$/, "$1.$2.$3-$4");
            } else if (value.length > 6) {
                value = value.replace(/^(\d{3})(\d{3})(\d{1,3})$/, "$1.$2.$3");
            } else if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d{1,3})$/, "$1.$2");
            }
            
            e.target.value = value;
        });
    });

    // Máscara de Data (dd/mm/aaaa)
    const dateInputs = document.querySelectorAll('.mask-date');
    dateInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 8) value = value.slice(0, 8);
            
            if (value.length > 4) {
                value = value.replace(/^(\d{2})(\d{2})(\d{1,4})$/, "$1/$2/$3");
            } else if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d{1,2})$/, "$1/$2");
            }
            
            e.target.value = value;
        });
    });
    
    // Máscara de Valor Monetário (R$ 1.234,56)
    const moneyInputs = document.querySelectorAll('.mask-money');
    moneyInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            // Formata centavos
            let valFloat = parseFloat(value) / 100;
            if (isNaN(valFloat)) {
                e.target.value = '';
                return;
            }
            
            e.target.value = valFloat.toLocaleString('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            });
        });
    });
}

/**
 * Helper para formatar CPF na UI
 */
function formatCPF(cpf) {
    const clean = cpf.replace(/\D/g, '');
    if (clean.length !== 11) return cpf;
    return clean.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
}

/**
 * Helper para formatar data BR para SQL
 */
function dateBrToSql(dateBr) {
    const parts = dateBr.split('/');
    if (parts.length !== 3) return null;
    return `${parts[2]}-${parts[1]}-${parts[0]}`;
}

/**
 * Notifica outras abas e a página do Dashboard em tempo real sobre entregas, devoluções ou cadastros alterados
 */
window.notificarAtualizacaoSistema = function() {
    if ('BroadcastChannel' in window) {
        try {
            new BroadcastChannel('gestao_epi_realtime').postMessage({ type: 'UPDATE_DASHBOARD' });
        } catch (e) {}
    }
    try {
        localStorage.setItem('gestao_epi_last_update', Date.now().toString());
    } catch (e) {}
};

