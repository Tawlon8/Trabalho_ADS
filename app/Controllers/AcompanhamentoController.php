<?php
namespace App\Controllers;

use App\Models\Acompanhamento;
use Core\Auth;
use Core\Controller;
use Core\Session;

/** Registros vêm das sessões; aqui só existe a exclusão (sessão feita por engano). */
class AcompanhamentoController extends Controller
{
    public function excluir(string $id): void
    {
        $id       = (int) $id;
        $registro = Acompanhamento::buscarDoUsuario($id, Auth::id());
        if (!$registro) {
            $this->erro(404, 'Registro não encontrado.');
        }
        Acompanhamento::excluir($id);
        Session::flash('sucesso', 'Registro excluído.');
        redirect('alunos/' . (int) $registro['aluno_id']);
    }
}
