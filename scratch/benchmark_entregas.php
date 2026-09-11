<?php
require_once 'C:/xampp/htdocs/gestao_epi_api_7/core/Auth.php';
use Core\Auth;

$user = ['usu_id' => 5, 'usu_login' => 'admin', 'usu_perfil' => 'ADMINISTRADOR'];
$secretKey = 'gestao_epi_api_chavesecretadoservidor_987654321';
$token = Auth::generateToken($user, $secretKey, 43200);

$ch = curl_init('http://localhost:8001/entregas');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token, 'Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$start = microtime(true);
$res = curl_exec($ch);
$elapsed = round((microtime(true) - $start) * 1000, 2);

echo "Response Time: " . $elapsed . " ms\n";
echo "Response Length: " . strlen((string)$res) . " bytes\n";
echo "Response Snippet: " . substr((string)$res, 0, 300) . "\n";
