<?php
$editando = $aluno !== null;
$acao = $editando ? 'alunos/' . (int) $aluno['id'] . '/editar' : 'alunos';
$v = function ($campo) use ($aluno) { return old($campo, $aluno[$campo] ?? ''); };
?>
<p class="migalha"><a href="<?= e(url($editando ? 'alunos/' . (int) $aluno['id'] : 'alunos')) ?>">&larr; Voltar</a></p>
<h1><?= e($titulo) ?></h1>

<form action="<?= e(url($acao)) ?>" method="POST" class="formulario" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label for="nome">Nome *</label>
    <input type="text" id="nome" name="nome" value="<?= e($v('nome')) ?>" maxlength="100" required<?= aria_erro('nome') ?>>
    <?= campo_erro('nome') ?>
  </div>

  <div class="campo">
    <label for="data_nascimento">Data de nascimento</label>
    <input type="date" id="data_nascimento" name="data_nascimento" value="<?= e($v('data_nascimento')) ?>" max="<?= e(date('Y-m-d')) ?>"<?= aria_erro('data_nascimento') ?>>
    <?= campo_erro('data_nascimento') ?>
  </div>

  <div class="campo">
    <label for="tipo_deficiencia_id">Tipo de deficiência</label>
    <select id="tipo_deficiencia_id" name="tipo_deficiencia_id"<?= aria_erro('tipo_deficiencia_id') ?>>
      <option value="">Não informado</option>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= (int) $t['id'] ?>"<?= selecionado($v('tipo_deficiencia_id'), $t['id']) ?>><?= e($t['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <?= campo_erro('tipo_deficiencia_id') ?>
  </div>

  <div class="campo">
    <label for="observacoes">Estudo de caso (observações pedagógicas)</label>
    <textarea id="observacoes" name="observacoes" rows="4" maxlength="2000"<?= aria_erro('observacoes') ?>><?= e($v('observacoes')) ?></textarea>
    <small class="dica">Registre o que o aluno faz, em que contextos e com que apoio. O estudo de caso não é um diagnóstico. Fontes que podem orientar as observações: relatórios, entrevistas com a família e com professores, observação em sala.</small>
    <?= campo_erro('observacoes') ?>
  </div>

  <div class="campo campo-check">
    <input type="checkbox" id="consentimento" name="consentimento" value="1"<?= marcado((string) $v('consentimento') === '1') ?> required<?= aria_erro('consentimento') ?>>
    <label for="consentimento">Tenho autorização do responsável para registrar os dados deste aluno (LGPD). *</label>
    <?= campo_erro('consentimento') ?>
  </div>

  <div class="acoes">
    <button type="submit" class="botao">Salvar</button>
    <a class="botao botao-secundario" href="<?= e(url($editando ? 'alunos/' . (int) $aluno['id'] : 'alunos')) ?>">Cancelar</a>
  </div>
</form>

<?php if ($editando): ?>
  <hr>
  <h2>Excluir aluno</h2>
  <p>Ao excluir, todos os registros de acompanhamento deste aluno também serão apagados.</p>
  <form action="<?= e(url('alunos/' . (int) $aluno['id'] . '/excluir')) ?>" method="POST"
        onsubmit="return confirm('Excluir este aluno e todos os seus registros? Esta ação não pode ser desfeita.')">
    <?= csrf_field() ?>
    <button type="submit" class="botao botao-perigo">Excluir aluno</button>
  </form>
<?php endif; ?>
