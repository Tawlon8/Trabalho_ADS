<?php

$servidor = "localhost";
$usuario = "root";
$senha = "";
$banco = "banco_acessibilidade";
$conn = new mysqli($servidor, $usuario, $senha, $banco);

if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);

} else {


    echo "Conexão com o banco de dados estabelecida com sucesso!";
    $conn->close();
    echo "<br>Conexão fechada.";

}
?>
