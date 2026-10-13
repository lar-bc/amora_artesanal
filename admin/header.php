<?php
/**
 * Cabeçalho do Painel Administrativo (Lojista)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Exige que seja administrador (UC07, UC08)
exigirAdmin();

$usuario = getUsuarioLogado();
$paginaAdmin = basename($_SERVER['PHP_SELF'] ?? 'index.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitizar($pageTitle) . ' | ' : '' ?>Painel Admin - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="background-color: #f8f6f5;">

    <header class="main-header" style="border-bottom: 2px solid var(--cor-rosa-medio);">
        <div class="container nav-wrapper">
            <div style="display: flex; align-items: center; gap: 14px;">
                <a href="<?= BASE_URL ?>/admin/index.php" class="logo-area">
                    <div class="logo-icon" style="background: var(--cor-marrom-suave);">
                        <i class="fa-solid fa-crown"></i>
                    </div>
                    <div class="logo-text">
                        <h1 style="color: var(--cor-marrom-suave);">Amora Admin</h1>
                        <span>Gestão da Vitrine Virtual</span>
                    </div>
                </a>
            </div>

            <ul class="nav-menu">
                <li><a href="<?= BASE_URL ?>/admin/index.php" class="nav-link <?= $paginaAdmin === 'index.php' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>/admin/produtos.php" class="nav-link <?= $paginaAdmin === 'produtos.php' || $paginaAdmin === 'produto_form.php' ? 'active' : '' ?>"><i class="fa-solid fa-boxes-stacked"></i> Produtos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/pedidos.php" class="nav-link <?= $paginaAdmin === 'pedidos.php' ? 'active' : '' ?>"><i class="fa-solid fa-bag-shopping"></i> Pedidos</a></li>
                <li><a href="<?= BASE_URL ?>/admin/mensagens.php" class="nav-link <?= $paginaAdmin === 'mensagens.php' ? 'active' : '' ?>"><i class="fa-regular fa-envelope"></i> Mensagens</a></li>
            </ul>

            <div class="nav-actions">
                <a href="<?= BASE_URL ?>/index.php" class="btn btn-secondary btn-sm" target="_blank" title="Abrir loja em nova aba">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver Loja
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="btn btn-outline btn-sm" title="Sair do painel">
                    <i class="fa-solid fa-right-from-bracket"></i> Sair
                </a>
            </div>
        </div>
    </header>

    <!-- Alertas Flash -->
    <div class="container" style="margin-top: 20px;">
        <?php $flash = getFlash(); ?>
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['tipo'] ?>">
                <i class="fa-solid <?= $flash['tipo'] === 'success' ? 'fa-circle-check' : ($flash['tipo'] === 'danger' ? 'fa-circle-exclamation' : 'fa-circle-info') ?>"></i>
                <span><?= sanitizar($flash['mensagem']) ?></span>
            </div>
        <?php endif; ?>
    </div>
