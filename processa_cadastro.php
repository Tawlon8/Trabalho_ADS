<?php
session_start();

// Conexão com o banco de dados (ajuste os detalhes conforme seu ambiente)
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'banco_acessibilidade';

// Criar conexão
$conn = new mysqli($host, $username, $password, $database);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $tipo_deficiencia_id = $_POST['tipo_deficiencia_id'];

    // Criptografa a senha por segurança
    $hashed_password = password_hash($senha, PASSWORD_DEFAULT);

    // Prepara e vincula a declaração SQL
    $stmt = $conn->prepare("INSERT INTO usuarios (nome, email, senha, tipo_deficiencia_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $nome, $email, $hashed_password, $tipo_deficiencia_id);

    // Executa a declaração
    if ($stmt->execute()) {
        $_SESSION['cadastro_sucesso'] = "Usuário cadastrado com sucesso!";
        header("Location: index.php"); // Redireciona para a página de login após o registro bem-sucedido
        exit();
    } else {
        $_SESSION['erro_cadastro'] = "Erro ao cadastrar usuário: " . $stmt->error;
        header("Location: users_cadastro.php"); // Redireciona de volta para a página de registro em caso de erro
        exit();
    }

    $stmt->close();
} else {
    // Se o formulário não foi enviado diretamente, redireciona para a página de registro
    header("Location: users_cadastro.php");
    exit();
}

$conn->close();
?>