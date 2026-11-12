<?php $usuario = \Core\Auth::user(); ?>
<h1>Olá, <?= e($usuario['nome']) ?>!</h1>
<p class="lead">Escolha um tipo de deficiência para ver atividades adequadas, ou busque por palavra.</p>

<?php if ($revisoesPendentes > 0): ?>
  <div class="alerta alerta-erro" role="alert">
    <p><strong><?= (int) $revisoesPendentes ?> meta<?= $revisoesPendentes === 1 ? '' : 's' ?> com revisão vencida.</strong>
      Confira os planos dos alunos e atualize as metas. <a href="<?= e(url('alunos')) ?>">Ver meus alunos</a></p>
  </div>
<?php endif; ?>

<form class="busca-grande" action="<?= e(url('atividades')) ?>" method="GET" role="search">
  <label for="q-home">Buscar atividade</label>
  <div class="linha">
    <input type="search" id="q-home" name="q" maxlength="100" placeholder="Ex.: sílaba, contar, ouvir">
    <button type="submit" class="botao">Buscar</button>
  </div>
</form>

<h2>Atividades por tipo de deficiência</h2>
<ul class="grade-cartoes">
  <?php foreach ($tipos as $t): ?>
    <li>
      <a class="cartao cartao-tipo" href="<?= e(url('atividades?tipo=' . (int) $t['id'])) ?>">
        <i class="fas <?= e($t['icone']) ?> icone" aria-hidden="true"></i>
        <span class="cartao-titulo"><?= e($t['nome']) ?></span>
        <span class="cartao-info"><?= (int) $t['total_atividades'] ?> atividade<?= (int) $t['total_atividades'] === 1 ? '' : 's' ?></span>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="duas-colunas">
  <section aria-labelledby="titulo-recentes">
    <h2 id="titulo-recentes">Atividades recentes</h2>
    <?php if ($recentes): ?>
      <ul class="lista-simples">
        <?php foreach ($recentes as $a): ?>
          <li><a href="<?= e(url('atividades/' . (int) $a['id'])) ?>"><?= e($a['titulo']) ?></a> </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Nenhuma atividade cadastrada ainda.</p>
    <?php endif; ?>
    <p><a class="botao botao-secundario" href="<?= e(url('atividades/nova')) ?>">Cadastrar atividade</a></p>
  </section>

  <section aria-labelledby="titulo-alunos">
    <h2 id="titulo-alunos">Meus alunos</h2>
    <p>Você acompanha <strong><?= (int) $totalAlunos ?></strong> aluno<?= $totalAlunos === 1 ? '' : 's' ?>.</p>
    <p>
      <a class="botao botao-secundario" href="<?= e(url('alunos')) ?>">Ver alunos</a>
      <a class="botao botao-secundario" href="<?= e(url('alunos/novo')) ?>">Cadastrar aluno</a>
    </p>
  </section>
</div>
