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
    if (isset($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        if (empty($_SESSION['usuario']['codigo_cliente']) && !empty($_SESSION['usuario']['id'])) {
            $_SESSION['usuario']['codigo_cliente'] = sprintf('CLI-%05d', (int)$_SESSION['usuario']['id']);
        }
        return $_SESSION['usuario'];
    }
    return null;
}

/**
 * Retorna o código único e padronizado do cliente (#CLI-0000X).
 * Garante que nenhum cliente seja confundido com outro.
 */
function getCodigoCliente(int $id): string {
    return sprintf('CLI-%05d', $id);
}

/**
 * Salva e persiste informações pessoais e de endereço da conta do cliente.
 * Atualiza no banco de dados e sincroniza com a sessão ativa.
 */
function salvarDadosPessoaisCliente(int $usuarioId, array $dados): bool {
    $db = getDBConnection();
    $stmt = $db->prepare("UPDATE usuarios SET 
        telefone = COALESCE(NULLIF(?, ''), telefone),
        cep = COALESCE(NULLIF(?, ''), cep),
        logradouro = COALESCE(NULLIF(?, ''), logradouro),
        numero = COALESCE(NULLIF(?, ''), numero),
        complemento = COALESCE(?, complemento),
        bairro = COALESCE(NULLIF(?, ''), bairro),
        cidade = COALESCE(NULLIF(?, ''), cidade),
        estado = COALESCE(NULLIF(?, ''), estado)
        WHERE id = ?");

    $sucesso = $stmt->execute([
        $dados['telefone'] ?? '',
        $dados['cep'] ?? '',
        $dados['logradouro'] ?? '',
        $dados['numero'] ?? '',
        $dados['complemento'] ?? '',
        $dados['bairro'] ?? '',
        $dados['cidade'] ?? '',
        $dados['estado'] ?? '',
        $usuarioId
    ]);

    if ($sucesso && isset($_SESSION['usuario']) && (int)$_SESSION['usuario']['id'] === $usuarioId) {
        foreach (['telefone', 'cep', 'logradouro', 'numero', 'complemento', 'bairro', 'cidade', 'estado'] as $campo) {
            if (isset($dados[$campo]) && ($dados[$campo] !== '' || $campo === 'complemento')) {
                $_SESSION['usuario'][$campo] = $dados[$campo];
            }
        }
    }

    return $sucesso;
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
 * Carrega e salva na sessão todas as informações pessoais cadastradas da conta.
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
            'codigo_cliente' => sprintf('CLI-%05d', (int)$usuario['id']),
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
 * Cadastra um novo cliente com dados pessoais e endereço:
 * - O e-mail DEVE OBRIGATORIAMENTE terminar com @gmail.com
 * - Telefone com limite de números/caracteres (máx 15 caracteres / 11 dígitos)
 * - CEP com limite de números/caracteres (máx 9 caracteres / 8 dígitos)
 * - Estado com limite de letras (máx 2 caracteres, ex.: RO)
 * - Senha com limite de caracteres (máx 32 caracteres, aceita qualquer senha escolhida)
 * - Endereço aceita qualquer formato informado pelo cliente
 * - Gera o Código Único do Cliente (#CLI-0000X) para que nenhum cliente seja confundido
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
    $estado = strtoupper(trim($estado));

    // Validações obrigatórias básicas
    if (empty($nome) || empty($email) || empty($senha)) {
        return ['sucesso' => false, 'mensagem' => 'Nome completo, e-mail e senha são campos obrigatórios.'];
    }

    // Regra estrita de e-mail: deve obrigatoriamente terminar com @gmail.com
    if (!str_ends_with(strtolower($email), '@gmail.com')) {
        return ['sucesso' => false, 'mensagem' => 'O e-mail cadastrado deve terminar obrigatoriamente com @gmail.com (exemplo: seu.nome@gmail.com).'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['sucesso' => false, 'mensagem' => 'Informe um endereço de e-mail válido.'];
    }

    // Limite de senha: até 32 caracteres (qualquer senha válida)
    if (strlen($senha) > 32) {
        return ['sucesso' => false, 'mensagem' => 'A senha deve conter no máximo 32 caracteres.'];
    }

    if ($senha !== $confirmarSenha) {
        return ['sucesso' => false, 'mensagem' => 'A confirmação de senha não confere com a senha digitada.'];
    }

    // Limite de telefone: máximo 15 caracteres / 11 dígitos
    if (!empty($telefone)) {
        $somenteDigitosTel = preg_replace('/\D/', '', $telefone);
        if (strlen($somenteDigitosTel) > 11 || strlen($telefone) > 15) {
            return ['sucesso' => false, 'mensagem' => 'O telefone deve conter no máximo 11 dígitos ou 15 caracteres (ex.: (69) 99999-9999).'];
        }
    }

    // Limite de CEP: máximo 9 caracteres / 8 dígitos
    if (!empty($cep)) {
        $somenteDigitosCep = preg_replace('/\D/', '', $cep);
        if (strlen($somenteDigitosCep) > 8 || strlen($cep) > 9) {
            return ['sucesso' => false, 'mensagem' => 'O CEP deve conter no máximo 8 números ou 9 caracteres (ex.: 76820-000).'];
        }
    }

    // Limite de Estado (UF): máximo 2 caracteres
    if (!empty($estado) && strlen($estado) > 2) {
        return ['sucesso' => false, 'mensagem' => 'O estado (UF) deve ter no máximo 2 letras (ex.: RO, SP, RJ).'];
    }

    // Endereço flexível: aceita qualquer preenchimento do cliente
    if (empty($logradouro) && empty($cidade)) {
        $logradouro = 'Endereço informado pelo cliente';
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
    $codigoCliente = sprintf('CLI-%05d', $novoId);

    return [
        'sucesso' => true,
        'mensagem' => "Cadastro realizado com sucesso! Seu código exclusivo de cliente é #{$codigoCliente}.",
        'usuario_id' => $novoId,
        'codigo_cliente' => $codigoCliente
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
