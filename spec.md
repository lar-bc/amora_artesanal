# Amora Artesanal - Especificação Formal de Requisitos e Arquitetura de Software (spec.md)

**Projeto Integrador de Desenvolvimento de Sistemas**  
**Instituição:** Instituto Federal de Rondônia (IFRO) — Campus Porto Velho Calama  
**Curso:** Curso Técnico Integrado ao Ensino Médio em Informática  
**Componente Curricular:** Desenvolvimento de Sistemas  
**Professor Orientador:** Prof. Rafael Pitwak  
**Autor(a):** Larissa Benício da Cruz (3º Ano B Informática)  
**Data:** 10 de Novembro de 2026  
**Repositório Oficial:** [https://github.com/lar-bc/amora_artesanal](https://github.com/lar-bc/amora_artesanal)  
**Sistema Publicado no Ar (Acesso Imediato sem Instalar Nada):** [https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal)  
**Colaborador Convidado no GitHub:** rafaelpitwak  

---

## 1. Visão Geral e Propósito do Sistema

O **Amora Artesanal** é uma plataforma completa de vitrine virtual e comércio eletrônico desenvolvida para apoiar a comercialização, personalização e gestão logística de produtos aromáticos e peças artesanais afetivas (velas aromáticas em cera vegetal de coco, sabonetes botânicos saponificados a frio, amigurumis de crochê em fio antialérgico e kits presenteáveis).

O sistema foi concebido para atender às demandas de pequenos produtores de artesanato regional no estado de Rondônia, proporcionando uma experiência de compra segura, acolhedora e transparente, com validação de endereços reais em tempo real, rastreamento logístico inovador em mapa interativo (Leaflet) e protocolo rigoroso de auditoria financeira bilateral para reembolsos.

### 1.1 Objetivos de Negócio
- Disponibilizar catálogo digital responsivo com ilustrações vetorizadas (SVG) e descrições poéticas;
- Garantir a validação estrita de endereços reais através da integração assíncrona com o web service dos Correios (ViaCEP), impedindo pedidos em logradouros inexistentes;
- Proporcionar telemetria e rastreamento em mapa interativo (Leaflet.js/OpenStreetMap), diferenciando rotas urbanas ultrarrápidas por Drones Expressos em Porto Velho e rotas de longa distância pela Rodovia BR-364 com veículos utilitários;
- Assegurar conformidade contábil e prevenção a fraudes em devoluções por meio de auditoria bilateral de estornos financeiros (validação conjunta entre transportadora LogiAmora e administração lojista antes do Pix de reembolso);
- Permitir execução universal com política 'Zero-Install', possibilitando acesso instantâneo pelo navegador via GitHub Pages sem que clientes ou avaliadores precisem configurar servidores locais.

---

## 2. Requisitos do Sistema

### 2.1 Requisitos Funcionais (RF)
- **RF01 - Catálogo e Detalhes de Produtos:** O sistema deve listar produtos por categorias (Velas, Sabonetes, Amigurumis, Kits), exibindo preço, estoque, dimensões e prazo de produção artesanal.
- **RF02 - Carrinho de Compras Interativo:** O sistema deve permitir inclusão, alteração de quantidades, exclusão de itens e cálculo dinâmico de subtotal com persistência de estado.
- **RF03 - Validação Geográfica de Endereço Real:** No checkout, o sistema deve consultar a API do ViaCEP dos Correios (`https://viacep.com.br/ws/{cep}/json/`) e preencher automaticamente logradouro, bairro, cidade e estado. Se o CEP for inexistente na base dos Correios, o sistema deve bloquear o avanço da compra.
- **RF04 - Finalização de Compra e Pagamento Pix:** O sistema deve gerar pedido com identificador único, chave Pix para pagamento, cálculo de frete e prazo estimado de entrega.
- **RF05 - Rastreamento Logístico em Tempo Real:** O sistema deve exibir mapa interativo (Leaflet.js/OpenStreetMap) com a movimentação da encomenda entre a origem e o destino do cliente.
- **RF06 - Diferenciação de Modais de Transporte:**
  - *Curta Duração / Perímetro Urbano (Porto Velho):* Drone Autônomo LogiAmora SkyDelivery com velocidade constante de 45 km/h, altitude de 45 m e animação de hélices giratórias.
  - *Longa Duração / Interestadual e Interior:* Furgão Utilitário Rodoviário trafegando pela BR-364 a 75 km/h com telemetria GPS.
- **RF07 - Protocolo de Auditoria Bilateral de Reembolso:** Solicitações de estorno financeiro exigem aprovação conjunta com geração de laudo técnico de recusa/avaria pela transportadora parceira (`LogiAmora Transportes / Sedex`) e parecer homologado do Administrador antes da emissão do comprovante de devolução Pix.
- **RF08 - Central de Atendimento e Chat:** O cliente deve poder enviar mensagens diretas ao ateliê e visualizar histórico de conversas.
- **RF09 - Avaliações de Produtos e Métrica NPS:** Clientes podem avaliar produtos com estrelas (1 a 5), comentários textuais e resposta objetiva à pergunta de recomendação ("Você indicaria a um amigo?").
- **RF10 - Autenticação Segura com Perfis de Usuário:** O sistema deve diferenciar perfis de acesso (`cliente`, `admin`, `logistica`, `auditor`), utilizando criptografia de senhas com algoritmo Bcrypt.
- **RF11 - Sanitização e Validação de Entradas:** Tratamento de e-mails, validação de formato numérico de telefone (com DDD 69) e CEP (8 dígitos).
- **RF12 - Painel Administrativo de Gestão:** O administrador deve poder alterar status de pedidos, emitir laudos e gerenciar estoque.

### 2.2 Requisitos Não Funcionais (RNF)
- **RNF01 - Segurança da Informação:** Armazenamento seguro de credenciais com hash Bcrypt, proteção contra injeção de SQL via PDO Prepared Statements e sanitização contra Cross-Site Scripting (XSS).
- **RNF02 - Desempenho e Tempo de Resposta:** Carregamento inicial de páginas em menos de 1,5 segundo em conexões 4G, otimizado por ilustrações SVG leves e código assíncrono.
- **RNF03 - Responsividade Mobile-First:** Interface gráfica totalmente adaptada para smartphones, tablets e desktops (resoluções de 320px a 4K).
- **RNF04 - Disponibilidade e Acesso Sem Instalação (Zero-Install):** O sistema deve estar publicado e operacional na web sem requerer a instalação de PHP, Apache ou SGBDs locais para uso imediato pelo usuário final.
- **RNF05 - Confiabilidade e Integridade Referencial:** Banco de dados relacional com integridade referencial ativa (`FOREIGN KEY`, `ON DELETE CASCADE`/`RESTRICT`).
- **RNF06 - Compatibilidade:** Suporte completo aos navegadores modernos (Google Chrome, Mozilla Firefox, Microsoft Edge e Safari).

---

## 3. Instalação e Execução (Seção Obrigatória da Apostila)

Esta seção documenta com precisão técnica todos os parâmetros necessários para que o professor ou qualquer avaliador clone, instale e coloque o sistema para rodar localmente do zero, ou utilize a versão publicada online sem qualquer instalação.

### 3.1 Ambiente
- **Linguagem e Versão:** PHP 8.2 ou superior (módulos recomendados: `pdo`, `pdo_sqlite`, `pdo_mysql`, `curl`, `mbstring`, `json`);
- **Servidor ou Ambiente Local:** Servidor embutido do interpretador PHP (`php -S 127.0.0.1:8000`) ou servidor Apache via XAMPP (`C:\xampp\htdocs\amora_artesanal`);
- **Bibliotecas e Dependências:** **Nenhuma dependência externa via composer ou npm é necessária.** O sistema foi projetado de forma autossuficiente utilizando exclusivamente recursos nativos do PHP 8.2+ e Vanilla JavaScript (ES6+), dispensando comandos de compilação ou instalação de pacotes como `composer install` ou `npm install`. As bibliotecas de front-end (Leaflet 1.9.4 e FontAwesome 6) são carregadas localmente de `assets/js/` e `assets/css/` ou via CDNs públicas com fallback local;
- **Sistemas Operacionais Testados e Homologados:**
  - Windows 11 Pro 64-bit (PowerShell 7 e Prompt de Comando cmd.exe);
  - Linux Ubuntu 22.04 LTS / Debian 12 (Bash).

### 3.2 Banco de Dados
- **SGBD e Versão:** SQLite 3 (versão 3.37+ autossuficiente) ou MySQL 8.0+ / MariaDB 10.4+;
- **Nome do Banco de Dados a ser Criado:** `amora_artesanal` (ou o arquivo `database/amora_artesanal.sqlite`);
- **Caminho do Script SQL no Repositório:** `database/schema.sql`;
- **Como Executá-lo:**
  - *Opção A (SQLite Automático - Recomendada):* Execute no terminal: `php -r "require 'database/init_db.php'; inicializarBanco();"`
  - *Opção B (MySQL via Terminal):* Execute: `mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS amora_artesanal;" && mysql -u root -p amora_artesanal < database/schema.sql`
  - *Opção C (phpMyAdmin do XAMPP):* Abra `http://localhost/phpmyadmin`, crie o banco `amora_artesanal`, clique na aba *Importar* e selecione o arquivo `database/schema.sql`.

#### Lista de Todas as Tabelas do Sistema (Modelo Oficial da Apostila):

| Tabela | Campos principais | Chave primária | Relacionamentos (Chaves Estrangeiras) |
| :--- | :--- | :--- | :--- |
| **`categorias`** | `id, nome_categoria` | `id` | — |
| **`usuarios`** | `id, nome, email, senha_hash, tipo_usuario, data_nascimento, telefone, cep, logradouro, numero, complemento, bairro, cidade, estado, data_cadastro` | `id` | — |
| **`produtos`** | `id, categoria_id, nome, descricao, preco, estoque, imagem_url` | `id` | `categoria_id` → `categorias.id` |
| **`pedidos`** | `id, usuario_id, data_pedido, valor_total, status, endereco_entrega, forma_pagamento, codigo_rastreio, transportadora, modalidade_transporte, prazo_estimado_dias, reembolso_solicitado, protocolo_reembolso` | `id` | `usuario_id` → `usuarios.id` |
| **`itens_pedido`** | `id, pedido_id, produto_id, quantidade, preco_unitario, personalizacao` | `id` | `pedido_id` → `pedidos.id`, `produto_id` → `produtos.id` |
| **`mensagens_contato`** | `id, nome, email, telefone, mensagem, data_envio, lida` | `id` | — |
| **`avaliacoes`** | `id, produto_id, usuario_id, nome_cliente, estrelas, comentario, indicaria_amigo, data_avaliacao` | `id` | `produto_id` → `produtos.id`, `usuario_id` → `usuarios.id` |
| **`mensagens_chat`** | `id, usuario_id, remetente, mensagem, data_envio, lida` | `id` | `usuario_id` → `usuarios.id` |
| **`auditoria_reembolsos`**| `id, pedido_id, protocolo_reembolso, laudo_transportadora, codigo_ocorrencia_transp, status_transportadora, parecer_admin, status_admin, hash_liquidacao_pix, valor_estorno, chave_pix_cliente, data_solicitacao` | `id` | `pedido_id` → `pedidos.id` |
| **`rastreamento_logistica`**| `id, pedido_id, modalidade, latitude, longitude, altitude_metros, velocidade_kmh, status_deslocamento, data_registro` | `id` | `pedido_id` → `pedidos.id` |

- **Dados Iniciais (Seed Data):** O arquivo `database/schema.sql` já insere automaticamente todas as 4 categorias (Velas Aromáticas, Sabonetes Artesanais, Amigurumis, Kits Presente), 11 produtos artesanais com preços de mercado e imagens vetorizadas, 4 usuários de teste pré-configurados com senhas criptografadas em Bcrypt (`senha123`) e avaliações iniciais com métrica NPS.

### 3.3 Variáveis de Ambiente
O repositório inclui o arquivo `.env.example` versionado com valores de modelo. O arquivo `.env` com configurações locais é protegido pelo `.gitignore`.

Conteúdo do `.env.example`:
```ini
# .env.example
DB_HOST=localhost
DB_NAME=amora_artesanal
DB_USER=root
DB_PASS=

# Configurações Adicionais da Aplicação
APP_NAME="Amora Artesanal"
APP_ENV=development
APP_URL=http://localhost:8000
APP_PUBLIC_URL=https://lar-bc.github.io/amora_artesanal
APP_DEBUG=true

# Banco de Dados (Padrão autossuficiente: SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=database/amora_artesanal.sqlite

# APIs e Serviços Externos
VIACEP_API_URL=https://viacep.com.br/ws
OPENSTREETMAP_TILE_URL=https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png

# Políticas de Logística e Segurança
LOGISTICA_TRANSPORTADORA="LogiAmora Transportes / Sedex"
LOGISTICA_DRONE_ATIVO=true
LOGISTICA_RODOVIA_BR364_ATIVO=true
SISTEMA_AUDITORIA_REEMBOLSO_DUPLO=true
SISTEMA_VALIDACAO_DOMINIO_EMAIL=true
```

**Explicação de Cada Variável:**
- `DB_HOST`: Endereço do servidor MySQL (padrão: `localhost` ou `127.0.0.1`);
- `DB_NAME`: Nome do banco de dados relacional (`amora_artesanal`);
- `DB_USER`: Usuário do banco (padrão XAMPP: `root`);
- `DB_PASS`: Senha do banco (padrão XAMPP: vazia);
- `APP_URL`: Endereço da aplicação no ambiente local (`http://localhost:8000`);
- `APP_PUBLIC_URL`: URL oficial pública publicada no ar (`https://lar-bc.github.io/amora_artesanal`);
- `DB_CONNECTION`: Driver de conexão PDO (`sqlite` para autossuficiência imediata ou `mysql`);
- `DB_DATABASE`: Caminho para o arquivo SQLite (`database/amora_artesanal.sqlite`);
- `VIACEP_API_URL`: Ponto de extremidade da API pública dos Correios para validação de endereços reais;
- `LOGISTICA_TRANSPORTADORA`: Nome da transportadora parceira integrada (`LogiAmora Transportes / Sedex`);
- `LOGISTICA_DRONE_ATIVO`: Flag booleana que habilita a telemetria com drones na malha urbana de Porto Velho;
- `SISTEMA_AUDITORIA_REEMBOLSO_DUPLO`: Flag que ativa a exigência de laudo duplo (transportadora + lojista) para liquidação de devoluções.

### 3.4 Passo a Passo de Instalação (6 Comandos Exatos)
Para clonar, configurar e rodar o sistema localmente em qualquer computador limpo, execute os 6 passos numerados abaixo:

1. **Clonar o repositório:**
   ```bash
   git clone https://github.com/lar-bc/amora_artesanal.git
   ```

2. **Entrar no diretório do projeto e verificar dependências:**
   ```bash
   cd amora_artesanal
   ```
   *(Nota: Nenhuma dependência externa via composer ou npm é necessária; o sistema roda em PHP 8.2 nativo com PDO e Vanilla JS).*

3. **Criar o banco de dados e importar `database/schema.sql`:**
   ```bash
   php -r "require 'database/init_db.php'; inicializarBanco();"
   ```
   *(Caso prefira utilizar MySQL: `mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS amora_artesanal;" && mysql -u root -p amora_artesanal < database/schema.sql`)*

4. **Copiar o arquivo `.env.example` para `.env`:**
   ```bash
   copy .env.example .env
   ```
   *(No Linux: `cp .env.example .env`)*

5. **Iniciar o servidor de desenvolvimento embutido do PHP:**
   ```bash
   php -S 127.0.0.1:8000
   ```
   *(Ou caso utilize XAMPP, copie a pasta para `C:\xampp\htdocs\amora_artesanal` e inicie o Apache no painel).*

6. **Abrir no navegador web o endereço exato:**
   - **Ambiente Local:** [http://localhost:8000](http://localhost:8000)
   - **Acesso Online Imediato (Sem Instalar Nada):** [https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal)

---

### 3.5 Como Testar

#### Credenciais de Teste Pré-Configuradas (Senha Padrão: `senha123`)
O banco de dados já possui perfis de teste pré-configurados com senhas Bcrypt válidas:

| Perfil | E-mail de Teste | Senha Padrão | Responsabilidade no Sistema |
| :--- | :--- | :--- | :--- |
| **Administradora Geral** | `admin@amora.com` | `senha123` | Painel de controle, estoque e homologação de estornos |
| **Cliente Consumidora** | `mariana.vasconcelos@amora.com.br` | `senha123` | Catálogo, carrinho, checkout ViaCEP e rastreamento |
| **Supervisor de Logística** | `logistica@amora.com` | `senha123` | Emissão de laudo pericial da transportadora LogiAmora |
| **Auditor Financeiro** | `auditoria@amora.com` | `senha123` | Validação de estornos e conformidade bancária Pix |

*(Na tela de login, há também botões de atalho para entrar ou alternar com 1 clique em qualquer um dos perfis).*

#### Roteiro de Teste do Fluxo Principal (8 Etapas em Ordem)
1. **Navegação e Catálogo:** Acesse a página inicial ou a aba *Produtos* e filtre pelas categorias (Velas, Sabonetes, Amigurumis, Kits);
2. **Personalização e Carrinho:** Escolha a *Vela Aromática Flor de Cerejeira*, adicione personalização ("Para presentear com carinho") e envie para a sacola de compras;
3. **Validação de Endereço Real no Checkout:** No carrinho, clique em *Finalizar Compra*. No campo CEP, digite `76820-441` (CEP real do IFRO Calama). Verifique que o sistema consome a API dos Correios e preenche automaticamente rua, bairro, cidade e estado. Tente digitar um CEP inexistente (`00000-000`) e observe o bloqueio ativo de segurança;
4. **Escolha de Transporte e Pagamento Pix:** Selecione a entrega expressa por **Drone** ou **Rodovia BR-364** e finalize o pedido simulando a geração de chave Pix;
5. **Rastreamento em Tempo Real:** Na tela de pedidos, clique em *Rastrear no Mapa*. Visualize a telemetria com mapa Leaflet, exibindo a trajetória com velocidade instantânea e animação de hélices do drone na malha urbana de Porto Velho;
6. **Solicitação de Reembolso:** No pedido entregue ou em trânsito, clique em *Solicitar Reembolso*, informe a chave Pix e descreva a avaria;
7. **Auditoria Bilateral de Estorno:** Observe a exigência dos dois laudos: o laudo da transportadora LogiAmora e a homologação do administrador do ateliê. Quando ambos são confirmados, o hash criptográfico do Pix de estorno é emitido;
8. **Avaliação NPS:** Retorne à página do produto e envie uma avaliação com 5 estrelas e recomendação positiva para amigos.

#### Limitações Conhecidas
- A API dos Correios (ViaCEP) depende de conectividade com a internet para preenchimento automático de CEPs; em caso de ausência de rede, os campos podem ser preenchidos manualmente pelo usuário;
- A movimentação do drone e furgão rodoviário no mapa Leaflet utiliza simulação de waypoints e telemetria baseada em rotas georreferenciadas de Porto Velho e da BR-364;
- A versão hospedada no GitHub Pages utiliza armazenamento no `LocalStorage` do navegador para manter o estado dos pedidos e das credenciais simuladas, garantindo que o avaliador teste todas as operações sem precisar de back-end ativo.

---

## 4. Requisitos Técnicos Mínimos e Checklist de Segurança (Anexo B da Apostila)

O sistema foi rigorosamente conferido contra as exigências técnicas e diretrizes de segurança da apostila:

- [x] **Acesso Público sem Instalação:** O sistema abre e funciona pelo link oficial [https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal), sem exigir instalação de softwares por parte do avaliador;
- [x] **Execução Local Conforme Spec:** O sistema roda perfeitamente em localhost seguindo apenas os 6 passos da Seção 3.4;
- [x] **Fluxo Completo de Cadastro e Consulta:** Cadastro de clientes, gravação de pedidos e consulta de rastreamento com persistência;
- [x] **Autenticação Multi-Perfil:** Login funcional diferenciando cliente, administrador, operador logístico e auditor financeiro;
- [x] **Proteção de Credenciais:** Nenhuma credencial de banco ou senha exposta no código-fonte do repositório; `.env` ignorado pelo `.gitignore` e `.env.example` versionado;
- [x] **Criptografia Bcrypt (RNF01):** Senhas armazenadas com hash forte gerado por `password_hash($senha, PASSWORD_BCRYPT)` e verificadas com `password_verify()`;
- [x] **Prevenção contra SQL Injection:** 100% das consultas ao banco de dados utilizam Prepared Statements do PDO com binding de parâmetros;
- [x] **Prevenção contra XSS:** Todas as saídas dinâmicas no HTML são filtradas e sanitizadas com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`;
- [x] **Prevenção contra Session Fixation:** Regeneração de ID de sessão com `session_regenerate_id(true)` no momento da autenticação.
