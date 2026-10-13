<?php
/**
 * Checkout e Finalização do Pedido (UC05, RF07, CA05, RNF02)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Exigir login do cliente
if (!usuarioLogado()) {
    setFlash('warning', 'Para finalizar sua compra com segurança, por favor faça login ou crie sua conta.');
    header('Location: ' . BASE_URL . '/login.php?redirect=checkout');
    exit;
}

$carrinho = getCarrinho();
$totais = getTotaisCarrinho();

// Se o carrinho estiver vazio, voltar para a vitrine
if (empty($carrinho)) {
    setFlash('warning', 'Seu carrinho está vazio. Adicione produtos antes de ir para o checkout.');
    header('Location: ' . BASE_URL . '/produtos.php');
    exit;
}

$usuario = getUsuarioLogado();
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        // Validação e sanitização de dados no servidor (RNF02)
        $cep = trim($_POST['cep'] ?? '');
        $logradouro = trim($_POST['logradouro'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $pagamento = trim($_POST['pagamento'] ?? 'Pix');

        if (empty($logradouro) || empty($numero) || empty($bairro) || empty($cidade) || empty($cep)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios do endereço de entrega.';
        } else {
            $enderecoFormatado = sprintf(
                "%s, nº %s%s - %s, %s/%s - CEP: %s (Pagamento: %s)",
                $logradouro,
                $numero,
                !empty($complemento) ? " ($complemento)" : "",
                $bairro,
                $cidade,
                $estado,
                $cep,
                $pagamento
            );

            $db = getDBConnection();

            try {
                $db->beginTransaction();

                // 1. Gravar Pedido (RF07)
                $stmtPedido = $db->prepare("INSERT INTO pedidos (usuario_id, data_pedido, valor_total, status, endereco_entrega) 
                                           VALUES (?, datetime('now', 'localtime'), ?, 'Pedido realizado', ?)");
                // Nota: se MySQL, o default é CURRENT_TIMESTAMP
                $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver !== 'sqlite') {
                    $stmtPedido = $db->prepare("INSERT INTO pedidos (usuario_id, data_pedido, valor_total, status, endereco_entrega) 
                                               VALUES (?, NOW(), ?, 'Pedido realizado', ?)");
                }

                $stmtPedido->execute([
                    $usuario['id'],
                    $totais['valor_total'],
                    $enderecoFormatado
                ]);

                $pedidoId = (int)$db->lastInsertId();

                // 2. Gravar Itens do Pedido com personalização e atualizar estoque
                $stmtItem = $db->prepare("INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario, personalizacao) 
                                         VALUES (?, ?, ?, ?, ?)");
                $stmtEstoque = $db->prepare("UPDATE produtos SET estoque = MAX(0, estoque - ?) WHERE id = ?");

                foreach ($carrinho as $item) {
                    $stmtItem->execute([
                        $pedidoId,
                        $item['produto_id'],
                        $item['quantidade'],
                        $item['preco'],
                        $item['personalizacao'] ?? null
                    ]);

                    $stmtEstoque->execute([
                        $item['quantidade'],
                        $item['produto_id']
                    ]);
                }

                $db->commit();

                // 3. Limpar carrinho após confirmação de compra (CA05)
                limparCarrinho();

                setFlash('success', "Pedido #{$pedidoId} confirmado com sucesso! Já começamos a cuidar dos detalhes do seu produto artesanal.");
                header('Location: ' . BASE_URL . '/pedidos.php');
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $erro = 'Ocorreu um erro ao processar seu pedido: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Finalizar Pedido / Checkout';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 2rem; margin-bottom: 4px;">Finalização de Pedido</h1>
        <p style="color: var(--cor-marrom-claro);">Informe onde deseja receber sua encomenda e conclua a compra</p>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= sanitizar($erro) ?></span>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px;">
        <!-- Formulário de Endereço e Pagamento -->
        <div style="background: var(--cor-branco); padding: 30px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
            <h3 style="margin-bottom: 20px; font-size: 1.3rem; color: var(--cor-rosa-queimado);">
                <i class="fa-solid fa-location-dot"></i> Dados de Entrega
            </h3>

            <form action="<?= BASE_URL ?>/checkout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                    <div class="form-group">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" name="cep" id="cep" class="form-control" placeholder="76800-000" required value="76820-120">
                    </div>
                    <div class="form-group">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" placeholder="Porto Velho" required value="Porto Velho">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="logradouro" class="form-label">Rua / Avenida *</label>
                        <input type="text" name="logradouro" id="logradouro" class="form-control" placeholder="Av. Calama" required value="Av. Tiradentes">
                    </div>
                    <div class="form-group">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" name="numero" id="numero" class="form-control" placeholder="123" required value="3000">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" placeholder="Centro" required value="Embratel">
                    </div>
                    <div class="form-group">
                        <label for="estado" class="form-label">Estado *</label>
                        <input type="text" name="estado" id="estado" class="form-control" placeholder="RO" required value="RO">
                    </div>
                    <div class="form-group">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" placeholder="Apto 101, Bloco B" value="Apto 204">
                    </div>
                </div>

                <h3 style="margin: 28px 0 16px; font-size: 1.3rem; color: var(--cor-rosa-queimado);">
                    <i class="fa-solid fa-credit-card"></i> Forma de Pagamento
                </h3>

                <div style="display: flex; gap: 14px; flex-direction: column; margin-bottom: 24px;">
                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--cor-rosa-medio); background: var(--cor-rosa-claro); border-radius: var(--raio-borda); cursor: pointer;">
                        <input type="radio" name="pagamento" value="Pix" checked style="accent-color: var(--cor-rosa-queimado); transform: scale(1.2);">
                        <i class="fa-brands fa-pix" style="color: #32bcad; font-size: 1.3rem;"></i>
                        <div>
                            <strong>Pix (Chave Instantânea)</strong>
                            <div style="font-size: 0.82rem; color: var(--cor-marrom-claro);">Aprovação imediata e envio prioritário da sua encomenda</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--cor-borda); background: #fff; border-radius: var(--raio-borda); cursor: pointer;">
                        <input type="radio" name="pagamento" value="Cartão de Crédito" style="accent-color: var(--cor-rosa-queimado); transform: scale(1.2);">
                        <i class="fa-regular fa-credit-card" style="color: var(--cor-rosa-queimado); font-size: 1.3rem;"></i>
                        <div>
                            <strong>Cartão de Crédito</strong>
                            <div style="font-size: 0.82rem; color: var(--cor-marrom-claro);">Parcelamento em até 3x sem juros</div>
                        </div>
                    </label>

                    <label style="display: flex; align-items: center; gap: 12px; padding: 14px; border: 1px solid var(--cor-borda); background: #fff; border-radius: var(--raio-borda); cursor: pointer;">
                        <input type="radio" name="pagamento" value="Boleto Bancário" style="accent-color: var(--cor-rosa-queimado); transform: scale(1.2);">
                        <i class="fa-solid fa-barcode" style="color: var(--cor-marrom-suave); font-size: 1.3rem;"></i>
                        <div>
                            <strong>Boleto Bancário</strong>
                            <div style="font-size: 0.82rem; color: var(--cor-marrom-claro);">Vencimento em 3 dias úteis</div>
                        </div>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fa-solid fa-check"></i> Confirmar e Gravar Pedido
                </button>
            </form>
        </div>

        <!-- Resumo da Compra no Checkout -->
        <div style="background: var(--cor-branco); padding: 26px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); height: fit-content;">
            <h3 style="font-size: 1.3rem; margin-bottom: 16px;">Itens Selecionados</h3>

            <div style="max-height: 280px; overflow-y: auto; margin-bottom: 20px;">
                <?php foreach ($carrinho as $item): ?>
                    <div style="display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--cor-borda); align-items: center;">
                        <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url']) ?>" alt="<?= sanitizar($item['nome']) ?>" style="width: 50px; height: 50px; border-radius: 8px; background: var(--cor-rosa-claro); padding: 4px; object-fit: contain;">
                        <div style="flex-grow: 1;">
                            <div style="font-weight: 600; font-size: 0.9rem;"><?= sanitizar($item['nome']) ?></div>
                            <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                Qtd: <?= $item['quantidade'] ?> &times; <?= formatarMoeda((float)$item['preco']) ?>
                            </div>
                            <?php if (!empty($item['personalizacao'])): ?>
                                <div style="font-size: 0.75rem; color: var(--cor-rosa-queimado);">
                                    <i class="fa-solid fa-sparkles"></i> Personalização: <?= sanitizar($item['personalizacao']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--cor-rosa-queimado);">
                            <?= formatarMoeda((float)($item['preco'] * $item['quantidade'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row">
                <span>Subtotal:</span>
                <strong><?= $totais['valor_formatado'] ?></strong>
            </div>
            <div class="summary-row">
                <span>Frete Especial:</span>
                <strong style="color: var(--cor-sucesso);">Grátis</strong>
            </div>
            <div class="summary-row summary-total">
                <span>Total a Pagar:</span>
                <span><?= $totais['valor_formatado'] ?></span>
            </div>

            <div style="margin-top: 16px; font-size: 0.82rem; color: var(--cor-marrom-claro); text-align: center;">
                Cliente: <strong><?= sanitizar($usuario['nome']) ?></strong> (<?= sanitizar($usuario['email']) ?>)
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
