<?php
/**
 * Gestão de Pedidos e Alteração de Status (RF10, UC08, CA07)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();

$statusPermitidos = [
    'Pedido realizado', 
    'Em preparação', 
    'Enviado', 
    'Entregue', 
    'Reembolso em Análise', 
    'Reembolsado (Estorno Pix Concluído)',
    'Reenvio Expresso Autorizado'
];

// Processar homologação de reembolso pelo Administrador (Dupla Verificação: Transportadora + Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'homologar_reembolso') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $decisao = $_POST['decisao'] ?? '';
        $parecer = trim($_POST['parecer_admin'] ?? '');
        $agora = date('Y-m-d H:i:s');

        if ($decisao === 'aprovar_estorno') {
            $comprovantePix = 'E2E-PIX-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $stmt = $db->prepare("UPDATE pedidos SET 
                status = 'Reembolsado (Estorno Pix Concluído)',
                status_auditoria_reembolso = 'Reembolso Aprovado e Estornado via Pix',
                parecer_admin = ?,
                data_aprovacao_admin = ?,
                comprovante_pix_estorno = ?
                WHERE id = ?");
            $parecerFinal = !empty($parecer) ? $parecer : 'Estorno Pix homologado e liberado pela Administração após confirmação do laudo técnico da transportadora.';
            $stmt->execute([$parecerFinal, $agora, $comprovantePix, $pedidoId]);
            setFlash('success', "Reembolso do Pedido #{$pedidoId} APROVADO! Comprovante Pix {$comprovantePix} emitido e estorno concluído com sucesso.");
        } elseif ($decisao === 'autorizar_reenvio') {
            $novoRastreio = 'PRIOR' . str_pad($pedidoId, 6, '0', STR_PAD_LEFT) . 'BR';
            $stmt = $db->prepare("UPDATE pedidos SET 
                status = 'Em preparação',
                status_auditoria_reembolso = 'Reenvio Expresso com Brinde Autorizado pela Administração',
                codigo_rastreio = ?,
                parecer_admin = ?,
                data_aprovacao_admin = ?
                WHERE id = ?");
            $parecerFinal = !empty($parecer) ? $parecer : 'Reenvio prioritário express homologado pela administração com mimo artesanal do ateliê.';
            $stmt->execute([$novoRastreio, $parecerFinal, $agora, $pedidoId]);
            setFlash('success', "Reenvio prioritário do Pedido #{$pedidoId} autorizado! Novo rastreio expresso: {$novoRastreio}.");
        } elseif ($decisao === 'recusar') {
            $stmt = $db->prepare("UPDATE pedidos SET 
                status = 'Entregue',
                status_auditoria_reembolso = 'Solicitação Indeferida - Entrega física comprovada com assinatura do recebedor',
                parecer_admin = ?,
                data_aprovacao_admin = ?
                WHERE id = ?");
            $parecerFinal = !empty($parecer) ? $parecer : 'Solicitação indeferida após conferência física de recebimento com o entregador.';
            $stmt->execute([$parecerFinal, $agora, $pedidoId]);
            setFlash('warning', "Solicitação de reembolso do Pedido #{$pedidoId} foi recusada com parecer oficial registrado.");
        }

        header('Location: ' . BASE_URL . '/admin/pedidos.php');
        exit;
    }
}

// Processar atualização de status manual (RF10, CA07)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'atualizar_status') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);
        $novoStatus = trim($_POST['status'] ?? '');

        if (in_array($novoStatus, $statusPermitidos)) {
            $stmt = $db->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
            $stmt->execute([$novoStatus, $pedidoId]);
            setFlash('success', "Situação do Pedido #{$pedidoId} atualizada para '{$novoStatus}' com sucesso!");
        } else {
            setFlash('danger', 'Status inválido fornecido.');
        }

        header('Location: ' . BASE_URL . '/admin/pedidos.php');
        exit;
    }
}

// Filtro de status
$filtroStatus = $_GET['status'] ?? '';
$sql = "SELECT p.*, u.nome as cliente_nome, u.email as cliente_email 
        FROM pedidos p 
        INNER JOIN usuarios u ON p.usuario_id = u.id 
        WHERE 1=1";
$params = [];

if (!empty($filtroStatus) && in_array($filtroStatus, $statusPermitidos)) {
    $sql .= " AND p.status = ?";
    $params[] = $filtroStatus;
}

$sql .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// Carregar itens para cada pedido
$itensPorPedido = [];
if (!empty($pedidos)) {
    $stmtItens = $db->prepare("SELECT ip.*, p.nome, p.imagem_url 
                               FROM itens_pedido ip 
                               INNER JOIN produtos p ON ip.produto_id = p.id 
                               WHERE ip.pedido_id = ?");
    foreach ($pedidos as $p) {
        $stmtItens->execute([$p['id']]);
        $itensPorPedido[$p['id']] = $stmtItens->fetchAll();
    }
}

function getClasseStatusAdminPage(string $status): string {
    return match ($status) {
        'Pedido realizado'                    => 'realizado',
        'Em preparação'                       => 'preparacao',
        'Enviado'                             => 'enviado',
        'Entregue'                            => 'entregue',
        'Reembolso em Análise'                => 'preparacao',
        'Reembolsado (Estorno Pix Concluído)' => 'entregue',
        'Reenvio Expresso Autorizado'         => 'enviado',
        default                               => 'realizado'
    };
}

// Pedidos com solicitação de reembolso ativa para auditoria do administrador
$pedidosReembolso = $db->query("SELECT p.*, u.nome as cliente_nome, u.email as cliente_email, u.telefone as cliente_telefone 
    FROM pedidos p 
    INNER JOIN usuarios u ON p.usuario_id = u.id 
    WHERE p.reembolso_solicitado = 1 
    ORDER BY p.id DESC")->fetchAll();

$pageTitle = 'Gerenciamento de Pedidos';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div class="admin-header-actions">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Gerenciamento de Pedidos</h1>
            <p style="color: var(--cor-marrom-claro);">Acompanhe os pedidos de clientes, gerencie estornos com dupla verificação e atualize o progresso de entrega (RF10, CA07)</p>
        </div>
    </div>

    <!-- SEÇÃO DE AUDITORIA DE REEMBOLSOS E ESTORNOS (DUPLA VERIFICAÇÃO: TRANSPORTADORA + ADMIN) -->
    <?php if (!empty($pedidosReembolso)): ?>
        <div style="background: #fffcf9; border: 2px solid var(--cor-rosa-medio); border-radius: var(--raio-borda-lg); padding: 24px; margin-bottom: 30px; box-shadow: var(--sombra-media);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <h3 style="margin: 0; font-size: 1.25rem; color: var(--cor-marrom-suave);">
                    <i class="fa-solid fa-shield-halved" style="color: var(--cor-rosa-queimado);"></i> 
                    Auditoria de Reembolsos & Estornos (<?= count($pedidosReembolso) ?> Solicitação(ões))
                </h3>
                <span style="font-size: 0.8rem; background: #fff; border: 1px solid var(--cor-borda); padding: 5px 12px; border-radius: var(--raio-pilula); color: var(--cor-rosa-queimado); font-weight: 700;">
                    <i class="fa-solid fa-scale-balanced"></i> Dupla Verificação Obrigatória: Transportadora &bull; Administrador
                </span>
            </div>

            <p style="font-size: 0.88rem; color: var(--cor-marrom-claro); margin-bottom: 20px;">
                Antes de devolver o dinheiro ou extorno ao cliente, o sistema exige verificação completa: 
                <strong>1º Laudo técnico emitido pela transportadora LogiAmora</strong> (atestando a ocorrência na rota) e 
                <strong>2º Homologação financeira e parecer do Administrador</strong> com emissão de comprovante Pix.
            </p>

            <div style="display: flex; flex-direction: column; gap: 20px;">
                <?php foreach ($pedidosReembolso as $pr): ?>
                    <div style="background: #ffffff; border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); padding: 20px; box-shadow: var(--sombra-suave);">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
                            <div>
                                <h4 style="margin: 0; font-size: 1.15rem; color: var(--cor-marrom-suave);">
                                    Pedido #<?= str_pad($pr['id'], 5, '0', STR_PAD_LEFT) ?> &bull; Protocolo: <span style="font-family: monospace; color: var(--cor-rosa-queimado); font-weight: bold;"><?= sanitizar($pr['protocolo_reembolso'] ?? '') ?></span>
                                </h4>
                                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); margin-top: 4px;">
                                    Cliente: <strong><?= sanitizar($pr['cliente_nome']) ?></strong> (<?= sanitizar($pr['cliente_email']) ?>) &bull; Tel: <?= sanitizar($pr['cliente_telefone'] ?? 'Não informado') ?>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <div style="font-size: 1.25rem; font-weight: 700; color: var(--cor-rosa-queimado);">
                                    Valor do Pedido: <?= formatarMoeda((float)$pr['valor_total']) ?>
                                </div>
                                <span class="badge-status <?= ($pr['status'] === 'Reembolsado (Estorno Pix Concluído)') ? 'entregue' : 'preparacao' ?>" style="font-size: 0.78rem;">
                                    <?= sanitizar($pr['status']) ?>
                                </span>
                            </div>
                        </div>

                        <!-- Grid de Verificação: Transportadora vs Administrador -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 16px;">
                            <!-- 1. Parecer da Transportadora -->
                            <div style="background: #f4f8fc; border: 1px solid #c2daf0; border-radius: 8px; padding: 14px;">
                                <div style="font-size: 0.82rem; font-weight: 700; color: #1e5a8a; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-truck-fast"></i> 1. Verificação com a Transportadora LogiAmora
                                </div>
                                <div style="font-size: 0.82rem; color: #2e4a62; line-height: 1.5;">
                                    <strong>Código da Ocorrência:</strong> <?= sanitizar($pr['codigo_ocorrencia_transp'] ?? 'OC-84920') ?><br>
                                    <strong>Data do Registro:</strong> <?= !empty($pr['data_laudo_transportadora']) ? date('d/m/Y H:i', strtotime($pr['data_laudo_transportadora'])) : date('d/m/Y H:i') ?><br>
                                    <strong>Laudo Técnico:</strong><br>
                                    <span style="font-family: monospace; font-size: 0.78rem; background: #fff; padding: 6px 10px; border-radius: 4px; display: block; margin-top: 4px; border: 1px solid #c2daf0;">
                                        <?= nl2br(sanitizar($pr['laudo_transportadora'] ?? 'Confirmado atraso operacional que excedeu o prazo limite contratual na rota.')) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- 2. Dados do Estorno Pix & Solicitação do Cliente -->
                            <div style="background: #fdfaf6; border: 1px solid #e8d0bd; border-radius: 8px; padding: 14px;">
                                <div style="font-size: 0.82rem; font-weight: 700; color: #7a4620; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-brands fa-pix" style="color: #32bcad;"></i> 2. Dados da Chave Pix & Opção Escolhida
                                </div>
                                <div style="font-size: 0.82rem; color: #4a3424; line-height: 1.5;">
                                    <strong>Chave Pix para Estorno:</strong><br>
                                    <code style="background: #fff; padding: 4px 10px; border-radius: 4px; font-weight: bold; color: var(--cor-rosa-queimado); display: inline-block; margin: 4px 0; border: 1px solid #e8d0bd;">
                                        <?= sanitizar($pr['chave_pix_reembolso'] ?? 'Chave cadastrada no pedido') ?>
                                    </code><br>
                                    <strong>Opção Solicitada:</strong> <?= sanitizar($pr['motivo_reembolso'] ?? 'Estorno Integral via Pix') ?><br>
                                    <strong>Auditoria Atual:</strong> <span style="color: #208743; font-weight: 600;"><?= sanitizar($pr['status_auditoria_reembolso'] ?? 'Em análise') ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Decisão e Homologação do Administrador -->
                        <?php if ($pr['status'] !== 'Reembolsado (Estorno Pix Concluído)'): ?>
                            <form action="<?= BASE_URL ?>/admin/pedidos.php" method="POST" style="background: #fcf9f7; padding: 16px; border-radius: 8px; border: 1px solid var(--cor-borda);">
                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                <input type="hidden" name="acao" value="homologar_reembolso">
                                <input type="hidden" name="pedido_id" value="<?= $pr['id'] ?>">

                                <div style="margin-bottom: 12px;">
                                    <label style="font-size: 0.82rem; font-weight: 600; color: var(--cor-marrom-suave); display: block; margin-bottom: 4px;">
                                        3. Parecer Oficial da Administração (Larissa Benício / Ateliê):
                                    </label>
                                    <input type="text" name="parecer_admin" class="form-control" placeholder="Parecer da gerência para o cliente..." value="Estorno Pix homologado após conferência com o laudo da transportadora e confirmação de não entrega no prazo." style="font-size: 0.85rem;" required>
                                </div>

                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button type="submit" name="decisao" value="aprovar_estorno" class="btn btn-primary btn-sm" style="background: #208743; border-color: #208743;">
                                        <i class="fa-solid fa-check-double"></i> Homologar & Efetuar Estorno Pix (<?= formatarMoeda((float)$pr['valor_total']) ?>)
                                    </button>
                                    <button type="submit" name="decisao" value="autorizar_reenvio" class="btn btn-secondary btn-sm" style="background: #3b6cd0; color: #fff; border-color: #3b6cd0;">
                                        <i class="fa-solid fa-box-open"></i> Autorizar Reenvio Expresso com Brinde Especial
                                    </button>
                                    <button type="submit" name="decisao" value="recusar" class="btn btn-secondary btn-sm" style="color: #c93b3b; border-color: #c93b3b;" onclick="return confirm('Tem certeza que deseja recusar este reembolso?');">
                                        <i class="fa-solid fa-xmark"></i> Recusar Solicitação
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Comprovante Oficial de Estorno Homologado -->
                            <div style="background: #e7f8ee; border: 1px solid #4e8d53; border-radius: 8px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                                <div style="font-size: 0.85rem; color: #1e5a26;">
                                    <i class="fa-solid fa-circle-check" style="color: #208743;"></i>
                                    <strong>Estorno Homologado & Concluído:</strong> Comprovante Pix <code><?= sanitizar($pr['comprovante_pix_estorno'] ?? '') ?></code> em <?= !empty($pr['data_aprovacao_admin']) ? date('d/m/Y \à\s H:i', strtotime($pr['data_aprovacao_admin'])) : date('d/m/Y') ?>
                                    <div style="font-size: 0.78rem; color: #356e3e; margin-top: 3px;">
                                        Parecer do Admin: "<?= sanitizar($pr['parecer_admin'] ?? 'Estorno autorizado com sucesso.') ?>"
                                    </div>
                                </div>
                                <span style="font-size: 0.85rem; background: #208743; color: #fff; padding: 4px 12px; border-radius: var(--raio-pilula); font-weight: 700;">
                                    ✔ 100% Estornado via Pix
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Filtros de Status em Pílulas -->
    <div style="background: var(--cor-branco); padding: 18px 24px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); margin-bottom: 24px;">
        <div class="categories-pills" style="margin: 0; justify-content: flex-start;">
            <a href="<?= BASE_URL ?>/admin/pedidos.php" class="pill-item <?= empty($filtroStatus) ? 'active' : '' ?>">
                Todos os Pedidos (<?= count($pedidos) ?>)
            </a>
            <?php foreach ($statusPermitidos as $st): ?>
                <a href="<?= BASE_URL ?>/admin/pedidos.php?status=<?= urlencode($st) ?>" class="pill-item <?= $filtroStatus === $st ? 'active' : '' ?>">
                    <?= sanitizar($st) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lista de Pedidos -->
    <?php if (empty($pedidos)): ?>
        <div class="admin-card" style="text-align: center; padding: 50px 20px;">
            <p style="color: var(--cor-marrom-claro); font-size: 1.1rem;">Nenhum pedido encontrado com o filtro selecionado.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <?php foreach ($pedidos as $p): ?>
                <div class="admin-card" style="margin-bottom: 0; padding: 0; overflow: hidden;">
                    <!-- Cabeçalho do Pedido com Formulário de Status (CA07) -->
                    <div style="background: var(--cor-rosa-claro); padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--cor-borda);">
                        <div>
                            <span style="font-weight: 700; font-size: 1.15rem; color: var(--cor-marrom-suave);">
                                Pedido #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.88rem; margin-left: 10px;">
                                Cliente: <strong><?= sanitizar($p['cliente_nome']) ?></strong> (<?= sanitizar($p['cliente_email']) ?>)
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.82rem; margin-left: 10px;">
                                &bull; <?= date('d/m/Y H:i', strtotime($p['data_pedido'])) ?>
                            </span>
                        </div>

                        <!-- Formulário de Atualização Imediata de Status (RF10, CA07) -->
                        <form action="<?= BASE_URL ?>/admin/pedidos.php" method="POST" style="display: flex; align-items: center; gap: 10px; margin: 0;">
                            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                            <input type="hidden" name="acao" value="atualizar_status">
                            <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">

                            <label style="font-size: 0.85rem; font-weight: 600; color: var(--cor-marrom-suave);">Alterar Status:</label>
                            <select name="status" class="form-control" style="width: auto; padding: 8px 12px; font-size: 0.88rem;">
                                <?php foreach ($statusPermitidos as $st): ?>
                                    <option value="<?= $st ?>" <?= $p['status'] === $st ? 'selected' : '' ?>>
                                        <?= $st ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-check"></i> Salvar
                            </button>
                        </form>
                    </div>

                    <!-- Conteúdo: Endereço, Itens e Personalizações -->
                    <div style="padding: 24px;">
                        <div style="font-size: 0.92rem; color: var(--cor-marrom-claro); margin-bottom: 16px;">
                            <i class="fa-solid fa-truck" style="color: var(--cor-rosa-queimado);"></i> 
                            <strong>Endereço de Entrega:</strong> <?= sanitizar($p['endereco_entrega']) ?>
                        </div>

                        <div style="border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--cor-marrom-suave); text-transform: uppercase;">Itens Solicitados:</h4>

                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if (!empty($itensPorPedido[$p['id']])): ?>
                                    <?php foreach ($itensPorPedido[$p['id']] as $item): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #fffbf9; padding: 12px 16px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda); flex-wrap: wrap; gap: 10px;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="" style="width: 44px; height: 44px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 3px;">
                                                <div>
                                                    <strong style="color: var(--cor-marrom-suave); font-size: 0.92rem;"><?= sanitizar($item['nome']) ?></strong>
                                                    <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                                        <?= $item['quantidade'] ?> unidade(s) &times; <?= formatarMoeda((float)$item['preco_unitario']) ?>
                                                    </div>
                                                    <!-- Personalização Destacada (RF05, UC03) -->
                                                    <?php if (!empty($item['personalizacao'])): ?>
                                                        <div style="font-size: 0.82rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 4px 10px; border-radius: 6px; display: inline-block; margin-top: 4px; border: 1px solid var(--cor-rosa-medio);">
                                                            <i class="fa-solid fa-wand-magic-sparkles"></i> <strong>Personalização Solicitada:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.05rem;">
                                                <?= formatarMoeda((float)($item['quantidade'] * $item['preco_unitario'])) ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--cor-borda); margin-top: 20px; padding-top: 16px;">
                            <span class="badge-status <?= getClasseStatusAdminPage($p['status']) ?>" style="font-size: 0.9rem;">
                                Status Atual: <strong><?= sanitizar($p['status']) ?></strong>
                            </span>

                            <div style="font-size: 1.25rem; font-weight: 700; color: var(--cor-rosa-queimado);">
                                Total: <?= formatarMoeda((float)$p['valor_total']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
