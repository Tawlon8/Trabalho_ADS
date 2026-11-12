<?php
namespace Core;

/** Proteção contra CSRF: o Router valida o token em toda requisição POST. */
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function validate(): bool
    {
        $enviado = $_POST['_csrf'] ?? '';
        return is_string($enviado) && !empty($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $enviado);
    }
}
