<?php
namespace App\Models;

use Core\Model;

/** Resultado de uma sessão de mini games numa meta (acertos, erros, dicas e tempo). */
class Acompanhamento extends Model
{
    /** Níveis de suporte, com os nomes usados no AppPEI. */
    const NIVEIS = [
        'nao_realiza' => 'Não realiza',
        'com_ajuda'   => 'Realiza com ajuda',
        'sem_suporte' => 'Realiza sem necessidade de suporte',
    ];

    /** Todos os registros de um aluno, do mais recente para o mais antigo. */
    public static function listarDoAluno(int $alunoId): array
    {
        return self::fetchAll(
            'SELECT c.id, c.data_aplicacao, c.desempenho, c.observacao,
                    c.rodadas, c.acertos, c.dicas, c.tempo_segundos,
                    c.atividade_id, a.titulo AS atividade_titulo,
                    c.meta_id, m.descricao AS meta_descricao
               FROM acompanhamentos c
               LEFT JOIN atividades a ON a.id = c.atividade_id
               LEFT JOIN metas m ON m.id = c.meta_id
              WHERE c.aluno_id = ?
              ORDER BY c.data_aplicacao DESC, c.id DESC',
            [$alunoId]
        );
    }

    /** Busca o registro se o aluno for do professor. */
    public static function buscarDoUsuario(int $id, int $usuarioId): ?array
    {
        return self::fetchOne(
            'SELECT c.id, c.aluno_id
               FROM acompanhamentos c
               JOIN alunos al ON al.id = c.aluno_id
              WHERE c.id = ? AND al.usuario_id = ?',
            [$id, $usuarioId]
        );
    }

    /** Registros por nível de suporte (inclui os níveis com zero). */
    public static function resumoDoAluno(int $alunoId): array
    {
        $resumo = array_fill_keys(array_keys(self::NIVEIS), 0);
        $linhas = self::fetchAll(
            'SELECT desempenho, COUNT(*) AS total FROM acompanhamentos WHERE aluno_id = ? GROUP BY desempenho',
            [$alunoId]
        );
        foreach ($linhas as $l) {
            $resumo[$l['desempenho']] = (int) $l['total'];
        }
        return $resumo;
    }

    /** Grava o resultado da sessão numa meta. */
    public static function registrarSessao(int $alunoId, int $metaId, int $atividadeId, array $r): int
    {
        self::execute(
            "INSERT INTO acompanhamentos
                (aluno_id, meta_id, atividade_id, origem, data_aplicacao, desempenho,
                 rodadas, acertos, dicas, erros, tempo_segundos, observacao)
             VALUES (?, ?, ?, 'sessao', ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $alunoId,
                $metaId,
                $atividadeId,
                $r['data_aplicacao'],
                $r['desempenho'],
                $r['rodadas'],
                $r['acertos'],
                $r['dicas'],
                $r['erros'],
                $r['tempo_segundos'],
                self::nulo($r['observacao']),
            ]
        );
        return self::lastId();
    }

    /** Sessões de uma meta, em ordem cronológica. */
    public static function sessoesDaMeta(int $metaId, int $alunoId): array
    {
        return self::fetchAll(
            "SELECT id, data_aplicacao, desempenho, rodadas, acertos, dicas, erros
               FROM acompanhamentos
              WHERE meta_id = ? AND aluno_id = ? AND origem = 'sessao'
              ORDER BY data_aplicacao ASC, id ASC",
            [$metaId, $alunoId]
        );
    }

    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM acompanhamentos WHERE id = ?', [$id]);
    }
}
