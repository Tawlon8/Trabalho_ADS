<?php
$editando = $tipo !== null;
$acao = $editando ? 'tipos/' . (int) $tipo['id'] . '/editar' : 'tipos';
$v = function ($campo) use ($tipo) { return old($campo, $tipo[$campo] ?? ''); };
?>
<p class="migalha"><a href="<?= e(url('tipos')) ?>">&larr; Voltar para tipos</a></p>
<h1><?= e($titulo) ?></h1>

<form action="<?= e(url($acao)) ?>" method="POST" class="formulario" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label for="nome">Nome *</label>
    <input type="text" id="nome" name="nome" value="<?= e($v('nome')) ?>" maxlength="50" required<?= aria_erro('nome') ?>>
    <?= campo_erro('nome') ?>
  </div>

  <div class="campo">
    <label for="descricao">Descrição</label>
    <input type="text" id="descricao" name="descricao" value="<?= e($v('descricao')) ?>" maxlength="255"<?= aria_erro('descricao') ?>>
    <?= campo_erro('descricao') ?>
  </div>

  <div class="campo">
    <label for="icone">Ícone *</label>
    <select id="icone" name="icone" required<?= aria_erro('icone') ?>>
      <?php foreach (\App\Models\TipoDeficiencia::ICONES as $classe => $nomeIcone): ?>
        <option value="<?= e($classe) ?>"<?= selecionado($v('icone') !== '' ? $v('icone') : 'fa-puzzle-piece', $classe) ?>><?= e($nomeIcone) ?></option>
      <?php endforeach; ?>
    </select>
    <?= campo_erro('icone') ?>
  </div>

  <div class="acoes">
    <button type="submit" class="botao">Salvar</button>
    <a class="botao botao-secundario" href="<?= e(url('tipos')) ?>">Cancelar</a>
  </div>
</form>
