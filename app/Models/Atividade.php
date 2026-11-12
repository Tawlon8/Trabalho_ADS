<?php
namespace App\Models;

use Core\Model;

/** Atividade do catálogo; os tipos de deficiência ficam em atividade_tipos. */
class Atividade extends Model
{
    /** Subconsulta com os nomes dos tipos da atividade "a". */
    private static function sqlTiposNomes(): string
    {
        return "(SELECT GROUP_CONCAT(t.nome ORDER BY t.id SEPARATOR ', ')
                   FROM atividade_tipos x
                   JOIN tipos_deficiencia t ON t.id = x.tipo_deficiencia_id
                  WHERE x.atividade_id = a.id)";
    }

    /** Lista com busca por texto, filtro por tipo e por área, e paginação. */
    public static function listar(string $busca, int $tipoId, string $area, int $pagina, int $porPagina): array
    {
        $where  = [];
        $params = [];

        if ($busca !== '') {
            $like    = '%' . addcslashes($busca, '%_\\') . '%';
            $where[] = '(a.titulo LIKE ? OR a.descricao LIKE ? OR a.objetivo LIKE ?)';
            array_push($params, $like, $like, $like);
        }
        if ($tipoId > 0) {
            $where[]  = 'EXISTS (SELECT 1 FROM atividade_tipos x WHERE x.atividade_id = a.id AND x.tipo_deficiencia_id = ?)';
            $params[] = $tipoId;
        }
        if ($area !== '') {
            $where[]  = 'a.area = ?';
            $params[] = $area;
        }
        $filtro = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $total  = (int) self::fetchValue("SELECT COUNT(*) FROM atividades a $filtro", $params);
        $limite = max(1, $porPagina);
        $inicio = max(0, ($pagina - 1) * $limite);

        $itens = self::fetchAll(
            'SELECT a.id, a.titulo, a.descricao, a.imagem, a.imagem_alt, a.area, a.formato, '
            . self::sqlTiposNomes() . " AS tipos_nomes
               FROM atividades a
               $filtro
              ORDER BY a.titulo
              LIMIT $limite OFFSET $inicio",
            $params
        );
        return ['itens' => $itens, 'total' => $total];
    }

    public static function recentes(int $quantidade): array
    {
        $quantidade = max(1, $quantidade);
        return self::fetchAll("SELECT a.id, a.titulo FROM atividades a ORDER BY a.id DESC LIMIT $quantidade");
    }

    /** Atividades que podem ser feitas na tela (têm formato definido). */
    public static function executaveis(): array
    {
        return self::fetchAll(
            'SELECT a.id, a.titulo, a.area, a.formato
               FROM atividades a
              WHERE a.formato IS NOT NULL
              ORDER BY a.area, a.titulo'
        );
    }

    /** Tipos de deficiência da atividade (id e nome). */
    public static function tipos(int $id): array
    {
        return self::fetchAll(
            'SELECT t.id, t.nome
               FROM atividade_tipos x
               JOIN tipos_deficiencia t ON t.id = x.tipo_deficiencia_id
              WHERE x.atividade_id = ?
              ORDER BY t.id',
            [$id]
        );
    }

    /** Atividades da área da meta, ainda não aplicadas ao aluno e compatíveis com o tipo dele. */
    public static function sugeridasParaMeta(int $metaId, int $alunoId, int $limite = 6): array
    {
        return self::fetchAll(
            'SELECT a.id, a.titulo, a.formato
               FROM metas m
               JOIN alunos al ON al.id = m.aluno_id
               JOIN atividades a ON a.area = m.area
              WHERE m.id = ?
                AND (al.tipo_deficiencia_id IS NULL OR EXISTS (
                        SELECT 1 FROM atividade_tipos x
                         WHERE x.atividade_id = a.id AND x.tipo_deficiencia_id = al.tipo_deficiencia_id))
                AND NOT EXISTS (
                        SELECT 1 FROM acompanhamentos c
                         WHERE c.atividade_id = a.id AND c.aluno_id = ?)
              ORDER BY a.titulo
              LIMIT ' . max(1, $limite),
            [$metaId, $alunoId]
        );
    }

    public static function buscar(int $id): ?array
    {
        return self::fetchOne(
            'SELECT a.*, u.nome AS autor_nome
               FROM atividades a
               LEFT JOIN usuarios u ON u.id = a.usuario_id
              WHERE a.id = ?',
            [$id]
        );
    }

    /** $d['tipos'] é a lista de ids dos tipos de deficiência da atividade. */
    public static function criar(array $d, int $usuarioId): int
    {
        self::execute(
            'INSERT INTO atividades
                (titulo, descricao, objetivo, materiais, area, imagem, imagem_alt, video, legenda, transcricao, fonte, formato, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $d['titulo'], $d['descricao'], self::nulo($d['objetivo']), self::nulo($d['materiais']),
                self::nulo($d['area']), self::nulo($d['imagem']), self::nulo($d['imagem_alt']),
                self::nulo($d['video']), self::nulo($d['legenda']), self::nulo($d['transcricao']),
                $d['fonte'], self::nulo($d['formato'] ?? ''), $usuarioId,
            ]
        );
        $id = self::lastId();
        self::sincronizarTipos($id, $d['tipos']);
        return $id;
    }

    public static function atualizar(int $id, array $d): void
    {
        self::execute(
            'UPDATE atividades
                SET titulo = ?, descricao = ?, objetivo = ?, materiais = ?, area = ?,
                    imagem = ?, imagem_alt = ?, video = ?, legenda = ?, transcricao = ?, fonte = ?,
                    formato = ?
              WHERE id = ?',
            [
                $d['titulo'], $d['descricao'], self::nulo($d['objetivo']), self::nulo($d['materiais']),
                self::nulo($d['area']), self::nulo($d['imagem']), self::nulo($d['imagem_alt']),
                self::nulo($d['video']), self::nulo($d['legenda']), self::nulo($d['transcricao']),
                $d['fonte'], self::nulo($d['formato'] ?? ''), $id,
            ]
        );
        self::sincronizarTipos($id, $d['tipos']);
    }

    /** Substitui os vínculos da atividade pela lista de tipos informada. */
    private static function sincronizarTipos(int $id, array $tipos): void
    {
        self::execute('DELETE FROM atividade_tipos WHERE atividade_id = ?', [$id]);
        foreach ($tipos as $tipoId) {
            self::execute(
                'INSERT INTO atividade_tipos (atividade_id, tipo_deficiencia_id) VALUES (?, ?)',
                [$id, (int) $tipoId]
            );
        }
    }

    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM atividades WHERE id = ?', [$id]);
    }
}
