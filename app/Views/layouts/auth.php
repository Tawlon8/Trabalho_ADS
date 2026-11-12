<?php
$flash = \Core\Session::getFlash();
$erros = \Core\Session::erros();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e(($titulo ?? 'UNIA') . ' - UNIA') ?></title>
  <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
  <link rel="shortcut icon" href="<?= e(asset('images/logo-acessibilidade-white.png')) ?>" type="image/png">
</head>
<body class="auth">
  <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>

  <div class="auth-container">
    <div class="auth-esquerda">
      <img src="<?= e(asset('images/logo-acessibilidade-white.png')) ?>" alt="" width="120" height="120">
      <p class="auth-marca">UNIA</p>
      <p>Unir Necessidades Individuais e Aprendizagem: acompanhamento das metas do PEI com a medição do desempenho em mini games.</p>
    </div>

    <main id="conteudo" class="auth-direita">
      <div class="auth-cartao">
        <?php if ($flash): ?>
          <p class="alerta alerta-<?= $flash['tipo'] === 'sucesso' ? 'sucesso' : 'erro' ?>" role="<?= $flash['tipo'] === 'sucesso' ? 'status' : 'alert' ?>"><?= e($flash['msg']) ?></p>
        <?php endif; ?>

        <?php if ($erros): ?>
          <div class="alerta alerta-erro" role="alert">
            <p><strong>Corrija os campos abaixo:</strong></p>
            <ul>
              <?php foreach ($erros as $campo => $msg): ?>
                <li><a href="#<?= e($campo) ?>"><?= e($msg) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?= $conteudo ?>
      </div>
    </main>
  </div>
</body>
</html>
