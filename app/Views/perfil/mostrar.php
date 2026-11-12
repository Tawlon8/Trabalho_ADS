<?php $minimo = (int) \Core\Config::get('senha_min_caracteres'); ?>
<h1>Meu perfil</h1>
<p>Perfil: <strong><?= $usuario['perfil'] === 'admin' ? 'Administrador' : 'Professor' ?></strong></p>

<section aria-labelledby="titulo-dados">
  <h2 id="titulo-dados">Meus dados</h2>
  <form action="<?= e(url('perfil')) ?>" method="POST" class="formulario" novalidate>
    <?= csrf_field() ?>
    <div class="campo">
      <label for="nome">Nome *</label>
      <input type="text" id="nome" name="nome" value="<?= e(old('nome', $usuario['nome'])) ?>" maxlength="100" autocomplete="name" required<?= aria_erro('nome') ?>>
      <?= campo_erro('nome') ?>
    </div>
    <div class="campo">
      <label for="email">E-mail *</label>
      <input type="email" id="email" name="email" value="<?= e(old('email', $usuario['email'])) ?>" maxlength="100" autocomplete="email" required<?= aria_erro('email') ?>>
      <?= campo_erro('email') ?>
    </div>
    <button type="submit" class="botao">Salvar dados</button>
  </form>
</section>

<section aria-labelledby="titulo-senha">
  <h2 id="titulo-senha">Alterar senha</h2>
  <form action="<?= e(url('perfil/senha')) ?>" method="POST" class="formulario" novalidate>
    <?= csrf_field() ?>
    <div class="campo">
      <label for="senha_atual">Senha atual *</label>
      <input type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required<?= aria_erro('senha_atual') ?>>
      <?= campo_erro('senha_atual') ?>
    </div>
    <div class="campo">
      <label for="senha_nova">Nova senha *</label>
      <input type="password" id="senha_nova" name="senha_nova" autocomplete="new-password" required<?= aria_erro('senha_nova') ?>>
      <small class="dica">Mínimo de <?= $minimo ?> caracteres.</small>
      <?= campo_erro('senha_nova') ?>
    </div>
    <div class="campo">
      <label for="senha_nova_confirm">Confirmar nova senha *</label>
      <input type="password" id="senha_nova_confirm" name="senha_nova_confirm" autocomplete="new-password" required>
    </div>
    <button type="submit" class="botao">Alterar senha</button>
  </form>
</section>

<section aria-labelledby="titulo-excluir">
  <h2 id="titulo-excluir">Excluir minha conta</h2>
  <p>Seus alunos e os registros de acompanhamento serão apagados. As atividades que você cadastrou continuam disponíveis.</p>
  <form action="<?= e(url('perfil/excluir')) ?>" method="POST" class="formulario" novalidate
        onsubmit="return confirm('Excluir sua conta definitivamente? Esta ação não pode ser desfeita.')">
    <?= csrf_field() ?>
    <div class="campo">
      <label for="senha_exclusao">Digite sua senha para confirmar *</label>
      <input type="password" id="senha_exclusao" name="senha_exclusao" autocomplete="current-password" required<?= aria_erro('senha_exclusao') ?>>
      <?= campo_erro('senha_exclusao') ?>
    </div>
    <button type="submit" class="botao botao-perigo">Excluir minha conta</button>
  </form>
</section>
