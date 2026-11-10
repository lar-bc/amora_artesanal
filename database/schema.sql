-- ========================================================
-- Amora Artesanal - Esquema Completo do Banco de Dados Relacional
-- Sistema de E-commerce Artesanal, Rastreamento Logístico e Auditoria
-- Autor(a): Larissa Benício da Cruz | IFRO Campus Porto Velho Calama
-- ========================================================

CREATE DATABASE IF NOT EXISTS amora_artesanal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE amora_artesanal;

-- 1. Categorias de Produtos
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome_categoria VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Usuários (Clientes, Administradores, Operadores de Logística e Auditores)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    senha_hash VARCHAR(255) NOT NULL,
    tipo_usuario ENUM('cliente', 'admin', 'logistica', 'auditor') NOT NULL DEFAULT 'cliente',
    data_nascimento DATE DEFAULT NULL,
    telefone VARCHAR(30) DEFAULT NULL,
    cep VARCHAR(15) DEFAULT NULL,
    logradouro VARCHAR(150) DEFAULT NULL,
    numero VARCHAR(20) DEFAULT NULL,
    complemento VARCHAR(100) DEFAULT NULL,
    bairro VARCHAR(100) DEFAULT NULL,
    cidade VARCHAR(100) DEFAULT NULL,
    estado VARCHAR(10) DEFAULT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Produtos Artesanais
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT,
    preco DECIMAL(10, 2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    imagem_url VARCHAR(255) DEFAULT NULL,
    CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Pedidos com Rastreamento em Tempo Real
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    valor_total DECIMAL(10, 2) NOT NULL,
    status ENUM('Pedido realizado', 'Em preparação', 'Enviado', 'Entregue', 'Reembolso Solicitado', 'Reembolsado') NOT NULL DEFAULT 'Pedido realizado',
    endereco_entrega TEXT NOT NULL,
    forma_pagamento VARCHAR(50) NOT NULL DEFAULT 'Pix',
    forma_pagamento_detalhes TEXT DEFAULT NULL,
    codigo_rastreio VARCHAR(30) DEFAULT NULL,
    transportadora VARCHAR(100) DEFAULT 'LogiAmora Transportes / Sedex',
    modalidade_transporte ENUM('drone', 'rodovia') NOT NULL DEFAULT 'drone',
    prazo_estimado_dias INT DEFAULT 5,
    confirmacao_entrega_cliente VARCHAR(20) DEFAULT NULL,
    reembolso_solicitado TINYINT(1) DEFAULT 0,
    protocolo_reembolso VARCHAR(50) DEFAULT NULL,
    motivo_reembolso TEXT DEFAULT NULL,
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Itens do Pedido com Personalização Exclusiva
CREATE TABLE IF NOT EXISTS itens_pedido (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10, 2) NOT NULL,
    personalizacao TEXT DEFAULT NULL,
    CONSTRAINT fk_itens_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_itens_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Mensagens de Contato e Encomendas Personalizadas
CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefone VARCHAR(30) DEFAULT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Avaliações de Produtos e Pesquisa NPS
CREATE TABLE IF NOT EXISTS avaliacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    usuario_id INT NOT NULL,
    nome_cliente VARCHAR(150) NOT NULL,
    estrelas INT NOT NULL DEFAULT 5,
    comentario TEXT NOT NULL,
    indicaria_amigo ENUM('sim', 'nao') NOT NULL DEFAULT 'sim',
    data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliacoes_produto FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    CONSTRAINT fk_avaliacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Mensagens de Chat em Tempo Real entre Clientes e Ateliê
CREATE TABLE IF NOT EXISTS mensagens_chat (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    remetente ENUM('cliente', 'admin') NOT NULL,
    mensagem TEXT NOT NULL,
    data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    CONSTRAINT fk_chat_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Auditoria Bilateral de Reembolsos e Estornos Financeiros
CREATE TABLE IF NOT EXISTS auditoria_reembolsos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    protocolo_reembolso VARCHAR(50) NOT NULL UNIQUE,
    laudo_transportadora TEXT NOT NULL,
    codigo_ocorrencia_transp VARCHAR(50) NOT NULL,
    status_transportadora ENUM('pendente', 'confirmado_avaria', 'confirmado_extravio', 'rejeitado') NOT NULL DEFAULT 'pendente',
    parecer_admin TEXT DEFAULT NULL,
    status_admin ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
    hash_liquidacao_pix VARCHAR(100) DEFAULT NULL,
    valor_estorno DECIMAL(10, 2) NOT NULL,
    chave_pix_cliente VARCHAR(100) NOT NULL,
    data_solicitacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_conclusao TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT fk_auditoria_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Registros de Telemetria e Rastreamento Logístico (Drones e Rodovia)
CREATE TABLE IF NOT EXISTS rastreamento_logistica (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    modalidade ENUM('drone', 'rodovia') NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    altitude_metros DECIMAL(6, 2) DEFAULT 0.00,
    velocidade_kmh DECIMAL(5, 2) NOT NULL,
    status_deslocamento VARCHAR(100) NOT NULL,
    data_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rastreamento_pedido FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- DADOS INICIAIS (SEED DATA)
-- Senhas individuais exclusivas com hash Bcrypt por perfil de usuário:
-- 1. admin@amora.com               -> Senha: AmoraAdmin@2026
-- 2. larissa.benicio@gmail.com     -> Senha: Larissa@Amora2026
-- 3. mariana.vasconcelos@amora.com.br -> Senha: Mariana#Vasc26
-- 4. logistica@amora.com           -> Senha: LogiSedex#2026
-- 5. auditoria@amora.com           -> Senha: AuditPix#2026
-- ========================================================

-- Categorias
INSERT INTO categorias (id, nome_categoria) VALUES
(1, 'Velas Aromáticas'),
(2, 'Sabonetes Artesanais'),
(3, 'Amigurumis'),
(4, 'Kits Presente')
ON DUPLICATE KEY UPDATE nome_categoria=VALUES(nome_categoria);

-- Usuários de Teste Pré-configurados com Senhas Individuais
INSERT INTO usuarios (id, nome, email, senha_hash, tipo_usuario, data_nascimento, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado) VALUES
(1, 'Larissa Benício (Administradora)', 'admin@amora.com', '$2y$10$gr1WZJSbWMRtD0zHk0Jcce/7CJl7QNHEamfx1ggyJLclbJ/57b2gK', 'admin', '2004-09-29', '(69) 98425-6710', '76820-120', 'Av. Tiradentes', '3000', 'Ateliê Central', 'Embratel', 'Porto Velho', 'RO'),
(2, 'Larissa Benício (Cliente)', 'larissa.benicio@gmail.com', '$2y$10$Da3f5d8RvoYjIq3Gx905p.TNPXYSAnVxx2AaT0fGhRBCukzJDwF1e', 'cliente', '2004-09-29', '(69) 98412-3456', '76820-120', 'Av. Tiradentes', '3000', 'Apto 204', 'Embratel', 'Porto Velho', 'RO'),
(3, 'Mariana Vasconcelos (Cliente)', 'mariana.vasconcelos@amora.com.br', '$2y$10$w7rEcn6VsJwOdWK2meXXwO5yHVhDlnB4xjk6c..uGiCukCjF287bW', 'cliente', '1998-05-14', '(69) 99345-1234', '76820-441', 'Av. Calama (IFRO Calama)', '4985', 'Bloco de Informática', 'Flodoaldo Pontes Pinto', 'Porto Velho', 'RO'),
(4, 'Moacir Supervisor (Logística)', 'logistica@amora.com', '$2y$10$HtibFT1zwad/ePJv7EnCiepj5uxOwhqqdZnBsYVhZNXOXy5CuwqYG', 'logistica', '1990-11-20', '(69) 98111-2233', '76801-000', 'Rua José Bonifácio', '450', 'Hangar Drones', 'Olaria', 'Porto Velho', 'RO'),
(5, 'Renan Innot (Auditor Financeiro)', 'auditoria@amora.com', '$2y$10$lz1fQPpx/HHT6lhI.CalrOtQykTx7RFtAO0UciqBkLXN8IWK.zR9G', 'auditor', '1992-08-03', '(69) 98444-5566', '76803-888', 'Av. Presidente Dutra', '120', 'Sala 402', 'Centro', 'Porto Velho', 'RO')
ON DUPLICATE KEY UPDATE nome=VALUES(nome), senha_hash=VALUES(senha_hash);

-- Produtos Artesanais
INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem_url) VALUES
(1, 1, 'Vela Aromática Flor de Cerejeira', 'Vela artesanal feita com cera vegetal de coco, pavio 100% algodão e essência premium de flor de cerejeira em pote de vidro decorado (180g). Queima limpa e duradoura de aproximadamente 36 horas.', 58.00, 15, 'assets/img/vela_cerejeira.svg'),
(2, 1, 'Vela Aromática Lavanda Francesa', 'Vela calmante com óleo essencial puro de lavanda francesa. Traz serenidade e aconchego ao quarto ou momento de leitura (180g).', 56.00, 20, 'assets/img/vela_lavanda.svg'),
(3, 1, 'Vela Aromática Canela & Especiarias', 'Aroma acolhedor de canela do Ceilão, cravo da Índia e favas de baunilha. Perfeito para aquecer os dias e deixar a casa perfumada (190g).', 62.00, 12, 'assets/img/vela_canela.svg'),
(4, 2, 'Sabonete Artesanal Argila Rosa & Rosas', 'Sabonete vegetal botânico saponificado a frio com argila rosa purificante e extrato de rosas mosqueta. Hidratação profunda para peles delicadas (110g).', 24.00, 30, 'assets/img/sabonete_rosa.svg'),
(5, 2, 'Sabonete Esfoliante Café & Mel', 'Sabonete esfoliante natural com grãos selecionados de café torrado e mel silvestre puro. Estimula a circulação e renova a pele (115g).', 22.50, 25, 'assets/img/sabonete_cafe.svg'),
(6, 2, 'Sabonete Calmante Aveia & Camomila', 'Fórmula suave hipoalergênica com infusão concentrada de flores de camomila e flocos de aveia coloidal (110g).', 22.00, 28, 'assets/img/sabonete_aveia.svg'),
(7, 3, 'Amigurumi Ursinho Teddy Caramelo', 'Ursinho confeccionado manualmente em crochê com fio 100% algodão mercerizado, olhos com trava de segurança e enchimento de fibra siliconada antialérgica (22cm).', 115.00, 8, 'assets/img/amigurumi_ursinho.svg'),
(8, 3, 'Amigurumi Coelhinha com Vestido Floral', 'Coelhinha artesanal personalizável com vestido florido removível e laço de cetim feito ponto a ponto (25cm).', 125.00, 6, 'assets/img/amigurumi_coelho.svg'),
(9, 3, 'Amigurumi Polvinho do Amor Rosa Bebê', 'Polvinho sensorial para bebês e recém-nascidos. Seus tentáculos macios reproduzem o cordão umbilical, proporcionando segurança e acalento.', 68.00, 14, 'assets/img/amigurumi_polvo.svg'),
(10, 4, 'Kit Presente Mimo Aromático', 'Caixa kraft presenteável com laço de cetim contendo 1 vela aromática de flor de cerejeira, 1 sabonete de argila rosa e mini buquê de flores desidratadas.', 98.00, 10, 'assets/img/kit_mimo.svg'),
(11, 4, 'Kit Encanto Spa em Casa', 'Cesta artesanal com 2 sabonetes botânicos, 1 vela aromática de lavanda, 1 toalhinha de lavabo bordada e mini escalda-pés relaxante.', 148.00, 5, 'assets/img/kit_spa.svg')
ON DUPLICATE KEY UPDATE preco=VALUES(preco), descricao=VALUES(descricao);

-- Avaliações Iniciais com Pergunta NPS
INSERT INTO avaliacoes (id, produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo) VALUES
(1, 1, 2, 'Juliana Mendes', 5, 'O aroma de flor de cerejeira é simplesmente divino! A queima foi super limpa e perfumou a sala inteira. Embalagem impecável!', 'sim'),
(2, 4, 2, 'Camila Silveira', 5, 'Minha pele é muito sensível e esse sabonete de argila rosa foi a melhor descoberta. Super hidratante e cheirinho maravilhoso.', 'sim'),
(3, 7, 2, 'Rafael Souza', 5, 'Comprei o ursinho de presente para o chá de bebê da minha sobrinha. O acabamento do crochê é perfeito, nota 10!', 'sim'),
(4, 10, 2, 'Beatriz Lima', 5, 'O kit veio embalado para presente com tanto capricho que fiquei até com dó de abrir. Chegou super rápido aqui em Rondônia.', 'sim')
ON DUPLICATE KEY UPDATE comentario=VALUES(comentario);
