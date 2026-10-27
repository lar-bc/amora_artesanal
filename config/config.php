<?php
/**
 * Amora Artesanal - Vitrine Virtual & E-commerce
 * Arquivo de Configuração Global
 */

// Iniciar sessão se ainda não iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configurações de Fuso Horário e Erros
date_default_timezone_set('America/Porto_Velho');
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Nome da aplicação
define('APP_NAME', 'Amora Artesanal');
define('APP_TAGLINE', 'Presentes e Produtos Feitos à Mão');

// URL Base da aplicação (detecta dinamicamente ou define raiz)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$base_path = ($script_dir === '/' || $script_dir === '\\') ? '' : rtrim(str_replace('\\', '/', $script_dir), '/');
// Se estiver rodando em subpasta como /admin, ajusta para a raiz do projeto
if (str_ends_with($base_path, '/admin')) {
    $base_path = substr($base_path, 0, -6);
}
define('BASE_URL', $protocol . $host . $base_path);

// Caminho absoluto da raiz do projeto
define('ROOT_PATH', dirname(__DIR__));

// Configurações do Banco de Dados MySQL (padrão XAMPP)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'amora_artesanal');
define('DB_USER', 'root');
define('DB_PASS', '');

// Arquivo de fallback para SQLite (permite rodar perfeitamente sem MySQL externo ativo)
define('SQLITE_FILE', ROOT_PATH . '/database/amora_artesanal.sqlite');

// Localização Física Oficial do Ateliê Amora Artesanal (Confirmada na vida real - IFRO Calama)
define('ATELIE_ENDERECO', 'Av. Calama, 4985');
define('ATELIE_COMPLEMENTO', 'IFRO Campus Porto Velho Calama');
define('ATELIE_BAIRRO', 'Flodoaldo Pontes Pinto');
define('ATELIE_CIDADE', 'Porto Velho');
define('ATELIE_ESTADO', 'RO');
define('ATELIE_CEP', '76820-441');
define('ATELIE_LAT', -8.7441684);
define('ATELIE_LNG', -63.8718575);

