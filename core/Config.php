<?php
namespace Core;

/** Leitura das configurações com notação de ponto: Config::get('db.host'). */
class Config
{
    private static $dados = null;

    public static function get(string $chave, $padrao = null)
    {
        if (self::$dados === null) {
            self::$dados = require BASE_DIR . '/config/config.php';
        }
        $valor = self::$dados;
        foreach (explode('.', $chave) as $parte) {
            if (!is_array($valor) || !array_key_exists($parte, $valor)) {
                return $padrao;
            }
            $valor = $valor[$parte];
        }
        return $valor;
    }
}
