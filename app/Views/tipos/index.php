<div class="cabecalho-pagina">
  <h1>Tipos de deficiência</h1>
  <a class="botao" href="<?= e(url('tipos/novo')) ?>">Novo tipo</a>
</div>

<div class="tabela-rolagem">
  <table class="tabela">
    <caption class="sr-only">Lista de tipos de deficiência cadastrados</caption>
    <thead>
      <tr>
        <th scope="col">Nome</th>
        <th scope="col">Descrição</th>
        <th scope="col">Atividades</th>
        <th scope="col">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tipos as $t): ?>
        <tr>
          <th scope="row"><i class="fas <?= e($t['icone']) ?>" aria-hidden="true"></i> <?= e($t['nome']) ?></th>
          <td><?= e($t['descricao']) ?></td>
          <td><?= (int) $t['total_atividades'] ?></td>
          <td class="acoes">
            <a class="botao botao-pequeno botao-secundario" href="<?= e(url('tipos/' . (int) $t['id'] . '/editar')) ?>">Editar<span class="sr-only"> <?= e($t['nome']) ?></span></a>
            <form action="<?= e(url('tipos/' . (int) $t['id'] . '/excluir')) ?>" method="POST"
                  onsubmit="return confirm('Excluir este tipo de deficiência?')">
              <?= csrf_field() ?>
              <button type="submit" class="botao botao-pequeno botao-perigo">Excluir<span class="sr-only"> <?= e($t['nome']) ?></span></button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<p class="dica">Um tipo só pode ser excluído se não tiver atividades cadastradas.</p>
