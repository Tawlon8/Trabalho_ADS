<?php
namespace App\Controllers;

use App\Models\Usuario;
use Core\Auth;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Validator;

/** Dados da conta do usuário logado: ver, editar, trocar senha e excluir. */
class PerfilController extends Controller
{
    public function mostrar(): void
    {
        $usuario = Usuario::buscar(Auth::id());
        if (!$usuario) {
            Auth::logout();
            redirect('login');
        }
        $this->view('perfil/mostrar', ['titulo' => 'Meu perfil', 'usuario' => $usuario]);
    }

    public function atualizar(): void
    {
        $id    = Auth::id();
        $dados = Request::only(['nome', 'email']);
        $v = Validator::make(
            $dados,
            [
                'nome'  => 'required|min:3|max:100',
                'email' => "required|email|max:100|unique:usuarios,email,$id",
            ],
            ['nome' => 'Nome', 'email' => 'E-mail']
        );
        if ($v->fails()) {
            $this->voltarComErros('perfil', $dados, $v->errors());
        }

        Usuario::atualizarDados($id, $dados['nome'], $dados['email']);
        $_SESSION['user']['nome'] = $dados['nome'];
        Session::flash('sucesso', 'Dados atualizados com sucesso.');
        redirect('perfil');
    }

    public function alterarSenha(): void
    {
        $id    = Auth::id();
        $min   = (int) Config::get('senha_min_caracteres');
        $dados = Request::only([], ['senha_atual', 'senha_nova', 'senha_nova_confirm']);
        $v = Validator::make(
            ['senha_atual' => $dados['senha_atual'], 'senha_nova' => $dados['senha_nova'], 'senha_nova_confirm' => $dados['senha_nova_confirm']],
            ['senha_atual' => 'required', 'senha_nova' => "required|min:$min|max:72|confirmed"],
            ['senha_atual' => 'Senha atual', 'senha_nova' => 'Nova senha']
        );
        $erros = $v->errors();

        $usuario = Usuario::buscar($id);
        if (!isset($erros['senha_atual']) && (!$usuario || !password_verify($dados['senha_atual'], $usuario['senha']))) {
            $erros['senha_atual'] = 'A senha atual está incorreta.';
        }
        if ($erros) {
            $this->voltarComErros('perfil', [], $erros);
        }

        Usuario::atualizarSenha($id, password_hash($dados['senha_nova'], PASSWORD_DEFAULT));
        Session::flash('sucesso', 'Senha alterada com sucesso.');
        redirect('perfil');
    }

    public function excluir(): void
    {
        $id    = Auth::id();
        $dados = Request::only([], ['senha_exclusao']);
        $usuario = Usuario::buscar($id);

        if ($dados['senha_exclusao'] === '' || !$usuario || !password_verify($dados['senha_exclusao'], $usuario['senha'])) {
            $this->voltarComErros('perfil', [], ['senha_exclusao' => 'Informe sua senha corretamente para excluir a conta.']);
        }

        Usuario::excluir($id);   // alunos e acompanhamentos são apagados junto (ON DELETE CASCADE)
        Auth::logout();
        Session::flash('sucesso', 'Sua conta foi excluída.');
        redirect('login');
    }
}
