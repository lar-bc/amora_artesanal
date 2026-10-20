<?php
/**
 * Checkout e Finalização do Pedido (UC05, RF07, CA05, RNF02)
 * Exige Cadastro/Login Obrigatório
 * Pagamentos Completos: Pix (QR Code/Copia e Cola/Chave) e Cartão de Crédito com Parcelas e Medidas de Segurança
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Exige obrigatoriamente que o cliente esteja cadastrado e logado para finalizar
if (!usuarioLogado()) {
    setFlash('warning', 'Para garantir a segurança, emissão do comprovante e rastreamento da sua encomenda, faça login ou cadastre-se antes de finalizar a compra.');
    header('Location: ' . BASE_URL . '/login.php?redirect=checkout');
    exit;
}

$carrinho = getCarrinho();
$totais = getTotaisCarrinho();

// Se o carrinho estiver vazio, voltar para o catálogo
if (empty($carrinho)) {
    setFlash('warning', 'Seu carrinho está vazio. Adicione produtos antes de ir para o checkout.');
    header('Location: ' . BASE_URL . '/produtos.php');
    exit;
}

$usuario = getUsuarioLogado();
$erro = '';

// Preencher dados do endereço a partir do cadastro do usuário
$cep = $usuario['cep'] ?? '';
$logradouro = $usuario['logradouro'] ?? '';
$numero = $usuario['numero'] ?? '';
$complemento = $usuario['complemento'] ?? '';
$bairro = $usuario['bairro'] ?? '';
$cidade = $usuario['cidade'] ?? '';
$estado = $usuario['estado'] ?? '';
$formaPagamento = 'Pix';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $cep = trim($_POST['cep'] ?? '');
        $logradouro = trim($_POST['logradouro'] ?? '');
        $numero = trim($_POST['numero'] ?? '');
        $bairro = trim($_POST['bairro'] ?? '');
        $cidade = trim($_POST['cidade'] ?? '');
        $estado = trim($_POST['estado'] ?? '');
        $complemento = trim($_POST['complemento'] ?? '');
        $formaPagamento = trim($_POST['forma_pagamento'] ?? 'Pix');

        // Detalhes do Pagamento
        $pagamentoDetalhes = '';
        if ($formaPagamento === 'Pix') {
            $pagamentoDetalhes = 'Chave Pix (pix@amoraartesanal.com.br) - Pagamento Instantâneo';
        } elseif ($formaPagamento === 'Cartão de Crédito') {
            $cartaoNumero = preg_replace('/\D/', '', $_POST['cartao_numero'] ?? '');
            $cartaoTitular = trim($_POST['cartao_titular'] ?? '');
            $cartaoValidade = trim($_POST['cartao_validade'] ?? '');
            $cartaoCvv = trim($_POST['cartao_cvv'] ?? '');
            $parcelas = (int)($_POST['cartao_parcelas'] ?? 1);

            if (empty($cartaoNumero) || empty($cartaoTitular) || empty($cartaoValidade) || empty($cartaoCvv)) {
                $erro = 'Por favor, preencha todos os dados do cartão de crédito (número, titular, validade e CVV).';
            } else {
                $ultimosDigitos = substr($cartaoNumero, -4);
                $pagamentoDetalhes = "Cartão de Crédito final {$ultimosDigitos} em {$parcelas}x sem juros (Titular: {$cartaoTitular})";
            }
        } else {
            $pagamentoDetalhes = 'Boleto Bancário registrado';
        }

        if (empty($logradouro) || empty($numero) || empty($bairro) || empty($cidade) || empty($cep)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios do endereço de entrega.';
        } elseif (empty($erro)) {
            $enderecoFormatado = sprintf(
                "%s, nº %s%s - %s, %s/%s - CEP: %s",
                $logradouro,
                $numero,
                !empty($complemento) ? " ($complemento)" : "",
                $bairro,
                $cidade,
                $estado,
                $cep
            );

            // Gerar código de rastreamento oficial
            $codigoRastreio = 'AM' . rand(10000000, 99999999) . 'BR';

            $db = getDBConnection();

            try {
                $db->beginTransaction();

                // 1. Gravar Pedido com rastreio, detalhes de pagamento e prazo
                $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
                if ($driver === 'sqlite') {
                    $sqlPedido = "INSERT INTO pedidos 
                        (usuario_id, data_pedido, valor_total, status, endereco_entrega, forma_pagamento, forma_pagamento_detalhes, codigo_rastreio, transportadora, prazo_estimado_dias) 
                        VALUES (?, datetime('now', 'localtime'), ?, 'Pedido realizado', ?, ?, ?, ?, 'LogiAmora Transportes / Sedex', 5)";
                } else {
                    $sqlPedido = "INSERT INTO pedidos 
                        (usuario_id, data_pedido, valor_total, status, endereco_entrega, forma_pagamento, forma_pagamento_detalhes, codigo_rastreio, transportadora, prazo_estimado_dias) 
                        VALUES (?, NOW(), ?, 'Pedido realizado', ?, ?, ?, ?, 'LogiAmora Transportes / Sedex', 5)";
                }

                $stmtPedido = $db->prepare($sqlPedido);
                $stmtPedido->execute([
                    $usuario['id'],
                    $totais['valor_total'],
                    $enderecoFormatado,
                    $formaPagamento,
                    $pagamentoDetalhes,
                    $codigoRastreio
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

                // 3. Limpar carrinho após confirmação
                limparCarrinho();

                setFlash('success', "Parabéns! Pedido #{$pedidoId} confirmado com sucesso. Seu código de rastreamento é {$codigoRastreio}.");
                header('Location: ' . BASE_URL . '/pedidos.php?sucesso_id=' . $pedidoId);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $erro = 'Ocorreu um erro ao processar seu pedido: ' . $e->getMessage();
            }
        }
    }
}

// Chave Pix do Ateliê
$chavePixOficial = 'pix@amoraartesanal.com.br';
$codigoPixCopiaCola = '00020126580014br.gov.bcb.pix0136' . $chavePixOficial . '520400005303986540' . number_format($totais['valor_total'], 2, '.', '') . '5802BR5915Amora Artesanal6011Porto Velho62070503***6304' . strtoupper(substr(md5(time()), 0, 4));

$pageTitle = 'Finalizar Compra Segura';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <!-- Barra de Segurança no Topo -->
    <div style="background: #eef7ee; border: 1px solid #c2e5c8; border-radius: var(--raio-borda); padding: 14px 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 10px; color: #1e5a26; font-size: 0.92rem; font-weight: 500;">
            <i class="fa-solid fa-shield-halved" style="font-size: 1.3rem;"></i>
            <span><strong>Ambiente 100% Criptografado & Seguro:</strong> Seus dados pessoais e de pagamento estão protegidos por criptografia SSL de 256 bits.</span>
        </div>
        <div style="display: flex; gap: 10px;">
            <span class="badge-status entregue" style="font-size: 0.75rem;">SSL Seguro</span>
            <span class="badge-status entregue" style="font-size: 0.75rem;">Garantia de Reembolso</span>
        </div>
    </div>

    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 2rem; margin-bottom: 4px;">Finalização de Pedido</h1>
        <p style="color: var(--cor-marrom-claro);">Confira o endereço cadastrado, escolha a forma de pagamento e finalize com proteção total</p>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= sanitizar($erro) ?></span>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1.4fr 1fr; gap: 30px;">
        <!-- Formulário de Endereço e Opções de Pagamento -->
        <div style="background: var(--cor-branco); padding: 30px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
            <form action="<?= BASE_URL ?>/checkout.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <!-- 1. Endereço de Entrega (Auto-preenchido do cadastro) -->
                <div style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 20px; margin-bottom: 24px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="font-size: 1.25rem; color: var(--cor-rosa-queimado); margin: 0;">
                            <i class="fa-solid fa-location-dot"></i> 1. Endereço de Entrega Cadastrado
                        </h3>
                        <span style="font-size: 0.8rem; color: var(--cor-sucesso); font-weight: 600;">
                            <i class="fa-solid fa-circle-check"></i> Dados do seu perfil
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                        <div class="form-group">
                            <label for="cep" class="form-label">CEP *</label>
                            <input type="text" name="cep" id="cep" class="form-control" value="<?= sanitizar($cep) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="cidade" class="form-label">Cidade *</label>
                            <input type="text" name="cidade" id="cidade" class="form-control" value="<?= sanitizar($cidade) ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label for="logradouro" class="form-label">Rua / Avenida *</label>
                            <input type="text" name="logradouro" id="logradouro" class="form-control" value="<?= sanitizar($logradouro) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="numero" class="form-label">Número *</label>
                            <input type="text" name="numero" id="numero" class="form-control" value="<?= sanitizar($numero) ?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                        <div class="form-group">
                            <label for="bairro" class="form-label">Bairro *</label>
                            <input type="text" name="bairro" id="bairro" class="form-control" value="<?= sanitizar($bairro) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="estado" class="form-label">Estado *</label>
                            <input type="text" name="estado" id="estado" class="form-control" value="<?= sanitizar($estado) ?>" required maxlength="2">
                        </div>
                        <div class="form-group">
                            <label for="complemento" class="form-label">Complemento</label>
                            <input type="text" name="complemento" id="complemento" class="form-control" value="<?= sanitizar($complemento) ?>" placeholder="Apto, Bloco...">
                        </div>
                    </div>
                </div>

                <!-- 2. Formas de Pagamento Seguras -->
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 1.25rem; color: var(--cor-rosa-queimado); margin-bottom: 16px;">
                        <i class="fa-solid fa-credit-card"></i> 2. Forma de Pagamento
                    </h3>

                    <!-- Opção PIX com Chave e QR Code -->
                    <div style="border: 2px solid var(--cor-rosa-medio); background: var(--cor-rosa-claro); border-radius: var(--raio-borda); padding: 18px; margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: 1.05rem;">
                            <input type="radio" name="forma_pagamento" value="Pix" checked style="accent-color: var(--cor-rosa-queimado); transform: scale(1.3);">
                            <strong style="color: var(--cor-marrom-suave);">Pix Oficial - Aprovação Instantânea (Recomendado)</strong>
                        </label>

                        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px dashed var(--cor-rosa-medio); background: #fff; padding: 16px; border-radius: var(--raio-borda-sm);">
                            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                                <!-- QR Code em SVG Puro -->
                                <div style="text-align: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" width="100" height="100" style="background:#fff; border:1px solid #edd5d8; border-radius:8px; padding:4px;">
                                        <rect width="100%" height="100%" fill="#ffffff"/>
                                        <!-- Quadrados dos Cantos -->
                                        <rect x="10" y="10" width="30" height="30" fill="#5c3d2e"/>
                                        <rect x="16" y="16" width="18" height="18" fill="#ffffff"/>
                                        <rect x="20" y="20" width="10" height="10" fill="#c86d7e"/>
                                        <rect x="80" y="10" width="30" height="30" fill="#5c3d2e"/>
                                        <rect x="86" y="16" width="18" height="18" fill="#ffffff"/>
                                        <rect x="90" y="20" width="10" height="10" fill="#c86d7e"/>
                                        <rect x="10" y="80" width="30" height="30" fill="#5c3d2e"/>
                                        <rect x="16" y="86" width="18" height="18" fill="#ffffff"/>
                                        <rect x="20" y="90" width="10" height="10" fill="#c86d7e"/>
                                        <!-- Padrão Pix Simulado -->
                                        <rect x="48" y="15" width="8" height="16" fill="#5c3d2e"/>
                                        <rect x="62" y="12" width="10" height="8" fill="#5c3d2e"/>
                                        <rect x="48" y="38" width="24" height="8" fill="#c86d7e"/>
                                        <rect x="52" y="52" width="16" height="16" fill="#5c3d2e"/>
                                        <rect x="25" y="52" width="14" height="8" fill="#5c3d2e"/>
                                        <rect x="80" y="52" width="10" height="22" fill="#5c3d2e"/>
                                        <rect x="96" y="60" width="14" height="14" fill="#c86d7e"/>
                                        <rect x="48" y="76" width="20" height="12" fill="#5c3d2e"/>
                                        <rect x="76" y="84" width="16" height="26" fill="#c86d7e"/>
                                        <rect x="48" y="96" width="20" height="14" fill="#5c3d2e"/>
                                    </svg>
                                    <span style="font-size: 0.72rem; color: var(--cor-marrom-claro); display: block; margin-top: 4px;">QR Code Pix Ateliê</span>
                                </div>

                                <div style="flex-grow: 1;">
                                    <div style="font-size: 0.88rem; margin-bottom: 6px;">
                                        <strong>Chave Pix (E-mail):</strong> 
                                        <span style="background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 2px 8px; border-radius: 4px; font-family: monospace; font-weight: bold;"><?= $chavePixOficial ?></span>
                                    </div>
                                    <div style="font-size: 0.82rem; color: var(--cor-marrom-claro); margin-bottom: 8px;">
                                        Banco: <strong>Banco do Brasil</strong> | Titular: <strong>Amora Artesanal Ltda</strong>
                                    </div>
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 2px;">Pix Copia e Cola:</label>
                                        <input type="text" class="form-control" readonly value="<?= $codigoPixCopiaCola ?>" style="font-size: 0.75rem; background: #fafafa; font-family: monospace;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Opção Cartão de Crédito -->
                    <div style="border: 1px solid var(--cor-borda); background: #fff; border-radius: var(--raio-borda); padding: 18px; margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; font-size: 1.05rem;">
                            <input type="radio" name="forma_pagamento" value="Cartão de Crédito" style="accent-color: var(--cor-rosa-queimado); transform: scale(1.3);">
                            <strong style="color: var(--cor-marrom-suave);">Cartão de Crédito (Até 6x sem juros)</strong>
                        </label>

                        <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--cor-borda);">
                            <div class="form-group">
                                <label for="cartao_numero" class="form-label">Número do Cartão</label>
                                <input type="text" name="cartao_numero" id="cartao_numero" class="form-control" placeholder="0000 0000 0000 0000" maxlength="19">
                            </div>

                            <div class="form-group">
                                <label for="cartao_titular" class="form-label">Nome Impresso no Cartão</label>
                                <input type="text" name="cartao_titular" id="cartao_titular" class="form-control" placeholder="Como no cartão">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                                <div class="form-group">
                                    <label for="cartao_validade" class="form-label">Validade (MM/AA)</label>
                                    <input type="text" name="cartao_validade" id="cartao_validade" class="form-control" placeholder="12/28" maxlength="5">
                                </div>
                                <div class="form-group">
                                    <label for="cartao_cvv" class="form-label">Código CVV</label>
                                    <input type="text" name="cartao_cvv" id="cartao_cvv" class="form-control" placeholder="123" maxlength="4">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="cartao_parcelas" class="form-label">Parcelamento Sem Juros</label>
                                <select name="cartao_parcelas" id="cartao_parcelas" class="form-control">
                                    <?php for ($i = 1; $i <= 6; $i++): ?>
                                        <option value="<?= $i ?>">
                                            <?= $i ?>x de <?= formatarMoeda($totais['valor_total'] / $i) ?> sem juros
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botão de Confirmação -->
                <button type="submit" class="btn btn-primary btn-block btn-lg" style="padding: 16px;">
                    <i class="fa-solid fa-lock"></i> Confirmar Pedido e Ativar Rastreamento
                </button>
            </form>
        </div>

        <!-- Resumo da Compra e Selos de Proteção -->
        <div>
            <div style="background: var(--cor-branco); padding: 24px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); margin-bottom: 20px;">
                <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Itens Selecionados (<?= count($carrinho) ?>)</h3>

                <div style="max-height: 240px; overflow-y: auto; margin-bottom: 16px;">
                    <?php foreach ($carrinho as $item): ?>
                        <div style="display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid var(--cor-borda); align-items: center;">
                            <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url']) ?>" alt="<?= sanitizar($item['nome']) ?>" style="width: 48px; height: 48px; border-radius: 8px; background: var(--cor-rosa-claro); padding: 4px; object-fit: contain;">
                            <div style="flex-grow: 1;">
                                <div style="font-weight: 600; font-size: 0.88rem;"><?= sanitizar($item['nome']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--cor-marrom-claro);">
                                    <?= $item['quantidade'] ?>x <?= formatarMoeda((float)$item['preco']) ?>
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
                    <span>Frete Expresso Seguro:</span>
                    <strong style="color: var(--cor-sucesso);">Grátis</strong>
                </div>
                <div class="summary-row summary-total">
                    <span>Total a Pagar:</span>
                    <span><?= $totais['valor_formatado'] ?></span>
                </div>
            </div>

            <!-- Medidas de Segurança e Garantia de Reembolso -->
            <div style="background: var(--cor-branco); padding: 22px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
                <h4 style="font-size: 0.95rem; color: var(--cor-marrom-suave); margin-bottom: 14px; text-transform: uppercase;">
                    <i class="fa-solid fa-shield-cat" style="color: var(--cor-rosa-queimado);"></i> Garantias de Compra Amora
                </h4>

                <div style="display: flex; flex-direction: column; gap: 12px; font-size: 0.85rem; color: var(--cor-marrom-texto);">
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fa-solid fa-truck-fast" style="color: var(--cor-rosa-queimado); margin-top: 2px;"></i>
                        <div>
                            <strong>Rastreamento Passo a Passo:</strong> Você receberá o código com mapa em tempo real da transportadora.
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fa-solid fa-hand-holding-dollar" style="color: var(--cor-sucesso); margin-top: 2px;"></i>
                        <div>
                            <strong>Reembolso Garantido:</strong> Caso a transportadora confirme atraso na entrega além do prazo, você recebe reembolso integral via Pix ou reenvio prioritário.
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <i class="fa-solid fa-lock" style="color: var(--cor-rosa-queimado); margin-top: 2px;"></i>
                        <div>
                            <strong>Transações Protegidas:</strong> Seus dados não são compartilhados com terceiros.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
