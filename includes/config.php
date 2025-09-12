<?php
// Configurações básicas
define('APP_NAME', 'Sistema de Avaliações Físicas');
define('APP_VERSION', '1.0.0');
define('APP_DEBUG', true);

// Definir a URL base do sistema
define('BASE_URL', '/DRM/'); // Ajuste conforme o diretório raiz do seu projeto

// Configurações de sessão
session_start();

// Configurações de timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de upload
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_FILE_TYPES', ['image/jpeg', 'image/png', 'image/gif']);

// PROTEÇÃO DAS PASTAS - Adicionar arquivos .htaccess automaticamente
function protegerPastas($pastas = ['../uploads', '../config', '../includes', '../assets', '../pages'])
{
    foreach ($pastas as $pasta) {
        $htaccessFile = $pasta . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $conteudo = "Order Deny,Allow\nDeny from all\n";
            file_put_contents($htaccessFile, $conteudo);
        }

        // Adicionar também arquivo index.html vazio para prevenir listagem
        $indexFile = $pasta . '/index.html';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Access Forbidden</h1></body></html>');
        }
    }
}

// Executar proteção das pastas (comente após primeira execução se necessário)
// protegerPastas();

// Função para verificar acesso direto a arquivos
function verificarAcessoDireto()
{
    if (php_sapi_name() !== 'cli-server' && !defined('ROOT_ACCESS')) {
        $scriptName = basename($_SERVER['SCRIPT_FILENAME']);
        if (
            $scriptName == 'config.php' ||
            $scriptName == 'functions.php' ||
            preg_match('/\.inc\.php$/', $scriptName)
        ) {
            // Redirecionar para index.php em vez de mostrar erro
            header('Location: ' . BASE_URL . 'index.php');
            exit();
        }
    }
}

// Verificar acesso direto a arquivos sensíveis
verificarAcessoDireto();

// Função para debug
function debug($data)
{
    if (APP_DEBUG) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

// Função para redirecionamento
function redirect($url)
{
    header("Location: " . BASE_URL . $url);
    exit();
}

// Função para sanitizar dados
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Proteção contra XSS
$_GET = array_map('sanitize', $_GET);
$_POST = array_map('sanitize', $_POST);
$_REQUEST = array_map('sanitize', $_REQUEST);

// Função para obter a URL base completa
function base_url($path = '')
{
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . '://' . $host . BASE_URL . $path;
}