
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Home Page</title>
  <link rel="stylesheet" href="assets/css/home.css" />
  <link rel="shortcut icon" href="assets/images/logo-acessibilidade-white.png" type="image/x-icon" />
  <script src="https://kit.fontawesome.com/6ea2d8d5f6.js" crossorigin="anonymous"></script>







  <link rel="stylesheet" href="assets/css/excluir.css">
</head>

<body>
  <section class="container">
    <div class="content">
      <div class="content-description-one description">
        <h1>Brincar é para todos!</h1>
        <p>Sugestões de brincadeiras para desenvolvimento de cada criança</p>
      </div>

      <div class="content-description-two description">
        <h2>Como podemos ajudar hoje?</h2>
        <p>
          Selecione abaixo o tipo de deficiência da para receber
          sugestões personalizadas.
        </p>
      </div>

      <div class="content-cards">
        <a class="card" href="motora.php">
          <i class="fas fa-dumbbell icon-description"></i>
          <h3 class="text-card">Motora</h3>
        </a>
        <a class="card" href="intelectual.php">
          <i class="fas fa-brain icon-description"></i>
          <h3 class="text-card">Intelectual</h3>
        </a>
        <a class="card" href="auditiva.php">
          <i class="fas fa-deaf icon-description"></i>
          <h3 class="text-card">Auditiva</h3>
        </a>
        <a class="card" href="visual.php">
          <i class="fas fa-low-vision icon-description"></i>
          <h3 class="text-card">Visual</h3>
        </a>
        <a class="card" href="tea.php">
          <i class="fas fa-puzzle-piece icon-description"></i>
          <h3 class="text-card">Transtorno do Espectro Autista (TEA)</h3>
        </a>
        <a class="card" href="multipla.php">
          <i class="fas fa-users icon-description"></i>
          <h3 class="text-card">Múltipla</h3>
        </a>
        <a class="card" href="outro.php">
          <i class="fas fa-question icon-description"></i>
          <h3 class="text-card">Outra / Não sei Dizer</h3>
        </a>
         
        <?php
session_start(); if (isset($_SESSION['usuario'])): ?>
          <a href="excluir_usuario.php?id=<?php echo $_SESSION['usuario']['id']; ?>"
            onclick="return confirm('Tem certeza que deseja excluir seu cadastro? Esta ação não poderá ser desfeita.')"
            class="btn-excluir">
            Excluir meu cadastro
          </a>
        <?php endif; ?>

      </div>
    </div>
  </section>
</body>

</html>