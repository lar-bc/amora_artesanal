<?php
/**
 * Gestão de Pedidos e Alteração de Status (RF10, UC08, CA07)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

$statusPermitidos = ['Pedido realizado', 'Em preparação', 'Enviado', 'Entregue'];

// Processar atualização de status (RF10, CA07)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $novoStatus = trim($_POST['status'] ?? '');

        if (in_array($novoStatus, $statusPermitidos)) {
            $stmt = $db->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->execute([$novoStatus, $pedidoId]);
            setFlash('success', "Situação do Pedido #{$pedidoId} atualizada para '{$novoStatus}' com sucesso!");
        } else {
            setFlash('danger', 'Status inválido fornecido.');
        }

        header('Location: ' . BASE_URL . '/admin/pedidos.php');
        exit;
    }
}

// Filtro de status
$filtroStatus = $_GET['status'] ?? '';
$sql = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email 
        FROM pedidos p 
        INNER JOIN usuarios u ON p.usuario_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($filtroStatus) && in_array($filtroStatus, $statusPermitidos)) {
    $sql .= " AND p.status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// Carregar itens para cada pedido
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

function getClasseStatusAdminPage(string $status): string {
    return match ($status) {
        'Pedido realizado' => 'realizado',
        'Em preparação'    => 'preparacao',
        'Enviado'          => 'enviado',
        'Entregue'         => 'entregue',
        default            => 'realizado'
    };
}

$pageTitle = 'Gerenciamento de Pedidos';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="admin-header-actions">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Gerenciamento de Pedidos</h1>
            <p style="color: var(--cor-marrom-claro);">Acompanhe os pedidos de clientes e atualize o progresso de entrega (RF10, CA07)</p>
        </div>
    </div>

    <!-- Filtros de Status em Pílulas -->
    <div style="background: var(--cor-branco); padding: 18px 24px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); margin-bottom: 24px;">
        <div class="categories-pills" style="margin: 0; justify-content: flex-start;">
            <a href="<?= BASE_URL ?>/admin/pedidos.php" class="pill-item <?= empty($filtroStatus) ? 'active' : '' ?>">
                Todos os Pedidos (<?= count($pedidos) ?>)
            </a>
            <?php foreach ($statusPermitidos as $st): ?>
                <a href="<?= BASE_URL ?>/admin/pedidos.php?status=<?= urlencode($st) ?>" class="pill-item <?= $filtroStatus === $st ? 'active' : '' ?>">
                    <?= sanitizar($st) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lista de Pedidos -->
    <?php if (empty($pedidos)): ?>
        <div class="admin-card" style="text-align: center; padding: 50px 20px;">
            <p style="color: var(--cor-marrom-claro); font-size: 1.1rem;">Nenhum pedido encontrado com o filtro selecionado.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($pedidos as $p): ?>
                <div class="admin-card" style="margin-bottom: 0; padding: 0; overflow: hidden;">
                    <!-- Cabeçalho do Pedido com Formulário de Status (CA07) -->
                    <div style="background: var(--cor-rosa-claro); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--cor-borda);">
                        <div>
                            <span style="font-weight: 700; font-size: 1.15rem; color: var(--cor-marrom-suave);">
                                Pedido #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.88rem; margin-left: 10px;">
                                Cliente: <strong><?= sanitizar($p['cliente_nome']) ?></strong> (<?= sanitizar($p['cliente_email']) ?>)
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.82rem; margin-left: 10px;">
                                &bull; <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?>
                            </span>
                        </div>

                        <!-- Formulário de Atualização Imediata de Status (RF10, CA07) -->
                        <form action="<?= BASE_URL ?>/admin/pedidos.php" method="POST" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="acao" value="atualizar_status">
                            <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">

                            <label style="font-size: 0.85rem; font-weight: 600; color: var(--cor-marrom-suave);">Alterar Status:</label>
                            <select name="status" class="form-control" style="width: auto; padding: 8px 12px; font-size: 0.88rem;">
                                <?php foreach ($statusPermitidos as $st): ?>
                                    <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>>
                                        <?= $st ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-check"></i> Salvar
                            </button>
                        </form>
                    </div>

                    <!-- Conteúdo: Endereço, Itens e Personalizações -->
                    <div style="padding: 24px;">
                        <div style="font-size: 0.92rem; color: var(--cor-marrom-claro); margin-bottom: 16px;">
                            <i class="fa-solid fa-truck" style="color: var(--cor-rosa-queimado);"></i> 
                            <strong>Endereço de Entrega:</strong> <?= sanitizar($p['endereco_entrega']) ?>
                        </div>

                        <div style="border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--cor-marrom-suave); text-transform: uppercase;">Itens Solicitados:</h4>

                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if (!empty($itensPorPedido[$p['id']])): ?>
                                    <?php foreach ($itensPorPedido[$p['id']] as $item): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #fffbf9; padding: 12px 16px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda); flex-wrap: wrap; gap: 10px;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="" style="width: 44px; height: 44px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 3px;">
                                                <div>
                                                    <strong style="color: var(--cor-marrom-suave); font-size: 0.92rem;"><?= sanitizar($item['nome']) ?></strong>
                                                    <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                                        <?= $item['quantidade'] ?> unidade(s) &times; <?= formatarMoeda((float)$item['preco_unitario']) ?>
                                                    </div>
                                                    <!-- Personalização Destacada (RF05, UC03) -->
                                                    <?php if (!empty($item['personalizacao'])): ?>
                                                        <div style="font-size: 0.82rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-top: 4px; border: 1px solid var(--cor-rosa-medio);">
                                                            <i class="fa-solid fa-wand-magic-sparkles"></i> <strong>Personalização Solicitada:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.05rem;">
                                                <?= formatarMoeda((float)($item['quantidade'] * $item['preco_unitario'])) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--cor-borda); margin-top: 20px; padding-top: 16px;">
                            <span class="badge-status <?= getClasseStatusAdminPage($p['status']) ?>" style="font-size: 0.9rem;">
                                Status Atual: <strong><?= sanitizar($p['status']) ?></strong>
                            </span>

                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--cor-rosa-queimado);">
                                Total: <?= formatarMoeda((float)$p['valor_total']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
