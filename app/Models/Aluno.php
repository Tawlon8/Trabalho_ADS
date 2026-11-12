<?php
namespace App\Models;

use Core\Model;

class Aluno extends Model
{
    /** Lista só os alunos do professor logado, com busca por nome. */
    public static function listarDoUsuario(int $usuarioId, string $busca): array
    {
        $params = [$usuarioId];
        $filtro = '';
        if ($busca !== '') {
            $filtro   = 'AND a.nome LIKE ?';
            $params[] = '%' . addcslashes($busca, '%_\\') . '%';
        }
        return self::fetchAll(
            "SELECT a.id, a.nome, a.data_nascimento, t.nome AS tipo_nome,
                    (SELECT COUNT(*) FROM acompanhamentos c WHERE c.aluno_id = a.id) AS total_registros
               FROM alunos a
               LEFT JOIN tipos_deficiencia t ON t.id = a.tipo_deficiencia_id
              WHERE a.usuario_id = ? $filtro
              ORDER BY a.nome",
            $params
        );
    }

    /** Busca o aluno se ele for do professor. */
    public static function buscarDoUsuario(int $id, int $usuarioId): ?array
    {
        return self::fetchOne(
            'SELECT a.*, t.nome AS tipo_nome
               FROM alunos a
               LEFT JOIN tipos_deficiencia t ON t.id = a.tipo_deficiencia_id
              WHERE a.id = ? AND a.usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    public static function contarDoUsuario(int $usuarioId): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM alunos WHERE usuario_id = ?', [$usuarioId]);
    }

    public static function criar(array $d, int $usuarioId): int
    {
        self::execute(
            'INSERT INTO alunos (usuario_id, nome, data_nascimento, tipo_deficiencia_id, observacoes, consentimento)
             VALUES (?, ?, ?, ?, ?, 1)',
            [
                $usuarioId, $d['nome'], self::nulo($d['data_nascimento']),
                self::nulo($d['tipo_deficiencia_id']) === null ? null : (int) $d['tipo_deficiencia_id'],
                self::nulo($d['observacoes']),
            ]
        );
        return self::lastId();
    }

    public static function atualizar(int $id, array $d): void
    {
        self::execute(
            'UPDATE alunos SET nome = ?, data_nascimento = ?, tipo_deficiencia_id = ?, observacoes = ?, consentimento = 1
              WHERE id = ?',
            [
                $d['nome'], self::nulo($d['data_nascimento']),
                self::nulo($d['tipo_deficiencia_id']) === null ? null : (int) $d['tipo_deficiencia_id'],
                self::nulo($d['observacoes']), $id,
            ]
        );
    }

    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM alunos WHERE id = ?', [$id]);
    }
}
