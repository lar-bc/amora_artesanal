# Amora Artesanal 🌸

> **Vitrine Virtual & E-commerce de Presentes e Aromas Feitos à Mão**  
> Desenvolvido com apoio de Inteligência Artificial  
> **Autora:** Larissa Benício da Cruz  
> **Turma:** 3º B Informática  
> **Instituição:** Instituto Federal de Rondônia (IFRO) - Campus Porto Velho Calama  
> **Disciplina:** Desenvolvimento de Sistemas  

---

## 📖 Sobre o Projeto

O **Amora Artesanal** é uma plataforma completa de e-commerce e vitrine virtual voltada para a comercialização e personalização de presentes e produtos artesanais feitos à mão, como:
- **Velas Aromáticas** (cera vegetal e essências botânicas);
- **Sabonetes Artesanais** (saponificação a frio, argilas e extratos naturais);
- **Amigurumis** (peças de crochê em algodão antialérgico);
- **Kits Presente** (combinações especiais em caixas artesanais).

O sistema atende a dois perfis principais de usuários:
1. **Cliente:** Navega pela vitrine, pesquisa e filtra por categoria, personaliza produtos antes da compra, gerencia o carrinho, finaliza pedidos e acompanha o status de entrega.
2. **Administrador (Lojista):** Acessa a área restrita (`/admin`), gerencia o catálogo e estoque de produtos, atualiza o status de pedidos em tempo real e visualiza mensagens de contato.

---

## 🎯 Requisitos Implementados

### Requisitos Funcionais (RF):
- **RF01:** Cadastro de clientes com nome, e-mail e senha protegida por hash Bcrypt.
- **RF02:** Autenticação segura (login e logout) para clientes e administradores.
- **RF03:** Listagem detalhada de produtos com imagem, nome, descrição, categoria, preço e estoque.
- **RF04:** Busca de produtos por nome e filtros dinâmicos por categoria.
- **RF05:** Personalização exclusiva de itens artesanais antes da inclusão no carrinho.
- **RF06:** Gerenciamento dinâmico de carrinho de compras (adição, alteração de quantidades, remoção e cálculo em tempo real).
- **RF07:** Gravação de pedidos e itens no banco de dados vinculados a cliente e endereço.
- **RF08:** Histórico e rastreamento de pedidos do cliente com status atualizado.
- **RF09:** Painel administrativo com CRUD completo de produtos (cadastro, edição, exclusão e estoque).
- **RF10:** Atualização do status dos pedidos pelo administrador (`Pedido realizado`, `Em preparação`, `Enviado`, `Entregue`).
- **RF11:** Formulário de contato e pedidos de encomendas personalizadas com visualização no painel admin.

### Requisitos Não Funcionais (RNF):
- **RNF01 (Segurança de Senhas):** Criptografia de senhas utilizando `password_hash` com algoritmo Bcrypt e verificação com `password_verify`.
- **RNF02 (Validação e Sanitização):** Tratamento rigoroso de todos os formulários no lado do servidor em PHP.
- **RNF03 (Responsividade):** Interface web totalmente adaptável para desktops, tablets e smartphones.
- **RNF04 (Usabilidade e Identidade Visual):** Paleta artesanal delicada com tons de creme (`#fffaf5`), rosa claro (`#fdeeed`), rosa queimado (`#c86d7e`) e marrom suave (`#5c3d2e`), com bordas arredondadas e tipografia elegante.

---

## 🛠️ Stack Tecnológica

- **Front-end:** HTML5 semântico, CSS3 responsivo e moderno, JavaScript puro (AJAX, controle dinâmico de carrinho).
- **Back-end:** PHP 8+ com PDO (Prepared Statements).
- **Banco de Dados:** MySQL / MariaDB (com fallback automático e autônomo para SQLite para execução rápida local).
- **Ícones & Tipografia:** FontAwesome 6, Google Fonts (*Playfair Display* e *Poppins*).

---

## 🚀 Como Executar o Projeto Localmente

### Opção 1: Servidor Embutido do PHP (Mais Rápido)
1. Abra o terminal na raiz da pasta `amora_artesanal`:
   ```bash
   php -S localhost:8000
   ```
2. Abra no navegador: [http://localhost:8000](http://localhost:8000)

*O sistema detectará o ambiente e inicializará o banco de dados e os dados de exemplo automaticamente!*

---

### Opção 2: Com XAMPP / Apache / MySQL
1. Copie a pasta `amora_artesanal` para dentro do diretório `htdocs` do seu XAMPP (`C:\xampp\htdocs\amora_artesanal`).
2. Inicie os módulos **Apache** e **MySQL** no Painel do XAMPP.
3. No phpMyAdmin ou no terminal do MySQL, crie o banco de dados e importe os scripts:
   - `database/schema.sql`
   - `database/seeds.sql`
4. Acesse: [http://localhost/amora_artesanal](http://localhost/amora_artesanal)

---

## 🔑 Credenciais de Demonstração (Seed)

| Perfil | E-mail | Senha | Acesso |
|---|---|---|---|
| **Administrador (Lojista)** | `admin@amora.com` | `admin123` | Painel Admin (`/admin`) |
| **Cliente de Testes** | `cliente@exemplo.com` | `cliente123` | Vitrine, Carrinho e Pedidos |

---

## 🗄️ Modelo de Dados (DER)

- `usuarios`: `id`, `nome`, `email`, `senha_hash`, `tipo_usuario` ('cliente' ou 'admin'), `data_cadastro`
- `categorias`: `id`, `nome_categoria`
- `produtos`: `id`, `categoria_id` (FK), `nome`, `descricao`, `preco`, `estoque`, `imagem_url`
- `pedidos`: `id`, `usuario_id` (FK), `data_pedido`, `valor_total`, `status`, `endereco_entrega`
- `itens_pedido`: `id`, `pedido_id` (FK), `produto_id` (FK), `quantidade`, `preco_unitario`, `personalizacao`
- `mensagens_contato`: `id`, `nome`, `email`, `mensagem`, `data_envio`, `lida`
