<?php
declare(strict_types=1);

$page_title = 'Histórico de Entregas';
$active_menu = 'entregas';
$page_roles = ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$entregas = [];

try {
    $response = $api->get('entregas');
    if (isset($response['success']) && $response['success']) {
        $entregas = $response['data'];
    } else {
        $erro = $response['message'] ?? 'Falha ao buscar o histórico de entregas.';
    }
} catch (Exception $e) {
    $erro = 'Erro de conexão: ' . $e->getMessage();
}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Histórico de Entregas</h3>
                <p class="text-muted mb-0">Consulte registros de fornecimento de EPIs homologados com termos de ciência e assinaturas validadas.</p>
            </div>
            <?php if (in_array($userProfile, ['ADMINISTRADOR', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true)): ?>
                <div>
                    <a href="nova_entrega.php" class="btn btn-primary shadow-sm">
                        <i class="bi bi-plus-circle-fill me-1"></i> Nova Entrega (Contingência)
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <!-- Listagem e Filtro -->
        <div class="card-custom">
            <div class="row g-3 mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="input-group">
                        <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="busca-input" class="form-control border-start-0" placeholder="Buscar por funcionário, EPI, motivo ou ID...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="filtro-motivo" class="form-select">
                        <option value="">Todos os Motivos</option>
                        <option value="ADMISSAO">Admissão</option>
                        <option value="SUBSTITUICAO">Substituição</option>
                        <option value="VENCIMENTO">Vencimento Vida Útil</option>
                        <option value="PERDA">Perda</option>
                        <option value="DANO">Dano</option>
                        <option value="TROCA_FUNCAO">Troca de Função</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtro-status" class="form-select">
                        <option value="">Todos os Status</option>
                        <option value="FINALIZADA">Finalizada</option>
                        <option value="CANCELADA">Cancelada</option>
                    </select>
                </div>
            </div>

            <!-- Tabela -->
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-entregas">
                    <thead>
                        <tr>
                            <th>Cód / Data</th>
                            <th>Colaborador</th>
                            <th>EPIs Fornecidos</th>
                            <th>Qtd Itens</th>
                            <th>Motivo</th>
                            <th>Assinatura</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($entregas)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Nenhum registro de entrega de EPI localizado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($entregas as $entr): ?>
                                <?php
                                $statusClass = strtolower($entr['entr_status']);
                                $validacaoSenha = $entr['entr_validacao_senha'] ?? 'PENDENTE';
                                $validacaoClass = ($validacaoSenha === 'VALIDADA') ? 'ativo' : 'inativo';
                                
                                // Detalhamento rápido dos EPIs para busca e exibição textual
                                $itensNomes = [];
                                $totalItens = 0;
                                foreach ($entr['itens'] as $item) {
                                    $itensNomes[] = ($item['item_epi_nome_snapshot'] ?? 'EPI') . " (Lote: " . ($item['item_numero_lote'] ?? 'N/A') . ")";
                                    $totalItens += (int)$item['item_quantidade'];
                                }
                                $itensString = implode(', ', $itensNomes);
                                ?>
                                <tr class="entrega-row" 
                                    data-id="<?= htmlspecialchars((string)$entr['entr_id']) ?>"
                                    data-nome="<?= htmlspecialchars(strtolower($entr['fun_nome'] ?? '')) ?>"
                                    data-epis="<?= htmlspecialchars(strtolower($itensString)) ?>"
                                    data-motivo="<?= htmlspecialchars($entr['entr_motivo']) ?>"
                                    data-status="<?= htmlspecialchars($entr['entr_status']) ?>">
                                    
                                    <td>
                                        <div class="fw-bold">#<?= $entr['entr_id'] ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= date('d/m/Y H:i', strtotime($entr['entr_data_entrega'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($entr['fun_nome'] ?? '---') ?></div>
                                        <div class="text-muted" style="font-size: 11px;"><?= htmlspecialchars($entr['fun_departamento'] ?? 'Setor') ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 320px; font-size: 13px;" title="<?= htmlspecialchars($itensString) ?>">
                                            <?= htmlspecialchars($itensString) ?>
                                        </div>
                                    </td>
                                    <td class="fw-medium text-center"><?= $totalItens ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($entr['entr_motivo']) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $validacaoClass ?>"><i class="bi bi-fingerprint"></i> <?= htmlspecialchars($validacaoSenha) ?></span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($entr['entr_status']) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border py-1 px-2" onclick="exibirTermo(<?= htmlspecialchars(json_encode($entr)) ?>)" title="Ver Recibo de Assinatura">
                                            <i class="bi bi-file-earmark-text"></i> Termo
                                        </button>
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

<!-- Modal Termo de Ciência e Assinatura Eletrônica -->
<div class="modal fade" id="modalTermo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-lock2 me-2"></i>Recibo de Entrega Eletrônica de EPI</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body bg-light" id="area-impressao-termo">
                <div class="card p-4 border shadow-sm bg-white" style="font-size: 13px; line-height: 1.6;">
                    
                    <!-- Cabeçalho Termo -->
                    <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-4">
                        <div>
                            <h5 class="fw-bold m-0" style="color: var(--color-primary);">Ficha de Entrega de EPI nº <span id="termo-id"></span></h5>
                            <span class="text-muted" style="font-size: 11px;">Emitido em: <span id="termo-data"></span></span>
                        </div>
                        <div class="text-end" style="font-size: 11px;">
                            <span class="status-badge ativo"><i class="bi bi-check-circle-fill"></i> ASSINADO DIGITALMENTE</span>
                        </div>
                    </div>

                    <!-- Dados do Funcionário -->
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-person-fill me-1"></i>Dados do Receptor</h6>
                    <div class="row g-2 mb-4 border p-3 rounded bg-light">
                        <div class="col-md-6"><strong>Colaborador:</strong> <span id="termo-nome"></span></div>
                        <div class="col-md-6"><strong>CPF:</strong> <span id="termo-cpf"></span></div>
                        <div class="col-md-6"><strong>Cargo:</strong> <span id="termo-cargo"></span></div>
                        <div class="col-md-6"><strong>Setor:</strong> <span id="termo-setor"></span></div>
                    </div>

                    <!-- Tabela de Itens Recebidos -->
                    <h6 class="fw-bold mb-3 text-secondary"><i class="bi bi-box-seam-fill me-1"></i>Equipamentos Fornecidos</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>EPI / Classificação</th>
                                    <th>Fabricante</th>
                                    <th>C.A.</th>
                                    <th>Validade C.A.</th>
                                    <th>Qtd</th>
                                    <th>Tamanho</th>
                                    <th>Lote</th>
                                </tr>
                            </thead>
                            <tbody id="termo-tabela-itens">
                                <!-- Dinâmico -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Texto do Termo -->
                    <h6 class="fw-bold mb-2 text-secondary"><i class="bi bi-journal-text me-1"></i>Declaração de Ciência e Compromisso</h6>
                    <div class="border p-3 rounded mb-4 text-muted bg-light" style="max-height: 180px; overflow-y: auto; text-align: justify; font-size: 12px;" id="termo-texto">
                        <!-- Texto do Snapshot -->
                    </div>

                    <!-- Validação da Assinatura -->
                    <h6 class="fw-bold mb-2 text-secondary"><i class="bi bi-fingerprint me-1"></i>Dados da Assinatura Eletrônica (Auditoria Judicial)</h6>
                    <div class="border p-3 rounded bg-light" style="font-family: monospace; font-size: 11px;">
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Método de Validação:</span>
                            <span class="fw-semibold">PIN Eletrônico (Senha Pessoal)</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Status da Validação:</span>
                            <span class="fw-semibold text-success">SENHA VALIDADA NO SERVIDOR</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Assinado por (Usuário Responsável):</span>
                            <span class="fw-semibold" id="termo-responsavel"></span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted">Data/Hora do Aceite Eletrônico:</span>
                            <span class="fw-semibold" id="termo-data-aceite"></span>
                        </div>
                        <div class="py-1">
                            <span class="text-muted d-block mb-1">Hash da Assinatura de Integridade (SHA-256):</span>
                            <span class="fw-bold text-color-primary text-break" id="termo-hash"></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-outline-primary" onclick="imprimirTermo()"><i class="bi bi-printer me-1"></i> Imprimir Termo</button>
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
 * Filtros em tempo real no front-end
 */
function initBuscaEFiltros() {
    const busca = document.getElementById('busca-input');
    const filtroMotivo = document.getElementById('filtro-motivo');
    const filtroStatus = document.getElementById('filtro-status');
    const rows = document.querySelectorAll('.entrega-row');
    
    function aplicarFiltros() {
        const query = busca.value.toLowerCase().trim();
        const motivo = filtroMotivo.value;
        const status = filtroStatus.value;
        
        rows.forEach(row => {
            const rId = row.getAttribute('data-id');
            const rNome = row.getAttribute('data-nome');
            const rEpis = row.getAttribute('data-epis');
            const rMotivo = row.getAttribute('data-motivo');
            const rStatus = row.getAttribute('data-status');
            
            const bateBusca = rId.includes(query) || rNome.includes(query) || rEpis.includes(query);
            const bateMotivo = (motivo === '' || rMotivo === motivo);
            const bateStatus = (status === '' || rStatus === status);
            
            if (bateBusca && bateMotivo && bateStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }
    
    busca.addEventListener('input', aplicarFiltros);
    filtroMotivo.addEventListener('change', aplicarFiltros);
    filtroStatus.addEventListener('change', aplicarFiltros);
}

/**
 * Preenche o modal de recibo com os metadados e o texto do termo assinado
 */
function exibirTermo(entr) {
    document.getElementById('termo-id').innerText = entr.entr_id;
    document.getElementById('termo-data').innerText = new Date(entr.entr_data_entrega).toLocaleString('pt-BR');
    document.getElementById('termo-nome').innerText = entr.fun_nome || '---';
    
    // CPF mascarado para auditoria se não for administrador
    let cpf = entr.fun_cpf || '';
    if (cpf.length === 11) {
        cpf = `***.***.***-${cpf.slice(-2)}`;
    }
    document.getElementById('termo-cpf').innerText = cpf;
    document.getElementById('termo-cargo').innerText = entr.fun_cargo || '---';
    document.getElementById('termo-setor').innerText = entr.fun_departamento || '---';
    
    // Tabela de itens
    let htmlItens = '';
    entr.itens.forEach(item => {
        let vencCa = '---';
        if (item.item_epi_validade_ca_snapshot) {
            const parts = item.item_epi_validade_ca_snapshot.split('-');
            vencCa = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : item.item_epi_validade_ca_snapshot;
        }
        
        htmlItens += `
            <tr>
                <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                <td>${item.item_epi_fabricante_snapshot || '---'}</td>
                <td>${item.item_epi_ca_snapshot || '---'}</td>
                <td>${vencCa}</td>
                <td>${item.item_quantidade}</td>
                <td>${item.item_tamanho || '---'}</td>
                <td>${item.item_numero_lote || '---'}</td>
            </tr>
        `;
    });
    document.getElementById('termo-tabela-itens').innerHTML = htmlItens;
    
    // Texto do termo (Se não houver snapshot, coloca texto padrão)
    const textoTermo = entr.texto_termo_snapshot || `Declaro estar recebendo, gratuitamente e sem qualquer ônus, os Equipamentos de Proteção Individual — EPIs discriminados neste termo. Declaro estar ciente da obrigatoriedade de sua utilização durante a execução das atividades para as quais foram fornecidos, conforme as orientações da empresa e a legislação aplicável.`;
    document.getElementById('termo-texto').innerText = textoTermo;
    
    // Dados de auditoria
    document.getElementById('termo-responsavel').innerText = `${entr.usu_login} (Perfil: ${entr.usu_perfil || 'Operador'})`;
    document.getElementById('termo-data-aceite').innerText = new Date(entr.data_hora_aceite || entr.entr_data_entrega).toLocaleString('pt-BR');
    document.getElementById('termo-hash').innerText = entr.entr_hash_assinatura;

    new bootstrap.Modal(document.getElementById('modalTermo')).show();
}

/**
 * Lógica de impressão do termo gerando uma janela estilizada
 */
function imprimirTermo() {
    const area = document.getElementById('area-impressao-termo').innerHTML;
    const janela = window.open('', '_blank', 'width=800,height=600');
    
    janela.document.write('<html><head><title>Imprimir Termo de EPI</title>');
    // Copia o estilo do Bootstrap para a página de impressão
    janela.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">');
    janela.document.write('<style>body { font-family: sans-serif; padding: 20px; }</style>');
    janela.document.write('</head><body>');
    janela.document.write(area);
    janela.document.write('</body></html>');
    
    janela.document.close();
    janela.focus();
    
    // Aguarda o carregamento rápido do css do bootstrap e dispara a impressão
    setTimeout(() => {
        janela.print();
        janela.close();
    }, 500);
}

function executarAcaoSubmenuEntrega(acao) {
    if (acao === 'nova_entrega' || acao === 'nova') {
        window.location.href = '<?= APP_ROOT ?>pages/nova_entrega.php';
    } else if (acao === 'devolucao' || acao === 'devolucoes') {
        window.location.href = '<?= APP_ROOT ?>pages/devolucoes.php';
    } else {
        if (window.location.pathname.indexOf('entregas.php') !== -1) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            window.location.href = '<?= APP_ROOT ?>pages/entregas.php';
        }
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
