<div class="cabecalho-pagina">
  <h1>Meus alunos</h1>
  <a class="botao" href="<?= e(url('alunos/novo')) ?>">Novo aluno</a>
</div>

<form class="filtros" action="<?= e(url('alunos')) ?>" method="GET" role="search">
  <div class="campo">
    <label for="q">Buscar aluno por nome</label>
    <input type="search" id="q" name="q" value="<?= e($busca) ?>" maxlength="100">
  </div>
  <button type="submit" class="botao">Buscar</button>
  <?php if ($busca !== ''): ?>
    <a class="botao botao-secundario" href="<?= e(url('alunos')) ?>">Limpar</a>
  <?php endif; ?>
</form>

<?php if ($alunos): ?>
  <div class="tabela-rolagem">
    <table class="tabela">
      <caption class="sr-only">Lista dos seus alunos</caption>
      <thead>
        <tr>
          <th scope="col">Nome</th>
          <th scope="col">Nascimento</th>
          <th scope="col">Tipo de deficiência</th>
          <th scope="col">Registros</th>
          <th scope="col">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($alunos as $a): ?>
          <tr>
            <th scope="row"><a href="<?= e(url('alunos/' . (int) $a['id'])) ?>"><?= e($a['nome']) ?></a></th>
            <td><?= e(data_br($a['data_nascimento'])) ?></td>
            <td><?= e($a['tipo_nome'] ?? 'Não informado') ?></td>
            <td><?= (int) $a['total_registros'] ?></td>
            <td class="acoes">
              <a class="botao botao-pequeno botao-secundario" href="<?= e(url('alunos/' . (int) $a['id'] . '/editar')) ?>">Editar<span class="sr-only"> <?= e($a['nome']) ?></span></a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php else: ?>
  <p>Nenhum aluno encontrado. <a href="<?= e(url('alunos/novo')) ?>">Cadastre seu primeiro aluno</a>.</p>
<?php endif; ?>
