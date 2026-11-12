<?php
/** Testes das regras sem banco. Uso: php tests/run.php */
require dirname(__DIR__) . '/core/bootstrap.php';

use App\Services\Evolucao;
use App\Services\SessaoAtividade;
use Core\Validator;

$falhas = 0;
$total  = 0;

function verifica(string $descricao, bool $condicao): void
{
    global $falhas, $total;
    $total++;
    if ($condicao) {
        echo "  ok     $descricao\n";
    } else {
        $falhas++;
        echo "  FALHA  $descricao\n";
    }
}

echo "Regra de classificação do nível de suporte\n";
verifica('nenhuma resposta correta => nao_realiza',
    SessaoAtividade::classificar(0, 5, 0) === 'nao_realiza');
verifica('todas corretas e sem dica => sem_suporte',
    SessaoAtividade::classificar(5, 5, 0) === 'sem_suporte');
verifica('todas corretas com uma dica => com_ajuda',
    SessaoAtividade::classificar(5, 5, 1) === 'com_ajuda');
verifica('quatro de cinco, sem dica => com_ajuda',
    SessaoAtividade::classificar(4, 5, 0) === 'com_ajuda');
verifica('uma correta de cinco => com_ajuda',
    SessaoAtividade::classificar(1, 5, 3) === 'com_ajuda');

echo "Validações do servidor (sem banco)\n";
$v = Validator::make(['nome' => ''], ['nome' => 'required'], ['nome' => 'Nome']);
verifica('campo obrigatório vazio gera erro', $v->fails() && isset($v->errors()['nome']));

$v = Validator::make(['nome' => 'Al'], ['nome' => 'required|min:3']);
verifica('mínimo de caracteres é aplicado', $v->fails());

$v = Validator::make(['email' => 'nao-e-email'], ['email' => 'email']);
verifica('e-mail inválido é recusado', $v->fails());

$v = Validator::make(['email' => 'prof@exemplo.com'], ['email' => 'required|email']);
verifica('e-mail válido passa', !$v->fails());

$v = Validator::make(['area' => 'inexistente'], ['area' => 'in:matematica,leitura_escrita']);
verifica('valor fora da lista é recusado', $v->fails());

$v = Validator::make(['senha' => 'abcdefgh', 'senha_confirm' => 'outra'], ['senha' => 'confirmed']);
verifica('confirmação de senha diferente é recusada', $v->fails());

$v = Validator::make(['data' => '2030-01-01'], ['data' => 'date|past_or_today']);
verifica('data futura é recusada quando só se aceita data passada ou hoje', $v->fails());

$v = Validator::make(['data' => '2020-02-30'], ['data' => 'date']);
verifica('data inexistente (30 de fevereiro) é recusada', $v->fails());

$v = Validator::make(['aceite' => ''], ['aceite' => 'accepted']);
verifica('aceite não marcado é recusado', $v->fails());

echo "\nMini games: montagem das rodadas\n";

use App\Services\Figuras;

/* Rodadas sorteadas: cada regra é conferida em vários sorteios. */
function paraCada(int $vezes, callable $f): bool
{
    for ($i = 0; $i < $vezes; $i++) {
        if (!$f()) { return false; }
    }
    return true;
}

verifica('o catálogo de figuras traz palavra, letra inicial e sílabas de cada figura',
    paraCada(1, function () {
        foreach (Figuras::nomes() as $n) {
            if (Figuras::palavra($n) === '' || Figuras::letra($n) === ''
                || !Figuras::silabas($n) || Figuras::svg($n) === '') { return false; }
        }
        return true;
    }));

verifica('contar: o número certo é o de figuras desenhadas',
    paraCada(30, function () {
        $r = SessaoAtividade::montar(['formato' => 'quantidade'], 4, 5);
        if (count($r) !== 4) { return false; }
        foreach ($r as $item) {
            if ($item['opcoes'][$item['correta']]['valor'] !== $item['cena']['quantidade']) { return false; }
        }
        return true;
    }));

verifica('comparar: a resposta certa é o grupo com mais (ou menos) figuras, conforme o pedido',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'comparacao'], 1, 5)[0];
        $a = $item['opcoes'][0]['quantidade'];
        $b = $item['opcoes'][1]['quantidade'];
        if ($a === $b) { return false; }
        $pedeMais = strpos($item['enunciado'], 'MAIS') !== false;
        $esperada = $pedeMais ? ($a > $b ? 0 : 1) : ($a < $b ? 0 : 1);
        return $item['correta'] === $esperada;
    }));

verifica('sequência: a resposta certa é o número que falta, e ele não aparece na cena',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'sequencia'], 1, 5)[0];
        $faltando = array_search(null, $item['cena']['numeros'], true);
        if ($faltando === false) { return false; }
        $vizinho = $item['cena']['numeros'][$faltando - 1] ?? null;
        $certo   = $item['opcoes'][$item['correta']]['valor'];
        return $vizinho === null || $certo === $vizinho + 1;
    }));

verifica('figura e palavra: a palavra certa é o nome da figura mostrada',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'figura_palavra'], 1, 5)[0];
        return count($item['opcoes']) === 3
            && $item['opcoes'][$item['correta']]['valor'] === Figuras::palavra($item['cena']['nome']);
    }));

verifica('palavra e figura: a figura certa é a da palavra mostrada',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'palavra_figura'], 1, 5)[0];
        return Figuras::palavra($item['opcoes'][$item['correta']]['nome']) === $item['cena']['texto'];
    }));

verifica('letra inicial: a letra certa é a inicial do nome da figura',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'figura_letra'], 1, 5)[0];
        return count($item['opcoes']) === 3
            && $item['opcoes'][$item['correta']]['valor'] === Figuras::letra($item['cena']['nome']);
    }));

verifica('sílaba: a sílaba certa completa o nome da figura',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'silaba'], 1, 5)[0];
        $montada = str_replace('__', $item['opcoes'][$item['correta']]['valor'],
                               str_replace(' - ', '', $item['cena']['texto']));
        return $montada === Figuras::palavra($item['cena']['nome']);
    }));

verifica('juntar: a resposta certa é a soma dos dois grupos, sem passar do máximo',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'soma'], 1, 5)[0];
        $soma = array_sum($item['cena']['grupos']);
        return $soma <= 5 && min($item['cena']['grupos']) >= 1
            && $item['opcoes'][$item['correta']]['valor'] === $soma;
    }));

verifica('sílaba inicial: a sílaba certa é a primeira do nome da figura',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'silaba_inicial'], 1, 5)[0];
        return count($item['opcoes']) === 3
            && $item['opcoes'][$item['correta']]['valor'] === Figuras::silabas($item['cena']['nome'])[0];
    }));

verifica('ouvir: a palavra não aparece escrita e a figura certa é a da palavra falada',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'ouvir_figura'], 1, 5)[0];
        return $item['cena']['tipo'] === 'ouvir'
            && Figuras::palavra($item['opcoes'][$item['correta']]['nome']) === $item['cena']['texto'];
    }));

verifica('janela: a resposta certa é o botão pedido no enunciado',
    paraCada(30, function () {
        $item = SessaoAtividade::montar(['formato' => 'janela'], 1, 5)[0];
        return strpos($item['enunciado'], $item['opcoes'][$item['correta']]['rotulo']) !== false;
    }));

verifica('o campo itens pode aumentar o máximo para 10 (e não passa de 10)',
    paraCada(10, function () {
        $ate10 = SessaoAtividade::montar(['formato' => 'quantidade', 'itens' => '{"maximo":10}'], 1, 5)[0];
        $ate99 = SessaoAtividade::montar(['formato' => 'quantidade', 'itens' => '{"maximo":99}'], 1, 5)[0];
        return count($ate10['opcoes']) === 10 && count($ate99['opcoes']) === 10;
    }));

verifica('as opções nunca repetem a mesma resposta',
    paraCada(30, function () {
        foreach (['figura_palavra', 'figura_letra', 'silaba', 'silaba_inicial', 'soma', 'janela'] as $f) {
            $item = SessaoAtividade::montar(['formato' => $f], 1, 5)[0];
            $valores = array_column($item['opcoes'], 'valor');
            if (count($valores) !== count(array_unique($valores))) { return false; }
        }
        return true;
    }));

verifica('a resposta certa aparece nas três posições, também nos jogos de numerais (tocar sempre no meio não basta)',
    (function () {
        foreach (['figura_palavra', 'soma', 'sequencia'] as $f) {
            $posicoes = [0, 0, 0];
            for ($i = 0; $i < 300; $i++) {
                $posicoes[SessaoAtividade::montar(['formato' => $f], 1, 10)[0]['correta']]++;
            }
            if (min($posicoes) < 45 || max($posicoes) > 165) { return false; }   // cada posição entre 15% e 55%
        }
        return true;
    })());

verifica('o campo itens pode restringir as figuras usadas',
    paraCada(10, function () {
        $item = SessaoAtividade::montar(
            ['formato' => 'figura_palavra', 'itens' => '{"figuras":["bola","casa","sol"]}'], 1, 5)[0];
        foreach ($item['opcoes'] as $o) {
            if (!in_array($o['valor'], ['BOLA', 'CASA', 'SOL'], true)) { return false; }
        }
        return true;
    }));

verifica('formato não implementado não gera sessão',
    SessaoAtividade::montar(['formato' => 'ordem'], 5, 5) === null);
verifica('atividade sem formato não gera sessão',
    SessaoAtividade::montar(['formato' => null], 5, 5) === null);

echo "\nMini game do tablet (alfabetização digital)\n";

verifica('o tablet tem as etapas abrir, usar o jogo e fechar',
    paraCada(20, function () {
        $r = SessaoAtividade::montar(['formato' => 'aplicativo'], 5, 5);
        $etapas = array_column($r, 'etapa');
        return $etapas[0] === 'abrir' && end($etapas) === 'fechar'
            && in_array('jogo', $etapas, true);
    }));

verifica('na etapa abrir, a resposta certa é o aplicativo pedido no enunciado',
    paraCada(20, function () {
        $item = SessaoAtividade::montar(['formato' => 'aplicativo'], 5, 5)[0];
        $app  = $item['opcoes'][$item['correta']]['app'];
        return count($item['opcoes']) === 4 && strpos($item['enunciado'], $app) !== false;
    }));

verifica('na etapa fechar, a resposta certa é a ação de fechar',
    paraCada(20, function () {
        $r = SessaoAtividade::montar(['formato' => 'aplicativo'], 5, 5);
        $item = end($r);
        return $item['opcoes'][$item['correta']]['acao'] === 'fechar';
    }));

verifica('cada rodada do tablet diz em que etapa a criança está',
    paraCada(10, function () {
        $r = SessaoAtividade::montar(['formato' => 'aplicativo'], 5, 5);
        foreach ($r as $item) {
            if (empty($item['etapa_rotulo'])) { return false; }
        }
        return strpos($r[0]['etapa_rotulo'], 'Etapa 1') === 0
            && strpos($r[1]['etapa_rotulo'], 'Etapa 2') === 0
            && strpos(end($r)['etapa_rotulo'], 'Etapa 3') === 0;
    }));

verifica('o aplicativo pedido pode ser escolhido no cadastro',
    paraCada(10, function () {
        $item = SessaoAtividade::montar(
            ['formato' => 'aplicativo', 'itens' => '{"app":"Fotos"}'], 5, 5)[0];
        return $item['opcoes'][$item['correta']]['app'] === 'Fotos';
    }));

echo "\nDica\n";

$umItem = SessaoAtividade::montar(['formato' => 'figura_palavra'], 1, 5)[0];
verifica('a dica deixa duas opções, sempre incluindo a correta',
    count(SessaoAtividade::opcoesVisiveis($umItem, true)) === 2
    && in_array($umItem['correta'], SessaoAtividade::opcoesVisiveis($umItem, true), true));
verifica('com a dica, a posição não revela a resposta: a certa fica ora em 1º, ora em 2º lugar, em todos os formatos',
    (function () {
        foreach (['figura_palavra', 'quantidade', 'soma', 'sequencia', 'silaba'] as $f) {
            $primeiras = 0;
            for ($i = 0; $i < 200; $i++) {
                $it = SessaoAtividade::montar(['formato' => $f], 1, 5)[0];
                $v  = SessaoAtividade::opcoesVisiveis($it, true);
                if ($v[0] === $it['correta']) { $primeiras++; }
            }
            if ($primeiras < 40 || $primeiras > 160) { return false; }   // entre 20% e 80% em 1º lugar
        }
        return true;
    })());
verifica('sem dica, todas as opções aparecem',
    count(SessaoAtividade::opcoesVisiveis($umItem, false)) === 3);
$comparar = SessaoAtividade::montar(['formato' => 'comparacao'], 1, 5)[0];
verifica('no jogo de comparar (só duas opções) não há dica; nos de três opções, há',
    !SessaoAtividade::temDica($comparar) && SessaoAtividade::temDica($umItem));

echo "\nCadastro de atividade com mini game\n";

$regra = ['formato' => 'required|in:' . implode(',', SessaoAtividade::FORMATOS)];
verifica('atividade sem mini game (não medida pelo sistema) é recusada',
    Validator::make(['formato' => ''], $regra)->fails());
verifica('um mini game existente é aceito no cadastro',
    Validator::make(['formato' => 'figura_palavra'], $regra)->fails() === false);
verifica('um mini game inexistente é recusado no cadastro',
    Validator::make(['formato' => 'inventado'], $regra)->fails());
verifica('todo mini game tem nome para aparecer na tela',
    count(SessaoAtividade::NOMES) === count(SessaoAtividade::FORMATOS)
    && !array_diff(array_keys(SessaoAtividade::NOMES), SessaoAtividade::FORMATOS));

echo "\nSessões na tela: separação dos resultados por meta\n";

$respostas = [
    ['meta_id' => 7, 'atividade_id' => 3, 'jogo' => 'Pareamento', 'etapa' => null,
     'acertou' => true,  'dica' => false, 'segundos' => 4],
    ['meta_id' => 7, 'atividade_id' => 3, 'jogo' => 'Pareamento', 'etapa' => null,
     'acertou' => false, 'dica' => true,  'segundos' => 6],
    ['meta_id' => 9, 'atividade_id' => 5, 'jogo' => 'Aplicativo', 'etapa' => 'abrir',
     'acertou' => true,  'dica' => false, 'segundos' => 12],
    ['meta_id' => 9, 'atividade_id' => 5, 'jogo' => 'Aplicativo', 'etapa' => 'jogo',
     'acertou' => true,  'dica' => false, 'segundos' => 5],
    ['meta_id' => 9, 'atividade_id' => 5, 'jogo' => 'Aplicativo', 'etapa' => 'fechar',
     'acertou' => false, 'dica' => false, 'segundos' => 3],
];
$grupos = SessaoAtividade::agrupar($respostas);
verifica('dois jogos de áreas diferentes geram dois grupos, um por meta',
    count($grupos) === 2 && isset($grupos[7], $grupos[9]));
verifica('cada meta soma suas rodadas, acertos, dicas e tempo',
    $grupos[7]['rodadas'] === 2 && $grupos[7]['acertos'] === 1
    && $grupos[7]['dicas'] === 1 && $grupos[7]['segundos'] === 10);
verifica('as etapas do aplicativo ficam guardadas na meta',
    count($grupos[9]['jogos']['Aplicativo']['etapas']) === 3);
verifica('a observação registra as etapas e o tempo até abrir',
    strpos(SessaoAtividade::detalhar($grupos[9]), 'Abrir: 1 de 1 em 12 s') !== false);

$mesmaMeta = SessaoAtividade::agrupar([
    ['meta_id' => 7, 'atividade_id' => 3, 'jogo' => 'Jogo A', 'etapa' => null,
     'acertou' => true,  'dica' => false, 'segundos' => 1],
    ['meta_id' => 7, 'atividade_id' => 4, 'jogo' => 'Jogo B', 'etapa' => null,
     'acertou' => false, 'dica' => false, 'segundos' => 2],
]);
verifica('dois jogos da mesma meta somam num só registro',
    $mesmaMeta[7]['rodadas'] === 2 && count($mesmaMeta[7]['jogos']) === 2);
verifica('a observação separa o resultado de cada jogo',
    SessaoAtividade::detalhar($mesmaMeta[7]) === 'Jogo A: 1 de 1. Jogo B: 0 de 1.');

$destino = SessaoAtividade::metaPorArea([
    ['id' => 12, 'area' => 'leitura_escrita', 'descricao' => 'Segunda meta'],
    ['id' => 8,  'area' => 'leitura_escrita', 'descricao' => 'Primeira meta'],
    ['id' => 5,  'area' => 'matematica',      'descricao' => 'Meta de matemática'],
]);
verifica('com duas metas na mesma área, o resultado vai para a primeira cadastrada; área sem meta fica de fora',
    $destino['leitura_escrita']['id'] === 8 && $destino['matematica']['id'] === 5
    && !isset($destino['comunicacao_oral']));

echo "\nProporções de evolução\n";

$sessoes = [];
for ($i = 0; $i < 6; $i++) {
    $sessoes[] = ['acertos' => $i < 3 ? 2 : 4, 'rodadas' => 5, 'dicas' => 0,
                  'desempenho' => 'com_ajuda'];
}
$evolucao = Evolucao::resumir($sessoes);
verifica('a evolução conta todas as sessões da meta', $evolucao['total'] === 6);
verifica('melhora da primeira para a segunda metade dá variação positiva',
    $evolucao['delta'] !== null && $evolucao['delta'] > 0);
verifica('com menos de seis sessões não se calcula variação',
    Evolucao::resumir(array_slice($sessoes, 0, 4))['delta'] === null);
verifica('sem sessões, a evolução vem vazia', Evolucao::resumir([])['total'] === 0);
verifica('a variação é a média da 2ª metade menos a da 1ª',
    $evolucao['media_inicio'] == 40.0 && $evolucao['media_fim'] == 80.0 && $evolucao['delta'] == 40.0);

echo "\nGráficos de evolução\n";

$sessoesData = array_map(fn($s) => $s + ['data_aplicacao' => '2026-11-01'], $sessoes);
$svg = App\Services\Grafico::linhaAcerto($sessoesData, $evolucao, 'g1');
verifica('o gráfico tem um ponto por sessão', substr_count($svg, 'class="g-marca"') === 6);
verifica('com 6 sessões, desenha as duas médias e as descreve em texto', substr_count($svg, 'class="g-media"') === 2
    && strpos($svg, 'primeira metade 40,0%, da segunda metade 80,0%') !== false);
verifica('o gráfico tem descrição em texto para leitor de tela',
    strpos($svg, 'role="img"') !== false && strpos($svg, 'Acerto por sessão: 40,0%') !== false);
$quatro = array_slice($sessoesData, 0, 4);
verifica('com menos de 6 sessões, não desenha as médias',
    strpos(App\Services\Grafico::linhaAcerto($quatro, Evolucao::resumir($quatro), 'g2'), 'g-media') === false);
verifica('sem sessões, não desenha gráfico', App\Services\Grafico::linhaAcerto([], Evolucao::resumir([]), 'g3') === '');
verifica('cada nível de suporte tem uma forma diferente',
    count(array_unique(array_map([App\Services\Grafico::class, 'simboloSuporte'],
        ['nao_realiza', 'com_ajuda', 'sem_suporte']))) === 3);

echo "\n$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
