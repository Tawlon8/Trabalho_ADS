<h1>Faça seu login</h1>
<form action="<?= e(url('login')) ?>" method="POST" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label class="sr-only" for="email">E-mail</label>
    <input type="email" id="email" name="email" placeholder="E-mail" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="100" required<?= aria_erro('email') ?>>
    <?= campo_erro('email') ?>
  </div>

  <div class="campo">
    <label class="sr-only" for="senha">Senha</label>
    <input type="password" id="senha" name="senha" placeholder="Senha" autocomplete="current-password" required<?= aria_erro('senha') ?>>
    <?= campo_erro('senha') ?>
  </div>

  <p class="opcoes-login"><a href="<?= e(url('esqueci-senha')) ?>">Esqueceu sua senha?</a></p>

  <button type="submit" class="botao botao-cheio">Acessar</button>
</form>

<p class="links-auth">Não tem conta? <a href="<?= e(url('cadastro')) ?>">Cadastre-se</a></p>
