<?php
namespace Core;

use PDO;
use PDOException;
use RuntimeException;

/** Conexão única (PDO) com o banco de dados. */
class Database
{
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c   = Config::get('db');
            $porta = (int) ($c['port'] ?? 3306);
            $dsn = "mysql:host={$c['host']};port={$porta};dbname={$c['name']};charset={$c['charset']}";
            try {
                self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('Erro de conexão com o banco: ' . $e->getMessage());
                throw new RuntimeException('Não foi possível conectar ao banco de dados.');
            }
        }
        return self::$pdo;
    }
}
