-- ============================================
-- SISTEMA PLUVIT - ESTRUTURA DO BANCO DE DADOS
-- ============================================
-- Este arquivo cria as tabelas necessárias para
-- armazenar os dados pluviométricos do INMET e CGE.

-- Cria o banco de dados
CREATE DATABASE IF NOT EXISTS pluvit
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE pluvit;

-- ============================================
-- TABELA: historico_chuvas (INMET)
-- ============================================
-- Armazena os dados horários do INMET (2020-2026)
CREATE TABLE IF NOT EXISTS historico_chuvas (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_registro DATE NOT NULL,
  hora_registro VARCHAR(10) NOT NULL,
  chuva_mm DECIMAL(5,2) NULL,
  estacao VARCHAR(150) NOT NULL,
  estado CHAR(2) NOT NULL DEFAULT 'SP',
  INDEX idx_data (data_registro),
  INDEX idx_estacao (estacao),
  INDEX idx_estado (estado),
  INDEX idx_data_estacao (data_registro, estacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA: historico_chuvas_cge (CGE-SP)
-- ============================================
-- Armazena os dados diários do CGE (2010-2026)
CREATE TABLE IF NOT EXISTS historico_chuvas_cge (
  id INT AUTO_INCREMENT PRIMARY KEY,
  data_registro DATE NOT NULL,
  chuva_mm DECIMAL(6,2),
  codigo VARCHAR(10),
  estacao VARCHAR(150) NOT NULL,
  media_zona VARCHAR(10),
  estado CHAR(2) DEFAULT 'SP',
  INDEX idx_data (data_registro),
  INDEX idx_estacao (estacao),
  INDEX idx_codigo (codigo),
  INDEX idx_data_estacao (data_registro, estacao),
  INDEX idx_media_zona (media_zona)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- TABELA: logs_download (Auditoria)
-- ============================================
-- Registra quem baixou cada arquivo (CSV ou Relatório)
CREATE TABLE IF NOT EXISTS logs_download (
  id INT AUTO_INCREMENT PRIMARY KEY,
  usuario VARCHAR(100) NOT NULL,
  data_hora DATETIME NOT NULL,
  tipo VARCHAR(20) NOT NULL,
  fonte VARCHAR(10) NOT NULL,
  local VARCHAR(255),
  periodo VARCHAR(100),
  ip VARCHAR(45),
  INDEX idx_data_hora (data_hora),
  INDEX idx_usuario (usuario),
  INDEX idx_fonte (fonte)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- FIM DO SCRIPT
-- ============================================