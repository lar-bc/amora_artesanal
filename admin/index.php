<?php
/**
 * Dashboard Administrativo (UC07, UC08, RF09, RF10)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

// Métricas gerais
$totalProdutos = (int)$db->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
$totalPedidos = (int)$db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$faturamento = (float)$db->query("SELECT SUM(valor_total) FROM pedidos")->fetchColumn();
$totalMensagens = (int)$db->query("SELECT COUNT(*) FROM mensagens_contato WHERE lida = 0")->fetchColumn();

// Últimos pedidos
$ultimosPedidos = $db->query("SELECT p.*, u.nome as cliente_nome 
                              FROM pedidos p 
                              INNER JOIN usuarios u ON p.usuario_id = u.id 
                              ORDER BY p.id DESC LIMIT 5")->fetchAll();

// Produtos com estoque baixo
$produtosEstoqueBaixo = $db->query("SELECT * FROM produtos WHERE estoque <= 8 ORDER BY estoque ASC LIMIT 5")->fetchAll();

function getClasseStatusAdmin(string $status): string {
    return match ($status) {
        'Pedido realizado' => 'realizado',
        'Em preparação'    => 'preparacao',
        'Enviado'          => 'enviado',
        'Entregue'         => 'entregue',
        default            => 'realizado'
    };
}
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Visão Geral da Loja</h1>
            <p style="color: var(--cor-marrom-claro);">Bem-vindo(a), <?= sanitizar($usuario['nome']) ?>! Aqui está o resumo das suas vendas e encomendas.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?= BASE_URL ?>/admin/produto_form.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Novo Produto
            </a>
            <a href="<?= BASE_URL ?>/admin/pedidos.php" class="btn btn-secondary">
                <i class="fa-solid fa-list-check"></i> Gerenciar Pedidos
            </a>
        </div>
    </div>

    <!-- Cards de Métricas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: #eef2ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-bag-shopping"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); text-transform: uppercase;">Total de Pedidos</div>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--cor-marrom-suave);"><?= $totalPedidos ?></div>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: #dcfce7; color: #15803d; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-dollar-sign"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); text-transform: uppercase;">Faturamento Total</div>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--cor-rosa-queimado);"><?= formatarMoeda($faturamento) ?></div>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: #fdf2f8; color: #be185d; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); text-transform: uppercase;">Produtos Ativos</div>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--cor-marrom-suave);"><?= $totalProdutos ?></div>
            </div>
        </div>

        <div class="admin-card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 12px; background: #fef3c7; color: #b45309; display: flex; align-items: center; justify-content: center; font-size: 1.4rem;">
                <i class="fa-regular fa-envelope"></i>
            </div>
            <div>
                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); text-transform: uppercase;">Novas Mensagens</div>
                <div style="font-size: 1.6rem; font-weight: 700; color: var(--cor-marrom-suave);"><?= $totalMensagens ?></div>
            </div>
        </div>
    </div>

    <!-- Seção de Duas Colunas: Últimos Pedidos e Estoque Baixo -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <!-- Tabela de Últimos Pedidos (RF10) -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.25rem;">Últimos Pedidos Registrados</h3>
                <a href="<?= BASE_URL ?>/admin/pedidos.php" style="font-size: 0.85rem; font-weight: 600;">Ver todos &rarr;</a>
            </div>

            <?php if (empty($ultimosPedidos)): ?>
                <p style="color: var(--cor-marrom-claro); text-align: center; padding: 20px 0;">Nenhum pedido realizado ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#ID</th>
                                <th>Cliente</th>
                                <th>Data</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimosPedidos as $p): ?>
                                <tr>
                                    <td><strong>#<?= $p['id'] ?></strong></td>
                                    <td><?= sanitizar($p['cliente_nome']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                                    <td style="font-weight: 600; color: var(--cor-rosa-queimado);"><?= formatarMoeda((float)$p['valor_total']) ?></td>
                                    <td>
                                        <span class="badge-status <?= getClasseStatusAdmin($p['status']) ?>">
                                            <?= sanitizar($p['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/admin/pedidos.php" class="btn btn-secondary btn-sm" title="Gerenciar status">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Produtos em Alerta de Estoque (UC07) -->
        <div class="admin-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1.25rem; color: #b45309;"><i class="fa-solid fa-triangle-exclamation"></i> Estoque Baixo</h3>
                <a href="<?= BASE_URL ?>/admin/produtos.php" style="font-size: 0.85rem; font-weight: 600;">Estoque &rarr;</a>
            </div>

            <?php if (empty($produtosEstoqueBaixo)): ?>
                <p style="color: var(--cor-marrom-claro); font-size: 0.9rem;">Todos os produtos com estoque saudável!</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php foreach ($produtosEstoqueBaixo as $prod): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; border-radius: var(--raio-borda-sm); background: #fdfaf6; border: 1px solid var(--cor-borda);">
                            <div>
                                <div style="font-weight: 600; font-size: 0.88rem; color: var(--cor-marrom-suave);"><?= sanitizar($prod['nome']) ?></div>
                                <div style="font-size: 0.78rem; color: var(--cor-marrom-claro);"><?= formatarMoeda((float)$prod['preco']) ?></div>
                            </div>
                            <span class="badge-status <?= $prod['estoque'] <= 3 ? 'preparacao' : 'realizado' ?>" style="font-weight: 700;">
                                <?= $prod['estoque'] ?> unid.
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
