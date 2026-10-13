-- ========================================================
-- Amora Artesanal - Dados Iniciais (Seeds)
-- ========================================================

-- Categorias de Produtos Artesanais
INSERT INTO categorias (id, nome_categoria) VALUES
(1, 'Velas Aromáticas'),
(2, 'Sabonetes Artesanais'),
(3, 'Amigurumis'),
(4, 'Kits Presente')
ON DUPLICATE KEY UPDATE nome_categoria=VALUES(nome_categoria);

-- Usuários Padrão (Senhas em Bcrypt conforme RNF01)
-- Admin: admin@amora.com | Senha: admin123
-- Cliente: cliente@exemplo.com | Senha: cliente123
INSERT INTO usuarios (id, nome, email, senha_hash, tipo_usuario) VALUES
(1, 'Administradora Amora', 'admin@amora.com', '$2y$10$5I6ySAqTLl4i9M/lh0QPMO3T/4xxfaah/qGz9wgn9Irp2.luVxNYm', 'admin'),
(2, 'Larissa Benício', 'cliente@exemplo.com', '$2y$10$bn6OtSPtaZZz4HqMi6Zgz.wnetbIj/H6HJgrLOH6bjiPwxFmeIWiO', 'cliente')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);

-- Produtos Iniciais
INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem_url) VALUES
(1, 1, 'Vela Aromática Flor de Cerejeira', 'Vela artesanal feita com cera vegetal de coco, pavio 100% algodão e essência premium de flor de cerejeira em pote de vidro decorado (180g). Queima limpa e duradoura.', 45.00, 15, 'assets/img/vela_cerejeira.svg'),
(2, 1, 'Vela Aromática Lavanda Francesa', 'Vela calmante com óleo essencial de lavanda pura. Ideal para momentos de autocuidado e relaxamento no fim do dia.', 42.00, 20, 'assets/img/vela_lavanda.svg'),
(3, 1, 'Vela Aromática Canela & Especiarias', 'Aroma acolhedor e envolvente de canela com toques de cravo e baunilha, perfeito para deixar o lar aconchegante.', 48.00, 12, 'assets/img/vela_canela.svg'),
(4, 2, 'Sabonete Artesanal Argila Rosa & Rosas', 'Sabonete vegano hidratante saponificado a frio com argila rosa e extrato botânico de rosas mosqueta. Indicado para peles sensíveis.', 18.50, 30, 'assets/img/sabonete_rosa.svg'),
(5, 2, 'Sabonete Esfoliante Café & Mel', 'Sabonete esfoliante natural enriquecido com pó de café selecionado e mel puro para renovação suave da pele.', 19.90, 25, 'assets/img/sabonete_cafe.svg'),
(6, 2, 'Sabonete Calmante Aveia & Camomila', 'Fórmula suave hipoalergênica enriquecida com flocos de aveia e infusão concentrada de camomila.', 17.50, 28, 'assets/img/sabonete_aveia.svg'),
(7, 3, 'Amigurumi Ursinho Teddy Caramelo', 'Ursinho fofo confeccionado à mão em crochê com fio 100% algodão mercerizado, olhos com trava de segurança e enchimento antialérgico (22cm).', 89.90, 8, 'assets/img/amigurumi_ursinho.svg'),
(8, 3, 'Amigurumi Coelhinha com Vestido Floral', 'Coelhinha artesanal personalizável com laço de cetim e vestidinho removível feito à mão (25cm).', 95.00, 6, 'assets/img/amigurumi_coelho.svg'),
(9, 3, 'Amigurumi Polvinho do Amor Rosa Bebê', 'Polvinho sensorial para bebês e recém-nascidos com tentáculos acolhedores que trazem sensação de conforto.', 55.00, 14, 'assets/img/amigurumi_polvo.svg'),
(10, 4, 'Kit Presente Mimo Aromático', 'Caixa kraft artesanal decorada contendo 1 vela aromática de flor de cerejeira, 1 sabonete de argila rosa e 1 mini buquê de flores secas.', 79.90, 10, 'assets/img/kit_mimo.svg'),
(11, 4, 'Kit Encanto Spa em Casa', 'Kit presente completo com 2 sabonetes artesanais, 1 vela aromática de lavanda e 1 toalhinha de lavabo bordada.', 119.00, 5, 'assets/img/kit_spa.svg')
ON DUPLICATE KEY UPDATE nome=VALUES(nome);
