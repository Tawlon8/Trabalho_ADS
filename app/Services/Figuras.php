<?php
namespace App\Services;

/**
 * Figuras dos mini games em SVG (sem arquivos de imagem), com palavra, letra inicial e sílabas.
 * Cada figura tem silhueta própria, para não depender de cor.
 */
class Figuras
{
    /** nome interno => [palavra, letra inicial, sílabas] */
    public const CATALOGO = [
        'bola'    => ['BOLA',    'B', ['BO', 'LA']],
        'casa'    => ['CASA',    'C', ['CA', 'SA']],
        'sol'     => ['SOL',     'S', ['SOL']],
        'lua'     => ['LUA',     'L', ['LU', 'A']],
        'flor'    => ['FLOR',    'F', ['FLOR']],
        'peixe'   => ['PEIXE',   'P', ['PEI', 'XE']],
        'carro'   => ['CARRO',   'C', ['CAR', 'RO']],
        'copo'    => ['COPO',    'C', ['CO', 'PO']],
        'maca'    => ['MAÇÃ',    'M', ['MA', 'ÇÃ']],
        'gato'    => ['GATO',    'G', ['GA', 'TO']],
        'pato'    => ['PATO',    'P', ['PA', 'TO']],
        'bolo'    => ['BOLO',    'B', ['BO', 'LO']],
        'livro'   => ['LIVRO',   'L', ['LI', 'VRO']],
        'estrela' => ['ESTRELA', 'E', ['ES', 'TRE', 'LA']],
    ];

    public static function existe(string $nome): bool
    {
        return isset(self::CATALOGO[$nome]);
    }

    public static function palavra(string $nome): string
    {
        return self::CATALOGO[$nome][0] ?? '';
    }

    public static function letra(string $nome): string
    {
        return self::CATALOGO[$nome][1] ?? '';
    }

    public static function silabas(string $nome): array
    {
        return self::CATALOGO[$nome][2] ?? [];
    }

    public static function nomes(): array
    {
        return array_keys(self::CATALOGO);
    }

    /** SVG decorativo (aria-hidden); o botão do jogo leva rótulo neutro. */
    public static function svg(string $nome, int $tamanho = 120): string
    {
        $d = self::desenho($nome);
        if ($d === '') {
            return '';
        }
        return '<svg class="figura" width="' . $tamanho . '" height="' . $tamanho . '"'
             . ' viewBox="0 0 100 100" aria-hidden="true" focusable="false">' . $d . '</svg>';
    }

    /** Traços de cada figura: contorno escuro e preenchimento claro. */
    private static function desenho(string $nome): string
    {
        $t = 'fill="#ffffff" stroke="#111111" stroke-width="4" stroke-linejoin="round" stroke-linecap="round"';
        $p = 'fill="#111111"';

        switch ($nome) {
            case 'bola':
                return '<circle cx="50" cy="50" r="34" ' . $t . '/>'
                     . '<path d="M50 16 Q66 50 50 84" fill="none" stroke="#111" stroke-width="4"/>'
                     . '<path d="M50 16 Q34 50 50 84" fill="none" stroke="#111" stroke-width="4"/>'
                     . '<path d="M18 42 Q50 54 82 42" fill="none" stroke="#111" stroke-width="4"/>';

            case 'casa':
                return '<path d="M20 50 L50 24 L80 50 Z" ' . $t . '/>'
                     . '<rect x="27" y="50" width="46" height="30" ' . $t . '/>'
                     . '<rect x="43" y="60" width="14" height="20" ' . $t . '/>';

            case 'sol':
                $raios = '';
                for ($i = 0; $i < 8; $i++) {
                    $a  = $i * M_PI / 4;
                    $x1 = 50 + 28 * cos($a);  $y1 = 50 + 28 * sin($a);
                    $x2 = 50 + 40 * cos($a);  $y2 = 50 + 40 * sin($a);
                    $raios .= sprintf(
                        '<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" stroke="#111" stroke-width="5" stroke-linecap="round"/>',
                        $x1, $y1, $x2, $y2
                    );
                }
                return '<circle cx="50" cy="50" r="20" ' . $t . '/>' . $raios;

            case 'lua':
                return '<path d="M62 16 A36 36 0 1 0 62 84 A30 30 0 1 1 62 16 Z" ' . $t . '/>';

            case 'flor':
                $petalas = '';
                for ($i = 0; $i < 6; $i++) {
                    $g = $i * 60;
                    $petalas .= '<ellipse cx="50" cy="22" rx="9" ry="16" ' . $t
                              . ' transform="rotate(' . $g . ' 50 44)"/>';
                }
                return '<line x1="50" y1="44" x2="50" y2="90" stroke="#111" stroke-width="5" stroke-linecap="round"/>'
                     . '<path d="M50 72 Q32 64 28 78 Q44 82 50 72 Z" ' . $t . '/>'
                     . $petalas . '<circle cx="50" cy="44" r="10" ' . $t . '/>';

            case 'peixe':
                return '<path d="M20 50 Q42 26 66 50 Q42 74 20 50 Z" ' . $t . '/>'
                     . '<path d="M66 50 L86 34 L86 66 Z" ' . $t . '/>'
                     . '<circle cx="34" cy="46" r="3.5" ' . $p . '/>';

            case 'carro':
                return '<path d="M16 62 L16 50 L30 50 L38 38 L66 38 L72 50 L84 50 L84 62 Z" ' . $t . '/>'
                     . '<circle cx="32" cy="66" r="9" ' . $t . '/>'
                     . '<circle cx="68" cy="66" r="9" ' . $t . '/>';

            case 'copo':
                return '<path d="M32 24 L68 24 L62 82 L38 82 Z" ' . $t . '/>'
                     . '<line x1="34" y1="44" x2="66" y2="44" stroke="#111" stroke-width="4"/>';

            case 'maca':
                return '<path d="M50 34 Q30 26 26 48 Q22 76 50 84 Q78 76 74 48 Q70 26 50 34 Z" ' . $t . '/>'
                     . '<line x1="50" y1="34" x2="50" y2="20" stroke="#111" stroke-width="5" stroke-linecap="round"/>'
                     . '<path d="M50 24 Q66 12 72 24 Q60 32 50 24 Z" ' . $t . '/>';

            case 'gato':
                return '<path d="M26 34 L30 14 L46 26 Z" ' . $t . '/>'
                     . '<path d="M74 34 L70 14 L54 26 Z" ' . $t . '/>'
                     . '<circle cx="50" cy="52" r="26" ' . $t . '/>'
                     . '<circle cx="41" cy="48" r="3.5" ' . $p . '/>'
                     . '<circle cx="59" cy="48" r="3.5" ' . $p . '/>'
                     . '<line x1="22" y1="56" x2="38" y2="58" stroke="#111" stroke-width="3"/>'
                     . '<line x1="78" y1="56" x2="62" y2="58" stroke="#111" stroke-width="3"/>'
                     . '<path d="M46 60 Q50 64 54 60" fill="none" stroke="#111" stroke-width="3"/>';

            case 'pato':
                return '<path d="M14 58 L30 50 L30 66 Z" ' . $t . '/>'
                     . '<ellipse cx="48" cy="62" rx="26" ry="17" ' . $t . '/>'
                     . '<path d="M58 44 Q58 30 68 30" fill="none" stroke="#111" stroke-width="5" stroke-linecap="round"/>'
                     . '<circle cx="70" cy="30" r="13" ' . $t . '/>'
                     . '<path d="M82 27 L96 31 L82 36 Z" ' . $t . '/>'
                     . '<circle cx="68" cy="26" r="3" ' . $p . '/>'
                     . '<path d="M38 58 Q50 52 60 60" fill="none" stroke="#111" stroke-width="4"/>';

            case 'bolo':
                return '<rect x="24" y="56" width="52" height="24" rx="4" ' . $t . '/>'
                     . '<rect x="30" y="40" width="40" height="18" rx="4" ' . $t . '/>'
                     . '<line x1="50" y1="40" x2="50" y2="26" stroke="#111" stroke-width="5" stroke-linecap="round"/>'
                     . '<path d="M50 26 Q44 18 50 12 Q56 18 50 26 Z" ' . $t . '/>';

            case 'livro':
                return '<path d="M50 30 Q34 20 18 26 L18 74 Q34 68 50 78 Z" ' . $t . '/>'
                     . '<path d="M50 30 Q66 20 82 26 L82 74 Q66 68 50 78 Z" ' . $t . '/>'
                     . '<line x1="50" y1="30" x2="50" y2="78" stroke="#111" stroke-width="4"/>';

            case 'estrela':
                $pontos = [];
                for ($i = 0; $i < 10; $i++) {
                    $r = ($i % 2 === 0) ? 36 : 15;
                    $a = $i * M_PI / 5 - M_PI / 2;
                    $pontos[] = sprintf('%.1f,%.1f', 50 + $r * cos($a), 50 + $r * sin($a));
                }
                return '<polygon points="' . implode(' ', $pontos) . '" ' . $t . '/>';
        }
        return '';
    }

    /** Ícones dos aplicativos do tablet simulado (formato 'aplicativo'). */
    public static function iconeApp(string $app, int $tamanho = 92): string
    {
        $fundo  = '<rect x="4" y="4" width="92" height="92" rx="20" fill="#ffffff" stroke="#111" stroke-width="4"/>';
        $traco  = 'fill="none" stroke="#111" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"';
        $cheio  = 'fill="#111111"';

        switch ($app) {
            case 'Calendário':
                $d = '<rect x="22" y="28" width="56" height="48" rx="6" ' . $traco . '/>'
                   . '<line x1="22" y1="44" x2="78" y2="44" ' . $traco . '/>'
                   . '<line x1="36" y1="20" x2="36" y2="34" ' . $traco . '/>'
                   . '<line x1="64" y1="20" x2="64" y2="34" ' . $traco . '/>'
                   . '<rect x="34" y="54" width="10" height="10" ' . $cheio . '/>';
                break;
            case 'Relógio':
                $d = '<circle cx="50" cy="52" r="26" ' . $traco . '/>'
                   . '<line x1="50" y1="52" x2="50" y2="36" ' . $traco . '/>'
                   . '<line x1="50" y1="52" x2="62" y2="58" ' . $traco . '/>';
                break;
            case 'Fotos':
                $d = '<rect x="22" y="30" width="56" height="44" rx="6" ' . $traco . '/>'
                   . '<circle cx="38" cy="44" r="5" ' . $cheio . '/>'
                   . '<path d="M26 70 L44 50 L58 64 L68 56 L74 62" ' . $traco . '/>';
                break;
            case 'Palavras e figuras':
                $d = '<circle cx="36" cy="40" r="11" ' . $traco . '/>'
                   . '<line x1="26" y1="62" x2="74" y2="62" ' . $traco . '/>'
                   . '<line x1="26" y1="72" x2="60" y2="72" ' . $traco . '/>'
                   . '<path d="M54 34 L64 48 L44 48 Z" ' . $traco . '/>';
                break;
            case 'Música':
                $d = '<circle cx="38" cy="68" r="9" ' . $traco . '/>'
                   . '<line x1="47" y1="68" x2="47" y2="28" ' . $traco . '/>'
                   . '<path d="M47 28 L72 34 L72 44 L47 38" ' . $traco . '/>';
                break;
            default:
                $d = '<circle cx="50" cy="50" r="22" ' . $traco . '/>';
        }
        return '<svg class="figura-app" width="' . $tamanho . '" height="' . $tamanho . '"'
             . ' viewBox="0 0 100 100" aria-hidden="true" focusable="false">' . $fundo . $d . '</svg>';
    }
}
