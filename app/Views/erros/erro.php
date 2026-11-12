<section class="erro-pagina">
  <h1>Erro <?= (int) $codigo ?></h1>
  <p><?= e($mensagem) ?></p>
  <p><a class="botao" href="<?= e(url('')) ?>">Voltar para o início</a></p>
</section>
