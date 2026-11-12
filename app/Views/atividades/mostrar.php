<?php
$a = $atividade;
$extensao  = strtolower(pathinfo((string) $a['video'], PATHINFO_EXTENSION));
$tipoVideo = $extensao === 'webm' ? 'video/webm' : 'video/mp4';
?>
<p class="migalha"><a href="<?= e(url('atividades')) ?>">&larr; Voltar para atividades</a></p>

<div class="cabecalho-pagina">
  <h1><?= e($a['titulo']) ?></h1>
  <?php if ($podeEditar): ?>
    <div class="acoes">
      <a class="botao botao-secundario" href="<?= e(url('atividades/' . (int) $a['id'] . '/editar')) ?>">Editar</a>
      <form action="<?= e(url('atividades/' . (int) $a['id'] . '/excluir')) ?>" method="POST"
            onsubmit="return confirm('Excluir esta atividade? Os registros de acompanhamento continuam no histórico, sem o nome da atividade.')">
        <?= csrf_field() ?>
        <button type="submit" class="botao botao-perigo">Excluir</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<p>
  <?php foreach ($tiposAtividade as $tp): ?><span class="etiqueta"><?= e($tp['nome']) ?></span><?php endforeach; ?>
  <?php if (!empty($a['area'])): ?><span class="etiqueta"><?= e(area_nome($a['area'])) ?></span><?php endif; ?>
</p>

<p class="aviso-tela">
  <strong>Mini game: <?= e(\App\Services\SessaoAtividade::NOMES[$a['formato']] ?? $a['formato']) ?>.</strong>
  Para aplicar, abra a ficha do aluno e use "Sessão de jogos na tela" ou o botão
  "Fazer na tela" nas atividades sugeridas. O sistema mede acertos, dicas e tempo e registra o resultado
  na meta da área <?= e(area_nome($a['area'])) ?>.
  <a href="<?= e(url('alunos')) ?>">Ir para Meus alunos</a>
</p>

<div class="detalhe">
  <div class="detalhe-texto">
    <h2>Descrição</h2>
    <p><?= nl2br(e($a['descricao'])) ?></p>

    <?php if (!empty($a['objetivo'])): ?>
      <h2>Objetivo</h2>
      <p><?= e($a['objetivo']) ?></p>
    <?php endif; ?>

    <?php if (!empty($a['materiais'])): ?>
      <h2>Materiais</h2>
      <p><?= e($a['materiais']) ?></p>
    <?php endif; ?>

    <h2>Fonte</h2>
    <p><?= e($a['fonte']) ?></p>

    <?php if (!empty($a['autor_nome'])): ?>
      <p class="dica">Cadastrada por <?= e($a['autor_nome']) ?>.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($a['imagem'])): ?>
    <div class="detalhe-imagem">
      <img src="<?= e(public_url($a['imagem'])) ?>" alt="<?= e($a['imagem_alt']) ?>">
    </div>
  <?php endif; ?>
</div>

<?php if (!empty($a['video'])): ?>
  <section class="video-bloco" aria-labelledby="titulo-video">
    <h2 id="titulo-video">Vídeo</h2>
    <?php if (midia_externa($a['video'])): ?>
      <p><a class="botao botao-secundario" href="<?= e($a['video']) ?>" target="_blank" rel="noopener noreferrer">Assistir ao vídeo (abre em nova aba)</a></p>
    <?php else: ?>
      <video controls preload="metadata">
        <source src="<?= e(public_url($a['video'])) ?>" type="<?= e($tipoVideo) ?>">
        <?php if (!empty($a['legenda'])): ?>
          <track kind="captions" src="<?= e(public_url($a['legenda'])) ?>" srclang="pt-BR" label="Português" default>
        <?php endif; ?>
        Seu navegador não suporta vídeo. <a href="<?= e(public_url($a['video'])) ?>">Baixe o vídeo</a>.
      </video>
      <?php if (empty($a['legenda'])): ?>
        <p class="dica">Este vídeo ainda não possui arquivo de legendas.</p>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($a['transcricao'])): ?>
      <details>
        <summary>Ler a transcrição do vídeo</summary>
        <p><?= nl2br(e($a['transcricao'])) ?></p>
      </details>
    <?php endif; ?>
  </section>
<?php endif; ?>
