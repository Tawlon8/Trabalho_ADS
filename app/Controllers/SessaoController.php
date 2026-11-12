<?php
namespace App\Controllers;

use App\Models\Acompanhamento;
use App\Models\Aluno;
use App\Models\Meta;
use App\Services\SessaoAtividade;
use Core\Auth;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;

/**
 * Sessão de jogos na tela: as respostas são conferidas no servidor e, no fim, gravadas
 * como um registro de acompanhamento por meta. Estado em $_SESSION['_sessao'].
 * Fluxo: iniciar() -> mostrar() -> responder() -> concluir().
 */
class SessaoController extends Controller
{
    private const CHAVE = '_sessao';

    /** GET /alunos/{id}/sessao?atividades[]=... ou ?atividade=N&meta=M */
    public function iniciar(string $id): void
    {
        $alunoId = (int) $id;
        if (!Aluno::buscarDoUsuario($alunoId, Auth::id())) {
            $this->erro(404, 'Aluno não encontrado.');
        }

        $ids = Request::ints('atividades');
        if (!$ids) {
            $um = (int) Request::get('atividade', '0');
            if ($um > 0) {
                $ids = [$um];
            }
        }
        if (!$ids) {
            Session::flash('erro', 'Escolha pelo menos um jogo para iniciar a sessão.');
            redirect("alunos/$alunoId");
        }

        $metaPorArea = $this->metasPorArea($alunoId);

        $rodadas = (int) Config::get('sessao.rodadas');
        $maximo  = (int) Config::get('sessao.maximo');

        $itens   = [];
        $jogos   = [];
        $semMeta = [];

        foreach ($ids as $idAtividade) {
            $atividade = SessaoAtividade::atividadeExecutavel($idAtividade);
            if (!$atividade) {
                continue;
            }

            $metaId = $metaPorArea[$atividade['area'] ?? ''] ?? null;
            if (!$metaId) {
                $semMeta[] = $atividade['titulo'];
                continue;
            }

            $parte = SessaoAtividade::montar($atividade, $rodadas, $maximo);
            if (!$parte) {
                continue;
            }

            foreach ($parte as $item) {
                $item['meta_id']      = $metaId;
                $item['atividade_id'] = (int) $atividade['id'];
                $item['jogo']         = $atividade['titulo'];
                $itens[] = $item;
            }
            $jogos[] = $atividade['titulo'];
        }

        if (!$itens) {
            Session::flash(
                'erro',
                $semMeta
                    ? 'O aluno não tem meta nas áreas destes jogos: ' . implode(', ', $semMeta)
                      . '. Crie a meta antes de iniciar a sessão.'
                    : 'Nenhum dos jogos escolhidos pode ser feito na tela.'
            );
            redirect("alunos/$alunoId");
        }

        if ($semMeta) {
            Session::flash(
                'erro',
                'Fora da sessão por falta de meta na área: ' . implode(', ', $semMeta) . '.'
            );
        }

        $_SESSION[self::CHAVE] = [
            'aluno_id'          => $alunoId,
            'jogos'             => $jogos,
            'itens'             => $itens,
            'indice'            => 0,
            'acertos'           => 0,
            'dicas'             => 0,
            'dica_nesta_rodada' => false,
            'respostas'         => [],
            'ultimo'            => time(),   // base do tempo de resposta
        ];
        redirect('sessao');
    }

    /** GET /sessao : mostra a rodada atual. */
    public function mostrar(): void
    {
        $estado = $this->estado();
        $item   = $estado['itens'][$estado['indice']];
        $dica   = $estado['dica_nesta_rodada'];

        $this->view('sessao/mostrar', [
            'titulo'    => count($estado['jogos']) > 1 ? 'Sessão de jogos' : $estado['jogos'][0],
            'jogo'      => $item['jogo'] ?? '',
            'numero'    => $estado['indice'] + 1,
            'total'     => count($estado['itens']),
            'item'      => $item,
            'visiveis'  => SessaoAtividade::opcoesVisiveis($item, $dica),
            'dicaUsada' => $dica,
            'temDica'   => SessaoAtividade::temDica($item),
            'feedback'  => $estado['feedback'] ?? null,
        ], 'layouts/jogo');   // layout sem menu
    }

    /** POST /sessao/responder : confere a resposta e avança. */
    public function responder(): void
    {
        $estado  = $this->estado();
        $item    = $estado['itens'][$estado['indice']];
        $escolha = filter_var(Request::str('resposta'), FILTER_VALIDATE_INT);

        $validas = SessaoAtividade::opcoesVisiveis($item, $estado['dica_nesta_rodada']);
        if ($escolha === false || !in_array($escolha, $validas, true)) {
            Session::flash('erro', 'Escolha uma das opções antes de responder.');
            redirect('sessao');
        }

        $agora   = time();
        $acertou = ($escolha === $item['correta']);
        if ($acertou) {
            $estado['acertos']++;
        }

        $estado['respostas'][] = [
            'meta_id'      => (int) $item['meta_id'],
            'atividade_id' => (int) $item['atividade_id'],
            'jogo'         => (string) $item['jogo'],
            'etapa'        => $item['etapa'] ?? null,
            'acertou'      => $acertou,
            'dica'         => (bool) $estado['dica_nesta_rodada'],
            'segundos'     => max(0, $agora - (int) $estado['ultimo']),
        ];

        $estado['feedback'] = [
            'acertou' => $acertou,
            'texto'   => $acertou ? 'Acertou!' : 'Dessa vez não. Vamos para a próxima.',
        ];
        $estado['ultimo']            = $agora;
        $estado['indice']++;
        $estado['dica_nesta_rodada'] = false;

        if ($estado['indice'] >= count($estado['itens'])) {
            $this->concluir($estado);   // grava e redireciona
        }

        $_SESSION[self::CHAVE] = $estado;
        redirect('sessao');
    }

    /** POST /sessao/dica : conta uma dica por rodada. */
    public function dica(): void
    {
        $estado = $this->estado();
        $item   = $estado['itens'][$estado['indice']];
        if (!$estado['dica_nesta_rodada'] && SessaoAtividade::temDica($item)) {
            $estado['dica_nesta_rodada'] = true;
            $estado['dicas']++;
            $_SESSION[self::CHAVE] = $estado;
        }
        redirect('sessao');
    }

    /** POST /sessao/cancelar : encerra sem registrar nada. */
    public function cancelar(): void
    {
        $alunoId = $_SESSION[self::CHAVE]['aluno_id'] ?? null;
        unset($_SESSION[self::CHAVE]);
        Session::flash('sucesso', 'Sessão cancelada. Nenhum resultado foi registrado.');
        redirect($alunoId ? "alunos/$alunoId" : '');
    }

    // ------------------------------------------------------------------

    /** Primeira meta do aluno em cada área; a meta de ?meta= tem prioridade na área dela. */
    private function metasPorArea(int $alunoId): array
    {
        $porArea = array_map(
            fn(array $m) => (int) $m['id'],
            SessaoAtividade::metaPorArea(Meta::listarDoAluno($alunoId))
        );

        $escolhida = (int) Request::get('meta', '0');
        if ($escolhida > 0) {
            $meta = Meta::buscarDoAluno($escolhida, $alunoId);
            if ($meta) {
                $porArea[$meta['area']] = (int) $meta['id'];
            }
        }
        return $porArea;
    }

    private function estado(): array
    {
        $estado = $_SESSION[self::CHAVE] ?? null;
        if (!is_array($estado) || empty($estado['itens'])) {
            Session::flash('erro', 'Nenhuma sessão em andamento.');
            redirect('');
        }
        if (!Aluno::buscarDoUsuario((int) $estado['aluno_id'], Auth::id())) {
            unset($_SESSION[self::CHAVE]);
            redirect('');
        }
        return $estado;
    }

    /** Agrupa as respostas por meta e grava um registro para cada uma. */
    private function concluir(array $estado): void
    {
        $gravados = 0;
        foreach (SessaoAtividade::agrupar($estado['respostas']) as $metaId => $g) {
            Acompanhamento::registrarSessao($estado['aluno_id'], $metaId, $g['atividade_id'], [
                'data_aplicacao' => date('Y-m-d'),
                'desempenho'     => SessaoAtividade::classificar($g['acertos'], $g['rodadas'], $g['dicas']),
                'rodadas'        => $g['rodadas'],
                'acertos'        => $g['acertos'],
                'dicas'          => $g['dicas'],
                'erros'          => $g['rodadas'] - $g['acertos'],
                'tempo_segundos' => $g['segundos'],
                'observacao'     => SessaoAtividade::detalhar($g),
            ]);
            $gravados++;
        }

        $total = count($estado['itens']);
        unset($_SESSION[self::CHAVE]);
        Session::flash(
            'sucesso',
            'Sessão concluída: ' . $estado['acertos'] . ' de ' . $total . ' acertos, com '
            . $estado['dicas'] . ' dica(s). ' . $gravados . ' registro(s) gravado(s), um por meta.'
        );
        redirect('alunos/' . $estado['aluno_id']);
    }

}
