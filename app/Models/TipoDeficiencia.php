<?php
namespace App\Models;

use Core\Model;

class TipoDeficiencia extends Model
{
    /** Ícones permitidos (Font Awesome) para escolher no cadastro do tipo. */
    const ICONES = [
        'fa-dumbbell'     => 'Haltere (motora)',
        'fa-brain'        => 'Cérebro (intelectual)',
        'fa-deaf'         => 'Ouvido (auditiva)',
        'fa-low-vision'   => 'Olho (visual)',
        'fa-puzzle-piece' => 'Peça de quebra-cabeça (TEA)',
        'fa-users'        => 'Pessoas (múltipla)',
        'fa-question'     => 'Interrogação (outra)',
        'fa-hands'        => 'Mãos',
        'fa-child'        => 'Criança',
        'fa-heart'        => 'Coração',
    ];

    public static function listar(): array
    {
        return self::fetchAll('SELECT id, nome, descricao, icone FROM tipos_deficiencia ORDER BY id');
    }

    /** Tipos com a quantidade de atividades de cada um. */
    public static function comContagem(): array
    {
        return self::fetchAll(
            'SELECT t.id, t.nome, t.descricao, t.icone, COUNT(a.id) AS total_atividades
               FROM tipos_deficiencia t
               LEFT JOIN atividade_tipos x ON x.tipo_deficiencia_id = t.id
               LEFT JOIN atividades a ON a.id = x.atividade_id
              GROUP BY t.id, t.nome, t.descricao, t.icone
              ORDER BY t.id'
        );
    }

    public static function buscar(int $id): ?array
    {
        return self::fetchOne('SELECT id, nome, descricao, icone FROM tipos_deficiencia WHERE id = ?', [$id]);
    }

    public static function criar(array $d): int
    {
        self::execute(
            'INSERT INTO tipos_deficiencia (nome, descricao, icone) VALUES (?, ?, ?)',
            [$d['nome'], self::nulo($d['descricao']), $d['icone']]
        );
        return self::lastId();
    }

    public static function atualizar(int $id, array $d): void
    {
        self::execute(
            'UPDATE tipos_deficiencia SET nome = ?, descricao = ?, icone = ? WHERE id = ?',
            [$d['nome'], self::nulo($d['descricao']), $d['icone'], $id]
        );
    }

    public static function excluir(int $id): void
    {
        self::execute('DELETE FROM tipos_deficiencia WHERE id = ?', [$id]);
    }

    /** Quantas atividades usam este tipo (impede exclusão de tipo em uso). */
    public static function totalAtividades(int $id): int
    {
        return (int) self::fetchValue('SELECT COUNT(*) FROM atividade_tipos WHERE tipo_deficiencia_id = ?', [$id]);
    }
}
