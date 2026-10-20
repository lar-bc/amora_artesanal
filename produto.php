<?php
/**
 * Detalhes do Produto, Personalização e Avaliações de Clientes (UC03, RF03, RF05, RF06)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$produtoId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$db = getDBConnection();
$stmt = $db->prepare("SELECT p.*, c.nome_categoria 
                      FROM produtos p 
                      INNER JOIN categorias c ON p.categoria_id = c.id 
                      WHERE p.id = ?");
$stmt->execute([$produtoId]);
$produto = $stmt->fetch();

if (!$produto) {
    setFlash('danger', 'Produto não encontrado.');
    header('Location: ' . BASE_URL . '/produtos.php');
    exit;
}

// Buscar avaliações deste produto
$stmtAval = $db->prepare("SELECT * FROM avaliacoes WHERE produto_id = ? ORDER BY id DESC");
$stmtAval->execute([$produtoId]);
$avaliacoes = $stmtAval->fetchAll();

// Processar inclusão no carrinho via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $quantidade = isset($_POST['quantidade']) ? (int)$_POST['quantidade'] : 1;
        $personalizacao = isset($_POST['personalizacao']) ? trim($_POST['personalizacao']) : '';

        if ($quantidade < 1) $quantidade = 1;
        if ($quantidade > $produto['estoque']) $quantidade = $produto['estoque'];

        if ($produto['estoque'] <= 0) {
            setFlash('danger', 'Desculpe, este produto está temporariamente sem estoque.');
        } else {
            if (adicionarAoCarrinho($produto['id'], $quantidade, $personalizacao)) {
                setFlash('success', 'Produto adicionado ao seu carrinho com sucesso!');
                header('Location: ' . BASE_URL . '/carrinho.php');
                exit;
            } else {
                setFlash('danger', 'Não foi possível adicionar o produto ao carrinho.');
            }
        }
    }
}

$pageTitle = $produto['nome'];
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.9rem; color: var(--cor-marrom-claro); margin-bottom: 24px;">
        <a href="<?= BASE_URL ?>/index.php">Início</a> &gt; 
        <a href="<?= BASE_URL ?>/produtos.php">Catálogo</a> &gt; 
        <a href="<?= BASE_URL ?>/produtos.php?categoria=<?= $produto['categoria_id'] ?>"><?= sanitizar($produto['nome_categoria']) ?></a> &gt; 
        <span><?= sanitizar($produto['nome']) ?></span>
    </div>

    <!-- Card Principal do Produto -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; background: var(--cor-branco); padding: 36px; border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); margin-bottom: 40px;">
        <!-- Imagem do Produto -->
        <div style="background: var(--cor-rosa-claro); border-radius: var(--raio-borda); display: flex; align-items: center; justify-content: center; padding: 30px; position: relative;">
            <span class="card-badge" style="top: 20px; left: 20px; font-size: 0.85rem; padding: 6px 14px;">
                <?= sanitizar($produto['nome_categoria']) ?>
            </span>
            <img src="<?= BASE_URL ?>/<?= sanitizar($produto['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="<?= sanitizar($produto['nome']) ?>" style="max-height: 380px; width: auto; object-fit: contain;">
        </div>

        <!-- Informações e Formulário de Compra/Personalização -->
        <div style="display: flex; flex-direction: column;">
            <span style="font-size: 0.85rem; color: var(--cor-rosa-queimado); font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px;">
                <?= sanitizar($produto['nome_categoria']) ?>
            </span>
            <h1 style="font-size: 2.2rem; color: var(--cor-marrom-suave); margin-bottom: 12px;"><?= sanitizar($produto['nome']) ?></h1>
            
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 20px;">
                <span class="product-price" style="font-size: 2rem;"><?= formatarMoeda((float)$produto['preco']) ?></span>
                <span class="stock-tag <?= $produto['estoque'] <= 5 ? 'low' : '' ?>" style="font-size: 0.9rem;">
                    <i class="fa-solid fa-box-archive"></i> <?= $produto['estoque'] ?> itens disponíveis
                </span>
            </div>

            <div style="color: var(--cor-marrom-texto); line-height: 1.7; margin-bottom: 28px; font-size: 0.96rem; border-top: 1px solid var(--cor-borda); border-bottom: 1px solid var(--cor-borda); padding: 18px 0;">
                <p><?= nl2br(sanitizar($produto['descricao'])) ?></p>
            </div>

            <!-- Formulário com Personalização (RF05) -->
            <?php if ($produto['estoque'] > 0): ?>
                <form action="<?= BASE_URL ?>/produto.php?id=<?= $produto['id'] ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <!-- Campo de Personalização do Produto Artesanal (RF05, UC03) -->
                    <div class="form-group" style="background: var(--cor-rosa-claro); padding: 18px; border-radius: var(--raio-borda); border: 1px solid var(--cor-rosa-medio); margin-bottom: 22px;">
                        <label for="personalizacao" class="form-label" style="display: flex; justify-content: space-between; align-items: center;">
                            <span>
                                <i class="fa-solid fa-wand-magic-sparkles" style="color: var(--cor-rosa-queimado);"></i> 
                                <strong>Personalize este produto (Opcional):</strong>
                            </span>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro);">Até 250 caracteres</span>
                        </label>
                        <textarea name="personalizacao" id="personalizacao" class="form-control" maxlength="250" rows="3" placeholder="Ex.: Nome para a etiqueta da vela ('Larissa'), cor favorita do laço, ou dedicatória especial para presente..."></textarea>
                        <small style="color: var(--cor-marrom-claro); display: block; margin-top: 6px; font-size: 0.8rem;">
                            ✨ Nós personalizaremos seu item artesanalmente antes do despacho!
                        </small>
                    </div>

                    <!-- Quantidade e Botão Adicionar ao Carrinho -->
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <div style="width: 120px;">
                            <label for="quantidade" class="form-label">Quantidade:</label>
                            <input type="number" name="quantidade" id="quantidade" class="form-control" value="1" min="1" max="<?= $produto['estoque'] ?>" style="text-align: center; font-weight: 600;">
                        </div>

                        <div style="flex-grow: 1; padding-top: 24px;">
                            <button type="submit" class="btn btn-primary btn-block btn-lg">
                                <i class="fa-solid fa-bag-shopping"></i> Adicionar ao Carrinho
                            </button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>Produto esgotado no momento. <a href="<?= BASE_URL ?>/contato.php"><strong>Fale conosco para encomendar sob medida!</strong></a></span>
                </div>
            <?php endif; ?>

            <!-- Destaques Adicionais -->
            <div style="margin-top: 30px; display: flex; gap: 20px; font-size: 0.85rem; color: var(--cor-marrom-claro); flex-wrap: wrap;">
                <div><i class="fa-solid fa-truck-fast" style="color: var(--cor-rosa-queimado);"></i> Envio com Rastreamento em Tempo Real</div>
                <div><i class="fa-solid fa-shield-halved" style="color: var(--cor-sucesso);"></i> Reembolso garantido em caso de atraso</div>
                <div><i class="fa-solid fa-gift" style="color: var(--cor-rosa-queimado);"></i> Embalagem pronta para presente</div>
            </div>
        </div>
    </div>

    <!-- Avaliações dos Clientes Deste Produto -->
    <div style="background: var(--cor-branco); padding: 36px; border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h3 style="font-size: 1.5rem; color: var(--cor-marrom-suave); margin-bottom: 4px;">
                    Avaliações de Quem Comprou
                </h3>
                <p style="color: var(--cor-marrom-claro); font-size: 0.9rem;">Opiniões reais e verificação de satisfação</p>
            </div>
            <div>
                <span class="badge-status entregue" style="font-size: 0.85rem; padding: 6px 14px;">
                    <i class="fa-solid fa-thumbs-up"></i> Clientes recomendam a Amora para amigos
                </span>
            </div>
        </div>

        <?php if (empty($avaliacoes)): ?>
            <div style="text-align: center; padding: 40px 20px; background: #fffcf9; border-radius: var(--raio-borda); border: 1px dashed var(--cor-borda);">
                <div style="font-size: 2rem; color: #f59e0b; margin-bottom: 8px;">★★★★★</div>
                <h4 style="color: var(--cor-marrom-suave); margin-bottom: 4px;">Seja o primeiro a avaliar este produto!</h4>
                <p style="font-size: 0.88rem; color: var(--cor-marrom-claro);">Compre agora e compartilhe sua experiência com outros apreciadores de aromas e artesanato.</p>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($avaliacoes as $av): ?>
                    <div style="background: #fffbf9; padding: 20px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda);">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <span style="color: #f59e0b; font-size: 1.1rem;">
                                    <?= str_repeat('★', $av['estrelas']) ?><?= str_repeat('☆', 5 - $av['estrelas']) ?>
                                </span>
                                <strong style="margin-left: 8px; color: var(--cor-marrom-suave); font-size: 0.95rem;"><?= sanitizar($av['nome_cliente']) ?></strong>
                                <span style="font-size: 0.75rem; color: var(--cor-sucesso); margin-left: 6px;">
                                    <i class="fa-solid fa-circle-check"></i> Compra Verificada
                                </span>
                            </div>

                            <?php if ($av['indicaria_amigo'] === 'sim'): ?>
                                <span style="font-size: 0.75rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 3px 10px; border-radius: 6px; font-weight: 600;">
                                    <i class="fa-solid fa-heart"></i> Indicaria para um amigo
                                </span>
                            <?php endif; ?>
                        </div>

                        <p style="color: var(--cor-marrom-texto); font-size: 0.92rem; line-height: 1.6; margin: 0;">
                            "<?= sanitizar($av['comentario']) ?>"
                        </p>
                        <div style="font-size: 0.72rem; color: var(--cor-marrom-claro); margin-top: 8px;">
                            Avaliado em <?= date('d/m/Y', strtotime($av['data_avaliacao'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
