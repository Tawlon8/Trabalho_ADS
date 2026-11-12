<?php
$editando = $meta !== null;
$alunoId  = (int) $aluno['id'];
$acao     = $editando ? 'metas/' . (int) $meta['id'] . '/editar' : 'alunos/' . $alunoId . '/metas';
$v        = function ($campo, $padrao = '') use ($meta) {
    return old($campo, $meta[$campo] ?? $padrao);
};
$areas   = \Core\Config::get('areas');
$niveis  = \App\Models\Acompanhamento::NIVEIS;
?>
<p class="migalha"><a href="<?= e(url('alunos/' . $alunoId . '#plano')) ?>">&larr; Voltar para <?= e($aluno['nome']) ?></a></p>
<h1><?= e($titulo) ?></h1>
<p class="dica">A meta é uma habilidade priorizada para o aluno. O suporte inicial registra o ponto de partida; as sessões de jogos na tela mostram a evolução.</p>

<form action="<?= e(url($acao)) ?>" method="POST" class="formulario" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label for="area">Área de habilidade *</label>
    <select id="area" name="area" required<?= aria_erro('area') ?>>
      <option value="">Selecione…</option>
      <?php foreach ($areas as $chave => $rotulo): ?>
        <option value="<?= e($chave) ?>"<?= selecionado($v('area'), $chave) ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
    <?= campo_erro('area') ?>
  </div>

  <div class="campo">
    <label for="descricao">Meta (habilidade priorizada) *</label>
    <input type="text" id="descricao" name="descricao" value="<?= e($v('descricao')) ?>" maxlength="255" required<?= aria_erro('descricao') ?>>
    <?= campo_erro('descricao') ?>
  </div>

  <div class="campo">
    <label for="nivel_inicial">Suporte inicial *</label>
    <select id="nivel_inicial" name="nivel_inicial" required<?= aria_erro('nivel_inicial') ?>>
      <option value="">Selecione…</option>
      <?php foreach ($niveis as $chave => $rotulo): ?>
        <option value="<?= e($chave) ?>"<?= selecionado($v('nivel_inicial'), $chave) ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
    <?= campo_erro('nivel_inicial') ?>
  </div>

  <div class="campo">
    <label for="professor_responsavel">Professor responsável (opcional)</label>
    <input type="text" id="professor_responsavel" name="professor_responsavel" value="<?= e($v('professor_responsavel')) ?>" maxlength="100"<?= aria_erro('professor_responsavel') ?>>
    <?= campo_erro('professor_responsavel') ?>
  </div>

  <div class="campo">
    <label for="revisar_em">Data de revisão</label>
    <input type="date" id="revisar_em" name="revisar_em" value="<?= e($v('revisar_em')) ?>"<?= aria_erro('revisar_em') ?>>
    <small class="dica">Se ficar em branco, a revisão é marcada para daqui a <?= (int) \Core\Config::get('revisao_dias') ?> dias.</small>
    <?= campo_erro('revisar_em') ?>
  </div>

  <div class="acoes">
    <button type="submit" class="botao">Salvar meta</button>
    <a class="botao botao-secundario" href="<?= e(url('alunos/' . $alunoId . '#plano')) ?>">Cancelar</a>
  </div>
</form>
