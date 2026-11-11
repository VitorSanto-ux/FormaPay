<?php
declare(strict_types=1);

class Database
{
    public static function conectar(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $nome = getenv('DB_NAME') ?: 'formapay';
        $usuario = getenv('DB_USER') ?: 'root';
        $senha = getenv('DB_PASS') ?: '';
        $pdo = new PDO("mysql:host={$host};dbname={$nome};charset=utf8mb4", $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $coluna = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'perfil'")->fetch();
        if (!$coluna) {
            $pdo->exec("ALTER TABLE usuarios ADD perfil ENUM('comissao','participante') NOT NULL DEFAULT 'comissao'");
        }
        if (!$pdo->query("SHOW COLUMNS FROM usuarios LIKE 'foto_perfil'")->fetch()) {
            $pdo->exec('ALTER TABLE usuarios ADD foto_perfil VARCHAR(255) NULL');
        }
        return $pdo;
    }
}
