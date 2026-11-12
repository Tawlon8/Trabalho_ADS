<?php
/** Front controller: ponto de entrada de todas as requisições. */
require dirname(__DIR__) . '/core/bootstrap.php';

$router = require BASE_DIR . '/config/routes.php';
$router->dispatch($_SERVER['REQUEST_METHOD']);
