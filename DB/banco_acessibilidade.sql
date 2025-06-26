create database if not exists banco_acessibilidade;
use banco_acessibilidade;

-- Criação das tabelas para o sistema Acessibilidade Assistida

-- Tabela: tipos_deficiencia
CREATE TABLE tipos_deficiencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(50) NOT NULL
);

-- Tabela: usuarios
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    tipo_deficiencia_id INT,
    FOREIGN KEY (tipo_deficiencia_id) REFERENCES tipos_deficiencia(id)
);
-- Tabela: atividades
CREATE TABLE atividades (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(100) NOT NULL,
    descricao TEXT,
    tipo_deficiencia_id INT,
    midia_url VARCHAR(255),
    FOREIGN KEY (tipo_deficiencia_id) REFERENCES tipos_deficiencia(id)
);

-- Tabela: feedbacks
CREATE TABLE feedbacks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    atividade_id INT,
    comentario TEXT,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (atividade_id) REFERENCES atividades(id)
);