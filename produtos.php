<?php
/**
 * Catálogo de Produtos com Busca e Filtros por Categoria (RF03, RF04, CA03)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$categoriaId = isset($_GET['categoria']) && is_numeric($_GET['categoria']) ? (int)$_GET['categoria'] : null;
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : null;

$categorias = getCategorias();
$produtos = getProdutos($categoriaId, $busca);

$categoriaAtivaNome = 'Todas as Coleções';
if ($categoriaId) {
    foreach ($categorias as $c) {
        if ($c['id'] == $categoriaId) {
            $categoriaAtivaNome = $c['nome_categoria'];
            break;
        }
    }
}

$pageTitle = 'Catálogo de Produtos Artesanais';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin-top: 30px;">
    <!-- Barra Superior de Filtros e Busca -->
    <div style="background: var(--cor-branco); padding: 24px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div>
                <h2 style="font-size: 1.8rem; margin-bottom: 4px;"><?= sanitizar($categoriaAtivaNome) ?></h2>
                <p style="color: var(--cor-marrom-claro); font-size: 0.9rem;">
                    Encontrado(s) <strong><?= count($produtos) ?></strong> produto(s) artesanal(is)
                    <?= !empty($busca) ? ' para a busca "<strong>' . sanitizar($busca) . '</strong>"' : '' ?>
                </p>
            </div>

            <!-- Formulário de Pesquisa por Nome (RF04) -->
            <form action="<?= BASE_URL ?>/produtos.php" method="GET" style="display: flex; gap: 10px; width: 100%; max-width: 420px;">
                <?php if ($categoriaId): ?>
                    <input type="hidden" name="categoria" value="<?= $categoriaId ?>">
                <?php endif; ?>
                <input type="text" name="busca" class="form-control" placeholder="Buscar por vela, sabonete, amigurumi..." value="<?= sanitizar($busca ?? '') ?>">
                <button type="submit" class="btn btn-primary" title="Pesquisar">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
                <?php if (!empty($busca) || $categoriaId): ?>
                    <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-secondary" title="Limpar filtros">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Filtros em Pílulas de Categoria (CA03) -->
        <div class="categories-pills" style="margin: 20px 0 0; justify-content: flex-start;">
            <a href="<?= BASE_URL ?>/produtos.php<?= !empty($busca) ? '?busca=' . urlencode($busca) : '' ?>" class="pill-item <?= $categoriaId === null ? 'active' : '' ?>">
                Todas as Peças
            </a>
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>/produtos.php?categoria=<?= $cat['id'] ?><?= !empty($busca) ? '&busca=' . urlencode($busca) : '' ?>" class="pill-item <?= $categoriaId === (int)$cat['id'] ? 'active' : '' ?>">
                    <?= sanitizar($cat['nome_categoria']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Grid de Produtos -->
    <?php if (empty($produtos)): ?>
        <div style="background: var(--cor-branco); padding: 60px 20px; border-radius: var(--raio-borda); text-align: center; border: 1px solid var(--cor-borda); margin-bottom: 50px;">
            <div style="font-size: 3rem; color: var(--cor-rosa-medio); margin-bottom: 16px;">
                <i class="fa-regular fa-face-frown-open"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Nenhum produto encontrado</h3>
            <p style="color: var(--cor-marrom-claro); margin-bottom: 24px;">Tente pesquisar com outros termos ou selecione outra categoria.</p>
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary">
                <i class="fa-solid fa-arrow-rotate-left"></i> Ver Todos os Produtos
            </a>
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php foreach ($produtos as $produto): ?>
                <div class="product-card">
                    <div class="card-img-wrap">
                        <span class="card-badge"><?= sanitizar($produto['nome_categoria']) ?></span>
                        <img src="<?= BASE_URL ?>/<?= sanitizar($produto['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="<?= sanitizar($produto['nome']) ?>" loading="lazy">
                    </div>
                    <div class="card-body">
                        <span class="card-category"><?= sanitizar($produto['nome_categoria']) ?></span>
                        <h3 class="card-title"><?= sanitizar($produto['nome']) ?></h3>
                        <p class="card-desc"><?= sanitizar($produto['descricao']) ?></p>
                        <div class="card-footer-action">
                            <div>
                                <div class="product-price"><?= formatarMoeda((float)$produto['preco']) ?></div>
                                <span class="stock-tag <?= $produto['estoque'] <= 5 ? 'low' : '' ?>">
                                    <i class="fa-solid fa-box-archive"></i> <?= $produto['estoque'] ?> disponíveis
                                </span>
                            </div>
                            <a href="<?= BASE_URL ?>/produto.php?id=<?= $produto['id'] ?>" class="btn btn-secondary btn-sm" title="Personalizar e adicionar">
                                Detalhes <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
