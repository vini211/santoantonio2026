
-- CRIAR BANCO DE DADOS
CREATE DATABASE IF NOT EXISTS supermercado
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE supermercado;

-- TABELA DE CATEGORIAS
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(80) NOT NULL UNIQUE,
    descricao VARCHAR(255)
) ENGINE=InnoDB;

-- TABELA DE PRODUTOS
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nome VARCHAR(120) NOT NULL,
    descricao TEXT,
    categoria_id INT NOT NULL,
    preco DECIMAL(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 0,
    validade DATE NULL,

    CONSTRAINT fk_categoria
    FOREIGN KEY (categoria_id)
    REFERENCES categorias(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,

    CHECK (preco > 0),
    CHECK (estoque >= 0),
    CHECK (estoque_minimo >= 0)
) ENGINE=InnoDB;

-- CADASTRAR CATEGORIAS
INSERT IGNORE INTO categorias (id, nome, descricao) VALUES
(1, 'Alimentos', 'Produtos alimentícios'),
(2, 'Bebidas', 'Bebidas em geral'),
(3, 'Limpeza', 'Produtos de limpeza'),
(4, 'Higiene', 'Produtos de higiene pessoal');

-- CADASTRAR PRODUTOS
INSERT IGNORE INTO produtos
(codigo, nome, descricao, categoria_id, preco,
 estoque, estoque_minimo, validade)
VALUES

('001', 'Arroz 5kg', 'Pacote de arroz de 5kg',
 1, 25.90, 20, 5, '2027-05-10'),

('002', 'Feijão 1kg', 'Pacote de feijão de 1kg',
 1, 8.50, 3, 5, '2027-03-15'),

('003', 'Macarrão', 'Pacote de macarrão',
 1, 4.99, 15, 5, '2027-08-20'),

('004', 'Refrigerante 2L', 'Garrafa de refrigerante',
 2, 9.50, 12, 4, '2027-01-10'),

('005', 'Água mineral', 'Garrafa de água mineral',
 2, 2.50, 0, 10, NULL),

('006', 'Detergente', 'Detergente líquido',
 3, 2.99, 30, 8, NULL),

('007', 'Sabão em pó', 'Pacote de sabão em pó',
 3, 12.90, 5, 8, NULL),

('008', 'Sabonete', 'Sabonete para higiene pessoal',
 4, 3.50, 25, 5, NULL),

('009', 'Shampoo', 'Shampoo para cabelos',
 4, 15.90, 10, 3, NULL),

('010', 'Biscoito', 'Pacote de biscoito',
 1, 5.50, 8, 4, '2025-01-10');
