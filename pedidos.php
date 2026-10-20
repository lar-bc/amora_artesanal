<?php
/**
 * Acompanhamento de Pedidos, Rastreamento com Mapa em Tempo Real,
 * Verificação de Entrega, Garantia de Reembolso e Avaliação NPS Pós-Compra
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

exigirLogin();

$usuario = getUsuarioLogado();
$db = getDBConnection();

// Processar Ações do Cliente (Verificação de Chegada, Reembolso e Avaliação)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        setFlash('danger', 'Sessão expirada. Tente novamente.');
    } else {
        $acao = $_POST['acao'] ?? '';
        $pedidoId = (int)($_POST['pedido_id'] ?? 0);

        // 1. Cliente confirma que a encomenda já chegou
        if ($acao === 'confirmar_chegada') {
            $stmt = $db->prepare("UPDATE pedidos SET status = 'Entregue', confirmacao_entrega_cliente = 'entregue' WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pedidoId, $usuario['id']]);
            setFlash('success', 'Que alegria que sua encomenda chegou! Esperamos que ela encha seu lar de perfume e aconchego. Por favor, deixe sua avaliação abaixo!');
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }

        // 2. Cliente informa que ainda não chegou -> Verificação com Transportadora
        elseif ($acao === 'relatar_atraso') {
            $stmt = $db->prepare("UPDATE pedidos SET confirmacao_entrega_cliente = 'nao_chegou' WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$pedidoId, $usuario['id']]);
            setFlash('info', 'Consultamos a transportadora LogiAmora. Abaixo você confere o parecer oficial e pode acionar a nossa garantia de reembolso imediato.');
            header('Location: ' . BASE_URL . '/pedidos.php#rastreio_' . $pedidoId);
            exit;
        }

        // 3. Cliente aciona a Garantia de Reembolso Integral ou Reenvio
        elseif ($acao === 'solicitar_reembolso') {
            $opcaoReembolso = $_POST['opcao_reembolso'] ?? 'reembolso_pix';
            $chavePixReembolso = trim($_POST['chave_pix_reembolso'] ?? '');
            $protocolo = 'REEMB-' . date('Y') . '-' . rand(10000, 99999);
            $motivo = ($opcaoReembolso === 'reembolso_pix') 
                ? "Reembolso Integral via Pix para chave: {$chavePixReembolso}" 
                : "Reenvio Expresso com brinde especial solicitado";

            $stmt = $db->prepare("UPDATE pedidos SET status = 'Reembolso Solicitado', reembolso_solicitado = 1, protocolo_reembolso = ?, motivo_reembolso = ? WHERE id = ? AND usuario_id = ?");
            $stmt->execute([$protocolo, $motivo, $pedidoId, $usuario['id']]);

            setFlash('success', "Solicitação registrada com sucesso! Seu protocolo é {$protocolo}. Nossa equipe do ateliê já iniciou o procedimento.");
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }

        // 4. Avaliação pós-compra do produto com NPS
        elseif ($acao === 'salvar_avaliacao') {
            $produtoId = (int)($_POST['produto_id'] ?? 0);
            $estrelas = min(5, max(1, (int)($_POST['estrelas'] ?? 5)));
            $comentario = trim($_POST['comentario'] ?? '');
            $indicariaAmigo = ($_POST['indicaria_amigo'] ?? 'sim') === 'nao' ? 'nao' : 'sim';

            if (!empty($comentario) && $produtoId > 0) {
                $stmt = $db->prepare("INSERT INTO avaliacoes (produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$produtoId, $usuario['id'], $usuario['nome'], $estrelas, $comentario, $indicariaAmigo]);
                setFlash('success', 'Muito obrigado pela sua avaliação carinhosa! Seu relato ajudará outros clientes a escolherem presentes especiais.');
            }
            header('Location: ' . BASE_URL . '/pedidos.php#pedido_' . $pedidoId);
            exit;
        }
    }
}

// Buscar pedidos do cliente
$stmt = $db->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY id DESC");
$stmt->execute([$usuario['id']]);
$pedidos = $stmt->fetchAll();

// Itens e produtos de cada pedido
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

function getClasseStatusDetalhado(string $status): string {
    return match ($status) {
        'Pedido realizado'     => 'realizado',
        'Em preparação'        => 'preparacao',
        'Enviado'              => 'enviado',
        'Entregue'             => 'entregue',
        'Reembolso Solicitado' => 'preparacao',
        'Reembolsado'          => 'entregue',
        default                => 'realizado'
    };
}

$pageTitle = 'Meus Pedidos & Rastreamento em Tempo Real';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;">Meus Pedidos & Rastreamento</h1>
            <p style="color: var(--cor-marrom-claro);">Acompanhe o trajeto da sua encomenda artesanal no mapa e gerencie suas compras</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/chat.php" class="btn btn-secondary">
                <i class="fa-regular fa-comments"></i> Falar com Ateliê no Chat
            </a>
        </div>
    </div>

    <!-- Card de Identificação Única do Cliente e Dados Pessoais Salvos -->
    <div style="background: var(--cor-branco); border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); padding: 20px 24px; margin-bottom: 28px; box-shadow: var(--sombra-suave); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, var(--cor-rosa-queimado), #df8d9c); display: flex; align-items: center; justify-content: center; color: white; font-size: 1.5rem;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h3 style="margin: 0; font-size: 1.2rem; color: var(--cor-marrom-suave);"><?= sanitizar($usuario['nome']) ?></h3>
                    <span style="background: var(--cor-rosa-claro); border: 1px solid var(--cor-rosa-queimado); color: var(--cor-rosa-queimado); font-weight: 700; padding: 2px 10px; border-radius: 12px; font-size: 0.82rem;">
                        <i class="fa-solid fa-id-badge"></i> #<?= sanitizar($usuario['codigo_cliente'] ?? ('CLI-' . str_pad($usuario['id'], 5, '0', STR_PAD_LEFT))) ?>
                    </span>
                    <span style="background: #eef7ee; color: #1e5a26; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                        <i class="fa-solid fa-lock"></i> Dados Salvos na Conta
                    </span>
                </div>
                <div style="font-size: 0.85rem; color: var(--cor-marrom-claro); margin-top: 4px; display: flex; gap: 16px; flex-wrap: wrap;">
                    <span><i class="fa-regular fa-envelope"></i> <?= sanitizar($usuario['email']) ?></span>
                    <?php if (!empty($usuario['telefone'])): ?>
                        <span><i class="fa-brands fa-whatsapp"></i> <?= sanitizar($usuario['telefone']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($usuario['cidade'])): ?>
                        <span><i class="fa-solid fa-location-dot"></i> <?= sanitizar($usuario['cidade']) ?>/<?= sanitizar($usuario['estado']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div style="font-size: 0.82rem; color: var(--cor-marrom-claro);">
            <i class="fa-solid fa-shield-check" style="color: var(--cor-sucesso);"></i> Suas informações pessoais e endereço estão salvos nesta conta.
        </div>
    </div>

    <?php if (empty($pedidos)): ?>
        <div style="background: var(--cor-branco); border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); padding: 70px 20px; text-align: center;">
            <div style="font-size: 3.5rem; color: var(--cor-rosa-medio); margin-bottom: 16px;">
                <i class="fa-solid fa-receipt"></i>
            </div>
            <h3 style="margin-bottom: 8px;">Nenhum pedido realizado ainda</h3>
            <p style="color: var(--cor-marrom-claro); margin-bottom: 24px;">Visite nossa vitrine virtual e encante-se com nossos aromas, sabonetes botânicos e amigurumis.</p>
            <a href="<?= BASE_URL ?>/produtos.php" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-store"></i> Explorar Vitrine
            </a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 32px;">
            <?php foreach ($pedidos as $p): ?>
                <?php 
                    $codigoRastreio = $p['codigo_rastreio'] ?? ('AM' . str_pad($p['id'], 8, '0', STR_PAD_LEFT) . 'BR');
                    $status = $p['status'];
                    $confirmacao = $p['confirmacao_entrega_cliente'] ?? '';
                    $reembolsoAtivo = (int)($p['reembolso_solicitado'] ?? 0);
                ?>
                <div id="pedido_<?= $p['id'] ?>" style="background: var(--cor-branco); border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); overflow: hidden;">
                    <!-- Cabeçalho do Pedido -->
                    <div style="background: var(--cor-rosa-claro); padding: 20px 26px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid var(--cor-borda);">
                        <div>
                            <span style="font-weight: 700; font-size: 1.2rem; color: var(--cor-marrom-suave);">
                                Pedido #<?= str_pad($p['id'], 5, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span style="color: var(--cor-marrom-claro); font-size: 0.88rem; margin-left: 12px;">
                                <i class="fa-regular fa-calendar"></i> <?= date('d/m/Y \à\s H:i', strtotime($p['data_pedido'])) ?>
                            </span>
                            <span style="margin-left: 12px; font-size: 0.85rem; background: #fff; padding: 4px 10px; border-radius: 6px; border: 1px solid var(--cor-borda); color: var(--cor-rosa-queimado); font-family: monospace;">
                                Rastreio: <strong><?= sanitizar($codigoRastreio) ?></strong>
                            </span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span class="badge-status <?= getClasseStatusDetalhado($status) ?>">
                                <i class="fa-solid <?= $status === 'Entregue' ? 'fa-circle-check' : ($status === 'Enviado' ? 'fa-truck-fast' : 'fa-clock') ?>"></i>
                                <?= sanitizar($status) ?>
                            </span>
                            <span style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.3rem;">
                                <?= formatarMoeda((float)$p['valor_total']) ?>
                            </span>
                        </div>
                    </div>

                    <div style="padding: 26px;">
                        <!-- Seção de Rastreamento em Tempo Real com Linha do Tempo e Mapinha em SVG -->
                        <div id="rastreio_<?= $p['id'] ?>" style="background: #fffcf9; border: 1px solid var(--cor-borda); border-radius: var(--raio-borda); padding: 22px; margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                                <h3 style="font-size: 1.15rem; color: var(--cor-marrom-suave); margin: 0;">
                                    <i class="fa-solid fa-map-location-dot" style="color: var(--cor-rosa-queimado);"></i> 
                                    Rastreamento em Tempo Real &bull; <?= sanitizar($p['transportadora'] ?? 'LogiAmora Transportes') ?>
                                </h3>
                                <span style="font-size: 0.82rem; color: var(--cor-sucesso); font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                                    <span style="width: 8px; height: 8px; background: #4e8d53; border-radius: 50%; display: inline-block;"></span>
                                    Sinal GPS da transportadora ativo
                                </span>
                            </div>

                            <!-- Mapa-Múndi Real e Telemetria Satelital Global em SVG -->
                            <div style="margin-bottom: 22px; border-radius: var(--raio-borda); overflow: hidden; border: 1px solid #1e334a; box-shadow: 0 4px 14px rgba(0,0,0,0.15); background: #101c2b;">
                                <!-- Painel Superior de Telemetria do Mapa -->
                                <div style="background: #0b1420; padding: 10px 16px; border-bottom: 1px solid #1e334a; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.78rem; color: #8faec8; font-family: monospace;">
                                    <div>
                                        <i class="fa-solid fa-satellite" style="color: #4da6ff;"></i>
                                        <strong style="color: #e1edf7;">MONITORAMENTO GLOBAL LOGIAMORA</strong> &bull; Satélite GlobalSat-4
                                    </div>
                                    <div>
                                        HUB ORIGEM: <span style="color: #ff8ca3;">Porto Velho/RO - Brasil (-08°45'S, -63°54'W)</span>
                                    </div>
                                    <div style="color: #4cd964;">
                                        <i class="fa-solid fa-circle-dot"></i> TELEMETRIA GPS ONLINE
                                    </div>
                                </div>

                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 960 440" width="100%" height="auto" style="display: block; background: #101c2b;">
                                    <defs>
                                        <!-- Gradiente Oceânico -->
                                        <radialGradient id="oceanGlow" cx="50%" cy="50%" r="65%">
                                            <stop offset="0%" stop-color="#142437"/>
                                            <stop offset="100%" stop-color="#0a121c"/>
                                        </radialGradient>
                                        <!-- Glow para a rota -->
                                        <filter id="glow" x="-20%" y="-20%" width="140%" height="140%">
                                            <feGaussianBlur stdDeviation="3" result="blur" />
                                            <feMerge>
                                                <feMergeNode in="blur" />
                                                <feMergeNode in="SourceGraphic" />
                                            </feMerge>
                                        </filter>
                                    </defs>

                                    <!-- Fundo Oceânico -->
                                    <rect width="100%" height="100%" fill="url(#oceanGlow)"/>

                                    <!-- Grade de Coordenadas Globais (Meridianos e Paralelos) -->
                                    <g stroke="#1b3047" stroke-width="0.8" opacity="0.6">
                                        <line x1="120" y1="0" x2="120" y2="440"/>
                                        <line x1="240" y1="0" x2="240" y2="440"/>
                                        <line x1="360" y1="0" x2="360" y2="440"/>
                                        <line x1="480" y1="0" x2="480" y2="440" stroke="#2a4563" stroke-width="1.2"/> <!-- Meridiano de Greenwich -->
                                        <line x1="600" y1="0" x2="600" y2="440"/>
                                        <line x1="720" y1="0" x2="720" y2="440"/>
                                        <line x1="840" y1="0" x2="840" y2="440"/>

                                        <line x1="0" y1="80" x2="960" y2="80"/>
                                        <line x1="0" y1="160" x2="960" y2="160" stroke="#253e5c" stroke-dasharray="4,4"/> <!-- Trópico de Câncer -->
                                        <line x1="0" y1="240" x2="960" y2="240" stroke="#c86d7e" stroke-width="1.2" stroke-dasharray="6,4" opacity="0.8"/> <!-- Linha do Equador -->
                                        <line x1="0" y1="320" x2="960" y2="320" stroke="#253e5c" stroke-dasharray="4,4"/> <!-- Trópico de Capricórnio -->
                                        <line x1="0" y1="380" x2="960" y2="380"/>
                                    </g>

                                    <text x="950" y="236" fill="#c86d7e" font-size="8" font-family="monospace" text-anchor="end" opacity="0.8">0° EQUADOR</text>
                                    <text x="485" y="15" fill="#4da6ff" font-size="8" font-family="monospace" opacity="0.7">0° GREENWICH</text>

                                    <!-- Continentes do Mundo em Vetor Detalhado -->
                                    <g fill="#21354b" stroke="#324d6b" stroke-width="1.2" stroke-linejoin="round">
                                        <!-- América do Norte & Groenlândia -->
                                        <path d="M 60,65 Q 110,40 180,45 Q 235,50 280,75 Q 310,110 330,85 Q 370,60 365,100 Q 320,135 275,160 Q 285,185 245,215 Q 195,245 165,225 Q 130,185 90,140 Q 60,100 60,65 Z"/>
                                        <!-- Groenlândia -->
                                        <path d="M 335,30 Q 380,20 405,55 Q 365,80 335,65 Z"/>

                                        <!-- América do Sul (com destaque para o Brasil e Porto Velho) -->
                                        <path d="M 260,230 Q 295,215 340,230 Q 400,265 385,320 Q 355,370 330,425 Q 315,420 305,370 Q 285,310 260,265 Z" fill="#263e58" stroke="#3e6187"/>

                                        <!-- Europa & Ilhas Britânicas -->
                                        <path d="M 455,165 Q 470,120 465,80 Q 500,60 515,100 Q 550,80 570,130 Q 530,155 490,170 Z"/>
                                        <path d="M 440,100 Q 460,95 455,120 Z"/>

                                        <!-- África & Madagascar -->
                                        <path d="M 450,180 Q 520,175 570,205 Q 565,250 530,310 Q 520,380 485,380 Q 455,305 430,255 Q 445,205 450,180 Z"/>
                                        <path d="M 575,310 Q 590,310 580,355 Z"/>

                                        <!-- Ásia (Rússia, Oriente Médio, Índia, China, Sudeste Asiático, Japão) -->
                                        <path d="M 570,80 Q 675,50 805,60 Q 865,95 825,145 Q 810,195 760,225 Q 730,265 690,240 Q 660,210 620,220 Q 570,190 560,130 Z"/>
                                        <path d="M 645,200 Q 680,205 670,260 Z"/> <!-- Índia -->
                                        <path d="M 830,130 Q 845,140 835,175 Z"/> <!-- Japão -->

                                        <!-- Oceania / Austrália & Nova Zelândia -->
                                        <path d="M 755,305 Q 835,300 855,345 Q 815,385 760,355 Z"/>
                                        <path d="M 870,380 Q 885,390 875,415 Z"/> <!-- Nova Zelândia -->

                                        <!-- Antártida -->
                                        <path d="M 40,432 L 920,432 L 935,438 L 25,438 Z" fill="#1b2a3c" stroke="#253a52"/>
                                    </g>

                                    <!-- Corredores Internacionais de Carga Amora Artesanal (Linhas Globais) -->
                                    <g stroke="rgba(200, 109, 126, 0.25)" stroke-width="1.2" stroke-dasharray="4,5" fill="none">
                                        <!-- Rota Brasil -> América do Norte -->
                                        <path d="M 310,275 Q 260,190 235,140"/>
                                        <!-- Rota Brasil -> Europa -->
                                        <path d="M 310,275 Q 400,190 470,140"/>
                                        <!-- Rota Brasil -> Ásia -->
                                        <path d="M 310,275 Q 580,120 760,150"/>
                                    </g>

                                    <!-- Ponto de Origem: Ateliê Amora (Porto Velho/RO - Brasil) -->
                                    <circle cx="310" cy="275" r="14" fill="#c86d7e" opacity="0.3"/>
                                    <circle cx="310" cy="275" r="7" fill="#c86d7e"/>
                                    <circle cx="310" cy="275" r="3" fill="#ffffff"/>
                                    <text x="310" y="260" font-family="'Poppins', sans-serif" font-size="10" font-weight="bold" fill="#ff99ab" text-anchor="middle" filter="url(#glow)">★ Ateliê Amora (Porto Velho/RO)</text>

                                    <!-- Ponto de Destino: Endereço do Cliente -->
                                    <?php 
                                        $destX = 360; 
                                        $destY = 330; 
                                    ?>
                                    <circle cx="<?= $destX ?>" cy="<?= $destY ?>" r="14" fill="#4cd964" opacity="0.3"/>
                                    <circle cx="<?= $destX ?>" cy="<?= $destY ?>" r="6" fill="#4cd964"/>
                                    <circle cx="<?= $destX ?>" cy="<?= $destY ?>" r="2.5" fill="#ffffff"/>
                                    <text x="<?= $destX ?>" y="<?= $destY + 18 ?>" font-family="'Poppins', sans-serif" font-size="9" font-weight="bold" fill="#8ced9e" text-anchor="middle">📍 Seu Endereço de Entrega</text>

                                    <!-- ROTA ATIVA DO PEDIDO DO CLIENTE -->
                                    <path d="M 310,275 Q 330,295 <?= $destX ?>,<?= $destY ?>" fill="none" stroke="#ff7b90" stroke-width="3" stroke-linecap="round" filter="url(#glow)"/>
                                    <path d="M 310,275 Q 330,295 <?= $destX ?>,<?= $destY ?>" fill="none" stroke="#ffffff" stroke-width="1.2" stroke-dasharray="4,4"/>

                                    <!-- Posição do Veículo Conforme o Status Atual -->
                                    <?php 
                                        $posicaoX = match($status) {
                                            'Pedido realizado' => 310,
                                            'Em preparação'    => 322,
                                            'Enviado'          => 342,
                                            'Entregue'         => $destX,
                                            default            => 330
                                        };
                                        $posicaoY = match($status) {
                                            'Pedido realizado' => 275,
                                            'Em preparação'    => 288,
                                            'Enviado'          => 310,
                                            'Entregue'         => $destY,
                                            default            => 300
                                        };
                                    ?>

                                    <!-- Radar de Localização em Tempo Real -->
                                    <circle cx="<?= $posicaoX ?>" cy="<?= $posicaoY ?>" r="18" fill="#ff7b90" opacity="0.25">
                                        <animate attributeName="r" values="8;22;8" dur="2.5s" repeatCount="indefinite"/>
                                        <animate attributeName="opacity" values="0.6;0.1;0.6" dur="2.5s" repeatCount="indefinite"/>
                                    </circle>

                                    <!-- Ícone do Transporte (Caminhão/Veículo de Entrega) -->
                                    <g transform="translate(<?= $posicaoX - 14 ?>, <?= $posicaoY - 12 ?>)">
                                        <rect x="0" y="2" width="20" height="12" rx="2" fill="#fff" stroke="#5c3d2e" stroke-width="1.5"/>
                                        <rect x="18" y="5" width="8" height="9" rx="2" fill="#c86d7e"/>
                                        <circle cx="6" cy="15" r="3" fill="#101c2b"/>
                                        <circle cx="21" cy="15" r="3" fill="#101c2b"/>
                                    </g>

                                    <rect x="<?= $posicaoX - 45 ?>" y="<?= $posicaoY - 30 ?>" width="90" height="16" rx="4" fill="#0b1420" stroke="#ff7b90" stroke-width="1"/>
                                    <text x="<?= $posicaoX ?>" y="<?= $posicaoY - 19 ?>" font-family="'Poppins', sans-serif" font-size="8.5" font-weight="bold" fill="#ffffff" text-anchor="middle">
                                        <?= ($status === 'Entregue') ? '✔ ENTREGUE' : '🚛 EM TRÂNSITO' ?>
                                    </text>
                                </svg>

                                <!-- Rodapé de Informações Cartográficas -->
                                <div style="background: #0b1420; padding: 10px 18px; border-top: 1px solid #1e334a; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: #a4c2db;">
                                    <div>
                                        <i class="fa-solid fa-route" style="color: #ff7b90;"></i>
                                        <strong>Trajeto:</strong> Ateliê Amora (Porto Velho/RO) &rarr; <?= sanitizar($p['cidade_entrega'] ?? 'Destino do Comprador') ?>
                                    </div>
                                    <div>
                                        <strong>Transportadora:</strong> <?= sanitizar($p['transportadora'] ?? 'LogiAmora Transportes / Sedex') ?> &bull; Código: <span style="font-family: monospace; color: #fff; background: #1b3047; padding: 2px 6px; border-radius: 4px;"><?= $codigoRastreio ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Linha do Tempo Passo a Passo das 5 Etapas -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; margin-top: 14px;">
                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid var(--cor-borda);">
                                    <div style="color: var(--cor-sucesso); font-size: 1.1rem; margin-bottom: 4px;"><i class="fa-solid fa-circle-check"></i></div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">1. Pedido Realizado</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Pagamento aprovado</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Em preparação', 'Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Em preparação', 'Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-hands-holding"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">2. Em Preparação</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Personalização ateliê</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-box"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">3. Coleta Efetuada</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">Com a transportadora</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= in_array($status, ['Enviado', 'Entregue']) ? 'var(--cor-rosa-queimado)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-truck-fast"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">4. Em Rota de Entrega</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);">A caminho de você</div>
                                </div>

                                <div style="text-align: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid <?= ($status === 'Entregue') ? 'var(--cor-sucesso)' : 'var(--cor-borda)' ?>;">
                                    <div style="color: <?= ($status === 'Entregue') ? 'var(--cor-sucesso)' : '#ccc' ?>; font-size: 1.1rem; margin-bottom: 4px;">
                                        <i class="fa-solid fa-house-circle-check"></i>
                                    </div>
                                    <div style="font-weight: 600; font-size: 0.82rem;">5. Entregue</div>
                                    <div style="font-size: 0.72rem; color: var(--cor-marrom-claro);"><?= ($status === 'Entregue') ? 'Recebido!' : 'No prazo' ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Pergunta Obrigatória: "Sua encomenda já chegou?" & Checagem com Transportadora -->
                        <?php if ($status !== 'Entregue' && !$reembolsoAtivo): ?>
                            <div style="background: #fdfaf6; border: 2px dashed var(--cor-rosa-medio); border-radius: var(--raio-borda); padding: 20px; margin-bottom: 24px;">
                                <h4 style="font-size: 1.05rem; color: var(--cor-marrom-suave); margin-bottom: 6px;">
                                    <i class="fa-regular fa-circle-question" style="color: var(--cor-rosa-queimado);"></i> 
                                    Sua encomenda já chegou ao seu endereço?
                                </h4>
                                <p style="font-size: 0.85rem; color: var(--cor-marrom-claro); margin-bottom: 14px;">
                                    Nosso compromisso é com a sua total satisfação. Diga-nos se já está com o pacote em mãos:
                                </p>

                                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                    <!-- Botão Confirmar Chegada -->
                                    <form action="<?= BASE_URL ?>/pedidos.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="acao" value="confirmar_chegada">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-check"></i> Sim, já recebi com sucesso!
                                        </button>
                                    </form>

                                    <!-- Botão Ainda Não Chegou (Checagem com transportadora) -->
                                    <form action="<?= BASE_URL ?>/pedidos.php" method="POST" style="margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                        <input type="hidden" name="acao" value="relatar_atraso">
                                        <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm">
                                            <i class="fa-solid fa-xmark"></i> Ainda não chegou
                                        </button>
                                    </form>
                                </div>

                                <!-- Parecer da Transportadora & Oferta de Reembolso caso tenha relatado não ter chegado -->
                                <?php if ($confirmacao === 'nao_chegou'): ?>
                                    <div style="margin-top: 18px; padding-top: 16px; border-top: 1px solid var(--cor-borda);">
                                        <div class="alert alert-warning" style="margin-bottom: 14px;">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            <div>
                                                <strong>Parecer da Transportadora LogiAmora:</strong> 
                                                <span>Identificamos que o veículo da rota regional registrou um atraso de tráfego que ultrapassou a estimativa habitual de entrega.</span>
                                            </div>
                                        </div>

                                        <!-- Oferta de Reembolso Imediato Conforme Requisito -->
                                        <div style="background: #fff; padding: 18px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-rosa-medio);">
                                            <h5 style="color: var(--cor-rosa-queimado); font-size: 1rem; margin-bottom: 8px;">
                                                <i class="fa-solid fa-hand-holding-dollar"></i> Garantia de Entrega Amora Artesanal
                                            </h5>
                                            <p style="font-size: 0.88rem; color: var(--cor-marrom-texto); line-height: 1.6; margin-bottom: 14px;">
                                                Como a entrega excedeu o prazo limite estipulado, você tem o direito garantido de solicitar o <strong>reembolso integral imediato</strong> ou o <strong>reenvio expresso com um mimo especial</strong> do ateliê.
                                            </p>

                                            <form action="<?= BASE_URL ?>/pedidos.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                <input type="hidden" name="acao" value="solicitar_reembolso">
                                                <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">

                                                <div style="display: flex; gap: 14px; flex-direction: column; margin-bottom: 14px;">
                                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                                                        <input type="radio" name="opcao_reembolso" value="reembolso_pix" checked style="accent-color: var(--cor-rosa-queimado);">
                                                        <span><strong>Reembolso Integral Imediato via Pix</strong> no valor de <?= formatarMoeda((float)$p['valor_total']) ?></span>
                                                    </label>

                                                    <div style="margin-left: 24px;">
                                                        <input type="text" name="chave_pix_reembolso" class="form-control" placeholder="Informe sua chave Pix (CPF, Celular ou E-mail)" style="font-size: 0.85rem;" required>
                                                    </div>

                                                    <label style="display: flex; align-items: center; gap: 10px; font-size: 0.9rem; cursor: pointer;">
                                                        <input type="radio" name="opcao_reembolso" value="reenvio_expresso" style="accent-color: var(--cor-rosa-queimado);">
                                                        <span><strong>Reenvio Expresso com Brinde</strong> (produziremos e despacharemos novamente hoje)</span>
                                                    </label>
                                                </div>

                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fa-solid fa-paper-plane"></i> Confirmar Solicitação de Reembolso / Reenvio
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($reembolsoAtivo): ?>
                            <!-- Alerta de Reembolso Já Registrado -->
                            <div class="alert alert-info" style="margin-bottom: 24px;">
                                <i class="fa-solid fa-shield-halved"></i>
                                <div>
                                    <strong>Protocolo de Reembolso Ativo: <?= sanitizar($p['protocolo_reembolso'] ?? '') ?></strong><br>
                                    <span style="font-size: 0.85rem;">Status: <?= sanitizar($p['motivo_reembolso'] ?? 'Solicitação em processamento prioritário pelo setor financeiro.') ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Itens do Pedido com Personalização Exclusiva -->
                        <div style="margin-bottom: 20px;">
                            <h4 style="font-size: 0.95rem; margin-bottom: 12px; color: var(--cor-marrom-suave); text-transform: uppercase;">
                                Itens Deste Pedido:
                            </h4>

                            <div style="display: flex; flex-direction: column; gap: 10px;">
                                <?php if (!empty($itensPorPedido[$p['id']])): ?>
                                    <?php foreach ($itensPorPedido[$p['id']] as $item): ?>
                                        <div style="display: flex; justify-content: space-between; align-items: center; background: #fffbf9; padding: 12px 16px; border-radius: var(--raio-borda-sm); border: 1px solid var(--cor-borda); flex-wrap: wrap; gap: 12px;">
                                            <div style="display: flex; gap: 12px; align-items: center;">
                                                <img src="<?= BASE_URL ?>/<?= sanitizar($item['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="" style="width: 46px; height: 46px; border-radius: 6px; background: var(--cor-rosa-claro); object-fit: contain; padding: 4px;">
                                                <div>
                                                    <div style="font-weight: 600; font-size: 0.92rem; color: var(--cor-marrom-suave);">
                                                        <?= sanitizar($item['nome']) ?>
                                                    </div>
                                                    <div style="font-size: 0.8rem; color: var(--cor-marrom-claro);">
                                                        <?= $item['quantidade'] ?>x <?= formatarMoeda((float)$item['preco_unitario']) ?>
                                                    </div>
                                                    <?php if (!empty($item['personalizacao'])): ?>
                                                        <div style="font-size: 0.78rem; background: var(--cor-rosa-claro); color: var(--cor-rosa-queimado); padding: 3px 8px; border-radius: 4px; display: inline-block; margin-top: 4px;">
                                                            <i class="fa-solid fa-sparkles"></i> <strong>Personalização:</strong> "<?= sanitizar($item['personalizacao']) ?>"
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <div style="text-align: right;">
                                                <div style="font-weight: 700; color: var(--cor-rosa-queimado); font-size: 1.05rem;">
                                                    <?= formatarMoeda((float)($item['quantidade'] * $item['preco_unitario'])) ?>
                                                </div>

                                                <!-- Se o pedido foi entregue, permitir avaliar o produto e responder à pergunta NPS -->
                                                <?php if ($status === 'Entregue'): ?>
                                                    <a href="#avaliar_prod_<?= $item['produto_id'] ?>" style="font-size: 0.78rem; color: var(--cor-rosa-queimado); text-decoration: underline; display: block; margin-top: 4px;">
                                                        ★ Deixar Avaliação
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- Formulário de Avaliação e Pergunta NPS ("Você indicaria a um amigo?") -->
                                        <?php if ($status === 'Entregue'): ?>
                                            <div id="avaliar_prod_<?= $item['produto_id'] ?>" style="background: #fff; border: 1px solid var(--cor-rosa-medio); padding: 18px; border-radius: var(--raio-borda-sm); margin: 6px 0 14px 20px;">
                                                <h5 style="color: var(--cor-rosa-queimado); margin-bottom: 8px; font-size: 0.95rem;">
                                                    Avalie o item: <?= sanitizar($item['nome']) ?>
                                                </h5>
                                                
                                                <form action="<?= BASE_URL ?>/pedidos.php" method="POST">
                                                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">
                                                    <input type="hidden" name="acao" value="salvar_avaliacao">
                                                    <input type="hidden" name="pedido_id" value="<?= $p['id'] ?>">
                                                    <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">

                                                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 14px; margin-bottom: 12px;">
                                                        <div class="form-group" style="margin-bottom: 0;">
                                                            <label class="form-label" style="font-size: 0.82rem;">Sua Nota de Satisfação:</label>
                                                            <select name="estrelas" class="form-control" style="font-size: 0.85rem;">
                                                                <option value="5">★★★★★ (5 Estrelas - Excelente!)</option>
                                                                <option value="4">★★★★☆ (4 Estrelas - Muito Bom)</option>
                                                                <option value="3">★★★☆☆ (3 Estrelas - Bom)</option>
                                                                <option value="2">★★☆☆☆ (2 Estrelas - Regular)</option>
                                                                <option value="1">★☆☆☆☆ (1 Estrela - Ruim)</option>
                                                            </select>
                                                        </div>

                                                        <!-- Pergunta NPS Solicitada Pelo Usuário -->
                                                        <div class="form-group" style="margin-bottom: 0;">
                                                            <label class="form-label" style="font-size: 0.82rem;">Você indicaria o nosso site para um amigo? *</label>
                                                            <div style="display: flex; gap: 16px; padding-top: 6px;">
                                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                                                                    <input type="radio" name="indicaria_amigo" value="sim" checked style="accent-color: var(--cor-rosa-queimado);"> Sim, com certeza!
                                                                </label>
                                                                <label style="display: flex; align-items: center; gap: 6px; font-size: 0.88rem; cursor: pointer;">
                                                                    <input type="radio" name="indicaria_amigo" value="nao" style="accent-color: var(--cor-rosa-queimado);"> Não no momento
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="form-group">
                                                        <label class="form-label" style="font-size: 0.82rem;">Seu Comentário sobre o Produto e Aroma:</label>
                                                        <textarea name="comentario" class="form-control" rows="2" placeholder="Conte como foi sua experiência com o produto artesanal..." required style="font-size: 0.85rem;"></textarea>
                                                    </div>

                                                    <button type="submit" class="btn btn-primary btn-sm">
                                                        Publicar Avaliação
                                                    </button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Dados de Entrega e Pagamento -->
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.88rem; color: var(--cor-marrom-claro); border-top: 1px solid var(--cor-borda); padding-top: 16px;">
                            <div>
                                <i class="fa-solid fa-location-dot" style="color: var(--cor-rosa-queimado);"></i> 
                                <strong>Destino:</strong> <?= sanitizar($p['endereco_entrega']) ?>
                            </div>
                            <div>
                                <strong>Pagamento:</strong> <?= sanitizar($p['forma_pagamento_detalhes'] ?? $p['forma_pagamento']) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
