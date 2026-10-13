<?php
/**
 * Cadastro de Novos Clientes (RF01, RNF01, RNF02, CA01)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redirecionar
if (usuarioLogado()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$erro = '';
$nome = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $nome = $_POST['nome'] ?? '';
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';

        $resultado = cadastrarCliente($nome, $email, $senha, $confirmarSenha);
        if ($resultado['sucesso']) {
            // Autenticar automaticamente
            loginUsuario($email, $senha);
            setFlash('success', 'Cadastro realizado com sucesso! Bem-vindo(a) ao Amora Artesanal.');
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

$pageTitle = 'Criar Conta';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
    <div class="auth-wrapper">
        <div class="auth-header">
            <h2>Criar Conta</h2>
            <p>Junte-se à nossa comunidade e encomende produtos artesanais exclusivos</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= sanitizar($erro) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/cadastro.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div class="form-group">
                <label for="nome" class="form-label">Nome Completo</label>
                <input type="text" name="nome" id="nome" class="form-control" placeholder="Seu nome" value="<?= sanitizar($nome) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">Endereço de E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" value="<?= sanitizar($email) ?>" required>
            </div>

            <div class="form-group">
                <label for="senha" class="form-label">Senha (mínimo 6 dígitos)</label>
                <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="confirmar_senha" class="form-label">Confirmar Senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="••••••••" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Finalizar Cadastro
            </button>
        </form>

        <div class="auth-footer">
            <p>Já possui uma conta? <a href="<?= BASE_URL ?>/login.php"><strong>Faça login aqui</strong></a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
