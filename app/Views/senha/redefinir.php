<?php $minimo = (int) \Core\Config::get('senha_min_caracteres'); ?>
<?php if (!$valido): ?>
  <h1>Link inválido ou expirado</h1>
  <p>Este link de redefinição não é válido ou já foi usado. Solicite um novo.</p>
  <p class="links-auth"><a href="<?= e(url('esqueci-senha')) ?>">Solicitar novo link</a></p>
<?php else: ?>
  <h1>Redefinir senha</h1>
  <form action="<?= e(url('redefinir-senha/' . $token)) ?>" method="POST" novalidate>
    <?= csrf_field() ?>
    <div class="campo">
      <label for="senha">Nova senha</label>
      <input type="password" id="senha" name="senha" autocomplete="new-password" required<?= aria_erro('senha') ?>>
      <small class="dica">Mínimo de <?= $minimo ?> caracteres.</small>
      <?= campo_erro('senha') ?>
    </div>
    <div class="campo">
      <label for="senha_confirm">Confirmar nova senha</label>
      <input type="password" id="senha_confirm" name="senha_confirm" autocomplete="new-password" required>
    </div>
    <button type="submit" class="botao botao-cheio">Redefinir senha</button>
  </form>
<?php endif; ?>
