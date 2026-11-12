<div class="cabecalho-pagina">
  <h1>Atividades</h1>
  <a class="botao" href="<?= e(url('atividades/nova')) ?>">Nova atividade</a>
</div>

<form class="filtros" action="<?= e(url('atividades')) ?>" method="GET" role="search">
  <div class="campo">
    <label for="q">Buscar por palavra</label>
    <input type="search" id="q" name="q" value="<?= e($busca) ?>" maxlength="100" placeholder="Ex.: sílaba, contar, ouvir">
  </div>
  <div class="campo">
    <label for="tipo">Tipo de deficiência</label>
    <select id="tipo" name="tipo">
      <option value="">Todos os tipos</option>
      <?php foreach ($tipos as $t): ?>
        <option value="<?= (int) $t['id'] ?>"<?= selecionado($tipo, $t['id']) ?>><?= e($t['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="campo">
    <label for="area">Área de habilidade</label>
    <select id="area" name="area">
      <option value="">Todas as áreas</option>
      <?php foreach (\Core\Config::get('areas') as $chave => $rotulo): ?>
        <option value="<?= e($chave) ?>"<?= selecionado($area, $chave) ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <button type="submit" class="botao">Filtrar</button>
  <?php if ($busca !== '' || $tipo > 0 || $area !== ''): ?>
    <a class="botao botao-secundario" href="<?= e(url('atividades')) ?>">Limpar filtros</a>
  <?php endif; ?>
</form>

<p class="resultado" aria-live="polite">
  <?= (int) $total ?> atividade<?= $total === 1 ? '' : 's' ?> encontrada<?= $total === 1 ? '' : 's' ?>.
</p>

<?php if ($itens): ?>
  <ul class="grade-cartoes grade-atividades">
    <?php foreach ($itens as $a): ?>
      <li>
        <article class="cartao cartao-atividade">
          <?php if (!empty($a['imagem'])): ?>
            <img src="<?= e(public_url($a['imagem'])) ?>" alt="<?= e($a['imagem_alt']) ?>" loading="lazy">
          <?php endif; ?>
          <h2><a href="<?= e(url('atividades/' . (int) $a['id'])) ?>"><?= e($a['titulo']) ?></a></h2>
          <p class="etiqueta"><?= e($a['tipos_nomes'] ?? '') ?></p>
          <p class="etiqueta"><?= e(area_nome($a['area'])) ?></p>
          <p><?= e(mb_strimwidth($a['descricao'], 0, 140, '…', 'UTF-8')) ?></p>
        </article>
      </li>
    <?php endforeach; ?>
  </ul>
  <?= paginacao((int) $total, (int) $pagina, (int) $porPagina, 'atividades', ['q' => $busca, 'tipo' => $tipo, 'area' => $area]) ?>
<?php else: ?>
  <p>Nenhuma atividade encontrada. Tente outra palavra ou <a href="<?= e(url('atividades/nova')) ?>">cadastre uma nova atividade</a>.</p>
<?php endif; ?>
