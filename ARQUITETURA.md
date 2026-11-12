# Arquitetura do UNIA

Este documento descreve o código **como ele está**, incluindo o que não foi testado e o que tem limitações.
Use-o junto com os diagramas da pasta `diagramas/` (modelo lógico do banco, pacotes, classes e sequência).

> **Estado do código:** revisado linha a linha; passou nas verificações de sintaxe, de rotas e de chamadas, nos
> 63 testes automatizados e num teste de ponta a ponta contra o MySQL do XAMPP (cadastro, metas, 6 sessões por
> área, evolução, gráficos). As telas logadas ainda precisam do roteiro manual da seção 14.
>
> **Ideia central:** todo resultado é **medido pelo sistema**. O aluno faz mini games na tela; o sistema confere
> cada resposta e grava acertos, erros, dicas e tempo. O professor não digita resultados.

---

## 1. Visão em uma frase

O UNIA é uma aplicação web em **PHP puro, no padrão MVC** (Model, View, Controller), com um **roteador central**
(front controller) e **URLs amigáveis**. O professor cadastra alunos, monta um plano de apoio (metas do PEI),
deixa o aluno fazer os mini games na tela e acompanha a evolução de cada meta em tabelas e gráficos.

## 2. Como uma requisição percorre o sistema

Exemplo: o professor salva uma nova meta na ficha do aluno 5. O formulário envia `POST /alunos/5/metas`.

1. **Apache** recebe `http://localhost/Trabalho_ADS/alunos/5/metas`.
2. O **`.htaccess` da raiz** redireciona para a pasta `public/` (o resto do projeto fica fora do alcance da web).
3. O **`public/.htaccess`** envia a requisição para `public/index.php`, porque `alunos/5/metas` não é um arquivo real.
4. **`public/index.php`** carrega `core/bootstrap.php` (constantes, autoload, helpers, sessão) e pega o roteador
   de `config/routes.php`.
5. O **Router** procura a rota `POST /alunos/{id}/metas`, converte `{id}` em `5` e aplica os **middlewares**
   (aqui, `auth`: o usuário precisa estar logado).
6. Para toda requisição `POST`, o Router confere o **token CSRF** antes de executar a ação.
7. O Router cria `MetaController` e chama `salvar("5")`.
8. O **Controller** confere se o aluno 5 é do professor logado (`Aluno::buscarDoUsuario`), lê os campos com
   `Request`, valida com `Validator`, e:
   - se houver erro: guarda os dados e os erros na sessão (`voltarComErros`) e redireciona de volta ao formulário;
   - se estiver correto: chama `Meta::criar(...)` (**Model**), que executa o `INSERT` com **prepared statement**.
9. O Controller grava uma mensagem (`Session::flash`) e redireciona para `alunos/5#plano`.
10. Na próxima requisição (um `GET`), a **View** é montada com `render()`, dentro do **layout**, e enviada ao navegador.

Esse caminho é o mesmo para todas as telas. Mude o trecho que você precisa e o resto continua igual.

## 3. Estrutura de pastas e função de cada arquivo

| Caminho | O que é | Quando você mexe nele |
|---|---|---|
| `public/index.php` | Ponto de entrada. Carrega o sistema e despacha a requisição. | quase nunca |
| `public/.htaccess` | Envia tudo o que não é arquivo real para `index.php` (URLs amigáveis). | quase nunca |
| `public/assets/` | CSS (`css/app.css`), imagens, vídeos. | estilos e mídias |
| `public/uploads/` | Imagens enviadas pelos professores. Não executa PHP (`.htaccess` próprio). | nunca manualmente |
| `.htaccess` (raiz) | Redireciona a raiz do projeto para `public/`. | nunca |
| `config/config.php` | Configurações gerais: banco, e-mail, áreas, parâmetros da sessão e da revisão. | parâmetros |
| `config/config.local.php` | Segredos (senha do banco, senha de app). **Fora do Git.** Você cria a partir do exemplo. | ao instalar |
| `config/config.local.example.php` | Modelo do arquivo acima. | ao instalar |
| `config/routes.php` | Mapa de todas as rotas (método, URL, controller, ação, acesso). | ao criar telas |
| `core/bootstrap.php` | Constantes, autoload das classes, tratamento de erros, início da sessão. | quase nunca |
| `core/helpers.php` | Funções usadas nas views e nos controllers (`e()`, `url()`, `redirect()`, `render()`...). | ao criar recursos de tela |
| `core/Router.php` | Associa URL e método HTTP a um controller; aplica middlewares e CSRF. | quase nunca |
| `core/Controller.php` | Classe-base dos controllers (`view`, `erro`, `voltarComErros`). | quase nunca |
| `core/Model.php` | Classe-base dos models (`fetchAll`, `fetchOne`, `fetchValue`, `execute`). | quase nunca |
| `core/Database.php` | Conexão PDO única com o MySQL. | nunca |
| `core/Config.php` | Lê as configurações com chaves em pontos: `Config::get('sessao.maximo')`. | nunca |
| `core/Validator.php` | Validação de formulários no servidor (regras em texto, como `required\|min:3`). | ao criar regras |
| `core/Request.php` | Lê o `$_POST` e o `$_GET` com segurança (sem arrays, com `trim`). | nunca |
| `core/Session.php` | Mensagens (`flash`), dados antigos do formulário e erros entre requisições. | nunca |
| `core/Auth.php` | Login, logout, quem está logado, perfil de administrador. | nunca |
| `core/Csrf.php` | Token anti-CSRF: gera, põe no formulário e confere. | nunca |
| `core/Upload.php` | Envio de imagens: tipo real, tamanho, nome aleatório. | ao mexer em imagens |
| `core/Mailer.php` | Envio do e-mail de recuperação de senha (PHPMailer, SMTP). | ao mexer no e-mail |
| `app/Controllers/` | Um controller por área do sistema (ver seção 5). | ao criar telas |
| `app/Models/` | Um model por tabela (ver seção 6). Só contém SQL. | ao criar consultas |
| `app/Services/SessaoAtividade.php` | Monta as rodadas dos 12 mini games, classifica o nível de suporte e agrupa os resultados por meta. | mini games |
| `app/Services/Figuras.php` | Desenha em SVG as figuras e os ícones usados nos mini games. | figuras |
| `app/Services/Evolucao.php` | Proporções de evolução a partir das sessões de uma meta, sem banco. | medição |
| `app/Services/Grafico.php` | Desenha em SVG os gráficos de evolução da ficha do aluno. | gráficos |
| `app/Views/` | HTML das telas, organizado por pasta (`alunos/`, `metas/`, `sessao/`...). | ao criar telas |
| `app/Views/layouts/` | `main.php` (área logada, com menu), `auth.php` (login e cadastro) e `jogo.php` (sessão de jogos, sem menu). | visual geral |
| `database/banco_acessibilidade.sql` | Cria o banco, as tabelas e os dados iniciais. **Apaga o banco existente.** | ao instalar |
| `tests/run.php` | Testes automatizados das regras (sem banco). Rode com `php tests/run.php`. | ao mudar regras |
| `tests/diagnostico.php` | Confere a instalação do banco (conexão, tabelas, colunas, dados). Só lê. | quando der "Erro 500" |
| `composer.json` | Declara o PHPMailer. Instale com `composer install`. | ao instalar |
| `ARQUITETURA.md` | Este guia. | |

## 4. Núcleo (`core/`): classes e métodos principais

Essas classes são o "framework" caseiro do projeto. Todas são simples e pequenas de propósito.

- **`Router`**: `get()`/`post()` registram rotas; `dispatch($metodo)` escolhe a rota. Converte `{id}` em número
  (só dígitos) e `{token}` em 64 caracteres hexadecimais. Responde 404 (página não existe), 405 (método errado)
  ou 403 (CSRF inválido). Middlewares: `guest` (só visitante), `auth` (só logado), `admin` (só administrador).
- **`Controller`** (abstrata): `view($nome, $dados)` renderiza uma tela; `erro($codigo, $mensagem)` mostra erro e
  encerra; `voltarComErros($rota, $dados, $erros)` guarda o que foi digitado e as mensagens, e redireciona.
- **`Model`** (abstrata): `fetchAll`, `fetchOne`, `fetchValue` (leitura) e `execute` (escrita). Todas usam
  **prepared statements**: os valores vão como parâmetros `?`, nunca dentro do texto SQL.
- **`Validator`**: `Validator::make($dados, $regras, $rotulos)`, depois `fails()` e `errors()`. Regras:
  `required`, `accepted`, `min:N`, `max:N`, `email`, `int`, `in:a,b,c`, `date`, `past_or_today`, `confirmed`,
  `unique:tabela,coluna[,idIgnorado]`, `exists:tabela,coluna`, ou uma função anônima. Campo opcional vazio passa
  em todas as regras, exceto `required` e `accepted`.
- **`Session`**: `flash($tipo, $mensagem)` (aparece na próxima tela), `withInput()` (devolve o que foi digitado,
  sem senhas), `old()` e `erro()` (usados pelos formulários).
- **`Auth`**: `check()`, `id()`, `user()`, `isAdmin()`, `login($usuario)` (renova o ID da sessão e guarda só id,
  nome e perfil), `logout()`, `exigirLogin()`, `exigirAdmin()`.
- **`Csrf`**: `token()` (um token por sessão), `field()` (campo oculto para o formulário), `validate()` (confere o
  token enviado). O Router chama `validate()` em todo `POST`.
- **`Request`**: `str($campo)`, `only($comTrim, $semTrim)` (várias chaves de uma vez), `get($campo, $padrao)`
  (para a query string, limitada a 100 caracteres).
- **`Database`**: uma conexão PDO compartilhada, com exceções ligadas e emulação de prepared statements desligada.
- **`Config`**: `Config::get('db.host')`. Une `config.php` e `config.local.php`.
- **`Upload`**: `enviado($campo)`, `imagem($campo)` (valida tipo real com `finfo`, tamanho máximo de 2 MB e
  `getimagesize`; grava com nome aleatório), `apagar($caminho)`.
- **`Mailer`**: `enviarRecuperacao($para, $link, $minutos)`. Usa PHPMailer por SMTP com STARTTLS. Em caso de falha,
  registra no log e devolve `false`, sem mostrar detalhes ao usuário.

## 5. Controllers (`app/Controllers/`)

| Controller | Responsabilidade | Ações principais |
|---|---|---|
| `AuthController` | Login, cadastro e logout. Limita tentativas erradas (5 em 5 minutos, por sessão). | `formLogin`, `login`, `formCadastro`, `cadastro`, `logout` |
| `SenhaController` | Recuperação de senha por e-mail, com token de uso único e validade de 60 minutos. | `formEsqueci`, `enviar`, `formRedefinir`, `redefinir` |
| `PerfilController` | Dados da conta, troca de senha e exclusão da própria conta (pede a senha). | `mostrar`, `atualizar`, `alterarSenha`, `excluir` |
| `HomeController` | Tela inicial: tipos de deficiência, atividades recentes e metas com revisão vencida. | `index` |
| `AlunoController` | CRUD de alunos e a **ficha do aluno** (estudo de caso, metas, evolução com gráficos, sugestões, sessão, histórico). | `index`, `criar`, `salvar`, `mostrar`, `editar`, `atualizar`, `excluir` |
| `SessaoController` | Sessão de jogos na tela: monta a trilha, confere as respostas e grava um registro por meta. | `iniciar`, `mostrar`, `responder`, `dica`, `cancelar` |
| `MetaController` | Metas do PEI: área, habilidade, suporte inicial, professor responsável e data de revisão. | `criar`, `salvar`, `editar`, `atualizar`, `excluir` |
| `AtividadeController` | Catálogo de atividades: busca, filtros, paginação, imagem, vídeo e fonte. Toda atividade precisa de mini game e área. | `index`, `mostrar`, `criar`, `salvar`, `editar`, `atualizar`, `excluir` |
| `AcompanhamentoController` | Exclusão de um registro (por exemplo, sessão feita por engano). Não há cadastro nem edição manual: os registros vêm das sessões. | `excluir` |
| `TipoDeficienciaController` | CRUD dos tipos de deficiência (só administrador). | `index`, `criar`, `salvar`, `editar`, `atualizar`, `excluir` |

Regras de acesso importantes:
- Um professor só vê e altera **os próprios alunos**, as **metas** desses alunos e os **registros** desses alunos.
  Fora disso, a resposta é 404 (como se não existisse).
- Uma atividade só pode ser editada ou excluída pelo **autor** ou por um **administrador**. Outros recebem 403.

## 6. Models (`app/Models/`)

Cada model corresponde a uma tabela. Os métodos são estáticos e só contêm SQL. A regra de negócio fica no controller
ou no service.

| Model | Tabela | Métodos principais |
|---|---|---|
| `Usuario` | `usuarios` | `buscarPorEmail`, `buscar`, `criar`, `atualizarDados`, `atualizarSenha`, `excluir` |
| `RecuperacaoSenha` | `recuperacoes_senha` | `criar` (apaga os anteriores e grava o hash), `buscarValido`, `apagarDoUsuario` |
| `TipoDeficiencia` | `tipos_deficiencia` | `listar`, `comContagem`, `buscar`, `criar`, `atualizar`, `excluir`, `totalAtividades` |
| `Aluno` | `alunos` | `listarDoUsuario` (com busca), `buscarDoUsuario`, `contarDoUsuario`, `criar`, `atualizar`, `excluir` |
| `Meta` | `metas` | `listarDoAluno` (com o suporte atual e o número de registros), `buscarDoUsuario`, `buscarDoAluno`, `criar`, `atualizar`, `excluir`, `vencidasDoUsuario` |
| `Atividade` | `atividades` | `listar` (busca, tipo, área, paginação), `recentes`, `executaveis`, `tipos`, `sugeridasParaMeta`, `buscar`, `criar`, `atualizar`, `excluir` |
| `Acompanhamento` | `acompanhamentos` | `listarDoAluno`, `buscarDoUsuario`, `resumoDoAluno`, `registrarSessao`, `sessoesDaMeta`, `excluir`. Constante `NIVEIS` |

A constante `Acompanhamento::NIVEIS` tem os três níveis de suporte do AppPEI: `nao_realiza`, `com_ajuda` e
`sem_suporte`. Ela é usada nos formulários, nas tabelas e nos gráficos.

## 7. Services (`app/Services/`)

Os services não acessam o banco (exceto `SessaoAtividade::atividadeExecutavel`), por isso são testados
isoladamente em `tests/run.php`.

- **`SessaoAtividade`**: `montar($atividade, $rodadas, $maximo)` sorteia as rodadas de um mini game;
  `opcoesVisiveis($item, $dica)` deixa duas opções quando há dica; `classificar($acertos, $rodadas, $dicas)`
  devolve o nível de suporte; `agrupar($respostas)` soma as respostas por meta; `detalhar($grupo)` monta o texto
  "jogo: X de Y" do histórico. Regra de `classificar`:
  - nenhum acerto → `nao_realiza`;
  - todos os acertos e nenhuma dica → `sem_suporte`;
  - qualquer outro caso → `com_ajuda`.

  Esta classificação é uma **hipótese do protótipo**, não um instrumento validado.
- **`Evolucao`**: `resumir($sessoes)` calcula acerto médio, autonomia, uso de dica e a variação (ver seção 10).
- **`Figuras`**: catálogo de 14 figuras (palavra, letra inicial, sílabas) desenhadas em SVG; ícones dos aplicativos.
- **`Grafico`**: `linhaAcerto($sessoes, $evolucao, $id)` desenha a linha de acerto por sessão com as médias de cada
  metade; `simboloSuporte($nivel)` desenha ○ ◐ ● para os três níveis.

## 8. Views e layouts (`app/Views/`)

- A função `render('pasta/tela', $dados, $layout)` extrai as chaves do array `$dados` em variáveis, executa a view,
  guarda o HTML em um buffer e o coloca dentro do layout (no lugar de `$conteudo`).
- **`layouts/main.php`**: cabeçalho com o menu, busca global, mensagens (`flash`), resumo de erros de validação e
  rodapé. Usado nas telas logadas.
- **`layouts/auth.php`**: tela com o painel azul e o cartão branco. Usado no login, no cadastro e na recuperação.
- **`layouts/jogo.php`**: só as mensagens e o jogo, sem menu, busca nem botão Sair, para a criança não sair da
  sessão por engano. O professor sai pelo botão Cancelar da rodada, que pede confirmação.
- **Escape obrigatório:** toda saída de dado do usuário passa por `e()` (HTML). Use `public_url()` para arquivos e
  `url()` para links. Assim o sistema funciona em qualquer pasta do servidor.
- **Atenção:** as views são incluídas dentro de uma função. Por isso, dentro de uma view, use `\Core\Config::get()`
  com o nome completo. O `use` do helper não vale para as views.
- Erros de validação aparecem como lista no topo, com link para cada campo, e em cada campo com
  `aria-invalid` e `aria-describedby`.

## 9. Banco de dados

Oito tabelas: `usuarios`, `recuperacoes_senha`, `tipos_deficiencia`, `atividades`, `atividade_tipos` (ligação
muitos-para-muitos entre atividades e tipos), `alunos`, `metas` e `acompanhamentos`. O diagrama está em
`diagramas/01_der.png`.

Pontos principais do modelo:
- **`alunos.observacoes`** guarda o *estudo de caso*. O nome da coluna foi mantido para não exigir mudança de
  banco.
- **`metas`** é a habilidade priorizada para o aluno. Ela tem uma área (as quatro áreas do AppPEI), um suporte
  inicial, um professor responsável opcional e uma data de revisão.
- **`acompanhamentos`** guarda o resultado de uma sessão de mini games numa meta (`origem = 'sessao'`):
  `rodadas`, `acertos`, `erros`, `dicas`, `tempo_segundos` e, em `observacao`, o detalhe por jogo e por etapa.
  Os valores `manual` e `jogo` do ENUM `origem` só existem por compatibilidade com bancos antigos; o sistema
  atual não cria registros com eles.
- **`atividades.formato`** diz qual mini game a atividade usa e é **obrigatório no cadastro** (só entra no
  catálogo o que o sistema mede). Os 12 formatos estão na seção 10.
- **`atividades.itens`** é **opcional**, em JSON: `figuras` restringe as figuras sorteadas, `app` escolhe o
  aplicativo do tablet e `maximo` (2 a 10) muda o maior número dos jogos de matemática.
  Ex.: `{"figuras":["bola","bolo","gato","pato"]}` ou `{"maximo":10}`.
- **Dados iniciais:** 7 tipos de deficiência e 24 atividades, **6 por área**.
- **Exclusões:** excluir um aluno apaga metas e registros (CASCADE). Excluir uma meta ou uma atividade **mantém o
  histórico** e só remove o vínculo (SET NULL). Excluir um tipo de deficiência com atividades é bloqueado pelo
  sistema (RESTRICT).
- O script SQL **apaga e recria** o banco. Não use em um banco com dados que você queira manter.

## 10. Fluxos principais, passo a passo

**Login.** `AuthController::login()` valida e-mail e senha; busca o usuário pelo e-mail (`Usuario::buscarPorEmail`);
confere a senha com `password_verify`; se estiver certa, chama `Auth::login()` (renova a sessão e guarda id, nome
e perfil). Se estiver errada, conta a tentativa e mostra a mesma mensagem para e-mail ou senha errados.

**Recuperação de senha.** `SenhaController::enviar()` cria um token aleatório de 64 caracteres. No banco fica só o
hash SHA-256 (`RecuperacaoSenha::criar`). O e-mail leva ao link `redefinir-senha/{token}`. Ao usar o link,
`redefinir()` confere o hash, troca a senha e **apaga todos os tokens do usuário**. A resposta do formulário é
sempre a mesma, exista o e-mail ou não.

**Cadastro de atividade com imagem.** `AtividadeController::salvar()` valida os campos e, se houver imagem,
chama `Upload::imagem()`. Se qualquer validação falhar, a imagem recém-enviada é apagada (`Upload::apagar`).
Ao editar, a imagem antiga só é apagada depois que a nova for aceita.

**Metas do PEI.** A ficha do aluno (`AlunoController::mostrar`) lista as metas com o suporte atual (o do registro
mais recente de cada meta) e, para cada meta, as atividades **da mesma área que ainda não foram aplicadas** a esse
aluno e compatíveis com o tipo de deficiência dele (`Atividade::sugeridasParaMeta`). Se a data de revisão não for
informada, `MetaController` usa hoje + 90 dias (`revisao_dias`).

**Sessão de jogos na tela.** É o caminho em que o próprio aluno responde e o resultado é medido sem digitação
do professor. Em resumo:
1. Na ficha do aluno, o professor marca um ou mais jogos e envia para `/alunos/{id}/sessao?atividades[]=...`
   (o botão "Fazer na tela", nas sugestões, envia um jogo só, com a meta indicada).
2. `iniciar()` descobre a **meta** de cada jogo pela área da atividade (a primeira meta do aluno naquela área,
   pela regra `SessaoAtividade::metaPorArea()`, ou a indicada em `?meta=`), sorteia as rodadas com `SessaoAtividade::montar()` e etiqueta cada rodada com
   `meta_id`, `atividade_id` e `jogo`. Jogo cuja área não tem meta no aluno fica de fora, com aviso. O estado
   fica em `$_SESSION['_sessao']`.
3. `mostrar()` apresenta uma rodada por vez: um enunciado, uma **cena desenhada** e botões grandes de toque
   (um toque já responde). `dica()` reduz as opções a duas, mantendo a correta, uma vez por rodada; a opção
   errada que fica é sorteada ao montar a rodada e as duas mantêm a ordem da tela, para que a posição não revele
   a certa. Pelo mesmo motivo, nos jogos de numerais a posição do número certo entre as três opções também é
   sorteada. A dica só existe em rodadas com mais de duas
   opções (`SessaoAtividade::temDica`): no jogo de comparar, que já tem duas, o botão não aparece. `responder()` confere no servidor e guarda
   a resposta em `respostas` (acerto, dica, segundos), com um retorno visual de acerto na rodada seguinte.
4. Ao terminar, `concluir()` usa `SessaoAtividade::agrupar()` para somar as respostas **por meta**, classifica o
   nível (`SessaoAtividade::classificar`) e grava **um registro por meta** com `origem = 'sessao'`. O detalhamento
   por jogo e por etapa vai para `observacao` (`SessaoAtividade::detalhar()`).
5. `cancelar()` encerra sem gravar nada.

**Os mini games.** Cada rodada traz `enunciado`, `cena` (o desenho), `opcoes` (os botões) e `correta`
(o índice da opção certa). As figuras são desenhadas em SVG por `App\Services\Figuras`, sem arquivo de
imagem nem internet, e cada uma tem silhueta própria, para **nenhuma informação depender de cor**.

| Formato | O que aparece na tela | Área típica |
|---|---|---|
| `quantidade` | figuras repetidas para contar; escolhe o numeral | Matemática |
| `comparacao` | dois grupos de figuras; escolhe o que tem mais (ou menos) | Matemática |
| `sequencia` | sequência de numerais com uma lacuna | Matemática |
| `soma` | dois grupos de figuras iguais; escolhe o total | Matemática |
| `figura_palavra` | uma figura e três palavras | Leitura e Escrita |
| `palavra_figura` | uma palavra escrita e três figuras | Leitura e Escrita |
| `figura_letra` | uma figura e três letras iniciais | Leitura e Escrita |
| `silaba_inicial` | uma figura e três sílabas iniciais | Leitura e Escrita |
| `silaba` | figura e o nome com uma sílaba faltando | Leitura e Escrita |
| `ouvir_figura` | botão "Ouvir a palavra" (voz do navegador, a palavra não aparece escrita) e três figuras | Comunicação oral |
| `aplicativo` | **tablet simulado**: abrir o aplicativo, usar o jogo dentro dele e fechar | Alfabetização digital |
| `janela` | aplicativo aberto; toca no botão pedido (Fechar, Minimizar ou Voltar) | Alfabetização digital |

No formato `aplicativo`, as três etapas são medidas à parte: **abrir** o aplicativo (com o tempo até o
toque), **usar** o jogo e **fechar**.

O professor escolhe o mini game no próprio cadastro da atividade (campo "Mini game na tela", obrigatório).

**Evolução e variação.** `App\Services\Evolucao::resumir()` recebe as sessões de uma meta em ordem cronológica
(`Acompanhamento::sessoesDaMeta`) e calcula:
- **acerto médio**: total de acertos ÷ total de rodadas;
- **autonomia**: % de sessões classificadas como `sem_suporte`;
- **com dica**: % de sessões em que houve pelo menos uma dica;
- **variação** (a partir de 6 sessões): média da taxa de acerto da 2ª metade das sessões menos a da 1ª metade,
  em pontos percentuais. Ex.: 1ª metade 46,7%, 2ª metade 86,7% → variação +40.

**Gráficos.** Na ficha do aluno, cada meta com sessões ganha um cartão com dois gráficos (`App\Services\Grafico`):
a **linha de acerto por sessão**, com as médias de cada metade tracejadas (a distância entre elas é a variação), e
a **linha do tempo do suporte** (○ não realiza, ◐ com ajuda, ● sem suporte; a forma muda, não só a cor).

**Exclusão de registro.** O professor não cria nem edita registros. Ele só pode excluir um (por exemplo, uma
sessão feita por engano); o registro deixa de contar na evolução.

**Revisão vencida.** `Meta::vencidasDoUsuario()` conta as metas com `revisar_em` até hoje. A tela inicial mostra o
aviso com link para a lista de alunos.

## 11. Segurança: ameaça, mecanismo e onde está

| Ameaça | Mecanismo | Onde está |
|---|---|---|
| Senha vazada do banco | `password_hash` / `password_verify` | `AuthController`, `PerfilController`, `SenhaController` |
| Injeção de SQL | PDO com prepared statements (valores como parâmetros) | `core/Model.php`, todos os models |
| XSS (script injetado) | `e()` em toda saída de dado do usuário | `core/helpers.php` e todas as views |
| CSRF (formulário forjado) | Token por sessão em todo `POST` | `core/Csrf.php`, `core/Router.php` |
| Acesso sem login | Middlewares `auth`, `guest`, `admin` | `config/routes.php`, `core/Router.php`, `core/Auth.php` |
| Ver dados de outro professor | Consultas filtradas pelo `usuario_id` da sessão (404 se não for dele) | `Aluno`, `Meta`, `Acompanhamento` |
| Editar atividade de outro autor | Confere autor ou administrador (403) | `AtividadeController::carregarEditavel` |
| Força bruta no login | 5 tentativas erradas bloqueiam por 5 minutos | `AuthController::login` (**por sessão**, ver limitações) |
| Sequestro de sessão | Cookie `HttpOnly` e `SameSite`; ID renovado no login | `core/Session.php`, `core/Auth.php` |
| Token de recuperação roubado do banco | Só o hash é guardado; token de uso único; validade de 60 min | `RecuperacaoSenha`, `SenhaController` |
| Vazamento de detalhes de erro | Erro vai para o log; a tela mostra mensagem genérica (exceto com `debug` ligado) | `core/bootstrap.php`, `core/Database.php` |
| Upload de arquivo malicioso | Tipo real (`finfo`), tamanho, nome aleatório, pasta sem execução de PHP | `core/Upload.php`, `public/uploads/.htaccess` |
| Acesso a arquivos internos | Só `public/` é servido; resto do projeto fica fora do alcance | `.htaccess` da raiz, `public/.htaccess` |
| Segredos no GitHub | `config.local.php` no `.gitignore` | `.gitignore`, `config/config.local.example.php` |

## 12. Acessibilidade (o que existe e o que **não** foi auditado)

Implementado seguindo práticas inspiradas nas diretrizes WCAG 2.1: idioma `pt-BR`; rótulos em todos os campos;
link "ir para o conteúdo"; foco visível no teclado; resumo de erros anunciado com `role="alert"`; tabelas com
`caption` e `scope`; texto alternativo obrigatório em imagens; campo de legendas e transcrição para vídeos;
`prefers-reduced-motion`; alvos de toque de 44 px; nos mini games e nos gráficos **nenhuma informação depende só
de cor** (figuras com silhueta própria, símbolos de suporte com formas diferentes) e os gráficos têm descrição em
texto (`role="img"`).

**Não foi feita auditoria** com leitor de tela, nem com a ferramenta Lighthouse ou com usuários. Por isso, o texto correto é
"segue práticas inspiradas nas diretrizes WCAG 2.1", e não "atende à WCAG".

## 13. Como adicionar um CRUD novo (receita, usando "Meta" como exemplo)

1. **Banco:** crie a tabela no `database/banco_acessibilidade.sql` com chave primária, chave estrangeira para o
   dono (por exemplo, `aluno_id`) e `ON DELETE` adequado.
2. **Model:** crie `app/Models/NomeDoModel.php`, estendendo `Core\Model`, com métodos estáticos que usam
   `self::fetchAll`, `self::fetchOne`, `self::execute` e `self::lastId()`. Use `?` para cada valor.
3. **Controller:** crie `app/Controllers/NomeController.php`, estendendo `Core\Controller`. Cada ação:
   - confere o dono do registro (`Aluno::buscarDoUsuario` ou o equivalente) e devolve 404 se não for dele;
   - lê os campos com `Request::only([...])`;
   - valida com `Validator::make(...)` e, se houver erro, chama `voltarComErros(...)`;
   - grava, chama `Session::flash(...)` e `redirect(...)`.
4. **Rotas:** acrescente em `config/routes.php` com o middleware certo (`auth`, `admin`...). **Declare as rotas
   fixas antes das com `{id}`.**
5. **Views:** crie uma pasta em `app/Views/` com a tela de lista e o formulário. Use `old()`, `campo_erro()` e
   `aria_erro()` nos campos, `csrf_field()` em todo formulário `POST` e `e()` em toda saída.
6. **Testes:** se criou regras de negócio puras, acrescente testes em `tests/run.php` e rode `php tests/run.php`.
7. **Conferência:** confira o nome de cada método chamado, o número de `?` e de parâmetros em cada SQL e se cada
   rota aponta para um método que existe.

## 14. Testes e verificações

- **Testes automatizados:** `php tests/run.php` (na pasta do projeto): **63 verificações**. Cobrem a
  classificação do nível de suporte, as validações do servidor, a montagem de cada um dos 12 mini games
  (conferida em vários sorteios por regra, já que as rodadas são aleatórias), a dica, o cadastro do formato,
  a meta de destino de cada área, a separação dos resultados por meta, a evolução e os gráficos. **Não cobrem** telas nem banco.
- **Diagnóstico do banco:** `php tests/diagnostico.php` confere conexão, tabelas, colunas e dados iniciais.
- **Verificações feitas:** sintaxe de todos os arquivos PHP; cada rota aponta para um método que
  existe; cada classe e método estático chamado existe; o script do banco importa no MySQL do XAMPP com 24
  atividades (6 por área); teste de ponta a ponta no banco (alunos, metas, 6 sessões por área, evolução,
  sugestões por tipo de deficiência, cadastro de atividade); a ficha do aluno renderizada e conferida em print.
- **Roteiro manual (obrigatório antes de entregar):**
  - [ ] Importar o SQL (7 tipos e 24 atividades).
  - [ ] Cadastro (com erros de validação e com dados corretos), login (certo e errado), 5 tentativas de bloqueio.
  - [ ] Recuperação de senha: o e-mail chega, o link redefine a senha, o mesmo link não funciona de novo.
  - [ ] Abrir `/alunos` sem login: deve ir para o login.
  - [ ] Dois professores: um não consegue abrir o aluno, a meta ou o registro do outro (404).
  - [ ] Pedir dica e cancelar uma sessão (não pode gravar nada).
  - [ ] Durante a sessão, conferir que o menu, a busca e o botão Sair não aparecem.
  - [ ] Abrir cada um dos 12 mini games e conferir se a cena desenhada aparece e se os botões respondem ao toque.
  - [ ] No "Ouvir a palavra", conferir se a voz fala a palavra (Chrome ou Edge).
  - [ ] Criar metas de matemática **e** de alfabetização digital; marcar dois jogos em "Sessão de jogos na tela",
        responder tudo e conferir que nasceram **dois** registros, um por meta, com a observação detalhando cada jogo.
  - [ ] Na lista de jogos da ficha, conferir a meta indicada ao lado de cada jogo (com duas metas na mesma área,
        a primeira cadastrada) e que os jogos de áreas sem meta ficam desabilitados.
  - [ ] No jogo de abrir e fechar aplicativo, conferir na observação as três etapas e o tempo até abrir.
  - [ ] Repetir a sessão seis vezes na mesma meta e conferir a variação e os gráficos em "Evolução nas sessões na tela".
  - [ ] Excluir um registro do histórico e conferir que ele sai da evolução.
  - [ ] Abrir a sessão num tablet (ou na largura de tablet do navegador) e conferir se os botões são tocáveis.
  - [ ] Criar atividade com imagem e fonte; a imagem aparece; excluir a atividade sem perder o histórico do aluno.
  - [ ] Navegar só com o teclado; testar com NVDA; rodar o Lighthouse.

## 15. Limitações e dívida técnica

**Medição e conteúdo pedagógico**
1. **Parâmetros não validados**: a regra de classificação em três níveis, as 5 rodadas, as quantidades até 5 ou 10,
   o mínimo de 6 sessões para a variação e os 90 dias de revisão são decisões do protótipo e precisam ser validados
   com professores.
2. **Conteúdo das atividades**: as 24 atividades têm fonte "Proposta do projeto UNIA" e não foram validadas por
   profissional da educação especial.
3. **Comunicação Oral mede só compreensão.** Produção oral depende de captar a voz da criança, o que o sistema não
   faz. Nessa área, a criança ouve a palavra e toca na figura (linguagem receptiva). A voz depende do navegador ter
   voz em português (Chrome e Edge têm).
4. **As figuras são desenhos simples** e não foram testadas com crianças. Se um desenho não for reconhecido, a
   medição mede o desenho, não a habilidade.
5. **A variação mistura os jogos da meta.** Se o professor trocar um jogo fácil por um difícil, a variação reflete
   a troca. Para comparar progresso, o mesmo jogo deve ser repetido.
6. **Uma sessão com vários jogos grava na primeira meta de cada área.** A ficha mostra essa meta ao lado de cada
   jogo e desabilita os jogos de áreas sem meta. Se o aluno tem duas metas na mesma área, o botão "Fazer na tela"
   da meta desejada direciona o resultado.
7. **Sugestões de atividades** são uma regra de ordenação (mesma área, ainda não aplicada), não uma recomendação
   pedagógica.

**Acessibilidade**
8. **Nenhum mini game serve para o aluno com deficiência visual**: todos dependem de ver as figuras. Escolher a
   figura certa depende de enxergar o desenho; nomear a figura no rótulo entregaria a resposta. Por isso as figuras
   são decorativas (`aria-hidden`) e os botões têm rótulo neutro ("Figura 1").
9. **Acessibilidade não auditada** com leitor de tela, Lighthouse ou usuários.

**Técnicas**
10. **Telas logadas sem teste manual completo**: o roteiro da seção 14 precisa ser feito no navegador.
11. **Limite de login por sessão**, não por endereço. Quem apaga o cookie recomeça a contagem. Um limite por IP
    exigiria uma tabela própria.
12. **Sem limite de envio** na recuperação de senha; cabe um limite por e-mail.
13. **Models com métodos estáticos**: simples de ler, mas difíceis de testar com banco falso. O caminho seria
    injetar a conexão e separar repositórios.
14. **Sem migrações**: o script SQL recria o banco; não há histórico de versões do banco.
15. **Estado da sessão de jogos em `$_SESSION`**: se o navegador fechar, a sessão se perde e nada é gravado.
16. **Dados de crianças**: há aceite e consentimento, mas as observações não são criptografadas no banco. Em
    produção, seria preciso criptografia e uma política de retenção.
17. **Sem testes de tela automatizados.**

## 16. Glossário

- **MVC**: separação entre dados (Model), apresentação (View) e fluxo da requisição (Controller).
- **Front controller**: um único arquivo (`public/index.php`) recebe todas as requisições.
- **Roteamento**: transformar uma URL em uma ação de controller.
- **Middleware**: verificação feita antes da ação (login, perfil).
- **CSRF**: ataque em que um site força o navegador do usuário a enviar um formulário. Combatido com um token.
- **Prepared statement**: consulta em que o SQL e os valores são enviados separadamente, o que impede a injeção.
- **Escape (`e()`)**: transforma `<` em `&lt;` etc., para o navegador mostrar o texto em vez de executá-lo.
- **Flash**: mensagem guardada para a próxima tela (ex.: "Aluno cadastrado").
- **ORM**: biblioteca que mapeia tabelas em objetos. Este projeto **não usa ORM**: o SQL é escrito à mão nos models.
- **PEI**: Plano Educacional Individualizado. **AEE**: Atendimento Educacional Especializado.
- **AppPEI**: sistema de PEI em planilha da UERJ, usado aqui como referência para as áreas e os níveis.

---

## Anexo A: tabela de rotas

Em caso de dúvida, o arquivo `config/routes.php` é a fonte.


| Método | URL | Controller | Ação | Quem acessa |
|---|---|---|---|---|
| GET | `/login` | Auth | `formLogin()` | visitante |
| POST | `/login` | Auth | `login()` | visitante |
| GET | `/cadastro` | Auth | `formCadastro()` | visitante |
| POST | `/cadastro` | Auth | `cadastro()` | visitante |
| GET | `/esqueci-senha` | Senha | `formEsqueci()` | visitante |
| POST | `/esqueci-senha` | Senha | `enviar()` | visitante |
| GET | `/redefinir-senha/{token}` | Senha | `formRedefinir()` | visitante |
| POST | `/redefinir-senha/{token}` | Senha | `redefinir()` | visitante |
| POST | `/logout` | Auth | `logout()` | logado |
| GET | `/` | Home | `index()` | logado |
| GET | `/perfil` | Perfil | `mostrar()` | logado |
| POST | `/perfil` | Perfil | `atualizar()` | logado |
| POST | `/perfil/senha` | Perfil | `alterarSenha()` | logado |
| POST | `/perfil/excluir` | Perfil | `excluir()` | logado |
| GET | `/atividades` | Atividade | `index()` | logado |
| GET | `/atividades/nova` | Atividade | `criar()` | logado |
| POST | `/atividades` | Atividade | `salvar()` | logado |
| GET | `/atividades/{id}` | Atividade | `mostrar()` | logado |
| GET | `/atividades/{id}/editar` | Atividade | `editar()` | logado |
| POST | `/atividades/{id}/editar` | Atividade | `atualizar()` | logado |
| POST | `/atividades/{id}/excluir` | Atividade | `excluir()` | logado |
| GET | `/alunos` | Aluno | `index()` | logado |
| GET | `/alunos/novo` | Aluno | `criar()` | logado |
| POST | `/alunos` | Aluno | `salvar()` | logado |
| GET | `/alunos/{id}` | Aluno | `mostrar()` | logado |
| GET | `/alunos/{id}/editar` | Aluno | `editar()` | logado |
| POST | `/alunos/{id}/editar` | Aluno | `atualizar()` | logado |
| POST | `/alunos/{id}/excluir` | Aluno | `excluir()` | logado |
| GET | `/alunos/{id}/metas/nova` | Meta | `criar()` | logado |
| POST | `/alunos/{id}/metas` | Meta | `salvar()` | logado |
| GET | `/metas/{id}/editar` | Meta | `editar()` | logado |
| POST | `/metas/{id}/editar` | Meta | `atualizar()` | logado |
| POST | `/metas/{id}/excluir` | Meta | `excluir()` | logado |
| POST | `/acompanhamentos/{id}/excluir` | Acompanhamento | `excluir()` | logado |
| GET | `/alunos/{id}/sessao` | Sessao | `iniciar()` | logado |
| GET | `/sessao` | Sessao | `mostrar()` | logado |
| POST | `/sessao/responder` | Sessao | `responder()` | logado |
| POST | `/sessao/dica` | Sessao | `dica()` | logado |
| POST | `/sessao/cancelar` | Sessao | `cancelar()` | logado |
| GET | `/tipos` | TipoDeficiencia | `index()` | administrador |
| GET | `/tipos/novo` | TipoDeficiencia | `criar()` | administrador |
| POST | `/tipos` | TipoDeficiencia | `salvar()` | administrador |
| GET | `/tipos/{id}/editar` | TipoDeficiencia | `editar()` | administrador |
| POST | `/tipos/{id}/editar` | TipoDeficiencia | `atualizar()` | administrador |
| POST | `/tipos/{id}/excluir` | TipoDeficiencia | `excluir()` | administrador |

## Anexo B: diagramas

Os arquivos estão em `diagramas/`: `01_der.png` (modelo lógico do banco, notação pé de galinha), `02_classes_dominio.png` (diagrama de classes da UML), `03_arquitetura_mvc.png` (diagrama de pacotes da UML) e `04_sequencia_sessao.png` (diagrama de sequência da UML). Cada um tem a versão vetorial `.svg` na mesma pasta.
