-- ============================================
-- Script de criação do banco de dados
-- Gerenciador de Contatos - Projeto Anti SQL Injection
-- ============================================

-- Criar o banco de dados (execute no MySQL antes de rodar o PHP)
CREATE DATABASE IF NOT EXISTS gerenciador_contatos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE gerenciador_contatos;

-- Criar a tabela de contatos
CREATE TABLE IF NOT EXISTS contatos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados de exemplo para testes iniciais
INSERT INTO contatos (nome, descricao) VALUES
  ('Maria Silva', 'Desenvolvedora backend'),
  ('João Santos', 'Analista de sistemas'),
  ('Ana Oliveira', 'Designer UX/UI');
