<?php
namespace Core;

/**
 * Roteador com URLs amigáveis. Cada rota liga método + caminho a um Controller@ação
 * e pode ter "middlewares": 'guest' (só visitantes), 'auth' (só logados), 'admin' (só administradores).
 */
class Router
{
    private $rotas = [];

    public function get(string $caminho, array $acao, array $middlewares = []): void
    {
        $this->adicionar('GET', $caminho, $acao, $middlewares);
    }

    public function post(string $caminho, array $acao, array $middlewares = []): void
    {
        $this->adicionar('POST', $caminho, $acao, $middlewares);
    }

    private function adicionar(string $metodo, string $caminho, array $acao, array $middlewares): void
    {
        // {id} e {...id} aceitam só números; {token} aceita 64 caracteres hexadecimais
        $regex = preg_replace_callback('#\{(\w+)\}#', function ($m) {
            $nome = $m[1];
            if ($nome === 'token') {
                return '(?P<token>[a-f0-9]{64})';
            }
            if (substr($nome, -2) === 'id') {
                return '(?P<' . $nome . '>\d+)';
            }
            return '(?P<' . $nome . '>[^/]+)';
        }, $caminho);

        $this->rotas[] = [
            'metodo'      => $metodo,
            'regex'       => '#^' . $regex . '$#',
            'acao'        => $acao,
            'middlewares' => $middlewares,
        ];
    }

    public function dispatch(string $metodo): void
    {
        $metodo = strtoupper($metodo) === 'HEAD' ? 'GET' : strtoupper($metodo);
        $caminho = current_path();
        $achouCaminho = false;

        foreach ($this->rotas as $rota) {
            if (!preg_match($rota['regex'], $caminho, $m)) {
                continue;
            }
            $achouCaminho = true;
            if ($rota['metodo'] !== $metodo) {
                continue;
            }
            $params = [];
            foreach ($m as $chave => $valor) {
                if (is_string($chave)) {
                    $params[] = $valor;
                }
            }
            $this->executar($rota, $params);
            return;
        }

        if ($achouCaminho) {
            render_erro(405, 'Método não permitido para esta página.');
        } else {
            render_erro(404, 'Página não encontrada.');
        }
    }

    private function executar(array $rota, array $params): void
    {
        // Toda requisição POST precisa do token CSRF
        if ($rota['metodo'] === 'POST' && !Csrf::validate()) {
            render_erro(403, 'Requisição inválida (token de segurança). Volte à página anterior e tente novamente.');
            return;
        }

        foreach ($rota['middlewares'] as $mw) {
            if ($mw === 'guest' && Auth::check()) {
                redirect('');
            }
            if ($mw === 'auth') {
                Auth::exigirLogin();
            }
            if ($mw === 'admin') {
                Auth::exigirLogin();
                Auth::exigirAdmin();
            }
        }

        [$classe, $metodo] = $rota['acao'];
        $classe = 'App\\Controllers\\' . $classe;
        $controller = new $classe();
        call_user_func_array([$controller, $metodo], $params);
    }
}
