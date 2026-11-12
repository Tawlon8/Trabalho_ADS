<?php
namespace Core;

/** Base dos Controllers. */
abstract class Controller
{
    protected function view(string $view, array $dados = [], string $layout = 'layouts/main'): void
    {
        render($view, $dados, $layout);
    }

    /** Mostra a página de erro e encerra. */
    protected function erro(int $codigo, string $mensagem): void
    {
        render_erro($codigo, $mensagem);
        exit;
    }

    /** Volta ao formulário guardando o que foi digitado e as mensagens de erro. */
    protected function voltarComErros(string $rota, array $dados, array $erros): void
    {
        Session::withInput($dados, $erros);
        redirect($rota);
    }
}
