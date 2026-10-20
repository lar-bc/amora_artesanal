<?php
/**
 * Inicializador Automático de Banco de Dados
 * Cria e atualiza as tabelas de Usuários, Produtos, Pedidos, Avaliações e Chat
 */
require_once __DIR__ . '/../config/database.php';

function inicializarBanco(): bool {
    $db = getDBConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        // Esquema completo para SQLite
        $queries = [
            "CREATE TABLE IF NOT EXISTS categorias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome_categoria TEXT NOT NULL UNIQUE
            );",
            "CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                senha_hash TEXT NOT NULL,
                tipo_usuario TEXT NOT NULL DEFAULT 'cliente',
                data_nascimento TEXT DEFAULT NULL,
                telefone TEXT DEFAULT NULL,
                cep TEXT DEFAULT NULL,
                logradouro TEXT DEFAULT NULL,
                numero TEXT DEFAULT NULL,
                complemento TEXT DEFAULT NULL,
                bairro TEXT DEFAULT NULL,
                cidade TEXT DEFAULT NULL,
                estado TEXT DEFAULT NULL,
                data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );",
            "CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                preco REAL NOT NULL,
                estoque INTEGER NOT NULL DEFAULT 0,
                imagem_url TEXT DEFAULT NULL,
                FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
            );",
            "CREATE TABLE IF NOT EXISTS pedidos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                valor_total REAL NOT NULL,
                status TEXT NOT NULL DEFAULT 'Pedido realizado',
                endereco_entrega TEXT NOT NULL,
                forma_pagamento TEXT DEFAULT 'Pix',
                forma_pagamento_detalhes TEXT DEFAULT NULL,
                codigo_rastreio TEXT DEFAULT NULL,
                transportadora TEXT DEFAULT 'LogiAmora Transportes / Sedex',
                prazo_estimado_dias INTEGER DEFAULT 5,
                confirmacao_entrega_cliente TEXT DEFAULT NULL,
                reembolso_solicitado INTEGER DEFAULT 0,
                protocolo_reembolso TEXT DEFAULT NULL,
                motivo_reembolso TEXT DEFAULT NULL,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            );",
            "CREATE TABLE IF NOT EXISTS itens_pedido (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                pedido_id INTEGER NOT NULL,
                produto_id INTEGER NOT NULL,
                quantidade INTEGER NOT NULL DEFAULT 1,
                preco_unitario REAL NOT NULL,
                personalizacao TEXT DEFAULT NULL,
                FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT
            );",
            "CREATE TABLE IF NOT EXISTS mensagens_contato (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL,
                email TEXT NOT NULL,
                telefone TEXT DEFAULT NULL,
                mensagem TEXT NOT NULL,
                data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                lida INTEGER NOT NULL DEFAULT 0
            );",
            "CREATE TABLE IF NOT EXISTS avaliacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                usuario_id INTEGER NOT NULL,
                nome_cliente TEXT NOT NULL,
                estrelas INTEGER NOT NULL DEFAULT 5,
                comentario TEXT NOT NULL,
                indicaria_amigo TEXT NOT NULL DEFAULT 'sim',
                data_avaliacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            );",
            "CREATE TABLE IF NOT EXISTS mensagens_chat (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER NOT NULL,
                remetente TEXT NOT NULL,
                mensagem TEXT NOT NULL,
                data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                lida INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
            );"
        ];

        foreach ($queries as $sql) {
            $db->exec($sql);
        }

        // Tentar adicionar colunas caso tabelas antigas existam
        $colunasNovas = [
            'usuarios' => [
                'data_nascimento' => 'TEXT DEFAULT NULL',
                'telefone' => 'TEXT DEFAULT NULL',
                'cep' => 'TEXT DEFAULT NULL',
                'logradouro' => 'TEXT DEFAULT NULL',
                'numero' => 'TEXT DEFAULT NULL',
                'complemento' => 'TEXT DEFAULT NULL',
                'bairro' => 'TEXT DEFAULT NULL',
                'cidade' => 'TEXT DEFAULT NULL',
                'estado' => 'TEXT DEFAULT NULL'
            ],
            'pedidos' => [
                'forma_pagamento' => 'TEXT DEFAULT "Pix"',
                'forma_pagamento_detalhes' => 'TEXT DEFAULT NULL',
                'codigo_rastreio' => 'TEXT DEFAULT NULL',
                'transportadora' => 'TEXT DEFAULT "LogiAmora Transportes / Sedex"',
                'prazo_estimado_dias' => 'INTEGER DEFAULT 5',
                'confirmacao_entrega_cliente' => 'TEXT DEFAULT NULL',
                'reembolso_solicitado' => 'INTEGER DEFAULT 0',
                'protocolo_reembolso' => 'TEXT DEFAULT NULL',
                'motivo_reembolso' => 'TEXT DEFAULT NULL'
            ],
            'mensagens_contato' => [
                'telefone' => 'TEXT DEFAULT NULL'
            ]
        ];

        foreach ($colunasNovas as $tabela => $cols) {
            foreach ($cols as $col => $tipo) {
                try {
                    $db->exec("ALTER TABLE {$tabela} ADD COLUMN {$col} {$tipo};");
                } catch (Exception $e) {
                    // Coluna já existe, ignorar
                }
            }
        }

        // Popular categorias
        $count = $db->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO categorias (id, nome_categoria) VALUES
                (1, 'Velas Aromáticas'),
                (2, 'Sabonetes Artesanais'),
                (3, 'Amigurumis'),
                (4, 'Kits Presente');");
        }

        // Popular ou atualizar usuários padrão
        $countUsers = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        if ($countUsers == 0) {
            $stmt = $db->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, tipo_usuario, data_nascimento, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([1, 'Administradora Amora', 'admin@amora.com', '$2y$10$5I6ySAqTLl4i9M/lh0QPMO3T/4xxfaah/qGz9wgn9Irp2.luVxNYm', 'admin', '1995-04-12', '(69) 98425-6710', '76820-120', 'Av. Tiradentes', '3000', 'Ateliê', 'Embratel', 'Porto Velho', 'RO']);
            $stmt->execute([2, 'Larissa Benício', 'cliente@exemplo.com', '$2y$10$bn6OtSPtaZZz4HqMi6Zgz.wnetbIj/H6HJgrLOH6bjiPwxFmeIWiO', 'cliente', '2004-09-29', '(69) 99345-1234', '76801-000', 'Rua José Bonifácio', '450', 'Apto 102', 'Olaria', 'Porto Velho', 'RO']);
        }

        // Atualizar produtos com Preços Reais de Mercado
        $produtos = [
            [1, 1, 'Vela Aromática Flor de Cerejeira', 'Vela artesanal feita com cera vegetal de coco, pavio 100% algodão e essência premium de flor de cerejeira em pote de vidro decorado (180g). Queima limpa e duradoura de aproximadamente 36 horas.', 58.00, 15, 'assets/img/vela_cerejeira.svg'],
            [2, 1, 'Vela Aromática Lavanda Francesa', 'Vela calmante com óleo essencial puro de lavanda francesa. Traz serenidade e aconchego ao quarto ou momento de leitura (180g).', 56.00, 20, 'assets/img/vela_lavanda.svg'],
            [3, 1, 'Vela Aromática Canela & Especiarias', 'Aroma acolhedor de canela do Ceilão, cravo da Índia e favas de baunilha. Perfeito para aquecer os dias e deixar a casa perfumada (190g).', 62.00, 12, 'assets/img/vela_canela.svg'],
            [4, 2, 'Sabonete Artesanal Argila Rosa & Rosas', 'Sabonete vegetal botânico saponificado a frio com argila rosa purificante e extrato de rosas mosqueta. Hidratação profunda para peles delicadas (110g).', 24.00, 30, 'assets/img/sabonete_rosa.svg'],
            [5, 2, 'Sabonete Esfoliante Café & Mel', 'Sabonete esfoliante natural com grãos selecionados de café torrado e mel silvestre puro. Estimula a circulação e renova a pele (115g).', 22.50, 25, 'assets/img/sabonete_cafe.svg'],
            [6, 2, 'Sabonete Calmante Aveia & Camomila', 'Fórmula suave hipoalergênica com infusão concentrada de flores de camomila e flocos de aveia coloidal (110g).', 22.00, 28, 'assets/img/sabonete_aveia.svg'],
            [7, 3, 'Amigurumi Ursinho Teddy Caramelo', 'Ursinho confeccionado manualmente em crochê com fio 100% algodão mercerizado, olhos com trava de segurança e enchimento de fibra siliconada antialérgica (22cm).', 115.00, 8, 'assets/img/amigurumi_ursinho.svg'],
            [8, 3, 'Amigurumi Coelhinha com Vestido Floral', 'Coelhinha artesanal personalizável com vestido florido removível e laço de cetim feito ponto a ponto (25cm).', 125.00, 6, 'assets/img/amigurumi_coelho.svg'],
            [9, 3, 'Amigurumi Polvinho do Amor Rosa Bebê', 'Polvinho sensorial para bebês e recém-nascidos. Seus tentáculos macios reproduzem o cordão umbilical, proporcionando segurança e acalento.', 68.00, 14, 'assets/img/amigurumi_polvo.svg'],
            [10, 4, 'Kit Presente Mimo Aromático', 'Caixa kraft presenteável com laço de cetim contendo 1 vela aromática de flor de cerejeira, 1 sabonete de argila rosa e mini buquê de flores desidratadas.', 98.00, 10, 'assets/img/kit_mimo.svg'],
            [11, 4, 'Kit Encanto Spa em Casa', 'Cesta artesanal com 2 sabonetes botânicos, 1 vela aromática de lavanda, 1 toalhinha de lavabo bordada e mini escalda-pés relaxante.', 148.00, 5, 'assets/img/kit_spa.svg']
        ];

        $countProd = $db->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
        if ($countProd == 0) {
            $stmtProd = $db->prepare("INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($produtos as $p) {
                $stmtProd->execute($p);
            }
        } else {
            // Atualizar preços reais e descrições
            $stmtUp = $db->prepare("UPDATE produtos SET preco = ?, descricao = ? WHERE id = ?");
            foreach ($produtos as $p) {
                $stmtUp->execute([$p[4], $p[3], $p[0]]);
            }
        }

        // Popular avaliações iniciais
        $countAval = $db->query("SELECT COUNT(*) FROM avaliacoes")->fetchColumn();
        if ($countAval == 0) {
            $avaliacoes = [
                [1, 1, 2, 'Juliana Mendes', 5, 'O aroma de flor de cerejeira é simplesmente divino! A queima foi super limpa e perfumou a sala inteira. Embalagem impecável!', 'sim'],
                [2, 4, 2, 'Camila Silveira', 5, 'Minha pele é muito sensível e esse sabonete de argila rosa foi a melhor descoberta. Super hidratante e cheirinho maravilhoso.', 'sim'],
                [3, 7, 2, 'Rafael Souza', 5, 'Comprei o ursinho de presente para o chá de bebê da minha sobrinha. O acabamento do crochê é perfeito, nota 10!', 'sim'],
                [4, 10, 2, 'Beatriz Lima', 5, 'O kit veio embalado para presente com tanto capricho que fiquei até com dó de abrir. Chegou super rápido aqui em Rondônia.', 'sim']
            ];
            $stmtAval = $db->prepare("INSERT INTO avaliacoes (id, produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($avaliacoes as $av) {
                $stmtAval->execute($av);
            }
        }
    } else {
        // Modo MySQL
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $db->exec($schemaSql);
        $seedsSql = file_get_contents(__DIR__ . '/seeds.sql');
        $db->exec($seedsSql);
    }

    return true;
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Atualizando banco de dados...\n";
    if (inicializarBanco()) {
        echo "Banco de dados atualizado com sucesso!\n";
    }
}
