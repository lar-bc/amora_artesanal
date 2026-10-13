<?php
/**
 * Visualização de Mensagens do Formulário de Contato (RF11)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

// Marcar como lida ou excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $acao = $_POST['acao'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($acao === 'marcar_lida') {
            $stmt = $db->prepare("UPDATE mensagens_contato SET lida = 1 WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Mensagem marcada como lida.');
        } elseif ($acao === 'excluir') {
            $stmt = $db->prepare("DELETE FROM mensagens_contato WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('info', 'Mensagem excluída.');
        }

        header('Location: ' . BASE_URL . '/admin/mensagens.php');
        exit;
    }
}

$mensagens = $db->query("SELECT * FROM mensagens_contato ORDER BY id DESC")->fetchAll();

$pageTitle = 'Mensagens de Contato';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="admin-header-actions">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Mensagens Recebidas</h1>
            <p style="color: var(--cor-marrom-claro);">Dúvidas de clientes e solicitações de encomendas personalizadas (RF11)</p>
        </div>
    </div>

    <?php if (empty($mensagens)): ?>
        <div class="admin-card" style="text-align: center; padding: 50px 20px;">
            <div style="font-size: 2.5rem; color: var(--cor-rosa-medio); margin-bottom: 12px;">
                <i class="fa-regular fa-envelope-open"></i>
            </div>
            <p style="color: var(--cor-marrom-claro); font-size: 1.1rem;">Nenhuma mensagem de contato recebida até o momento.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($mensagens as $m): ?>
                <div class="admin-card" style="margin-bottom: 0; border-left: 4px solid <?= $m['lida'] ? 'var(--cor-borda)' : 'var(--cor-rosa-queimado)' ?>;">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 12px;">
                        <div>
                            <strong style="font-size: 1.1rem; color: var(--cor-marrom-suave);"><?= sanitizar($m['nome']) ?></strong>
                            <span style="font-size: 0.88rem; color: var(--cor-marrom-claro); margin-left: 8px;">
                                &lt;<a href="mailto:<?= sanitizar($m['email']) ?>"><?= sanitizar($m['email']) ?></a>&gt;
                            </span>
                            <div style="font-size: 0.8rem; color: var(--cor-marrom-claro); margin-top: 2px;">
                                <i class="fa-regular fa-clock"></i> <?= date('d/m/Y H:i', strtotime($m['data_envio'])) ?>
                                <?php if (!$m['lida']): ?>
                                    <span class="badge-status preparacao" style="margin-left: 8px; font-size: 0.72rem;">Nova Mensagem</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <?php if (!$m['lida']): ?>
                                <form action="<?= BASE_URL ?>/admin/mensagens.php" method="POST" style="margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                    <input type="hidden" name="acao" value="marcar_lida">
                                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm">
                                        <i class="fa-solid fa-check"></i> Marcar como Lida
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form action="<?= BASE_URL ?>/admin/mensagens.php" method="POST" style="margin: 0;" onsubmit="return confirm('Deseja excluir esta mensagem?');">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm" style="color: #c93b3b; border-color: #f7c5c7;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div style="background: #fffbf9; padding: 14px 18px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda); font-size: 0.95rem; line-height: 1.6; color: var(--cor-marrom-texto);">
                        <?= nl2br(sanitizar($m['mensagem'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
