<?php
// Inicio / retomada da sessão
session_start();
require_once 'conexao.php';

// Verifica se os campos de matrícula e senha foram enviados via POST
$email = $_POST['email'] ?? '';
$senha_digitada = $_POST['senha'] ?? ''; // Renomeado para clareza

// 1. Busca o usuário pela matrícula (apenas a matrícula, para obter a senha hash)
$stmt = $conexao->prepare("SELECT id, nome, email, senha, tipo_deficiencia_id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();

// Verifica se encontrou um usuário com a matrícula
if ($resultado->num_rows > 0) {
    $usuario = $resultado->fetch_assoc();
    $senha_hash_do_banco = $usuario['senha']; // Pega a senha hash do banco

    // 2. Verifica a senha usando password_verify()
    if (password_verify($senha_digitada, $senha_hash_do_banco)) {
        // Senha correta, login bem-sucedido
        $_SESSION['usuario'] = $usuario;

        // Redireciona para a página principal
        header("Location: ../home.php");
        exit;
    } else {
        // Senha incorreta
        echo "<script>
                alert('Matrícula ou senha incorretos!');
                window.location.href = '../index.php';
              </script>";
    }
} else {
    // Matrícula não encontrada
    echo "<script>
            alert('Matrícula ou senha incorretos!');
            window.location.href = '../index.php';
          </script>";
}

$stmt->close();
$conexao->close();
?>