CREATE DATABASE IF NOT EXISTS formapay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE formapay;

CREATE TABLE IF NOT EXISTS usuarios (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    perfil ENUM('comissao','participante') NOT NULL DEFAULT 'participante',
    foto_perfil VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coortes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(120) NOT NULL,
 meta DECIMAL(12,2) NOT NULL DEFAULT 30000.00, vencimento_dia TINYINT UNSIGNED NOT NULL DEFAULT 10,
 criado_por INT UNSIGNED NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_coorte_criador FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS anos_letivos (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coorte_id INT UNSIGNED NOT NULL,
 ano_escolar TINYINT UNSIGNED NOT NULL, ano INT UNSIGNED NOT NULL,
 UNIQUE KEY uq_coorte_ano (coorte_id, ano_escolar, ano),
 CONSTRAINT fk_ano_coorte FOREIGN KEY (coorte_id) REFERENCES coortes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS participantes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coorte_id INT UNSIGNED NOT NULL,
 usuario_id INT UNSIGNED NULL, nome VARCHAR(120) NOT NULL, email VARCHAR(190) NULL,
 status ENUM('ativo','inativo','desistente') NOT NULL DEFAULT 'ativo',
 entrou_em DATE NOT NULL, saiu_em DATE NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY uq_participante_usuario_coorte (coorte_id, usuario_id),
 CONSTRAINT fk_participante_coorte FOREIGN KEY (coorte_id) REFERENCES coortes(id) ON DELETE CASCADE,
 CONSTRAINT fk_participante_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS planos_contribuicao (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coorte_id INT UNSIGNED NOT NULL,
 valor DECIMAL(10,2) NOT NULL, periodicidade ENUM('mensal','bimestral','unica') NOT NULL DEFAULT 'mensal',
 vigencia_inicio DATE NOT NULL, vigencia_fim DATE NULL, permite_parcial TINYINT(1) NOT NULL DEFAULT 1,
 ativo TINYINT(1) NOT NULL DEFAULT 1,
 CONSTRAINT fk_plano_coorte FOREIGN KEY (coorte_id) REFERENCES coortes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parcelas_previstas (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, participante_id INT UNSIGNED NOT NULL,
 plano_id INT UNSIGNED NOT NULL, competencia DATE NOT NULL, vencimento DATE NOT NULL,
 valor DECIMAL(10,2) NOT NULL, status ENUM('aberta','parcial','paga','cancelada') NOT NULL DEFAULT 'aberta',
 UNIQUE KEY uq_parcela_participante_competencia (participante_id, competencia),
 CONSTRAINT fk_parcela_participante FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE CASCADE,
 CONSTRAINT fk_parcela_plano FOREIGN KEY (plano_id) REFERENCES planos_contribuicao(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categorias_financeiras (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coorte_id INT UNSIGNED NOT NULL,
 nome VARCHAR(80) NOT NULL, tipo ENUM('receita','despesa','ambos') NOT NULL DEFAULT 'ambos',
 CONSTRAINT fk_categoria_coorte FOREIGN KEY (coorte_id) REFERENCES coortes(id) ON DELETE CASCADE,
 UNIQUE KEY uq_categoria (coorte_id,nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS movimentacoes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, coorte_id INT UNSIGNED NOT NULL,
 parcela_id INT UNSIGNED NULL, participante_id INT UNSIGNED NULL, usuario_id INT UNSIGNED NOT NULL,
 tipo ENUM('receita','despesa') NOT NULL, categoria_id INT UNSIGNED NULL,
 descricao VARCHAR(180) NOT NULL, valor DECIMAL(10,2) NOT NULL,
 data_movimento DATE NOT NULL, competencia DATE NOT NULL,
 meio ENUM('pix','dinheiro','transferencia','cartao','outro') NOT NULL DEFAULT 'pix',
 estorno_de INT UNSIGNED NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_mov_coorte FOREIGN KEY (coorte_id) REFERENCES coortes(id),
 CONSTRAINT fk_mov_parcela FOREIGN KEY (parcela_id) REFERENCES parcelas_previstas(id) ON DELETE SET NULL,
 CONSTRAINT fk_mov_participante FOREIGN KEY (participante_id) REFERENCES participantes(id) ON DELETE SET NULL,
 CONSTRAINT fk_mov_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
 CONSTRAINT fk_mov_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_financeiras(id) ON DELETE SET NULL,
 CONSTRAINT fk_mov_estorno FOREIGN KEY (estorno_de) REFERENCES movimentacoes(id) ON DELETE SET NULL,
 INDEX ix_mov_coorte_data (coorte_id,data_movimento), INDEX ix_mov_participante (participante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS historico_movimentacoes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, movimentacao_id INT UNSIGNED NOT NULL,
 usuario_id INT UNSIGNED NOT NULL, acao VARCHAR(40) NOT NULL, detalhes JSON NULL,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_hist_mov FOREIGN KEY (movimentacao_id) REFERENCES movimentacoes(id),
 CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
