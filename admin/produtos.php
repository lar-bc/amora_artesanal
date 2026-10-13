<?php
/**
 * Gerenciamento do Catálogo e Estoque (RF09, UC07)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

// Processar exclusão de produto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'excluir') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $id = (int)($_POST['id'] ?? 0);

        // Verificar se há itens de pedidos vinculados
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM itens_pedido WHERE produto_id = ?");
        $stmtCheck->execute([$id]);
        $vinculado = (int)$stmtCheck->fetchColumn();

        if ($vinculado > 0) {
            setFlash('warning', 'Este produto já possui vendas registradas e não pode ser excluído para preservar o histórico. Você pode zerar o estoque dele para ocultá-lo.');
        } else {
            $stmtDel = $db->prepare("DELETE FROM produtos WHERE id = ?");
            $stmtDel->execute([$id]);
            setFlash('success', 'Produto excluído do catálogo com sucesso.');
        }
        header('Location: ' . BASE_URL . '/admin/produtos.php');
        exit;
    }
}

// Filtro de busca na área admin
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$categoriaId = isset($_GET['categoria']) && is_numeric($_GET['categoria']) ? (int)$_GET['categoria'] : null;

$sql = "SELECT p.*, c.nome_categoria 
        FROM produtos p 
        INNER JOIN categorias c ON p.categoria_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($busca)) {
    $sql .= " AND (p.nome LIKE ? OR p.descricao LIKE ?)";
    $params[] = "%$busca%";
    $params[] = "%$busca%";
}

if ($categoriaId) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoriaId;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

$categorias = getCategorias();
$pageTitle = 'Gerenciar Catálogo & Estoque';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="admin-header-actions">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Catálogo de Produtos</h1>
            <p style="color: var(--cor-marrom-claro);">Cadastre, edite informações, gerencie fotos e controle os níveis de estoque (RF09)</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/produto_form.php" class="btn btn-primary">
                <i class="fa-solid fa-plus"></i> Adicionar Novo Produto
            </a>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div style="background: var(--cor-branco); padding: 18px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); margin-bottom: 24px;">
        <form action="<?= BASE_URL ?>/admin/produtos.php" method="GET" style="display: flex; gap: 14px; flex-wrap: wrap; align-items: center;">
            <div style="flex-grow: 1; min-width: 240px;">
                <input type="text" name="busca" class="form-control" placeholder="Buscar por nome do produto..." value="<?= sanitizar($busca) ?>">
            </div>

            <div style="min-width: 200px;">
                <select name="categoria" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas as Categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoriaId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= sanitizar($cat['nome_categoria']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-secondary">
                <i class="fa-solid fa-magnifying-glass"></i> Filtrar
            </button>
            <?php if (!empty($busca) || $categoriaId): ?>
                <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-outline">Limpar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabela de Produtos -->
    <div class="admin-card">
        <?php if (empty($produtos)): ?>
            <p style="text-align: center; color: var(--cor-marrom-claro); padding: 30px 0;">Nenhum produto cadastrado com esses critérios.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="width: 70px;">Foto</th>
                            <th>Nome do Produto</th>
                            <th>Categoria</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= BASE_URL ?>/<?= sanitizar($p['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="<?= sanitizar($p['nome']) ?>" style="width: 48px; height: 48px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 4px;">
                                </td>
                                <td>
                                    <strong><?= sanitizar($p['nome']) ?></strong>
                                    <div style="font-size: 0.78rem; color: var(--cor-marrom-claro); max-width: 320px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= sanitizar($p['descricao']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-status" style="background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado);">
                                        <?= sanitizar($p['nome_categoria']) ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; color: var(--cor-rosa-queimado);">
                                    <?= formatarMoeda((float)$p['preco']) ?>
                                </td>
                                <td>
                                    <span class="stock-tag <?= $p['estoque'] <= 5 ? 'low' : '' ?>" style="font-weight: 600;">
                                        <i class="fa-solid fa-box-archive"></i> <?= $p['estoque'] ?> unid.
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; gap: 8px;">
                                        <a href="<?= BASE_URL ?>/admin/produto_form.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm" title="Editar produto">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar
                                        </a>
                                        <form action="<?= BASE_URL ?>/admin/produtos.php" method="POST" style="margin: 0;" onsubmit="return confirm('Deseja realmente remover o produto <?= addslashes($p['nome']) ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                            <input type="hidden" name="acao" value="excluir">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: #c93b3b; border-color: #f7c5c7;" title="Excluir produto">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
