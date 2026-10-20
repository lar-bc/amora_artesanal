<?php
/**
 * Página Inicial - Vitrine Virtual Amora Artesanal (RF03, RNF04)
 * Com Avaliações de Clientes, Preços Reais e Encomendas Personalizadas
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Vitrine Virtual de Presentes & Aromas';
require_once __DIR__ . '/includes/header.php';

$categorias = getCategorias();
$produtosDestaque = getProdutos(null, null, 8); // 8 primeiros produtos com preços reais

// Buscar avaliações reais de clientes com NPS
$db = getDBConnection();
$avaliacoesDestaque = $db->query("
    SELECT a.*, p.nome as produto_nome, p.imagem_url as produto_imagem
    FROM avaliacoes a
    INNER JOIN produtos p ON a.produto_id = p.id
    ORDER BY a.id DESC LIMIT 4
")->fetchAll();
?>

<div class="container">
    <!-- Banner Principal / Hero -->
    <section class="hero-section">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fa-solid fa-sparkles"></i> Criações Exclusivas Feitas à Mão
                </div>
                <h2>Aconchego, aromas e <span>afeto</span> em cada detalhe.</h2>
                <p>Descubra nossa linha de velas aromáticas com cera vegetal, sabonetes botânicos e amigurumis personalizados. Presentes perfeitos para celebrar momentos inesquecíveis.</p>
                <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                    <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary btn-lg">
                        <i class="fa-solid fa-basket-shopping"></i> Explorar Vitrine
                    </a>
                    <a href="<?= BASE_URL ?>/contato.php" class="btn btn-secondary btn-lg">
                        <i class="fa-regular fa-envelope"></i> Pedir Encomenda
                    </a>
                    <a href="<?= BASE_URL ?>/chat.php" class="btn btn-outline btn-lg" style="background: #fff;">
                        <i class="fa-regular fa-comments"></i> Falar no Chat
                    </a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-image-wrapper">
                    <img src="<?= BASE_URL ?>/assets/img/hero_artesanal.svg" alt="Amora Artesanal Ilustração" loading="lazy">
                </div>
            </div>
        </div>
    </section>

    <!-- Navegação Rápida por Categorias -->
    <section style="margin: 40px 0 20px;">
        <div class="section-title">
            <h2>Nossas Coleções Artesanais</h2>
            <p>Selecione uma categoria para descobrir criações manuais com fragrâncias e texturas únicas</p>
        </div>

        <div class="categories-pills">
            <a href="<?= BASE_URL ?>/produtos.php" class="pill-item active">
                <i class="fa-solid fa-border-all"></i> Todas as Peças
            </a>
            <?php foreach ($categorias as $cat): ?>
                <a href="<?= BASE_URL ?>/produtos.php?categoria=<?= $cat['id'] ?>" class="pill-item">
                    <?php if (stripos($cat['nome_categoria'], 'vela') !== false): ?>
                        <i class="fa-solid fa-fire-flame-curved"></i>
                    <?php elseif (stripos($cat['nome_categoria'], 'sabonete') !== false): ?>
                        <i class="fa-solid fa-soap"></i>
                    <?php elseif (stripos($cat['nome_categoria'], 'amigurumi') !== false): ?>
                        <i class="fa-solid fa-heart"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-gift"></i>
                    <?php endif; ?>
                    <?= sanitizar($cat['nome_categoria']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Vitrine de Produtos em Destaque com Preços Reais -->
    <section>
        <div class="products-grid">
            <?php foreach ($produtosDestaque as $produto): ?>
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
                                    <i class="fa-solid fa-box-archive"></i> <?= $produto['estoque'] ?> em estoque
                                </span>
                            </div>
                            <a href="<?= BASE_URL ?>/produto.php?id=<?= $produto['id'] ?>" class="btn btn-secondary btn-sm" title="Ver detalhes e personalizar">
                                Personalizar <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="text-align: center; margin-bottom: 60px;">
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-list-check"></i> Ver Todos os Produtos do Catálogo
            </a>
        </div>
    </section>

    <!-- Seção de Avaliações Reais de Clientes (Depoimentos & Pergunta NPS) -->
    <?php if (!empty($avaliacoesDestaque)): ?>
        <section style="margin-bottom: 60px;">
            <div class="section-title">
                <h2>O Que Nossos Clientes Dizem</h2>
                <p>Relatos verdadeiros de quem já comprou, perfumou o lar ou presenteou com a Amora Artesanal</p>
                <div style="margin-top: 10px;">
                    <span class="badge-status entregue" style="font-size: 0.85rem; padding: 6px 16px;">
                        <i class="fa-solid fa-thumbs-up"></i> 100% dos clientes indicam a Amora Artesanal para amigos
                    </span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 24px;">
                <?php foreach ($avaliacoesDestaque as $av): ?>
                    <div style="background: var(--cor-branco); padding: 24px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <!-- Estrelas -->
                            <div style="color: #f59e0b; font-size: 1.1rem; margin-bottom: 8px;">
                                <?= str_repeat('★', $av['estrelas']) ?><?= str_repeat('☆', 5 - $av['estrelas']) ?>
                            </div>

                            <!-- Comentário -->
                            <p style="font-size: 0.92rem; color: var(--cor-marrom-texto); line-height: 1.6; font-style: italic; margin-bottom: 16px;">
                                "<?= sanitizar($av['comentario']) ?>"
                            </p>
                        </div>

                        <div style="border-top: 1px solid var(--cor-borda); padding-top: 12px; display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <strong style="font-size: 0.9rem; color: var(--cor-marrom-suave); display: block;">
                                    <?= sanitizar($av['nome_cliente']) ?>
                                </strong>
                                <span style="font-size: 0.78rem; color: var(--cor-sucesso);">
                                    <i class="fa-solid fa-circle-check"></i> Compra Verificada
                                </span>
                            </div>

                            <!-- Tag NPS Indicação a Amigo -->
                            <?php if ($av['indicaria_amigo'] === 'sim'): ?>
                                <span style="font-size: 0.72rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 4px 8px; border-radius: 6px; font-weight: 600;">
                                    Indica para amigos!
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Seção de Diferenciais Artesanais -->
    <section style="background: var(--cor-branco); border-radius: var(--raio-borda-lg); padding: 40px; margin-bottom: 60px; border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 30px; text-align: center;">
            <div>
                <div style="width: 60px; height: 60px; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem;">
                    <i class="fa-solid fa-hands-holding"></i>
                </div>
                <h4 style="margin-bottom: 8px;">100% Artesanal</h4>
                <p style="font-size: 0.88rem; color: var(--cor-marrom-claro);">Cada peça é moldada, decorada e aromatizada à mão com ingredientes botânicos selecionados.</p>
            </div>
            <div>
                <div style="width: 60px; height: 60px; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h4 style="margin-bottom: 8px;">Personalização Exclusiva</h4>
                <p style="font-size: 0.88rem; color: var(--cor-marrom-claro);">Adicione nomes na etiqueta, escolha laços e aromas para criar um presente único.</p>
            </div>
            <div>
                <div style="width: 60px; height: 60px; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem;">
                    <i class="fa-solid fa-leaf"></i>
                </div>
                <h4 style="margin-bottom: 8px;">Ceras & Óleos Vegetais</h4>
                <p style="font-size: 0.88rem; color: var(--cor-marrom-claro);">Respeitamos a saúde e o meio ambiente com cera de coco, pavio 100% algodão e formulação vegana.</p>
            </div>
            <div>
                <div style="width: 60px; height: 60px; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 1.5rem;">
                    <i class="fa-solid fa-truck-fast"></i>
                </div>
                <h4 style="margin-bottom: 8px;">Rastreamento & Garantia</h4>
                <p style="font-size: 0.88rem; color: var(--cor-marrom-claro);">Acompanhamento no mapa em tempo real e reembolso integral garantido caso ocorra atraso.</p>
            </div>
        </div>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
