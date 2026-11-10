# Registro de Decisões Técnicas e Revisão Humana (CHANGELOG.md)

Todas as alterações e evoluções do sistema **Amora Artesanal** foram documentadas neste arquivo, comprovando a atuação crítica da desenvolvedora (Larissa Benício da Cruz) sobre as propostas automáticas geradas pela Inteligência Artificial (Google Antigravity), em conformidade com as exigências pedagógicas e normativas do IFRO Campus Porto Velho Calama.

---

## [v1.4.1] - 2026-11-10 (Hardening de Segurança e Senhas Individuais)
### Alterado
- **Remoção de Preenchimento Automático na Autenticação:** Eliminação de qualquer valor pré-preenchido de e-mail e senha nos formulários de login (`login.html` e `login.php`). Os campos agora iniciam completamente vazios, exigindo entrada manual e consciente pelo usuário.
- **Remoção do Painel de Acesso Rápido em 1 Clique:** Remoção definitiva de botões de atalho na tela de login por solicitação da desenvolvedora para evitar exposição de credenciais e garantir privacidade e integridade do controle de acesso.
- **Senhas Individuais Exclusivas por Perfil:** Definição de senhas únicas para cada perfil de teste (`admin`, `larissa`, `mariana`, `logistica`, `auditoria`), com hashes Bcrypt individuais gerados e atualizados no banco de dados e na especificação formal.

### Revisão Humana Crítica vs. IA
- *Proposta da IA:* A IA sugeriu manter botões de 1 clique e campos pré-preenchidos na tela de login para facilitar os testes rápidos.
- *Intervenção da Aluna:* A aluna rejeitou enfaticamente a proposta ("se tiver isso aqui vai acabar não tendo segurança no site, e tire também a senha e o e-mail quando for fazer o login; cada usuário tem uma senha individual e que não apareça na hora que eu for fazer o login"), forçando a aplicação estrita de boas práticas de segurança da informação e privacidade de dados.

---

## [v1.4.0] - 2026-11-10 (Versão de Entrega Oficial)
### Adicionado
- **Arquitetura Desacoplada Zero-Install (GitHub Pages):** Criação da camada de espelhamento estático em HTML5/JavaScript moderno (`index.html`, `produtos.html`, `pedidos.html`, `contato.html`, `carrinho.html`, `checkout.html`, `login.html`, `cadastro.html`), permitindo acesso universal imediato sem que nenhum usuário ou avaliador precise instalar PHP, Apache, MySQL ou extensões locais.
- **Workflow de Deploy Contínuo:** Configuração do `.github/workflows/pages.yml` e arquivo `.nojekyll` para automação de build e deploy no GitHub Pages (`https://lar-bc.github.io/amora_artesanal`).
- **Protocolo de Auditoria Bilateral de Reembolso:** Implementação de exigência rigorosa de validação cruzada para estornos financeiros via Pix. O estorno requer:
  1. Laudo pericial da transportadora LogiAmora com código de ocorrência auditável (ex.: `OC-84920`);
  2. Homologação formal do Administrador do Ateliê com geração de hash criptográfico de liquidação bancária.
- **Documentação de Engenharia e Especificação Formal:** Criação do `spec.md` completo e do `RELATO_DE_EXPERIENCIA.md` estruturado segundo normas acadêmicas da ABNT.

### Revisão Humana Crítica vs. IA
- *Proposta da IA:* A IA sugeriu inicialmente um botão de 'Solicitar Reembolso' que alterava imediatamente o status do pedido para 'Reembolsado' e simulava o envio do Pix sem qualquer checagem externa.
- *Intervenção da Aluna:* Rejeição imediata da proposta por violar regras de governança contábil. A aluna exigiu a criação do fluxo de dupla verificação ("Na parte do reembolso tem que haver uma verificação completa antes de devolver para o cliente o estorno do dinheiro, verifique com a transportadora, com o adm").

---

## [v1.3.0] - 2026-11-05
### Adicionado
- **Rastreamento em Tempo Real Multi-Modal (Leaflet.js + OpenStreetMap):**
  - **Curta Duração / Perímetro Urbano (Porto Velho):** Drone Autônomo LogiAmora SkyDelivery com altitude de 45 m, velocidade de 45 km/h e animação visual de hélices giratórias via CSS.
  - **Longa Duração / Interestadual e Interior:** Furgão Utilitário Rodoviário trafegando pela Rodovia BR-364 a 75 km/h com telemetria de trânsito em tempo real.
- **Painel de Telemetria e Despacho:** Exibição de coordenadas (latitude/longitude), velocidade instantânea, estimativa de chegada e identificador de despacho.

### Revisão Humana Crítica vs. IA
- *Proposta da IA:* A IA propôs apenas uma barra de progresso com texto estático "Em trânsito".
- *Intervenção da Aluna:* A aluna especificou que o rastreamento precisava se movimentar na malha viária real e diferenciar meios de transporte: drones para entregas urbanas rápidas e rodovia para entregas de longa distância.

---

## [v1.2.0] - 2026-10-28
### Adicionado
- **Validação Geográfica Real via API ViaCEP dos Correios:** Consulta assíncrona ao web service público (`https://viacep.com.br/ws/{cep}/json/`).
- **Bloqueio Ativo de Endereços Falsos:** Se o CEP não existir ou for rejeitado pela base nacional dos Correios, o sistema emite alerta vermelho impeditivo e desabilita o avanço do checkout.
- **Sanitização Rigorosa de Formulários:** Filtros contra injeção de scripts (XSS), exigência de formato de e-mail autêntico (`@gmail.com` ou domínios válidos), validação de máscara de telefone (DDD 69) e restrição de campos numéricos (CPF de 11 dígitos).

### Revisão Humana Crítica vs. IA
- *Proposta da IA:* A IA havia programado apenas uma expressão regular simples `^\d{8}$` que aceitava sequências fictícias como `00000000` ou `12345678`.
- *Intervenção da Aluna:* A aluna interveio enfaticamente: "Confirme se a localização existe mesmo na vida real, quero que você verifique, se não houver não aceite". Forçou a integração HTTP com a API dos Correios.

---

## [v1.1.0] - 2026-10-22
### Adicionado
- **Modelagem Relacional Completa (SQL):** Criação de 10 tabelas relacionais com chaves primárias `AUTO_INCREMENT`, chaves estrangeiras com restrições `ON DELETE CASCADE` / `RESTRICT`, índices e integridade referencial.
- **Script Autossuficiente de Inicialização:** Arquivo `database/schema.sql` com estrutura DDL e dados de seed para SQLite 3 e MySQL/MariaDB.
- **Camada de Acesso a Dados com PDO:** Abstração de banco no `config/database.php` com suporte a transações seguras e prepared statements contra SQL Injection.

### Revisão Humana Crítica vs. IA
- *Proposta da IA:* A IA sugeriu salvar dados apenas em arrays voláteis em memória ou arquivos JSON simples no diretório local.
- *Intervenção da Aluna:* A aluna exigiu arquitetura profissional com banco de dados relacional e script SQL reproduzível para avaliação formal no IFRO.

---

## [v1.0.0] - 2026-10-15
### Adicionado
- **Setup Inicial e Identidade Visual Afetiva:** Estruturação da paleta de cores floral da Amora Artesanal (rosa suave `#fcf8f8`, lavanda `#9c88ff`, dourado artesanal e marrom acolhedor).
- **Catálogo de Produtos Artesanais:** 11 itens reais com descrições detalhadas e preços de mercado (velas vegetais, sabonetes botânicos, amigurumis e kits presente).
- **Vetorização de Imagens:** Criação de acervo visual em SVG dedicado aos produtos do ateliê para garantir carregamento instantâneo e alta definição.
- **Controle de Versão:** Inicialização do repositório Git com convenção de Commits Semânticos.
