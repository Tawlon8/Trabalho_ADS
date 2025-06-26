<?php
session_start();

// Conexão com o banco (ajuste os dados conforme necessário)
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'banco_acessibilidade';

$conn = new mysqli($host, $usuario, $senha, $banco);
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows === 1) {
        $usuario = $resultado->fetch_assoc();
        if (password_verify($senha, $usuario['senha'])) {
            $_SESSION['usuario'] = $usuario;
            header("Location: home.php");
            exit();
        } else {
            $_SESSION['erro_login'] = "Senha incorreta.";
        }
    } else {
        $_SESSION['erro_login'] = "Usuário não encontrado.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login</title>
  <link rel="stylesheet" href="assets/css/login.css" />
  <link rel="shortcut icon" href="assets/images/logo-acessibilidade-white.png" type="image/x-icon" />
</head>
<body>
  <?php if (isset($_SESSION["erro_login"])): ?>
    <p style="color: red; text-align:center;"><?php echo $_SESSION["erro_login"]; unset($_SESSION["erro_login"]); ?></p>
  <?php endif; ?>

  <section class="container">
    <div class="left-login login">
      <img src="assets/images/logo.png" alt="Logo da plataforma" />
      <p>Nosso compromisso é oferecer uma experiência digital inclusiva e acessível a todos.</p>
    </div>
    <div class="right-login login">
      <form action="index.php" method="POST">
        <h1>FAÇA SEU LOGIN</h1>
        <div class="right">
          <h2>Login</h2>
          <p>Acesse:</p>
          <div class="form-group">
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" required placeholder="Digite seu e-mail">
          </div>
          <div class="form-group">  
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
          </div>
          <button type="submit" class="acessar">Acessar</button>
        </div>
        <span class="create-account">
          Não tem conta? <a href="#">Cadastre-se</a>
        </span>
      </form>
    </div>
  </section>
</body>
</html>