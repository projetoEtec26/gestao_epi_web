// Javascript Principal - Gestão EPI Web

document.addEventListener('DOMContentLoaded', function() {
    initDarkMode();
    initSidebarToggle();
    initInputsMasks();
    initNumberSpinners();
    initDatePickers();

    // Notifica automaticamente o Dashboard sempre que qualquer alteração/sucesso for realizada no sistema
    if (document.querySelector('.alert-success')) {
        window.notificarAtualizacaoSistema();
    }
});

/**
 * Suporte global para abrir o modal de calendário Material Design (mês, dia, ano - conforme Print 2) ao clicar em qualquer campo de data ou seu ícone
 */
function initDatePickers() {
    function triggerPicker(e) {
        const input = e.target.closest('input[type="date"], input[type="datetime-local"], .mask-date');
        if (!input || input.disabled) return;

        // Evita reabrir se já houver um modal ativo
        if (document.querySelector('.md-picker-backdrop')) return;

        e.preventDefault();
        e.stopPropagation();
        if (typeof input.blur === 'function') input.blur();
        openMaterialDatePickerModal(input);
    }

    document.addEventListener('mousedown', function(e) {
        const input = e.target.closest('input[type="date"], input[type="datetime-local"], .mask-date');
        if (input && !input.disabled) {
            triggerPicker(e);
        }
    }, true);

    document.addEventListener('click', function(e) {
        const input = e.target.closest('input[type="date"], input[type="datetime-local"], .mask-date');
        if (input && !input.disabled) {
            triggerPicker(e);
        }
    }, true);

    document.addEventListener('touchend', function(e) {
        const input = e.target.closest('input[type="date"], input[type="datetime-local"], .mask-date');
        if (input && !input.disabled) {
            triggerPicker(e);
        }
    }, { capture: true, passive: false });
}

/**
 * Abre o Modal de Calendário Estilo Material Design (Print 2) para seleção interativa de data
 */
function openMaterialDatePickerModal(inputElement) {
    const existing = document.querySelector('.md-picker-backdrop');
    if (existing) existing.remove();

    let initialDate = new Date();
    if (inputElement.value && inputElement.value.trim() !== '') {
        const val = inputElement.value.trim();
        if (val.includes('-')) {
            const parts = val.split('-');
            if (parts.length === 3) {
                const y = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const d = parseInt(parts[2], 10);
                if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                    initialDate = new Date(y, m, d);
                }
            }
        } else if (val.includes('/')) {
            const parts = val.split('/');
            if (parts.length === 3) {
                const d = parseInt(parts[0], 10);
                const m = parseInt(parts[1], 10) - 1;
                const y = parseInt(parts[2], 10);
                if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                    initialDate = new Date(y, m, d);
                }
            }
        }
    }

    let selectedDate = new Date(initialDate.getFullYear(), initialDate.getMonth(), initialDate.getDate());
    let viewDate = new Date(initialDate.getFullYear(), initialDate.getMonth(), 1);
    let isSelectingYear = false;

    const weekdaysShort = ['Dom.', 'Seg.', 'Ter.', 'Qua.', 'Qui.', 'Sex.', 'Sáb.'];
    const weekdaysHeaders = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'];
    const monthsShort = ['jan.', 'fev.', 'mar.', 'abr.', 'mai.', 'jun.', 'jul.', 'ago.', 'set.', 'out.', 'nov.', 'dez.'];
    const monthsLong = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];

    const backdrop = document.createElement('div');
    backdrop.className = 'md-picker-backdrop';

    backdrop.innerHTML = `
        <div class="md-picker-modal" role="dialog" aria-modal="true">
            <div class="md-picker-header">
                <div class="md-picker-header-year" id="md-picker-year-display">${selectedDate.getFullYear()}</div>
                <div class="md-picker-header-date" id="md-picker-date-display"></div>
            </div>
            <div class="md-picker-body" id="md-picker-body-content">
                <div class="md-picker-nav">
                    <button type="button" class="md-picker-nav-btn" id="md-picker-prev-month" aria-label="Mês Anterior">&lt;</button>
                    <div class="md-picker-month-title" id="md-picker-month-title"></div>
                    <button type="button" class="md-picker-nav-btn" id="md-picker-next-month" aria-label="Próximo Mês">&gt;</button>
                </div>
                <div class="md-picker-weekdays">
                    ${weekdaysHeaders.map(h => `<div class="md-picker-weekday">${h}</div>`).join('')}
                </div>
                <div class="md-picker-days" id="md-picker-days-grid"></div>
            </div>
            <div class="md-picker-footer">
                <button type="button" class="md-picker-btn md-picker-btn-cancel" id="md-picker-btn-cancel">CANCELAR</button>
                <button type="button" class="md-picker-btn md-picker-btn-ok" id="md-picker-btn-ok">OK</button>
            </div>
        </div>
    `;

    document.body.appendChild(backdrop);

    const yearDisplay = backdrop.querySelector('#md-picker-year-display');
    const dateDisplay = backdrop.querySelector('#md-picker-date-display');
    const bodyContent = backdrop.querySelector('#md-picker-body-content');
    const cancelBtn = backdrop.querySelector('#md-picker-btn-cancel');
    const okBtn = backdrop.querySelector('#md-picker-btn-ok');

    function updateHeader() {
        yearDisplay.textContent = selectedDate.getFullYear();
        const dayName = weekdaysShort[selectedDate.getDay()];
        const dayNum = selectedDate.getDate();
        const monthName = monthsShort[selectedDate.getMonth()];
        dateDisplay.textContent = `${dayName}, ${dayNum} de ${monthName}`;
    }

    function renderDaysView() {
        const monthTitle = backdrop.querySelector('#md-picker-month-title');
        const daysGrid = backdrop.querySelector('#md-picker-days-grid');
        if (!monthTitle || !daysGrid) return;

        monthTitle.textContent = `${monthsLong[viewDate.getMonth()]} de ${viewDate.getFullYear()}`;
        
        const firstDayIdx = new Date(viewDate.getFullYear(), viewDate.getMonth(), 1).getDay();
        const daysInMonth = new Date(viewDate.getFullYear(), viewDate.getMonth() + 1, 0).getDate();
        const today = new Date();

        let gridHtml = '';
        for (let i = 0; i < firstDayIdx; i++) {
            gridHtml += `<div class="md-picker-day-cell empty"></div>`;
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const isSelected = (
                selectedDate.getFullYear() === viewDate.getFullYear() &&
                selectedDate.getMonth() === viewDate.getMonth() &&
                selectedDate.getDate() === d
            );
            const isToday = (
                today.getFullYear() === viewDate.getFullYear() &&
                today.getMonth() === viewDate.getMonth() &&
                today.getDate() === d
            );

            let classes = 'md-picker-day-cell';
            if (isSelected) classes += ' selected';
            else if (isToday) classes += ' today';

            gridHtml += `<div class="${classes}" data-day="${d}">${d}</div>`;
        }

        daysGrid.innerHTML = gridHtml;

        daysGrid.querySelectorAll('.md-picker-day-cell[data-day]').forEach(cell => {
            cell.addEventListener('click', function(e) {
                e.stopPropagation();
                const d = parseInt(cell.getAttribute('data-day'), 10);
                selectedDate = new Date(viewDate.getFullYear(), viewDate.getMonth(), d);
                updateHeader();
                renderDaysView();
            });
        });
    }

    function setupNavEvents() {
        const prevBtn = backdrop.querySelector('#md-picker-prev-month');
        const nextBtn = backdrop.querySelector('#md-picker-next-month');
        if (prevBtn) {
            prevBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() - 1);
                renderDaysView();
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                viewDate.setMonth(viewDate.getMonth() + 1);
                renderDaysView();
            });
        }
    }

    function renderYearView() {
        let yearsHtml = '<div class="md-picker-year-list">';
        for (let y = 1940; y <= 2060; y++) {
            const isSelected = (y === selectedDate.getFullYear());
            yearsHtml += `<div class="md-picker-year-item ${isSelected ? 'selected' : ''}" data-year="${y}">${y}</div>`;
        }
        yearsHtml += '</div>';
        
        bodyContent.innerHTML = yearsHtml;

        const yearListEl = bodyContent.querySelector('.md-picker-year-list');
        const selectedYearEl = yearListEl.querySelector('.md-picker-year-item.selected');
        if (selectedYearEl) {
            selectedYearEl.scrollIntoView({ block: 'center' });
        }

        yearListEl.querySelectorAll('.md-picker-year-item').forEach(item => {
            item.addEventListener('click', function() {
                const chosenYear = parseInt(item.getAttribute('data-year'), 10);
                viewDate.setFullYear(chosenYear);
                selectedDate.setFullYear(chosenYear);
                isSelectingYear = false;
                
                restoreDaysLayout();
            });
        });
    }

    function restoreDaysLayout() {
        bodyContent.innerHTML = `
            <div class="md-picker-nav">
                <button type="button" class="md-picker-nav-btn" id="md-picker-prev-month" aria-label="Mês Anterior">&lt;</button>
                <div class="md-picker-month-title" id="md-picker-month-title"></div>
                <button type="button" class="md-picker-nav-btn" id="md-picker-next-month" aria-label="Próximo Mês">&gt;</button>
            </div>
            <div class="md-picker-weekdays">
                ${weekdaysHeaders.map(h => `<div class="md-picker-weekday">${h}</div>`).join('')}
            </div>
            <div class="md-picker-days" id="md-picker-days-grid"></div>
        `;
        setupNavEvents();
        updateHeader();
        renderDaysView();
    }

    setupNavEvents();

    yearDisplay.addEventListener('click', function(e) {
        e.stopPropagation();
        isSelectingYear = !isSelectingYear;
        if (isSelectingYear) {
            renderYearView();
        } else {
            restoreDaysLayout();
        }
    });

    cancelBtn.addEventListener('click', function() {
        backdrop.remove();
    });

    backdrop.addEventListener('click', function(e) {
        if (e.target === backdrop) {
            backdrop.remove();
        }
    });

    okBtn.addEventListener('click', function() {
        const yyyy = selectedDate.getFullYear();
        const mm = String(selectedDate.getMonth() + 1).padStart(2, '0');
        const dd = String(selectedDate.getDate()).padStart(2, '0');
        
        if (inputElement.type === 'date' || inputElement.type === 'datetime-local') {
            inputElement.value = `${yyyy}-${mm}-${dd}`;
        } else {
            inputElement.value = `${dd}/${mm}/${yyyy}`;
        }
        
        inputElement.dispatchEvent(new Event('input', { bubbles: true }));
        inputElement.dispatchEvent(new Event('change', { bubbles: true }));
        
        backdrop.remove();
    });

    updateHeader();
    renderDaysView();
}

/**
 * Suporte global de clique para incrementar/decrementar campos do tipo número (setas ▲ e ▼)
 */
function initNumberSpinners() {
    document.addEventListener('click', function(e) {
        const input = e.target.closest('input[type="number"]');
        if (!input || input.disabled || input.readOnly) return;

        const rect = input.getBoundingClientRect();
        const clickX = e.clientX - rect.left;
        const clickY = e.clientY - rect.top;

        // Se o clique for no lado direito do campo (na área das setas ▲▼)
        if (clickX > rect.width - 28) {
            if (clickY < rect.height / 2) {
                try { input.stepUp(); } catch(err) { input.value = (parseFloat(input.value) || 0) + 1; }
            } else {
                try { input.stepDown(); } catch(err) { input.value = Math.max((parseFloat(input.value) || 0) - 1, 0); }
            }
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
    });
}

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
        if (typeof window.atualizarCoresGraficoModoEscuro === 'function') {
            window.atualizarCoresGraficoModoEscuro();
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
 * Controla o recolhimento e ativação da barra lateral (Sidebar) em Desktop e Mobile
 */
function initSidebarToggle() {
    const sidebarToggle = document.getElementById('sidebar-toggle-btn');
    const sidebar = document.getElementById('sidebar');
    let overlay = document.getElementById('sidebar-overlay');

    // Garante a existência do overlay no DOM
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'sidebar-overlay';
        overlay.className = 'sidebar-overlay';
        overlay.setAttribute('aria-hidden', 'true');
        document.body.appendChild(overlay);
    }

    function openMobileSidebar() {
        document.body.classList.add('sidebar-active');
        if (sidebar) sidebar.classList.add('open');
        if (overlay) overlay.classList.add('active');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'true');
        }
    }

    function closeMobileSidebar() {
        document.body.classList.remove('sidebar-active');
        if (sidebar) sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('active');
        if (sidebarToggle) {
            sidebarToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function toggleMobileSidebar() {
        const isOpen = document.body.classList.contains('sidebar-active') || (sidebar && sidebar.classList.contains('open'));
        if (isOpen) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    }

    // Expõe as funções globalmente no window para chamadas pelos submenus
    window.openMobileSidebar = openMobileSidebar;
    window.closeMobileSidebar = closeMobileSidebar;
    window.toggleMobileSidebar = toggleMobileSidebar;

    // Evento de clique no botão hambúrguer
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (window.innerWidth > 991) {
                // Desktop: alterna o modo colapsado
                document.body.classList.toggle('sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('sidebar-collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed ? 'true' : 'false');
            } else {
                // Mobile: alterna abertura da sidebar off-canvas
                toggleMobileSidebar();
            }
        });
    }

    // Clicar no overlay semitransparente fecha a sidebar no mobile
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (window.innerWidth <= 991) {
                closeMobileSidebar();
            }
        });
    }

    // Fechar ao pressionar a tecla ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' || e.key === 'Esc') {
            if (window.innerWidth <= 991 && (document.body.classList.contains('sidebar-active') || (sidebar && sidebar.classList.contains('open')))) {
                closeMobileSidebar();
            }
        }
    });

    // Fechar a sidebar mobile automaticamente ao clicar apenas em opções finais/links diretos do menu
    if (sidebar) {
        sidebar.querySelectorAll('.sidebar-submenu-list a, .nav-item:not(.has-submenu) a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    closeMobileSidebar();
                }
            });
        });
    }

    // Restaura ou limpa estados em mudanças de tamanho de tela (Resize)
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            closeMobileSidebar();
            const isCollapsedSaved = localStorage.getItem('sidebar-collapsed');
            if (isCollapsedSaved === 'true') {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    });

    // No Mobile (largura <= 991px), a 1ª tela que deve aparecer após o login / entrada no app é o menu aberto (Print em vermelho)
    if (window.innerWidth <= 991) {
        openMobileSidebar();
    } else {
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

