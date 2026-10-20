<?php
/**
 * Funções Auxiliares do Sistema Amora Artesanal
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Formata um valor numérico para o padrão de moeda Real (R$).
 */
function formatarMoeda(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * Sanitiza entradas de texto para prevenir XSS e injeções.
 */
function sanitizar(string $dado): string {
    return htmlspecialchars(trim($dado), ENT_QUOTES, 'UTF-8');
}

/**
 * Define uma mensagem flash na sessão.
 */
function setFlash(string $tipo, string $mensagem): void {
    $_SESSION['flash'] = [
        'tipo' => $tipo, // 'success', 'danger', 'info', 'warning'
        'mensagem' => $mensagem
    ];
}

/**
 * Recupera e consome a mensagem flash da sessão.
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Gera ou recupera um token CSRF seguro.
 */
function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado.
 */
function validarCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Obtém os itens do carrinho armazenados na sessão.
 */
function getCarrinho(): array {
    if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }
    return $_SESSION['carrinho'];
}

/**
 * Calcula o total de itens e o valor total do carrinho.
 */
function getTotaisCarrinho(): array {
    $carrinho = getCarrinho();
    $qtdTotal = 0;
    $valorTotal = 0.0;

    foreach ($carrinho as $item) {
        $qtdTotal += $item['quantidade'];
        $valorTotal += $item['preco'] * $item['quantidade'];
    }

    return [
        'quantidade' => $qtdTotal,
        'valor_total' => $valorTotal,
        'valor_formatado' => formatarMoeda($valorTotal)
    ];
}

/**
 * Adiciona um produto ao carrinho com suporte à personalização (RF05, RF06).
 */
function adicionarAoCarrinho(int $produtoId, int $quantidade, string $personalizacao = ''): bool {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, nome, preco, imagem_url, estoque FROM produtos WHERE id = ?");
    $stmt->execute([$produtoId]);
    $produto = $stmt->fetch();

    if (!$produto) {
        return false;
    }

    $carrinho = getCarrinho();
    $personalizacaoLimpa = trim($personalizacao);
    
    // Chave única para o item no carrinho baseada no produto e personalização
    $itemKey = $produtoId . '_' . md5($personalizacaoLimpa);

    if (isset($carrinho[$itemKey])) {
        $novaQtd = $carrinho[$itemKey]['quantidade'] + $quantidade;
        // Limitar ao estoque
        if ($novaQtd > $produto['estoque']) {
            $novaQtd = $produto['estoque'];
        }
        $carrinho[$itemKey]['quantidade'] = $novaQtd;
    } else {
        $qtdFinal = min($quantidade, $produto['estoque']);
        if ($qtdFinal <= 0) return false;

        $carrinho[$itemKey] = [
            'key' => $itemKey,
            'produto_id' => $produto['id'],
            'nome' => $produto['nome'],
            'preco' => (float)$produto['preco'],
            'imagem_url' => $produto['imagem_url'] ?? 'assets/img/placeholder.svg',
            'quantidade' => $qtdFinal,
            'personalizacao' => $personalizacaoLimpa,
            'estoque_max' => (int)$produto['estoque']
        ];
    }

    $_SESSION['carrinho'] = $carrinho;
    return true;
}

/**
 * Atualiza a quantidade de um item no carrinho.
 */
function atualizarQuantidadeCarrinho(string $itemKey, int $novaQuantidade): bool {
    $carrinho = getCarrinho();
    if (!isset($carrinho[$itemKey])) {
        return false;
    }

    if ($novaQuantidade <= 0) {
        unset($carrinho[$itemKey]);
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT estoque FROM produtos WHERE id = ?");
        $stmt->execute([$carrinho[$itemKey]['produto_id']]);
        $estoque = (int)$stmt->fetchColumn();

        $carrinho[$itemKey]['quantidade'] = min($novaQuantidade, $estoque);
    }

    $_SESSION['carrinho'] = $carrinho;
    return true;
}

/**
 * Remove um item específico do carrinho.
 */
function removerDoCarrinho(string $itemKey): void {
    $carrinho = getCarrinho();
    if (isset($carrinho[$itemKey])) {
        unset($carrinho[$itemKey]);
        $_SESSION['carrinho'] = $carrinho;
    }
}

/**
 * Limpa todos os itens do carrinho.
 */
function limparCarrinho(): void {
    $_SESSION['carrinho'] = [];
}

/**
 * Busca todas as categorias disponíveis.
 */
function getCategorias(): array {
    $db = getDBConnection();
    return $db->query("SELECT * FROM categorias ORDER BY nome_categoria ASC")->fetchAll();
}

/**
 * Busca produtos com suporte a filtro de categoria e busca por nome (RF03, RF04).
 */
function getProdutos(?int $categoriaId = null, ?string $busca = null, ?int $limite = null): array {
    $db = getDBConnection();
    $sql = "SELECT p.*, c.nome_categoria 
            FROM produtos p 
            INNER JOIN categorias c ON p.categoria_id = c.id 
            WHERE 1=1";
    $params = [];

    if ($categoriaId !== null && $categoriaId > 0) {
        $sql .= " AND p.categoria_id = ?";
        $params[] = $categoriaId;
    }

    if (!empty($busca)) {
        $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
        $termo = '%' . trim($busca) . '%';
        $params[] = $termo;
        $params[] = $termo;
    }

    $sql .= " ORDER BY p.id DESC";

    if ($limite !== null && $limite > 0) {
        $sql .= " LIMIT " . (int)$limite;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Retorna as coordenadas geográficas (latitude e longitude) para a entrega
 * com base na cidade, estado e bairro do cliente.
 */
function getCoordenadasEntrega(?string $cidade = '', ?string $estado = '', ?string $bairro = ''): array {
    $locais = [
        'flodoaldo pontes pinto' => [-8.7512, -63.8825],
        'olaria'                 => [-8.7580, -63.9010],
        'rio madeira'            => [-8.7450, -63.8720],
        'pedrinhas'              => [-8.7590, -63.9100],
        'arigolândia'            => [-8.7640, -63.9150],
        'baixa da união'         => [-8.7680, -63.9080],
        'centro'                 => [-8.7610, -63.9030],
        'nova porto velho'       => [-8.7720, -63.8850],
        'tiradentes'             => [-8.7850, -63.8650],
        'tancredo neves'         => [-8.7890, -63.8550],
        'socialista'             => [-8.7950, -63.8450],
        'nacional'               => [-8.7350, -63.8920],
        'porto velho'            => [-8.7550, -63.8780],
        'candeias do jamari'     => [-8.7905, -63.6986],
        'ariquemes'              => [-9.9133, -63.0408],
        'jaru'                   => [-10.4389, -62.4664],
        'ji-paraná'              => [-10.8828, -61.9444],
        'ji-parana'              => [-10.8828, -61.9444],
        'cacoal'                 => [-11.4386, -61.4472],
        'pimenta bueno'          => [-11.6725, -61.1936],
        'rolim de moura'         => [-11.7275, -61.7786],
        'vilhena'                => [-12.7406, -60.1458],
        'guajará-mirim'          => [-10.7833, -65.3333],
        'rio branco'             => [-9.9749, -67.8243],
        'manaus'                 => [-3.1190, -60.0217],
        'cuiabá'                 => [-15.6010, -56.0974],
        'cuiaba'                 => [-15.6010, -56.0974],
        'brasília'               => [-15.7975, -47.8919],
        'brasilia'               => [-15.7975, -47.8919],
        'são paulo'              => [-23.5505, -46.6333],
        'sao paulo'              => [-23.5505, -46.6333],
        'rio de janeiro'         => [-22.9068, -43.1729],
        'curitiba'               => [-25.4284, -49.2733],
        'belo horizonte'         => [-19.9167, -43.9345],
        'salvador'               => [-12.9714, -38.5014],
        'fortaleza'              => [-3.7172, -38.5434],
        'goiânia'                => [-16.6869, -49.2648],
        'goiania'                => [-16.6869, -49.2648],
    ];

    $bairroLimpo = mb_strtolower(trim($bairro ?? ''));
    if (!empty($bairroLimpo) && isset($locais[$bairroLimpo])) {
        return $locais[$bairroLimpo];
    }

    $cidadeLimpa = mb_strtolower(trim($cidade ?? ''));
    if (!empty($cidadeLimpa) && isset($locais[$cidadeLimpa])) {
        return $locais[$cidadeLimpa];
    }

    $uf = strtoupper(trim($estado ?? ''));
    $estados = [
        'RO' => [-8.7550, -63.8780],
        'AC' => [-9.9749, -67.8243],
        'AM' => [-3.1190, -60.0217],
        'MT' => [-15.6010, -56.0974],
        'DF' => [-15.7975, -47.8919],
        'SP' => [-23.5505, -46.6333],
        'RJ' => [-22.9068, -43.1729],
        'PR' => [-25.4284, -49.2733],
        'MG' => [-19.9167, -43.9345],
        'BA' => [-12.9714, -38.5014],
        'CE' => [-3.7172, -38.5434],
        'GO' => [-16.6869, -49.2648],
        'RS' => [-30.0346, -51.2177],
        'SC' => [-27.5954, -48.5480],
    ];
    if (isset($estados[$uf])) {
        return $estados[$uf];
    }

    return [-8.7520, -63.8810];
}

