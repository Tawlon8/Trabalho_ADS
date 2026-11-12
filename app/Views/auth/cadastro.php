<?php $minimo = (int) \Core\Config::get('senha_min_caracteres'); ?>
<h1>Cadastro de professor</h1>
<form action="<?= e(url('cadastro')) ?>" method="POST" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label for="nome">Nome</label>
    <input type="text" id="nome" name="nome" value="<?= e(old('nome')) ?>" autocomplete="name" maxlength="100" required<?= aria_erro('nome') ?>>
    <?= campo_erro('nome') ?>
  </div>

  <div class="campo">
    <label for="email">E-mail</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="100" required<?= aria_erro('email') ?>>
    <?= campo_erro('email') ?>
  </div>

  <div class="campo">
    <label for="senha">Senha</label>
    <input type="password" id="senha" name="senha" autocomplete="new-password" required<?= aria_erro('senha') ?>>
    <small class="dica">Mínimo de <?= $minimo ?> caracteres.</small>
    <?= campo_erro('senha') ?>
  </div>

  <div class="campo">
    <label for="senha_confirm">Confirmar senha</label>
    <input type="password" id="senha_confirm" name="senha_confirm" autocomplete="new-password" required>
  </div>

  <div class="campo campo-check">
    <input type="checkbox" id="aceite" name="aceite" value="1" required<?= aria_erro('aceite') ?>>
    <label for="aceite">Concordo com o tratamento dos meus dados e dos dados dos alunos cadastrados, conforme a LGPD, apenas para fins pedagógicos.</label>
    <?= campo_erro('aceite') ?>
  </div>

  <button type="submit" class="botao botao-cheio">Cadastrar</button>
</form>

<p class="links-auth">Já tem conta? <a href="<?= e(url('login')) ?>">Fazer login</a></p>
