<?php
declare(strict_types=1);

require_once __DIR__ . '/services/ApiService.php';

use Services\ApiService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/config/api.php';
if (!defined('APP_ROOT')) {
    define('APP_ROOT', $config['app_root_url'] ?? '/gestao_epi_web/');
}

// Se o usuário já estiver logado com token válido e não exigir troca de senha, redireciona para a dashboard
if (isset($_SESSION['token']) && $_SESSION['token'] !== '' && isset($_SESSION['usuario']) && !($_SESSION['exige_troca_senha'] ?? false)) {
    header('Location: ' . APP_ROOT . 'pages/dashboard.php');
    exit;
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
            $api = new ApiService();
            $response = $api->post('auth/login', [
                'usu_login' => $login,
                'senha' => $senha
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

                $redirectUrl = $_SESSION['exige_troca_senha'] ? APP_ROOT . 'login.php' : APP_ROOT . 'pages/dashboard.php';
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'alterar_senha') {
    $senhaAtual = $_SESSION['senha_temporaria'] ?? '';
    $novaSenha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmarSenha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    if ($novaSenha === '' || $confirmarSenha === '') {
        $erro = 'Os campos de nova senha e confirmação são obrigatórios.';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve possuir pelo menos 6 caracteres.';
    } elseif (!preg_match('/[a-zA-Z]/', $novaSenha) || !preg_match('/[0-9]/', $novaSenha)) {
        $erro = 'A nova senha deve conter pelo menos uma letra e um número.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'A confirmação de nova senha não coincide.';
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
                header('Location: ' . APP_ROOT . 'pages/dashboard.php');
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
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-card {
            background-color: var(--color-card-bg);
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(48, 91, 211, 0.08);
            padding: 40px;
            max-width: 450px;
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
            gap: 12px;
        }

        .btn-primary {
            background-color: var(--color-primary);
            border-color: var(--color-primary);
            padding: 12px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease-in-out;
        }

        .btn-primary:hover {
            background-color: var(--color-primary-hover);
            border-color: var(--color-primary-hover);
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(48, 91, 211, 0.15);
            border-color: var(--color-primary);
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 20px;
        }

        .forgot-password a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="brand-logo">
        <i class="bi bi-shield-check"></i>
        <span>Gestão_EPI</span>
    </div>

    <?php if ($erro !== null): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($erro) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== null): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
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
        <!-- FORMULÁRIO DE TROCA DE SENHA OBRIGATÓRIA -->
        <h4 class="text-center mb-2 font-weight-bold">Alterar Senha</h4>
        <p class="text-muted text-center mb-4">Insira sua nova credencial de acesso segura abaixo.</p>

        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="acao" value="alterar_senha">
            
            <div class="mb-3">
                <label for="nova_senha" class="form-label">Nova Senha *</label>
                <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Mínimo 6 caracteres (letras e números)" required>
            </div>

            <div class="mb-4">
                <label for="confirmar_senha" class="form-label">Confirmar Nova Senha *</label>
                <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a nova senha novamente" required>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3">Salvar e Acessar</button>
            <a href="logout.php" class="btn btn-light w-100">Cancelar e Sair</a>
        </form>
    <?php else: ?>
        <!-- FORMULÁRIO DE LOGIN NORMAL -->
        <h4 class="text-center mb-4 font-weight-bold">Acesse sua Conta</h4>

        <form method="POST" action="login.php" novalidate>
            <input type="hidden" name="acao" value="login">
            
            <div class="mb-3">
                <label for="usu_login" class="form-label">Usuário *</label>
                <input type="text" class="form-control" id="usu_login" name="usu_login" placeholder="Digite seu login" required>
            </div>

            <div class="mb-3">
                <label for="senha" class="form-label">Senha *</label>
                <input type="password" class="form-control" id="senha" name="senha" placeholder="Digite sua senha" required>
            </div>

            <div class="forgot-password">
                <a href="recuperar-senha.php">Esqueceu a senha?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100">Entrar no Sistema</button>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
