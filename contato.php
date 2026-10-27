<?php
/**
 * Fale Conosco, Pedidos de Encomendas & Perguntas Frequentes (FAQ)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$erro = '';
$sucesso = '';
$nome = '';
$email = '';
$telefone = '';
$mensagem = '';

$usuario = getUsuarioLogado();
if ($usuario) {
    $nome = $usuario['nome'];
    $email = $usuario['email'];
    $telefone = $usuario['telefone'] ?? '';
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));
        $telefone = trim($_POST['telefone'] ?? '');
        $mensagem = trim($_POST['mensagem'] ?? '');

        if (empty($nome) || empty($email) || empty($mensagem)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios do formulário.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Por favor, informe um endereço de e-mail válido.';
        } else {
            $db = getDBConnection();
            $stmt = $db->prepare("INSERT INTO mensagens_contato (nome, email, telefone, mensagem) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nome, $email, $telefone, $mensagem]);

            setFlash('success', 'Mensagem enviada com sucesso! Em breve nossa equipe do ateliê entrará em contato com você.');
            header('Location: ' . BASE_URL . '/contato.php');
            exit;
        }
    }
}

$pageTitle = 'Fale Conosco & Encomendas Sob Medida';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="text-align: center; max-width: 700px; margin: 0 auto 36px;">
        <h1 style="font-size: 2.2rem; margin-bottom: 8px;">Fale com o Ateliê Amora</h1>
        <p style="color: var(--cor-marrom-claro);">Tire dúvidas sobre aromas botânicos, solicite encomendas exclusivas para datas comemorativas ou converse no chat direto</p>
    </div>

    <!-- Seção de Contato e Formulário -->
    <div style="display: grid; grid-template-columns: 1fr 1.3fr; gap: 40px; background: var(--cor-branco); padding: 40px; border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); margin-bottom: 50px;">
        <!-- Informações Oficiais da Central de Ajuda e Contato -->
        <div style="background: var(--cor-rosa-claro); padding: 30px; border-radius: var(--raio-borda); border: 1px solid var(--cor-rosa-medio); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: inline-block; background: var(--cor-rosa-medio); color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.78rem; font-weight: 700; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">
                    <i class="fa-solid fa-headset"></i> Central de Atendimento ao Cliente
                </div>
                <h3 style="font-size: 1.45rem; color: var(--cor-rosa-queimado); margin-bottom: 12px;">
                    Central de Ajuda & Suporte Amora
                </h3>
                <p style="font-size: 0.92rem; line-height: 1.6; color: var(--cor-marrom-texto); margin-bottom: 22px;">
                    Dúvidas sobre entregas, pedidos sob medida ou pós-compra? Nossa equipe está pronta para te atender com carinho e agilidade!
                </p>

                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.92rem; margin-bottom: 24px;">
                    <!-- SAC Nacional 0800 (Fictício realista) -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px; background: #fff0f3; color: var(--cor-rosa-queimado); font-size: 1.25rem;">
                            <i class="fa-solid fa-phone-volume"></i>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">SAC NACIONAL (LIGAÇÃO GRATUITA)</span>
                            <strong style="color: var(--cor-rosa-queimado); font-size: 1.2rem; letter-spacing: 0.5px;">0800 892 4567</strong>
                            <div style="font-size: 0.75rem; color: var(--cor-marrom-claro);">Disponível para todo o Brasil (fixo e celular)</div>
                        </div>
                    </div>

                    <!-- Telefone Fixo Ateliê -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px; background: #fff6ee; color: #d0753b; font-size: 1.2rem;">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">CENTRAL LOCAL (PORTO VELHO / RO)</span>
                            <strong style="color: var(--cor-marrom-suave); font-size: 1.05rem;">(69) 3219-4500</strong>
                            <div style="font-size: 0.75rem; color: var(--cor-marrom-claro);">Recepção e pedidos do Ateliê</div>
                        </div>
                    </div>

                    <!-- WhatsApp Oficial -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px; background: #e7f8ee; color: #25d366; font-size: 1.3rem;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">WHATSAPP OFICIAL DE ATENDIMENTO</span>
                            <div><a href="https://wa.me/5569984123456?text=Olá! Vim pela Central de Ajuda do site Amora Artesanal e gostaria de tirar uma dúvida." target="_blank" style="color: #208743; font-weight: 700; font-size: 1.05rem;">(69) 98412-3456</a></div>
                            <div style="font-size: 0.75rem; color: var(--cor-marrom-claro);">Clique para abrir conversa no WhatsApp</div>
                        </div>
                    </div>

                    <!-- Chat Integrado no Site -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px; background: #f0f4ff; color: #3b6cd0; font-size: 1.2rem;">
                            <i class="fa-regular fa-comments"></i>
                        </div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">CHAT ONLINE EM TEMPO REAL</span>
                            <div><a href="<?= BASE_URL ?>/chat.php" style="color: #3b6cd0; font-weight: 700;">Conectar com Ateliê agora &rarr;</a></div>
                            <div style="font-size: 0.75rem; color: var(--cor-marrom-claro);">Atendimento exclusivo e seguro pelo site</div>
                        </div>
                    </div>

                    <!-- E-mail de Suporte -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px;"><i class="fa-solid fa-envelope"></i></div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">E-MAIL DE SUPORTE</span>
                            <div style="color: var(--cor-marrom-suave); font-weight: 600; font-size: 0.92rem;">ajuda@amoraartesanal.com.br</div>
                        </div>
                    </div>

                    <!-- Endereço Físico do Ateliê -->
                    <div style="display: flex; gap: 12px; align-items: center; background: #fff; padding: 12px 14px; border-radius: 10px; border: 1px solid var(--cor-borda);">
                        <div class="btn-icon" style="width: 44px; height: 44px;"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <span style="font-size: 0.78rem; color: var(--cor-marrom-claro); display: block; font-weight: 600;">ATELIÊ & PONTO DE RETIRADA</span>
                            <div style="font-size: 0.88rem; color: var(--cor-marrom-suave);">Av. Calama, 4985 - Flodoaldo Pontes Pinto, Porto Velho - RO (CEP: 76820-408)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding-top: 18px; border-top: 1px solid var(--cor-borda); font-size: 0.85rem; color: var(--cor-marrom-suave); line-height: 1.6;">
                <strong>🕒 Horário de Atendimento da Central de Ajuda:</strong><br>
                &bull; <strong>Segunda a Sexta:</strong> 08:00 às 18:00<br>
                &bull; <strong>Sábados:</strong> 08:00 às 13:00<br>
                &bull; <strong>Domingos e Feriados:</strong> Plantão pelo formulário e Chat Online
            </div>
        </div>

        <!-- Formulário de Mensagem e Encomendas -->
        <div>
            <h3 style="font-size: 1.4rem; margin-bottom: 20px; color: var(--cor-marrom-suave);">Envie sua Mensagem ou Peça sua Encomenda</h3>

            <?php if (!empty($erro)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= sanitizar($erro) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/contato.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="form-group">
                    <label for="nome" class="form-label">Seu Nome Completo *</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Como podemos te chamar?" value="<?= sanitizar($nome) ?>" required>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label for="email" class="form-label">Seu E-mail *</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="seu.nome@gmail.com" value="<?= sanitizar($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefone" class="form-label">Telefone / WhatsApp (máx 15 caracteres)</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(69) 99999-9999" maxlength="15" value="<?= sanitizar($telefone) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="mensagem" class="form-label">Mensagem ou Detalhes da Encomenda Sob Medida *</label>
                    <textarea name="mensagem" id="mensagem" class="form-control" rows="5" placeholder="Conte quais peças você gostaria de encomendar, quantidades, aromas preferidos ou datas para presentes..." required><?= sanitizar($mensagem) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top: 10px;">
                    <i class="fa-regular fa-paper-plane"></i> Enviar Solicitação ao Ateliê
                </button>
            </form>
        </div>
    </div>

    <!-- Mapa Interativo do Ateliê Amora Artesanal (Estilo Google Maps) -->
    <div style="margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
            <div>
                <h3 style="font-size: 1.35rem; color: var(--cor-marrom-suave); margin-bottom: 4px;">
                    <i class="fa-solid fa-map-location-dot" style="color: var(--cor-rosa-queimado);"></i> Nosso Ateliê & Ponto de Retirada
                </h3>
                <p style="color: var(--cor-marrom-claro); font-size: 0.88rem; margin: 0;">
                    Av. Calama, 4985 - Bairro Flodoaldo Pontes Pinto, Porto Velho - RO (CEP: 76820-408)
                </p>
            </div>
            <a href="https://maps.google.com/?q=-8.76194,-63.90389" target="_blank" class="btn btn-secondary" style="font-size: 0.82rem; padding: 6px 14px;">
                <i class="fa-solid fa-arrow-up-right-from-square"></i> Abrir no Google Maps Oficial
            </a>
        </div>

        <div class="mapa-interativo-wrapper">
            <div class="mapa-top-bar">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span class="mapa-status-gps">
                        <span class="gps-dot-pulse"></span>
                        <span>ATELIÊ ABERTO &bull; Atendimento Presencial e Retiradas</span>
                    </span>
                    <span style="font-size: 0.82rem; color: var(--cor-marrom-claro);">
                        Seg a Sex: 08h às 18h &bull; Sáb: 08h às 13h
                    </span>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <div class="mapa-camadas-group">
                        <button type="button" class="btn-camada-mapa active" data-camada="ruas">
                            <i class="fa-solid fa-map"></i> Google Ruas
                        </button>
                        <button type="button" class="btn-camada-mapa" data-camada="satelite">
                            <i class="fa-solid fa-satellite"></i> Satélite
                        </button>
                        <button type="button" class="btn-camada-mapa" data-camada="amora">
                            <i class="fa-solid fa-palette"></i> Amora
                        </button>
                    </div>

                    <div class="mapa-acoes-group">
                        <button type="button" class="btn-mapa-acao btn-mapa-fullscreen" title="Tela cheia">
                            <i class="fa-solid fa-expand"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div id="mapa_loja_amora" class="mapa-leaflet-canvas" style="height: 380px;"></div>

            <div style="background: #fff; padding: 10px 18px; border-top: 1px solid var(--cor-borda); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 0.8rem; color: var(--cor-marrom-suave);">
                <div>
                    <i class="fa-solid fa-store" style="color: var(--cor-rosa-queimado);"></i>
                    <strong>Venha nos visitar:</strong> Estacionamento gratuito no local e espaço aconchegante para conhecer nossos aromas e criações botânicas.
                </div>
                <div>
                    <strong>Telefone:</strong> (69) 3219-4500 &bull; <strong>WhatsApp:</strong> (69) 98412-3456
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.AmoraMapas && typeof window.AmoraMapas.init === 'function') {
                window.AmoraMapas.init('mapa_loja_amora', {
                    origem: {
                        lat: -8.7441684,
                        lng: -63.8718575,
                        nome: 'Ateliê Amora Artesanal (IFRO Calama)',
                        endereco: 'Av. Calama, 4985 - Bairro Flodoaldo Pontes Pinto, Porto Velho - RO (CEP: 76820-441)'
                    },
                    tipo: 'loja'
                });
            }
        });
        </script>
    </div>

    <!-- Seção de Perguntas Frequentes (FAQ de Grandes Sites) -->
    <div style="background: var(--cor-branco); padding: 40px; border-radius: var(--raio-borda-lg); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave);">
        <div style="text-align: center; margin-bottom: 32px;">
            <h2 style="font-size: 1.8rem; margin-bottom: 8px;">Perguntas Frequentes (FAQ)</h2>
            <p style="color: var(--cor-marrom-claro);">Tire suas principais dúvidas sobre cuidados, prazos, rastreamento e nossa garantia de reembolso</p>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
            <div style="background: #fffbf9; padding: 20px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda);">
                <h4 style="color: var(--cor-rosa-queimado); font-size: 1.05rem; margin-bottom: 8px;">
                    <i class="fa-solid fa-fire-burner"></i> Como cuidar da minha vela para durar mais?
                </h4>
                <p style="font-size: 0.9rem; color: var(--cor-marrom-texto); line-height: 1.6;">
                    Na primeira queima, deixe a cera derreter até alcançar toda a borda do vidro (cerca de 2 horas). Isso evita a formação de "túneis". Sempre apare o pavio para 5mm antes de reacender.
                </p>
            </div>

            <div style="background: #fffbf9; padding: 20px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda);">
                <h4 style="color: var(--cor-rosa-queimado); font-size: 1.05rem; margin-bottom: 8px;">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Qual o prazo de produção para itens personalizados?
                </h4>
                <p style="font-size: 0.9rem; color: var(--cor-marrom-texto); line-height: 1.6;">
                    Peças personalizadas levam de 2 a 4 dias úteis para confecção, secagem e cura adequada da cera vegetal e saponificação, garantindo máxima intensidade de aroma e acabamento perfeito.
                </p>
            </div>

            <div style="background: #fffbf9; padding: 20px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda);">
                <h4 style="color: var(--cor-rosa-queimado); font-size: 1.05rem; margin-bottom: 8px;">
                    <i class="fa-solid fa-map-location-dot"></i> Como funciona o rastreamento com mapa em tempo real?
                </h4>
                <p style="font-size: 0.9rem; color: var(--cor-marrom-texto); line-height: 1.6;">
                    Assim que seu pedido é despachado, um código oficial de rastreio (padrão AM...BR) é emitido. Na página "Rastrear Pedido", você acompanha a rota visual no mapa com status da transportadora.
                </p>
            </div>

            <div style="background: #fffbf9; padding: 20px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda);">
                <h4 style="color: var(--cor-rosa-queimado); font-size: 1.05rem; margin-bottom: 8px;">
                    <i class="fa-solid fa-hand-holding-dollar"></i> E se a minha encomenda atrasar além do prazo?
                </h4>
                <p style="font-size: 0.9rem; color: var(--cor-marrom-texto); line-height: 1.6;">
                    Nossa política de satisfação garante 100% do seu dinheiro de volta via Pix imediato ou o reenvio expresso da sua encomenda com um brinde especial caso a transportadora confirme atraso fora da data limite.
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
