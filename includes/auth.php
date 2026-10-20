<?php
/**
 * Módulo de Autenticação e Controle de Sessão
 * Atende aos requisitos RF01, RF02, RNF01, RNF02 e Cadastro Completo
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Verifica se há um usuário autenticado.
 */
function usuarioLogado(): bool {
    return !empty($_SESSION['usuario']) && isset($_SESSION['usuario']['id']);
}

/**
 * Retorna os dados do usuário autenticado.
 */
function getUsuarioLogado(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Verifica se o usuário autenticado é administrador.
 */
function isAdmin(): bool {
    return usuarioLogado() && (($_SESSION['usuario']['tipo_usuario'] ?? '') === 'admin');
}

/**
 * Exige que o usuário esteja logado para acessar a página.
 */
function exigirLogin(): void {
    if (!usuarioLogado()) {
        setFlash('warning', 'Para continuar e finalizar seu pedido, você precisa estar cadastrado e conectado.');
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/**
 * Exige que o usuário seja administrador para acessar a página.
 */
function exigirAdmin(): void {
    if (!usuarioLogado()) {
        setFlash('warning', 'Área restrita. Por favor, autentique-se como administrador.');
        header('Location: ' . BASE_URL . '/login.php?redirect=admin');
        exit;
    }

    if (!isAdmin()) {
        setFlash('danger', 'Acesso negado. Você não possui permissão de administrador.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }
}

/**
 * Realiza o login do usuário validando com password_verify (RNF01, RF02).
 */
function loginUsuario(string $email, string $senha): array {
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    if (empty($email) || empty($senha)) {
        return ['sucesso' => false, 'mensagem' => 'Preencha o e-mail e a senha.'];
    }

    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
        // Regenerar ID de sessão para segurança contra session fixation
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        $_SESSION['usuario'] = [
            'id' => (int)$usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'tipo_usuario' => $usuario['tipo_usuario'],
            'data_nascimento' => $usuario['data_nascimento'] ?? '',
            'telefone' => $usuario['telefone'] ?? '',
            'cep' => $usuario['cep'] ?? '',
            'logradouro' => $usuario['logradouro'] ?? '',
            'numero' => $usuario['numero'] ?? '',
            'complemento' => $usuario['complemento'] ?? '',
            'bairro' => $usuario['bairro'] ?? '',
            'cidade' => $usuario['cidade'] ?? '',
            'estado' => $usuario['estado'] ?? ''
        ];

        return [
            'sucesso' => true,
            'usuario' => $_SESSION['usuario'],
            'tipo_usuario' => $usuario['tipo_usuario']
        ];
    }

    return ['sucesso' => false, 'mensagem' => 'E-mail ou senha incorretos.'];
}

/**
 * Cadastra um novo cliente com dados completos:
 * Nome completo, Data de Nascimento, Endereço completo, Telefone e Senha Bcrypt
 */
function cadastrarCliente(
    string $nome,
    string $email,
    string $senha,
    string $confirmarSenha,
    string $dataNascimento = '',
    string $telefone = '',
    string $cep = '',
    string $logradouro = '',
    string $numero = '',
    string $complemento = '',
    string $bairro = '',
    string $cidade = '',
    string $estado = ''
): array {
    $nome = trim($nome);
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $dataNascimento = trim($dataNascimento);
    $telefone = trim($telefone);
    $cep = trim($cep);
    $logradouro = trim($logradouro);
    $numero = trim($numero);
    $complemento = trim($complemento);
    $bairro = trim($bairro);
    $cidade = trim($cidade);
    $estado = trim($estado);

    // Validações obrigatórias
    if (empty($nome) || empty($email) || empty($senha)) {
        return ['sucesso' => false, 'mensagem' => 'Nome completo, e-mail e senha são campos obrigatórios.'];
    }

    if (empty($dataNascimento)) {
        return ['sucesso' => false, 'mensagem' => 'Por favor, informe sua data de nascimento.'];
    }

    if (empty($cep) || empty($logradouro) || empty($numero) || empty($bairro) || empty($cidade) || empty($estado)) {
        return ['sucesso' => false, 'mensagem' => 'Por favor, preencha todos os campos do seu endereço completo.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um endereço de e-mail válido.'];
    }

    if (strlen($senha) < 6) {
        return ['sucesso' => false, 'mensagem' => 'A senha deve conter no mínimo 6 caracteres.'];
    }

    if ($senha !== $confirmarSenha) {
        return ['sucesso' => false, 'mensagem' => 'As senhas não coincidem.'];
    }

    $db = getDBConnection();

    // Verificar se o e-mail já existe
    $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['sucesso' => false, 'mensagem' => 'Este e-mail já está cadastrado. Tente fazer login.'];
    }

    // Gerar hash seguro Bcrypt (RNF01)
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

    $stmtInsert = $db->prepare("INSERT INTO usuarios 
        (nome, email, senha_hash, tipo_usuario, data_nascimento, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado) 
        VALUES (?, ?, ?, 'cliente', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmtInsert->execute([
        $nome, $email, $senhaHash, $dataNascimento, $telefone, $cep, $logradouro, $numero, $complemento, $bairro, $cidade, $estado
    ]);

    $novoId = (int)$db->lastInsertId();

    return [
        'sucesso' => true,
        'mensagem' => 'Cadastro realizado com sucesso! Seja bem-vindo(a) ao Ateliê Amora Artesanal.',
        'usuario_id' => $novoId
    ];
}

/**
 * Encerra a sessão do usuário com segurança.
 */
function fazerLogout(): void {
    $_SESSION['usuario'] = null;
    unset($_SESSION['usuario']);
    session_destroy();
}
