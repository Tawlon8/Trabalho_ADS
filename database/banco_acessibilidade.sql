-- =====================================================================
-- UNIA - Banco de dados (versão 3)
--
-- ATENÇÃO: este script APAGA o banco "banco_acessibilidade" (se existir) e o recria.
-- Use para instalar ou reinstalar o sistema. Não rode em um banco com dados que queira manter.
--
-- Como importar: phpMyAdmin -> Importar -> escolha este arquivo -> Executar.
-- Ou no terminal: mysql -u root -p < database/banco_acessibilidade.sql
--
-- Dados iniciais: 7 tipos de deficiência e 24 atividades (seis por área, nos 12 mini games). O catálogo só tem
-- atividades que o próprio sistema mede (acertos, dicas e tempo na tela). Atividades com material físico foram retiradas.
-- =====================================================================
DROP DATABASE IF EXISTS banco_acessibilidade;
CREATE DATABASE banco_acessibilidade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE banco_acessibilidade;

-- Professores e administradores. A senha é o hash gerado por password_hash().
CREATE TABLE usuarios (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(100) NOT NULL,
    email     VARCHAR(100) NOT NULL UNIQUE,
    senha     VARCHAR(255) NOT NULL,
    perfil    ENUM('professor','admin') NOT NULL DEFAULT 'professor',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tokens de recuperação de senha. Em "token" fica o HASH SHA-256 do token enviado por e-mail.
CREATE TABLE recuperacoes_senha (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    token      CHAR(64) NOT NULL UNIQUE,
    expira_em  DATETIME NOT NULL,
    criado_em  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (usuario_id),
    CONSTRAINT fk_recuperacao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Tipos de deficiência (categorias da tela inicial).
CREATE TABLE tipos_deficiencia (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(50)  NOT NULL UNIQUE,
    descricao VARCHAR(255) NULL,
    icone     VARCHAR(40)  NOT NULL DEFAULT 'fa-puzzle-piece'
) ENGINE=InnoDB;

-- Atividades do catálogo. "area" usa as áreas do AppPEI; "fonte" é obrigatória.
-- Uma atividade pode atender vários tipos de deficiência: os tipos ficam em atividade_tipos.
CREATE TABLE atividades (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    titulo              VARCHAR(100) NOT NULL,
    descricao           TEXT NOT NULL,
    objetivo            VARCHAR(255) NULL,
    materiais           VARCHAR(255) NULL,
    area                ENUM('matematica','comunicacao_oral','leitura_escrita','alfabetizacao_digital') NULL,
    imagem              VARCHAR(255) NULL,
    imagem_alt          VARCHAR(200) NULL,
    video               VARCHAR(255) NULL,
    legenda             VARCHAR(255) NULL,
    transcricao         TEXT NULL,
    fonte               VARCHAR(255) NOT NULL,
    formato             ENUM('quantidade','comparacao','sequencia','figura_palavra','palavra_figura','figura_letra','silaba','aplicativo','soma','silaba_inicial','ouvir_figura','janela') NULL,   -- atividade executável na tela
    itens               TEXT NULL,                           -- JSON opcional: restringe figuras ou escolhe o app
    usuario_id          INT NULL,
    criado_em           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (area),
    CONSTRAINT fk_ativ_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Ligação entre atividades e tipos de deficiência (muitos para muitos).
-- Excluir uma atividade remove só os seus vínculos. Excluir um tipo com vínculos é bloqueado (RESTRICT).
CREATE TABLE atividade_tipos (
    atividade_id        INT NOT NULL,
    tipo_deficiencia_id INT NOT NULL,
    PRIMARY KEY (atividade_id, tipo_deficiencia_id),
    INDEX (tipo_deficiencia_id),
    CONSTRAINT fk_at_atividade FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE CASCADE,
    CONSTRAINT fk_at_tipo      FOREIGN KEY (tipo_deficiencia_id) REFERENCES tipos_deficiencia(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Alunos de cada professor. "observacoes" é o estudo de caso (observações pedagógicas).
CREATE TABLE alunos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT NOT NULL,
    nome                VARCHAR(100) NOT NULL,
    data_nascimento     DATE NULL,
    tipo_deficiencia_id INT NULL,
    observacoes         TEXT NULL,
    consentimento       TINYINT(1) NOT NULL DEFAULT 0,   -- autorização do responsável (LGPD)
    criado_em           TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (usuario_id),
    CONSTRAINT fk_aluno_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_aluno_tipo    FOREIGN KEY (tipo_deficiencia_id) REFERENCES tipos_deficiencia(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Metas do PEI: habilidade priorizada para o aluno, em uma área, com o suporte inicial.
CREATE TABLE metas (
    id                    INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id              INT NOT NULL,
    area                  ENUM('matematica','comunicacao_oral','leitura_escrita','alfabetizacao_digital') NOT NULL,
    descricao             VARCHAR(255) NOT NULL,
    nivel_inicial         ENUM('nao_realiza','com_ajuda','sem_suporte') NOT NULL,
    professor_responsavel VARCHAR(100) NULL,
    revisar_em            DATE NULL,
    criado_em             TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (aluno_id),
    CONSTRAINT fk_meta_aluno FOREIGN KEY (aluno_id) REFERENCES alunos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Registros de acompanhamento: o resultado de uma sessão de mini games numa meta (origem 'sessao').
-- 'manual' e 'jogo' ficam no ENUM só por compatibilidade com bancos de versões anteriores; o sistema não os grava.
-- Ao excluir uma meta ou uma atividade, o histórico é mantido e o vínculo fica vazio (SET NULL).
CREATE TABLE acompanhamentos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id        INT NOT NULL,
    meta_id         INT NULL,
    atividade_id    INT NULL,
    origem          ENUM('manual','jogo','sessao') NOT NULL DEFAULT 'sessao',
    data_aplicacao  DATE NOT NULL,
    desempenho      ENUM('nao_realiza','com_ajuda','sem_suporte') NOT NULL,
    rodadas         TINYINT UNSIGNED NULL,   -- sessão
    acertos         TINYINT UNSIGNED NULL,   -- sessão
    dicas           TINYINT UNSIGNED NULL,   -- sessão
    erros           TINYINT UNSIGNED NULL,   -- sessão
    tempo_segundos  INT UNSIGNED NULL,       -- sessão
    observacao      TEXT NULL,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (aluno_id),
    INDEX (meta_id),
    CONSTRAINT fk_acomp_aluno     FOREIGN KEY (aluno_id)     REFERENCES alunos(id)     ON DELETE CASCADE,
    CONSTRAINT fk_acomp_meta      FOREIGN KEY (meta_id)      REFERENCES metas(id)      ON DELETE SET NULL,
    CONSTRAINT fk_acomp_atividade FOREIGN KEY (atividade_id) REFERENCES atividades(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Dados iniciais
-- ---------------------------------------------------------------------
INSERT INTO tipos_deficiencia (nome, descricao, icone) VALUES
    ('Motora', NULL, 'fa-dumbbell'),
    ('Intelectual', NULL, 'fa-brain'),
    ('Auditiva', NULL, 'fa-deaf'),
    ('Visual', NULL, 'fa-low-vision'),
    ('Transtorno do Espectro Autista (TEA)', NULL, 'fa-puzzle-piece'),
    ('Múltipla', NULL, 'fa-users'),
    ('Outra / Não sei dizer', NULL, 'fa-question');

-- ---------------------------------------------------------------------
-- Mini games: seis atividades por área. O aluno responde na tela e o sistema mede
-- acertos, erros, dicas e tempo. As rodadas são sorteadas a partir do catálogo de figuras
-- (App\Services\Figuras). 'itens' é OPCIONAL, em JSON: "figuras" restringe as figuras,
-- "app" escolhe o aplicativo e "maximo" (2 a 10) muda o maior número dos jogos de matemática.
-- Propostas deste projeto, não validadas por profissional da educação especial.
-- ---------------------------------------------------------------------
INSERT INTO atividades (titulo, descricao, objetivo, materiais, area, fonte, formato, itens) VALUES
 -- Alfabetização Matemática
 ('Contar e escolher o numeral (até 5)',
  'Na tela aparecem algumas figuras iguais. A criança conta e toca no numeral correspondente. Quantidades de 1 a 5.',
  'Relacionar quantidade e numeral.', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'quantidade', NULL),
 ('Contar e escolher o numeral (até 10)',
  'Na tela aparecem algumas figuras iguais. A criança conta e toca no numeral correspondente. Quantidades de 1 a 10.',
  'Relacionar quantidade e numeral até 10.', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'quantidade', '{"maximo":10}'),
 ('Comparar quantidades: mais e menos (até 5)',
  'Na tela aparecem dois grupos de figuras. A criança toca no grupo que tem mais (ou menos) figuras, conforme o pedido da rodada.',
  'Comparar quantidades.', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'comparacao', NULL),
 ('Comparar quantidades: mais e menos (até 10)',
  'Na tela aparecem dois grupos de até 10 figuras. A criança toca no grupo que tem mais (ou menos) figuras, conforme o pedido da rodada.',
  'Comparar quantidades maiores.', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'comparacao', '{"maximo":10}'),
 ('Completar a sequência de numerais (até 10)',
  'Na tela aparece uma sequência de numerais com um deles faltando. A criança toca no numeral que completa a sequência.',
  'Reconhecer a ordem dos numerais.', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'sequencia', '{"maximo":10}'),
 ('Juntar quantidades (até 5)',
  'Na tela aparecem dois grupos de figuras iguais. A criança junta os grupos e toca no numeral do total.',
  'Juntar duas quantidades (ideia de adição).', 'Computador ou tablet', 'matematica', 'Proposta do projeto UNIA', 'soma', NULL),

 -- Leitura e Escrita
 ('Associar figura e palavra',
  'Na tela aparece uma figura e três palavras. A criança toca na palavra que nomeia a figura.',
  'Relacionar palavra escrita e figura.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'figura_palavra', NULL),
 ('Associar figura e palavra: palavras parecidas',
  'Igual a "Associar figura e palavra", mas com palavras que mudam em uma letra (BOLA, BOLO, GATO, PATO).',
  'Ler com atenção a cada letra da palavra.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'figura_palavra',
  '{"figuras":["bola","bolo","gato","pato"]}'),
 ('Ler a palavra e escolher a figura',
  'Na tela aparece uma palavra escrita e três figuras. A criança toca na figura correspondente.',
  'Compreender a palavra escrita.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'palavra_figura', NULL),
 ('Identificar a letra inicial da figura',
  'Na tela aparece uma figura e três letras. A criança toca na letra com que o nome da figura começa.',
  'Reconhecer a letra inicial de palavras conhecidas.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'figura_letra', NULL),
 ('Identificar a sílaba inicial da figura',
  'Na tela aparece uma figura e três sílabas. A criança toca na sílaba com que o nome da figura começa.',
  'Reconhecer a sílaba inicial de palavras conhecidas.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'silaba_inicial', NULL),
 ('Completar a sílaba que falta',
  'Na tela aparece a figura e o nome dela com uma sílaba faltando. A criança toca na sílaba que completa a palavra.',
  'Associar sílabas a palavras conhecidas.', 'Computador ou tablet', 'leitura_escrita', 'Proposta do projeto UNIA', 'silaba', NULL),

 -- Comunicação Oral (compreensão do que se ouve; a palavra é falada pela voz do navegador)
 ('Ouvir a palavra e escolher a figura',
  'A criança toca em "Ouvir a palavra", escuta e toca na figura correspondente entre três. A palavra não aparece escrita.',
  'Compreender palavras ouvidas.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura', NULL),
 ('Ouvir e escolher: animais',
  'Igual a "Ouvir a palavra e escolher a figura", só com animais.',
  'Compreender nomes de animais ouvidos.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura',
  '{"figuras":["gato","pato","peixe"]}'),
 ('Ouvir e escolher: céu e natureza',
  'Igual a "Ouvir a palavra e escolher a figura", só com sol, lua, flor e estrela.',
  'Compreender nomes da natureza ouvidos.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura',
  '{"figuras":["sol","lua","flor","estrela"]}'),
 ('Ouvir e escolher: objetos',
  'Igual a "Ouvir a palavra e escolher a figura", só com objetos do dia a dia.',
  'Compreender nomes de objetos ouvidos.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura',
  '{"figuras":["bola","casa","carro","livro","copo"]}'),
 ('Ouvir e escolher: coisas da mesa',
  'Igual a "Ouvir a palavra e escolher a figura", só com maçã, bolo e copo.',
  'Compreender nomes de alimentos e utensílios ouvidos.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura',
  '{"figuras":["maca","bolo","copo"]}'),
 ('Ouvir e escolher: palavras parecidas',
  'Igual a "Ouvir a palavra e escolher a figura", com palavras de som parecido (BOLA, BOLO, GATO, PATO).',
  'Diferenciar palavras de som parecido.', 'Computador ou tablet com som', 'comunicacao_oral', 'Proposta do projeto UNIA', 'ouvir_figura',
  '{"figuras":["bola","bolo","gato","pato"]}'),

 -- Alfabetização Digital
 ('Abrir, usar e fechar: Palavras e figuras',
  'Tablet simulado na tela. A criança toca no ícone do aplicativo pedido para abrir, usa o jogo de figuras e palavras dentro dele e depois toca em Fechar. As três etapas (abrir, usar e fechar) são medidas separadamente, com o tempo até abrir.',
  'Usar o toque para abrir e fechar aplicativos.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'aplicativo',
  '{"app":"Palavras e figuras"}'),
 ('Abrir, usar e fechar: Calendário',
  'Igual a "Abrir, usar e fechar: Palavras e figuras", mas o aplicativo pedido é o Calendário.',
  'Encontrar e abrir o aplicativo pedido.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'aplicativo',
  '{"app":"Calendário"}'),
 ('Abrir, usar e fechar: Relógio',
  'Igual a "Abrir, usar e fechar: Palavras e figuras", mas o aplicativo pedido é o Relógio.',
  'Encontrar e abrir o aplicativo pedido.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'aplicativo',
  '{"app":"Relógio"}'),
 ('Abrir, usar e fechar: Fotos',
  'Igual a "Abrir, usar e fechar: Palavras e figuras", mas o aplicativo pedido é o Fotos.',
  'Encontrar e abrir o aplicativo pedido.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'aplicativo',
  '{"app":"Fotos"}'),
 ('Abrir, usar e fechar: Música',
  'Igual a "Abrir, usar e fechar: Palavras e figuras", mas o aplicativo pedido é o Música.',
  'Encontrar e abrir o aplicativo pedido.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'aplicativo',
  '{"app":"Música"}'),
 ('Botões da janela: fechar, minimizar e voltar',
  'Um aplicativo aberto no tablet simulado. A cada rodada a criança toca no botão pedido: Fechar, Minimizar ou Voltar.',
  'Reconhecer os botões de controle de um aplicativo.', 'Computador ou tablet', 'alfabetizacao_digital', 'Proposta do projeto UNIA', 'janela', NULL);

-- Todos os mini games dependem de enxergar as figuras, por isso não são ligados ao tipo Visual.
-- Os de ouvir também não são ligados ao tipo Auditiva.
INSERT INTO atividade_tipos (atividade_id, tipo_deficiencia_id)
 SELECT a.id, t.id FROM atividades a, tipos_deficiencia t
  WHERE t.nome <> 'Visual' AND NOT (a.formato = 'ouvir_figura' AND t.nome = 'Auditiva');
