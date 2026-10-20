<?php
/**
 * Cadastro Completo de Clientes (RF01, RNF01, RNF02, CA01)
 * Solicita Nome Completo, Data de Nascimento, Endereço Completo, Contato e Senha
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

// Se já estiver logado, redirecionar
if (usuarioLogado()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$erro = '';
$dados = [
    'nome' => '',
    'email' => '',
    'data_nascimento' => '',
    'telefone' => '',
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'estado' => ''
];

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        foreach ($dados as $k => $v) {
            $dados[$k] = trim($_POST[$k] ?? '');
        }
        $senha = $_POST['senha'] ?? '';
        $confirmarSenha = $_POST['confirmar_senha'] ?? '';
        $redirectParam = $_POST['redirect'] ?? '';

        $resultado = cadastrarCliente(
            $dados['nome'],
            $dados['email'],
            $senha,
            $confirmarSenha,
            $dados['data_nascimento'],
            $dados['telefone'],
            $dados['cep'],
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado']
        );

        if ($resultado['sucesso']) {
            // Autenticar automaticamente e carregar os dados
            loginUsuario($dados['email'], $senha);
            setFlash('success', 'Cadastro realizado com sucesso! Seja muito bem-vindo(a) à Amora Artesanal.');

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

$pageTitle = 'Criar Conta de Cliente';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="auth-wrapper" style="max-width: 680px;">
        <div class="auth-header">
            <h2>Criar Conta de Cliente</h2>
            <p>Preencha seus dados para receber seus pedidos com segurança e rastreamento em tempo real</p>
        </div>

        <?php if (!empty($redirect) && $redirect === 'checkout'): ?>
            <div class="alert alert-info">
                <i class="fa-solid fa-lock"></i>
                <span>Cadastre-se para garantir o rastreamento exclusivo e finalizar sua compra artesanal.</span>
            </div>
        <?php endif; ?>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= sanitizar($erro) ?></span>
            </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/cadastro.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="hidden" name="redirect" value="<?= sanitizar($redirect) ?>">

            <!-- Dados Pessoais -->
            <div style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 18px; margin-bottom: 20px;">
                <h4 style="font-size: 1.1rem; color: var(--cor-rosa-queimado); margin-bottom: 14px;">
                    <i class="fa-regular fa-id-card"></i> 1. Dados Pessoais
                </h4>

                <div class="form-group">
                    <label for="nome" class="form-label">Nome Completo *</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Seu nome completo" value="<?= sanitizar($dados['nome']) ?>" required autofocus>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="data_nascimento" class="form-label">Data de Nascimento *</label>
                        <input type="date" name="data_nascimento" id="data_nascimento" class="form-control" value="<?= sanitizar($dados['data_nascimento']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefone" class="form-label">Telefone / WhatsApp *</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(69) 99999-9999" value="<?= sanitizar($dados['telefone']) ?>" required>
                    </div>
                </div>
            </div>

            <!-- Endereço Completo -->
            <div style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 18px; margin-bottom: 20px;">
                <h4 style="font-size: 1.1rem; color: var(--cor-rosa-queimado); margin-bottom: 14px;">
                    <i class="fa-solid fa-house-chimney"></i> 2. Endereço Completo de Entrega
                </h4>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                    <div class="form-group">
                        <label for="cep" class="form-label">CEP *</label>
                        <input type="text" name="cep" id="cep" class="form-control" placeholder="76800-000" value="<?= sanitizar($dados['cep']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" placeholder="Porto Velho" value="<?= sanitizar($dados['cidade']) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="logradouro" class="form-label">Rua / Avenida *</label>
                        <input type="text" name="logradouro" id="logradouro" class="form-control" placeholder="Ex.: Av. Calama" value="<?= sanitizar($dados['logradouro']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" name="numero" id="numero" class="form-control" placeholder="123" value="<?= sanitizar($dados['numero']) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" placeholder="Centro" value="<?= sanitizar($dados['bairro']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="estado" class="form-label">Estado (UF) *</label>
                        <input type="text" name="estado" id="estado" class="form-control" placeholder="RO" maxlength="2" value="<?= sanitizar($dados['estado']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" placeholder="Apto, Bloco..." value="<?= sanitizar($dados['complemento']) ?>">
                    </div>
                </div>
            </div>

            <!-- Dados de Acesso -->
            <div>
                <h4 style="font-size: 1.1rem; color: var(--cor-rosa-queimado); margin-bottom: 14px;">
                    <i class="fa-solid fa-lock"></i> 3. Dados de Acesso
                </h4>

                <div class="form-group">
                    <label for="email" class="form-label">Endereço de E-mail *</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" value="<?= sanitizar($dados['email']) ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="senha" class="form-label">Senha (mínimo 6 dígitos) *</label>
                        <input type="password" name="senha" id="senha" class="form-control" placeholder="••••••••" minlength="6" required>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="••••••••" minlength="6" required>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 18px;">
                <i class="fa-solid fa-user-plus"></i> Concluir Cadastro
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 24px; border-top: 1px solid var(--cor-borda); padding-top: 18px;">
            <p>Já possui cadastro? <a href="<?= BASE_URL ?>/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>"><strong>Acesse sua conta aqui</strong></a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
