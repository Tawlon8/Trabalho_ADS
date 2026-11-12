<?php
namespace App\Controllers;

use App\Models\Acompanhamento;
use App\Models\Aluno;
use App\Models\Meta;
use Core\Auth;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Validator;

/** Metas do PEI dos alunos do professor logado. */
class MetaController extends Controller
{
    private const CAMPOS = ['area', 'descricao', 'nivel_inicial', 'professor_responsavel', 'revisar_em'];

    public function criar(string $alunoId): void
    {
        $aluno = $this->alunoDoUsuario((int) $alunoId);
        $this->view('metas/form', [
            'titulo' => 'Nova meta do PEI',
            'aluno'  => $aluno,
            'meta'   => null,
        ]);
    }

    public function salvar(string $alunoId): void
    {
        $aluno  = $this->alunoDoUsuario((int) $alunoId);
        $alunoIdInt = (int) $aluno['id'];

        $dados = Request::only(self::CAMPOS);
        $erros = $this->validar($dados);
        if ($erros) {
            $this->voltarComErros("alunos/$alunoIdInt/metas/nova", $dados, $erros);
        }
        if ($dados['revisar_em'] === '') {
            $dados['revisar_em'] = $this->prazoPadrao();
        }

        Meta::criar($alunoIdInt, $dados);
        Session::flash('sucesso', 'Meta cadastrada no plano do aluno.');
        redirect("alunos/$alunoIdInt#plano");
    }

    public function editar(string $id): void
    {
        $meta = $this->carregar((int) $id);
        $this->view('metas/form', [
            'titulo' => 'Editar meta do PEI',
            'aluno'  => ['id' => $meta['aluno_id'], 'nome' => $meta['aluno_nome']],
            'meta'   => $meta,
        ]);
    }

    public function atualizar(string $id): void
    {
        $id   = (int) $id;
        $meta = $this->carregar($id);

        $dados = Request::only(self::CAMPOS);
        $erros = $this->validar($dados);
        if ($erros) {
            $this->voltarComErros("metas/$id/editar", $dados, $erros);
        }
        if ($dados['revisar_em'] === '') {
            $dados['revisar_em'] = $this->prazoPadrao();
        }

        Meta::atualizar($id, $dados);
        Session::flash('sucesso', 'Meta atualizada.');
        redirect('alunos/' . (int) $meta['aluno_id'] . '#plano');
    }

    public function excluir(string $id): void
    {
        $meta = $this->carregar((int) $id);
        Meta::excluir((int) $meta['id']);
        Session::flash('sucesso', 'Meta excluída. Os registros dela continuam no histórico do aluno.');
        redirect('alunos/' . (int) $meta['aluno_id'] . '#plano');
    }

    // ------------------------------------------------------------------

    /** Devolve o aluno se ele for do professor logado; senão, erro 404. */
    private function alunoDoUsuario(int $id): array
    {
        $aluno = Aluno::buscarDoUsuario($id, Auth::id());
        if (!$aluno) {
            $this->erro(404, 'Aluno não encontrado.');
        }
        return $aluno;
    }

    /** Devolve a meta se ela pertencer a um aluno do professor logado; senão, erro 404. */
    private function carregar(int $id): array
    {
        $meta = Meta::buscarDoUsuario($id, Auth::id());
        if (!$meta) {
            $this->erro(404, 'Meta não encontrada.');
        }
        return $meta;
    }

    private function validar(array $dados): array
    {
        $v = Validator::make(
            $dados,
            [
                'area'                  => 'required|in:' . implode(',', array_keys(Config::get('areas'))),
                'descricao'             => 'required|min:5|max:255',
                'nivel_inicial'         => 'required|in:' . implode(',', array_keys(Acompanhamento::NIVEIS)),
                'professor_responsavel' => 'max:100',
                'revisar_em'            => 'date',
            ],
            [
                'area'                  => 'Área',
                'descricao'             => 'Meta',
                'nivel_inicial'         => 'Nível de suporte inicial',
                'professor_responsavel' => 'Professor responsável',
                'revisar_em'            => 'Data de revisão',
            ]
        );
        return $v->errors();
    }

    /** Hoje + revisao_dias. */
    private function prazoPadrao(): string
    {
        return date('Y-m-d', strtotime('+' . (int) Config::get('revisao_dias') . ' days'));
    }
}
