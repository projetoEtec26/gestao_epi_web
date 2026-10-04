<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/services/ApiService.php';

use Services\ApiService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/config/api.php';
if (!defined('APP_ROOT')) {
    define('APP_ROOT', $config['app_root_url'] ?? '/gestao_epi_web_14/');
}

// Se o usuário já estiver logado com token válido e não exigir troca de senha, valida o token na API e redireciona para a dashboard
if (isset($_SESSION['token']) && $_SESSION['token'] !== '' && isset($_SESSION['usuario']) && !($_SESSION['exige_troca_senha'] ?? false)) {
    try {
        $apiVal = new ApiService();
        $meVal = $apiVal->get('auth/me');
        if (isset($meVal['success']) && $meVal['success']) {
            $homePage = 'pages/dashboard.php';
            header('Location: ' . APP_ROOT . $homePage);
            exit;
        } else {
            // Token expirado ou inválido na nuvem: limpa para forçar nova autenticação
            unset($_SESSION['token'], $_SESSION['usuario']);
        }
    } catch (\Throwable $e) {
        unset($_SESSION['token'], $_SESSION['usuario']);
    }
}

$erro = null;
$sucesso = null;

// Recupera mensagens salvas na sessão de redirects anteriores
if (isset($_SESSION['error_message'])) {
    $erro = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
if (isset($_SESSION['success_message'])) {
    $sucesso = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Fluxo 1: Login de Usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'login') {
    $login = isset($_POST['usu_login']) ? trim($_POST['usu_login']) : '';
    $senha = isset($_POST['senha']) ? $_POST['senha'] : '';

    if ($login === '' || $senha === '') {
        $erro = 'Por favor, preencha o usuário e a senha.';
    } else {
        try {
            $deviceInfo = function_exists('obterDispositivoWeb') ? obterDispositivoWeb() : 'Web (Navegador Desconhecido)';
            $api = new ApiService();
            $response = $api->post('auth/login', [
                'usu_login' => $login,
                'senha' => $senha,
                'aparelho' => $deviceInfo,
                'dispositivo' => $deviceInfo
            ]);

            $statusCode = $response['status_code'] ?? 200;

            if (isset($response['success']) && $response['success']) {
                $data = $response['data'];
                $_SESSION['token'] = $data['token'];
                $_SESSION['usuario'] = $data['usuario'];
                $_SESSION['exige_troca_senha'] = $data['exige_troca_senha'] ?? false;

                // Enriquece a sessão do usuário com auth/me para garantir usu_aceite_termos e usu_data_aceite_termos
                try {
                    $meRes = $api->get('auth/me');
                    if (isset($meRes['success']) && $meRes['success'] && is_array($meRes['data'])) {
                        $_SESSION['usuario'] = array_merge($_SESSION['usuario'], $meRes['data']);
                    }
                } catch (\Throwable $e) {}

                // Garante a gravação imediata da sessão PHP em disco
                session_write_close();

                $homePage = 'pages/dashboard.php?from_login=1';
                $redirectUrl = $_SESSION['exige_troca_senha'] ? APP_ROOT . 'login.php' : APP_ROOT . $homePage;
                header('Location: ' . $redirectUrl);
                exit;
            } else {
                $erro = $response['message'] ?? 'Credenciais inválidas.';
                if (isset($response['raw_response'])) {
                    $erro .= ' [Bruto: ' . htmlspecialchars(substr($response['raw_response'], 0, 250)) . '...]';
                }
            }
        } catch (Exception $e) {
            $erro = 'Não foi possível conectar ao servidor. Verifique a API e tente novamente.';
        }
    }
}

// Fluxo 2: Troca Obrigatória de Senha no Primeiro Acesso
if (!function_exists('validarPoliticaSenha')) {
    function validarPoliticaSenha(string $senha): ?string {
        if (strlen($senha) < 6) {
            return "A senha deve conter no mínimo 6 caracteres.";
        }
        if (!preg_match('/[a-zA-Z]/', $senha) || !preg_match('/[0-9]/', $senha)) {
            return "A senha deve conter pelo menos uma letra e um número.";
        }
        return null; // Senha válida
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $senhaAtual = $_SESSION['senha_temporaria'] ?? '';
    $novaSenha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmarSenha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    if ($novaSenha === '' || $confirmarSenha === '') {
        $erro = 'Os campos de nova senha e confirmação são obrigatórios.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'As senhas não coincidem.';
    } elseif (($erroPolitica = validarPoliticaSenha($novaSenha)) !== null) {
        $erro = $erroPolitica;
    } else {
        try {
            $api = new ApiService();
            $response = $api->post('auth/alterar-senha-primeiro-acesso', [
                'senha_atual' => $senhaAtual,
                'nova_senha' => $novaSenha,
                'confirmar_senha' => $confirmarSenha
            ]);

            if (isset($response['success']) && $response['success']) {
                unset($_SESSION['senha_temporaria']);
                $_SESSION['exige_troca_senha'] = false;
                session_write_close();
                
                $_SESSION['success_message'] = 'Senha alterada com sucesso! Bem-vindo ao Gestão EPI.';
                $perfil = $_SESSION['usuario']['usu_perfil'] ?? '';
                $homePage = match (strtoupper((string)$perfil)) {
                    'ADMINISTRADOR'       => 'pages/usuarios.php',
                    'RH_ADMINISTRATIVO'   => 'pages/funcionarios.php',
                    'TECNICO_SST'         => 'pages/epis.php',
                    'GESTOR'              => 'pages/dashboard.php',
                    'ALMOXARIFE_OPERADOR' => 'pages/entregas.php',
                    default               => 'pages/entregas.php',
                };
                header('Location: ' . APP_ROOT . $homePage);
                exit;
            } else {
                $erro = $response['message'] ?? 'Não foi possível alterar a senha.';
            }
        } catch (Exception $e) {
            $erro = 'Erro de conexão na alteração da senha: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestão EPI</title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= APP_ROOT ?>assets/favicon.svg">
    
    <!-- Script e Estilo Anti-Flicker do Modo Escuro -->
    <script>
        (function() {
            var theme = localStorage.getItem('theme-mode');
            if (theme === 'dark') {
                document.documentElement.classList.add('dark-mode');
                document.documentElement.style.backgroundColor = '#0f172a';
                document.documentElement.style.color = '#f8fafc';
            }
        })();
    </script>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts (Outfit) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --color-primary: #305BD3;
            --color-primary-hover: #1e44a5;
            --color-bg: #f8fafc;
            --color-card-bg: #ffffff;
            --color-text: #0f172a;
            --color-border: #cbd5e1;
        }

        html.dark-mode {
            --color-bg: #0f172a;
            --color-card-bg: #1e293b;
            --color-text: #f8fafc;
            --color-border: #334155;
        }

        /* Oculta o ícone nativo de revelar senha do Microsoft Edge/IE e autopreenchimento Chromium */
        input::-ms-reveal,
        input::-ms-clear {
            display: none !important;
            width: 0 !important;
            height: 0 !important;
        }

        input::-webkit-contacts-auto-fill-button,
        input::-webkit-credentials-auto-fill-button {
            visibility: hidden !important;
            display: none !important;
            pointer-events: none !important;
            position: absolute !important;
            right: 0 !important;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }

        .auth-card {
            background-color: var(--color-card-bg);
            border: 1px solid var(--color-border);
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(48, 91, 211, 0.08);
            padding: 40px;
            max-width: 440px;
            width: 100%;
        }

        .brand-logo {
            font-size: 32px;
            font-weight: 700;
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .brand-logo i {
            font-size: 34px;
        }

        .form-label {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 6px;
            color: var(--color-text);
        }

        .input-group-custom {
            display: flex;
            align-items: center;
            background-color: var(--color-card-bg);
            border: 1.5px solid var(--color-border);
            border-radius: 10px;
            padding: 0 12px;
            transition: all 0.2s ease;
        }

        .input-group-custom:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(48, 91, 211, 0.15);
        }

        .input-group-custom i.icon-prefix {
            color: #64748b;
            font-size: 18px;
            margin-right: 10px;
            flex-shrink: 0;
        }

        .input-group-custom input {
            border: none;
            outline: none;
            background: transparent;
            color: var(--color-text);
            padding: 12px 0;
            font-size: 14.5px;
            width: 100%;
        }

        .input-group-custom input::placeholder {
            color: #94a3b8;
        }

        .input-group-custom button.btn-toggle-eye {
            border: none;
            background: transparent;
            color: #64748b;
            padding: 0;
            margin-left: 8px;
            cursor: pointer;
            font-size: 18px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .input-group-custom button.btn-toggle-eye:hover {
            color: var(--color-primary);
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            padding: 13px;
            font-weight: 600;
            font-size: 15px;
            border-radius: 10px;
            transition: all 0.2s ease-in-out;
            width: 100%;
            box-shadow: 0 4px 12px rgba(48, 91, 211, 0.2);
        }

        .btn-primary:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
        }

        .forgot-password-link {
            text-align: right;
            margin-top: 8px;
            margin-bottom: 24px;
        }

        .forgot-password-link a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 600;
        }

        .forgot-password-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <!-- Logotipo Oficial Gestão_EPI -->
    <div class="brand-logo">
        <i class="bi bi-shield-check"></i>
        <span>Gestão_EPI</span>
    </div>

    <h4 class="text-center mb-4" style="font-weight: 700; color: var(--color-text);">Acesse sua Conta</h4>

    <!-- Alertas do Sistema -->
    <?php if ($erro !== null): ?>
        <div class="alert alert-danger d-flex align-items-center mb-4" role="alert" style="border-radius: 10px; font-size: 13.5px;">
            <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($erro) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== null): ?>
        <div class="alert alert-success d-flex align-items-center mb-4" role="alert" style="border-radius: 10px; font-size: 13.5px;">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            <div><?= htmlspecialchars($sucesso) ?></div>
        </div>
        <script>
            try {
                localStorage.removeItem('token');
                localStorage.removeItem('usuario');
                console.log('[AUTH LOGOUT] localStorage limpo com sucesso.');
            } catch(e) {}
        </script>
    <?php endif; ?>

    <?php if (($_SESSION['exige_troca_senha'] ?? false) === true): ?>
        <!-- FORMULÁRIO DE TROCA DE SENHA OBRIGATÓRIA (FLUXO 2 DO MANUAL) -->
        <div class="alert alert-warning py-2 px-3 mb-4 d-flex align-items-center gap-2" style="border-radius: 10px; font-size: 13px;">
            <i class="bi bi-shield-lock-fill fs-5"></i>
            <div><strong>Bloqueio Ativo:</strong> Sua senha é temporária. Cadastre uma nova senha para continuar.</div>
        </div>

        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="acao" value="alterar_senha">
            
            <!-- Campo Nova Senha -->
            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha *</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock icon-prefix"></i>
                    <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-eye" onclick="alternarVisibilidadeSenha('nova_senha', this)" title="Mostrar / Ocultar Senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Campo Confirmar Nova Senha -->
            <div class="mb-3">
                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha *</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock icon-prefix"></i>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a nova senha novamente" required autocomplete="new-password">
                    <button type="button" class="btn-toggle-eye" onclick="alternarVisibilidadeSenha('confirmar_senha', this)" title="Mostrar / Ocultar Senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="p-3 mb-4 rounded-3 border" style="background-color: rgba(0,0,0,0.02); font-size: 12px;">
                <div class="fw-bold mb-1 text-uppercase text-muted" style="letter-spacing: 0.5px;">Requisitos Obrigatórios:</div>
                <div class="text-muted"><i class="bi bi-check-circle me-1"></i> Mínimo de 6 caracteres</div>
                <div class="text-muted"><i class="bi bi-check-circle me-1"></i> Pelo menos uma letra e um número</div>
                <div class="text-muted"><i class="bi bi-check-circle me-1"></i> Confirmação de senha idêntica</div>
            </div>

            <button type="submit" class="btn btn-primary mb-2">Salvar e Acessar Sistema</button>
            <a href="logout.php" class="btn btn-outline-secondary w-100 text-decoration-none py-2" style="border-radius: 10px;">Cancelar e Sair</a>
        </form>
    <?php else: ?>
        <!-- FORMULÁRIO DE LOGIN NORMAL (EXATAMENTE COMO NO MODELO DO PRINT WEB) -->
        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="acao" value="login">
            
            <!-- Campo Usuário -->
            <div class="mb-3">
                <label for="usu_login" class="form-label">Usuário *</label>
                <div class="input-group-custom">
                    <i class="bi bi-person icon-prefix"></i>
                    <input type="text" id="usu_login" name="usu_login" placeholder="Digite seu login" required autocomplete="username">
                </div>
            </div>

            <!-- Campo Senha (Com Cadeado no Ícone e Olhinho de Alternar Visibilidade) -->
            <div class="mb-2">
                <label for="senha" class="form-label">Senha *</label>
                <div class="input-group-custom">
                    <i class="bi bi-lock icon-prefix"></i>
                    <input type="password" id="senha" name="senha" placeholder="Digite sua senha" required autocomplete="current-password">
                    <button type="button" class="btn-toggle-eye" onclick="alternarVisibilidadeSenha('senha', this)" title="Mostrar / Ocultar Senha">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Link Esqueceu a Senha (Alinhado à Direita) -->
            <div class="forgot-password-link">
                <a href="recuperar-senha.php">Esqueceu a senha?</a>
            </div>

            <!-- Botão Entrar no Sistema -->
            <button type="submit" class="btn btn-primary">Entrar no Sistema</button>
        </form>
    <?php endif; ?>
</div>

<script>
function alternarVisibilidadeSenha(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
