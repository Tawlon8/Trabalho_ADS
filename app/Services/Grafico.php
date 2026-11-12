<?php
namespace App\Services;

/** Gráficos de evolução em SVG, com descrição em texto para leitor de tela. */
class Grafico
{
    private const W = 440;  // viewBox próximo do tamanho exibido
    private const H = 200;
    private const L = 46;   // espaço dos rótulos do eixo y
    private const R = 14;
    private const T = 14;
    private const B = 30;   // espaço dos números das sessões

    /** Linha do acerto por sessão; com 6 ou mais, as médias de cada metade. $ev vem de Evolucao::resumir. */
    public static function linhaAcerto(array $sessoes, array $ev, string $id): string
    {
        $n = count($sessoes);
        if ($n === 0) {
            return '';
        }
        $larg = self::W - self::L - self::R;
        $x = static fn(int $i): float => $n === 1 ? self::L + $larg / 2 : self::L + $i * $larg / ($n - 1);
        $y = static fn(float $p): float => self::T + (100 - $p) / 100 * (self::H - self::T - self::B);

        $taxas = [];
        foreach ($sessoes as $s) {
            $taxas[] = 100 * (int) $s['acertos'] / max(1, (int) $s['rodadas']);
        }

        $svg = '';

        // grade e eixo y
        foreach ([0, 50, 100] as $p) {
            $svg .= sprintf('<line x1="%d" y1="%.1f" x2="%d" y2="%.1f" class="g-grade"/>',
                            self::L, $y($p), self::W - self::R, $y($p))
                  . sprintf('<text x="%d" y="%.1f" class="g-rotulo" text-anchor="end" dy="4">%d%%</text>',
                            self::L - 8, $y($p), $p);
        }

        // números das sessões (no máximo ~12 rótulos)
        $passo = max(1, (int) ceil($n / 12));
        for ($i = 0; $i < $n; $i += $passo) {
            $svg .= sprintf('<text x="%.1f" y="%d" class="g-rotulo" text-anchor="middle">%d</text>',
                            $x($i), self::H - 8, $i + 1);
        }

        // médias de cada metade
        if ($ev['media_inicio'] !== null) {
            $meio = intdiv($n, 2);
            foreach ([[0, $meio - 1, $ev['media_inicio']], [$n - $meio, $n - 1, $ev['media_fim']]] as [$de, $ate, $media]) {
                $svg .= sprintf('<line x1="%.1f" y1="%.1f" x2="%.1f" y2="%.1f" class="g-media"/>',
                                $x($de), $y($media), $x($ate), $y($media));
            }
        }

        // linha da série
        if ($n > 1) {
            $pontos = [];
            foreach ($taxas as $i => $t) {
                $pontos[] = sprintf('%.1f,%.1f', $x($i), $y($t));
            }
            $svg .= '<polyline points="' . implode(' ', $pontos) . '" class="g-linha"/>';
        }

        // pontos com <title> (aparece com o mouse, não com o toque)
        foreach ($sessoes as $i => $s) {
            $dicas = (int) $s['dicas'];
            $texto = sprintf('Sessão %d (%s): %d de %d acertos (%s%%)%s',
                             $i + 1, date('d/m/Y', strtotime($s['data_aplicacao'])),
                             (int) $s['acertos'], (int) $s['rodadas'], self::num($taxas[$i]),
                             $dicas ? ", $dicas dica" . ($dicas > 1 ? 's' : '') : ', sem dica');
            $svg .= sprintf('<g class="g-ponto"><title>%s</title>'
                          . '<circle cx="%.1f" cy="%.1f" r="14" class="g-alvo"/>'
                          . '<circle cx="%.1f" cy="%.1f" r="5" class="g-marca"/></g>',
                            htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'),
                            $x($i), $y($taxas[$i]), $x($i), $y($taxas[$i]));
        }

        $desc = 'Acerto por sessão: ' . implode(', ', array_map(
            static fn($t) => self::num($t) . '%', $taxas)) . '.';
        if ($ev['media_inicio'] !== null) {
            $desc .= ' Média da primeira metade ' . self::num($ev['media_inicio'])
                   . '%, da segunda metade ' . self::num($ev['media_fim']) . '%.';
        }

        return sprintf('<svg class="grafico" viewBox="0 0 %d %d" role="img" aria-labelledby="%s-t %s-d">'
                     . '<title id="%s-t">Acerto por sessão</title><desc id="%s-d">%s</desc>%s</svg>',
                       self::W, self::H, $id, $id, $id, $id,
                       htmlspecialchars($desc, ENT_QUOTES, 'UTF-8'), $svg);
    }

    /** Símbolo do nível de suporte: círculo vazio, meio cheio ou cheio. */
    public static function simboloSuporte(string $nivel, int $tamanho = 22): string
    {
        $meio = $tamanho / 2;
        $r    = $meio - 2;
        $base = sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" class="s-borda"/>', $meio, $meio, $r);
        $dentro = '';
        if ($nivel === 'sem_suporte') {
            $dentro = sprintf('<circle cx="%.1f" cy="%.1f" r="%.1f" class="s-cheio"/>', $meio, $meio, $r);
        } elseif ($nivel === 'com_ajuda') {
            // metade esquerda preenchida
            $dentro = sprintf('<path d="M%.1f %.1f A%.1f %.1f 0 0 0 %.1f %.1f Z" class="s-cheio"/>',
                              $meio, $meio - $r, $r, $r, $meio, $meio + $r);
        }
        return sprintf('<svg class="simbolo-suporte" width="%d" height="%d" viewBox="0 0 %d %d" aria-hidden="true" focusable="false">%s%s</svg>',
                       $tamanho, $tamanho, $tamanho, $tamanho, $base, $dentro);
    }

    private static function num(float $v): string
    {
        return number_format($v, 1, ',', '.');
    }
}
