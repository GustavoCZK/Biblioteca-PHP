DROP DATABASE IF EXISTS biblioteca;
CREATE DATABASE biblioteca CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE biblioteca;

-- 1. Criação das Tabelas Principais
CREATE TABLE biblioteca (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    endereco VARCHAR(200)
);

CREATE TABLE autor (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    nacionalidade VARCHAR(50)
);

CREATE TABLE livro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    cor_capa VARCHAR(7) DEFAULT '#3b82f6',
    preco DECIMAL(10,2) NOT NULL DEFAULT 39.90,
    quantidade INT NOT NULL DEFAULT 1,
    id_autor INT NOT NULL,
    id_biblioteca INT NOT NULL,
    FOREIGN KEY (id_autor) REFERENCES autor(id) ON DELETE CASCADE,
    FOREIGN KEY (id_biblioteca) REFERENCES biblioteca(id) ON DELETE CASCADE
);

-- 2. Criação da Tabela de Auditoria (Preço e Quantidade)
CREATE TABLE auditoria_livro (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_livro INT NOT NULL,
    preco_antigo DECIMAL(10,2) NOT NULL,
    preco_novo DECIMAL(10,2) NOT NULL,
    quantidade_antiga INT NOT NULL,
    quantidade_nova INT NOT NULL,
    data_alteracao DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_livro) REFERENCES livro(id) ON DELETE CASCADE
);

-- 3. Inserção dos Dados Iniciais
INSERT INTO biblioteca (id, nome, endereco) VALUES 
(1, 'Biblioteca Central', 'Av. Principal, 100'),
(2, 'Biblioteca Comunitária Norte', 'Rua das Flores, 45');

-- Insere os autores garantindo os IDs 1, 2, 3 e 4
INSERT INTO autor (id, nome, nacionalidade) VALUES 
(1, 'Machado de Assis', 'Brasileira'),
(2, 'Clarice Lispector', 'Brasileira'),
(3, 'George Orwell', 'Britânica'),
(4, 'J.R.R. Tolkien', 'Britânica');

-- Agora os IDs 1, 2, 3 e 4 existem com certeza!
INSERT INTO livro (titulo, cor_capa, preco, quantidade, id_autor, id_biblioteca) VALUES 
('Dom Casmurro', '#8b0000', 45.00, 12, 1, 1),
('Memórias Póstumas de Brás Cubas', '#2f4f4f', 52.50, 8, 1, 1),
('A Hora da Estrela', '#d97706', 38.90, 5, 2, 1),
('1984', '#1e3a8a', 49.90, 15, 3, 1),
('O Senhor dos Anéis', '#065f46', 89.90, 4, 4, 2),
('A Revolução dos Bichos', '#7c2d12', 34.00, 20, 3, 2);
-- 4. Criação do TRIGGER (dispara se o preço OU a quantidade mudarem)
DELIMITER //
CREATE TRIGGER trg_auditoria_livro
AFTER UPDATE ON livro
FOR EACH ROW
BEGIN
    IF OLD.preco <> NEW.preco OR OLD.quantidade <> NEW.quantidade THEN
        INSERT INTO auditoria_livro (
            id_livro, 
            preco_antigo, 
            preco_novo, 
            quantidade_antiga, 
            quantidade_nova, 
            data_alteracao
        )
        VALUES (
            NEW.id, 
            OLD.preco, 
            NEW.preco, 
            OLD.quantidade, 
            NEW.quantidade, 
            NOW()
        );
    END IF;
END //
DELIMITER ;

-- 5. Criação da VIEW (Método 1: Lista completa)
CREATE VIEW QLivros AS
SELECT 
    l.id AS livro_id,
    l.titulo,
    l.cor_capa,
    l.preco,
    l.quantidade,
    a.nome AS autor,
    b.id AS biblioteca_id,
    b.nome AS biblioteca
FROM livro l
INNER JOIN autor a ON l.id_autor = a.id
INNER JOIN biblioteca b ON l.id_biblioteca = b.id;

-- 6. Criação da STORED PROCEDURE (Método 2: Filtro por biblioteca)
DROP PROCEDURE IF EXISTS SQBuscarBiblioteca;
DELIMITER //
CREATE PROCEDURE SQBuscarBiblioteca(
    IN p_id_biblioteca INT,
    IN p_preco_max DECIMAL(10,2),
    IN p_termo VARCHAR(100),
    IN p_apenas_estoque TINYINT
)
BEGIN
    SELECT 
        l.id AS livro_id,
        l.titulo,
        l.cor_capa,
        l.preco,
        l.quantidade,
        a.nome AS autor,
        b.nome AS biblioteca
    FROM livro l
    INNER JOIN autor a ON l.id_autor = a.id
    INNER JOIN biblioteca b ON l.id_biblioteca = b.id
    WHERE l.id_biblioteca = p_id_biblioteca
      AND (p_preco_max <= 0 OR l.preco <= p_preco_max)
      AND (p_termo = '' OR l.titulo LIKE CONCAT('%', p_termo, '%') OR a.nome LIKE CONCAT('%', p_termo, '%'))
      AND (p_apenas_estoque = 0 OR l.quantidade > 0);
END //
DELIMITER ;


