<?php
/**
 * Formulário de Contato e Encomendas Personalizadas (RF11, RNF02)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$erro = '';
$sucesso = '';
$nome = '';
$email = '';
$mensagem = '';

$usuario = getUsuarioLogado();
if ($usuario) {
    $nome = $usuario['nome'];
    $email = $usuario['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $mensagem = trim($_POST['mensagem'] ?? '');

        if (empty($nome) || empty($email) || empty($mensagem)) {
            $erro = 'Por favor, preencha todos os campos do formulário.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Por favor, informe um endereço de e-mail válido.';
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("INSERT INTO mensagens_contato (nome, email, mensagem) VALUES (?, ?, ?)");
            $stmt->execute([$nome, $email, $mensagem]);

            setFlash('success', 'Mensagem enviada com sucesso! Em breve nossa equipe entrará em contato com você.');
            header('Location: ' . BASE_URL . '/contato.php');
            exit;
        }
    }
}

$pageTitle = 'Fale Conosco';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="text-align: center; max-width: 650px; margin: 0 auto 36px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;">Fale Conosco</h1>
        <p style="color: var(--cor-marrom-claro);">Tire dúvidas sobre aromas, solicite encomendas especiais para festas e casamentos ou deixe uma mensagem carinhosa</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 40px; background: var(--cor-branco); padding: 40px; border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
        <!-- Informações de Contato e Horários -->
        <div style="background: var(--cor-rosa-claro); padding: 30px; border-radius: var(--raio-borda); border: 1px solid var(--cor-rosa-medio); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.4rem; color: var(--cor-rosa-queimado); margin-bottom: 16px;">
                    Ateliê Amora Artesanal
                </h3>
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--cor-marrom-texto); margin-bottom: 24px;">
                    Produzimos itens que acolhem o coração. Entre em contato conosco para personalizar lembrancinhas de batizados, aniversários e brindes corporativos aromáticos.
                </p>

                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.92rem;">
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 38px; height: 38px;"><i class="fa-solid fa-location-dot"></i></div>
                        <div>Porto Velho - Rondônia / Brasil</div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 38px; height: 38px;"><i class="fa-solid fa-envelope"></i></div>
                        <div>contato@amoraartesanal.com.br</div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 38px; height: 38px;"><i class="fa-brands fa-whatsapp"></i></div>
                        <div>(69) 99999-8888 (Atendimento via WhatsApp)</div>
                    </div>

                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 38px; height: 38px;"><i class="fa-solid fa-clock"></i></div>
                        <div>Segunda a Sexta: 08:00 às 18:00</div>
                    </div>
                </div>
            </div>

            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--cor-borda); font-size: 0.85rem; color: var(--cor-marrom-claro);">
                ✨ Respondemos todas as mensagens em até 24 horas úteis.
            </div>
        </div>

        <!-- Formulário de Contato (RF11, RNF02) -->
        <div>
            <h3 style="font-size: 1.4rem; margin-bottom: 20px; color: var(--cor-marrom-suave);">Envie sua Mensagem</h3>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= sanitizar($erro) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/contato.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="form-group">
                    <label for="nome" class="form-label">Seu Nome *</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Como podemos te chamar?" value="<?= sanitizar($nome) ?>" required>
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Seu E-mail *</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" value="<?= sanitizar($email) ?>" required>
                </div>

                <div class="form-group">
                    <label for="mensagem" class="form-label">Sua Mensagem / Detalhes da Encomenda *</label>
                    <textarea name="mensagem" id="mensagem" class="form-control" rows="5" placeholder="Conte-nos como podemos te ajudar..." required><?= sanitizar($mensagem) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                    <i class="fa-regular fa-paper-plane"></i> Enviar Mensagem
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
