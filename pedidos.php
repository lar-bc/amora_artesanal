<?php
/**
 * Acompanhamento de Pedidos, Rastreamento com Mapa em Tempo Real,
 * Verificação de Entrega, Garantia de Reembolso e Avaliação NPS Pós-Compra
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();

$usuario = getUsuarioLogado();
$db = getDBConnection();

// Processar Ações do Cliente (Verificação de Chegada, Reembolso e Avaliação)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $acao = $_POST['acao'] ?? '';
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);

        // 1. Cliente confirma que a encomenda já chegou
        if ($acao === 'confirmar_chegada') {
            $stmt = $db->prepare("UPDATE pedidos SET status = 'Entregue', confirmacao_entrega_cliente = 'entregue' WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pedidoId, $usuario['id']]);
            setFlash('success', 'Que alegria que sua encomenda chegou! Esperamos que ela encha seu lar de perfume e aconchego. Por favor, deixe sua avaliação abaixo!');
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }

        // 2. Cliente informa que ainda não chegou -> Verificação com Transportadora
        elseif ($acao === 'relatar_atraso') {
            $stmt = $db->prepare("UPDATE pedidos SET confirmacao_entrega_cliente = 'nao_chegou' WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pedidoId, $usuario['id']]);
            setFlash('info', 'Consultamos a transportadora LogiAmora. Abaixo você confere o parecer oficial e pode acionar a nossa garantia de reembolso imediato.');
            header('Location: ' . BASE_URL . '/pedidos.php#rastreio_' . $pedidoId);
            exit;
        }

        // 3. Cliente aciona a Garantia de Reembolso Integral ou Reenvio
        elseif ($acao === 'solicitar_reembolso') {
            $opcaoReembolso = $_POST['opcao_reembolso'] ?? 'reembolso_pix';
            $chavePixReembolso = trim($_POST['chave_pix_reembolso'] ?? '');
            $protocolo = 'REEMB-' . date('Y') . '-' . rand(10000, 99999);
            $motivo = ($opcaoReembolso === 'reembolso_pix') 
                ? "Reembolso Integral via Pix para chave: {$chavePixReembolso}" 
                : "Reenvio Expresso com brinde especial solicitado";

            $stmt = $db->prepare("UPDATE pedidos SET status = 'Reembolso Solicitado', reembolso_solicitado = 1, protocolo_reembolso = ?, motivo_reembolso = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$protocolo, $motivo, $pedidoId, $usuario['id']]);

            setFlash('success', "Solicitação registrada com sucesso! Seu protocolo é {$protocolo}. Nossa equipe do ateliê já iniciou o procedimento.");
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }

        // 4. Avaliação pós-compra do produto com NPS
        elseif ($acao === 'salvar_avaliacao') {
            $produtoId = (int)($_POST['produto_id'] ?? 0);
            $estrelas = min(5, max(1, (int)($_POST['estrelas'] ?? 5)));
            $comentario = trim($_POST['comentario'] ?? '');
            $indicariaAmigo = ($_POST['indicaria_amigo'] ?? 'sim') === 'nao' ? 'nao' : 'sim';

            if (!empty($comentario) && $produtoId > 0) {
                $stmt = $db->prepare("INSERT INTO avaliacoes (produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$produtoId, $usuario['id'], $usuario['nome'], $estrelas, $comentario, $indicariaAmigo]);
                setFlash('success', 'Muito obrigado pela sua avaliação carinhosa! Seu relato ajudará outros clientes a escolherem presentes especiais.');
            }
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }
    }
}

// Buscar pedidos do cliente
$stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario['id']]);
$pedidos = $stmt->fetchAll();

// Itens e produtos de cada pedido
$itensPorPedido = [];
if (!empty($pedidos)) {
    $stmtItens = $db->prepare("SELECT ip.*, p.nome, p.imagem_url 
                               FROM itens_pedido ip 
                               INNER JOIN produtos p ON ip.produto_id = p.id 
                               WHERE ip.pedido_id = ?");
    foreach ($pedidos as $p) {
        $stmtItens->execute([$p['id']]);
        $itensPorPedido[$p['id']] = $stmtItens->fetchAll();
    }
}

function getClasseStatusDetalhado(string $status): string {
    return match ($status) {
        'Pedido realizado'     => 'realizado',
        'Em preparação'        => 'preparacao',
        'Enviado'              => 'enviado',
        'Entregue'             => 'entregue',
        'Reembolso Solicitado' => 'preparacao',
        'Reembolsado'          => 'entregue',
        default                => 'realizado'
    };
}

$pageTitle = 'Meus Pedidos & Rastreamento em Tempo Real';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Meus Pedidos & Rastreamento</h1>
            <p style="color: var(--cor-marrom-claro);">Acompanhe o trajeto da sua encomenda artesanal no mapa e gerencie suas compras</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/chat.php" class="btn btn-secondary">
                <i class="fa-regular fa-comments"></i> Falar com Ateliê no Chat
            </a>
        </div>
    </div>

    <?php if (empty($pedidos)): ?>
        <div style="background: var(--cor-branco); border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); padding: 70px 20px; text-align: center;">
            <div style="font-size: 3.5rem; color: var(--cor-rosa-medio); margin-bottom: 16px;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Nenhum pedido realizado ainda</h3>
            <p style="color: var(--cor-marrom-claro); margin-bottom: 24px;">Visite nossa vitrine virtual e encante-se com nossos aromas, sabonetes botânicos e amigurumis.</p>
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-store"></i> Explorar Vitrine
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 32px;">
            <?php foreach ($pedidos as $p): ?>
                <?php 
                    $codigoRastreio = $p['codigo_rastreio'] ?? ('AM' . str_pad($p['id'], 8, '0', STR_PAD_LEFT) . 'BR');
                    $status = $p['status'];
                    $confirmacao = $p['confirmacao_entrega_cliente'] ?? '';
                    $reembolsoAtivo = (int)($p['reembolso_solicitado'] ?? 0);
                ?>
                <div id="pedido_<?= $p['id'] ?>" style="background: var(--cor-branco); border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); overflow: hidden;">
                    <!-- Cabeçalho do Pedido -->
                    <div style="background: var(--cor-rosa-claro); padding: 20px 26px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--cor-borda);">
                        <div>
                            <span style="font-weight: 700; font-size: 1.2rem; color: var(--cor-marrom-suave);">
                                Pedido #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.88rem; margin-left: 12px;">
                                <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y \à\s H:i', strtotime($p['data_pedido'])) ?>
                            </span>
                            <span style="margin-left: 12px; font-size: 0.85rem; background: #fff; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--cor-borda); color: var(--cor-rosa-queimado); font-family: monospace;">
                                Rastreio: <strong><?= sanitizar($codigoRastreio) ?></strong>
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge-status <?= getClasseStatusDetalhado($status) ?>">
                                <i class="fa-solid <?= $status === 'Entregue' ? 'fa-circle-check' : ($status === 'Enviado' ? 'fa-truck-fast' : 'fa-clock') ?>"></i>
                                <?= sanitizar($status) ?>
                            </span>
                            <span style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.3rem;">
                                <?= formatarMoeda((float)$p['valor_total']) ?>
                            </span>
                        </div>
                    </div>

                    <div style="padding: 26px;">
                        <!-- Seção de Rastreamento em Tempo Real com Linha do Tempo e Mapinha em SVG -->
                        <div id="rastreio_<?= $p['id'] ?>" style="background: #fffcf9; border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); padding: 22px; margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                                <h3 style="font-size: 1.15rem; color: var(--cor-marrom-suave); margin: 0;">
                                    <i class="fa-solid fa-map-location-dot" style="color: var(--cor-rosa-queimado);"></i> 
                                    Rastreamento em Tempo Real &bull; <?= sanitizar($p['transportadora'] ?? 'LogiAmora Transportes') ?>
                                </h3>
                                <span style="font-size: 0.82rem; color: var(--cor-sucesso); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="width: 8px; height: 8px; background: #4e8d53; border-radius: 50%; display: inline-block;"></span>
                                    Sinal GPS da transportadora ativo
                                </span>
                            </div>

                            <!-- Mapinha Visual em SVG do Trajeto em Tempo Real -->
                            <div style="margin-bottom: 20px; border-radius: var(--raio-borda); overflow: hidden; border: 1px solid var(--cor-borda); background: #fdf6f0;">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 700 160" width="100%" height="auto" style="display: block;">
                                    <!-- Fundo do mapa com relevo suave -->
                                    <rect width="100%" height="100%" fill="#fef8f4"/>
                                    <!-- Rios e Estradas Estilizadas -->
                                    <path d="M0,80 Q150,110 350,70 T700,90" fill="none" stroke="#eed9ce" stroke-width="26" stroke-linecap="round"/>
                                    <path d="M0,80 Q150,110 350,70 T700,90" fill="none" stroke="#fffaf5" stroke-width="14" stroke-dasharray="8,6" stroke-linecap="round"/>

                                    <!-- Ponto A: Ateliê Amora (Porto Velho) -->
                                    <circle cx="80" cy="80" r="18" fill="#5c3d2e"/>
                                    <circle cx="80" cy="80" r="12" fill="#fff"/>
                                    <circle cx="80" cy="80" r="6" fill="#c86d7e"/>
                                    <text x="80" y="125" font-family="'Poppins', sans-serif" font-size="11" font-weight="bold" fill="#5c3d2e" text-anchor="middle">Ateliê Amora (Porto Velho)</text>
                                    <text x="80" y="140" font-family="'Poppins', sans-serif" font-size="9" fill="#8a6a59" text-anchor="middle">Origem do Pedido</text>

                                    <!-- Ponto B: Destino do Cliente -->
                                    <circle cx="620" cy="88" r="18" fill="#c86d7e"/>
                                    <circle cx="620" cy="88" r="12" fill="#fff"/>
                                    <circle cx="620" cy="88" r="6" fill="#5c3d2e"/>
                                    <text x="620" y="125" font-family="'Poppins', sans-serif" font-size="11" font-weight="bold" fill="#c86d7e" text-anchor="middle">Seu Endereço</text>
                                    <text x="620" y="140" font-family="'Poppins', sans-serif" font-size="9" fill="#8a6a59" text-anchor="middle">Entrega Prioritária</text>

                                    <!-- Posição Dinâmica do Entregador no Mapa -->
                                    <?php 
                                        $posicaoX = match($status) {
                                            'Pedido realizado' => 120,
                                            'Em preparação'    => 240,
                                            'Enviado'          => 450,
                                            'Entregue'         => 610,
                                            default            => 350
                                        };
                                        $posicaoY = match($status) {
                                            'Pedido realizado' => 84,
                                            'Em preparação'    => 92,
                                            'Enviado'          => 74,
                                            'Entregue'         => 88,
                                            default            => 80
                                        };
                                    ?>

                                    <!-- Radar de Pulso -->
                                    <circle cx="<?= $posicaoX ?>" cy="<?= $posicaoY ?>" r="20" fill="#c86d7e" opacity="0.25"/>
                                    <circle cx="<?= $posicaoX ?>" cy="<?= $posicaoY ?>" r="12" fill="#c86d7e" opacity="0.6"/>

                                    <!-- Ícone do Caminhão / Entregador -->
                                    <rect x="<?= $posicaoX - 16 ?>" y="<?= $posicaoY - 14 ?>" width="32" height="18" rx="4" fill="#5c3d2e"/>
                                    <rect x="<?= $posicaoX + 4 ?>" y="<?= $posicaoY - 10 ?>" width="10" height="12" rx="2" fill="#fff"/>
                                    <circle cx="<?= $posicaoX - 8 ?>" cy="<?= $posicaoY + 4 ?>" r="4" fill="#c86d7e"/>
                                    <circle cx="<?= $posicaoX + 10 ?>" cy="<?= $posicaoY + 4 ?>" r="4" fill="#c86d7e"/>
                                    
                                    <text x="<?= $posicaoX ?>" y="<?= $posicaoY - 20 ?>" font-family="'Poppins', sans-serif" font-size="10" font-weight="bold" fill="#c86d7e" text-anchor="middle">
                                        <?= ($status === 'Entregue') ? 'Destino Final' : 'Em Trânsito' ?>
                                    </text>
                                </svg>
                            </div>

                            <!-- Linha do Tempo Passo a Passo das 5 Etapas -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 14px;">
                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid var(--cor-borda);">
                                    <div style="color: var(--cor-sucesso); font-size: 1.1rem; margin-bottom: 4px;"><i class="fa-solid fa-circle-check"></i></div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">1. Pedido Realizado</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Pagamento aprovado</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Em preparação', 'Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Em preparação', 'Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-hands-holding"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">2. Em Preparação</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Personalização ateliê</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">3. Coleta Efetuada</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Com a transportadora</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">4. Em Rota de Entrega</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">A caminho de você</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= ($status === 'Entregue') ? 'var(--cor-sucesso)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= ($status === 'Entregue') ? 'var(--cor-sucesso)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-house-circle-check"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">5. Entregue</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);"><?= ($status === 'Entregue') ? 'Recebido!' : 'No prazo' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta Obrigatória: "Sua encomenda já chegou?" & Checagem com Transportadora -->
                        <?php if ($status !== 'Entregue' && !$reembolsoAtivo): ?>
                            <div style="background: #fdfaf6; border: 2px dashed var(--cor-rosa-medio); border-radius: var(--raio-borda); padding: 20px; margin-bottom: 24px;">
                                <h4 style="font-size: 1.05rem; color: var(--cor-marrom-suave); margin-bottom: 6px;">
                                    <i class="fa-regular fa-circle-question" style="color: var(--cor-rosa-queimado);"></i> 
                                    Sua encomenda já chegou ao seu endereço?
                                </h4>
                                <p style="font-size: 0.85rem; color: var(--cor-marrom-claro); margin-bottom: 14px;">
                                    Nosso compromisso é com a sua total satisfação. Diga-nos se já está com o pacote em mãos:
                                </p>

                                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                    <!-- Botão Confirmar Chegada -->
                                    <form action="<?= BASE_URL ?>/pedidos.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="acao" value="confirmar_chegada">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-check"></i> Sim, já recebi com sucesso!
                                        </button>
                                    </form>

                                    <!-- Botão Ainda Não Chegou (Checagem com transportadora) -->
                                    <form action="<?= BASE_URL ?>/pedidos.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="acao" value="relatar_atraso">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-xmark"></i> Ainda não chegou
                                        </button>
                                    </form>
                                </div>

                                <!-- Parecer da Transportadora & Oferta de Reembolso caso tenha relatado não ter chegado -->
                                <?php if ($confirmacao === 'nao_chegou'): ?>
                                    <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--cor-borda);">
                                        <div class="alert alert-warning" style="margin-bottom: 14px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            <div>
                                                <strong>Parecer da Transportadora LogiAmora:</strong> 
                                                <span>Identificamos que o veículo da rota regional registrou um atraso de tráfego que ultrapassou a estimativa habitual de entrega.</span>
                                            </div>
                                        </div>

                                        <!-- Oferta de Reembolso Imediato Conforme Requisito -->
                                        <div style="background: #fff; padding: 18px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-rosa-medio);">
                                            <h5 style="color: var(--cor-rosa-queimado); font-size: 1rem; margin-bottom: 8px;">
                                                <i class="fa-solid fa-hand-holding-dollar"></i> Garantia de Entrega Amora Artesanal
                                            </h5>
                                            <p style="font-size: 0.88rem; color: var(--cor-marrom-texto); line-height: 1.6; margin-bottom: 14px;">
                                                Como a entrega excedeu o prazo limite estipulado, você tem o direito garantido de solicitar o <strong>reembolso integral imediato</strong> ou o <strong>reenvio expresso com um mimo especial</strong> do ateliê.
                                            </p>

                                            <form action="<?= BASE_URL ?>/pedidos.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                <input type="hidden" name="acao" value="solicitar_reembolso">
                                                <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">

                                                <div style="display: flex; gap: 14px; flex-direction: column; margin-bottom: 14px;">
                                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                                                        <input type="radio" name="opcao_reembolso" value="reembolso_pix" checked style="accent-color: var(--cor-rosa-queimado);">
                                                        <span><strong>Reembolso Integral Imediato via Pix</strong> no valor de <?= formatarMoeda((float)$p['valor_total']) ?></span>
                                                    </label>

                                                    <div style="margin-left: 24px;">
                                                        <input type="text" name="chave_pix_reembolso" class="form-control" placeholder="Informe sua chave Pix (CPF, Celular ou E-mail)" style="font-size: 0.85rem;" required>
                                                    </div>

                                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                                                        <input type="radio" name="opcao_reembolso" value="reenvio_expresso" style="accent-color: var(--cor-rosa-queimado);">
                                                        <span><strong>Reenvio Expresso com Brinde</strong> (produziremos e despacharemos novamente hoje)</span>
                                                    </label>
                                                </div>

                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-paper-plane"></i> Confirmar Solicitação de Reembolso / Reenvio
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($reembolsoAtivo): ?>
                            <!-- Alerta de Reembolso Já Registrado -->
                            <div class="alert alert-info" style="margin-bottom: 24px;">
                                <i class="fa-solid fa-shield-halved"></i>
                                <div>
                                    <strong>Protocolo de Reembolso Ativo: <?= sanitizar($p['protocolo_reembolso'] ?? '') ?></strong><br>
                                    <span style="font-size: 0.85rem;">Status: <?= sanitizar($p['motivo_reembolso'] ?? 'Solicitação em processamento prioritário pelo setor financeiro.') ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Itens do Pedido com Personalização Exclusiva -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--cor-marrom-suave); text-transform: uppercase;">
                                Itens Deste Pedido:
                            </h4>

                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if (!empty($itensPorPedido[$p['id']])): ?>
                                    <?php foreach ($itensPorPedido[$p['id']] as $item): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #fffbf9; padding: 12px 16px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda); flex-wrap: wrap; gap: 12px;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="" style="width: 46px; height: 46px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 4px;">
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.92rem; color: var(--cor-marrom-suave);">
                                                        <?= sanitizar($item['nome']) ?>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                                        <?= $item['quantidade'] ?>x <?= formatarMoeda((float)$item['preco_unitario']) ?>
                                                    </div>
                                                    <?php if (!empty($item['personalizacao'])): ?>
                                                        <div style="font-size: 0.78rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 3px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;">
                                                            <i class="fa-solid fa-sparkles"></i> <strong>Personalização:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div style="text-align: right;">
                                                <div style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.05rem;">
                                                    <?= formatarMoeda((float)($item['quantidade'] * $item['preco_unitario'])) ?>
                                                </div>

                                                <!-- Se o pedido foi entregue, permitir avaliar o produto e responder à pergunta NPS -->
                                                <?php if ($status === 'Entregue'): ?>
                                                    <a href="#avaliar_prod_<?= $item['produto_id'] ?>" style="font-size: 0.78rem; color: var(--cor-rosa-queimado); text-decoration: underline; display: block; margin-top: 4px;">
                                                        ★ Deixar Avaliação
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Formulário de Avaliação e Pergunta NPS ("Você indicaria a um amigo?") -->
                                        <?php if ($status === 'Entregue'): ?>
                                            <div id="avaliar_prod_<?= $item['produto_id'] ?>" style="background: #fff; border: 1px solid var(--cor-rosa-medio); padding: 18px; border-radius: var(--raio-borda-sm); margin: 6px 0 14px 20px;">
                                                <h5 style="color: var(--cor-rosa-queimado); margin-bottom: 8px; font-size: 0.95rem;">
                                                    Avalie o item: <?= sanitizar($item['nome']) ?>
                                                </h5>
                                                
                                                <form action="<?= BASE_URL ?>/pedidos.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                    <input type="hidden" name="acao" value="salvar_avaliacao">
                                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                                    <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">

                                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 12px;">
                                                        <div class="form-group" style="margin-bottom: 0;">
                                                            <label class="form-label" style="font-size: 0.82rem;">Sua Nota de Satisfação:</label>
                                                            <select name="estrelas" class="form-control" style="font-size: 0.85rem;">
                                                                <option value="5">★★★★★ (5 Estrelas - Excelente!)</option>
                                                                <option value="4">★★★★☆ (4 Estrelas - Muito Bom)</option>
                                                                <option value="3">★★★☆☆ (3 Estrelas - Bom)</option>
                                                                <option value="2">★★☆☆☆ (2 Estrelas - Regular)</option>
                                                                <option value="1">★☆☆☆☆ (1 Estrela - Ruim)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Pergunta NPS Solicitada Pelo Usuário -->
                                                        <div class="form-group" style="margin-bottom: 0;">
                                                            <label class="form-label" style="font-size: 0.82rem;">Você indicaria o nosso site para um amigo? *</label>
                                                            <div style="display: flex; gap: 16px; padding-top: 6px;">
                                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                                                                    <input type="radio" name="indicaria_amigo" value="sim" checked style="accent-color: var(--cor-rosa-queimado);"> Sim, com certeza!
                                                                </label>
                                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                                                                    <input type="radio" name="indicaria_amigo" value="nao" style="accent-color: var(--cor-rosa-queimado);"> Não no momento
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="form-label" style="font-size: 0.82rem;">Seu Comentário sobre o Produto e Aroma:</label>
                                                        <textarea name="comentario" class="form-control" rows="2" placeholder="Conte como foi sua experiência com o produto artesanal..." required style="font-size: 0.85rem;"></textarea>
                                                    </div>

                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        Publicar Avaliação
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Dados de Entrega e Pagamento -->
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.88rem; color: var(--cor-marrom-claro); border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                            <div>
                                <i class="fa-solid fa-location-dot" style="color: var(--cor-rosa-queimado);"></i> 
                                <strong>Destino:</strong> <?= sanitizar($p['endereco_entrega']) ?>
                            </div>
                            <div>
                                <strong>Pagamento:</strong> <?= sanitizar($p['forma_pagamento_detalhes'] ?? $p['forma_pagamento']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
