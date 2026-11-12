<?php
namespace App\Models;

use Core\Model;

/** Pedidos de recuperação de senha; a coluna token guarda o hash. */
class RecuperacaoSenha extends Model
{
    /** Novo token; apaga os anteriores do usuário. */
    public static function criar(int $usuarioId, string $hashToken, int $minutos): void
    {
        self::execute('DELETE FROM recuperacoes_senha WHERE usuario_id = ?', [$usuarioId]);
        self::execute(
            'INSERT INTO recuperacoes_senha (usuario_id, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))',
            [$usuarioId, $hashToken, $minutos]
        );
    }

    public static function buscarValido(string $hashToken): ?array
    {
        return self::fetchOne(
            'SELECT id, usuario_id FROM recuperacoes_senha WHERE token = ? AND expira_em > NOW()',
            [$hashToken]
        );
    }

    public static function apagarDoUsuario(int $usuarioId): void
    {
        self::execute('DELETE FROM recuperacoes_senha WHERE usuario_id = ?', [$usuarioId]);
    }
}
