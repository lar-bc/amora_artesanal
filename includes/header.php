<?php
/**
 * Cabeçalho Global do Sistema Amora Artesanal
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Garantir inicialização do banco caso necessário
require_once __DIR__ . '/../database/init_db.php';
inicializarBanco();

$usuarioAtual = getUsuarioLogado();
$totaisCarrinho = getTotaisCarrinho();
$paginaAtual = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitizar($pageTitle) . ' | ' : '' ?><?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <!-- FontAwesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

    <!-- Barra de Notificação Superior com Central de Ajuda Oficial -->
    <div class="top-bar">
        <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
            <span>✨ Produtos feitos à mão com afeto e exclusividade | Encomendas sob medida com carinho</span>
            <div style="display: flex; gap: 16px; align-items: center;">
                <span><i class="fa-solid fa-headset"></i> SAC Central de Ajuda: <strong>0800 892 4567</strong></span>
                <span><i class="fa-brands fa-whatsapp"></i> WhatsApp: <strong>(69) 98412-3456</strong></span>
            </div>
        </div>
    </div>

    <!-- Cabeçalho Principal -->
    <header class="main-header">
        <div class="container nav-wrapper">
            <!-- Logo -->
            <a href="<?= BASE_URL ?>/index.php" class="logo-area">
                <div class="logo-icon">
                    <i class="fa-solid fa-heart"></i>
                </div>
                <div class="logo-text">
                    <h1><?= APP_NAME ?></h1>
                    <span><?= APP_TAGLINE ?></span>
                </div>
            </a>

            <!-- Menu de Navegação -->
            <ul class="nav-menu">
                <li><a href="<?= BASE_URL ?>/index.php" class="nav-link <?= $paginaAtual === 'index.php' ? 'active' : '' ?>">Início</a></li>
                <li><a href="<?= BASE_URL ?>/produtos.php" class="nav-link <?= $paginaAtual === 'produtos.php' ? 'active' : '' ?>">Produtos</a></li>
                <li><a href="<?= BASE_URL ?>/contato.php" class="nav-link <?= $paginaAtual === 'contato.php' ? 'active' : '' ?>">Fale Conosco</a></li>
                <li><a href="<?= BASE_URL ?>/pedidos.php" class="nav-link <?= $paginaAtual === 'pedidos.php' ? 'active' : '' ?>">Rastrear Pedido</a></li>
                <li><a href="<?= BASE_URL ?>/chat.php" class="nav-link <?= $paginaAtual === 'chat.php' ? 'active' : '' ?>"><i class="fa-regular fa-comments"></i> Chat Ateliê</a></li>
                <?php if (isAdmin()): ?>
                    <li><a href="<?= BASE_URL ?>/admin/index.php" class="nav-link" style="color: var(--cor-rosa-queimado); font-weight: 700;"><i class="fa-solid fa-gauge-high"></i> Painel Admin</a></li>
                <?php endif; ?>
            </ul>

            <!-- Ações: Usuário e Carrinho -->
            <div class="nav-actions">
                <?php if (usuarioLogado()): ?>
                    <div style="font-size: 0.9rem; color: var(--cor-marrom-suave); display: flex; align-items: center; gap: 8px;">
                        <span title="Seu Código Único de Identificação" style="background: var(--cor-rosa-claro); border: 1px solid var(--cor-rosa-medio); padding: 3px 8px; border-radius: 12px; font-weight: 700; color: var(--cor-rosa-queimado); font-size: 0.78rem;">
                            <i class="fa-solid fa-id-badge"></i> #<?= sanitizar($usuarioAtual['codigo_cliente'] ?? ('CLI-' . str_pad($usuarioAtual['id'], 5, '0', STR_PAD_LEFT))) ?>
                        </span>
                        <span>Olá, <strong><?= sanitizar(explode(' ', $usuarioAtual['nome'])[0]) ?></strong></span>
                        <a href="<?= BASE_URL ?>/logout.php" title="Sair da conta" class="btn-icon" style="width: 36px; height: 36px; font-size: 0.9rem;">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login.php" class="btn btn-secondary btn-sm">
                        <i class="fa-regular fa-user"></i> Entrar
                    </a>
                <?php endif; ?>

                <!-- Botão do Carrinho -->
                <a href="<?= BASE_URL ?>/carrinho.php" class="btn-icon" title="Ver Carrinho de Compras">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="badge-count"><?= $totaisCarrinho['quantidade'] ?></span>
                </a>
            </div>
        </div>
    </header>

    <!-- Exibição de Mensagens Flash -->
    <div class="container" style="margin-top: 20px;">
        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['tipo'] ?>">
                <i class="fa-solid <?= $flash['tipo'] === 'success' ? 'fa-circle-check' : ($flash['tipo'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
                <span><?= sanitizar($flash['mensagem']) ?></span>
            </div>
        <?php endif; ?>
    </div>
