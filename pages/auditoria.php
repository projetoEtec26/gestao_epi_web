<?php
declare(strict_types=1);

$page_title = 'Auditoria de Logs';
$active_menu = 'auditoria';
$page_roles = ['ADMINISTRADOR']; // Apenas Administradores do Sistema

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$logs = [];

// Trata filtros enviados via GET
$filtros = [];
$queryArray = [];
if (!empty($_GET['usuario'])) {
    $filtros['usuario'] = $_GET['usuario'];
    $queryArray[] = 'usuario=' . urlencode($_GET['usuario']);
}
if (!empty($_GET['data_inicio'])) {
    $filtros['data_inicio'] = $_GET['data_inicio'];
    $queryArray[] = 'data_inicio=' . urlencode($_GET['data_inicio']);
}
if (!empty($_GET['data_fim'])) {
    $filtros['data_fim'] = $_GET['data_fim'];
    $queryArray[] = 'data_fim=' . urlencode($_GET['data_fim']);
}
if (!empty($_GET['acao'])) {
    $filtros['acao'] = $_GET['acao'];
    $queryArray[] = 'acao=' . urlencode($_GET['acao']);
}
if (!empty($_GET['entidade'])) {
    $filtros['entidade'] = $_GET['entidade'];
    $queryArray[] = 'entidade=' . urlencode($_GET['entidade']);
}
if (!empty($_GET['funcionario'])) {
    $filtros['funcionario'] = $_GET['funcionario'];
    $queryArray[] = 'funcionario=' . urlencode($_GET['funcionario']);
}
if (!empty($_GET['palavra_chave'])) {
    $filtros['palavra_chave'] = $_GET['palavra_chave'];
    $queryArray[] = 'palavra_chave=' . urlencode($_GET['palavra_chave']);
}

$queryString = implode('&', $queryArray);
$endpoint = 'logs' . ($queryString !== '' ? '?' . $queryString : '');

try {
    $response = $api->get($endpoint);
    if (isset($response['success']) && $response['success']) {
        $logs = $response['data'];
    } else {
        $erro = $response['message'] ?? 'Falha ao recuperar logs de auditoria.';
    }
} catch (Exception $e) {
    $erro = 'Erro de conexão: ' . $e->getMessage();
}

// Filtro local adicional para funcionário, caso preenchido
if (!empty($_GET['funcionario']) && !empty($logs)) {
    $buscaFunc = strtolower($_GET['funcionario']);
    $logs = array_values(array_filter($logs, function($l) use ($buscaFunc) {
        $det = $l['log_detalhes'] ?? '';
        return strpos(strtolower($det), $buscaFunc) !== false || strpos(strtolower($l['usu_login'] ?? ''), $buscaFunc) !== false;
    }));
}

// Busca todos os operadores cadastrados para popular os filtros
$usuariosOperadores = [];
try {
    $usuRes = $api->get('usuarios');
    if (isset($usuRes['success']) && $usuRes['success']) {
        $usuariosOperadores = $usuRes['data'];
    }
} catch (\Throwable $e) {}

// Lógica de Paginação (Estilo Print 2)
$limite = isset($_GET['limite']) ? max(1, (int)$_GET['limite']) : 10;
$paginaAtual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;

$totalLogs = count($logs);
$totalPaginas = max(1, (int)ceil($totalLogs / $limite));
if ($paginaAtual > $totalPaginas) {
    $paginaAtual = $totalPaginas;
}

$offset = ($paginaAtual - 1) * $limite;
$logsPagina = array_slice($logs, $offset, $limite);

$inicioRegistro = $totalLogs > 0 ? $offset + 1 : 0;
$fimRegistro = min($offset + $limite, $totalLogs);

function buildAuditUrl(array $overrides = []): string {
    $params = array_merge($_GET, $overrides);
    return 'auditoria.php?' . http_build_query($params);
}
?>

<style>
.audit-container {
    max-width: 1100px;
    margin: 0 auto;
}

.audit-card-filter {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.audit-card-title {
    color: #2563eb;
    font-weight: 600;
    font-size: 1.05rem;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.audit-label {
    font-size: 12px;
    font-weight: 500;
    color: #64748b;
    margin-bottom: 4px;
}

.audit-select, .audit-input {
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    padding: 8px 12px;
}

.audit-btn-pdf {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #2563eb;
    font-weight: 600;
    font-size: 12px;
    border-radius: 8px;
    padding: 8px 16px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.2s;
}

.audit-btn-pdf:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
    border-color: #93c5fd;
}

.audit-btn-limpar {
    background-color: #ffffff;
    border: 1px solid #cbd5e1;
    color: #2563eb;
    font-weight: 600;
    font-size: 12px;
    border-radius: 8px;
    padding: 8px 20px;
    text-transform: uppercase;
    text-decoration: none;
    transition: all 0.2s;
}

.audit-btn-limpar:hover {
    background-color: #f8fafc;
    color: #1e40af;
}

.audit-btn-filtrar {
    background-color: #2563eb;
    border: none;
    color: #ffffff;
    font-weight: 600;
    font-size: 12px;
    border-radius: 8px;
    padding: 8px 24px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    transition: all 0.2s;
}

.audit-btn-filtrar:hover {
    background-color: #1d4ed8;
}

.audit-table-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

body.dark-mode .audit-card-filter,
body.dark-mode .audit-table-card {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}

body.dark-mode .audit-card-title {
    color: #60a5fa !important;
}

body.dark-mode .audit-label {
    color: #cbd5e1 !important;
}

body.dark-mode .audit-select,
body.dark-mode .audit-input {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
    color-scheme: dark !important;
}

body.dark-mode .audit-input::placeholder,
body.dark-mode .audit-select::placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
}

body.dark-mode .audit-input::-webkit-input-placeholder {
    color: #94a3b8 !important;
    opacity: 1 !important;
}

body.dark-mode .audit-btn-pdf,
body.dark-mode .audit-btn-limpar {
    background-color: #1e293b !important;
    color: #60a5fa !important;
    border-color: #334155 !important;
}
</style>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="audit-container">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="fw-bold m-0" style="color: var(--color-primary);">Auditoria de Logs</h3>
                    <p class="text-muted small m-0">Consulte e filtre os registros históricos de segurança, cadastros e conformidade legal.</p>
                </div>
            </div>

            <?php if ($erro !== null): ?>
                <div class="alert alert-danger d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div><?= htmlspecialchars($erro) ?></div>
                </div>
            <?php endif; ?>

            <!-- Bloco de Filtros de Auditoria (Estilo Print 2) -->
            <div class="audit-card-filter mb-4">
                <h6 class="audit-card-title">
                    <i class="bi bi-search fs-5"></i> Filtros de Auditoria
                </h6>
                <form method="GET" action="auditoria.php">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Usuário</label>
                            <select name="usuario" class="form-select audit-select">
                                <option value="">Todos</option>
                                <?php foreach ($usuariosOperadores as $op): ?>
                                    <option value="<?= htmlspecialchars($op['usu_login']) ?>" <?= ($_GET['usuario'] ?? '') === $op['usu_login'] ? 'selected' : '' ?>><?= htmlspecialchars($op['usu_login']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Ação</label>
                            <select name="acao" class="form-select audit-select">
                                <option value="">Todas</option>
                                <option value="LOGIN" <?= ($_GET['acao'] ?? '') === 'LOGIN' ? 'selected' : '' ?>>LOGIN</option>
                                <option value="CADASTRO" <?= ($_GET['acao'] ?? '') === 'CADASTRO' ? 'selected' : '' ?>>CADASTRO</option>
                                <option value="ALTERAÇÃO" <?= ($_GET['acao'] ?? '') === 'ALTERAÇÃO' ? 'selected' : '' ?>>ALTERAÇÃO</option>
                                <option value="EXCLUSÃO" <?= ($_GET['acao'] ?? '') === 'EXCLUSÃO' ? 'selected' : '' ?>>EXCLUSÃO</option>
                                <option value="DEVOLUÇÃO" <?= ($_GET['acao'] ?? '') === 'DEVOLUÇÃO' ? 'selected' : '' ?>>DEVOLUÇÃO</option>
                                <option value="BLOQUEIO" <?= ($_GET['acao'] ?? '') === 'BLOQUEIO' ? 'selected' : '' ?>>BLOQUEIO</option>
                                <option value="DESBLOQUEIO" <?= ($_GET['acao'] ?? '') === 'DESBLOQUEIO' ? 'selected' : '' ?>>DESBLOQUEIO</option>
                                <option value="EXPORTAÇÃO" <?= ($_GET['acao'] ?? '') === 'EXPORTAÇÃO' ? 'selected' : '' ?>>EXPORTAÇÃO</option>
                                <option value="GERACAO_RELATORIO" <?= ($_GET['acao'] ?? '') === 'GERACAO_RELATORIO' ? 'selected' : '' ?>>GERACAO_RELATORIO</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Data Inicial</label>
                            <input type="date" name="data_inicio" class="form-control audit-input" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Data Final</label>
                            <input type="date" name="data_fim" class="form-control audit-input" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Entidade (Módulo)</label>
                            <select name="entidade" class="form-select audit-select">
                                <option value="">Todas</option>
                                <option value="EPIs" <?= ($_GET['entidade'] ?? '') === 'EPIs' ? 'selected' : '' ?>>Catálogo de EPIs</option>
                                <option value="Funcionarios" <?= ($_GET['entidade'] ?? '') === 'Funcionarios' ? 'selected' : '' ?>>Funcionários</option>
                                <option value="Entrega_EPIs" <?= ($_GET['entidade'] ?? '') === 'Entrega_EPIs' ? 'selected' : '' ?>>Entregas Realizadas</option>
                                <option value="Itens_Entrega" <?= ($_GET['entidade'] ?? '') === 'Itens_Entrega' ? 'selected' : '' ?>>Itens e Posse</option>
                                <option value="Assinaturas_Eletronicas" <?= ($_GET['entidade'] ?? '') === 'Assinaturas_Eletronicas' ? 'selected' : '' ?>>Assinaturas PIN</option>
                                <option value="Usuarios" <?= ($_GET['entidade'] ?? '') === 'Usuarios' ? 'selected' : '' ?>>Usuários / Operadores</option>
                            </select>
                        </div>

                        <div class="col-md-6 col-lg-6">
                            <label class="audit-label">Funcionário</label>
                            <input type="text" name="funcionario" class="form-control audit-input" placeholder="Filtrar por nome do funcionário..." value="<?= htmlspecialchars($_GET['funcionario'] ?? '') ?>">
                        </div>

                        <div class="col-12">
                            <label class="audit-label">Palavra-chave</label>
                            <input type="text" name="palavra_chave" class="form-control audit-input" placeholder="Pesquisar por palavras contidas nas descrições de logs..." value="<?= htmlspecialchars($_GET['palavra_chave'] ?? '') ?>">
                        </div>
                    </div>
                </form>
            </div>

            <!-- Card de Ações e Tabela com Trilha de Logs -->
            <div class="audit-table-card">
                <!-- Barra de Botões (Print 2 layout) -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 border-bottom bg-light">
                    <a href="relatorio_auditoria_impressao.php<?= !empty($queryString) ? '?' . htmlspecialchars($queryString) : '' ?>" target="_blank" class="audit-btn-pdf">
                        <i class="bi bi-bar-chart-line-fill text-primary"></i> EXPORTAR PDF
                    </a>

                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <a href="auditoria.php" class="audit-btn-limpar">LIMPAR</a>
                        <button type="button" class="audit-btn-filtrar" onclick="document.querySelector('.audit-card-filter form').submit();">FILTRAR</button>
                    </div>
                </div>

                <div class="table-responsive-custom">
                    <table class="table-custom audit-table mb-0" id="tabela-auditoria">
                        <thead>
                            <tr>
                                <th>Data e hora <i class="bi bi-arrow-down-up opacity-50 ms-1"></i></th>
                                <th>Usuário <i class="bi bi-arrow-down-up opacity-50 ms-1"></i></th>
                                <th>Ação <i class="bi bi-arrow-down-up opacity-50 ms-1"></i></th>
                                <th>Tabela / Registro <i class="bi bi-arrow-down-up opacity-50 ms-1"></i></th>
                                <th>Descrição da Ocorrência</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logsPagina)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">Nenhum log correspondente aos filtros foi localizado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logsPagina as $log): ?>
                                    <?php
                                    $acaoClass = 'pendente'; // Fallback cinza
                                    $acao = $log['log_acao'];
                                    
                                    if ($acao === 'LOGIN' || $acao === 'CADASTRO' || $acao === 'DEVOLUÇÃO' || $acao === 'DESBLOQUEIO') {
                                        $acaoClass = 'ativo';
                                    } elseif ($acao === 'ALTERAÇÃO' || $acao === 'GERACAO_RELATORIO') {
                                        $acaoClass = 'a-vencer';
                                    } elseif ($acao === 'EXCLUSÃO' || $acao === 'BLOQUEIO') {
                                        $acaoClass = 'vencido';
                                    }
                                    
                                    // Converte UTC para America/Sao_Paulo
                                    $dataLog = (new DateTime($log['log_datahora'], new DateTimeZone('UTC')))
                                        ->setTimezone(new DateTimeZone('America/Sao_Paulo'))
                                        ->format('d/m/Y H:i:s');

                                    $ocorrencia = $log['log_ocorrencia'] ?? null;
                                    if (empty($ocorrencia)) {
                                        $detalhesJson = json_decode($log['log_detalhes'] ?? '', true);
                                        $ocorrencia = $detalhesJson['ocorrencia'] ?? 'Sem descrição.';
                                    }
                                    ?>
                                    <tr>
                                        <td><span class="text-muted fw-medium" style="font-size: 13px;"><?= $dataLog ?></span></td>
                                        <td>
                                            <div class="fw-semibold" style="font-size: 13px;"><?= htmlspecialchars($log['usu_login'] ?? 'Sistema/Automat.') ?></div>
                                            <div class="text-muted" style="font-size: 10px;">ID: #<?= $log['usu_id'] ?? '---' ?></div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $acaoClass ?> fw-bold"><?= htmlspecialchars($log['log_acao']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-medium" style="font-size: 13px;"><?= htmlspecialchars($log['log_tabela']) ?></div>
                                            <div class="text-muted" style="font-size: 11px;">ID Reg: <?= $log['log_registro_id'] ?? '---' ?></div>
                                        </td>
                                        <td>
                                            <div class="text-truncate text-muted" style="max-width: 320px; font-size: 13px;" title="<?= htmlspecialchars($ocorrencia) ?>">
                                                <?= htmlspecialchars($ocorrencia) ?>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-light border py-1 px-2" onclick="verDetalhesLog(<?= $log['log_id'] ?>)" title="Ver Detalhes do JSON">
                                                <i class="bi bi-braces me-1"></i> Detalhes
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Rodapé com Paginação (Print 2 layout) -->
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 p-3 bg-light border-top" style="font-size: 12px;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted">Exibir:</span>
                        <select class="form-select form-select-sm" style="width: auto; border-radius: 6px;" onchange="window.location.href=this.value">
                            <?php foreach ([10, 25, 50, 100] as $optLim): ?>
                                <option value="<?= buildAuditUrl(['limite' => $optLim, 'pagina' => 1]) ?>" <?= $limite === $optLim ? 'selected' : '' ?>><?= $optLim ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="text-muted">
                        Exibindo <?= $inicioRegistro ?>–<?= $fimRegistro ?> de <?= $totalLogs ?> registros
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <a href="<?= buildAuditUrl(['pagina' => 1]) ?>" class="btn btn-sm btn-light border p-1 <?= $paginaAtual <= 1 ? 'disabled' : '' ?>" title="Primeira página">
                            <i class="bi bi-chevron-double-left"></i>
                        </a>
                        <a href="<?= buildAuditUrl(['pagina' => max(1, $paginaAtual - 1)]) ?>" class="btn btn-sm btn-light border p-1 <?= $paginaAtual <= 1 ? 'disabled' : '' ?>" title="Página anterior">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                        <span class="px-2 fw-semibold text-primary"><?= $paginaAtual ?> / <?= $totalPaginas ?></span>
                        <a href="<?= buildAuditUrl(['pagina' => min($totalPaginas, $paginaAtual + 1)]) ?>" class="btn btn-sm btn-light border p-1 <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>" title="Próxima página">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                        <a href="<?= buildAuditUrl(['pagina' => $totalPaginas]) ?>" class="btn btn-sm btn-light border p-1 <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>" title="Última página">
                            <i class="bi bi-chevron-double-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalhes do Log (Estrutura JSON do Payload) -->
<div class="modal fade" id="modalDetalhesLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-braces me-2"></i>Payload Estruturado de Auditoria</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light">
                <div class="d-flex flex-column gap-3">
                    <div class="row g-2 border p-3 rounded bg-white" style="font-size: 13px;">
                        <div class="col-md-6"><strong>Log ID:</strong> <span id="det-log-id"></span></div>
                        <div class="col-md-6"><strong>Data/Hora:</strong> <span id="det-log-data"></span></div>
                        <div class="col-md-6"><strong>Responsável:</strong> <span id="det-log-resp"></span></div>
                        <div class="col-md-6"><strong>Módulo:</strong> <span id="det-log-tabela"></span></div>
                        <div class="col-12 border-top pt-2 mt-2"><strong>Ocorrência:</strong> <span id="det-log-ocorrencia"></span></div>
                    </div>
                    
                    <div>
                        <h6 class="fw-bold mb-2 text-secondary"><i class="bi bi-code-square me-1"></i>Metadados Completos do Evento (JSON)</h6>
                        <pre class="bg-dark text-light p-3 rounded" style="font-size: 11px; max-height: 380px; overflow-y: auto;" id="det-log-json"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= JAVASCRIPT ================= -->
<script>
const PROXY_URL = 'api_proxy.php';

/**
 * Consulta a API de logs via proxy server-side para buscar os detalhes estruturados
 */
function verDetalhesLog(logId) {
    document.getElementById('det-log-id').innerText = '...';
    document.getElementById('det-log-data').innerText = '...';
    document.getElementById('det-log-resp').innerText = '...';
    document.getElementById('det-log-tabela').innerText = '...';
    document.getElementById('det-log-ocorrencia').innerText = 'Carregando...';
    document.getElementById('det-log-json').innerText = 'Carregando metadados estruturados...';

    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesLog'));
    modal.show();

    fetch(`${PROXY_URL}?route=logs/${logId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const log = res.data;
                document.getElementById('det-log-id').innerText = log.log_id;
                document.getElementById('det-log-data').innerText = new Date(String(log.log_datahora).replace(' ', 'T') + 'Z').toLocaleString('pt-BR');
                document.getElementById('det-log-resp').innerText = log.usu_login || 'Sistema';
                document.getElementById('det-log-tabela').innerText = `${log.log_tabela} (ID Reg: ${log.log_registro_id || '---'})`;
                let detalheOcorrencia = (log.log_ocorrencia || '');
                if (!detalheOcorrencia) {
                    try {
                        const det = JSON.parse(log.log_detalhes || '{}');
                        detalheOcorrencia = det.ocorrencia || 'Sem descrição.';
                    } catch {
                        detalheOcorrencia = 'Sem descrição.';
                    }
                }
                document.getElementById('det-log-ocorrencia').innerText = detalheOcorrencia;
                
                try {
                    let parsed = JSON.parse(log.log_detalhes);
                    document.getElementById('det-log-json').innerText = JSON.stringify(parsed, null, 4);
                } catch {
                    document.getElementById('det-log-json').innerText = log.log_detalhes || 'Nenhum metadado extra registrado.';
                }
            } else {
                document.getElementById('det-log-ocorrencia').innerText = 'Erro ao buscar dados de logs.';
            }
        })
        .catch(() => {
            document.getElementById('det-log-ocorrencia').innerText = 'Erro de conexão na requisição.';
        });
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

