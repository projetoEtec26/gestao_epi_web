<?php
require_once __DIR__ . '/../services/ApiService.php';
require_once 'C:/xampp/htdocs/gestao_epi_api_7/core/Auth.php';
use Core\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = ['usu_id' => 5, 'usu_login' => 'admin', 'usu_perfil' => 'ADMINISTRADOR'];
$token = Auth::generateToken($user, 'gestao_epi_api_chavesecretadoservidor_987654321', 43200);

$_SESSION['token'] = $token;
$_SESSION['usuario'] = $user;

// Simular renderização do funcionarios.php
ob_start();
require_once __DIR__ . '/../pages/funcionarios.php';
$html = ob_get_clean();

echo "HTML Length: " . strlen($html) . " bytes\n";
echo "Has Speed Dial Backdrop: " . (strpos($html, 'speed-dial-backdrop') !== false ? 'YES' : 'NO') . "\n";
echo "Has Speed Dial Toggle: " . (strpos($html, 'speed-dial-toggle') !== false ? 'YES' : 'NO') . "\n";
echo "Has Option Pendencias: " . (strpos($html, 'Pendências') !== false ? 'YES' : 'NO') . "\n";
echo "Has Option Senha / PIN: " . (strpos($html, 'Senha / PIN') !== false ? 'YES' : 'NO') . "\n";
echo "Has Option Novo Funcionario: " . (strpos($html, 'Novo Funcionário') !== false ? 'YES' : 'NO') . "\n";
echo "Has Option Lista Funcionarios: " . (strpos($html, 'Lista Funcionários') !== false ? 'YES' : 'NO') . "\n";
