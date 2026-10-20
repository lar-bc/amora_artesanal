<?php
/**
 * Painel Administrativo de Atendimento e Chat com Clientes
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

// Obter lista de clientes com conversas
$stmtClientes = $db->query("
    SELECT u.id, u.nome, u.email, u.telefone, 
           MAX(m.data_envio) as ultima_mensagem_data,
           SUM(CASE WHEN m.remetente = 'cliente' AND m.lida = 0 THEN 1 ELSE 0 END) as nao_lidas
    FROM usuarios u
    INNER JOIN mensagens_chat m ON u.id = m.usuario_id
    GROUP BY u.id
    ORDER BY ultima_mensagem_data DESC
");
$clientesComConversa = $stmtClientes->fetchAll();

// Cliente selecionado para visualização
$clienteIdAtivo = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : ($clientesComConversa[0]['id'] ?? 0);

// Processar envio de resposta da Administradora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $clienteIdAtivo > 0) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $resposta = trim($_POST['resposta'] ?? '');
        if (!empty($resposta)) {
            $stmtResp = $db->prepare("INSERT INTO mensagens_chat (usuario_id, remetente, mensagem) VALUES (?, 'admin', ?)");
            $stmtResp->execute([$clienteIdAtivo, $resposta]);

            // Marcar mensagens do cliente como lidas
            $stmtLida = $db->prepare("UPDATE mensagens_chat SET lida = 1 WHERE usuario_id = ? AND remetente = 'cliente'");
            $stmtLida->execute([$clienteIdAtivo]);

            setFlash('success', 'Resposta enviada com sucesso ao cliente!');
            header('Location: ' . BASE_URL . '/admin/chat.php?cliente_id=' . $clienteIdAtivo);
            exit;
        }
    }
}

// Buscar dados e mensagens do cliente ativo
$clienteAtivo = null;
$mensagensAtivas = [];

if ($clienteIdAtivo > 0) {
    $stmtUser = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmtUser->execute([$clienteIdAtivo]);
    $clienteAtivo = $stmtUser->fetch();

    // Marcar como lida ao abrir
    $db->prepare("UPDATE mensagens_chat SET lida = 1 WHERE usuario_id = ? AND remetente = 'cliente'")->execute([$clienteIdAtivo]);

    $stmtMsgs = $db->prepare("SELECT * FROM mensagens_chat WHERE usuario_id = ? ORDER BY id ASC");
    $stmtMsgs->execute([$clienteIdAtivo]);
    $mensagensAtivas = $stmtMsgs->fetchAll();
}

$pageTitle = 'Chat de Atendimento ao Cliente';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="admin-header-actions">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Atendimento em Tempo Real</h1>
            <p style="color: var(--cor-marrom-claro);">Converse diretamente com os clientes da loja e tire dúvidas sobre encomendas</p>
        </div>
    </div>

    <?php if (empty($clientesComConversa)): ?>
        <div class="admin-card" style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 3rem; color: var(--cor-rosa-medio); margin-bottom: 14px;">
                <i class="fa-regular fa-comments"></i>
            </div>
            <p style="color: var(--cor-marrom-claro); font-size: 1.1rem;">Nenhuma conversa iniciada por clientes ainda.</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
            <!-- Lista Lateral de Clientes -->
            <div class="admin-card" style="padding: 16px; margin: 0; max-height: 580px; overflow-y: auto;">
                <h4 style="font-size: 1rem; color: var(--cor-marrom-suave); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--cor-borda);">
                    Clientes em Conversa (<?= count($clientesComConversa) ?>)
                </h4>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($clientesComConversa as $c): ?>
                        <a href="<?= BASE_URL ?>/admin/chat.php?cliente_id=<?= $c['id'] ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border-radius: 8px; border: 1px solid <?= ($c['id'] == $clienteIdAtivo) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>; background: <?= ($c['id'] == $clienteIdAtivo) ? 'var(--cor-rosa-claro)' : '#fff' ?>; transition: var(--transicao);">
                            <div>
                                <strong style="color: var(--cor-marrom-suave); font-size: 0.92rem; display: block;"><?= sanitizar($c['nome']) ?></strong>
                                <span style="font-size: 0.78rem; color: var(--cor-marrom-claro);"><?= sanitizar($c['telefone'] ?? $c['email']) ?></span>
                            </div>
                            <?php if ($c['nao_lidas'] > 0): ?>
                                <span class="badge-status preparacao" style="font-size: 0.75rem; font-weight: bold;">
                                    <?= $c['nao_lidas'] ?> nova(s)
                                </span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Caixa de Chat Ativa -->
            <div class="admin-card" style="padding: 0; margin: 0; overflow: hidden; display: flex; flex-direction: column; height: 580px;">
                <?php if ($clienteAtivo): ?>
                    <!-- Topo da Conversa com Dados do Cliente -->
                    <div style="background: var(--cor-rosa-claro); padding: 16px 20px; border-bottom: 1px solid var(--cor-borda); display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="font-size: 1.1rem; color: var(--cor-marrom-suave);"><?= sanitizar($clienteAtivo['nome']) ?></strong>
                            <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                E-mail: <?= sanitizar($clienteAtivo['email']) ?> &bull; Tel: <?= sanitizar($clienteAtivo['telefone'] ?? 'Não informado') ?>
                            </div>
                        </div>
                        <div>
                            <?php if (!empty($clienteAtivo['telefone'])): ?>
                                <a href="https://wa.me/55<?= preg_replace('/\D/', '', $clienteAtivo['telefone']) ?>" target="_blank" class="btn btn-secondary btn-sm" title="Abrir WhatsApp">
                                    <i class="fa-brands fa-whatsapp" style="color: #25d366;"></i> WhatsApp
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Histórico da Conversa -->
                    <div style="flex-grow: 1; overflow-y: auto; padding: 20px; background: #fffcf9; display: flex; flex-direction: column; gap: 12px;">
                        <?php foreach ($mensagensAtivas as $msg): ?>
                            <?php $isAdminMsg = $msg['remetente'] === 'admin'; ?>
                            <div style="align-self: <?= $isAdminMsg ? 'flex-end' : 'flex-start' ?>; max-width: 75%; background: <?= $isAdminMsg ? 'var(--cor-rosa-claro)' : '#fff' ?>; padding: 12px 16px; border-radius: <?= $isAdminMsg ? '12px 12px 2px 12px' : '12px 12px 12px 2px' ?>; border: 1px solid <?= $isAdminMsg ? 'var(--cor-rosa-medio)' : 'var(--cor-borda)' ?>; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                                <div style="font-weight: 600; font-size: 0.78rem; color: <?= $isAdminMsg ? 'var(--cor-rosa-queimado)' : 'var(--cor-marrom-suave)' ?>; margin-bottom: 2px;">
                                    <?= $isAdminMsg ? 'Você (Administradora)' : sanitizar($clienteAtivo['nome']) ?>
                                </div>
                                <div style="font-size: 0.9rem; color: var(--cor-marrom-texto); line-height: 1.5;">
                                    <?= nl2br(sanitizar($msg['mensagem'])) ?>
                                </div>
                                <div style="font-size: 0.7rem; color: var(--cor-marrom-claro); text-align: right; margin-top: 4px;">
                                    <?= date('d/m/Y H:i', strtotime($msg['data_envio'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Enviar Resposta -->
                    <div style="padding: 16px; background: #fff; border-top: 1px solid var(--cor-borda);">
                        <form action="<?= BASE_URL ?>/admin/chat.php?cliente_id=<?= $clienteIdAtivo ?>" method="POST" style="display: flex; gap: 10px; margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="text" name="resposta" class="form-control" placeholder="Escreva a resposta do ateliê..." required autofocus style="flex-grow: 1;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-paper-plane"></i> Responder
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px 20px; color: var(--cor-marrom-claro);">
                        Selecione um cliente na lista ao lado para visualizar a conversa.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
