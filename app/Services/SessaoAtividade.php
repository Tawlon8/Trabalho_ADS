<?php
namespace App\Services;

use App\Models\Atividade;

/**
 * Mini games: monta as rodadas, classifica o resultado e agrupa as respostas por meta.
 * Rodada: enunciado, cena, opcoes, correta, dica_errada e, no formato aplicativo, etapa.
 * Configuração opcional da atividade (campo itens, JSON): figuras, app, maximo.
 */
class SessaoAtividade
{
    public const FORMATOS = [
        'quantidade',      // conte as figuras e escolha o numeral
        'comparacao',      // qual grupo tem mais (ou menos)
        'sequencia',       // qual numeral falta na sequência
        'figura_palavra',  // veja a figura e escolha a palavra
        'palavra_figura',  // leia a palavra e escolha a figura
        'figura_letra',    // com que letra começa
        'silaba',          // complete a sílaba que falta
        'aplicativo',      // abrir, usar e fechar um aplicativo no tablet simulado
        'soma',            // junte dois grupos e escolha o total
        'silaba_inicial',  // com que sílaba começa
        'ouvir_figura',    // ouça a palavra (voz do navegador) e escolha a figura
        'janela',          // toque no botão pedido: fechar, minimizar ou voltar
    ];

    /** Nome de cada formato, para telas e relatórios. */
    public const NOMES = [
        'quantidade'     => 'Contar e escolher o numeral',
        'comparacao'     => 'Comparar quantidades',
        'sequencia'      => 'Completar a sequência de numerais',
        'figura_palavra' => 'Associar figura e palavra',
        'palavra_figura' => 'Associar palavra e figura',
        'figura_letra'   => 'Letra inicial',
        'silaba'         => 'Completar a sílaba',
        'aplicativo'     => 'Abrir, usar e fechar o aplicativo',
        'soma'           => 'Juntar quantidades',
        'silaba_inicial' => 'Sílaba inicial',
        'ouvir_figura'   => 'Ouvir a palavra e escolher a figura',
        'janela'         => 'Botões da janela (fechar, minimizar, voltar)',
    ];

    private const APPS = ['Calendário', 'Relógio', 'Fotos', 'Palavras e figuras', 'Música'];

    private const ACOES = ['fechar' => 'Fechar', 'minimizar' => 'Minimizar', 'voltar' => 'Voltar'];

    /** Rodadas da atividade, ou null se o formato não existe. */
    public static function montar(array $atividade, int $rodadas, int $maximo): ?array
    {
        $formato = $atividade['formato'] ?? null;
        if (!in_array($formato, self::FORMATOS, true)) {
            return null;
        }

        $config  = json_decode((string) ($atividade['itens'] ?? ''), true);
        $config  = is_array($config) ? $config : [];
        $figuras = self::figurasDisponiveis($config);
        $rodadas = max(1, $rodadas);
        $maximo  = max(2, min(10, (int) ($config['maximo'] ?? $maximo)));   // até 10 cabe na tela

        if ($formato === 'aplicativo') {
            $itens = self::montarAplicativo($config, $figuras);
        } else {
            $itens = [];
            for ($i = 0; $i < $rodadas; $i++) {
                $item = self::rodada($formato, $figuras, $maximo);
                if ($item === null) {
                    return null;           // figuras insuficientes
                }
                $itens[] = $item;
            }
        }
        return $itens === null ? null : array_map([self::class, 'sortearErradaDaDica'], $itens);
    }

    /** Sorteia a opção errada que a dica mantém, para a posição não indicar a certa. */
    private static function sortearErradaDaDica(array $item): array
    {
        $erradas = array_values(array_diff(array_keys($item['opcoes']), [$item['correta']]));
        if (count($item['opcoes']) > 2 && $erradas) {
            $item['dica_errada'] = $erradas[random_int(0, count($erradas) - 1)];
        }
        return $item;
    }

    /** Uma rodada de cada formato. */
    private static function rodada(string $formato, array $figuras, int $maximo): ?array
    {
        switch ($formato) {
            case 'quantidade':
                $quantidade = random_int(1, $maximo);
                $figura     = $figuras[array_rand($figuras)];
                $opcoes     = [];
                for ($n = 1; $n <= $maximo; $n++) {
                    $opcoes[] = ['tipo' => 'numero', 'valor' => $n, 'rotulo' => (string) $n];
                }
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Conte as figuras e toque no número certo.',
                    'cena'      => ['tipo' => 'contagem', 'nome' => $figura, 'quantidade' => $quantidade],
                    'opcoes'    => $opcoes,
                    'correta'   => $quantidade - 1,
                ];

            case 'comparacao':
                $a = random_int(1, $maximo);
                do { $b = random_int(1, $maximo); } while ($b === $a);
                $mais   = random_int(0, 1) === 1;
                $par    = self::duasFiguras($figuras);
                $grupos = [
                    ['nome' => $par[0], 'quantidade' => $a],
                    ['nome' => $par[1], 'quantidade' => $b],
                ];
                $vencedor = $mais ? ($a > $b ? 0 : 1) : ($a < $b ? 0 : 1);
                return [
                    'tipo'      => $formato,
                    'enunciado' => $mais ? 'Toque no grupo que tem MAIS figuras.'
                                         : 'Toque no grupo que tem MENOS figuras.',
                    'cena'      => ['tipo' => 'nenhuma'],
                    'opcoes'    => [
                        ['tipo' => 'grupo', 'nome' => $grupos[0]['nome'], 'quantidade' => $a,
                         'rotulo' => 'Primeiro grupo'],
                        ['tipo' => 'grupo', 'nome' => $grupos[1]['nome'], 'quantidade' => $b,
                         'rotulo' => 'Segundo grupo'],
                    ],
                    'correta'   => $vencedor,
                ];

            case 'sequencia':
                $inicio = random_int(1, max(1, $maximo - 3));
                $numeros = [$inicio, $inicio + 1, $inicio + 2, $inicio + 3];
                $posicao = random_int(1, 3);
                $falta   = $numeros[$posicao];
                $numeros[$posicao] = null;
                $opcoes  = [];
                foreach (self::numerosProximos($falta, $maximo) as $n) {
                    $opcoes[] = ['tipo' => 'numero', 'valor' => $n, 'rotulo' => (string) $n];
                }
                $correta = array_search($falta, array_column($opcoes, 'valor'), true);
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Qual número está faltando?',
                    'cena'      => ['tipo' => 'sequencia', 'numeros' => $numeros],
                    'opcoes'    => $opcoes,
                    'correta'   => (int) $correta,
                ];

            case 'figura_palavra':
                $tres = self::tresFiguras($figuras);
                if (!$tres) { return null; }
                $certa  = $tres[0];
                $ordem  = self::embaralhar($tres);
                $opcoes = [];
                foreach ($ordem as $f) {
                    $opcoes[] = ['tipo' => 'texto', 'valor' => Figuras::palavra($f),
                                 'rotulo' => Figuras::palavra($f)];
                }
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Olhe a figura e toque na palavra certa.',
                    'cena'      => ['tipo' => 'figura', 'nome' => $certa],
                    'opcoes'    => $opcoes,
                    'correta'   => (int) array_search($certa, $ordem, true),
                ];

            case 'palavra_figura':
            case 'ouvir_figura':
                $tres = self::tresFiguras($figuras);
                if (!$tres) { return null; }
                $certa  = $tres[0];
                $ordem  = self::embaralhar($tres);
                $opcoes = [];
                foreach ($ordem as $i => $f) {
                    $opcoes[] = ['tipo' => 'figura', 'nome' => $f,
                                 'rotulo' => 'Figura ' . ($i + 1)];
                }
                $ouvir = $formato === 'ouvir_figura';
                return [
                    'tipo'      => $formato,
                    'enunciado' => $ouvir ? 'Toque em "Ouvir a palavra", escute e toque na figura certa.'
                                          : 'Leia a palavra e toque na figura certa.',
                    'cena'      => ['tipo' => $ouvir ? 'ouvir' : 'palavra', 'texto' => Figuras::palavra($certa)],
                    'opcoes'    => $opcoes,
                    'correta'   => (int) array_search($certa, $ordem, true),
                ];

            case 'figura_letra':
                return self::inicio($formato, $figuras, 'Com que letra começa o nome desta figura?', 'Letra ',
                    static fn($f) => Figuras::letra($f));

            case 'silaba_inicial':
                return self::inicio($formato, $figuras, 'Com que pedaço começa o nome desta figura?', '',
                    static fn($f) => Figuras::silabas($f)[0]);

            case 'soma':
                $total  = random_int(2, $maximo);   // total sorteado primeiro: distribuição uniforme
                $a      = random_int(1, $total - 1);
                $b      = $total - $a;
                $opcoes = [];
                foreach (self::numerosProximos($a + $b, $maximo) as $n) {
                    $opcoes[] = ['tipo' => 'numero', 'valor' => $n, 'rotulo' => (string) $n];
                }
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Junte os dois grupos. Quantas figuras são ao todo?',
                    'cena'      => ['tipo' => 'soma', 'nome' => $figuras[array_rand($figuras)],
                                    'grupos' => [$a, $b]],
                    'opcoes'    => $opcoes,
                    'correta'   => (int) array_search($a + $b, array_column($opcoes, 'valor'), true),
                ];

            case 'janela':
                $opcoes = self::opcoesJanela();
                $pedida = $opcoes[array_rand($opcoes)];
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Toque no botão "' . $pedida['rotulo'] . '".',
                    'cena'      => ['tipo' => 'app', 'app' => self::APPS[array_rand(self::APPS)],
                                    'nome' => $figuras[array_rand($figuras)]],
                    'opcoes'    => $opcoes,
                    'correta'   => (int) array_search($pedida['acao'], array_column($opcoes, 'acao'), true),
                ];

            case 'silaba':
                $comSilabas = array_values(array_filter(
                    $figuras,
                    static fn($f) => count(Figuras::silabas($f)) >= 2
                ));
                if (count($comSilabas) < 2) { return null; }
                $certa    = $comSilabas[array_rand($comSilabas)];
                $silabas  = Figuras::silabas($certa);
                $posicao  = random_int(0, count($silabas) - 1);
                $faltante = $silabas[$posicao];

                $mostrado = $silabas;
                $mostrado[$posicao] = '__';

                $opcoes = [$faltante];
                foreach (self::embaralhar($comSilabas) as $f) {
                    foreach (Figuras::silabas($f) as $s) {
                        if (!in_array($s, $opcoes, true)) { $opcoes[] = $s; }
                        if (count($opcoes) === 3) { break 2; }
                    }
                }
                if (count($opcoes) < 3) { return null; }
                $ordem = self::embaralhar($opcoes);
                $botoes = [];
                foreach ($ordem as $s) {
                    $botoes[] = ['tipo' => 'texto', 'valor' => $s, 'rotulo' => $s];
                }
                return [
                    'tipo'      => $formato,
                    'enunciado' => 'Qual pedaço falta para escrever o nome da figura?',
                    'cena'      => ['tipo' => 'figura_palavra', 'nome' => $certa,
                                    'texto' => implode(' - ', $mostrado)],
                    'opcoes'    => $botoes,
                    'correta'   => (int) array_search($faltante, $ordem, true),
                ];
        }
        return null;
    }

    /** Formato aplicativo: abrir, usar o jogo (três rodadas) e fechar. */
    private static function montarAplicativo(array $config, array $figuras): ?array
    {
        $app = $config['app'] ?? 'Palavras e figuras';
        if (!in_array($app, self::APPS, true)) {
            $app = 'Palavras e figuras';
        }

        $icones = self::embaralhar(self::APPS);
        $icones = array_slice($icones, 0, 4);
        if (!in_array($app, $icones, true)) {
            $icones[0] = $app;
            $icones    = self::embaralhar($icones);
        }
        $opcoesApp = [];
        foreach ($icones as $nome) {
            $opcoesApp[] = ['tipo' => 'icone', 'app' => $nome, 'valor' => $nome, 'rotulo' => $nome];
        }

        $itens = [[
            'tipo'      => 'aplicativo',
            'etapa'     => 'abrir',
            'etapa_rotulo' => 'Etapa 1 de 3: abrir o aplicativo',
            'enunciado' => 'Toque no aplicativo "' . $app . '" para abrir.',
            'cena'      => ['tipo' => 'tablet'],
            'opcoes'    => $opcoesApp,
            'correta'   => (int) array_search($app, $icones, true),
        ]];

        for ($i = 0; $i < 3; $i++) {   // jogo de figura e palavra
            $tres = self::tresFiguras($figuras);
            if (!$tres) { return null; }
            $certa = $tres[0];
            $ordem = self::embaralhar($tres);
            $opcoes = [];
            foreach ($ordem as $f) {
                $opcoes[] = ['tipo' => 'texto', 'valor' => Figuras::palavra($f),
                             'rotulo' => Figuras::palavra($f)];
            }
            $itens[] = [
                'tipo'      => 'aplicativo',
                'etapa'     => 'jogo',
                'etapa_rotulo' => 'Etapa 2 de 3: usar o jogo dentro do aplicativo ('
                                  . ($i + 1) . ' de 3)',
                'enunciado' => 'Olhe a figura e toque na palavra certa.',
                'cena'      => ['tipo' => 'app', 'app' => $app, 'nome' => $certa],
                'opcoes'    => $opcoes,
                'correta'   => (int) array_search($certa, $ordem, true),
            ];
        }

        $opcoesAcao = self::opcoesJanela();
        $itens[] = [
            'tipo'      => 'aplicativo',
            'etapa'     => 'fechar',
            'etapa_rotulo' => 'Etapa 3 de 3: fechar o aplicativo',
            'enunciado' => 'Terminou. Toque em "Fechar" para sair do aplicativo.',
            'cena'      => ['tipo' => 'app', 'app' => $app, 'nome' => null],
            'opcoes'    => $opcoesAcao,
            'correta'   => (int) array_search('fechar', array_column($opcoesAcao, 'acao'), true),
        ];

        return $itens;
    }

    // ------------------------------------------------------------------ apoio

    /** Botões Fechar / Minimizar / Voltar em ordem sorteada. */
    private static function opcoesJanela(): array
    {
        $opcoes = [];
        foreach (self::embaralhar(array_keys(self::ACOES)) as $acao) {
            $opcoes[] = ['tipo' => 'janela', 'acao' => $acao,
                         'valor' => self::ACOES[$acao], 'rotulo' => self::ACOES[$acao]];
        }
        return $opcoes;
    }

    /** Figura e três inícios de palavra (letra ou sílaba). */
    private static function inicio(string $formato, array $figuras, string $enunciado,
                                   string $prefixo, callable $inicioDe): ?array
    {
        $certa  = $figuras[array_rand($figuras)];
        $certo  = $inicioDe($certa);
        $lista  = [$certo];
        foreach (self::embaralhar($figuras) as $f) {
            $x = $inicioDe($f);
            if (!in_array($x, $lista, true)) { $lista[] = $x; }
            if (count($lista) === 3) { break; }
        }
        if (count($lista) < 3) { return null; }
        $ordem  = self::embaralhar($lista);
        $opcoes = [];
        foreach ($ordem as $x) {
            $opcoes[] = ['tipo' => 'texto', 'valor' => $x, 'rotulo' => $prefixo . $x];
        }
        return [
            'tipo'      => $formato,
            'enunciado' => $enunciado,
            'cena'      => ['tipo' => 'figura', 'nome' => $certa],
            'opcoes'    => $opcoes,
            'correta'   => (int) array_search($certo, $ordem, true),
        ];
    }

    /** Figuras que a atividade pode usar: as do campo 'itens' ou o catálogo inteiro. */
    private static function figurasDisponiveis(array $config): array
    {
        $escolhidas = [];
        foreach ((array) ($config['figuras'] ?? []) as $nome) {
            if (is_string($nome) && Figuras::existe($nome)) {
                $escolhidas[] = $nome;
            }
        }
        return $escolhidas ?: Figuras::nomes();
    }

    /** Sorteia três figuras diferentes; a primeira é a certa. */
    private static function tresFiguras(array $figuras): ?array
    {
        if (count($figuras) < 3) {
            return null;
        }
        $chaves = (array) array_rand($figuras, 3);
        $saida  = [];
        foreach ($chaves as $k) {
            $saida[] = $figuras[$k];
        }
        shuffle($saida);
        return $saida;
    }

    private static function duasFiguras(array $figuras): array
    {
        if (count($figuras) < 2) {
            return [$figuras[0], $figuras[0]];
        }
        $chaves = (array) array_rand($figuras, 2);
        return [$figuras[$chaves[0]], $figuras[$chaves[1]]];
    }

    /** Três numerais seguidos com o alvo em posição sorteada, dentro de 1..$maximo. */
    private static function numerosProximos(int $alvo, int $maximo): array
    {
        $inicios = [];
        for ($k = 0; $k <= 2; $k++) {
            if ($alvo - $k >= 1 && $alvo - $k + 2 <= $maximo) {
                $inicios[] = $alvo - $k;
            }
        }
        if (!$inicios) {
            return range(1, $maximo);   // máximo 2: só cabem dois numerais
        }
        $inicio = $inicios[random_int(0, count($inicios) - 1)];
        return [$inicio, $inicio + 1, $inicio + 2];
    }

    private static function embaralhar(array $lista): array
    {
        $copia = array_values($lista);
        shuffle($copia);
        return $copia;
    }

    /** Índices das opções exibidas; com dica, só a correta e a dica_errada. */
    public static function opcoesVisiveis(array $item, bool $dica): array
    {
        $todas = array_keys($item['opcoes']);
        if (!$dica || count($todas) <= 2) {
            return $todas;
        }
        $errada = $item['dica_errada'] ?? null;
        if ($errada === null || $errada === $item['correta'] || !isset($item['opcoes'][$errada])) {
            $errada = array_values(array_diff($todas, [$item['correta']]))[0];
        }
        $par = [$item['correta'], $errada];
        sort($par);
        return $par;
    }

    /** Dica só com mais de duas opções. */
    public static function temDica(array $item): bool
    {
        return count($item['opcoes']) > 2;
    }

    /** Meta de destino de cada área: a de menor id. */
    public static function metaPorArea(array $metas): array
    {
        $porArea = [];
        foreach ($metas as $m) {
            $atual = $porArea[$m['area']] ?? null;
            if ($atual === null || (int) $m['id'] < (int) $atual['id']) {
                $porArea[$m['area']] = $m;
            }
        }
        return $porArea;
    }

    /**
     * Nível de suporte (regra do protótipo, não validada): nenhum acerto = nao_realiza;
     * tudo certo sem dica = sem_suporte; demais casos = com_ajuda.
     */
    public static function classificar(int $acertos, int $rodadas, int $dicas): string
    {
        if ($acertos <= 0) {
            return 'nao_realiza';
        }
        if ($acertos >= $rodadas && $dicas === 0) {
            return 'sem_suporte';
        }
        return 'com_ajuda';
    }

    /** Lê a atividade e confirma que ela é executável na tela. */
    public static function atividadeExecutavel(int $id): ?array
    {
        $a = Atividade::buscar($id);
        return ($a && in_array($a['formato'] ?? null, self::FORMATOS, true)) ? $a : null;
    }

    /** Soma as respostas por meta, guardando o detalhamento por jogo e por etapa. */
    public static function agrupar(array $respostas): array
    {
        $porMeta = [];
        foreach ($respostas as $r) {
            $meta = $r['meta_id'];
            if (!isset($porMeta[$meta])) {
                $porMeta[$meta] = [
                    'atividade_id' => $r['atividade_id'],
                    'rodadas'      => 0,
                    'acertos'      => 0,
                    'dicas'        => 0,
                    'segundos'     => 0,
                    'jogos'        => [],
                ];
            }
            $g = &$porMeta[$meta];
            $g['rodadas']++;
            $g['acertos']  += $r['acertou'] ? 1 : 0;
            $g['dicas']    += $r['dica'] ? 1 : 0;
            $g['segundos'] += (int) $r['segundos'];

            $jogo = $r['jogo'];
            if (!isset($g['jogos'][$jogo])) {
                $g['jogos'][$jogo] = ['total' => 0, 'acertos' => 0, 'etapas' => []];
            }
            $g['jogos'][$jogo]['total']++;
            $g['jogos'][$jogo]['acertos'] += $r['acertou'] ? 1 : 0;

            if (!empty($r['etapa'])) {
                $etapa = $r['etapa'];
                $e = &$g['jogos'][$jogo]['etapas'][$etapa];
                $e['total']    = ($e['total'] ?? 0) + 1;
                $e['acertos']  = ($e['acertos'] ?? 0) + ($r['acertou'] ? 1 : 0);
                $e['segundos'] = ($e['segundos'] ?? 0) + (int) $r['segundos'];
                unset($e);
            }
            unset($g);
        }
        return $porMeta;
    }

    /** Observação do registro: acertos por jogo e por etapa. */
    public static function detalhar(array $g): string
    {
        $nomes  = ['abrir' => 'Abrir', 'jogo' => 'Usar o jogo', 'fechar' => 'Fechar'];
        $partes = [];

        foreach ($g['jogos'] as $titulo => $j) {
            $texto = $titulo . ': ' . $j['acertos'] . ' de ' . $j['total'];

            if (!empty($j['etapas'])) {
                $detalhe = [];
                foreach ($nomes as $chave => $nome) {
                    if (isset($j['etapas'][$chave])) {
                        $e = $j['etapas'][$chave];
                        $linha = $nome . ': ' . $e['acertos'] . ' de ' . $e['total'];
                        if ($chave === 'abrir') {
                            $linha .= ' em ' . $e['segundos'] . ' s';
                        }
                        $detalhe[] = $linha;
                    }
                }
                if ($detalhe) {
                    $texto .= ' (' . implode('; ', $detalhe) . ')';
                }
            }
            $partes[] = $texto;
        }
        return implode('. ', $partes) . '.';
    }
}
