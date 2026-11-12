<?php
namespace App\Controllers;

use App\Models\Acompanhamento;
use App\Models\Aluno;
use App\Models\Atividade;
use App\Services\Evolucao;
use App\Models\Meta;
use App\Models\TipoDeficiencia;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Validator;

/** Cadastro de alunos e ficha do aluno (estudo de caso, metas, evolução, sugestões, sessão e histórico). */
class AlunoController extends Controller
{
    private const CAMPOS = ['nome', 'data_nascimento', 'tipo_deficiencia_id', 'observacoes'];

    public function index(): void
    {
        $busca = Request::get('q');
        $this->view('alunos/index', [
            'titulo' => 'Meus alunos',
            'busca'  => $busca,
            'alunos' => Aluno::listarDoUsuario(Auth::id(), $busca),
        ]);
    }

    public function criar(): void
    {
        $this->view('alunos/form', [
            'titulo' => 'Novo aluno',
            'aluno'  => null,
            'tipos'  => TipoDeficiencia::listar(),
        ]);
    }

    public function salvar(): void
    {
        $dados = $this->dadosDoFormulario();
        $erros = $this->validar($dados);
        if ($erros) {
            $this->voltarComErros('alunos/novo', $dados, $erros);
        }
        $id = Aluno::criar($dados, Auth::id());
        Session::flash('sucesso', 'Aluno cadastrado. Agora você pode montar o plano de apoio (metas).');
        redirect('alunos/' . $id);
    }

    /** Ficha do aluno. */
    public function mostrar(string $id): void
    {
        $aluno = $this->carregar((int) $id);
        $alunoId = (int) $aluno['id'];

        $metas = Meta::listarDoAluno($alunoId);
        $sugestoes = [];
        $evolucao  = [];
        $sessoes   = [];
        foreach ($metas as $meta) {
            $metaId = (int) $meta['id'];
            $sugestoes[$meta['id']] = Atividade::sugeridasParaMeta($metaId, $alunoId);
            $sessoes[$meta['id']]   = Acompanhamento::sessoesDaMeta($metaId, $alunoId);
            $evolucao[$meta['id']]  = Evolucao::resumir($sessoes[$meta['id']]);
        }

        $this->view('alunos/mostrar', [
            'titulo'          => $aluno['nome'],
            'aluno'           => $aluno,
            'metas'           => $metas,
            'sugestoes'       => $sugestoes,
            'evolucao'        => $evolucao,
            'sessoes'         => $sessoes,
            'acompanhamentos' => Acompanhamento::listarDoAluno($alunoId),
            'resumo'          => Acompanhamento::resumoDoAluno($alunoId),
            'executaveis'     => Atividade::executaveis(),
        ]);
    }

    public function editar(string $id): void
    {
        $aluno = $this->carregar((int) $id);
        $this->view('alunos/form', [
            'titulo' => 'Editar aluno',
            'aluno'  => $aluno,
            'tipos'  => TipoDeficiencia::listar(),
        ]);
    }

    public function atualizar(string $id): void
    {
        $id = (int) $id;
        $this->carregar($id);

        $dados = $this->dadosDoFormulario();
        $erros = $this->validar($dados);
        if ($erros) {
            $this->voltarComErros("alunos/$id/editar", $dados, $erros);
        }
        Aluno::atualizar($id, $dados);
        Session::flash('sucesso', 'Dados do aluno atualizados.');
        redirect('alunos/' . $id);
    }

    public function excluir(string $id): void
    {
        $id = (int) $id;
        $this->carregar($id);
        Aluno::excluir($id);   // metas e registros são apagados junto (ON DELETE CASCADE)
        Session::flash('sucesso', 'Aluno excluído.');
        redirect('alunos');
    }

    // ------------------------------------------------------------------

    /** Só devolve o aluno se ele for do professor logado; senão, erro 404. */
    private function carregar(int $id): array
    {
        $aluno = Aluno::buscarDoUsuario($id, Auth::id());
        if (!$aluno) {
            $this->erro(404, 'Aluno não encontrado.');
        }
        return $aluno;
    }

    private function dadosDoFormulario(): array
    {
        $dados = Request::only(self::CAMPOS);
        $dados['consentimento'] = Request::str('consentimento');
        return $dados;
    }

    private function validar(array $dados): array
    {
        $v = Validator::make(
            $dados,
            [
                'nome'                => 'required|min:3|max:100',
                'data_nascimento'     => 'date|past_or_today',
                'tipo_deficiencia_id' => 'int|exists:tipos_deficiencia,id',
                'observacoes'         => 'max:2000',
                'consentimento'       => 'accepted',
            ],
            [
                'nome'                => 'Nome',
                'data_nascimento'     => 'Data de nascimento',
                'tipo_deficiencia_id' => 'Tipo de deficiência',
                'observacoes'         => 'Estudo de caso',
                'consentimento'       => 'a autorização do responsável',
            ]
        );
        return $v->errors();
    }
}
