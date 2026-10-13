<?php
/**
 * Histórico e Acompanhamento de Pedidos do Cliente (UC06, RF08, CA06)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();

$usuario = getUsuarioLogado();
$db = getDBConnection();

// Buscar pedidos do cliente logado ordenados do mais recente para o mais antigo (CA06)
$stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario['id']]);
$pedidos = $stmt->fetchAll();

// Para cada pedido, buscar seus itens e personalizações
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

// Mapeamento de classe CSS por status (RF10)
function getClasseStatus(string $status): string {
    return match ($status) {
        'Pedido realizado' => 'realizado',
        'Em preparação'    => 'preparacao',
        'Enviado'          => 'enviado',
        'Entregue'         => 'entregue',
        default            => 'realizado'
    };
}

$pageTitle = 'Meus Pedidos';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="margin-bottom: 24px;">
        <h1 style="font-size: 2rem; margin-bottom: 4px;">Meus Pedidos</h1>
        <p style="color: var(--cor-marrom-claro);">Acompanhe a produção, envio e histórico de suas compras artesanais</p>
    </div>

    <?php if (empty($pedidos)): ?>
        <div style="background: var(--cor-branco); border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); padding: 60px 20px; text-align: center;">
            <div style="font-size: 3rem; color: var(--cor-rosa-medio); margin-bottom: 16px;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Você ainda não realizou nenhum pedido</h3>
            <p style="color: var(--cor-marrom-claro); margin-bottom: 24px;">Navegue pela nossa vitrine e encante-se com nossos aromas e lembranças artesanais.</p>
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary">
                <i class="fa-solid fa-basket-shopping"></i> Começar a Comprar
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($pedidos as $p): ?>
                <div style="background: var(--cor-branco); border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); overflow: hidden;">
                    <!-- Cabeçalho do Card de Pedido -->
                    <div style="background: var(--cor-rosa-claro); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid var(--cor-borda);">
                        <div>
                            <span style="font-weight: 700; font-size: 1.1rem; color: var(--cor-marrom-suave);">
                                Pedido #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.85rem; margin-left: 12px;">
                                <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?>
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge-status <?= getClasseStatus($p['status']) ?>">
                                <i class="fa-solid <?= $p['status'] === 'Entregue' ? 'fa-circle-check' : ($p['status'] === 'Enviado' ? 'fa-truck' : ($p['status'] === 'Em preparação' ? 'fa-fire-burner' : 'fa-clock')) ?>"></i>
                                <?= sanitizar($p['status']) ?>
                            </span>
                            <span style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.2rem;">
                                <?= formatarMoeda((float)$p['valor_total']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Corpo do Card de Pedido: Itens e Endereço -->
                    <div style="padding: 24px;">
                        <div style="margin-bottom: 16px; font-size: 0.9rem; color: var(--cor-marrom-claro);">
                            <i class="fa-solid fa-location-dot" style="color: var(--cor-rosa-queimado);"></i> 
                            <strong>Endereço de Entrega:</strong> <?= sanitizar($p['endereco_entrega']) ?>
                        </div>

                        <div style="border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--cor-marrom-suave); text-transform: uppercase; letter-spacing: 0.5px;">Itens Inclusos:</h4>

                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php if (!empty($itensPorPedido[$p['id']])): ?>
                                    <?php foreach ($itensPorPedido[$p['id']] as $item): ?>
                                        <div style="display: flex; gap: 14px; align-items: center; background: #fffbf9; padding: 12px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda);">
                                            <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="<?= sanitizar($item['nome']) ?>" style="width: 50px; height: 50px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 4px;">
                                            <div style="flex-grow: 1;">
                                                <div style="font-weight: 600; font-size: 0.95rem; color: var(--cor-marrom-suave);">
                                                    <?= sanitizar($item['nome']) ?>
                                                </div>
                                                <div style="font-size: 0.82rem; color: var(--cor-marrom-claro);">
                                                    Quantidade: <strong><?= $item['quantidade'] ?></strong> &times; <?= formatarMoeda((float)$item['preco_unitario']) ?>
                                                </div>
                                                <?php if (!empty($item['personalizacao'])): ?>
                                                    <div style="font-size: 0.8rem; color: var(--cor-rosa-queimado); margin-top: 4px;">
                                                        <i class="fa-solid fa-wand-magic-sparkles"></i> <strong>Personalização:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1rem;">
                                                <?= formatarMoeda((float)($item['quantidade'] * $item['preco_unitario'])) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
