<?php
namespace Core;

/** Autenticação e controle de acesso (login obrigatório e perfis). */
class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return ($_SESSION['user']['perfil'] ?? '') === 'admin';
    }

    public static function login(array $usuario): void
    {
        session_regenerate_id(true);
        // sem o hash da senha
        $_SESSION['user'] = [
            'id'     => (int) $usuario['id'],
            'nome'   => $usuario['nome'],
            'perfil' => $usuario['perfil'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
    }

    /** Sem login, vai para a tela de login. */
    public static function exigirLogin(): void
    {
        if (!self::check()) {
            Session::flash('erro', 'Faça login para acessar esta página.');
            redirect('login');
        }
    }

    /** Barra quem não é administrador. */
    public static function exigirAdmin(): void
    {
        if (!self::isAdmin()) {
            render_erro(403, 'Você não tem permissão para acessar esta página.');
            exit;
        }
    }
}
