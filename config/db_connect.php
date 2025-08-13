<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "banco_acessibilidade";

$mysqli = new mysqli($host, $user, $pass, $dbname);

if ($mysqli->connect_errno) {
    die("Falha na conexão com o banco de dados: " . $mysqli->connect_error);
}
?>
