<?php
// excluir_usuario.php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'banco_acessibilidade';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Erro na conexão: " . $conn->connect_error);
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "Usuário excluído com sucesso.";
    } else {
        echo "Erro ao excluir usuário: " . $stmt->error;
    }

    $stmt->close();
} else {
    echo "ID do usuário não fornecido.";
}

$conn->close();
?>
