<?php
/**
 * Formulário de Cadastro e Edição de Produto (RF09, UC07, RNF02)
 */
require_once __DIR__ . '/header.php';

$db = getDBConnection();
$categorias = getCategorias();

$id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;
$modoEdicao = $id > 0;

$produto = [
    'categoria_id' => '',
    'nome' => '',
    'descricao' => '',
    'preco' => '',
    'estoque' => '10',
    'imagem_url' => 'assets/img/placeholder.svg'
];

if ($modoEdicao) {
    $stmt = $db->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $dados = $stmt->fetch();
    if ($dados) {
        $produto = $dados;
    } else {
        setFlash('danger', 'Produto não encontrado.');
        header('Location: ' . BASE_URL . '/admin/produtos.php');
        exit;
    }
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $erro = 'Sessão expirada. Por favor, tente novamente.';
    } else {
        // Validação e sanitização (RNF02)
        $categoria_id = (int)($_POST['categoria_id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $preco = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
        $preco = (float)$preco;
        $estoque = (int)($_POST['estoque'] ?? 0);
        $imagem_url = trim($_POST['imagem_url'] ?? $produto['imagem_url']);

        // Upload de Imagem (se fornecido)
        if (!empty($_FILES['imagem_arquivo']['name'])) {
            $arquivo = $_FILES['imagem_arquivo'];
            $extensoesPermitidas = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
            $ext = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $extensoesPermitidas)) {
                $novoNome = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                $destino = ROOT_PATH . '/assets/img/' . $novoNome;
                if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
                    $imagem_url = 'assets/img/' . $novoNome;
                }
            } else {
                $erro = 'Formato de imagem inválido. Formatos permitidos: JPG, PNG, SVG, WEBP.';
            }
        }

        if (empty($nome) || $categoria_id <= 0 || $preco <= 0) {
            $erro = 'Por favor, preencha o nome do produto, selecione uma categoria válida e defina um preço maior que zero.';
        } elseif (empty($erro)) {
            if ($modoEdicao) {
                $stmtUpdate = $db->prepare("UPDATE produtos 
                                            SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, estoque = ?, imagem_url = ? 
                                            WHERE id = ?");
                $stmtUpdate->execute([$categoria_id, $nome, $descricao, $preco, $estoque, $imagem_url, $id]);
                setFlash('success', 'Produto atualizado com sucesso!');
            } else {
                $stmtInsert = $db->prepare("INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem_url) 
                                            VALUES (?, ?, ?, ?, ?, ?)");
                $stmtInsert->execute([$categoria_id, $nome, $descricao, $preco, $estoque, $imagem_url]);
                setFlash('success', 'Novo produto cadastrado com sucesso!');
            }

            header('Location: ' . BASE_URL . '/admin/produtos.php');
            exit;
        }
    }
}

$pageTitle = $modoEdicao ? 'Editar Produto' : 'Cadastrar Produto';
?>

<div class="container" style="margin: 30px auto 60px;">
    <div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h1 style="font-size: 2rem; margin-bottom: 4px;"><?= $modoEdicao ? 'Editar Produto' : 'Novo Produto' ?></h1>
            <p style="color: var(--cor-marrom-claro);">Preencha os dados do item para a vitrine virtual</p>
        </div>
        <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Voltar à Lista
        </a>
    </div>

    <?php if (!empty($erro)): ?>
        <div class="alert alert-danger">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= sanitizar($erro) ?></span>
        </div>
    <?php endif; ?>

    <div style="background: var(--cor-branco); padding: 36px; border-radius: var(--raio-borda); border: 1px solid var(--cor-borda); box-shadow: var(--sombra-suave); max-width: 800px; margin: 0 auto;">
        <form action="<?= BASE_URL ?>/admin/produto_form.php<?= $modoEdicao ? '?id=' . $id : '' ?>" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="nome" class="form-label">Nome do Produto Artesanal *</label>
                    <input type="text" name="nome" id="nome" class="form-control" placeholder="Ex.: Vela Aromática Baunilha Real" value="<?= sanitizar($produto['nome']) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <label for="categoria_id" class="form-label">Categoria *</label>
                    <select name="categoria_id" id="categoria_id" class="form-control" required>
                        <option value="">Selecione uma categoria...</option>
                        <?php foreach ($categorias as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= (int)$produto['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                <?= sanitizar($c['nome_categoria']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="descricao" class="form-label">Descrição Completa</label>
                <textarea name="descricao" id="descricao" class="form-control" rows="4" placeholder="Detalhes dos ingredientes, tamanho, fragrância e cuidados com a peça..."><?= sanitizar($produto['descricao']) ?></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="preco" class="form-label">Preço de Venda (R$) *</label>
                    <input type="number" step="0.01" name="preco" id="preco" class="form-control" placeholder="45.00" value="<?= $produto['preco'] ?>" required>
                </div>

                <div class="form-group">
                    <label for="estoque" class="form-label">Quantidade em Estoque *</label>
                    <input type="number" name="estoque" id="estoque" class="form-control" placeholder="10" value="<?= $produto['estoque'] ?>" min="0" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; align-items: center; border-top: 1px solid var(--cor-borda); padding-top: 20px; margin-top: 10px;">
                <div>
                    <div class="form-group">
                        <label for="imagem_url" class="form-label">Caminho da Imagem ou URL</label>
                        <input type="text" name="imagem_url" id="imagem_url" class="form-control" placeholder="assets/img/meu_produto.svg" value="<?= sanitizar($produto['imagem_url'] ?? '') ?>">
                    </div>

                    <div class="form-group">
                        <label for="imagem_arquivo" class="form-label">Ou Enviar Arquivo de Foto (JPG, PNG, SVG)</label>
                        <input type="file" name="imagem_arquivo" id="imagem_arquivo" class="form-control" accept="image/*">
                    </div>
                </div>

                <div style="text-align: center;">
                    <span style="font-size: 0.8rem; color: var(--cor-marrom-claro); display: block; margin-bottom: 6px;">Prévia Atual:</span>
                    <img src="<?= BASE_URL ?>/<?= sanitizar($produto['imagem_url'] ?? 'assets/img/placeholder.svg') ?>" alt="Prévia" style="width: 100px; height: 100px; object-fit: contain; border-radius: 8px; border: 1px solid var(--cor-borda); background: var(--cor-rosa-claro); margin: 0 auto; padding: 6px;">
                </div>
            </div>

            <div style="margin-top: 30px; display: flex; gap: 14px; justify-content: flex-end;">
                <a href="<?= BASE_URL ?>/admin/produtos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> <?= $modoEdicao ? 'Salvar Alterações' : 'Cadastrar Produto' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
