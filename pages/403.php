<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Acesso Negado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f8fafc;
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .error-icon {
            font-size: 72px;
            color: #ef4444;
            margin-bottom: 24px;
        }
        .btn-primary {
            background-color: #305BD3;
            border-color: #305BD3;
            padding: 10px 24px;
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #1e44a5;
            border-color: #1e44a5;
        }
    </style>
</head>
<body>
<div class="error-card">
    <div class="error-icon">
        <i class="bi bi-shield-slash"></i>
    </div>
    <h1 class="fw-bold mb-3">403</h1>
    <h4 class="mb-3">Acesso Negado</h4>
    <p class="text-muted mb-4">Você não possui permissão para acessar esta funcionalidade. A autoridade deste endpoint é controlada remotamente pela API.</p>
    <a href="../index.php" class="btn btn-primary"><i class="bi bi-house me-1"></i> Ir para a Página Inicial</a>
</div>
</body>
</html>
