<?php
/**
 * Gerenciamento do Carrinho de Compras (UC04, RF06, CA04)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Tratamento de requisições POST para atualizar ou remover itens
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

    if ($acao === 'atualizar') {
        $key = $_POST['key'] ?? '';
        $quantidade = (int)($_POST['quantidade'] ?? 1);
        atualizarQuantidadeCarrinho($key, $quantidade);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => true,
                'totais' => getTotaisCarrinho()
            ]);
            exit;
        }
    } elseif ($acao === 'remover') {
        $key = $_POST['key'] ?? '';
        removerDoCarrinho($key);
        setFlash('info', 'Item removido do carrinho.');
    } elseif ($acao === 'limpar') {
        limparCarrinho();
        setFlash('info', 'Seu carrinho foi esvaziado.');
    }

    if (!$isAjax) {
        header('Location: ' . BASE_URL . '/carrinho.php');
        exit;
    }
}

$carrinho = getCarrinho();
$totais = getTotaisCarrinho();

$pageTitle = 'Meu Carrinho de Compras';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Carrinho de Compras</h1>
            <p style="color: var(--cor-marrom-claro);">Confira suas escolhas artesanais antes de prosseguir para o pagamento</p>
        </div>
        <?php if (!empty($carrinho)): ?>
            <form action="<?= BASE_URL ?>/carrinho.php" method="POST" onsubmit="return confirm('Tem certeza que deseja esvaziar o carrinho?');">
                <input type="hidden" name="acao" value="limpar">
                <button type="submit" class="btn btn-secondary btn-sm">
                    <i class="fa-regular fa-trash-can"></i> Esvaziar Carrinho
                </button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($carrinho)): ?>
        <div style="background: var(--cor-branco); border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); padding: 70px 20px; text-align: center; margin-bottom: 60px;">
            <div style="font-size: 3.5rem; color: var(--cor-rosa-medio); margin-bottom: 16px;">
                <i class="fa-solid fa-basket-shopping"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Seu carrinho está vazio</h3>
            <p style="color: var(--cor-marrom-claro); margin-bottom: 24px;">Que tal escolher uma vela aromática relaxante ou um presente especial?</p>
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-store"></i> Explorar Vitrine
            </a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Lista de Itens do Carrinho -->
            <div class="cart-items-card">
                <?php foreach ($carrinho as $item): ?>
                    <div class="cart-item" data-key="<?= sanitizar($item['key']) ?>" data-price="<?= $item['preco'] ?>">
                        <!-- Imagem Miniatura -->
                        <div class="cart-thumb">
                            <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url']) ?>" alt="<?= sanitizar($item['nome']) ?>">
                        </div>

                        <!-- Nome e Personalização -->
                        <div>
                            <div class="cart-item-title"><?= sanitizar($item['nome']) ?></div>
                            <div style="font-size: 0.85rem; color: var(--cor-marrom-claro);">
                                Preço unitário: <?= formatarMoeda((float)$item['preco']) ?>
                            </div>

                            <!-- Nota de Personalização (RF05) -->
                            <?php if (!empty($item['personalizacao'])): ?>
                                <div class="cart-custom-note">
                                    <i class="fa-solid fa-wand-magic-sparkles"></i> 
                                    <strong>Personalização:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Controle de Quantidade Dinâmico (CA04) -->
                        <div class="qty-control">
                            <button type="button" class="qty-btn btn-qty-minus">-</button>
                            <input type="text" class="qty-input" value="<?= $item['quantidade'] ?>" min="1" max="<?= $item['estoque_max'] ?>" readonly>
                            <button type="button" class="qty-btn btn-qty-plus">+</button>
                        </div>

                        <!-- Subtotal do Item -->
                        <div class="cart-item-total">
                            <?= formatarMoeda((float)($item['preco'] * $item['quantidade'])) ?>
                        </div>

                        <!-- Botão Remover -->
                        <div>
                            <form action="<?= BASE_URL ?>/carrinho.php" method="POST" style="margin: 0;">
                                <input type="hidden" name="acao" value="remover">
                                <input type="hidden" name="key" value="<?= sanitizar($item['key']) ?>">
                                <button type="submit" class="btn-remove-item" title="Remover item">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Resumo e Finalização de Compra -->
            <div class="cart-summary-card">
                <h3>Resumo do Pedido</h3>
                
                <div class="summary-row">
                    <span style="color: var(--cor-marrom-claro);">Subtotal de Itens:</span>
                    <span id="cart-subtotal-geral" style="font-weight: 600;"><?= $totais['valor_formatado'] ?></span>
                </div>
                
                <div class="summary-row">
                    <span style="color: var(--cor-marrom-claro);">Frete / Entrega:</span>
                    <span style="color: var(--cor-sucesso); font-weight: 600;">A calcular no checkout</span>
                </div>

                <div class="summary-row summary-total">
                    <span>Total:</span>
                    <span id="cart-total-geral"><?= $totais['valor_formatado'] ?></span>
                </div>

                <div style="margin-top: 24px; display: flex; flex-direction: column; gap: 12px;">
                    <a href="<?= BASE_URL ?>/checkout.php" class="btn btn-primary btn-block btn-lg">
                        <i class="fa-solid fa-lock"></i> Finalizar Compra
                    </a>
                    <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-secondary btn-block">
                        <i class="fa-solid fa-arrow-left"></i> Continuar Comprando
                    </a>
                </div>

                <div style="margin-top: 20px; font-size: 0.8rem; color: var(--cor-marrom-claro); text-align: center;">
                    <i class="fa-solid fa-shield-cat"></i> Compra segura com confirmação imediata.
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
