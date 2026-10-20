<?php
/**
 * Fale Conosco, Pedidos de Encomendas & Perguntas Frequentes (FAQ)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        <!-- Informações Oficiais e Botões de Ação Imediata -->
        <div style="background: var(--cor-rosa-claro); padding: 30px; border-radius: var(--raio-borda); border: 1px solid var(--cor-rosa-medio); display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h3 style="font-size: 1.4rem; color: var(--cor-rosa-queimado); margin-bottom: 16px;">
                    Canais de Atendimento Oficial
                </h3>
                <p style="font-size: 0.95rem; line-height: 1.7; color: var(--cor-marrom-texto); margin-bottom: 24px;">
                    Cada peça que criamos carrega história, perfume e dedicação. Fale conosco pelo canal que for mais prático para você!
                </p>

                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 0.92rem; margin-bottom: 24px;">
                    <!-- WhatsApp Oficial com Link Direto -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 42px; height: 42px; background: #e7f8ee; color: #25d366; font-size: 1.3rem;">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div>
                            <strong>WhatsApp Direto do Ateliê:</strong>
                            <div><a href="https://wa.me/5569984256710?text=Olá! Gostaria de informações sobre produtos e encomendas da Amora Artesanal." target="_blank" style="color: var(--cor-rosa-queimado); font-weight: 700; font-size: 1.05rem;">(69) 98425-6710</a></div>
                        </div>
                    </div>

                    <!-- Chat Integrado no Site -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 42px; height: 42px;"><i class="fa-regular fa-comments"></i></div>
                        <div>
                            <strong>Chat Online na Plataforma:</strong>
                            <div><a href="<?= BASE_URL ?>/chat.php" style="color: var(--cor-rosa-queimado); font-weight: 600;">Abrir Chat em Tempo Real &rarr;</a></div>
                        </div>
                    </div>

                    <!-- E-mail -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 42px; height: 42px;"><i class="fa-solid fa-envelope"></i></div>
                        <div>
                            <strong>E-mail Institucional:</strong>
                            <div>contato@amoraartesanal.com.br</div>
                        </div>
                    </div>

                    <!-- Localização -->
                    <div style="display: flex; gap: 12px; align-items: center;">
                        <div class="btn-icon" style="width: 42px; height: 42px;"><i class="fa-solid fa-location-dot"></i></div>
                        <div>
                            <strong>Ateliê de Produção:</strong>
                            <div>Av. Tiradentes, 3000 - Embratel, Porto Velho - RO</div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="padding-top: 20px; border-top: 1px solid var(--cor-borda); font-size: 0.85rem; color: var(--cor-marrom-claro);">
                ✨ Horário de Atendimento: Segunda a Sexta, das 08h às 18h.
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
                        <input type="email" name="email" id="email" class="form-control" placeholder="seuemail@exemplo.com" value="<?= sanitizar($email) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="telefone" class="form-label">Telefone / WhatsApp</label>
                        <input type="text" name="telefone" id="telefone" class="form-control" placeholder="(69) 99999-9999" value="<?= sanitizar($telefone) ?>">
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
