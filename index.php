<?php
session_start();

// Conexão com o banco
$host = 'localhost';
$usuario = 'root';
$senha = '';
$banco = 'banco_acessibilidade';

// Criar conexão
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verificar conexão
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
            $_SESSION['erro_login'] = "Usuário ou Senha incorreta.";
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
       <div class="form-group">
            <div class="input-login">
              <input type="email" name="email" placeholder="Usuário" required />
              <input type="password" name="senha" placeholder="Senha" required />
            </div>

            <div class="login-options">
              <div class="remember">
                <input type="checkbox" name="lembrar" id="lembrar" />
                <label for="lembrar">Lembrar de mim</label>
              </div>
              <a class="redefinicao-senha" href="form_esqueci_senha.php">Esqueceu sua senha?</a>
            </div>

            <button type="submit" class="entrar-button">Acessar</button>
          </div>
        <span class="create-account">
          Não tem conta? <a href="users_cadastro.php">Cadastre-se</a>
        </span>
      </form>
    </div>
  </section>
</body>
</html>
