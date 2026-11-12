<?php
namespace App\Controllers;

use App\Models\RecuperacaoSenha;
use App\Models\Usuario;
use Core\Config;
use Core\Controller;
use Core\Mailer;
use Core\Request;
use Core\Session;
use Core\Validator;

/** Recuperação de senha por e-mail. */
class SenhaController extends Controller
{
    public function formEsqueci(): void
    {
        $this->view('senha/esqueci', ['titulo' => 'Recuperar senha'], 'layouts/auth');
    }

    public function enviar(): void
    {
        $dados = Request::only(['email']);
        $v = Validator::make($dados, ['email' => 'required|email'], ['email' => 'E-mail']);
        if ($v->fails()) {
            $this->voltarComErros('esqueci-senha', $dados, $v->errors());
        }

        try {
            $usuario = Usuario::buscarPorEmail($dados['email']);
        } catch (\Throwable $e) {
            error_log('Erro ao buscar usuário na recuperação de senha: ' . $e->getMessage());
            $usuario = null;
        }
        if ($usuario) {
            try {
                $token   = bin2hex(random_bytes(32));
                $minutos = (int) Config::get('token_validade_minutos');
                RecuperacaoSenha::criar((int) $usuario['id'], hash('sha256', $token), $minutos);   // grava só o hash
                $link = rtrim(Config::get('app_url'), '/') . '/redefinir-senha/' . $token;
                Mailer::enviarRecuperacao($usuario['email'], $link, $minutos);
            } catch (\Throwable $e) {
                error_log('Erro na recuperação de senha: ' . $e->getMessage());
            }
        }

        // mesma resposta com ou sem cadastro
        Session::flash('sucesso', 'Se o e-mail estiver cadastrado, enviamos um link para redefinir a senha. Verifique também a caixa de spam.');
        redirect('esqueci-senha');
    }

    public function formRedefinir(string $token): void
    {
        header('Referrer-Policy: no-referrer');
        $valido = RecuperacaoSenha::buscarValido(hash('sha256', $token)) !== null;
        $this->view('senha/redefinir', ['titulo' => 'Redefinir senha', 'token' => $token, 'valido' => $valido], 'layouts/auth');
    }

    public function redefinir(string $token): void
    {
        $registro = RecuperacaoSenha::buscarValido(hash('sha256', $token));
        if (!$registro) {
            Session::flash('erro', 'Este link não é válido ou já expirou. Solicite um novo.');
            redirect('esqueci-senha');
        }

        $min   = (int) Config::get('senha_min_caracteres');
        $dados = Request::only([], ['senha', 'senha_confirm']);
        $v = Validator::make($dados, ['senha' => "required|min:$min|max:72|confirmed"], ['senha' => 'Nova senha']);
        if ($v->fails()) {
            $this->voltarComErros('redefinir-senha/' . $token, [], $v->errors());
        }

        $usuarioId = (int) $registro['usuario_id'];
        Usuario::atualizarSenha($usuarioId, password_hash($dados['senha'], PASSWORD_DEFAULT));
        RecuperacaoSenha::apagarDoUsuario($usuarioId);   // uso único

        Session::flash('sucesso', 'Senha redefinida com sucesso! Você já pode fazer login.');
        redirect('login');
    }
}
