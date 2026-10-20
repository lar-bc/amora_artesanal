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
                        <label for="telefone" class="form-label">Telefone / WhatsApp (máx 15 caracteres) *</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(69) 99999-9999" maxlength="15" value="<?= sanitizar($dados['telefone']) ?>" required>
                        <small style="color: var(--cor-marrom-claro); font-size: 0.78rem;">Limite: até 15 caracteres ou 11 números com DDD</small>
                    </div>
                </div>
            </div>

            <!-- Endereço (Pode ser qualquer um) -->
            <div style="border-bottom: 1px solid var(--cor-borda); padding-bottom: 18px; margin-bottom: 20px;">
                <h4 style="font-size: 1.1rem; color: var(--cor-rosa-queimado); margin-bottom: 6px;">
                    <i class="fa-solid fa-house-chimney"></i> 2. Endereço de Entrega
                </h4>
                <p style="font-size: 0.84rem; color: var(--cor-marrom-claro); margin-bottom: 14px;">
                    Informe qualquer endereço de sua preferência. Ele ficará salvo na sua conta para futuras entregas.
                </p>

                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px;">
                    <div class="form-group">
                        <label for="cep" class="form-label">CEP (máx 9 caracteres) *</label>
                        <input type="text" name="cep" id="cep" class="form-control" placeholder="76800-000" maxlength="9" value="<?= sanitizar($dados['cep']) ?>" required>
                        <small style="color: var(--cor-marrom-claro); font-size: 0.78rem;">Limite: até 9 caracteres (8 números)</small>
                    </div>

                    <div class="form-group">
                        <label for="cidade" class="form-label">Cidade *</label>
                        <input type="text" name="cidade" id="cidade" class="form-control" placeholder="Ex.: Porto Velho" value="<?= sanitizar($dados['cidade']) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="logradouro" class="form-label">Rua / Avenida / Qualquer Logradouro *</label>
                        <input type="text" name="logradouro" id="logradouro" class="form-control" placeholder="Qualquer rua, avenida, travessa ou condomínio" value="<?= sanitizar($dados['logradouro']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="numero" class="form-label">Número *</label>
                        <input type="text" name="numero" id="numero" class="form-control" placeholder="123 ou S/N" value="<?= sanitizar($dados['numero']) ?>" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="bairro" class="form-label">Bairro *</label>
                        <input type="text" name="bairro" id="bairro" class="form-control" placeholder="Centro ou Bairro" value="<?= sanitizar($dados['bairro']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="estado" class="form-label">Estado (UF - máx 2 letras) *</label>
                        <input type="text" name="estado" id="estado" class="form-control" placeholder="RO" maxlength="2" pattern="[A-Za-z]{2}" title="Digite a sigla do estado em 2 letras (ex.: RO, SP, RJ)" style="text-transform: uppercase;" value="<?= sanitizar($dados['estado']) ?>" required>
                        <small style="color: var(--cor-marrom-claro); font-size: 0.78rem;">Limite: 2 letras (UF)</small>
                    </div>

                    <div class="form-group">
                        <label for="complemento" class="form-label">Complemento</label>
                        <input type="text" name="complemento" id="complemento" class="form-control" placeholder="Apto, Bloco, Casa..." value="<?= sanitizar($dados['complemento']) ?>">
                    </div>
                </div>
            </div>

            <!-- Dados de Acesso -->
            <div>
                <h4 style="font-size: 1.1rem; color: var(--cor-rosa-queimado); margin-bottom: 14px;">
                    <i class="fa-solid fa-lock"></i> 3. Dados de Acesso
                </h4>

                <div class="form-group">
                    <label for="email" class="form-label">E-mail (obrigatoriamente @gmail.com) *</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="seu.nome@gmail.com" pattern=".*@gmail\.com$" title="O e-mail deve terminar obrigatoriamente com @gmail.com" value="<?= sanitizar($dados['email']) ?>" required>
                    <small style="color: var(--cor-rosa-queimado); font-size: 0.8rem; font-weight: 600;">
                        <i class="fa-solid fa-envelope"></i> O e-mail deve terminar sempre com <strong>@gmail.com</strong>
                    </small>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="senha" class="form-label">Senha (qualquer senha, máx 32 caracteres) *</label>
                        <input type="password" name="senha" id="senha" class="form-control" placeholder="Qualquer senha de sua escolha" maxlength="32" required>
                        <small style="color: var(--cor-marrom-claro); font-size: 0.78rem;">Limite: até 32 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="confirmar_senha" class="form-label">Confirmar Senha (máx 32 caracteres) *</label>
                        <input type="password" name="confirmar_senha" id="confirmar_senha" class="form-control" placeholder="Repita a senha" maxlength="32" required>
                        <small style="color: var(--cor-marrom-claro); font-size: 0.78rem;">Deve ser igual à senha digitada</small>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 18px;">
                <i class="fa-solid fa-user-plus"></i> Concluir Cadastro & Obter Código de Cliente
            </button>
        </form>

        <div class="auth-footer" style="margin-top: 24px; border-top: 1px solid var(--cor-borda); padding-top: 18px;">
            <p>Já possui cadastro? <a href="<?= BASE_URL ?>/login.php<?= !empty($redirect) ? '?redirect=' . urlencode($redirect) : '' ?>"><strong>Acesse sua conta aqui</strong></a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
