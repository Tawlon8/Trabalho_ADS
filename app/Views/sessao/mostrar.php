<?php
/* Rodada do mini game. Figuras decorativas e rótulos neutros ("Figura 1"), para não entregar a resposta. */

use App\Services\Figuras;

$cena    = $item['cena'];
$noTablet = in_array($cena['tipo'], ['tablet', 'app'], true);
$progresso = (int) round(100 * ($numero - 1) / max(1, $total));
?>
<section class="jogo" aria-labelledby="titulo-sessao">

  <div class="jogo-topo">
    <h1 id="titulo-sessao"><?= e($titulo) ?></h1>
    <p class="resultado" aria-live="polite">Rodada <?= (int) $numero ?> de <?= (int) $total ?></p>
  </div>

  <div class="progresso" role="img"
       aria-label="<?= (int) ($numero - 1) ?> de <?= (int) $total ?> rodadas concluídas">
    <span style="width: <?= $progresso ?>%"></span>
  </div>

  <?php if ($jogo !== '' && $jogo !== $titulo): ?>
    <p class="dica">Jogo: <?= e($jogo) ?></p>
  <?php endif; ?>

  <?php if (!empty($item['etapa_rotulo'])): ?>
    <p class="etapa-atual"><?= e($item['etapa_rotulo']) ?></p>
  <?php endif; ?>

  <?php if (!empty($feedback)): ?>
    <p class="feedback <?= $feedback['acertou'] ? 'feedback-certo' : 'feedback-errado' ?>" aria-live="polite">
      <?= e($feedback['texto']) ?>
    </p>
  <?php endif; ?>

  <p class="enunciado"><?= e($item['enunciado']) ?></p>

  <div class="<?= $noTablet ? 'tablet' : 'palco' ?>">

    <?php if ($noTablet): ?>
      <div class="tablet-barra" aria-hidden="true">
        <span>UNIA</span><span>9:00</span>
      </div>
    <?php endif; ?>

    <?php if ($cena['tipo'] === 'app'): ?>
      <div class="app-barra"><span><?= e($cena['app']) ?></span></div>
    <?php endif; ?>

    <?php if ($cena['tipo'] !== 'nenhuma' && $cena['tipo'] !== 'tablet'): ?>
    <div class="cena">
      <?php if ($cena['tipo'] === 'figura'): ?>
        <?= Figuras::svg($cena['nome'], 220) ?>

      <?php elseif ($cena['tipo'] === 'contagem'): ?>
        <div class="cena-contagem">
          <?php for ($n = 0; $n < (int) $cena['quantidade']; $n++): ?>
            <?= Figuras::svg($cena['nome'], 90) ?>
          <?php endfor; ?>
        </div>

      <?php elseif ($cena['tipo'] === 'soma'): ?>
        <div class="cena-contagem">
          <?php for ($n = 0; $n < (int) $cena['grupos'][0]; $n++): ?><?= Figuras::svg($cena['nome'], 70) ?><?php endfor; ?>
          <span class="cena-palavra" aria-hidden="true">+</span>
          <?php for ($n = 0; $n < (int) $cena['grupos'][1]; $n++): ?><?= Figuras::svg($cena['nome'], 70) ?><?php endfor; ?>
        </div>

      <?php elseif ($cena['tipo'] === 'ouvir'): ?>
        <?php /* A palavra não aparece escrita: a atividade mede a compreensão do que se ouve. */ ?>
        <button type="button" class="botao botao-ouvir" data-texto="<?= e(mb_strtolower($cena['texto'], 'UTF-8')) ?>"
                onclick="if (!window.speechSynthesis) { alert('Este navegador não tem voz. Use Chrome ou Edge.'); return; }
                         var u = new SpeechSynthesisUtterance(this.dataset.texto); u.lang = 'pt-BR';
                         speechSynthesis.cancel(); speechSynthesis.speak(u);">
          <i class="fa-solid fa-volume-high" aria-hidden="true"></i> Ouvir a palavra
        </button>

      <?php elseif ($cena['tipo'] === 'palavra'): ?>
        <p class="cena-palavra"><?= e($cena['texto']) ?></p>

      <?php elseif ($cena['tipo'] === 'figura_palavra'): ?>
        <?= Figuras::svg($cena['nome'], 170) ?>
        <p class="cena-palavra"><?= e($cena['texto']) ?></p>

      <?php elseif ($cena['tipo'] === 'sequencia'): ?>
        <div class="cena-sequencia">
          <?php foreach ($cena['numeros'] as $n): ?>
            <span class="numero-caixa <?= $n === null ? 'numero-vazio' : '' ?>">
              <?= $n === null ? '?' : (int) $n ?>
            </span>
          <?php endforeach; ?>
        </div>

      <?php elseif ($cena['tipo'] === 'app' && !empty($cena['nome'])): ?>
        <?= Figuras::svg($cena['nome'], 170) ?>

      <?php elseif ($cena['tipo'] === 'app'): ?>
        <p class="cena-palavra">Muito bem!</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($dicaUsada): ?>
      <p class="aviso-dica" aria-live="polite">Dica: sobraram duas opções para escolher.</p>
    <?php endif; ?>

    <form action="<?= e(url('sessao/responder')) ?>" method="POST"
          class="opcoes-jogo<?= $cena['tipo'] === 'tablet' ? ' opcoes-tela-inicial' : '' ?>">
      <?= csrf_field() ?>
      <?php foreach ($visiveis as $i): ?>
        <?php $o = $item['opcoes'][$i]; ?>
        <button type="submit" name="resposta" value="<?= (int) $i ?>"
                class="opcao opcao-<?= e($o['tipo']) ?>">
          <?php if ($o['tipo'] === 'numero'): ?>
            <span class="opcao-numero" aria-hidden="true"><?= (int) $o['valor'] ?></span>
            <span class="sr-only"><?= e($o['rotulo']) ?></span>

          <?php elseif ($o['tipo'] === 'texto'): ?>
            <span class="opcao-texto"><?= e($o['valor']) ?></span>

          <?php elseif ($o['tipo'] === 'figura'): ?>
            <?= Figuras::svg($o['nome'], 120) ?>
            <span class="sr-only"><?= e($o['rotulo']) ?></span>

          <?php elseif ($o['tipo'] === 'grupo'): ?>
            <span class="opcao-grupo" aria-hidden="true">
              <?php for ($n = 0; $n < (int) $o['quantidade']; $n++): ?>
                <?= Figuras::svg($o['nome'], 54) ?>
              <?php endfor; ?>
            </span>
            <span class="sr-only"><?= e($o['rotulo']) ?></span>

          <?php elseif ($o['tipo'] === 'icone'): ?>
            <?= Figuras::iconeApp($o['app'], 92) ?>
            <span class="opcao-legenda"><?= e($o['valor']) ?></span>

          <?php elseif ($o['tipo'] === 'janela'): ?>
            <span class="opcao-janela" aria-hidden="true">
              <?= $o['acao'] === 'fechar' ? '&times;' : ($o['acao'] === 'minimizar' ? '&minus;' : '&larr;') ?>
            </span>
            <span class="opcao-legenda"><?= e($o['valor']) ?></span>
          <?php endif; ?>
        </button>
      <?php endforeach; ?>
    </form>
  </div>

  <div class="acoes jogo-acoes">
    <?php if ($temDica && !$dicaUsada): ?>
      <form action="<?= e(url('sessao/dica')) ?>" method="POST">
        <?= csrf_field() ?>
        <button type="submit" class="botao botao-secundario">Pedir dica</button>
      </form>
    <?php elseif ($dicaUsada): ?>
      <p class="dica">Dica já usada nesta rodada.</p>
    <?php endif; ?>
    <form action="<?= e(url('sessao/cancelar')) ?>" method="POST"
          onsubmit="return confirm('Cancelar a sessão? Nada será registrado.')">
      <?= csrf_field() ?>
      <button type="submit" class="botao botao-perigo">Cancelar</button>
    </form>
  </div>
</section>
