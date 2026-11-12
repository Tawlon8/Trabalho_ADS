<?php
$usuario = \Core\Auth::user();
$flash   = \Core\Session::getFlash();
$erros   = \Core\Session::erros();

$menu = [['', 'Início'], ['atividades', 'Atividades'], ['alunos', 'Meus alunos']];
if (\Core\Auth::isAdmin()) {
    $menu[] = ['tipos', 'Tipos de deficiência'];
}
$menu[] = ['perfil', 'Meu perfil'];
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
  <a class="skip-link" href="#conteudo">Ir para o conteúdo</a>

  <header class="topo">
    <div class="topo-interno">
      <a class="marca" href="<?= e(url('')) ?>">
        <img src="<?= e(asset('images/logo-acessibilidade-white.png')) ?>" alt="" width="32" height="32">
        <span>UNIA</span>
      </a>

      <form class="busca" action="<?= e(url('atividades')) ?>" method="GET" role="search">
        <label class="sr-only" for="busca-global">Buscar atividades</label>
        <input type="search" id="busca-global" name="q" placeholder="Buscar atividades" maxlength="100">
        <button type="submit">Buscar</button>
      </form>

      <nav class="menu" aria-label="Menu principal">
        <ul>
          <?php foreach ($menu as [$rota, $rotulo]): ?>
            <li><a href="<?= e(url($rota)) ?>"<?= rota_atual($rota) ? ' aria-current="page"' : '' ?>><?= e($rotulo) ?></a></li>
          <?php endforeach; ?>
          <li>
            <form action="<?= e(url('logout')) ?>" method="POST" class="form-sair">
              <?= csrf_field() ?>
              <button type="submit" class="btn-sair">Sair (<?= e($usuario['nome'] ?? '') ?>)</button>
            </form>
          </li>
        </ul>
      </nav>
    </div>
  </header>

  <main id="conteudo" class="pagina">
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
  </main>

  <footer class="rodape">
    <p>UNIA – Unir Necessidades Individuais e Aprendizagem · Projeto de TCC (ADS)</p>
  </footer>
</body>
</html>
