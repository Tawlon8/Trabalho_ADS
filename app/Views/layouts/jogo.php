<?php
/* Layout da sessão de jogos, sem menu. */
$flash = \Core\Session::getFlash();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(($titulo ?? 'UNIA') . ' - UNIA') ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="shortcut icon" href="<?= e(asset('images/logo-acessibilidade-white.png')) ?>" type="image/png">
  <script src="https://kit.fontawesome.com/6ea2d8d5f6.js" crossorigin="anonymous"></script>
</head>
<body>
  <main id="conteudo" class="pagina">
    <?php if ($flash): ?>
      <p class="alerta alerta-<?= $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="<?= $flash['tipo'] === 'sucesso' ? 'status' : 'alert' ?>"><?= e($flash['msg']) ?></p>
    <?php endif; ?>

    <?= $conteudo ?>
  </main>
</body>
</html>
