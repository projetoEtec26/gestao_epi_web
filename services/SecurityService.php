<?php
declare(strict_types=1);

namespace Services;

use Exception;

/**
 * Service responsável pela Segurança da Informação do sistema Gestão EPI Web.
 * Garante interoperabilidade de Criptografia (AES-256-GCM), Hashes (Bcrypt / SHA-256) e RBAC
 * 100% alinhados com o aplicativo Android (Java).
 */
class SecurityService {

    /**
     * Obtém a chave secreta de 256 bits (32 bytes) para AES-256-GCM
     */
    private static function getSecretKey(): string {
        $key = getenv('AES_SECRET_KEY') ?: ($_ENV['AES_SECRET_KEY'] ?? null);
        if (!$key) {
            // Chave padrão simétrica de 32 bytes para dev/homologação (em prod deve vir do .env)
            $key = 'GestaoEPI_AES256SecretKey_2026!';
        }
        return pad_to_32_bytes($key);
    }

    /**
     * Criptografa dado sensível em AES-256-GCM (Reversível)
     * Retorna array com o dado cifrado em Base64, IV (12 bytes Base64) e Tag de autenticação (16 bytes Base64)
     */
    public static function encryptAES256GCM(string $plaintext): array {
        if (empty($plaintext)) {
            return ['enc' => null, 'iv' => null, 'tag' => null];
        }
        $key = self::getSecretKey();
        $iv = random_bytes(12); // 96 bits / 12 bytes IV conforme especificação GCM
        $tag = '';
        
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // 128 bits / 16 bytes Tag
        );

        return [
            'enc' => base64_encode($ciphertext),
            'iv'  => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }

    /**
     * Descriptografa dado sensível cifrado em AES-256-GCM
     */
    public static function decryptAES256GCM(?string $encBase64, ?string $ivBase64, ?string $tagBase64): ?string {
        if (empty($encBase64) || empty($ivBase64) || empty($tagBase64)) {
            return null;
        }
        $key = self::getSecretKey();
        $ciphertext = base64_decode($encBase64);
        $iv = base64_decode($ivBase64);
        $tag = base64_decode($tagBase64);

        $plaintext = openssl_decrypt(
            $ciphertext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return $plaintext !== false ? $plaintext : null;
    }

    /**
     * Gera Hash de Busca Indexada HMAC-SHA256 (para consultas no banco sem descriptografar a tabela toda)
     */
    public static function generateLookupHash(string $value): string {
        $cleanValue = preg_replace('/\D/', '', $value);
        $key = self::getSecretKey();
        return hash_hmac('sha256', $cleanValue, $key);
    }

    /**
     * Hashing de Senhas de Usuários do Sistema (Bcrypt - Cost 10)
     */
    public static function hashPassword(string $senha): string {
        return password_hash($senha, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Valida Senha do Usuário do Sistema
     */
    public static function verifyPassword(string $senhaDigitada, string $hashSalvo): bool {
        return password_verify($senhaDigitada, $hashSalvo);
    }

    /**
     * Hashing de PINs de Assinatura Eletrônica (Compatível com Android HashUtils.java)
     * Algoritmo: SHA-256(PIN + Salt) -> String hex minúscula de 64 caracteres
     */
    public static function hashPin(string $pin, ?string $salt = null): array {
        if (empty($salt)) {
            $salt = bin2hex(random_bytes(16)); // Salt hexadecimal de 16 bytes (32 caracteres)
        }
        $hashSHA256 = hash('sha256', $pin . $salt);
        return [
            'hash' => strtolower($hashSHA256),
            'salt' => $salt
        ];
    }

    /**
     * Valida PIN de Assinatura Eletrônica contra o hash e salt armazenados
     */
    public static function verifyPin(string $pinDigitado, string $hashSalvo, string $saltSalvo): bool {
        $calculatedHash = strtolower(hash('sha256', $pinDigitado . $saltSalvo));
        return hash_equals(strtolower($hashSalvo), $calculatedHash);
    }

    /**
     * Mascara o CPF para exibição padrão na interface Web (ex: ***.456.789-**)
     */
    public static function mascararCPF(?string $cpf): string {
        if (empty($cpf)) {
            return '---';
        }
        $clean = preg_replace('/\D/', '', $cpf);
        if (strlen($clean) !== 11) {
            return '***.***.***-**';
        }
        return '***.' . substr($clean, 3, 3) . '.' . substr($clean, 6, 3) . '-**';
    }

    /**
     * Verifica se o perfil do usuário logado possui permissão para visualizar dados sensíveis descriptografados
     */
    public static function podeVerDadosSensiveis(string $perfil): bool {
        $perfisAutorizados = ['ADMINISTRADOR', 'RH_ADMINISTRATIVO', 'TECNICO_SST'];
        return in_array(strtoupper($perfil), $perfisAutorizados, true);
    }

    /**
     * Helper/Middleware de Segurança e RBAC para controle de acesso por rotas/perfis
     */
    public static function checkAccess(array $perfisPermitidos): void {
        self::initSecureSession();

        if (!isset($_SESSION['usuario']) || empty($_SESSION['usuario']['usu_perfil'])) {
            http_response_code(401);
            if (self::isJsonRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Não autenticado (401)']);
            } else {
                $appRoot = defined('APP_ROOT') ? APP_ROOT : '/';
                header('Location: ' . $appRoot . 'login.php');
            }
            exit;
        }

        $userProfile = strtoupper($_SESSION['usuario']['usu_perfil']);
        $perfisPermitidosUpper = array_map('strtoupper', $perfisPermitidos);

        if (!in_array($userProfile, $perfisPermitidosUpper, true)) {
            http_response_code(403);
            if (self::isJsonRequest()) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => 'Acesso proibido (403 Forbidden)']);
            } else {
                $appRoot = defined('APP_ROOT') ? APP_ROOT : '/';
                header('Location: ' . $appRoot . 'pages/403.php');
            }
            exit;
        }
    }

    /**
     * Inicializa a Sessão PHP de forma segura com suporte a HTTPS e cookies HttpOnly + SameSite=Strict
     */
    public static function initSecureSession(): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ||
                        (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            session_start();

            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }

    private static function isJsonRequest(): bool {
        return (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json')) ||
               (isset($_SERVER['CONTENT_TYPE']) && str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
    }
}

/**
 * Função utilitária local para garantir chave de 32 bytes
 */
function pad_to_32_bytes(string $key): string {
    if (strlen($key) >= 32) {
        return substr($key, 0, 32);
    }
    return str_pad($key, 32, "\0");
}
