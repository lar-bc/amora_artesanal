<?php
/**
 * Autenticação de Usuários (RF02, RNF01, RNF02, CA02)
 * Tela limpa e profissional para Clientes e Administrador
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redirecionar
if (usuarioLogado()) {
    if (isAdmin()) {
        header('Location: ' . BASE_URL . '/admin/index.php');
    } else {
        $redirect = $_GET['redirect'] ?? '';
        if ($redirect === 'checkout') {
            header('Location: ' . BASE_URL . '/checkout.php');
        } else {
            header('Location: ' . BASE_URL . '/index.php');
        }
    }
    exit;
}

$erro = '';
$email = '';
$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $email = $_POST['email'] ?? '';
        $senha = $_POST['senha'] ?? '';
        $redirectParam = $_POST['redirect'] ?? '';

        $resultado = loginUsuario($email, $senha);
        if ($resultado['sucesso']) {
            setFlash('success', 'Bem-vindo(a) de volta, ' . htmlspecialchars($resultado['usuario']['nome']) . '!');

            if ($resultado['tipo_usuario'] === 'admin') {
                header('Location: ' . BASE_URL . '/admin/index.php');
                exit;
            }

            if ($redirectParam === 'checkout') {
                header('Location: ' . BASE_URL . '/checkout.php');
                exit;
            }

            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

$pageTitle = 'Entrar na Conta';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 40px auto 60px;">
    <div class="auth-wrapper">
        <div class="auth-header">
            <h2>Acessar Conta</h2>
            <p>Entre com seu e-mail e senha cadastrados para continuar</p>
        </div>

        <?php if (!empty($redirect) && $redirect === 'checkout'): ?>
            <div class="alert alert-warning" style="font-size: 0.88rem;">
                <i class="fa-solid fa-lock"></i>
                <span>Para finalizar seu pedido com segurança e entrega garantida, faça seu login ou cadastre-se abaixo.</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= sanitizar($erro) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/login.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="redirect" value="<?= sanitizar($redirect) ?>">

            <div class="form-group">
                <label for="email" class="form-label">Endereço de E-mail</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" value="<?= sanitizar($email) ?>" required autofocus>
            </div>

            <div class="form-group">
                <label for="senha" class="form-label">Senha</label>
                <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 14px;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 28px; border-top: 1px solid var(--cor-borda); padding-top: 20px;">
            <p>Ainda não possui conta? <a href="<?= BASE_URL ?>/cadastro.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>"><strong>Cadastre-se com entrega completa</strong></a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
