<?php
/**
 * Chat em Tempo Real entre o Cliente e o Ateliê Amora Artesanal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();

$usuario = getUsuarioLogado();
$db = getDBConnection();

// Processar envio de mensagem pelo cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $mensagem = trim($_POST['mensagem'] ?? '');
        if (!empty($mensagem)) {
            $stmt = $db->prepare("INSERT INTO mensagens_chat (usuario_id, remetente, mensagem) VALUES (?, 'cliente', ?)");
            $stmt->execute([$usuario['id'], $mensagem]);
            header('Location: ' . BASE_URL . '/chat.php');
            exit;
        }
    }
}

// Buscar histórico de mensagens deste cliente
$stmtChat = $db->prepare("SELECT * FROM mensagens_chat WHERE usuario_id = ? ORDER BY id ASC");
$stmtChat->execute([$usuario['id']]);
$mensagens = $stmtChat->fetchAll();

$pageTitle = 'Chat com o Ateliê';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px; max-width: 850px;">
    <!-- Cabeçalho do Chat -->
    <div style="background: var(--cor-branco); border: 1px solid var(--cor-borda); border-radius: var(--raio-borda-lg) var(--raio-borda-lg) 0 0; padding: 20px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--cor-rosa-medio);">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(135deg, var(--cor-rosa-queimado), #df8d9c); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.4rem; box-shadow: 0 4px 10px rgba(200, 109, 126, 0.3);">
                <i class="fa-solid fa-heart"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <h2 style="font-size: 1.3rem; margin: 0; color: var(--cor-marrom-suave);">Atendimento Amora Artesanal</h2>
                    <span style="background: var(--cor-rosa-claro); border: 1px solid var(--cor-rosa-queimado); color: var(--cor-rosa-queimado); font-weight: 700; padding: 2px 8px; border-radius: 12px; font-size: 0.78rem;">
                        #<?= sanitizar($usuario['codigo_cliente'] ?? ('CLI-' . str_pad($usuario['id'], 5, '0', STR_PAD_LEFT))) ?>
                    </span>
                </div>
                <div style="font-size: 0.8rem; color: var(--cor-sucesso); display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; background: #4e8d53; border-radius: 50%;"></span>
                    Equipe online &bull; Cliente: <strong><?= sanitizar($usuario['nome']) ?></strong>
                </div>
            </div>
        </div>

        <div>
            <a href="https://wa.me/5569984123456?text=Olá! Vim pelo chat da Amora Artesanal (Cliente <?= urlencode($usuario['codigo_cliente'] ?? '') ?>) e gostaria de falar com a equipe." target="_blank" class="btn btn-secondary btn-sm" title="Abrir no WhatsApp">
                <i class="fa-brands fa-whatsapp" style="color: #25d366; font-size: 1.1rem;"></i> (69) 98412-3456
            </a>
        </div>
    </div>

    <!-- Caixa de Mensagens -->
    <div style="background: #fffcf9; border: 1px solid var(--cor-borda); border-top: none; height: 420px; overflow-y: auto; padding: 24px; display: flex; flex-direction: column; gap: 14px;">
        <!-- Mensagem de Boas-Vindas Padrão -->
        <div style="align-self: flex-start; max-width: 80%; background: #fff; padding: 14px 18px; border-radius: 14px 14px 14px 2px; border: 1px solid var(--cor-borda); box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
            <div style="font-weight: 600; font-size: 0.82rem; color: var(--cor-rosa-queimado); margin-bottom: 4px;">Ateliê Amora Artesanal</div>
            <div style="font-size: 0.92rem; color: var(--cor-marrom-texto); line-height: 1.5;">
                Olá, <?= sanitizar(explode(' ', $usuario['nome'])[0]) ?>! Seja muito bem-vindo(a) ao nosso chat de atendimento. Como podemos te ajudar hoje? Ficou com dúvida sobre aromas, prazos ou quer personalizar uma peça exclusiva?
            </div>
            <div style="font-size: 0.72rem; color: var(--cor-marrom-claro); text-align: right; margin-top: 4px;">Ateliê Oficial</div>
        </div>

        <?php foreach ($mensagens as $m): ?>
            <?php $isCliente = $m['remetente'] === 'cliente'; ?>
            <div style="align-self: <?= $isCliente ? 'flex-end' : 'flex-start' ?>; max-width: 80%; background: <?= $isCliente ? 'var(--cor-rosa-claro)' : '#fff' ?>; padding: 14px 18px; border-radius: <?= $isCliente ? '14px 14px 2px 14px' : '14px 14px 14px 2px' ?>; border: 1px solid <?= $isCliente ? 'var(--cor-rosa-medio)' : 'var(--cor-borda)' ?>; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-weight: 600; font-size: 0.82rem; color: <?= $isCliente ? 'var(--cor-marrom-suave)' : 'var(--cor-rosa-queimado)' ?>; margin-bottom: 4px;">
                    <?= $isCliente ? 'Você' : 'Ateliê Amora Artesanal' ?>
                </div>
                <div style="font-size: 0.92rem; color: var(--cor-marrom-texto); line-height: 1.5;">
                    <?= nl2br(sanitizar($m['mensagem'])) ?>
                </div>
                <div style="font-size: 0.72rem; color: var(--cor-marrom-claro); text-align: right; margin-top: 4px;">
                    <?= date('d/m/Y H:i', strtotime($m['data_envio'])) ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Campo de Entrada da Mensagem -->
    <div style="background: var(--cor-branco); border: 1px solid var(--cor-borda); border-top: none; border-radius: 0 0 var(--raio-borda-lg) var(--raio-borda-lg); padding: 18px 24px;">
        <form action="<?= BASE_URL ?>/chat.php" method="POST" style="display: flex; gap: 12px; align-items: center; margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
            <input type="text" name="mensagem" class="form-control" placeholder="Digite sua mensagem ou dúvida sobre encomendas..." required autofocus style="flex-grow: 1;">
            <button type="submit" class="btn btn-primary" style="padding: 12px 24px;">
                <i class="fa-regular fa-paper-plane"></i> Enviar
            </button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
