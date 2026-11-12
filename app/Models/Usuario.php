<?php
namespace App\Models;

use Core\Model;

class Usuario extends Model
{
    public static function buscar(int $id): ?array
    {
        return self::fetchOne('SELECT id, nome, email, senha, perfil FROM usuarios WHERE id = ?', [$id]);
    }

    public static function buscarPorEmail(string $email): ?array
    {
        return self::fetchOne('SELECT id, nome, email, senha, perfil FROM usuarios WHERE email = ?', [$email]);
    }

    public static function criar(string $nome, string $email, string $hashSenha): int
    {
        self::execute(
            "INSERT INTO usuarios (nome, email, senha, perfil) VALUES (?, ?, ?, 'professor')",
            [$nome, $email, $hashSenha]
        );
        return self::lastId();
    }

    public static function atualizarDados(int $id, string $nome, string $email): void
    {
        self::execute('UPDATE usuarios SET nome = ?, email = ? WHERE id = ?', [$nome, $email, $id]);
    }

    public static function atualizarSenha(int $id, string $hashSenha): void
    {
        self::execute('UPDATE usuarios SET senha = ? WHERE id = ?', [$hashSenha, $id]);
    }

    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM usuarios WHERE id = ?', [$id]);
    }
}
