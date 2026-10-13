<?php
/**
 * Conexão com Banco de Dados usando PDO
 * Suporta MySQL nativamente e SQLite para execução local autônoma.
 */
require_once __DIR__ . '/config.php';

function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $driver = getenv('DB_DRIVER') ?: 'auto';

    // Tentativa 1: Conectar ao MySQL se configurado ou em modo auto
    if ($driver === 'mysql' || $driver === 'auto') {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // Se o modo for estritamente MySQL, repassar o erro
            if ($driver === 'mysql') {
                die("Erro de conexão com o banco de dados MySQL: " . $e->getMessage());
            }
            // Em modo 'auto', fallback automático para SQLite
        }
    }

    // Tentativa 2: SQLite
    try {
        $sqliteDir = dirname(SQLITE_FILE);
        if (!is_dir($sqliteDir)) {
            mkdir($sqliteDir, 0777, true);
        }

        $dsn = 'sqlite:' . SQLITE_FILE;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];
        $pdo = new PDO($dsn, null, null, $options);
        // Habilitar foreign keys no SQLite
        $pdo->exec('PRAGMA foreign_keys = ON;');
        return $pdo;
    } catch (PDOException $e) {
        die("Erro de conexão com o banco de dados: " . $e->getMessage());
    }
}
