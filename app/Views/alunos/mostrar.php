<?php
use App\Models\Acompanhamento;
use App\Services\Grafico;
use App\Services\SessaoAtividade;
use Core\Config;

$alunoId        = (int) $aluno['id'];
$destino        = SessaoAtividade::metaPorArea($metas);   // meta que recebe o resultado de cada área
$niveis         = Acompanhamento::NIVEIS;
$totalRegistros = array_sum($resumo);
$hoje           = date('Y-m-d');
?>
<p class="migalha"><a href="<?= e(url('alunos')) ?>">&larr; Meus alunos</a></p>

<div class="cabecalho-pagina">
  <h1><?= e($aluno['nome']) ?></h1>
  <a class="botao botao-secundario" href="<?= e(url('alunos/' . $alunoId . '/editar')) ?>">Editar dados e estudo de caso</a>
</div>

<dl class="dados">
  <dt>Data de nascimento</dt>
  <dd><?= $aluno['data_nascimento'] ? e(data_br($aluno['data_nascimento'])) : 'Não informada' ?></dd>
  <dt>Tipo de deficiência</dt>
  <dd><?= e($aluno['tipo_nome'] ?? 'Não informado') ?></dd>
</dl>

<section aria-labelledby="titulo-estudo">
  <h2 id="titulo-estudo">Estudo de caso</h2>
  <?php if (!empty($aluno['observacoes'])): ?>
    <p><?= nl2br(e($aluno['observacoes'])) ?></p>
  <?php else: ?>
    <p>Nenhum estudo de caso registrado. Use "Editar dados e estudo de caso" para registrar as observações.</p>
  <?php endif; ?>
</section>

<section id="plano" aria-labelledby="titulo-plano">
  <div class="cabecalho-pagina">
    <h2 id="titulo-plano">Plano de apoio (metas)</h2>
    <a class="botao" href="<?= e(url('alunos/' . $alunoId . '/metas/nova')) ?>">Nova meta</a>
  </div>
  <p class="dica">Cada meta é uma habilidade priorizada, em uma das áreas da matriz de habilidades do AppPEI, com o nível de suporte inicial.</p>

  <?php if ($metas): ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="sr-only">Metas do plano de apoio de <?= e($aluno['nome']) ?></caption>
        <thead>
          <tr>
            <th scope="col">Área</th>
            <th scope="col">Meta</th>
            <th scope="col">Suporte inicial</th>
            <th scope="col">Suporte atual</th>
            <th scope="col">Registros</th>
            <th scope="col">Revisão</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($metas as $m): ?>
            <tr>
              <td><?= e(area_nome($m['area'])) ?></td>
              <td><?= e($m['descricao']) ?><?php if (!empty($m['professor_responsavel'])): ?><br><small>Professor: <?= e($m['professor_responsavel']) ?></small><?php endif; ?></td>
              <td><?= e(nivel_nome($m['nivel_inicial'])) ?></td>
              <td><?= e(nivel_nome($m['nivel_atual'])) ?></td>
              <td><?= (int) $m['registros'] ?></td>
              <td>
                <?= $m['revisar_em'] ? e(data_br($m['revisar_em'])) : 'Sem data' ?>
                <?php if ($m['revisar_em'] && $m['revisar_em'] <= $hoje): ?>
                  <br><span class="vencida">Revisão vencida</span>
                <?php endif; ?>
              </td>
              <td class="acoes">
                <a class="botao botao-pequeno botao-secundario" href="<?= e(url('metas/' . (int) $m['id'] . '/editar')) ?>">Editar<span class="sr-only"> meta</span></a>
                <form action="<?= e(url('metas/' . (int) $m['id'] . '/excluir')) ?>" method="POST"
                      onsubmit="return confirm('Excluir esta meta? Os registros dela continuam no histórico.')">
                  <?= csrf_field() ?>
                  <button type="submit" class="botao botao-pequeno botao-perigo">Excluir<span class="sr-only"> meta</span></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>Nenhuma meta cadastrada. Crie a primeira meta para acompanhar o aluno.</p>
  <?php endif; ?>
</section>

<?php if ($metas): ?>
<section aria-labelledby="titulo-evolucao">
  <h2 id="titulo-evolucao">Evolução nas sessões na tela</h2>
  <p class="dica">
    Calculado só a partir das sessões feitas na tela pelo aluno. O acerto médio é a proporção de
    respostas corretas; a autonomia é a proporção de sessões realizadas sem necessidade de suporte;
    a variação compara a primeira metade das sessões com a segunda e aparece a partir de seis sessões.
  </p>
  <?php $temEvolucao = false; foreach ($metas as $m) { if (($evolucao[$m['id']]['total'] ?? 0) > 0) { $temEvolucao = true; break; } } ?>
  <?php if ($temEvolucao): ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="sr-only">Proporções de evolução por meta</caption>
        <thead>
          <tr>
            <th scope="col">Área e meta</th>
            <th scope="col">Sessões</th>
            <th scope="col">Acerto médio</th>
            <th scope="col">Autonomia</th>
            <th scope="col">Com dica</th>
            <th scope="col">Variação</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($metas as $m): ?>
            <?php $ev = $evolucao[$m['id']] ?? null; ?>
            <?php if (!$ev || $ev['total'] === 0) { continue; } ?>
            <tr>
              <td><?= e(area_nome($m['area'])) ?><br><small><?= e($m['descricao']) ?></small></td>
              <td><?= (int) $ev['total'] ?></td>
              <td><?= e(number_format((float) $ev['taxa_media'], 1, ',', '.')) ?>%</td>
              <td><?= e(number_format((float) $ev['autonomia'], 1, ',', '.')) ?>%</td>
              <td><?= e(number_format((float) $ev['dependencia_dica'], 1, ',', '.')) ?>%</td>
              <td>
                <?php if ($ev['delta'] === null): ?>
                  <span class="dica">Precisa de 6 sessões</span>
                <?php else: ?>
                  <?= $ev['delta'] > 0 ? '+' : '' ?><?= e(number_format((float) $ev['delta'], 1, ',', '.')) ?>
                  pontos percentuais
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="legenda-suporte" aria-hidden="true">
      <?php foreach ($niveis as $chave => $rotulo): ?>
        <span><?= Grafico::simboloSuporte($chave, 18) ?> <?= e($rotulo) ?></span>
      <?php endforeach; ?>
    </p>
    <div class="graficos">
      <?php foreach ($metas as $m): ?>
        <?php $lista = $sessoes[$m['id']] ?? []; if (!$lista) { continue; } ?>
        <figure class="grafico-meta">
          <figcaption>
            <strong><?= e(area_nome($m['area'])) ?></strong>: <?= e($m['descricao']) ?>
          </figcaption>
          <?= Grafico::linhaAcerto($lista, $evolucao[$m['id']], 'grafico-meta-' . (int) $m['id']) ?>
          <?php $ev = $evolucao[$m['id']]; ?>
          <p class="dica">Acerto (%) em cada sessão, da mais antiga para a mais nova.</p>
          <?php if ($ev['media_inicio'] !== null): ?>
            <p class="legenda-medias">
              <span class="traco-media" aria-hidden="true"></span>
              Média da 1ª metade: <strong><?= e(number_format((float) $ev['media_inicio'], 1, ',', '.')) ?>%</strong>
              &rarr; 2ª metade: <strong><?= e(number_format((float) $ev['media_fim'], 1, ',', '.')) ?>%</strong>
            </p>
          <?php else: ?>
            <p class="dica">As médias de cada metade aparecem a partir de 6 sessões.</p>
          <?php endif; ?>
          <p class="rotulo-linha-suporte">Nível de suporte em cada sessão:</p>
          <ol class="linha-suporte">
            <?php foreach ($lista as $i => $s): ?>
              <li title="Sessão <?= $i + 1 ?>: <?= e($niveis[$s['desempenho']]) ?>">
                <?= Grafico::simboloSuporte($s['desempenho']) ?>
                <span class="sr-only">Sessão <?= $i + 1 ?>: <?= e($niveis[$s['desempenho']]) ?></span>
              </li>
            <?php endforeach; ?>
          </ol>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p>Nenhuma sessão na tela registrada ainda. Use "Sessão de jogos na tela" abaixo.</p>
  <?php endif; ?>
</section>

<section aria-labelledby="titulo-sugestoes">
  <h2 id="titulo-sugestoes">Atividades sugeridas por meta</h2>
  <p class="dica">Sugestões são atividades da mesma área da meta que ainda não foram aplicadas a este aluno.</p>
  <?php foreach ($metas as $m): ?>
    <?php $lista = $sugestoes[$m['id']] ?? []; ?>
    <h3><?= e(area_nome($m['area'])) ?>: <?= e($m['descricao']) ?></h3>
    <?php if ($lista): ?>
      <ul class="lista-simples">
        <?php foreach ($lista as $a): ?>
          <li>
            <a href="<?= e(url('atividades/' . (int) $a['id'])) ?>"><?= e($a['titulo']) ?></a>
            <a class="botao botao-pequeno botao-secundario" href="<?= e(url('alunos/' . $alunoId . '/sessao?atividade=' . (int) $a['id'] . '&meta=' . (int) $m['id'])) ?>">Fazer na tela</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>Nenhuma atividade desta área ainda sem registro para este aluno.</p>
    <?php endif; ?>
  <?php endforeach; ?>
</section>
<?php endif; ?>

<section aria-labelledby="titulo-sessao-jogos">
  <h2 id="titulo-sessao-jogos">Sessão de jogos na tela</h2>
  <p class="dica">
    Escolha um ou mais jogos. A criança responde na tela (computador ou tablet) e o resultado
    é registrado automaticamente na meta indicada ao lado de cada jogo. Se a área tem mais de uma meta,
    vale a primeira cadastrada; jogos de áreas sem meta ficam desabilitados.
  </p>
  <?php if ($executaveis): ?>
    <form action="<?= e(url('alunos/' . $alunoId . '/sessao')) ?>" method="GET" class="formulario">
      <fieldset>
        <legend>Jogos que podem ser feitos na tela</legend>
        <?php foreach ($executaveis as $j): ?>
          <?php $metaDestino = $destino[$j['area']] ?? null; ?>
          <div class="campo-check">
            <input type="checkbox" id="jogo-<?= (int) $j['id'] ?>" name="atividades[]" value="<?= (int) $j['id'] ?>"<?= $metaDestino ? '' : ' disabled' ?>>
            <label for="jogo-<?= (int) $j['id'] ?>">
              <?= e($j['titulo']) ?>
              <span class="dica">(<?= e(area_nome($j['area'])) ?>) ·
                <?= $metaDestino ? 'grava na meta: ' . e($metaDestino['descricao']) : 'sem meta nesta área' ?></span>
            </label>
          </div>
        <?php endforeach; ?>
      </fieldset>
      <button type="submit" class="botao">Iniciar sessão</button>
    </form>
  <?php else: ?>
    <p>Nenhum jogo disponível para fazer na tela.</p>
  <?php endif; ?>
</section>

<section aria-labelledby="titulo-historico">
  <h2 id="titulo-historico">Histórico de acompanhamento</h2>
  <?php if ($totalRegistros > 0): ?>
    <ul class="barras">
      <?php foreach ($niveis as $chave => $rotulo): ?>
        <li>
          <label for="nivel-<?= e($chave) ?>"><?= e($rotulo) ?>: <strong><?= (int) $resumo[$chave] ?></strong></label>
          <progress id="nivel-<?= e($chave) ?>" max="<?= (int) $totalRegistros ?>" value="<?= (int) $resumo[$chave] ?>"><?= (int) $resumo[$chave] ?> de <?= (int) $totalRegistros ?></progress>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($acompanhamentos): ?>
    <div class="tabela-rolagem">
      <table class="tabela">
        <caption class="sr-only">Registros de acompanhamento de <?= e($aluno['nome']) ?></caption>
        <thead>
          <tr>
            <th scope="col">Data</th>
            <th scope="col">Jogo</th>
            <th scope="col">Meta</th>
            <th scope="col">Suporte</th>
            <th scope="col">Acertos e tempo</th>
            <th scope="col">Detalhe por jogo</th>
            <th scope="col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($acompanhamentos as $c): ?>
            <tr>
              <td><?= e(data_br($c['data_aplicacao'])) ?></td>
              <td>
                <?php if ($c['atividade_id']): ?>
                  <a href="<?= e(url('atividades/' . (int) $c['atividade_id'])) ?>"><?= e($c['atividade_titulo']) ?></a>
                <?php else: ?>
                  Atividade removida
                <?php endif; ?>
              </td>
              <td><?= $c['meta_descricao'] ? e($c['meta_descricao']) : 'Meta removida' ?></td>
              <td><?= e(nivel_nome($c['desempenho'])) ?></td>
              <td>
                <?= (int) $c['acertos'] ?> de <?= (int) $c['rodadas'] ?>
                <?php if ((int) $c['dicas'] > 0): ?>(<?= (int) $c['dicas'] ?> dica<?= (int) $c['dicas'] === 1 ? '' : 's' ?>)<?php endif; ?>
                · <?= (int) $c['tempo_segundos'] ?> s
              </td>
              <td><?= e($c['observacao'] ?? '') ?></td>
              <td class="acoes">
                <form action="<?= e(url('acompanhamentos/' . (int) $c['id'] . '/excluir')) ?>" method="POST"
                      onsubmit="return confirm('Excluir este registro? Ele deixa de contar na evolução.')">
                  <?= csrf_field() ?>
                  <button type="submit" class="botao botao-pequeno botao-perigo">Excluir<span class="sr-only"> registro de <?= e(data_br($c['data_aplicacao'])) ?></span></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p>Nenhum registro ainda. Os registros são criados ao concluir uma sessão de jogos na tela.</p>
  <?php endif; ?>
</section>
