<?php


session_start();

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Cadastro de Usuário</title>
<link rel="stylesheet" href="assets/css/users_cadastro.css" />
</head>
<body>

  <?php if (isset($_SESSION["erro_cadastro"])): ?>
    <p style="color: red; text-align:center;"><?php echo $_SESSION["erro_cadastro"]; unset($_SESSION["erro_cadastro"]); ?></p>
  <?php endif; ?>

  <h2>Cadastro de Usuário</h2>
  <form action="processa_cadastro.php" method="POST">
    <label for="nome">Nome:</label>
    <input type="text" name="nome" id="nome" required>

    <label for="email">E-mail:</label>
    <input type="email" name="email" id="email" required>

    <label for="senha">Senha:</label>
    <input type="password" name="senha" id="senha" required>

    
      <?php
        // conexão com banco (ajuste os dados conforme seu ambiente)
        $conn = new mysqli("localhost", "root", "", "banco_acessibilidade");
        $sql = "SELECT id, nome FROM tipos_deficiencia";
        $result = $conn->query($sql);
        while ($row = $result->fetch_assoc()) {
          echo "<option value='{$row['id']}'>{$row['nome']}</option>";
        }
        $conn->close();
      ?>
    </select>

    <button type="submit">Cadastrar</button>
  </form>
</body>
</html>
