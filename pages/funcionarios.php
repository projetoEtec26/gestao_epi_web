<?php
declare(strict_types=1);

$page_title = 'Funcionários';
$active_menu = 'funcionarios';
$page_roles = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR', 'GESTOR'];

require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../components/sidebar.php';
require_once __DIR__ . '/../services/ApiService.php';

use Services\ApiService;

$api = new ApiService();
$erro = null;
$sucesso = null;

// Lida com formulários de alteração de dados (PHP POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    $acao = $_POST['acao'];

    // 1. Cadastrar Funcionário
    if ($acao === 'cadastrar') {
        $nome = trim($_POST['fun_nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['fun_cpf'] ?? '');
        $esocial = trim($_POST['fun_esocial'] ?? '');
        $departamento = trim($_POST['fun_departamento'] ?? '');
        $cargo = trim($_POST['fun_cargo'] ?? '');
        $dataAdmissao = $_POST['fun_dataadmissao'] ?? '';
        $situacao = $_POST['fun_situacao'] ?? 'ATIVO';

        try {
            $response = $api->post('funcionarios', [
                'fun_nome' => $nome,
                'fun_cpf' => $cpf,
                'fun_esocial' => $esocial,
                'fun_departamento' => $departamento,
                'fun_cargo' => $cargo,
                'fun_dataadmissao' => $dataAdmissao,
                'fun_situacao' => $situacao
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Funcionário ' . htmlspecialchars($nome) . ' cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar funcionário.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 2. Editar/Atualizar Funcionário
    if ($acao === 'editar') {
        $id = (int)($_POST['fun_id'] ?? 0);
        $nome = trim($_POST['fun_nome'] ?? '');
        $cpf = preg_replace('/\D/', '', $_POST['fun_cpf'] ?? '');
        $esocial = trim($_POST['fun_esocial'] ?? '');
        $departamento = trim($_POST['fun_departamento'] ?? '');
        $cargo = trim($_POST['fun_cargo'] ?? '');
        $dataAdmissao = $_POST['fun_dataadmissao'] ?? '';
        $situacao = $_POST['fun_situacao'] ?? 'ATIVO';

        try {
            $response = $api->put("funcionarios/{$id}", [
                'fun_nome' => $nome,
                'fun_cpf' => $cpf,
                'fun_esocial' => $esocial,
                'fun_departamento' => $departamento,
                'fun_cargo' => $cargo,
                'fun_dataadmissao' => $dataAdmissao,
                'fun_situacao' => $situacao
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Dados do funcionário atualizados com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao atualizar dados.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 3. Excluir/Inativar Funcionário
    if ($acao === 'excluir') {
        $id = (int)($_POST['fun_id'] ?? 0);

        try {
            $response = $api->delete("funcionarios/{$id}");

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Funcionário inativado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao inativar funcionário.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 4. Cadastrar PIN de Assinatura
    if ($acao === 'cadastrar_pin') {
        $funId = (int)($_POST['fun_id'] ?? 0);
        $pin = trim($_POST['pin'] ?? '');

        try {
            $response = $api->post('assinaturas', [
                'fun_id' => $funId,
                'pin' => $pin
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'PIN de Assinatura Eletrônica cadastrado com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao cadastrar PIN.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 5. Redefinir PIN de Assinatura
    if ($acao === 'redefinir_pin') {
        $funId = (int)($_POST['fun_id'] ?? 0);
        $pin = trim($_POST['pin'] ?? '');

        try {
            $response = $api->post('assinaturas/redefinir', [
                'fun_id' => $funId,
                'pin' => $pin
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'PIN de Assinatura Eletrônica redefinido com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao redefinir PIN.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 6. Bloquear Assinatura
    if ($acao === 'bloquear_pin') {
        $assId = (int)($_POST['ass_id'] ?? 0);
        $motivo = trim($_POST['motivo_bloqueio'] ?? 'Bloqueio administrativo');

        try {
            $response = $api->post("assinaturas/bloquear/{$assId}", [
                'motivo_bloqueio' => $motivo
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Assinatura eletrônica bloqueada!';
            } else {
                $erro = $response['message'] ?? 'Falha ao bloquear.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }

    // 7. Desbloquear Assinatura
    if ($acao === 'desbloquear_pin') {
        $assId = (int)($_POST['ass_id'] ?? 0);

        try {
            $response = $api->post("assinaturas/desbloquear/{$assId}", []);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Assinatura eletrônica desbloqueada com sucesso!';
            } else {
                $erro = $response['message'] ?? 'Falha ao desbloquear.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão: ' . $e->getMessage();
        }
    }
}

// Carrega listagem de funcionários
$funcionarios = [];
try {
    $listaRes = $api->get('funcionarios');
    if (isset($listaRes['success']) && $listaRes['success']) {
        $funcionarios = $listaRes['data'];
    }
} catch (Exception $e) {
    $erro = 'Não foi possível carregar a lista de funcionários: ' . $e->getMessage();
}

$podeEditar = in_array($userProfile, ['ADMINISTRADOR', 'RH_ADMINISTRATIVO'], true);
$podeExcluir = ($userProfile === 'ADMINISTRADOR');
$podeGerenciarPin = in_array($userProfile, ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST', 'ALMOXARIFE_OPERADOR'], true);
$acao = $_GET['acao'] ?? 'lista';
?>

<div id="main-content">
    <?php require_once __DIR__ . '/../components/topbar.php'; ?>
    
    <div class="content-body">
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

        <!-- VIEW 1: LISTA FUNCIONÁRIOS (PADRÃO) -->
        <div id="view-lista-funcionarios" style="display: <?= ($acao === 'lista' || empty($acao) || $acao === 'novo') ? 'block' : 'none' ?>;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <h3 class="fw-bold m-0" style="color: var(--color-primary);">Funcionários</h3>
                    <p class="text-muted">Gerencie o cadastro, PIN de segurança e histórico de posse de EPIs dos colaboradores.</p>
                </div>
                
                <div class="d-flex gap-2">
                    <?php if ($podeEditar): ?>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Importar
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCadastrar">
                            <i class="bi bi-person-plus me-1"></i> Novo Funcionário
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Listagem e Filtro -->
            <div class="card-custom">
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-5 position-relative">
                        <label for="busca-input" class="form-label fw-semibold mb-1" style="font-size:12px;">
                            <i class="bi bi-search text-primary me-1"></i> Buscar Colaborador (Tempo Real) *
                        </label>
                        <div id="srch-box-border-func" style="
                            display:flex; align-items:center; gap:8px;
                            background:#fff; border:1.5px solid #d0d5dd;
                            border-radius:10px; padding:0 12px;
                            transition:border-color .2s, box-shadow .2s;">
                            <i class="bi bi-search" style="color:#3b82f6;font-size:15px;flex-shrink:0;"></i>
                            <input type="text" 
                                   id="busca-input" 
                                   autocomplete="off"
                                   placeholder="Digite nome (ex: Ron...), CPF ou cargo..." 
                                   style="border:none;outline:none;flex:1;padding:9px 0;font-size:14px;background:transparent;"
                                   oninput="aoDigitarBuscaFuncionario(this.value)"
                                   onfocus="this.closest('#srch-box-border-func').style.borderColor='#3b82f6'; this.closest('#srch-box-border-func').style.boxShadow='0 0 0 3px rgba(59,130,246,.15)'; aoFocarBuscaFuncionario();"
                                   onblur="this.closest('#srch-box-border-func').style.borderColor='#d0d5dd'; this.closest('#srch-box-border-func').style.boxShadow='none';"
                                   onkeydown="aoTeclarBuscaFuncionario(event)">
                            <button type="button" 
                                    id="btn-limpar-busca" 
                                    title="Limpar busca" 
                                    onclick="limparBuscaFuncionario()" 
                                    style="display:none;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 2px;">
                                &times;
                            </button>
                        </div>
                        <!-- Dropdown de Autocomplete / Sugestões em Tempo Real -->
                        <div id="autocomplete-lista" 
                             style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; width: 100%; max-height: 340px; overflow-y: auto; z-index: 99999; border-radius: 12px; background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 12px 32px -4px rgba(0,0,0,0.18), 0 2px 8px -2px rgba(0,0,0,0.08) !important;">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select id="filtro-setor" class="form-select" onchange="aplicarFiltrosFuncionario()">
                            <option value="">Todos os Setores</option>
                            <?php
                            $setores = array_unique(array_column($funcionarios, 'fun_departamento'));
                            sort($setores);
                            foreach ($setores as $setor) {
                                if (!empty($setor)) {
                                    echo '<option value="' . htmlspecialchars($setor) . '">' . htmlspecialchars($setor) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select id="filtro-status" class="form-select" onchange="aplicarFiltrosFuncionario()">
                            <option value="">Todos os Status</option>
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="AFASTADO">Afastado</option>
                            <option value="DEMITIDO">Demitido</option>
                        </select>
                    </div>
                </div>

                <!-- Tabela -->
                <div class="table-responsive-custom">
                    <table class="table-custom" id="tabela-funcionarios">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>CPF</th>
                                <th>Cargo / Setor</th>
                                <th>Admissão</th>
                                <th>Status PIN</th>
                                <th>Situação</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($funcionarios)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">Nenhum funcionário cadastrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($funcionarios as $func): ?>
                                    <?php
                                    $cpf = $func['fun_cpf'];
                                    if (strlen($cpf) === 11) {
                                        $cpf = substr($cpf, 0, 3) . '.***.***-' . substr($cpf, 9, 2);
                                    }
                                    $statusPin = $func['assinatura_status'] ?? 'PENDENTE';
                                    $statusPinClass = strtolower(str_replace(' ', '-', $statusPin));
                                    $situacaoClass = strtolower($func['fun_situacao']);
                                    $dataAdmissao = $func['fun_dataadmissao'] ?? '';
                                    $dataAdmissaoFormatada = !empty($dataAdmissao) ? date('d/m/Y', strtotime($dataAdmissao)) : '---';
                                    ?>
                                    <tr class="func-row" 
                                        data-id="<?= (int)$func['fun_id'] ?>"
                                        data-nome="<?= htmlspecialchars(strtolower($func['fun_nome'])) ?>"
                                        data-cpf="<?= htmlspecialchars($func['fun_cpf']) ?>"
                                        data-cargo="<?= htmlspecialchars(strtolower($func['fun_cargo'])) ?>"
                                        data-setor="<?= htmlspecialchars($func['fun_departamento']) ?>"
                                        data-status="<?= htmlspecialchars($func['fun_situacao']) ?>">
                                        
                                        <td class="fw-semibold"><?= htmlspecialchars($func['fun_nome']) ?></td>
                                        <td class="text-muted"><?= htmlspecialchars($cpf) ?></td>
                                        <td>
                                            <div class="fw-medium"><?= htmlspecialchars($func['fun_cargo']) ?></div>
                                            <div class="text-muted" style="font-size: 12px;"><?= htmlspecialchars($func['fun_departamento']) ?></div>
                                        </td>
                                        <td><?= $dataAdmissaoFormatada ?></td>
                                        <td>
                                            <span class="status-badge <?= $statusPinClass ?>"><?= htmlspecialchars($statusPin) ?></span>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $situacaoClass ?>"><?= htmlspecialchars($func['fun_situacao']) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <button class="btn btn-sm btn-light border py-1 px-2" onclick="verDetalhes(<?= $func['fun_id'] ?>)" title="Ver Ficha e Histórico">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                
                                                <?php if ($podeEditar): ?>
                                                    <button class="btn btn-sm btn-light border text-primary py-1 px-2" onclick="prepararEdicao(<?= htmlspecialchars(json_encode($func)) ?>)" title="Editar dados">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($podeExcluir && $func['fun_situacao'] === 'ATIVO'): ?>
                                                    <button class="btn btn-sm btn-light border text-danger py-1 px-2" onclick="confirmarExclusao(<?= $func['fun_id'] ?>, '<?= htmlspecialchars($func['fun_nome']) ?>')" title="Inativar Colaborador">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr id="sem-resultados-busca" style="display: none;">
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-search me-2 text-primary fs-5"></i>
                                        <span>Nenhum colaborador localizado com os critérios informados.</span>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- VIEW 2: TELA PENDÊNCIAS ("Funcionários com Senha Pendente") -->
        <div id="view-pendencias-funcionarios" style="display: <?= ($acao === 'pendencias' || $acao === 'pendencia') ? 'block' : 'none' ?>;">
            <div class="header-azul-mobile d-flex align-items-center bg-primary text-white p-3 mb-3 rounded-3 shadow-sm" style="background-color: #2563eb !important;">
                <button class="btn btn-link text-white p-0 me-3 fs-4 border-0 d-lg-none" onclick="document.getElementById('sidebar-toggle-btn')?.click(); return false;">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="m-0 fw-bold text-white fs-5">Funcionários</h5>
            </div>
            
            <h6 class="fw-bold mb-3" style="color: #2563eb; font-size: 16px;">Funcionários com Senha Pendente</h6>
            
            <div id="lista-cards-pendencias" class="d-flex flex-column gap-3 mb-5">
                <?php
                $pendentesCount = 0;
                foreach ($funcionarios as $f) {
                    $pinStatus = $f['assinatura_status'] ?? 'PENDENTE';
                    $funcStatus = $f['fun_situacao'] ?? 'ATIVO';
                    $temPend = ($pinStatus === 'PENDENTE' || $pinStatus === 'INATIVO' || $pinStatus === 'NÃO CADASTRADO' || $pinStatus === 'BLOQUEADO' || $funcStatus !== 'ATIVO');
                    if (!$temPend) continue;

                    $pendentesCount++;
                    $badgeLabel = 'Senha pendente';
                    $badgeBg = '#f59e0b';

                    if ($funcStatus === 'AFASTADO') {
                        $badgeLabel = 'Afastado';
                        $badgeBg = '#f59e0b';
                    } elseif ($funcStatus !== 'ATIVO') {
                        $badgeLabel = htmlspecialchars($funcStatus);
                        $badgeBg = '#6b7280';
                    } elseif ($pinStatus === 'BLOQUEADO') {
                        $badgeLabel = 'PIN Bloqueado';
                        $badgeBg = '#ef4444';
                    }

                    $cpfRaw = $f['fun_cpf'] ?? '';
                    $cpfM = (strlen($cpfRaw) === 11) ? (substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, 9, 2)) : $cpfRaw;
                    $matr = !empty($f['fun_matricula']) ? $f['fun_matricula'] : ('MAT-' . str_pad((string)$f['fun_id'], 5, '0', STR_PAD_LEFT));
                    ?>
                    <div class="card border-0 shadow-sm rounded-4 p-3 style-card-pendencia" style="background:#ffffff; cursor:pointer;" onclick="verDetalhes(<?= (int)$f['fun_id'] ?>)">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <span class="fw-bold fs-6" style="color: #1d4ed8 !important;"><?= htmlspecialchars($f['fun_nome']) ?></span>
                            <span class="badge" style="background-color: <?= $badgeBg ?>; color: #ffffff; font-size: 11px; padding: 5px 10px; border-radius: 6px; font-weight: 600;"><?= $badgeLabel ?></span>
                        </div>
                        <div class="text-muted small mb-1" style="font-size: 13px;">
                            Matrícula: <?= htmlspecialchars($matr) ?> | CPF: <?= htmlspecialchars($cpfM) ?>
                        </div>
                        <div class="text-muted small" style="font-size: 13px;">
                            Cargo: <?= htmlspecialchars($f['fun_cargo'] ?? '---') ?> | Setor: <?= htmlspecialchars($f['fun_departamento'] ?? '---') ?>
                        </div>
                    </div>
                    <?php
                }
                if ($pendentesCount === 0) {
                    echo '<div class="alert alert-success text-center py-4 rounded-4 shadow-sm"><i class="bi bi-check-circle me-2"></i>Nenhuma pendência encontrada! Todos os colaboradores estão regulares.</div>';
                }
                ?>
            </div>
        </div>

        <!-- VIEW 3: TELA SENHA / PIN ("Gerenciar Senha/PIN do Colaborador") -->
        <div id="view-pin-funcionarios" style="display: <?= ($acao === 'pin' || $acao === 'senha_pin') ? 'block' : 'none' ?>;">
            <div class="header-azul-mobile d-flex align-items-center bg-primary text-white p-3 mb-3 rounded-3 shadow-sm" style="background-color: #2563eb !important;">
                <button class="btn btn-link text-white p-0 me-3 fs-4 border-0 d-lg-none" onclick="document.getElementById('sidebar-toggle-btn')?.click(); return false;">
                    <i class="bi bi-list"></i>
                </button>
                <h5 class="m-0 fw-bold text-white fs-5">Funcionários</h5>
            </div>
            
            <h6 class="fw-bold mb-3" style="color: #2563eb; font-size: 16px;">Gerenciar Senha/PIN do Colaborador</h6>
            
            <div class="d-flex gap-2 mb-4">
                <input type="text" 
                       id="busca-pin-input" 
                       class="form-control rounded-3 py-2 px-3 shadow-sm" 
                       placeholder="Buscar por nome, CPF ou ma..."
                       style="border: 1px solid #d1d5db; font-size: 14px;"
                       oninput="filtrarCardsPin(this.value)">
                <button type="button" 
                        class="btn btn-primary rounded-3 px-3 d-flex align-items-center justify-content-center shadow-sm"
                        style="background-color: #2563eb; border: none; min-width: 48px;"
                        onclick="filtrarCardsPin(document.getElementById('busca-pin-input').value)">
                    <i class="bi bi-person-fill fs-5"></i>
                </button>
            </div>
            
            <div id="lista-cards-pin" class="d-flex flex-column gap-3 mb-5">
                <?php foreach ($funcionarios as $f): ?>
                    <?php
                    $pinStatus = $f['assinatura_status'] ?? 'PENDENTE';
                    $badgeBg = '#f59e0b';
                    if ($pinStatus === 'CADASTRADO' || $pinStatus === 'ATIVO') $badgeBg = '#10b981';
                    if ($pinStatus === 'BLOQUEADO') $badgeBg = '#ef4444';

                    $cpfRaw = $f['fun_cpf'] ?? '';
                    $cpfM = (strlen($cpfRaw) === 11) ? (substr($cpfRaw, 0, 3) . '.***.***-' . substr($cpfRaw, 9, 2)) : $cpfRaw;
                    $matr = !empty($f['fun_matricula']) ? $f['fun_matricula'] : ('MAT-' . str_pad((string)$f['fun_id'], 5, '0', STR_PAD_LEFT));
                    ?>
                    <div class="card border-0 shadow-sm rounded-4 p-3 style-card-pin" style="background:#ffffff;">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <div class="fw-bold fs-6" style="color: #1d4ed8 !important;"><?= htmlspecialchars($f['fun_nome']) ?></div>
                                <div class="text-muted small" style="font-size: 12.5px;">Matrícula: <?= htmlspecialchars($matr) ?> | CPF: <?= htmlspecialchars($cpfM) ?></div>
                                <div class="text-muted small" style="font-size: 12.5px;">Cargo: <?= htmlspecialchars($f['fun_cargo'] ?? '---') ?> | Setor: <?= htmlspecialchars($f['fun_departamento'] ?? '---') ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge mb-2 d-inline-block" style="background-color: <?= $badgeBg ?>; color: #ffffff; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600;"><?= htmlspecialchars($pinStatus) ?></span>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1" style="font-size:12px;" onclick="verDetalhes(<?= (int)$f['fun_id'] ?>)">
                                        <i class="bi bi-key me-1"></i>Gerenciar PIN
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>



    <!-- Mobile Bottom Navigation Bar -->
    <div class="mobile-bottom-nav d-lg-none" style="
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60px;
        background-color: #ffffff;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-around;
        align-items: center;
        z-index: 1030;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
    ">
        <a href="<?= APP_ROOT ?>pages/entregas.php" class="text-decoration-none text-muted text-center py-1 flex-fill" style="font-size: 10px; font-weight: 600;">
            <i class="bi bi-box-arrow-up d-block fs-5 mb-1" style="color: #6b7280;"></i>
            <span>ENTREGAS</span>
        </a>
        <a href="<?= APP_ROOT ?>pages/epis.php" class="text-decoration-none text-muted text-center py-1 flex-fill" style="font-size: 10px; font-weight: 600;">
            <i class="bi bi-shield-check d-block fs-5 mb-1" style="color: #6b7280;"></i>
            <span>EPI'S</span>
        </a>
        <a href="<?= APP_ROOT ?>pages/relatorios.php" class="text-decoration-none text-muted text-center py-1 flex-fill" style="font-size: 10px; font-weight: 600;">
            <i class="bi bi-bar-chart-line d-block fs-5 mb-1" style="color: #6b7280;"></i>
            <span>RELATÓRIOS</span>
        </a>
        <a href="<?= APP_ROOT ?>pages/dashboard.php" class="text-decoration-none text-muted text-center py-1 flex-fill" style="font-size: 10px; font-weight: 600;">
            <i class="bi bi-grid-1x2 d-block fs-5 mb-1" style="color: #6b7280;"></i>
            <span>DASHBOARD</span>
        </a>
        <a href="#" onclick="document.getElementById('sidebar-toggle-btn')?.click(); return false;" class="text-decoration-none text-muted text-center py-1 flex-fill" style="font-size: 10px; font-weight: 600;">
            <i class="bi bi-list d-block fs-5 mb-1" style="color: #6b7280;"></i>
            <span>MAIS</span>
        </a>
    </div>
</div>

<!-- ================= MODAIS DE AÇÃO ================= -->

<!-- 1. Modal Cadastrar -->
<div class="modal fade" id="modalCadastrar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php" novalidate id="formCadastrar">
            <input type="hidden" name="acao" value="cadastrar">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-person-plus me-2"></i>Novo Funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="erro-cadastrar" class="alert alert-danger form-ajax-alert d-none py-2" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" name="fun_nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CPF *</label>
                    <input type="text" class="form-control mask-cpf" name="fun_cpf" placeholder="000.000.000-00" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código eSocial *</label>
                    <input type="text" class="form-control" name="fun_esocial" placeholder="Ex: ESO123456" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Departamento *</label>
                        <input type="text" class="form-control" name="fun_departamento" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Cargo *</label>
                        <input type="text" class="form-control" name="fun_cargo" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Admissão *</label>
                        <input type="date" class="form-control" name="fun_dataadmissao" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" name="fun_situacao">
                            <option value="ATIVO">Ativo</option>
                            <option value="AFASTADO">Afastado</option>
                        </select>
                    </div>
                </div>
                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar Funcionário</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. Modal Editar -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php" novalidate id="formEditar">
            <input type="hidden" name="acao" value="editar">
            <input type="hidden" id="edit-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-pencil me-2"></i>Editar Funcionário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="erro-editar" class="alert alert-danger form-ajax-alert d-none py-2" role="alert"></div>
                <div class="mb-3">
                    <label class="form-label">Nome Completo *</label>
                    <input type="text" class="form-control" id="edit-fun-nome" name="fun_nome" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">CPF *</label>
                    <input type="text" class="form-control mask-cpf" id="edit-fun-cpf" name="fun_cpf" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Código eSocial *</label>
                    <input type="text" class="form-control" id="edit-fun-esocial" name="fun_esocial" required>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Departamento *</label>
                        <input type="text" class="form-control" id="edit-fun-dept" name="fun_departamento" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Cargo *</label>
                        <input type="text" class="form-control" id="edit-fun-cargo" name="fun_cargo" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3">
                        <label class="form-label">Admissão *</label>
                        <input type="date" class="form-control" id="edit-fun-admissao" name="fun_dataadmissao" required>
                    </div>
                    <div class="col-6 mb-3">
                        <label class="form-label">Situação</label>
                        <select class="form-select" id="edit-fun-situacao" name="fun_situacao">
                            <option value="ATIVO">Ativo</option>
                            <option value="INATIVO">Inativo</option>
                            <option value="AFASTADO">Afastado</option>
                            <option value="DEMITIDO">Demitido</option>
                        </select>
                    </div>
                </div>
                <small class="text-muted">* Campos obrigatórios</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. Modal Excluir -->
<div class="modal fade" id="modalExcluir" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="excluir">
            <input type="hidden" id="excluir-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-trash me-2"></i>Confirmar Inativação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza de que deseja inativar o funcionário <strong id="excluir-fun-nome"></strong>?</p>
                <p class="text-muted" style="font-size: 13px;">Esta ação fará a exclusão lógica do colaborador. O histórico de entregas de EPIs continuará registrado para auditoria.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Confirmar Inativação</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. Modal Importar Funcionários em Lote -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-earmark-arrow-up me-2"></i>Importar Funcionários em Lote</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <p class="text-muted small m-0">Selecione uma planilha CSV contendo os colaboradores a serem cadastrados.</p>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="baixarModeloCsv()">
                        <i class="bi bi-download me-1"></i>Baixar Modelo CSV
                    </button>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Selecione o arquivo (CSV) *</label>
                    <input type="file" id="input-arquivo-importacao" class="form-control" accept=".csv, .txt, text/csv" onchange="lerArquivoImportacao(this)">
                    <div class="form-text">Formatos aceitos: <code>.csv</code> ou <code>.txt</code> delimitados por ponto e vírgula (<code>;</code>) ou vírgula (<code>,</code>).</div>
                </div>

                <div id="import-preview-wrapper" class="d-none">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-muted" id="import-preview-count">0 colaboradores detectados</span>
                        <span id="import-valid-badge" class="badge bg-success">0 prontos</span>
                    </div>
                    <div class="table-responsive border rounded" style="max-height: 220px; overflow-y: auto;">
                        <table class="table table-sm table-striped align-middle m-0" style="font-size: 12px;">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>#</th>
                                    <th>Nome</th>
                                    <th>CPF</th>
                                    <th>Cargo</th>
                                    <th>Departamento</th>
                                    <th>eSocial</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="import-preview-tbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Barra de Progresso da Importação -->
                <div id="import-progresso-wrapper" class="mt-3 d-none">
                    <label class="form-label small fw-semibold" id="import-progresso-texto">Processando importação...</label>
                    <div class="progress" style="height: 22px;">
                        <div id="import-progresso-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;">0%</div>
                    </div>
                </div>

                <!-- Mensagem de Resultado -->
                <div id="import-resultado-msg" class="mt-3 d-none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btn-processar-importacao" onclick="executarImportacaoLote()" disabled>
                    <i class="bi bi-cloud-upload me-1"></i>Processar Arquivo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- 5. Modal de Ficha Detalhada (Histórico e PIN) -->
<div class="modal fade" id="modalDetalhes" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);"><i class="bi bi-file-earmark-person me-2"></i>Ficha Individual do Colaborador</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-4">
                    <!-- Ficha Cadastral e QR Code -->
                    <div class="col-md-4 border-end">
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 32px; font-weight: 600;">
                                <span id="det-iniciais"></span>
                            </div>
                            <h5 class="fw-bold m-0" id="det-nome">Nome</h5>
                            <span class="text-muted" id="det-cargo">Cargo</span>
                        </div>
                        
                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">CPF:</span>
                                <span class="fw-semibold" id="det-cpf"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">eSocial:</span>
                                <span class="fw-semibold" id="det-esocial"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Departamento:</span>
                                <span class="fw-semibold" id="det-setor"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Admissão:</span>
                                <span class="fw-semibold" id="det-admissao"></span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <span class="text-muted">Situação Funcional:</span>
                                <span class="status-badge" id="det-situacao"></span>
                            </div>
                        </div>

                        <!-- Gerenciamento de PIN -->
                        <div class="card p-3 border-slate">
                            <h6 class="fw-bold mb-3"><i class="bi bi-key-fill me-1 text-primary"></i>PIN de Assinatura Eletrônica</h6>
                            <div class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">Status do PIN:</span>
                                <span class="status-badge" id="det-pin-status"></span>
                            </div>
                            
                            <?php if ($podeGerenciarPin): ?>
                                <div class="d-grid gap-2" id="area-acoes-pin">
                                    <!-- Dinâmico via JS -->
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Abas de Histórico -->
                    <div class="col-md-8">
                        <ul class="nav nav-tabs" id="detAbas" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="entregas-tab" data-bs-toggle="tab" data-bs-target="#tab-entregas" type="button" role="tab"><i class="bi bi-journal-text me-1"></i>EPIs em Posse / Entregues</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="devolucoes-tab" data-bs-toggle="tab" data-bs-target="#tab-devolucoes" type="button" role="tab"><i class="bi bi-arrow-counterclockwise me-1"></i>Devoluções</button>
                            </li>
                        </ul>
                        
                        <div class="tab-content pt-3" id="detAbasConteudo">
                            <!-- Aba Entregas -->
                            <div class="tab-pane fade show active" id="tab-entregas" role="tabpanel">
                                <div class="table-responsive" style="max-height: 380px;">
                                    <table class="table table-hover border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data</th>
                                                <th>EPI</th>
                                                <th>C.A.</th>
                                                <th>Qtd</th>
                                                <th>Tamanho</th>
                                                <th>Motivo</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-det-entregas">
                                            <!-- Dinâmico via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Aba Devoluções -->
                            <div class="tab-pane fade" id="tab-devolucoes" role="tabpanel">
                                <div class="table-responsive" style="max-height: 380px;">
                                    <table class="table table-hover border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Data Devolução</th>
                                                <th>EPI</th>
                                                <th>C.A.</th>
                                                <th>Qtd</th>
                                                <th>Motivo</th>
                                                <th>Condição</th>
                                            </tr>
                                        </thead>
                                        <tbody id="lista-det-devolucoes">
                                            <!-- Dinâmico via JS -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div>
                    <a href="#" id="btn-imprimir-ficha-oficial" target="_blank" class="btn btn-outline-primary">
                        <i class="bi bi-printer-fill me-1"></i> Imprimir Ficha Oficial (NR-06)
                    </a>
                    <a href="nova_entrega.php" id="btn-nova-entrega-colab" class="btn btn-success ms-2">
                        <i class="bi bi-plus-circle-fill me-1"></i> Nova Entrega para este Colaborador
                    </a>
                </div>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar Ficha</button>
            </div>
        </div>
    </div>
</div>

<!-- Modais secundários de PIN -->
<!-- 6. Modal Cadastrar PIN -->
<div class="modal fade" id="modalCadastrarPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="cadastrar_pin">
            <input type="hidden" id="pin-cad-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-key me-2"></i>Cadastrar PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Defina um PIN (4 a 10 dígitos) *</label>
                    <input type="password" class="form-control" name="pin" maxlength="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Cadastrar</button>
            </div>
        </form>
    </div>
</div>

<!-- 7. Modal Redefinir PIN -->
<div class="modal fade" id="modalRedefinirPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="redefinir_pin">
            <input type="hidden" id="pin-red-fun-id" name="fun_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat me-2"></i>Redefinir PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Defina o novo PIN *</label>
                    <input type="password" class="form-control" name="pin" maxlength="10" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary">Alterar PIN</button>
            </div>
        </form>
    </div>
</div>

<!-- 8. Modal Bloquear PIN -->
<div class="modal fade" id="modalBloquearPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="bloquear_pin">
            <input type="hidden" id="pin-bloq-ass-id" name="ass_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-lock me-2"></i>Bloquear PIN</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Motivo do Bloqueio *</label>
                    <input type="text" class="form-control" name="motivo_bloqueio" placeholder="Ex: Suspeita de fraude" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Bloquear</button>
            </div>
        </form>
    </div>
</div>

<!-- 9. Modal Desbloquear PIN -->
<div class="modal fade" id="modalDesbloquearPin" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <form class="modal-content" method="POST" action="funcionarios.php">
            <input type="hidden" name="acao" value="desbloquear_pin">
            <input type="hidden" id="pin-desb-ass-id" name="ass_id">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-unlock me-2"></i>Desbloquear</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Confirma o desbloqueio administrativo da assinatura do funcionário?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Desbloquear</button>
            </div>
        </form>
    </div>

<!-- 10. Modal Gestão Global de PIN (Speed Dial) -->
<div class="modal fade" id="modalGestaoPinGlobal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" style="color: var(--color-primary);">
                    <i class="bi bi-shield-lock me-2"></i>Gerenciamento de Senha / PIN de Assinatura
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Selecione um colaborador da lista para cadastrar, redefinir ou gerenciar o PIN de assinatura eletrônica.</p>
                
                <div class="input-group mb-3">
                    <span class="input-group-text bg-white border-end-0 text-primary"><i class="bi bi-search"></i></span>
                    <input type="text" id="busca-pin-global" class="form-control border-start-0" placeholder="Filtrar colaborador por nome ou CPF..." oninput="filtrarColaboradoresPin(this.value)">
                </div>

                <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Colaborador</th>
                                <th>Cargo / Setor</th>
                                <th>Status PIN</th>
                                <th class="text-end">Ação</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-pin-global">
                            <!-- Preenchido via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<!-- 11. Modal Resumo de Pendências Funcionais (Speed Dial) -->
<div class="modal fade" id="modalPendencias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-warning-emphasis">
                    <i class="bi bi-exclamation-triangle-fill me-2 text-warning"></i>Central de Pendências Funcionais
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light text-center">
                            <h4 class="fw-bold text-warning m-0" id="count-pend-pin">0</h4>
                            <small class="text-muted fw-semibold">Sem PIN Cadastrado</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light text-center">
                            <h4 class="fw-bold text-danger m-0" id="count-pend-bloq">0</h4>
                            <small class="text-muted fw-semibold">PIN Bloqueado</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 border rounded bg-light text-center">
                            <h4 class="fw-bold text-secondary m-0" id="count-pend-inativo">0</h4>
                            <small class="text-muted fw-semibold">Inativos / Afastados</small>
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-hover align-middle m-0" style="font-size: 13px;">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th>Colaborador</th>
                                <th>Pendente / Ocorrência</th>
                                <th class="text-end">Ação Rápida</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-pendencias">
                            <!-- Preenchido via JS -->
                        </tbody>
                    </table>
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
/* Lógica do Speed Dial definida no final do arquivo */

function renderizarTelaPendencias() {
    const container = document.getElementById('lista-cards-pendencias');
    if (!container) return;

    let html = '';
    const pendentes = listaFuncionariosCadastrados.filter(f => {
        const pinStatus = f.assinatura_status || 'PENDENTE';
        const funcStatus = f.fun_situacao || 'ATIVO';
        return (pinStatus === 'PENDENTE' || pinStatus === 'INATIVO' || pinStatus === 'NÃO CADASTRADO' || pinStatus === 'BLOQUEADO' || funcStatus !== 'ATIVO');
    });

    if (pendentes.length === 0) {
        container.innerHTML = '<div class="alert alert-success text-center py-4 rounded-4 shadow-sm"><i class="bi bi-check-circle me-2"></i>Nenhuma pendência encontrada! Todos os colaboradores estão regulares.</div>';
        return;
    }

    pendentes.forEach(f => {
        const pinStatus = f.assinatura_status || 'PENDENTE';
        const funcStatus = f.fun_situacao || 'ATIVO';
        let badgeLabel = 'Senha pendente';
        let badgeBg = '#f59e0b';

        if (funcStatus === 'AFASTADO') {
            badgeLabel = 'Afastado';
            badgeBg = '#f59e0b';
        } else if (funcStatus !== 'ATIVO') {
            badgeLabel = funcStatus;
            badgeBg = '#6b7280';
        } else if (pinStatus === 'BLOQUEADO') {
            badgeLabel = 'PIN Bloqueado';
            badgeBg = '#ef4444';
        }

        const cpfMasc = mascararCPF(f.fun_cpf);
        const mat = f.fun_matricula ? f.fun_matricula : ('MAT-' + String(f.fun_id).padStart(5, '0'));

        html += `
            <div class="card border-0 shadow-sm rounded-4 p-3 style-card-pendencia" style="background:#ffffff; cursor:pointer;" onclick="verDetalhes(${f.fun_id})">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="fw-bold fs-6" style="color: #1d4ed8 !important;">${htmlEscape(f.fun_nome)}</span>
                    <span class="badge" style="background-color: ${badgeBg}; color: #ffffff; font-size: 11px; padding: 5px 10px; border-radius: 6px; font-weight: 600;">${badgeLabel}</span>
                </div>
                <div class="text-muted small mb-1" style="font-size: 13px;">
                    Matrícula: ${mat} | CPF: ${cpfMasc}
                </div>
                <div class="text-muted small" style="font-size: 13px;">
                    Cargo: ${htmlEscape(f.fun_cargo || '---')} | Setor: ${htmlEscape(f.fun_departamento || '---')}
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function renderizarTelaPin(filtro = '') {
    const container = document.getElementById('lista-cards-pin');
    if (!container) return;

    const termoNorm = normalizarTexto(filtro);
    const filtrados = listaFuncionariosCadastrados.filter(f => {
        if (!termoNorm) return true;
        const nomeNorm = normalizarTexto(f.fun_nome);
        const cpfNorm = String(f.fun_cpf || '').replace(/\D/g, '');
        const matNorm = normalizarTexto(f.fun_matricula || ('MAT-' + String(f.fun_id).padStart(5, '0')));
        return nomeNorm.includes(termoNorm) || cpfNorm.includes(termoNorm) || matNorm.includes(termoNorm);
    });

    if (filtrados.length === 0) {
        container.innerHTML = '<div class="alert alert-light text-center py-4 rounded-4 shadow-sm text-muted">Nenhum colaborador encontrado com os critérios digitados.</div>';
        return;
    }

    let html = '';
    filtrados.forEach(f => {
        const pinStatus = f.assinatura_status || 'PENDENTE';
        let badgeBg = '#f59e0b';
        if (pinStatus === 'CADASTRADO' || pinStatus === 'ATIVO') badgeBg = '#10b981';
        if (pinStatus === 'BLOQUEADO') badgeBg = '#ef4444';

        const cpfMasc = mascararCPF(f.fun_cpf);
        const mat = f.fun_matricula ? f.fun_matricula : ('MAT-' + String(f.fun_id).padStart(5, '0'));

        html += `
            <div class="card border-0 shadow-sm rounded-4 p-3 style-card-pin" style="background:#ffffff;">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <div class="fw-bold fs-6" style="color: #1d4ed8 !important;">${htmlEscape(f.fun_nome)}</div>
                        <div class="text-muted small" style="font-size: 12.5px;">Matrícula: ${mat} | CPF: ${cpfMasc}</div>
                        <div class="text-muted small" style="font-size: 12.5px;">Cargo: ${htmlEscape(f.fun_cargo || '---')} | Setor: ${htmlEscape(f.fun_departamento || '---')}</div>
                    </div>
                    <div class="text-end">
                        <span class="badge mb-2 d-inline-block" style="background-color: ${badgeBg}; color: #ffffff; font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600;">${pinStatus}</span>
                        <div>
                            <button class="btn btn-sm btn-outline-primary rounded-3 px-3 py-1" style="font-size:12px;" onclick="verDetalhes(${f.fun_id})">
                                <i class="bi bi-key me-1"></i>Gerenciar PIN
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

function filtrarCardsPin(val) {
    renderizarTelaPin(val);
}

function fecharModalEMostrarDetalhes(idModal, funId) {
    const modalEl = document.getElementById(idModal);
    if (modalEl) {
        const inst = bootstrap.Modal.getInstance(modalEl) || bootstrap.Modal.getOrCreateInstance(modalEl);
        if (inst) inst.hide();
    }
    if (typeof verDetalhes === 'function') {
        verDetalhes(funId);
    }
}

function renderizarModalGestaoPinGlobal(filtro = '') {
    const tbody = document.getElementById('tbody-pin-global');
    if (!tbody) return;

    const termoNorm = normalizarTexto(filtro);
    const filtrados = listaFuncionariosCadastrados.filter(f => {
        if (!termoNorm) return true;
        const nomeNorm = normalizarTexto(f.fun_nome);
        const cpfNorm = String(f.fun_cpf || '').replace(/\D/g, '');
        return nomeNorm.includes(termoNorm) || cpfNorm.includes(termoNorm);
    });

    if (filtrados.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">Nenhum colaborador encontrado.</td></tr>';
        return;
    }

    let html = '';
    filtrados.forEach(f => {
        const cpfFmt = mascararCPF(f.fun_cpf);
        const status = f.assinatura_status || 'PENDENTE';
        const badgeClass = status.toLowerCase();

        html += `
            <tr>
                <td>
                    <div class="fw-semibold">${htmlEscape(f.fun_nome)}</div>
                    <small class="text-muted">CPF: ${cpfFmt}</small>
                </td>
                <td>
                    <div>${htmlEscape(f.fun_cargo || '---')}</div>
                    <small class="text-muted">${htmlEscape(f.fun_departamento || 'Geral')}</small>
                </td>
                <td>
                    <span class="status-badge ${badgeClass}">${status}</span>
                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" onclick="fecharModalEMostrarDetalhes('modalGestaoPinGlobal', ${f.fun_id})">
                        <i class="bi bi-key me-1"></i>Gerenciar PIN
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

function filtrarColaboradoresPin(val) {
    renderizarModalGestaoPinGlobal(val);
}

function renderizarModalPendencias() {
    const tbody = document.getElementById('tbody-pendencias');
    const elPin = document.getElementById('count-pend-pin');
    const elBloq = document.getElementById('count-pend-bloq');
    const elInativo = document.getElementById('count-pend-inativo');

    if (!tbody) return;

    let cPin = 0, cBloq = 0, cInativo = 0;
    let html = '';

    listaFuncionariosCadastrados.forEach(f => {
        const pinStatus = f.assinatura_status || 'PENDENTE';
        const funcStatus = f.fun_situacao || 'ATIVO';
        let temPendencia = false;
        let motifoPend = '';
        let acaoBtn = '';

        if (pinStatus === 'PENDENTE' || pinStatus === 'INATIVO' || pinStatus === 'NÃO CADASTRADO') {
            cPin++;
            temPendencia = true;
            motivoPend = '<span class="badge bg-warning text-dark"><i class="bi bi-key me-1"></i>PIN Não Cadastrado</span>';
            acaoBtn = `<button class="btn btn-sm btn-primary" onclick="fecharModalEMostrarDetalhes('modalPendencias', ${f.fun_id})"><i class="bi bi-key me-1"></i>Cadastrar PIN</button>`;
        } else if (pinStatus === 'BLOQUEADO') {
            cBloq++;
            temPendencia = true;
            motivoPend = '<span class="badge bg-danger"><i class="bi bi-lock me-1"></i>PIN Bloqueado</span>';
            acaoBtn = `<button class="btn btn-sm btn-outline-danger" onclick="fecharModalEMostrarDetalhes('modalPendencias', ${f.fun_id})"><i class="bi bi-unlock me-1"></i>Desbloquear</button>`;
        }

        if (funcStatus !== 'ATIVO') {
            cInativo++;
            if (!temPendencia) {
                temPendencia = true;
                motivoPend = `<span class="badge bg-secondary"><i class="bi bi-person-x me-1"></i>Situação: ${funcStatus}</span>`;
                acaoBtn = `<button class="btn btn-sm btn-outline-secondary" onclick="fecharModalEMostrarDetalhes('modalPendencias', ${f.fun_id})"><i class="bi bi-eye me-1"></i>Ver Ficha</button>`;
            }
        }

        if (temPendencia) {
            html += `
                <tr>
                    <td>
                        <div class="fw-semibold">${htmlEscape(f.fun_nome)}</div>
                        <small class="text-muted">${htmlEscape(f.fun_cargo || '')} • ${htmlEscape(f.fun_departamento || '')}</small>
                    </td>
                    <td>${motivoPend}</td>
                    <td class="text-end">${acaoBtn}</td>
                </tr>
            `;
        }
    });

    if (elPin) elPin.textContent = cPin;
    if (elBloq) elBloq.textContent = cBloq;
    if (elInativo) elInativo.textContent = cInativo;

    if (html === '') {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-success py-3"><i class="bi bi-check-circle me-1"></i>Nenhuma pendência encontrada! Todos os colaboradores estão com cadastros ativos e PINs regularizados.</td></tr>';
    } else {
        tbody.innerHTML = html;
    }
}
const PROXY_URL = 'api_proxy.php';

// Base de Colaboradores para Busca e Autocomplete
let listaFuncionariosCadastrados = <?= json_encode(array_values(array_map(function($f) {
    return [
        'fun_id' => (int)$f['fun_id'],
        'fun_nome' => $f['fun_nome'] ?? '',
        'fun_cpf' => $f['fun_cpf'] ?? '',
        'fun_matricula' => $f['fun_matricula'] ?? '',
        'fun_cargo' => $f['fun_cargo'] ?? 'Operacional',
        'fun_departamento' => $f['fun_departamento'] ?? 'Geral',
        'fun_situacao' => $f['fun_situacao'] ?? 'ATIVO',
        'assinatura_status' => $f['assinatura_status'] ?? 'PENDENTE'
    ];
}, $funcionarios)), JSON_UNESCAPED_UNICODE) ?>;

// Estado do Autocomplete
let sugestoesAtuais = [];
let indexFocadoAutocomplete = -1;

/**
 * Normaliza strings removendo acentos e espaços extras para comparação
 */
function normalizarTexto(str) {
    return String(str || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function destacarTrecho(texto, query) {
    if (!texto) return '';
    if (!query) return htmlEscape(texto);
    const textoNorm = normalizarTexto(texto);
    const queryNorm = normalizarTexto(query);
    const idx = textoNorm.indexOf(queryNorm);
    if (idx === -1) return htmlEscape(texto);

    const antes = texto.substring(0, idx);
    const meio = texto.substring(idx, idx + query.length);
    const depois = texto.substring(idx + query.length);
    return `${htmlEscape(antes)}<strong class="text-primary">${htmlEscape(meio)}</strong>${htmlEscape(depois)}`;
}

/**
 * Disparado no evento input do campo de busca
 */
function aoDigitarBuscaFuncionario(termo) {
    indexFocadoAutocomplete = -1;
    const btnLimpar = document.getElementById('btn-limpar-busca');

    if (btnLimpar) {
        btnLimpar.style.display = (termo && termo.length > 0) ? 'block' : 'none';
    }

    aplicarFiltrosFuncionario();
}

function aoFocarBuscaFuncionario() {
    aplicarFiltrosFuncionario();
}

function limparBuscaFuncionario() {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = '';
        busca.focus();
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.style.display = 'none';
    fecharAutocompleteFunc();
    aplicarFiltrosFuncionario();
}

function fecharAutocompleteFunc() {
    const autoList = document.getElementById('autocomplete-lista');
    if (autoList) {
        autoList.style.display = 'none';
        autoList.classList.add('d-none');
        autoList.innerHTML = '';
    }
    indexFocadoAutocomplete = -1;
    sugestoesAtuais = [];
}

/**
 * Filtra a tabela e o dropdown de autocomplete em tempo real
 */
function aplicarFiltrosFuncionario() {
    const busca = document.getElementById('busca-input');
    const filtroSetor = document.getElementById('filtro-setor');
    const filtroStatus = document.getElementById('filtro-status');
    const rows = document.querySelectorAll('.func-row');
    const semResultadosRow = document.getElementById('sem-resultados-busca');

    const rawQuery = busca ? busca.value.trim() : '';
    const queryNorm = normalizarTexto(rawQuery);
    const queryCleanCpf = rawQuery.replace(/\D/g, '');
    const setor = filtroSetor ? filtroSetor.value : '';
    const status = filtroStatus ? filtroStatus.value : '';

    let visiveis = 0;

    rows.forEach(row => {
        const rNome = normalizarTexto(row.getAttribute('data-nome'));
        const rCpf = row.getAttribute('data-cpf') || '';
        const rCpfLimpo = rCpf.replace(/\D/g, '');
        const rCargo = normalizarTexto(row.getAttribute('data-cargo'));
        const rSetor = row.getAttribute('data-setor') || '';
        const rStatus = row.getAttribute('data-status') || '';

        let bateBusca = false;
        if (!queryNorm) {
            bateBusca = true;
        } else {
            const matchNome = rNome.includes(queryNorm);
            const matchCargo = rCargo.includes(queryNorm);
            const matchCpf = (queryCleanCpf.length > 0 && rCpfLimpo.includes(queryCleanCpf)) || rCpf.includes(rawQuery);

            bateBusca = matchNome || matchCargo || matchCpf;
        }

        const bateSetor = (setor === '' || rSetor === setor);
        const bateStatus = (status === '' || rStatus === status);

        if (bateBusca && bateSetor && bateStatus) {
            row.style.display = '';
            visiveis++;
        } else {
            row.style.display = 'none';
        }
    });

    if (semResultadosRow) {
        semResultadosRow.style.display = (visiveis === 0 && rows.length > 0) ? '' : 'none';
    }

    renderizarAutocompleteFunc(rawQuery, queryNorm, queryCleanCpf, setor, status);
}

function renderizarAutocompleteFunc(rawQuery, queryNorm, queryCleanCpf, setor, status) {
    const autoList = document.getElementById('autocomplete-lista');
    if (!autoList) return;

    // Filtra colaboradores cadastrados por inclusão (.includes)
    sugestoesAtuais = listaFuncionariosCadastrados.filter(f => {
        const fSetor = f.fun_departamento || '';
        const fStatus = f.fun_situacao || 'ATIVO';

        const bateSetor = (setor === '' || fSetor === setor);
        const bateStatus = (status === '' || fStatus === status);
        if (!bateSetor || !bateStatus) return false;

        if (!queryNorm) return true;

        const nomeNorm = normalizarTexto(f.fun_nome);
        const cargoNorm = normalizarTexto(f.fun_cargo);
        const setorNorm = normalizarTexto(f.fun_departamento);
        const cpfLimpo = String(f.fun_cpf || '').replace(/\D/g, '');

        const matchNome = nomeNorm.includes(queryNorm);
        const matchCargo = cargoNorm.includes(queryNorm);
        const matchSetor = setorNorm.includes(queryNorm);
        const matchCpf = (queryCleanCpf.length > 0 && cpfLimpo.includes(queryCleanCpf)) || String(f.fun_cpf || '').includes(rawQuery);

        return matchNome || matchCargo || matchSetor || matchCpf;
    });

    indexFocadoAutocomplete = -1;

    if (sugestoesAtuais.length === 0) {
        autoList.innerHTML = `
            <div class="p-3 text-center text-muted" style="font-size: 13px;">
                <i class="bi bi-search me-1 text-secondary"></i> Nenhum colaborador encontrado com "<strong>${htmlEscape(rawQuery)}</strong>"
            </div>`;
        autoList.style.display = 'block';
        autoList.classList.remove('d-none');
        return;
    }

    const itensExibir = sugestoesAtuais.slice(0, 8);
    let html = `
        <div class="px-3 py-2 bg-light border-bottom text-muted d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size: 11px;">
            <span><i class="bi bi-people-fill me-1 text-primary"></i> <strong class="text-dark">${sugestoesAtuais.length}</strong> colaborador(es) encontrado(s)</span>
            <span class="d-inline-flex align-items-center gap-1">
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 7px; font-size:11px; font-weight:600;">▲</span>
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 7px; font-size:11px; font-weight:600;">▼</span>
                <span style="color:#64748b;">para navegar</span>
                <span style="color:#64748b;">•</span>
                <span style="background:#1e293b; color:#ffffff; border-radius:5px; padding:1px 10px; font-size:11px; font-weight:600;">Enter</span>
                <span style="color:#64748b;">para escolher</span>
            </span>
        </div>
        <div class="list-group list-group-flush">
    `;

    itensExibir.forEach((f, idx) => {
        const nomeDestacado = destacarTrecho(f.fun_nome, rawQuery);
        const cargo = htmlEscape(f.fun_cargo || 'Operacional');
        const setor = htmlEscape(f.fun_departamento || '');
        const cpfRaw = String(f.fun_cpf || '').replace(/\D/g, '');
        const cpfFmt = cpfRaw.length === 11 
            ? cpfRaw.substr(0, 3) + '.***.***-' + cpfRaw.substr(9, 2)
            : (f.fun_cpf || '---');
        const iniciais = (f.fun_nome || 'CO')
            .split(' ')
            .filter(Boolean)
            .slice(0, 2)
            .map(n => n[0].toUpperCase())
            .join('');

        html += `
            <a href="javascript:void(0)" 
               class="list-group-item list-group-item-action p-2 px-3 border-0 border-bottom d-flex align-items-center gap-2 autocomplete-item auto-func-item" 
               id="auto-func-${idx}"
               onclick="selecionarColaboradorAutocomplete('${f.fun_nome.replace(/'/g, "\\'")}', ${f.fun_id})"
               onmouseover="destacarItemFunc(${idx})">
                <div class="rounded-circle fw-bold d-flex align-items-center justify-content-center flex-shrink-0" 
                     style="width: 38px; height: 38px; font-size: 13px; background: #dbeafe; color: #1d4ed8;">
                    ${iniciais}
                </div>
                <div class="flex-grow-1 min-w-0" style="line-height: 1.3;">
                    <div class="d-flex align-items-center justify-content-between gap-2">
                        <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;">${nomeDestacado}</span>
                        <span class="badge bg-light text-secondary border flex-shrink-0" style="font-size: 10px; padding: 3px 8px; border-radius: 6px;">ID #${f.fun_id}</span>
                    </div>
                    <div class="text-muted d-flex flex-wrap align-items-center gap-1 mt-1" style="font-size: 11px;">
                        <span><i class="bi bi-briefcase me-1 text-secondary"></i>${cargo}</span>
                        ${setor ? '<span class="text-muted">•</span><span><i class="bi bi-building me-1 text-secondary"></i>' + setor + '</span>' : ''}
                        <span class="text-muted">•</span>
                        <code style="color: #e11d48; font-family: inherit; font-size: 11px; font-weight: 500;">CPF: ${cpfFmt}</code>
                    </div>
                </div>
            </a>
        `;
    });

    html += `</div>`;
    autoList.innerHTML = html;
    autoList.style.display = 'block';
    autoList.classList.remove('d-none');
}

function destacarItemFunc(idx) {
    indexFocadoAutocomplete = idx;
    const items = document.querySelectorAll('.auto-func-item');
    items.forEach((el, i) => {
        if (i === idx) {
            el.classList.add('active');
        } else {
            el.classList.remove('active');
        }
    });
}

function aoTeclarBuscaFuncionario(e) {
    const autoList = document.getElementById('autocomplete-lista');
    if (!autoList || autoList.style.display === 'none') return;

    if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (sugestoesAtuais.length > 0) {
            indexFocadoAutocomplete = (indexFocadoAutocomplete + 1) % Math.min(sugestoesAtuais.length, 8);
            destacarItemFunc(indexFocadoAutocomplete);
            const el = document.getElementById(`auto-func-${indexFocadoAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (sugestoesAtuais.length > 0) {
            indexFocadoAutocomplete = (indexFocadoAutocomplete - 1 + Math.min(sugestoesAtuais.length, 8)) % Math.min(sugestoesAtuais.length, 8);
            destacarItemFunc(indexFocadoAutocomplete);
            const el = document.getElementById(`auto-func-${indexFocadoAutocomplete}`);
            if (el) el.scrollIntoView({ block: 'nearest' });
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (indexFocadoAutocomplete >= 0 && sugestoesAtuais[indexFocadoAutocomplete]) {
            selecionarColaboradorAutocomplete(sugestoesAtuais[indexFocadoAutocomplete].fun_nome, sugestoesAtuais[indexFocadoAutocomplete].fun_id);
        } else if (sugestoesAtuais.length === 1) {
            selecionarColaboradorAutocomplete(sugestoesAtuais[0].fun_nome, sugestoesAtuais[0].fun_id);
        }
    } else if (e.key === 'Escape') {
        fecharAutocompleteFunc();
    }
}

function selecionarColaboradorAutocomplete(nome, funId) {
    const busca = document.getElementById('busca-input');
    if (busca) {
        busca.value = nome;
    }
    const btnLimpar = document.getElementById('btn-limpar-busca');
    if (btnLimpar) btnLimpar.style.display = 'block';
    fecharAutocompleteFunc();
    aplicarFiltrosFuncionario();

    if (funId) {
        const row = document.querySelector(`.func-row[data-id="${funId}"]`);
        if (row) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('table-active');
            setTimeout(() => row.classList.remove('table-active'), 2500);
        }
    }
}

// Fecha autocomplete se clicar fora
document.addEventListener('click', function(e) {
    const busca = document.getElementById('busca-input');
    const wrapper = busca ? busca.closest('.position-relative') : null;
    if (wrapper && !wrapper.contains(e.target)) {
        fecharAutocompleteFunc();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    registrarSubmissaoAjax();

    // Processa ações de submenu ou URL de forma unificada
    const urlParams = new URLSearchParams(window.location.search);
    const acaoParam = urlParams.get('acao');
    if (acaoParam) {
        setTimeout(() => {
            executarAcaoSubmenu(acaoParam);
        }, 200);
    }
});

/**
 * Submete os formulários de cadastro/edição via AJAX pelo proxy da API,
 * exibindo a mensagem de erro dentro do próprio modal (sem perder o que foi digitado).
 */
function registrarSubmissaoAjax() {
    async function enviarFormulario(form, route, metodo) {
        const alertEl = form.querySelector('.form-ajax-alert');
        const btn = form.querySelector('button[type="submit"]');
        const modalEl = form.closest('.modal');
        const dados = Object.fromEntries(new FormData(form).entries());
        delete dados.acao;
        delete dados.fun_id;

        alertEl.classList.add('d-none');
        btn.disabled = true;
        try {
            const res = await fetch(`${PROXY_URL}?route=${route}`, {
                method: metodo,
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(dados)
            });
            let json;
            try {
                json = await res.json();
            } catch {
                json = { success: false, message: 'Resposta inválida do servidor.' };
            }

            if (json.success) {
                const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                modal.hide();
                setTimeout(() => location.reload(), 350);
            } else {
                alertEl.textContent = json.message || 'Falha na operação.';
                alertEl.classList.remove('d-none');
            }
        } catch (err) {
            alertEl.textContent = 'Erro de conexão: ' + err.message;
            alertEl.classList.remove('d-none');
        } finally {
            btn.disabled = false;
        }
    }

    const formCadastrar = document.getElementById('formCadastrar');
    if (formCadastrar) {
        formCadastrar.addEventListener('submit', e => {
            e.preventDefault();
            enviarFormulario(formCadastrar, 'funcionarios', 'POST');
        });
    }

    const formEditar = document.getElementById('formEditar');
    if (formEditar) {
        formEditar.addEventListener('submit', e => {
            e.preventDefault();
            const id = document.getElementById('edit-fun-id').value;
            if (!id) return;
            enviarFormulario(formEditar, `funcionarios/${id}`, 'PUT');
        });
    }
}

/**
 * Preenche o modal de edição com os dados do funcionário selecionado
 */
function prepararEdicao(func) {
    document.getElementById('edit-fun-id').value = func.fun_id;
    document.getElementById('edit-fun-nome').value = func.fun_nome;
    document.getElementById('edit-fun-cpf').value = formatCPF(func.fun_cpf);
    document.getElementById('edit-fun-esocial').value = func.fun_esocial;
    document.getElementById('edit-fun-dept').value = func.fun_departamento;
    document.getElementById('edit-fun-cargo').value = func.fun_cargo;
    document.getElementById('edit-fun-admissao').value = func.fun_dataadmissao;
    document.getElementById('edit-fun-situacao').value = func.fun_situacao;
    
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
}

/**
 * Preenche o modal de exclusão
 */
function confirmarExclusao(id, nome) {
    document.getElementById('excluir-fun-id').value = id;
    document.getElementById('excluir-fun-nome').innerText = nome;
    
    new bootstrap.Modal(document.getElementById('modalExcluir')).show();
}

/**
 * Mascara o CPF conforme as diretrizes da LGPD (exibe apenas os 3 primeiros e os 2 últimos dígitos)
 */
function mascararCPF(cpf) {
    if (!cpf) return '';
    const limpo = cpf.replace(/\D/g, '');
    if (limpo.length !== 11) return cpf;
    return limpo.substring(0, 3) + '.***.***-' + limpo.substring(9, 11);
}

/**
 * Mascara o código do eSocial conforme as diretrizes da LGPD (exibe as 3 primeiras letras e as 2 últimas)
 */
function mascararESocial(esocial) {
    if (!esocial) return '';
    const len = esocial.length;
    if (len <= 5) return '*****';
    return esocial.substring(0, 3) + '*****' + esocial.substring(len - 2);
}

/**
 * Puxa os dados consolidados do funcionário de forma assíncrona da API (AJAX)
 */
function verDetalhes(funId) {

    // Abre modal de loading fictício ou preenche com "Carregando..."
    document.getElementById('det-nome').innerText = 'Carregando...';
    document.getElementById('det-iniciais').innerText = '...';
    document.getElementById('det-cpf').innerText = '';
    document.getElementById('det-esocial').innerText = '';
    document.getElementById('det-setor').innerText = '';
    document.getElementById('det-cargo').innerText = '';
    document.getElementById('det-admissao').innerText = '';
    document.getElementById('det-situacao').className = 'status-badge';
    document.getElementById('det-situacao').innerText = '';
    
    document.getElementById('det-pin-status').className = 'status-badge';
    document.getElementById('det-pin-status').innerText = 'Carregando...';
    
    const areaAcoes = document.getElementById('area-acoes-pin');
    if (areaAcoes) areaAcoes.innerHTML = '';
    
    document.getElementById('lista-det-entregas').innerHTML = '<tr><td colspan="7" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando histórico...</td></tr>';
    document.getElementById('lista-det-devolucoes').innerHTML = '<tr><td colspan="6" class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary" role="status"></div> Carregando histórico...</td></tr>';

    const modal = new bootstrap.Modal(document.getElementById('modalDetalhes'));
    modal.show();

    // 1. Puxa dados do Funcionário via Proxy Genérico
    fetch(`${PROXY_URL}?route=funcionarios/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const f = res.data;
                document.getElementById('det-nome').innerText = f.fun_nome;
                document.getElementById('det-iniciais').innerText = f.fun_nome.split(' ').slice(0,2).map(n => n[0]).join('').toUpperCase();
                document.getElementById('det-cpf').innerText = mascararCPF(f.fun_cpf);
                document.getElementById('det-esocial').innerText = mascararESocial(f.fun_esocial);
                document.getElementById('det-setor').innerText = f.fun_departamento;
                document.getElementById('det-cargo').innerText = f.fun_cargo;
                
                // Formatação data admissão
                const parts = f.fun_dataadmissao.split('-');
                document.getElementById('det-admissao').innerText = parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : f.fun_dataadmissao;
                
                // Situação Funcional
                const situacao = f.fun_situacao;
                const badge = document.getElementById('det-situacao');
                badge.className = `status-badge ${situacao.toLowerCase()}`;
                badge.innerText = situacao;

                // Links de Ação Rápida
                const btnFicha = document.getElementById('btn-imprimir-ficha-oficial');
                if (btnFicha) btnFicha.href = `ficha_colaborador.php?id=${funId}`;
                const btnEntrega = document.getElementById('btn-nova-entrega-colab');
                if (btnEntrega) btnEntrega.href = `nova_entrega.php`;

                // 2. Consulta assinatura eletrônica do funcionário
                consultarAssinatura(funId);
                
                // 3. Consulta histórico de entregas
                consultarEntregas(funId);
            }
        })
        .catch(err => {
            document.getElementById('det-nome').innerText = 'Erro ao carregar';
        });
}

function consultarAssinatura(funId) {
    const pinBadge = document.getElementById('det-pin-status');
    const areaAcoes = document.getElementById('area-acoes-pin');
    
    fetch(`${PROXY_URL}?route=assinaturas/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                const ass = res.data;
                const status = ass.ass_status.toUpperCase();
                pinBadge.className = `status-badge ${status.toLowerCase()}`;
                pinBadge.innerText = status;

                if (areaAcoes) {
                    if (status === 'ATIVO') {
                        areaAcoes.innerHTML = `
                            <button class="btn btn-sm btn-outline-warning" onclick="prepararBloqueio(${ass.ass_id})"><i class="bi bi-lock me-1"></i>Bloquear Assinatura</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="prepararRedefinir(${funId})"><i class="bi bi-arrow-repeat me-1"></i>Redefinir PIN</button>
                        `;
                    } else if (status === 'BLOQUEADO') {
                        areaAcoes.innerHTML = `
                            <button class="btn btn-sm btn-outline-success" onclick="prepararDesbloqueio(${ass.ass_id})"><i class="bi bi-unlock me-1"></i>Desbloquear PIN</button>
                            <button class="btn btn-sm btn-outline-primary" onclick="prepararRedefinir(${funId})"><i class="bi bi-arrow-repeat me-1"></i>Redefinir PIN</button>
                        `;
                    }
                }
            } else {
                pinBadge.className = 'status-badge inativo';
                pinBadge.innerText = 'NÃO CADASTRADO';
                if (areaAcoes) {
                    areaAcoes.innerHTML = `
                        <button class="btn btn-sm btn-primary" onclick="prepararCadastroPin(${funId})"><i class="bi bi-key me-1"></i>Cadastrar PIN</button>
                    `;
                }
            }
        })
        .catch(() => {
            pinBadge.className = 'status-badge inativo';
            pinBadge.innerText = 'ERRO';
        });
}

function consultarEntregas(funId) {
    const listEntregas = document.getElementById('lista-det-entregas');
    const listDevolucoes = document.getElementById('lista-det-devolucoes');

    fetch(`${PROXY_URL}?route=entregas/funcionario/${funId}`)
        .then(res => res.json())
        .then(res => {
            if (res.success && res.data) {
                let htmlEntregas = '';
                let htmlDevolucoes = '';
                let totalEntregas = 0;
                let totalDevolucoes = 0;

                res.data.forEach(entrega => {
                    const dataFormat = new Date(entrega.entr_data_entrega).toLocaleString('pt-BR');
                    
                    entrega.itens.forEach(item => {
                        // Linha de entregas
                        totalEntregas++;
                        const isCancelado = item.item_status === 'CANCELADO';
                        const isDevolvido = item.item_status === 'DEVOLVIDO';
                        
                        let statusClass = 'ativo';
                        if (isCancelado) statusClass = 'inativo';
                        if (isDevolvido) statusClass = 'pendente'; // Amarelo/Aviso
                        
                        htmlEntregas += `
                            <tr>
                                <td>${dataFormat.slice(0, 10)}</td>
                                <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                                <td>${item.item_epi_ca_snapshot || '---'}</td>
                                <td>${item.item_quantidade}</td>
                                <td>${item.item_tamanho || '---'}</td>
                                <td class="text-muted" style="font-size: 13px;">${item.item_devolucao_motivo || entrega.entr_motivo || '---'}</td>
                                <td><span class="status-badge ${statusClass}">${item.item_status}</span></td>
                            </tr>
                        `;

                        // Linha de devoluções se o item foi devolvido
                        if (item.item_data_devolucao) {
                            totalDevolucoes++;
                            const dataDev = new Date(item.item_data_devolucao).toLocaleString('pt-BR');
                            htmlDevolucoes += `
                                <tr>
                                    <td>${dataDev.slice(0, 10)}</td>
                                    <td class="fw-semibold">${item.item_epi_nome_snapshot || 'EPI'}</td>
                                    <td>${item.item_epi_ca_snapshot || '---'}</td>
                                    <td>${item.item_quantidade}</td>
                                    <td>${item.item_devolucao_motivo || '---'}</td>
                                    <td><span class="badge bg-secondary">${item.item_devolucao_condicao || 'USADO'}</span></td>
                                </tr>
                            `;
                        }
                    });
                });

                listEntregas.innerHTML = totalEntregas > 0 ? htmlEntregas : '<tr><td colspan="7" class="text-center text-muted py-3">Sem registros de entregas para este colaborador.</td></tr>';
                listDevolucoes.innerHTML = totalDevolucoes > 0 ? htmlDevolucoes : '<tr><td colspan="6" class="text-center text-muted py-3">Nenhuma devolução realizada por este colaborador.</td></tr>';
            } else {
                listEntregas.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">Sem registros.</td></tr>';
                listDevolucoes.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Sem registros.</td></tr>';
            }
        })
        .catch(() => {
            listEntregas.innerHTML = '<tr><td colspan="7" class="text-center text-danger py-3">Erro ao carregar dados.</td></tr>';
            listDevolucoes.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Erro ao carregar dados.</td></tr>';
        });
}

/* Funções de acionamento de modais secundários de PIN */
function prepararCadastroPin(funId) {
    document.getElementById('pin-cad-fun-id').value = funId;
    new bootstrap.Modal(document.getElementById('modalCadastrarPin')).show();
}

function prepararRedefinir(funId) {
    document.getElementById('pin-red-fun-id').value = funId;
    new bootstrap.Modal(document.getElementById('modalRedefinirPin')).show();
}

function prepararBloqueio(assId) {
    document.getElementById('pin-bloq-ass-id').value = assId;
    new bootstrap.Modal(document.getElementById('modalBloquearPin')).show();
}

function prepararDesbloqueio(assId) {
    document.getElementById('pin-desb-ass-id').value = assId;
    new bootstrap.Modal(document.getElementById('modalDesbloquearPin')).show();
}

/* ========================================================
 *  IMPORTAÇÃO CSV EM LOTE
 * ======================================================== */
let dadosImportacao = []; // Array de objetos parseados do CSV

/**
 * Baixa um arquivo CSV modelo para o usuário preencher
 */
function baixarModeloCsv() {
    const BOM = '\uFEFF'; // UTF-8 BOM para Excel reconhecer acentos
    const header = 'Nome;CPF;Cargo;Departamento;eSocial;Data Admissão (AAAA-MM-DD)';
    const exemplo = 'João da Silva;12345678901;Operador de Máquinas;Produção;ESO-001;2024-01-15';
    const csv = BOM + header + '\r\n' + exemplo + '\r\n';

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'modelo_importacao_funcionarios.csv';
    link.click();
    URL.revokeObjectURL(link.href);
}

/**
 * Lê o arquivo CSV selecionado, detecta delimitador, e exibe preview
 */
function lerArquivoImportacao(input) {
    const file = input.files[0];
    if (!file) return;

    const btnProcessar = document.getElementById('btn-processar-importacao');
    const previewWrapper = document.getElementById('import-preview-wrapper');
    const previewTbody = document.getElementById('import-preview-tbody');
    const previewCount = document.getElementById('import-preview-count');
    const validBadge = document.getElementById('import-valid-badge');
    const resultadoMsg = document.getElementById('import-resultado-msg');
    const progressoWrapper = document.getElementById('import-progresso-wrapper');

    // Reset state
    dadosImportacao = [];
    previewTbody.innerHTML = '';
    previewWrapper.classList.add('d-none');
    btnProcessar.disabled = true;
    resultadoMsg.classList.add('d-none');
    progressoWrapper.classList.add('d-none');

    const reader = new FileReader();
    reader.onload = function(e) {
        const text = e.target.result;
        const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');

        if (lines.length < 2) {
            alert('O arquivo está vazio ou contém apenas o cabeçalho.');
            return;
        }

        // Auto-detect delimiter: ; or ,
        const firstLine = lines[0];
        const delim = firstLine.includes(';') ? ';' : ',';

        // Skip header (line 0), parse data rows
        let validos = 0;
        for (let i = 1; i < lines.length; i++) {
            const cols = lines[i].split(delim).map(c => c.trim().replace(/^"|"$/g, ''));
            const nome = cols[0] || '';
            const cpf = (cols[1] || '').replace(/\D/g, '');
            const cargo = cols[2] || '';
            const depto = cols[3] || '';
            const esocial = cols[4] || '';
            const dataAdm = cols[5] || '';

            const ok = nome.length >= 3 && cpf.length === 11;

            dadosImportacao.push({
                fun_nome: nome,
                fun_cpf: cpf,
                fun_cargo: cargo,
                fun_departamento: depto,
                fun_esocial: esocial,
                fun_dataadmissao: dataAdm,
                fun_situacao: 'ATIVO',
                valido: ok
            });

            if (ok) validos++;

            const statusHtml = ok
                ? '<span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>'
                : '<span class="badge bg-danger"><i class="bi bi-x-lg"></i> Inválido</span>';

            const cpfFmt = cpf.length === 11
                ? cpf.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
                : cpf || '<span class="text-danger">vazio</span>';

            previewTbody.innerHTML += `
                <tr class="${ok ? '' : 'table-danger'}">
                    <td>${i}</td>
                    <td>${htmlEscape(nome) || '<span class="text-danger">vazio</span>'}</td>
                    <td><code>${cpfFmt}</code></td>
                    <td>${htmlEscape(cargo)}</td>
                    <td>${htmlEscape(depto)}</td>
                    <td>${htmlEscape(esocial)}</td>
                    <td>${statusHtml}</td>
                </tr>`;
        }

        previewCount.textContent = `${dadosImportacao.length} colaborador(es) detectado(s)`;
        validBadge.textContent = `${validos} pronto(s)`;
        validBadge.className = validos > 0 ? 'badge bg-success' : 'badge bg-secondary';
        previewWrapper.classList.remove('d-none');
        btnProcessar.disabled = validos === 0;
    };

    reader.readAsText(file, 'UTF-8');
}

/**
 * Envia cada funcionário válido sequencialmente via POST para a API
 */
async function executarImportacaoLote() {
    const validos = dadosImportacao.filter(d => d.valido);
    if (validos.length === 0) return;

    const btnProcessar = document.getElementById('btn-processar-importacao');
    const progressoWrapper = document.getElementById('import-progresso-wrapper');
    const progressoBar = document.getElementById('import-progresso-bar');
    const progressoTexto = document.getElementById('import-progresso-texto');
    const resultadoMsg = document.getElementById('import-resultado-msg');

    btnProcessar.disabled = true;
    progressoWrapper.classList.remove('d-none');
    resultadoMsg.classList.add('d-none');

    let sucesso = 0;
    let erros = [];

    for (let i = 0; i < validos.length; i++) {
        const item = validos[i];
        const pct = Math.round(((i + 1) / validos.length) * 100);
        progressoBar.style.width = pct + '%';
        progressoBar.textContent = pct + '%';
        progressoTexto.textContent = `Processando ${i + 1} de ${validos.length}: ${item.fun_nome}...`;

        try {
            const resp = await fetch(PROXY_URL + '?route=funcionarios', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    fun_nome: item.fun_nome,
                    fun_cpf: item.fun_cpf,
                    fun_cargo: item.fun_cargo,
                    fun_departamento: item.fun_departamento,
                    fun_esocial: item.fun_esocial,
                    fun_dataadmissao: item.fun_dataadmissao,
                    fun_situacao: item.fun_situacao
                })
            });
            const json = await resp.json();
            if (json.success) {
                sucesso++;
            } else {
                erros.push(`${item.fun_nome}: ${json.message || 'Erro desconhecido'}`);
            }
        } catch (e) {
            erros.push(`${item.fun_nome}: Falha de conexão`);
        }
    }

    progressoBar.classList.remove('progress-bar-animated');
    progressoTexto.textContent = 'Importação concluída!';

    let msgHtml = '';
    if (sucesso > 0) {
        msgHtml += `<div class="alert alert-success d-flex align-items-center"><i class="bi bi-check-circle-fill me-2"></i><strong>${sucesso}</strong>&nbsp;colaborador(es) importado(s) com sucesso!</div>`;
    }
    if (erros.length > 0) {
        msgHtml += `<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i><strong>${erros.length} erro(s):</strong><ul class="mb-0 mt-1">${erros.map(e => '<li>' + htmlEscape(e) + '</li>').join('')}</ul></div>`;
    }
    resultadoMsg.innerHTML = msgHtml;
    resultadoMsg.classList.remove('d-none');

    // Se tudo deu certo, recarrega a página após 2 segundos para mostrar os novos funcionários
    // Se tudo deu certo, recarrega a página após 2 segundos para mostrar os novos funcionários
    if (sucesso > 0 && erros.length === 0) {
        setTimeout(() => location.reload(), 2000);
    }
}

/**
 * Garante a limpeza total de overlays de modal e recupera a rolagem e cliques da página
 */
function limparOverlaysModal() {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
    
    if (window.location.search.indexOf('acao=') !== -1) {
        try {
            history.replaceState(null, '', window.location.pathname);
        } catch (e) {}
    }
}

// Escutador global para quando qualquer modal Bootstrap for escondido
document.addEventListener('hidden.bs.modal', function () {
    limparOverlaysModal();
});

/**
 * Executa as ações dos 4 submenus sem acúmulo de instâncias nem travamentos
 */
function executarAcaoSubmenu(acao) {
    // Fecha qualquer modal aberto previamente
    document.querySelectorAll('.modal.show').forEach(m => {
        const inst = bootstrap.Modal.getInstance(m);
        if (inst) inst.hide();
    });
    limparOverlaysModal();

    setTimeout(() => {
        if (acao === 'novo_funcionario' || acao === 'novo') {
            const modalEl = document.getElementById('modalCadastrar');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        } else if (acao === 'pin') {
            if (typeof renderizarModalGestaoPinGlobal === 'function') renderizarModalGestaoPinGlobal();
            const modalEl = document.getElementById('modalGestaoPinGlobal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                limparBuscaFuncionario();
                const inputBusca = document.getElementById('busca-input');
                if (inputBusca) {
                    inputBusca.value = 'PIN';
                    aoDigitarBuscaFuncionario('PIN');
                }
            }
        } else if (acao === 'pendencias') {
            if (typeof renderizarModalPendencias === 'function') renderizarModalPendencias();
            const modalEl = document.getElementById('modalPendencias');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            } else {
                limparBuscaFuncionario();
                const selStatus = document.getElementById('filtro-status');
                if (selStatus) {
                    selStatus.value = 'AFASTADO';
                    aplicarFiltrosFuncionario();
                }
            }
        } else if (acao === 'lista_funcionarios' || acao === 'lista') {
            limparBuscaFuncionario();
            const selSetor = document.getElementById('filtro-setor');
            const selStatus = document.getElementById('filtro-status');
            if (selSetor) selSetor.value = '';
            if (selStatus) selStatus.value = '';
            aplicarFiltrosFuncionario();
            const tabela = document.getElementById('tabela-funcionarios');
            if (tabela) {
                tabela.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    }, 80);
}

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const acaoParam = urlParams.get('acao');
    if (acaoParam) {
        setTimeout(() => executarAcaoSubmenu(acaoParam), 300);
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>

