<?php
$config = require __DIR__ . '/../config/api.php';
echo "API BASE URL: " . var_export($config['api_base_url'], true) . "\n";
echo "GETENV API_BASE_URL: " . var_export(getenv('API_BASE_URL'), true) . "\n";
