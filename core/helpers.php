<?php
use Core\Auth;
use Core\Config;
use Core\Csrf;
use Core\Session;

/** Escapa texto para HTML (XSS). */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/** Pasta onde o sistema está instalada ('' na raiz do servidor, '/Trabalho_ADS' em XAMPP). */
function base_path(): string
{
    static $base = null;
    if ($base === null) {
        $dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        if (substr($dir, -7) === '/public') {
            $dir = substr($dir, 0, -7);
        }
        $base = $dir;
    }
    return $base;
}

/** Caminho da requisição atual, sem a pasta base. Ex.: /alunos/3 */
function current_path(): string
{
    $caminho = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $caminho = rawurldecode($caminho);
    $base = base_path();
    if ($base !== '' && strpos($caminho, $base) === 0) {
        $caminho = substr($caminho, strlen($base));
    }
    return '/' . trim($caminho, '/');
}

/** URL de uma rota do sistema. Ex.: url('alunos/3') */
function url(string $caminho = ''): string
{
    return base_path() . '/' . ltrim($caminho, '/');
}

/** URL de um arquivo público (uploads, vídeos, imagens), com acentos e espaços codificados. */
function public_url(string $caminho): string
{
    $partes = array_map('rawurlencode', explode('/', ltrim($caminho, '/')));
    return base_path() . '/' . implode('/', $partes);
}

function asset(string $caminho): string
{
    $caminho = ltrim($caminho, '/');
    // ?v=data do arquivo, para renovar o cache
    $arquivo = BASE_DIR . '/public/assets/' . $caminho;
    $versao  = is_file($arquivo) ? '?v=' . filemtime($arquivo) : '';
    return public_url('assets/' . $caminho) . $versao;
}

function redirect(string $caminho): void
{
    header('Location: ' . url($caminho));
    exit;
}

/** Valor digitado antes de um erro de validação (ou o valor padrão). */
function old(string $campo, $padrao = '')
{
    return Session::old($campo, $padrao);
}

function campo_erro(string $campo): string
{
    $msg = Session::erro($campo);
    return $msg ? '<span class="erro-campo" id="erro-' . e($campo) . '">' . e($msg) . '</span>' : '';
}

/** Atributos ARIA de um campo com erro. */
function aria_erro(string $campo): string
{
    return Session::erro($campo) ? ' aria-invalid="true" aria-describedby="erro-' . e($campo) . '"' : '';
}

function selecionado($a, $b): string
{
    return (string) $a === (string) $b ? ' selected' : '';
}

function marcado($condicao): string
{
    return $condicao ? ' checked' : '';
}

function csrf_field(): string
{
    return Csrf::field();
}

function data_br(?string $data): string
{
    if (!$data) {
        return '';
    }
    $t = strtotime($data);
    return $t ? date('d/m/Y', $t) : '';
}

/** Rótulo de uma área de habilidade (ex.: 'matematica' => 'Alfabetização Matemática'). */
function area_nome(?string $chave): string
{
    if ($chave === null || $chave === '') {
        return 'Sem área';
    }
    return Config::get('areas.' . $chave, $chave);
}

/** Rótulo de um nível de suporte (ex.: 'com_ajuda' => 'Realiza com ajuda'). */
function nivel_nome(?string $chave): string
{
    if ($chave === null || $chave === '') {
        return 'Sem registros';
    }
    return \App\Models\Acompanhamento::NIVEIS[$chave] ?? $chave;
}

/** Menu: true se a rota atual corresponde ao item. */
function rota_atual(string $rota): bool
{
    $atual = current_path();
    $alvo  = '/' . trim($rota, '/');
    if ($alvo === '/') {
        return $atual === '/';
    }
    return $atual === $alvo || strpos($atual, $alvo . '/') === 0;
}

/** Renderiza uma view dentro de um layout. */
function render(string $view, array $dados = [], string $layout = 'layouts/main'): void
{
    extract($dados, EXTR_SKIP);
    ob_start();
    require VIEW_PATH . '/' . $view . '.php';
    $conteudo = ob_get_clean();
    if ($layout !== '') {
        require VIEW_PATH . '/' . $layout . '.php';
    } else {
        echo $conteudo;
    }
}

function render_erro(int $codigo, string $mensagem): void
{
    if (!headers_sent()) {
        http_response_code($codigo);
    }
    render(
        'erros/erro',
        ['titulo' => 'Erro ' . $codigo, 'codigo' => $codigo, 'mensagem' => $mensagem],
        Auth::check() ? 'layouts/main' : 'layouts/auth'
    );
}

/** Links de paginação acessíveis (aria-current na página atual). */
function paginacao(int $total, int $pagina, int $porPagina, string $rota, array $query = []): string
{
    $paginas = (int) ceil($total / max(1, $porPagina));
    if ($paginas <= 1) {
        return '';
    }
    $html = '<nav class="paginacao" aria-label="Paginação"><ul>';
    for ($i = 1; $i <= $paginas; $i++) {
        $q = array_filter(array_merge($query, ['pagina' => $i]), function ($v) {
            return $v !== '' && $v !== null && $v !== 0;
        });
        $href = url($rota) . '?' . http_build_query($q);
        if ($i === $pagina) {
            $html .= '<li><span class="atual" aria-current="page">' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . e($href) . '" aria-label="Página ' . $i . '">' . $i . '</a></li>';
        }
    }
    return $html . '</ul></nav>';
}

/** true se o valor de mídia é um endereço externo (http/https). */
function midia_externa(string $valor): bool
{
    return (bool) preg_match('#^https?://#i', $valor);
}
