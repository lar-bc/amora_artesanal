-- ========================================================
-- Amora Artesanal - Dados Iniciais com Preços Reais de Mercado
-- ========================================================

-- Categorias de Produtos Artesanais
INSERT INTO categorias (id, nome_categoria) VALUES
(1, 'Velas Aromáticas'),
(2, 'Sabonetes Artesanais'),
(3, 'Amigurumis'),
(4, 'Kits Presente')
ON DUPLICATE KEY UPDATE nome_categoria=VALUES(nome_categoria);

-- Usuários Padrão
-- Admin: admin@amora.com | Senha: admin123
-- Cliente: cliente@exemplo.com | Senha: cliente123
INSERT INTO usuarios (id, nome, email, senha_hash, tipo_usuario, data_nascimento, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado) VALUES
(1, 'Administradora Amora', 'admin@amora.com', '$2y$10$5I6ySAqTLl4i9M/lh0QPMO3T/4xxfaah/qGz9wgn9Irp2.luVxNYm', 'admin', '1995-04-12', '(69) 98425-6710', '76820-120', 'Av. Tiradentes', '3000', 'Ateliê', 'Embratel', 'Porto Velho', 'RO'),
(2, 'Larissa Benício', 'cliente@exemplo.com', '$2y$10$bn6OtSPtaZZz4HqMi6Zgz.wnetbIj/H6HJgrLOH6bjiPwxFmeIWiO', 'cliente', '2004-09-29', '(69) 99345-1234', '76801-000', 'Rua José Bonifácio', '450', 'Apto 102', 'Olaria', 'Porto Velho', 'RO')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- Produtos com Preços Reais de Mercado Artesanal
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

-- Avaliações Iniciais com Pergunta NPS ("Você indicaria?")
INSERT INTO avaliacoes (id, produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo) VALUES
(1, 1, 2, 'Juliana Mendes', 5, 'O aroma de flor de cerejeira é simplesmente divino! A queima foi super limpa e perfumou a sala inteira. Embalagem impecável!', 'sim'),
(2, 4, 2, 'Camila Silveira', 5, 'Minha pele é muito sensível e esse sabonete de argila rosa foi a melhor descoberta. Super hidratante e cheirinho maravilhoso.', 'sim'),
(3, 7, 2, 'Rafael Souza', 5, 'Comprei o ursinho de presente para o chá de bebê da minha sobrinha. O acabamento do crochê é perfeito, nota 10!', 'sim'),
(4, 10, 2, 'Beatriz Lima', 5, 'O kit veio embalado para presente com tanto capricho que fiquei até com dó de abrir. Chegou super rápido aqui em Rondônia.', 'sim')
ON DUPLICATE KEY UPDATE comentario=VALUES(comentario);
