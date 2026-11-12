<?php
namespace Core;

/** Leitura segura dos dados enviados pelo navegador. */
class Request
{
    /** Valor de $_POST como texto (arrays viram texto vazio). */
    public static function str(string $campo): string
    {
        $v = $_POST[$campo] ?? '';
        return is_string($v) ? $v : '';
    }

    /**
     * Pega vários campos do POST.
     * $comTrim: campos com espaços removidos nas pontas. $semTrim: ex. senhas (mantidas como vieram).
     */
    public static function only(array $comTrim, array $semTrim = []): array
    {
        $saida = [];
        foreach ($comTrim as $c) {
            $saida[$c] = trim(self::str($c));
        }
        foreach ($semTrim as $c) {
            $saida[$c] = self::str($c);
        }
        return $saida;
    }

    /** Lista de inteiros positivos da query string (?atividades[]=1&atividades[]=2), sem repetição. */
    public static function ints(string $campo, int $maximo = 20): array
    {
        $v = $_GET[$campo] ?? null;
        if (!is_array($v)) {
            return [];
        }
        $saida = [];
        foreach ($v as $item) {
            $n = filter_var($item, FILTER_VALIDATE_INT);
            if ($n !== false && $n > 0) {
                $saida[$n] = $n;
            }
        }
        return array_slice(array_values($saida), 0, $maximo);
    }

    /** Valor da query string (?q=...), limitado a 100 caracteres. */
    public static function get(string $campo, string $padrao = ''): string
    {
        $v = $_GET[$campo] ?? $padrao;
        return is_string($v) ? mb_substr(trim($v), 0, 100) : $padrao;
    }
}
