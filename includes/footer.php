<?php
/**
 * Rodapé Global do Sistema Amora Artesanal
 */
?>
    <!-- Rodapé Principal -->
    <footer class="main-footer">
        <div class="container">
            <div class="footer-grid">
                <!-- Coluna 1: Sobre a Loja -->
                <div class="footer-col">
                    <h4><?= APP_NAME ?></h4>
                    <p>Sua vitrine virtual de presentes exclusivos e criações manuais com aroma, carinho e delicadeza para marcar momentos especiais.</p>
                    <p style="font-size: 0.85rem; color: var(--cor-rosa-queimado);">
                        <i class="fa-solid fa-heart"></i> Feito com amor por Larissa Benício da Cruz
                    </p>
                </div>

                <!-- Coluna 2: Navegação Rápida -->
                <div class="footer-col">
                    <h4>Categorias</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/produtos.php?categoria=1">Velas Aromáticas</a></li>
                        <li><a href="<?= BASE_URL ?>/produtos.php?categoria=2">Sabonetes Artesanais</a></li>
                        <li><a href="<?= BASE_URL ?>/produtos.php?categoria=3">Amigurumis</a></li>
                        <li><a href="<?= BASE_URL ?>/produtos.php?categoria=4">Kits Presente</a></li>
                    </ul>
                </div>

                <!-- Coluna 3: Atendimento & Conta -->
                <div class="footer-col">
                    <h4>Atendimento</h4>
                    <ul class="footer-links">
                        <li><a href="<?= BASE_URL ?>/contato.php">Fale Conosco</a></li>
                        <li><a href="<?= BASE_URL ?>/pedidos.php">Rastrear Pedido</a></li>
                        <li><a href="<?= BASE_URL ?>/carrinho.php">Meu Carrinho</a></li>
                        <?php if (usuarioLogado()): ?>
                            <li><a href="<?= BASE_URL ?>/logout.php">Sair da Conta</a></li>
                        <?php else: ?>
                            <li><a href="<?= BASE_URL ?>/login.php">Entrar / Cadastrar</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- Coluna 4: Informações Acadêmicas / Projeto -->
                <div class="footer-col">
                    <h4>Projeto</h4>
                    <p style="font-size: 0.85rem; line-height: 1.5;">
                        <strong>IFRO - Campus Porto Velho Calama</strong><br>
                        Técnico em Informática - 3º B<br>
                        Desenvolvimento de Sistemas
                    </p>
                    <div style="margin-top: 10px;">
                        <span class="badge-status entregue" style="font-size: 0.75rem;">Versão 1.0.0</span>
                    </div>
                </div>
            </div>

            <!-- Rodapé Inferior -->
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Amora Artesanal. Todos os direitos reservados. Plataforma desenvolvida com apoio de Inteligência Artificial.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
</body>
</html>
