<?php
namespace App\Controllers;

use App\Models\Usuario;
use Core\Auth;
use Core\Config;
use Core\Controller;
use Core\Request;
use Core\Session;
use Core\Validator;

/** Login, cadastro e logout. */
class AuthController extends Controller
{
    const MAX_TENTATIVAS    = 5;
    const BLOQUEIO_SEGUNDOS = 300;

    public function formLogin(): void
    {
        $this->view('auth/login', ['titulo' => 'Entrar'], 'layouts/auth');
    }

    public function login(): void
    {
        if (($_SESSION['login_bloqueado_ate'] ?? 0) > time()) {
            Session::flash('erro', 'Muitas tentativas. Aguarde alguns minutos e tente novamente.');
            redirect('login');
        }

        $dados = Request::only(['email'], ['senha']);
        $v = Validator::make(
            $dados,
            ['email' => 'required|email', 'senha' => 'required'],
            ['email' => 'E-mail', 'senha' => 'Senha']
        );
        if ($v->fails()) {
            $this->voltarComErros('login', ['email' => $dados['email']], $v->errors());
        }

        $usuario = Usuario::buscarPorEmail($dados['email']);
        if ($usuario && password_verify($dados['senha'], $usuario['senha'])) {
            unset($_SESSION['login_tentativas'], $_SESSION['login_bloqueado_ate']);
            Auth::login($usuario);
            redirect('');
        }

        // falha: conta a tentativa; mensagem única para e-mail ou senha
        $_SESSION['login_tentativas'] = ($_SESSION['login_tentativas'] ?? 0) + 1;
        if ($_SESSION['login_tentativas'] >= self::MAX_TENTATIVAS) {
            $_SESSION['login_bloqueado_ate'] = time() + self::BLOQUEIO_SEGUNDOS;
            $_SESSION['login_tentativas']    = 0;
        }
        Session::flash('erro', 'E-mail ou senha incorretos.');
        $this->voltarComErros('login', ['email' => $dados['email']], []);
    }

    public function logout(): void
    {
        Auth::logout();
        Session::flash('sucesso', 'Você saiu do sistema.');
        redirect('login');
    }

    public function formCadastro(): void
    {
        $this->view('auth/cadastro', ['titulo' => 'Cadastre-se'], 'layouts/auth');
    }

    public function cadastro(): void
    {
        $min   = (int) Config::get('senha_min_caracteres');
        $dados = Request::only(['nome', 'email'], ['senha', 'senha_confirm']);
        $dados['aceite'] = Request::str('aceite');

        $v = Validator::make(
            $dados,
            [
                'nome'   => 'required|min:3|max:100',
                'email'  => 'required|email|max:100|unique:usuarios,email',
                'senha'  => "required|min:$min|max:72|confirmed",
                'aceite' => 'accepted',
            ],
            ['nome' => 'Nome', 'email' => 'E-mail', 'senha' => 'Senha', 'aceite' => 'o tratamento de dados (LGPD)']
        );
        if ($v->fails()) {
            $this->voltarComErros('cadastro', ['nome' => $dados['nome'], 'email' => $dados['email']], $v->errors());
        }

        try {
            Usuario::criar($dados['nome'], $dados['email'], password_hash($dados['senha'], PASSWORD_DEFAULT));
        } catch (\PDOException $e) {
            error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
            $this->voltarComErros(
                'cadastro',
                ['nome' => $dados['nome'], 'email' => $dados['email']],
                ['email' => 'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.']
            );
        }

        Session::flash('sucesso', 'Cadastro realizado com sucesso! Faça login para continuar.');
        redirect('login');
    }
}
