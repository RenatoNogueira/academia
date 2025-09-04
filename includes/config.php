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