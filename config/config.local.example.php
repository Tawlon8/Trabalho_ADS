<?php
// Copie este arquivo para config/config.local.php e preencha com seus dados.
// O config.local.php está no .gitignore e nunca deve ir para o GitHub.
return [
    'debug'   => true,                              // erros aparecem na tela (use false em produção)
    'app_url' => 'http://localhost/Trabalho_ADS',   // ajuste se a pasta tiver outro nome
    'db' => [
        'user' => 'root',
        'pass' => '',
        // 'port' => 3307,   // descomente se o MySQL do XAMPP estiver em outra porta
    ],
    'mail' => [
        'user' => 'seu_email@gmail.com',
        'pass' => 'SENHA_DE_APP_DO_GMAIL',
    ],
];
