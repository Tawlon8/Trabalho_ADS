<?php
namespace App\Controllers;

use App\Models\TipoDeficiencia;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Validator;

/** CRUD dos tipos de deficiência (somente administrador). */
class TipoDeficienciaController extends Controller
{
    public function index(): void
    {
        $this->view('tipos/index', ['titulo' => 'Tipos de deficiência', 'tipos' => TipoDeficiencia::comContagem()]);
    }

    public function criar(): void
    {
        $this->view('tipos/form', ['titulo' => 'Novo tipo de deficiência', 'tipo' => null]);
    }

    public function salvar(): void
    {
        $dados = Request::only(['nome', 'descricao', 'icone']);
        $erros = $this->validar($dados, 0);
        if ($erros) {
            $this->voltarComErros('tipos/novo', $dados, $erros);
        }
        TipoDeficiencia::criar($dados);
        Session::flash('sucesso', 'Tipo de deficiência cadastrado com sucesso.');
        redirect('tipos');
    }

    public function editar(string $id): void
    {
        $tipo = TipoDeficiencia::buscar((int) $id);
        if (!$tipo) {
            $this->erro(404, 'Tipo de deficiência não encontrado.');
        }
        $this->view('tipos/form', ['titulo' => 'Editar tipo de deficiência', 'tipo' => $tipo]);
    }

    public function atualizar(string $id): void
    {
        $id   = (int) $id;
        $tipo = TipoDeficiencia::buscar($id);
        if (!$tipo) {
            $this->erro(404, 'Tipo de deficiência não encontrado.');
        }
        $dados = Request::only(['nome', 'descricao', 'icone']);
        $erros = $this->validar($dados, $id);
        if ($erros) {
            $this->voltarComErros("tipos/$id/editar", $dados, $erros);
        }
        TipoDeficiencia::atualizar($id, $dados);
        Session::flash('sucesso', 'Tipo de deficiência atualizado com sucesso.');
        redirect('tipos');
    }

    public function excluir(string $id): void
    {
        $id   = (int) $id;
        $tipo = TipoDeficiencia::buscar($id);
        if (!$tipo) {
            $this->erro(404, 'Tipo de deficiência não encontrado.');
        }
        if (TipoDeficiencia::totalAtividades($id) > 0) {
            Session::flash('erro', 'Não é possível excluir: existem atividades cadastradas neste tipo.');
            redirect('tipos');
        }
        TipoDeficiencia::excluir($id);
        Session::flash('sucesso', 'Tipo de deficiência excluído.');
        redirect('tipos');
    }

    private function validar(array $dados, int $ignorarId): array
    {
        $unique = 'unique:tipos_deficiencia,nome' . ($ignorarId > 0 ? ",$ignorarId" : '');
        $v = Validator::make(
            $dados,
            [
                'nome'      => "required|min:3|max:50|$unique",
                'descricao' => 'max:255',
                'icone'     => 'required|in:' . implode(',', array_keys(TipoDeficiencia::ICONES)),
            ],
            ['nome' => 'Nome', 'descricao' => 'Descrição', 'icone' => 'Ícone']
        );
        return $v->errors();
    }
}
