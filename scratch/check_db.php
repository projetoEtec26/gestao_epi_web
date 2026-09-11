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

    $stmtUsers = $pdo->query("SELECT * FROM usuarios LIMIT 5");
    print_r($stmtUsers->fetchAll());

    $startEntregas = microtime(true);
    $stmtEntregas = $pdo->query("SELECT COUNT(*) FROM entrega_epis");
    echo "Entregas count: " . $stmtEntregas->fetchColumn() . " in " . round((microtime(true) - $startEntregas) * 1000, 2) . " ms\n";

} catch (Exception $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
