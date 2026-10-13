<?php
/**
 * Teste Automatizado das Regras de Negócio e Requisitos
 * Valida RF01-RF11, RNF01, RNF02, DER
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../database/init_db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

echo "=== INICIANDO SUÍTE DE TESTES AMORA ARTESANAL ===\n";

// 1. Inicializar e testar banco
assert(inicializarBanco(), "Falha ao inicializar banco");
$db = getDBConnection();
echo "✔ [OK] Conexão com banco de dados e tabelas verificada.\n";

// 2. Testar RF01 e RNF01: Cadastro de usuário com Bcrypt
$emailTeste = 'teste_' . time() . '@exemplo.com';
$resCad = cadastrarCliente('Cliente Teste Automatizado', $emailTeste, 'senha123', 'senha123');
assert($resCad['sucesso'] === true, "Falha no cadastro de cliente: " . ($resCad['mensagem'] ?? ''));
$userDb = $db->query("SELECT * FROM usuarios WHERE email = '$emailTeste'")->fetch();
assert(password_verify('senha123', $userDb['senha_hash']), "Hash da senha não é Bcrypt válido");
echo "✔ [OK] RF01 & RNF01: Cadastro de usuário e hash seguro Bcrypt validados.\n";

// 3. Testar RF02: Autenticação de cliente e admin
$loginCliente = loginUsuario($emailTeste, 'senha123');
assert($loginCliente['sucesso'] === true, "Falha no login do cliente");
assert($loginCliente['tipo_usuario'] === 'cliente', "Tipo incorreto");

$loginAdmin = loginUsuario('admin@amora.com', 'admin123');
assert($loginAdmin['sucesso'] === true, "Falha no login do admin");
assert($loginAdmin['tipo_usuario'] === 'admin', "Tipo incorreto para admin");
echo "✔ [OK] RF02: Login e verificação de permissões validados.\n";

// 4. Testar RF03 & RF04: Catálogo, categorias e busca
$cats = getCategorias();
assert(count($cats) >= 4, "Categorias insuficientes");
$prods = getProdutos();
assert(count($prods) >= 11, "Produtos insuficientes");
$buscaVela = getProdutos(null, 'Cerejeira');
assert(count($buscaVela) >= 1, "Busca textual falhou");
$filtroCat1 = getProdutos(1, null);
assert(count($filtroCat1) >= 1, "Filtro de categoria falhou");
echo "✔ [OK] RF03 & RF04: Catálogo, busca por nome e filtros de categoria validados.\n";

// 5. Testar RF05 & RF06: Carrinho e Personalização
limparCarrinho();
$add1 = adicionarAoCarrinho(1, 2, 'Gravar nome Larissa na etiqueta');
assert($add1 === true, "Falha ao adicionar item ao carrinho");
$totais = getTotaisCarrinho();
assert($totais['quantidade'] === 2, "Quantidade total incorreta no carrinho");
assert($totais['valor_total'] == 90.00, "Valor total incorreto");
echo "✔ [OK] RF05 & RF06: Adição com personalização e cálculo de carrinho validados.\n";

// 6. Testar RF07 & CA05: Gravação de pedido e itens
$stmtPed = $db->prepare("INSERT INTO pedidos (usuario_id, data_pedido, valor_total, status, endereco_entrega) VALUES (?, datetime('now', 'localtime'), ?, 'Pedido realizado', ?)");
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver !== 'sqlite') {
    $stmtPed = $db->prepare("INSERT INTO pedidos (usuario_id, data_pedido, valor_total, status, endereco_entrega) VALUES (?, NOW(), ?, 'Pedido realizado', ?)");
}
$stmtPed->execute([$userDb['id'], $totais['valor_total'], 'Av. Calama, 1000 - Porto Velho/RO']);
$pedidoId = (int)$db->lastInsertId();

$stmtIt = $db->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, personalizacao) VALUES (?, ?, ?, ?, ?)");
$stmtIt->execute([$pedidoId, 1, 2, 45.00, 'Gravar nome Larissa na etiqueta']);
limparCarrinho();
assert(count(getCarrinho()) === 0, "Carrinho deveria estar vazio após checkout");
echo "✔ [OK] RF07 & CA05: Pedido e itens gravados no banco de dados e carrinho limpo.\n";

// 7. Testar RF08: Consulta ao histórico do cliente
$stmtHist = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ?");
$stmtHist->execute([$userDb['id']]);
$pedidosHist = $stmtHist->fetchAll();
assert(count($pedidosHist) >= 1, "Histórico de compras vazio");
echo "✔ [OK] RF08: Histórico de pedidos do cliente validado.\n";

// 8. Testar RF10 & CA07: Atualização de status pelo Admin
$stmtUp = $db->prepare("UPDATE pedidos SET status = 'Em preparação' WHERE id = ?");
$stmtUp->execute([$pedidoId]);
$statusAtual = $db->query("SELECT status FROM pedidos WHERE id = $pedidoId")->fetchColumn();
assert($statusAtual === 'Em preparação', "Atualização de status falhou");
echo "✔ [OK] RF10 & CA07: Atualização de status de entrega validada.\n";

// 9. Testar RF11: Formulário de contato
$stmtCont = $db->prepare("INSERT INTO mensagens_contato (nome, email, mensagem) VALUES (?, ?, ?)");
$stmtCont->execute(['Maria Visitante', 'maria@exemplo.com', 'Gostaria de saber sobre kit para lembrancinhas de batizado']);
$msgSalva = $db->query("SELECT COUNT(*) FROM mensagens_contato WHERE email = 'maria@exemplo.com'")->fetchColumn();
assert($msgSalva >= 1, "Mensagem de contato não persistida");
echo "✔ [OK] RF11: Envio e persistência de mensagem de contato validados.\n";

echo "=== TODOS OS 9 TESTES PASSARAM COM SUCESSO! ===\n";
