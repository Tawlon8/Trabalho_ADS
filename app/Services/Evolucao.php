<?php
namespace App\Services;

/**
 * Indicadores de evolução de uma meta, a partir das sessões em ordem cronológica:
 * acerto médio, autonomia, sessões com dica e variação entre as metades (6 ou mais sessões).
 */
class Evolucao
{
    public static function resumir(array $sessoes): array
    {
        $total = count($sessoes);
        if ($total === 0) {
            return ['total' => 0, 'taxa_media' => null, 'autonomia' => null, 'dependencia_dica' => null,
                    'delta' => null, 'media_inicio' => null, 'media_fim' => null];
        }

        $acertos = 0;
        $rodadas = 0;
        $semSuporte = 0;
        $comDica = 0;
        $taxas = [];
        foreach ($sessoes as $s) {
            $r = max(1, (int) $s['rodadas']);
            $taxas[] = 100 * (int) $s['acertos'] / $r;
            $acertos += (int) $s['acertos'];
            $rodadas += $r;
            if ($s['desempenho'] === 'sem_suporte') { $semSuporte++; }
            if ((int) $s['dicas'] > 0)              { $comDica++; }
        }

        $delta = $inicio = $fim = null;
        if ($total >= 6) {
            $meio = intdiv($total, 2);
            $inicio = array_sum(array_slice($taxas, 0, $meio)) / $meio;
            $fim    = array_sum(array_slice($taxas, $total - $meio)) / $meio;
            $delta  = round($fim - $inicio, 1);
        }

        return [
            'total'            => $total,
            'taxa_media'       => round(100 * $acertos / $rodadas, 1),
            'autonomia'        => round(100 * $semSuporte / $total, 1),
            'dependencia_dica' => round(100 * $comDica / $total, 1),
            'delta'            => $delta,
            'media_inicio'     => $inicio === null ? null : round($inicio, 1),
            'media_fim'        => $fim === null ? null : round($fim, 1),
        ];
    }
}
