<h1>Recuperar senha</h1>
<form action="<?= e(url('esqueci-senha')) ?>" method="POST" novalidate>
  <?= csrf_field() ?>
  <div class="campo">
    <label for="email">Digite seu e-mail cadastrado</label>
    <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" maxlength="100" required<?= aria_erro('email') ?>>
    <?= campo_erro('email') ?>
  </div>
  <button type="submit" class="botao botao-cheio">Enviar link de recuperação</button>
</form>
<p class="links-auth"><a href="<?= e(url('login')) ?>">Voltar ao login</a></p>
