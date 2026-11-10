# Amora Artesanal - Especificação Formal de Requisitos e Arquitetura de Software (spec.md)

**Projeto Integrador de Desenvolvimento de Sistemas**  
**Instituição:** Instituto Federal de Rondônia (IFRO) — Campus Porto Velho Calama  
**Curso:** Curso Técnico Integrado ao Ensino Médio em Informática  
**Componente Curricular:** Desenvolvimento de Sistemas  
**Professor Orientador:** Prof. Rafael Pitwak  
**Autor(a):** Larissa Benício da Cruz (3º Ano B Informática)  
**Data:** 10 de Novembro de 2026  
**Repositório Oficial:** [https://github.com/lar-bc/amora_artesanal](https://github.com/lar-bc/amora_artesanal)  
**Sistema Publicado no Ar (Acesso Imediato sem Instalação):** [https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal)

---

## 1. Visão Geral e Propósito do Sistema

O **Amora Artesanal** é uma plataforma completa de vitrine virtual e comércio eletrônico desenvolvida para apoiar a comercialização, personalização e gestão logística de produtos aromáticos e peças artesanais afetivas (velas aromáticas em cera vegetal, sabonetes botânicos saponificados a frio, amigurumis tecidos à mão em crochê e kits de presentes temáticos).

O sistema foi concebido para atender às demandas de pequenos produtores de artesanato regional no estado de Rondônia, proporcionando uma experiência de compra segura, transparente e acolhedora, com rastreamento logístico inovador em tempo real e protocolo rigoroso de auditoria financeira.

### 1.1 Objetivos de Negócio
- Disponibilizar catálogo digital responsivo com fotos vetorizadas (SVG) e descrições detalhadas;
- Garantir a validação estrita de endereços reais através da integração com o web service dos Correios (ViaCEP);
- Proporcionar telemetria e rastreamento em mapa interativo (Leaflet), diferenciando rotas urbanas ultrarrápidas por drones autônomos e rotas interestaduais pela rodovia BR-364;
- Assegurar conformidade contábil e prevenção a fraudes em devoluções por meio de auditoria bilateral de estornos financeiros (validação conjunta entre transportadora e administração);
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
  - *Curta Duração / Perímetro Urbano (Porto Velho):* Drone Autônomo LogiAmora SkyDelivery com velocidade constante de 45 km/h e altitude de 45 m.
  - *Longa Duração / Interestadual e Interior:* Furgão Utilitário Rodoviário trafegando pela BR-364 a 75 km/h.
- **RF07 - Protocolo de Auditoria Bilateral de Reembolso:** Solicitações de estorno exigem aprovação conjunta com geração de laudo técnico de recusa/avaria pela transportadora (`LogiAmora Transportes / Sedex`) e parecer homologado do Administrador antes da emissão do comprovante de devolução Pix.
- **RF08 - Central de Atendimento e Chat:** O cliente deve poder enviar mensagens diretas ao ateliê e visualizar histórico de conversas.
- **RF09 - Avaliações de Produtos e Métrica NPS:** Clientes podem avaliar produtos com estrelas (1 a 5), comentários textuais e resposta objetiva à pergunta de recomendação ("Você indicaria a um amigo?").
- **RF10 - Autenticação Segura com Perfis de Usuário:** O sistema deve diferenciar perfis de acesso (`cliente`, `admin`, `logistica`, `auditor`), utilizando criptografia de senhas com algoritmo Bcrypt.
- **RF11 - Sanitização e Validação de Entradas:** Bloqueio de e-mails com domínios inválidos (exigindo formatos válidos como `@gmail.com`), validação de CPF (11 dígitos) e telefone com DDD 69.
- **RF12 - Painel Administrativo de Gestão:** O administrador deve poder alterar status de pedidos, emitir laudos e gerenciar estoque.

### 2.2 Requisitos Não Funcionais (RNF)
- **RNF01 - Segurança da Informação:** Armazenamento seguro de credenciais com hash Bcrypt, proteção contra injeção de SQL via PDO Prepared Statements e sanitização contra Cross-Site Scripting (XSS).
- **RNF02 - Desempenho e Tempo de Resposta:** Carregamento inicial de páginas em menos de 1,5 segundo em conexões 4G, otimizado por ilustrações SVG leves e código assíncrono.
- **RNF03 - Responsividade Mobile-First:** Interface gráfica totalmente adaptada para smartphones, tablets e desktops (resoluções de 320px a 4K).
- **RNF04 - Disponibilidade e Acesso Sem Instalação (Zero-Install):** O sistema deve estar publicado e operacional na web sem requerer a instalação de PHP, Apache ou SGBDs locais para uso imediato pelo usuário final.
- **RNF05 - Confiabilidade e Integridade Referencial:** Banco de dados relacional com integridade referencial ativa (`FOREIGN KEY`, `ON DELETE CASCADE`/`RESTRICT`).
- **RNF06 - Compatibilidade:** Suporte completo aos navegadores modernos (Google Chrome, Mozilla Firefox, Microsoft Edge e Safari).

---

## 3. Arquitetura do Sistema e Modelo de Dados

### 3.1 Arquitetura Dual Desacoplada
O sistema implementa uma arquitetura híbrida projetada para máxima portabilidade:
1. **Camada Back-end Completa (PHP 8.2 + PDO):** Arquitetura cliente-servidor para execução em servidores Apache/XAMPP ou Docker, com persistência em banco relacional SQLite 3 ou MySQL.
2. **Camada Front-end Desacoplada (HTML5 / ES6+ / LocalStorage):** Espelhamento estático integral das rotas e regras de negócio para hospedagem estática no GitHub Pages, garantindo que qualquer professor, avaliador ou cliente navegue e teste 100% dos fluxos sem pré-requisitos de runtime.

### 3.2 Diagrama e Estrutura do Banco de Dados Relacional
O banco de dados relacional é estruturado em **10 tabelas** interdependentes:

```
[categorias] (1) <---> (N) [produtos]
                             | (1)
                             |
                             v (N)
[usuarios] (1) <---> (N) [pedidos] (1) <---> (N) [itens_pedido]
   |    |                    |
   |    |                    +---> (1) [auditoria_reembolsos]
   |    |                    +---> (N) [rastreamento_logistica]
   |    +---> (N) [mensagens_chat]
   +--------> (N) [avaliacoes] (N) <---> (1) [produtos]

[mensagens_contato] (Tabela independente para leads)
```

#### Definição das 10 Tabelas Relacionais:
1. **`categorias`**: Identificador e nome da categoria dos produtos artesanais.
2. **`usuarios`**: Cadastro completo (nome, e-mail único, senha com hash Bcrypt, perfil, nascimento, telefone, CEP, logradouro, bairro, cidade, estado).
3. **`produtos`**: Nome, descrição poética, preço unitário real, estoque disponível, URL da imagem e chave estrangeira para `categorias`.
4. **`pedidos`**: Registro do pedido, chave estrangeira para `usuarios`, valor total, modalidade de transporte (`drone` ou `rodovia`), código de rastreio e status.
5. **`itens_pedido`**: Associação relacional entre `pedidos` e `produtos`, contendo quantidade, preço congelado e texto de personalização artesanal.
6. **`mensagens_contato`**: Formulário de orçamento sob medida, mensagens de contato e dúvidas.
7. **`avaliacoes`**: Avaliações de clientes associadas a `produtos` e `usuarios`, estrelas (1 a 5), resenha e pergunta NPS ("indicaria_amigo").
8. **`mensagens_chat`**: Diálogos em tempo real entre cliente e lojista com chave estrangeira para `usuarios`.
9. **`auditoria_reembolsos`**: Registro bilateral de auditoria de estornos com chaves para `pedidos`, protocolo, laudo técnico da transportadora, código de ocorrência, parecer da administração e hash de liquidação Pix.
10. **`rastreamento_logistica`**: Telemetria em tempo real das encomendas com chave para `pedidos`, modalidade (`drone`/`rodovia`), latitude, longitude, altitude em metros, velocidade instantânea em km/h e status de deslocamento.

---

## 4. Regras de Negócio e Governança

### RN01 - Validação de Endereço Real (ViaCEP)
- O preenchimento do CEP dispara uma requisição assíncrona HTTP GET para `https://viacep.com.br/ws/{cep}/json/`.
- Se o retorno contiver `{ "erro": true }` ou a requisição falhar por formato inválido, o sistema bloqueia a finalização da compra e exibe notificação em vermelho.
- Endereço de teste verificado e homologado em Rondônia: **CEP 76820-441** (Av. Calama, 4985 - IFRO Campus Porto Velho Calama).

### RN02 - Rastreamento Logístico Multi-Modal
- **Drone Autônomo LogiAmora SkyDelivery:** Ativado para entregas dentro da cidade de Porto Velho (distâncias de curta duração). Velocidade: 45 km/h, altitude: 45 m. Ícone animado em tempo real com hélices giratórias.
- **Furgão Rodoviário LogiAmora Sedex:** Ativado para entregas de longa distância ou intermunicipais (via Rodovia BR-364). Velocidade média: 75 km/h, altitude: nível do solo.

### RN03 - Protocolo de Dupla Auditoria para Devoluções e Reembolsos
- Nenhuma devolução é liquidada unilateralmente ou de forma automática.
- Para haver estorno do valor pago via Pix, o fluxo exige:
  1. Laudo Pericial de Avaria ou Recusa emitido pela transportadora parceira (`LogiAmora Transportes / Sedex`) com código de ocorrência auditável;
  2. Homologação formal pelo Administrador com validação do motivo e assinatura digital via Hash Pix de liquidação bancária.

### RN04 - Sanitização e Blindagem de Dados
- Campos de e-mail devem passar por verificação de integridade de formato e domínio (`@gmail.com` ou provedores corporativos reconhecidos).
- Números de telefone exigem máscara brasileira e DDD válido (padrão Rondônia: `(69) 9XXXX-XXXX`).
- CPF deve conter estritamente 11 dígitos numéricos com sanitização contra caracteres maliciosos.

---

## 5. Mapeamento de Telas e Rotas

| Funcionalidade | Rota Back-end (PHP) | Rota Web Estática (GitHub Pages) | Descrição |
| :--- | :--- | :--- | :--- |
| **Página Inicial** | `/index.php` | `index.html` | Vitrine de destaques, banners e depoimentos |
| **Catálogo** | `/produtos.php` | `produtos.html` | Listagem filtrável por categoria e busca |
| **Carrinho** | `/carrinho.php` | `carrinho.html` | Gestão de itens e cálculo de subtotal |
| **Checkout** | `/checkout.php` | `checkout.html` | Consulta ViaCEP, escolha de frete e Pix |
| **Rastreamento** | `/pedidos.php` | `pedidos.html` | Mapa Leaflet com Drones e Furgão BR-364 |
| **Login** | `/login.php` | `login.html` | Autenticação com perfis de teste rápidos |
| **Cadastro** | `/cadastro.php` | `cadastro.html` | Criação de conta com validação completa |
| **Contato** | `/contato.php` | `contato.html` | Formulário para pedidos personalizados |
| **Painel Admin** | `/admin/pedidos.php`| `pedidos.html#admin` | Auditoria de estornos e controle de pedidos |

---

## 6. Casos de Uso Principais

### UC01 - Realizar Compra com Validação de Endereço Real
- **Ator:** Cliente
- **Fluxo Principal:**
  1. O cliente adiciona produtos artesanais ao carrinho;
  2. Acessa a tela de checkout e digita o seu CEP (ex.: `76820-441`);
  3. O sistema valida o CEP junto ao ViaCEP e auto-preenche o endereço oficial do IFRO Calama;
  4. O cliente seleciona a modalidade de entrega (Drone ou Rodovia);
  5. O pedido é gerado com código de acompanhamento único e chave Pix.

### UC02 - Acompanhar Rastreamento em Tempo Real
- **Ator:** Cliente
- **Fluxo Principal:**
  1. O cliente acessa a aba "Meus Pedidos";
  2. Clica em "Rastrear Encomenda";
  3. O sistema renderiza o mapa interativo Leaflet centralizado em Porto Velho;
  4. O marcador correspondente ao modal (Drone com hélices giratórias ou Furgão na BR-364) desloca-se dinamicamente pela malha de ruas com telemetria em tempo real.

### UC03 - Solicitar e Auditar Reembolso Bilateral
- **Atores:** Cliente, Transportadora LogiAmora e Administrador do Ateliê
- **Fluxo Principal:**
  1. O cliente clica em "Solicitar Reembolso" informando o motivo e chave Pix;
  2. O status altera para "Reembolso Solicitado";
  3. A transportadora emite laudo de ocorrência (`OC-84920`) comprovando o sinistro ou extravio;
  4. O Administrador homologa o laudo e autoriza o estorno com hash bancário;
  5. O status do pedido altera para "Reembolsado" e o comprovante Pix é disponibilizado.

---

## 7. Instalação e Execução (Seção 7 da Apostila)

Esta seção documenta com precisão técnica todos os parâmetros necessários para homologação e execução do sistema, contemplando a execução local completa e a execução web imediata sem instalação.

### 3.1 Ambiente
- **Linguagem / Runtime Back-end:** PHP 8.2 ou superior (módulos recomendados: `pdo`, `pdo_sqlite`, `pdo_mysql`, `curl`, `mbstring`);
- **Servidor Web:** Servidor embutido do interpretador PHP (`php -S`) ou servidor Apache / XAMPP;
- **Tecnologias Front-end:** HTML5 semântico, CSS3 com variáveis personalizadas, JavaScript Moderno (ES6+), biblioteca Leaflet.js 1.9.4 e OpenStreetMap Tiles;
- **Sistemas Operacionais Testados:**
  - Windows 11 Pro 64-bit (PowerShell 7 e Prompt de Comando);
  - Linux Ubuntu 22.04 LTS / Debian 12;
- **Ambiente de Publicação sem Instalação:** Hospedagem direta no GitHub Pages através do repositório remoto, operando de forma 100% estática e desacoplada em qualquer navegador moderno (Chrome, Edge, Firefox, Safari).

### 3.2 Banco de Dados
- **SGBD Relacional Suportado:** SQLite 3 (padrão autossuficiente) ou MySQL 8.0+ / MariaDB 10.4+;
- **Nome do Banco:** `amora_artesanal` (ou arquivo `database/amora_artesanal.sqlite`);
- **Caminho do Script DDL e Seed:** `database/schema.sql`;
- **Estrutura:** O script `database/schema.sql` é totalmente autossuficiente e cria as **10 tabelas relacionais** do sistema com chaves primárias `AUTO_INCREMENT`, chaves estrangeiras com restrições `ON DELETE CASCADE` / `RESTRICT` e dados iniciais de teste (Seed Data).

### 3.3 Variáveis de Ambiente
O projeto conta com o arquivo de modelo `.env.example` versionado no repositório. O arquivo `.env` real contém as credenciais locais e é protegido pelo `.gitignore`.

Principais variáveis:
- `APP_NAME`: Nome identificador do sistema (`"Amora Artesanal"`);
- `APP_URL`: URL local para desenvolvimento (`http://localhost:8000`);
- `APP_PUBLIC_URL`: URL pública oficial no GitHub Pages (`https://lar-bc.github.io/amora_artesanal`);
- `DB_CONNECTION`: Driver do banco de dados relacional (`sqlite` ou `mysql`);
- `DB_DATABASE`: Caminho do arquivo SQLite (`database/amora_artesanal.sqlite`) ou nome do banco MySQL;
- `VIACEP_API_URL`: Ponto de extremidade da API dos Correios (`https://viacep.com.br/ws`);
- `LOGISTICA_DRONE_ATIVO`: Flag booleana habilitando telemetria de drones em Porto Velho (`true`);
- `SISTEMA_AUDITORIA_REEMBOLSO_DUPLO`: Flag ativando a exigência de laudo duplo de estorno (`true`).

### 3.4 Passo a Passo de Instalação (6 Comandos Exatos)
Para clonar, configurar e rodar o sistema localmente no terminal, execute os 6 comandos numerados abaixo:

```bash
# 1. Clonar o repositório oficial do GitHub
git clone https://github.com/lar-bc/amora_artesanal.git

# 2. Entrar no diretório raiz do projeto
cd amora_artesanal

# 3. Criar o arquivo de variáveis de ambiente a partir do exemplo
copy .env.example .env

# 4. Inicializar o banco de dados relacional e aplicar o esquema com seeds
php -r "require 'database/init_db.php'; inicializarBanco();"

# 5. Iniciar o servidor de desenvolvimento embutido do PHP
php -S 127.0.0.1:8000

# 6. Abrir a aplicação no navegador web
# Ambiente Local: http://localhost:8000
# OU Acesso Direto Online sem Instalar Nada: https://lar-bc.github.io/amora_artesanal
```

> **Nota sobre o Acesso Imediato:** Qualquer pessoa ou avaliador que não deseje instalar PHP, Composer, Apache ou SGBD localmente pode acessar diretamente o sistema publicado no link do GitHub Pages:  
> 👉 **[https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal)**

### 3.5 Como Testar

#### Credenciais de Teste Pré-Configuradas (Senha Padrão: `senha123`)
O banco de dados inicial já possui contas pré-configuradas para validação de todos os papéis de governança do sistema:

| Perfil | E-mail de Teste | Senha Padrão | Responsabilidade no Sistema |
| :--- | :--- | :--- | :--- |
| **Administradora Geral** | `admin@amora.com` | `senha123` | Gestão de pedidos, aprovação de estornos e estoque |
| **Cliente Consumidora** | `mariana.vasconcelos@amora.com.br` | `senha123` | Catálogo, carrinho, checkout e rastreamento |
| **Supervisor de Logística** | `logistica@amora.com` | `senha123` | Emissão de laudo pericial da transportadora |
| **Auditor Financeiro** | `auditoria@amora.com` | `senha123` | Validação de estornos e conformidade Pix |

*(Na tela de login, há também botões de atalho para entrar com 1 clique em qualquer um dos perfis).*

#### Roteiro de Teste em 8 Etapas do Fluxo Principal
1. **Navegação e Catálogo:** Acesse a página inicial ou a aba *Produtos* e filtre pelas categorias (Velas, Sabonetes, Amigurumis, Kits);
2. **Personalização e Carrinho:** Escolha a *Vela Aromática Flor de Cerejeira*, adicione personalização ("Para presentear com carinho") e envie para o carrinho;
3. **Validação de Endereço Real no Checkout:** No carrinho, clique em *Finalizar Compra*. No campo CEP, digite `76820-441` (CEP real do IFRO Calama). Verifique que o sistema consome a API dos Correios e preenche automaticamente rua, bairro, cidade e estado. Tente digitar um CEP inexistente (`00000-000`) e observe o bloqueio de segurança;
4. **Escolha de Transporte e Pagamento Pix:** Selecione a entrega expressa por **Drone** ou **Rodovia BR-364** e finalize o pedido simulando o pagamento Pix;
5. **Rastreamento em Tempo Real:** Na tela de pedidos, clique em *Rastrear no Mapa*. Visualize a telemetria com mapa Leaflet, exibindo a trajetória com velocidade instantânea e animação de hélices do drone em Porto Velho;
6. **Solicitação de Reembolso:** No pedido entregue ou em trânsito, clique em *Solicitar Reembolso*, informe a chave Pix e descreva a avaria;
7. **Auditoria Bilateral de Estorno:** Observe a exigência dos dois laudos: o laudo da transportadora LogiAmora e a homologação do administrador do ateliê. Quando ambos são confirmados, o hash criptográfico do Pix de estorno é emitido;
8. **Avaliação NPS:** Retorne à página do produto e envie uma avaliação com 5 estrelas e recomendação positiva para amigos.

#### Limitações Conhecidas
- A API dos Correios (ViaCEP) depende de conectividade com a internet para preenchimento automático de CEPs; em caso de ausência de rede, os campos podem ser preenchidos manualmente pelo usuário;
- A movimentação do drone e furgão rodoviário no mapa Leaflet utiliza simulação de waypoints e telemetria baseada em rotas georreferenciadas de Porto Velho e da BR-364;
- A versão hospedada no GitHub Pages utiliza armazenamento no `LocalStorage` do navegador para manter o estado dos pedidos e das credenciais simuladas, garantindo que o avaliador teste todas as operações sem precisar de back-end ativo.
