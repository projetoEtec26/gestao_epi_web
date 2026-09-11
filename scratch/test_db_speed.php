<?php
$start = microtime(true);
$pdo = new PDO('mysql:host=127.0.0.1;dbname=database_schema;charset=utf8mb4', 'root', '');
$stmt = $pdo->query('SELECT COUNT(*) FROM funcionarios');
$count = $stmt->fetchColumn();
$time = (microtime(true) - $start) * 1000;
echo "Connected in {$time} ms. Funcionarios count: {$count}\n";
