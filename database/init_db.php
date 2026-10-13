<?php
/**
 * Inicializador Automático de Banco de Dados
 * Cria as tabelas e popula os dados iniciais tanto em MySQL quanto em SQLite.
 */
require_once __DIR__ . '/../config/database.php';

function inicializarBanco(): bool {
    $db = getDBConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    if ($driver === 'sqlite') {
        // Esquema compatível com SQLite
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
                mensagem TEXT NOT NULL,
                data_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                lida INTEGER NOT NULL DEFAULT 0
            );"
        ];

        foreach ($queries as $sql) {
            $db->exec($sql);
        }

        // Popular categorias se vazio
        $count = $db->query("SELECT COUNT(*) FROM categorias")->fetchColumn();
        if ($count == 0) {
            $db->exec("INSERT INTO categorias (id, nome_categoria) VALUES
                (1, 'Velas Aromáticas'),
                (2, 'Sabonetes Artesanais'),
                (3, 'Amigurumis'),
                (4, 'Kits Presente');");
        }

        // Popular usuários se vazio
        $countUsers = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        if ($countUsers == 0) {
            $stmt = $db->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, tipo_usuario) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([1, 'Administradora Amora', 'admin@amora.com', '$2y$10$5I6ySAqTLl4i9M/lh0QPMO3T/4xxfaah/qGz9wgn9Irp2.luVxNYm', 'admin']);
            $stmt->execute([2, 'Larissa Benício', 'cliente@exemplo.com', '$2y$10$bn6OtSPtaZZz4HqMi6Zgz.wnetbIj/H6HJgrLOH6bjiPwxFmeIWiO', 'cliente']);
        }

        // Popular produtos se vazio
        $countProd = $db->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
        if ($countProd == 0) {
            $produtos = [
                [1, 1, 'Vela Aromática Flor de Cerejeira', 'Vela artesanal feita com cera vegetal de coco, pavio 100% algodão e essência premium de flor de cerejeira em pote de vidro decorado (180g). Queima limpa e duradoura.', 45.00, 15, 'assets/img/vela_cerejeira.svg'],
                [2, 1, 'Vela Aromática Lavanda Francesa', 'Vela calmante com óleo essencial de lavanda pura. Ideal para momentos de autocuidado e relaxamento no fim do dia.', 42.00, 20, 'assets/img/vela_lavanda.svg'],
                [3, 1, 'Vela Aromática Canela & Especiarias', 'Aroma acolhedor e envolvente de canela com toques de cravo e baunilha, perfeito para deixar o lar aconchegante.', 48.00, 12, 'assets/img/vela_canela.svg'],
                [4, 2, 'Sabonete Artesanal Argila Rosa & Rosas', 'Sabonete vegano hidratante saponificado a frio com argila rosa e extrato botânico de rosas mosqueta. Indicado para peles sensíveis.', 18.50, 30, 'assets/img/sabonete_rosa.svg'],
                [5, 2, 'Sabonete Esfoliante Café & Mel', 'Sabonete esfoliante natural enriquecido com pó de café selecionado e mel puro para renovação suave da pele.', 19.90, 25, 'assets/img/sabonete_cafe.svg'],
                [6, 2, 'Sabonete Calmante Aveia & Camomila', 'Fórmula suave hipoalergênica enriquecida com flocos de aveia e infusão concentrada de camomila.', 17.50, 28, 'assets/img/sabonete_aveia.svg'],
                [7, 3, 'Amigurumi Ursinho Teddy Caramelo', 'Ursinho fofo confeccionado à mão em crochê com fio 100% algodão mercerizado, olhos com trava de segurança e enchimento antialérgico (22cm).', 89.90, 8, 'assets/img/amigurumi_ursinho.svg'],
                [8, 3, 'Amigurumi Coelhinha com Vestido Floral', 'Coelhinha artesanal personalizável com laço de cetim e vestidinho removível feito à mão (25cm).', 95.00, 6, 'assets/img/amigurumi_coelho.svg'],
                [9, 3, 'Amigurumi Polvinho do Amor Rosa Bebê', 'Polvinho sensorial para bebês e recém-nascidos com tentáculos acolhedores que trazem sensação de conforto.', 55.00, 14, 'assets/img/amigurumi_polvo.svg'],
                [10, 4, 'Kit Presente Mimo Aromático', 'Caixa kraft artesanal decorada contendo 1 vela aromática de flor de cerejeira, 1 sabonete de argila rosa e 1 mini buquê de flores secas.', 79.90, 10, 'assets/img/kit_mimo.svg'],
                [11, 4, 'Kit Encanto Spa em Casa', 'Kit presente completo com 2 sabonetes artesanais, 1 vela aromática de lavanda e 1 toalhinha de lavabo bordada.', 119.00, 5, 'assets/img/kit_spa.svg']
            ];

            $stmtProd = $db->prepare("INSERT INTO produtos (id, categoria_id, nome, descricao, preco, estoque, imagem_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($produtos as $p) {
                $stmtProd->execute($p);
            }
        }
    } else {
        // Execução em MySQL
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $db->exec($schemaSql);
        $seedsSql = file_get_contents(__DIR__ . '/seeds.sql');
        $db->exec($seedsSql);
    }

    return true;
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    echo "Inicializando banco de dados...\n";
    if (inicializarBanco()) {
        echo "Banco de dados inicializado com sucesso!\n";
    }
}
