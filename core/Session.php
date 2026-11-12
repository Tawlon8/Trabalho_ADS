<?php
namespace Core;

/** Sessão, mensagens "flash" e dados antigos de formulário (old input / erros). */
class Session
{
    private static $old    = [];
    private static $erros  = [];
    private static $flash  = null;

    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();

        // Dados que valem só para a requisição seguinte
        self::$old   = $_SESSION['_old'] ?? [];
        self::$erros = $_SESSION['_erros'] ?? [];
        self::$flash = $_SESSION['_flash'] ?? null;
        unset($_SESSION['_old'], $_SESSION['_erros'], $_SESSION['_flash']);
    }

    public static function flash(string $tipo, string $mensagem): void
    {
        $_SESSION['_flash'] = ['tipo' => $tipo, 'msg' => $mensagem];
    }

    public static function getFlash(): ?array
    {
        return self::$flash;
    }

    /** Guarda o que foi digitado e os erros para mostrar no formulário após o redirecionamento. */
    public static function withInput(array $dados, array $erros): void
    {
        foreach (['senha', 'senha_confirm', 'senha_atual', 'senha_nova', 'senha_nova_confirm'] as $campo) {
            unset($dados[$campo]);   // nunca devolve senhas ao formulário
        }
        $_SESSION['_old']   = $dados;
        $_SESSION['_erros'] = $erros;
    }

    public static function old(string $campo, $padrao = '')
    {
        return array_key_exists($campo, self::$old) ? self::$old[$campo] : $padrao;
    }

    public static function erros(): array
    {
        return self::$erros;
    }

    public static function erro(string $campo): ?string
    {
        return self::$erros[$campo] ?? null;
    }
}
