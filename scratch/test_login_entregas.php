<?php
require_once __DIR__ . '/../services/ApiService.php';
use Services\ApiService;

$api = new ApiService();

$loginRes = $api->post('auth/login', [
    'usu_login' => 'admin',
    'senha' => '123456'
]);

echo "Login response: " . json_encode($loginRes) . "\n";

if (isset($loginRes['data']['token'])) {
    $_SESSION['token'] = $loginRes['data']['token'];
    
    $start = microtime(true);
    $entregasRes = $api->get('entregas');
    $elapsed = round((microtime(true) - $start) * 1000, 2);
    
    echo "Entregas fetch time: " . $elapsed . " ms\n";
    echo "Entregas count: " . (is_array($entregasRes['data'] ?? null) ? count($entregasRes['data']) : 0) . "\n";
}
