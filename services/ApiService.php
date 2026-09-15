<?php
declare(strict_types=1);

namespace Services;

use Exception;

class ApiService {
    private string $baseUrl;
    private string $appRoot;

    public function __construct() {
        $configFile = dirname(__DIR__) . '/config/api.php';
        if (!file_exists($configFile)) {
            throw new Exception("Arquivo de configuração da API não encontrado.");
        }
        $config = require $configFile;
        $this->baseUrl = rtrim($config['api_base_url'], '/') . '/';
        $this->appRoot = $config['app_root_url'] ?? '/gestao_epi_web/';

        // Garante que a sessão esteja iniciada
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
    }

    /**
     * Executa uma requisição HTTP via cURL à API REST com política de Retry otimizada
     */
    public function request(string $method, string $endpoint, ?array $data = null): array {
        $maxRetries = 1;
        $retryDelay = 1; // segundo
        $attempt = 0;

        while ($attempt <= $maxRetries) {
            $url = $this->baseUrl . ltrim($endpoint, '/');
            $ch = curl_init($url);

            if ($ch === false) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível inicializar a conexão com a API.',
                    'data' => null,
                    'status_code' => 500
                ];
            }

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json'
            ];

            // Injeta automaticamente o token JWT da sessão se o usuário estiver logado
            if (isset($_SESSION['token']) && $_SESSION['token'] !== '') {
                $headers[] = 'Authorization: Bearer ' . $_SESSION['token'];
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Connect timeout otimizado de 5s
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Response timeout de 30s para relatórios pesados e logs de auditoria
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Evita problemas de SSL em localhost/Render de teste
            if ($data !== null && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
                $jsonData = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
            }

            // Libera a trava do arquivo de sessão no PHP para evitar deadlocks no Apache/localhost
            $wasSessionActive = (session_status() === PHP_SESSION_ACTIVE);
            if ($wasSessionActive) {
                session_write_close();
            }

            $response = curl_exec($ch);
            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            curl_close($ch);

            // Restaura a sessão se estava ativa antes da requisição HTTP para permitir que o script chamador grave $_SESSION
            if ($wasSessionActive && session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
                @session_start();
            }

            // Tenta novamente apenas se for falha pontual de rede/gateway (não se for timeout prolongado)
            $isGatewayError = in_array($statusCode, [502, 503, 504], true);
            $isTransientError = ($response === false && $errno !== CURLE_OPERATION_TIMEDOUT);

            if (($isTransientError || $isGatewayError) && $attempt < $maxRetries) {
                $attempt++;
                sleep($retryDelay);
                continue;
            }

            if ($response === false) {
                $msgErro = 'Não foi possível conectar ao servidor de API local. Verifique se o Apache e o MySQL do XAMPP estão ativos.';
                if ($errno === CURLE_OPERATION_TIMEDOUT) {
                    $msgErro = 'Tempo limite de resposta excedido (Timeout). Verifique o servidor Apache ou a conexão com o banco de dados.';
                }
                return [
                    'success' => false,
                    'message' => $msgErro . ' (Detalhes: ' . $error . ')',
                    'data' => null,
                    'status_code' => 0
                ];
            }

            $decodedData = json_decode($response, true);
            
            // Se a resposta não for um JSON válido (retornou HTML de erro persistente)
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [
                    'success' => false,
                    'message' => 'A API retornou uma resposta em formato inválido. Contate o administrador.',
                    'data' => null,
                    'status_code' => $statusCode,
                    'raw_response' => $response
                ];
            }

            // Adiciona o status code HTTP retornado na resposta para facilitar checagens
            $decodedData['status_code'] = $statusCode;

            // Tratamento centralizado para sessões expiradas (401)
            if ($statusCode === 401 && $endpoint !== 'auth/login') {
                unset($_SESSION['token']);
                unset($_SESSION['usuario']);
                
                $_SESSION['error_message'] = 'Sua sessão expirou ou o acesso é inválido. Por favor, faça login novamente.';
                
                $isProxy = defined('IS_API_PROXY') || (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'api_proxy.php');
                if ($isProxy || headers_sent() || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
                    return [
                        'success' => false,
                        'message' => 'Sua sessão expirou ou o token é inválido. Por favor, faça login novamente.',
                        'data' => null,
                        'status_code' => 401,
                        'logged_out' => true
                    ];
                }

                header('Location: ' . $this->appRoot . 'login.php');
                exit;
            }

            return $decodedData;
        }

        return [
            'success' => false,
            'message' => 'Falha de comunicação persistente com o servidor de API.',
            'data' => null,
            'status_code' => 502
        ];
    }

    public function get(string $endpoint): array {
        return $this->request('GET', $endpoint);
    }

    public function post(string $endpoint, array $data): array {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data): array {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint): array {
        return $this->request('DELETE', $endpoint);
    }
}
