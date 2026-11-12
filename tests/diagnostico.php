<?php
/** Confere a instalação do banco (só leitura). Uso: php tests/diagnostico.php */

require dirname(__DIR__) . '/core/bootstrap.php';

use Core\Config;

function linha(string $estado, string $texto): void
{
    echo '  ' . str_pad($estado, 8) . $texto . PHP_EOL;
}

$c = Config::get('db');
$porta = (int) ($c['port'] ?? 3306);

echo PHP_EOL . 'Configuração em uso' . PHP_EOL;
linha('', "host={$c['host']}  porta={$porta}  banco={$c['name']}  usuário={$c['user']}");
linha('', 'debug=' . (Config::get('debug') ? 'true (os erros aparecem na tela)' : 'false (os erros ficam só no log)'));

// 1. O servidor responde?
echo PHP_EOL . '1. Servidor MySQL' . PHP_EOL;
try {
    $pdo = new PDO(
        "mysql:host={$c['host']};port={$porta};charset={$c['charset']}",
        $c['user'], $c['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    linha('ok', 'o MySQL respondeu em ' . $c['host'] . ':' . $porta);
} catch (PDOException $e) {
    linha('FALHA', 'não foi possível conectar: ' . $e->getMessage());
    echo PHP_EOL . '  O que fazer:' . PHP_EOL;
    echo '  - confira se o MySQL está verde no painel do XAMPP;' . PHP_EOL;
    echo '  - se você mudou a porta no my.ini (ex.: 3307), ajuste "port" em config/config.local.php;' . PHP_EOL;
    echo '  - confira usuário e senha em config/config.local.php (no XAMPP é root sem senha).' . PHP_EOL . PHP_EOL;
    exit(1);
}

// 2. O banco existe?
echo PHP_EOL . '2. Banco de dados' . PHP_EOL;
$existe = $pdo->query(
    'SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name = ' . $pdo->quote($c['name'])
)->fetchColumn();
if (!$existe) {
    linha('FALHA', "o banco \"{$c['name']}\" não existe.");
    echo PHP_EOL . '  O que fazer: importe database/banco_acessibilidade.sql pelo phpMyAdmin.' . PHP_EOL . PHP_EOL;
    exit(1);
}
linha('ok', "o banco \"{$c['name']}\" existe");
$pdo->exec('USE `' . $c['name'] . '`');

// 3. As tabelas
echo PHP_EOL . '3. Tabelas' . PHP_EOL;
$esperadas = ['usuarios', 'recuperacoes_senha', 'tipos_deficiencia', 'atividades',
              'atividade_tipos', 'alunos', 'metas', 'acompanhamentos'];
$tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$faltando = array_diff($esperadas, $tabelas);
if ($faltando) {
    linha('FALHA', 'faltam tabelas: ' . implode(', ', $faltando));
    echo PHP_EOL . '  O que fazer: importe database/banco_acessibilidade.sql de novo.' . PHP_EOL . PHP_EOL;
    exit(1);
}
linha('ok', count($esperadas) . ' tabelas encontradas');

// 4. As colunas dos mini games
echo PHP_EOL . '4. Colunas dos mini games' . PHP_EOL;
$colunas = function (string $tabela) use ($pdo): array {
    return $pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_COLUMN);
};
$precisa = [
    'atividades'      => ['formato', 'itens'],
    'acompanhamentos' => ['erros'],
];
$semColuna = [];
foreach ($precisa as $tabela => $lista) {
    $tem = $colunas($tabela);
    foreach ($lista as $coluna) {
        if (!in_array($coluna, $tem, true)) {
            $semColuna[] = "$tabela.$coluna";
        }
    }
}
if ($semColuna) {
    linha('FALHA', 'faltam colunas: ' . implode(', ', $semColuna));
    echo PHP_EOL . '  Seu banco é de uma versão anterior do sistema. O que fazer:' . PHP_EOL;
    echo '  importe database/banco_acessibilidade.sql (ele APAGA e recria o banco).' . PHP_EOL . PHP_EOL;
    exit(1);
}
linha('ok', 'atividades.formato, atividades.itens e acompanhamentos.erros existem');

$origem = $pdo->query("SHOW COLUMNS FROM acompanhamentos LIKE 'origem'")->fetch(PDO::FETCH_ASSOC);
if (strpos((string) ($origem['Type'] ?? ''), 'sessao') === false) {
    linha('FALHA', "a coluna acompanhamentos.origem ainda não aceita 'sessao'");
    echo PHP_EOL . '  O que fazer: importe database/banco_acessibilidade.sql (ele APAGA e recria o banco).' . PHP_EOL . PHP_EOL;
    exit(1);
}
linha('ok', "acompanhamentos.origem aceita 'sessao'");

// 5. Dados iniciais
echo PHP_EOL . '5. Dados iniciais' . PHP_EOL;
$conta = function (string $sql) use ($pdo) { return (int) $pdo->query($sql)->fetchColumn(); };
$tipos      = $conta('SELECT COUNT(*) FROM tipos_deficiencia');
$atividades = $conta('SELECT COUNT(*) FROM atividades');
$jogaveis   = $conta('SELECT COUNT(*) FROM atividades WHERE formato IS NOT NULL');
$usuarios   = $conta('SELECT COUNT(*) FROM usuarios');

linha($tipos > 0 ? 'ok' : 'FALHA', "tipos de deficiência: $tipos");
linha($atividades > 0 ? 'ok' : 'FALHA', "atividades: $atividades");
linha($jogaveis > 0 ? 'ok' : 'AVISO', "atividades que podem ser feitas na tela: $jogaveis");
linha('', "usuários cadastrados: $usuarios");

if ($jogaveis === 0) {
    echo PHP_EOL . '  Nenhum mini game configurado. Os INSERT do final do arquivo SQL não rodaram.' . PHP_EOL;
    echo '  O que fazer: importe de novo o arquivo inteiro (sem parar no meio).' . PHP_EOL;
}

echo PHP_EOL . 'Banco de dados: tudo certo.' . PHP_EOL;
echo 'Se o "Erro 500" continuar, ligue o debug para ver a mensagem real:' . PHP_EOL;
echo "  em config/config.local.php, use  'debug' => true" . PHP_EOL . PHP_EOL;
