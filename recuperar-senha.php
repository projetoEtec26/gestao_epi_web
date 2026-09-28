<?php
declare(strict_types=1);

// Garante o fuso horário padrão oficial do Brasil (America/Sao_Paulo - GMT-3)
date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/services/ApiService.php';

use Services\ApiService;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$erro = null;
$sucesso = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = isset($_POST['usu_login']) ? trim($_POST['usu_login']) : '';
    $novaSenha = isset($_POST['nova_senha']) ? $_POST['nova_senha'] : '';
    $confirmarSenha = isset($_POST['confirmar_senha']) ? $_POST['confirmar_senha'] : '';

    if ($login === '' || $novaSenha === '' || $confirmarSenha === '') {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (strlen($novaSenha) < 6) {
        $erro = 'A nova senha deve possuir pelo menos 6 caracteres.';
    } elseif (!preg_match('/[a-zA-Z]/', $novaSenha) || !preg_match('/[0-9]/', $novaSenha)) {
        $erro = 'A nova senha deve conter pelo menos uma letra e um número.';
    } elseif ($novaSenha !== $confirmarSenha) {
        $erro = 'A confirmação de senha não coincide.';
    } else {
        try {
            $api = new ApiService();
            $response = $api->post('auth/recuperar-senha', [
                'usu_login' => $login,
                'nova_senha' => $novaSenha,
                'confirmar_senha' => $confirmarSenha
            ]);

            if (isset($response['success']) && $response['success']) {
                $sucesso = 'Senha redefinida com sucesso! Você pode realizar o login com a nova senha.';
            } else {
                $erro = $response['message'] ?? 'Falha ao redefinir a senha. Tente novamente.';
            }
        } catch (Exception $e) {
            $erro = 'Não foi possível conectar ao servidor de API. Detalhes: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Gestão EPI</title>
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
            max-width: 480px;
            width: 100%;
        }

        .brand-logo {
            font-size: 28px;
            font-weight: 700;
            color: var(--color-primary);
            text-align: center;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
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

        .back-to-login {
            text-align: center;
            margin-top: 20px;
        }

        .back-to-login a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
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
    
    <h4 class="text-center mb-2 font-weight-bold">Recuperar Senha</h4>
    <p class="text-muted text-center mb-4">Insira seu usuário do sistema para redefinir sua credencial de acesso.</p>

    <?php if ($erro !== null): ?>
        <div class="alert alert-danger d-flex align-items-center" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <div><?= htmlspecialchars($erro) ?></div>
        </div>
    <?php endif; ?>

    <?php if ($sucesso !== null): ?>
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>
                <?= htmlspecialchars($sucesso) ?><br>
                <a href="login.php" class="alert-link">Clique aqui para logar</a>.
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="recuperar-senha.php" novalidate>
        <div class="mb-3">
            <label for="usu_login" class="form-label">Usuário *</label>
            <input type="text" class="form-control" id="usu_login" name="usu_login" placeholder="Ex: sst_user" required>
        </div>

        <div class="mb-3">
            <label for="nova_senha" class="form-label">Nova Senha *</label>
            <input type="password" class="form-control" id="nova_senha" name="nova_senha" placeholder="Letras e números, mín. 6 caracteres" required>
        </div>

        <div class="mb-4">
            <label for="confirmar_senha" class="form-label">Confirmar Nova Senha *</label>
            <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" placeholder="Digite a senha novamente" required>
        </div>

        <button type="submit" class="btn btn-primary w-100 mb-3">Redefinir Senha</button>
    </form>

    <div class="back-to-login">
        <a href="login.php"><i class="bi bi-arrow-left me-1"></i> Voltar para o Login</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
