<?php
/** Configuração do UNIA. Senhas ficam em config.local.php (fora do Git). */
$config = [
    'debug'   => false,
    'app_url' => 'http://localhost/Trabalho_ADS',    // usado no link do e-mail de recuperação

    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'banco_acessibilidade',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'mail' => [
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'user'      => '',
        'pass'      => '',                           // senha de app do Gmail
        'from_name' => 'UNIA - Recuperação de Senha',
    ],

    'senha_min_caracteres'   => 8,
    'token_validade_minutos' => 60,
    'por_pagina'             => 8,

    /* Áreas da matriz de habilidades do AppPEI (chave no banco => rótulo). */
    'areas' => [
        'matematica'            => 'Alfabetização Matemática',
        'comunicacao_oral'      => 'Comunicação Oral',
        'leitura_escrita'       => 'Leitura e Escrita',
        'alfabetizacao_digital' => 'Alfabetização Digital',
    ],

    /* Parâmetros do protótipo (não validados). */
    'revisao_dias' => 90,   // revisão padrão da meta

    'sessao' => [
        'rodadas' => 5,     // por jogo
        'maximo'  => 5,     // maior número nos jogos de matemática
    ],
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}

return $config;
