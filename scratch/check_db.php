<?php
require_once 'C:/xampp/htdocs/gestao_epi_api_7/config/database.php';
use Config\Database;

$start = microtime(true);
echo "Connecting to Aiven DB...\n";
try {
    $pdo = Database::getConnection();
    echo "Connected in " . round((microtime(true) - $start) * 1000, 2) . " ms\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    echo "Tables: " . json_encode($stmt->fetchAll(PDO::FETCH_COLUMN)) . "\n";

    $salt = "5313a7bef82d84393462e48a41a25fc0";
    $senha = "admin123";
    echo "sha256(salt+senha): " . hash('sha256', $salt . $senha) . "\n";
    echo "sha256(senha+salt): " . hash('sha256', $senha . $salt) . "\n";
    echo "sha256(senha):      " . hash('sha256', $senha) . "\n";
    echo "hash_hmac(sha256):  " . hash_hmac('sha256', $senha, $salt) . "\n";
    echo "Current in DB:      e676153916e530ef1a734da4cf8603269c2f28e93eb2ce141619a7a82f27b50f\n";
} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
