<?php
/**
 * Inicialização: constantes, autoload das classes, funções auxiliares,
 * tratamento de erros e sessão. É chamado por public/index.php.
 */
define('BASE_DIR', dirname(__DIR__));
define('VIEW_PATH', BASE_DIR . '/app/Views');
define('UPLOAD_DIR', BASE_DIR . '/public/uploads');

// Autoload simples: App\Models\Aluno => app/Models/Aluno.php | Core\Router => core/Router.php
spl_autoload_register(function ($classe) {
    $mapa = ['App\\' => BASE_DIR . '/app/', 'Core\\' => BASE_DIR . '/core/'];
    foreach ($mapa as $prefixo => $pasta) {
        if (strncmp($classe, $prefixo, strlen($prefixo)) === 0) {
            $arquivo = $pasta . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
            if (is_file($arquivo)) {
                require $arquivo;
            }
            return;
        }
    }
});

require_once __DIR__ . '/helpers.php';

$debug = (bool) Core\Config::get('debug', false);
error_reporting(E_ALL);
ini_set('display_errors', $debug ? '1' : '0');

// Qualquer erro não tratado vira uma página de erro 500 (detalhes só no log)
set_exception_handler(function ($e) use ($debug) {
    error_log($e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    $msg = $debug ? $e->getMessage() : 'Ocorreu um erro inesperado. Tente novamente mais tarde.';
    try {
        render_erro(500, $msg);
    } catch (Throwable $e2) {
        echo 'Erro interno do servidor.';
    }
});

if (PHP_SAPI !== 'cli') {
    Core\Session::start();
}
