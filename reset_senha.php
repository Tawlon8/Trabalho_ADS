<?php
// Configurações do banco
$host = "localhost";
$user = "root";
$pass = "";
$db   = "banco_acessibilidade"; // 

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Token inválido.");
}

// Verifica se o token existe e não expirou
$stmt = $conn->prepare("SELECT id, token_expira FROM usuarios WHERE token_recuperacao = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    die("Token inválido ou já usado.");
}

$stmt->bind_result($user_id, $token_expira);
$stmt->fetch();

// Verifica se expirou
if (strtotime($token_expira) < time()) {
    die("O link de redefinição expirou.");
}

// Se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $senha = $_POST['senha'];
    $senha_confirm = $_POST['senha_confirm'];

    if ($senha !== $senha_confirm) {
        echo "As senhas não coincidem.";
    } else {
        // Hash da nova senha
        $hash = password_hash($senha, PASSWORD_DEFAULT);

        // Atualiza a senha e limpa token
        $stmt_upd = $conn->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira = NULL WHERE id = ?");
        $stmt_upd->bind_param("si", $hash, $user_id);
        if ($stmt_upd->execute()) {
            echo "Senha redefinida com sucesso! Você já pode fazer login.";
        } else {
            echo "Erro ao redefinir senha.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Redefinir Senha</title>
</head>
<body>
    <h2>Redefinir Senha</h2>
    <form method="POST">
        <label>Nova senha:</label><br>
        <input type="password" name="senha" required><br><br>

        <label>Confirmar nova senha:</label><br>
        <input type="password" name="senha_confirm" required><br><br>

        <button type="submit">Redefinir</button>
    </form>
</body>
</html>
