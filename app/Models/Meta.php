<?php
namespace App\Models;

use Core\Model;

/** Meta do PEI. O nível atual é o do registro mais recente da meta. */
class Meta extends Model
{
    /** Metas de um aluno, com o nível atual e o número de registros de cada uma. */
    public static function listarDoAluno(int $alunoId): array
    {
        return self::fetchAll(
            'SELECT m.id, m.area, m.descricao, m.nivel_inicial, m.professor_responsavel, m.revisar_em,
                    (SELECT c.desempenho FROM acompanhamentos c
                      WHERE c.meta_id = m.id
                      ORDER BY c.data_aplicacao DESC, c.id DESC
                      LIMIT 1) AS nivel_atual,
                    (SELECT COUNT(*) FROM acompanhamentos c WHERE c.meta_id = m.id) AS registros
               FROM metas m
              WHERE m.aluno_id = ?
              ORDER BY m.area, m.id',
            [$alunoId]
        );
    }

    /** Busca a meta se o aluno for do professor. */
    public static function buscarDoUsuario(int $id, int $usuarioId): ?array
    {
        return self::fetchOne(
            'SELECT m.*, al.nome AS aluno_nome
               FROM metas m
               JOIN alunos al ON al.id = m.aluno_id
              WHERE m.id = ? AND al.usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    /** Busca a meta se ela for do aluno. */
    public static function buscarDoAluno(int $id, int $alunoId): ?array
    {
        return self::fetchOne('SELECT * FROM metas WHERE id = ? AND aluno_id = ?', [$id, $alunoId]);
    }

    public static function criar(int $alunoId, array $d): int
    {
        self::execute(
            'INSERT INTO metas (aluno_id, area, descricao, nivel_inicial, professor_responsavel, revisar_em)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $alunoId,
                $d['area'],
                $d['descricao'],
                $d['nivel_inicial'],
                self::nulo($d['professor_responsavel']),
                self::nulo($d['revisar_em']),
            ]
        );
        return self::lastId();
    }

    public static function atualizar(int $id, array $d): void
    {
        self::execute(
            'UPDATE metas
                SET area = ?, descricao = ?, nivel_inicial = ?, professor_responsavel = ?, revisar_em = ?
              WHERE id = ?',
            [
                $d['area'],
                $d['descricao'],
                $d['nivel_inicial'],
                self::nulo($d['professor_responsavel']),
                self::nulo($d['revisar_em']),
                $id,
            ]
        );
    }

    /** Os registros da meta ficam no histórico (ON DELETE SET NULL). */
    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM metas WHERE id = ?', [$id]);
    }

    /** Metas do professor com revisão vencida. */
    public static function vencidasDoUsuario(int $usuarioId, string $hoje): int
    {
        return (int) self::fetchValue(
            'SELECT COUNT(*)
               FROM metas m
               JOIN alunos al ON al.id = m.aluno_id
              WHERE al.usuario_id = ? AND m.revisar_em IS NOT NULL AND m.revisar_em <= ?',
            [$usuarioId, $hoje]
        );
    }
}
