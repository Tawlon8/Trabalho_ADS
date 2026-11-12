<?php
$editando = $atividade !== null;
$acao     = $editando ? 'atividades/' . (int) $atividade['id'] . '/editar' : 'atividades';
$voltar   = $editando ? 'atividades/' . (int) $atividade['id'] : 'atividades';
$v        = function ($campo) use ($atividade) { return old($campo, $atividade[$campo] ?? ''); };
?>
<p class="migalha"><a href="<?= e(url($voltar)) ?>">&larr; Voltar</a></p>
<h1><?= e($titulo) ?></h1>
<p class="dica">Campos com * são obrigatórios. Informe a fonte da atividade: se for sua, escreva isso; se veio de outro material, cite-o.</p>

<form action="<?= e(url($acao)) ?>" method="POST" enctype="multipart/form-data" class="formulario" novalidate>
  <?= csrf_field() ?>

  <div class="campo">
    <label for="titulo">Título *</label>
    <input type="text" id="titulo" name="titulo" value="<?= e($v('titulo')) ?>" maxlength="100" required<?= aria_erro('titulo') ?>>
    <?= campo_erro('titulo') ?>
  </div>

  <fieldset id="tipos">
    <legend>Tipos de deficiência * (marque todos a que a atividade se destina)</legend>
    <?php $marcados = array_map('intval', (array) old('tipos', $tiposSelecionados ?? [])); ?>
    <?php foreach ($tipos as $t): ?>
      <div class="campo campo-check">
        <input type="checkbox" id="tipo-<?= (int) $t['id'] ?>" name="tipos[]" value="<?= (int) $t['id'] ?>"<?= marcado(in_array((int) $t['id'], $marcados, true)) ?>>
        <label for="tipo-<?= (int) $t['id'] ?>"><?= e($t['nome']) ?></label>
      </div>
    <?php endforeach; ?>
    <?= campo_erro('tipos') ?>
  </fieldset>

  <div class="campo">
    <label for="area">Área de habilidade *</label>
    <select id="area" name="area" required<?= aria_erro('area') ?>>
      <option value="">Selecione…</option>
      <?php foreach (\Core\Config::get('areas') as $chave => $rotulo): ?>
        <option value="<?= e($chave) ?>"<?= selecionado($v('area'), $chave) ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
    <small class="dica">O resultado do mini game é gravado na meta do aluno desta área.</small>
    <?= campo_erro('area') ?>
  </div>

  <div class="campo">
    <label for="formato">Mini game na tela *</label>
    <select id="formato" name="formato" required<?= aria_erro('formato') ?>>
      <option value="">Selecione…</option>
      <?php foreach (\App\Services\SessaoAtividade::NOMES as $chave => $rotulo): ?>
        <option value="<?= e($chave) ?>"<?= selecionado($v('formato'), $chave) ?>><?= e($rotulo) ?></option>
      <?php endforeach; ?>
    </select>
    <small class="dica">
      O aluno faz a atividade na tela e o sistema mede acertos, dicas e tempo, gravando o resultado
      na meta da área. As rodadas são sorteadas pelo sistema a partir do catálogo de figuras.
    </small>
    <?= campo_erro('formato') ?>
  </div>

  <div class="campo">
    <label for="descricao">Descrição *</label>
    <textarea id="descricao" name="descricao" rows="6" maxlength="5000" required<?= aria_erro('descricao') ?>><?= e($v('descricao')) ?></textarea>
    <?= campo_erro('descricao') ?>
  </div>

  <div class="campo">
    <label for="objetivo">Objetivo</label>
    <input type="text" id="objetivo" name="objetivo" value="<?= e($v('objetivo')) ?>" maxlength="255"<?= aria_erro('objetivo') ?>>
    <?= campo_erro('objetivo') ?>
  </div>

  <div class="campo">
    <label for="materiais">Materiais</label>
    <input type="text" id="materiais" name="materiais" value="<?= e($v('materiais')) ?>" maxlength="255"<?= aria_erro('materiais') ?>>
    <?= campo_erro('materiais') ?>
  </div>

  <div class="campo">
    <label for="fonte">Fonte da atividade *</label>
    <input type="text" id="fonte" name="fonte" value="<?= e($v('fonte')) ?>" maxlength="255" required<?= aria_erro('fonte') ?>>
    <small class="dica">Exemplo: "Proposta própria", ou o material, o autor e o ano de onde a atividade foi tirada.</small>
    <?= campo_erro('fonte') ?>
  </div>

  <fieldset>
    <legend>Imagem (opcional)</legend>
    <?php if ($editando && !empty($atividade['imagem'])): ?>
      <p>Imagem atual: <img class="miniatura" src="<?= e(public_url($atividade['imagem'])) ?>" alt="<?= e($atividade['imagem_alt']) ?>"></p>
      <div class="campo campo-check">
        <input type="checkbox" id="remover_imagem" name="remover_imagem" value="1">
        <label for="remover_imagem">Remover a imagem atual</label>
      </div>
    <?php endif; ?>
    <div class="campo">
      <label for="imagem"><?= $editando && !empty($atividade['imagem']) ? 'Trocar imagem' : 'Enviar imagem' ?></label>
      <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp"<?= aria_erro('imagem') ?>>
      <small class="dica">JPG, PNG ou WEBP, até 2 MB. Não envie fotos de crianças sem autorização dos responsáveis.</small>
      <?= campo_erro('imagem') ?>
    </div>
    <div class="campo">
      <label for="imagem_alt">Descrição da imagem (texto alternativo)</label>
      <input type="text" id="imagem_alt" name="imagem_alt" value="<?= e($v('imagem_alt')) ?>" maxlength="200"<?= aria_erro('imagem_alt') ?>>
      <small class="dica">Obrigatória quando há imagem. Descreva o que aparece, para quem usa leitor de tela.</small>
      <?= campo_erro('imagem_alt') ?>
    </div>
  </fieldset>

  <fieldset>
    <legend>Vídeo (opcional)</legend>
    <div class="campo">
      <label for="video">Endereço do vídeo</label>
      <input type="text" id="video" name="video" value="<?= e($v('video')) ?>" maxlength="255" placeholder="assets/video/arquivo.mp4 ou https://..."<?= aria_erro('video') ?>>
      <small class="dica">Caminho de um vídeo em public/assets/video, ou um link http(s).</small>
      <?= campo_erro('video') ?>
    </div>
    <div class="campo">
      <label for="legenda">Arquivo de legendas (.vtt)</label>
      <input type="text" id="legenda" name="legenda" value="<?= e($v('legenda')) ?>" maxlength="255" placeholder="assets/video/legenda.vtt"<?= aria_erro('legenda') ?>>
      <small class="dica">Opcional. Arquivo de legendas no formato WebVTT, na mesma pasta do vídeo.</small>
      <?= campo_erro('legenda') ?>
    </div>
    <div class="campo">
      <label for="transcricao">Transcrição do vídeo</label>
      <textarea id="transcricao" name="transcricao" rows="4" maxlength="5000"<?= aria_erro('transcricao') ?>><?= e($v('transcricao')) ?></textarea>
      <?= campo_erro('transcricao') ?>
    </div>
  </fieldset>

  <div class="acoes">
    <button type="submit" class="botao">Salvar</button>
    <a class="botao botao-secundario" href="<?= e(url($voltar)) ?>">Cancelar</a>
  </div>
</form>
