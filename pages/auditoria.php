<?php
declare(strict_types=1);

$page_title = 'Auditoria do Sistema';
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

// Busca todos os operadores cadastrados para popular o filtro de usuários
$usuariosOperadores = [];
try {
    $usuRes = $api->get('usuarios');
    if (isset($usuRes['success']) && $usuRes['success']) {
        $usuariosOperadores = $usuRes['data'];
    }
} catch (\Throwable $e) {}
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
                <h3 class="fw-bold m-0" style="color: var(--color-primary);">Trilha de Auditoria</h3>
                <p class="text-muted">Consulte e filtre os registros históricos de segurança, cadastros e conformidade legal.</p>
            </div>
        </div>

        <?php if ($erro !== null): ?>
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?= htmlspecialchars($erro) ?></div>
            </div>
        <?php endif; ?>

        <!-- Bloco de Filtros Avançados -->
        <div class="card-custom mb-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-funnel me-1"></i> Filtros de Auditoria</h6>
            <form method="GET" action="auditoria.php">
                <div class="row g-3">
                    <div class="col-md-3 col-lg-2">
                        <label class="form-label text-muted" style="font-size: 12px; font-weight: 500;">Operador</label>
                        <select name="usuario" class="form-select">
                            <option value="">Todos</option>
                            <?php foreach ($usuariosOperadores as $op): ?>
                                <option value="<?= htmlspecialchars($op['usu_login']) ?>" <?= ($_GET['usuario'] ?? '') === $op['usu_login'] ? 'selected' : '' ?>><?= htmlspecialchars($op['usu_login']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label text-muted" style="font-size: 12px; font-weight: 500;">Ação</label>
                        <select name="acao" class="form-select">
                            <option value="">Todas</option>
                            <option value="LOGIN" <?= ($_GET['acao'] ?? '') === 'LOGIN' ? 'selected' : '' ?>>Login</option>
                            <option value="CADASTRO" <?= ($_GET['acao'] ?? '') === 'CADASTRO' ? 'selected' : '' ?>>Cadastro</option>
                            <option value="ALTERAÇÃO" <?= ($_GET['acao'] ?? '') === 'ALTERAÇÃO' ? 'selected' : '' ?>>Alteração</option>
                            <option value="EXCLUSÃO" <?= ($_GET['acao'] ?? '') === 'EXCLUSÃO' ? 'selected' : '' ?>>Exclusão</option>
                            <option value="DEVOLUÇÃO" <?= ($_GET['acao'] ?? '') === 'DEVOLUÇÃO' ? 'selected' : '' ?>>Devolução</option>
                            <option value="BLOQUEIO" <?= ($_GET['acao'] ?? '') === 'BLOQUEIO' ? 'selected' : '' ?>>Bloqueio PIN</option>
                            <option value="DESBLOQUEIO" <?= ($_GET['acao'] ?? '') === 'DESBLOQUEIO' ? 'selected' : '' ?>>Desbloqueio PIN</option>
                            <option value="EXPORTAÇÃO" <?= ($_GET['acao'] ?? '') === 'EXPORTAÇÃO' ? 'selected' : '' ?>>Exportação</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label text-muted" style="font-size: 12px; font-weight: 500;">Módulo (Tabela)</label>
                        <select name="entidade" class="form-select">
                            <option value="">Todos</option>
                            <option value="EPIs" <?= ($_GET['entidade'] ?? '') === 'EPIs' ? 'selected' : '' ?>>Catálogo de EPIs</option>
                            <option value="Funcionarios" <?= ($_GET['entidade'] ?? '') === 'Funcionarios' ? 'selected' : '' ?>>Funcionários</option>
                            <option value="Entrega_EPIs" <?= ($_GET['entidade'] ?? '') === 'Entrega_EPIs' ? 'selected' : '' ?>>Entregas Realizadas</option>
                            <option value="Itens_Entrega" <?= ($_GET['entidade'] ?? '') === 'Itens_Entrega' ? 'selected' : '' ?>>Itens e Posse</option>
                            <option value="Assinaturas_Eletronicas" <?= ($_GET['entidade'] ?? '') === 'Assinaturas_Eletronicas' ? 'selected' : '' ?>>Assinaturas PIN</option>
                            <option value="Usuarios" <?= ($_GET['entidade'] ?? '') === 'Usuarios' ? 'selected' : '' ?>>Usuários / Operadores</option>
                        </select>
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label text-muted" style="font-size: 12px; font-weight: 500;">Data Início</label>
                        <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($_GET['data_inicio'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 col-lg-2">
                        <label class="form-label text-muted" style="font-size: 12px; font-weight: 500;">Data Fim</label>
                        <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($_GET['data_fim'] ?? '') ?>">
                    </div>

                    <div class="col-md-3 col-lg-2 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
                        <a href="auditoria.php" class="btn btn-light border w-100">Limpar</a>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <input type="text" name="palavra_chave" class="form-control" placeholder="Pesquisar por palavras contidas nas descrições de logs..." value="<?= htmlspecialchars($_GET['palavra_chave'] ?? '') ?>">
                    </div>
                </div>
            </form>
        </div>

        <!-- Trilha de Logs -->
        <div class="card-custom">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold m-0 text-muted">Exibindo <?= count($logs) ?> registros de auditoria</h6>
                <div class="d-flex gap-2">
                    <a href="relatorio_auditoria_impressao.php<?= !empty($queryString) ? '?' . htmlspecialchars($queryString) : '' ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-printer me-1"></i> Imprimir Relatório (v2)
                    </a>
                </div>
            </div>
            
            <div class="table-responsive-custom">
                <table class="table-custom" id="tabela-auditoria">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Operador</th>
                            <th>Ação</th>
                            <th>Tabela / Registro</th>
                            <th>Descrição da Ocorrência</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Nenhum log correspondente aos filtros foi localizado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <?php
                                $acaoClass = 'pendente'; // Fallback cinza
                                $acao = $log['log_acao'];
                                
                                if ($acao === 'LOGIN') $acaoClass = 'ativo';
                                elseif ($acao === 'CADASTRO') $acaoClass = 'ativo';
                                elseif ($acao === 'ALTERAÇÃO') $acaoClass = 'a-vencer';
                                elseif ($acao === 'EXCLUSÃO' || $acao === 'BLOQUEIO') $acaoClass = 'vencido';
                                elseif ($acao === 'DEVOLUÇÃO' || $acao === 'DESBLOQUEIO') $acaoClass = 'ativo';
                                
                                // O backend grava log_datahora em UTC (NOW() do MySQL Aiven) — converte para o fuso de Brasília
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
                                        <div class="text-muted" style="font-size: 10px;">ID do Usuário: <?= $log['usu_id'] ?? '---' ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $acaoClass ?> fw-bold"><?= htmlspecialchars($log['log_acao']) ?></span>
                                    </td>
                                    <td>
                                        <div class="fw-medium" style="font-size: 13px;"><?= htmlspecialchars($log['log_tabela']) ?></div>
                                        <div class="text-muted" style="font-size: 11px;">ID do Reg: <?= $log['log_registro_id'] ?? '---' ?></div>
                                    </td>
                                    <td>
                                        <div class="text-truncate text-muted" style="max-width: 320px; font-size: 13px;" title="<?= htmlspecialchars($ocorrencia) ?>">
                                            <?= htmlspecialchars($ocorrencia) ?>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-light border py-1 px-2" onclick="verDetalhesLog(<?= $log['log_id'] ?>)" title="Ver Detalhes do JSON">
                                            <i class="bi bi-braces"></i> Detalhes
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

<!-- Modal Detalhes do Log (Estrutura JSON do Payload) -->
<div class="modal fade" id="modalDetalhesLog" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
                // Interpreta o timestamp como UTC e exibe no fuso local do navegador
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
                
                // Formatação bonita do JSON
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
