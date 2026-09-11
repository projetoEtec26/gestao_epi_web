<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, ""); // Ativa a engine de cookies em memória do cURL

// Step 1: GET login.php para obter o cookie de sessão inicial
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login.php');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$body0 = curl_exec($ch);
$code0 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Step 0 (GET login.php): HTTP Code {$code0}\n";

// Step 2: POST login.php com as credenciais
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'acao' => 'login',
    'usu_login' => 'admin',
    'senha' => 'admin123'
]));
$body1 = curl_exec($ch);
$code1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "Step 1 (POST login.php): HTTP Code {$code1}\n";

// Step 3: GET dashboard.php com a sessão mantida na memória do cURL
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/pages/dashboard.php');
curl_setopt($ch, CURLOPT_HTTPGET, true);
$body2 = curl_exec($ch);
$code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Step 2 (GET dashboard.php): HTTP Code {$code2}\n";

if (strpos($body2, 'Painel Geral') !== false || strpos($body2, 'Dashboard') !== false) {
    echo "SUCCESS! Dashboard loaded with Painel Geral for logged-in session!\n";
} else {
    echo "FAILED! Body snippet:\n" . substr(strip_tags($body2), 0, 400) . "\n";
}




