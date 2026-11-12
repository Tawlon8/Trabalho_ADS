<?php
namespace Core;

use PDO;

/** Base dos Models: métodos auxiliares para executar SQL com prepared statements. */
abstract class Model
{
    protected static function fetchAll(string $sql, array $params = []): array
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    protected static function fetchOne(string $sql, array $params = []): ?array
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        $linha = $st->fetch();
        return $linha ? $linha : null;
    }

    protected static function fetchValue(string $sql, array $params = [])
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    /** Executa INSERT/UPDATE/DELETE e devolve o número de linhas afetadas. */
    protected static function execute(string $sql, array $params = []): int
    {
        $st = Database::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }

    protected static function lastId(): int
    {
        return (int) Database::pdo()->lastInsertId();
    }

    /** Converte texto vazio em NULL (para colunas opcionais). */
    protected static function nulo($valor)
    {
        return ($valor === '' || $valor === null) ? null : $valor;
    }
}
