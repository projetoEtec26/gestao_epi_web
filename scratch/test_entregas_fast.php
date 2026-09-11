<?php
require_once __DIR__ . '/../services/ApiService.php';
require_once 'C:/xampp/htdocs/gestao_epi_api_7/core/Auth.php';
use Services\ApiService;
use Core\Auth;

// Gerar token válido para admin
$user = ['usu_id' => 5, 'usu_login' => 'admin', 'usu_perfil' => 'ADMINISTRADOR'];
$secretKey = 'gestao_epi_api_chavesecretadoservidor_987654321';
$ttl = 43200;
$token = Auth::generateToken($user, $secretKey, $ttl);

$_SESSION['token'] = $token;

$start = microtime(true);
$api = new ApiService();
$response = $api->get('entregas');
$elapsed = round((microtime(true) - $start) * 1000, 2);

echo "Entregas Response Status: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
echo "Response Message: " . ($response['message'] ?? '') . "\n";
echo "Total Entregas Loaded: " . (is_array($response['data'] ?? null) ? count($response['data']) : 0) . "\n";
echo "Total Time Elapsed: " . $elapsed . " ms\n";
