# Amora Artesanal 🌸

> **Vitrine Virtual & E-commerce de Presentes e Aromas Feitos à Mão**  
> Desenvolvido com apoio de Inteligência Artificial Responsável (Google Antigravity)  
> **Autora:** Larissa Benício da Cruz  
> **Turma:** 3º Ano B Informática  
> **Instituição:** Instituto Federal de Rondônia (IFRO) - Campus Porto Velho Calama  
> **Disciplina:** Desenvolvimento de Sistemas  
> **Professor Orientador:** Prof. Rafael Pitwak  
> **Repositório GitHub:** [https://github.com/lar-bc/amora_artesanal](https://github.com/lar-bc/amora_artesanal)  

---

## 🌐 ACESSE O SITE ONLINE AGORA (SEM INSTALAR NADA)

Você pode abrir o site funcionando diretamente pelo navegador no celular ou computador:

### 👉 **[https://lar-bc.github.io/amora_artesanal](https://lar-bc.github.io/amora_artesanal)**

---

## 📚 Documentos Oficiais do Projeto no Repositório

- 📄 **[Especificação Formal Completa (spec.md)](spec.md):** Documento detalhado com arquitetura, casos de uso, diagrama relacional de 10 tabelas e a **Seção 7 (Instalação e Execução)** com comandos exatos e roteiro de testes.
- 📝 **[Relato de Experiência ABNT (RELATO_DE_EXPERIENCIA.md)](RELATO_DE_EXPERIENCIA.md):** Artigo acadêmico completo com 7 seções cobrindo introdução, metodologia, desafios enfrentados, análise crítica e referências bibliográficas.
- 📜 **[Registro de Revisão Humana (CHANGELOG.md)](CHANGELOG.md):** Histórico documentado de versões (v1.0.0 a v1.4.0) comprovando supervisão humana crítica sobre a IA.
- 🗄️ **[Esquema Relacional do Banco (database/schema.sql)](database/schema.sql):** Script SQL autossuficiente com 10 tabelas relacionais, chaves estrangeiras e dados iniciais de seed.
- ⚙️ **[Exemplo de Variáveis de Ambiente (.env.example)](.env.example):** Modelo de configuração para desenvolvimento e produção.

---

## 📖 Sobre o Projeto

O **Amora Artesanal** é uma plataforma completa de comércio eletrônico concebida para comercialização e personalização de itens aromáticos e artesanais:
- **Velas Aromáticas** (cera vegetal de coco, pavio 100% algodão e essências premium);
- **Sabonetes Botânicos** (saponificação a frio com argilas e extratos medicinais);
- **Amigurumis** (peças de crochê feitas ponto a ponto com fio antialérgico);
- **Kits Presente** (combinações presenteáveis em caixas artesanais).

---

## 🎯 Diferenciais Técnicos e Inovações Implementadas

1. **Validação Geográfica Real (ViaCEP):**
   - Consulta em tempo real aos Correios para autocompletar endereços e **bloquear ativamente CEPs fictícios**.
   - Endereço real homologado para testes em Porto Velho: CEP `76820-441` (IFRO Campus Porto Velho Calama).
2. **Rastreamento Logístico Multi-Modal (Leaflet.js + OpenStreetMap):**
   - **Curta Duração (Urbano / Porto Velho):** Drone Autônomo LogiAmora SkyDelivery a 45 km/h com animação de hélices giratórias.
   - **Longa Duração (Interestadual / Interior):** Furgão Rodoviário trafegando pela Rodovia BR-364 a 75 km/h com telemetria.
3. **Protocolo Bilateral de Auditoria de Reembolso:**
   - Prevenção contra estornos fraudulentos: exige laudo pericial da transportadora (`LogiAmora Transportes / Sedex`) com código de ocorrência e parecer homologado do Administrador com hash Pix.
4. **Arquitetura Desacoplada Zero-Install:**
   - Suíte completa em PHP 8.2 + SQLite/MySQL para execução em servidores locais;
   - Camada paralela estática em HTML5/JavaScript moderno para deploy imediato no GitHub Pages.

---

## 🛠️ Stack Tecnológica

- **Front-end:** HTML5 semântico, CSS3 responsivo, JavaScript ES6+, Leaflet 1.9.4.
- **Back-end:** PHP 8.2+ com PDO (suporte transparente a SQLite 3 e MySQL 8.0+).
- **Banco de Dados Relacional:** 10 tabelas com integridade referencial estrita (`schema.sql`).
- **APIs Integradas:** ViaCEP (Correios) e OpenStreetMap.
- **Hospedagem & CI/CD:** GitHub Pages com GitHub Actions e Render/Docker.

---

## 🚀 Como Executar Localmente (6 Comandos Exatos)

```bash
# 1. Clonar o repositório
git clone https://github.com/lar-bc/amora_artesanal.git

# 2. Acessar o diretório
cd amora_artesanal

# 3. Criar arquivo de configuração .env
copy .env.example .env

# 4. Inicializar banco de dados e aplicar seeds
php -r "require 'database/init_db.php'; inicializarBanco();"

# 5. Iniciar servidor web embutido
php -S 127.0.0.1:8000

# 6. Abrir no navegador
# http://localhost:8000
```

---

## 🔑 Credenciais de Teste Pré-Configuradas (Senha Padrão: `senha123`)

| Papel no Sistema | E-mail de Acesso | Senha Padrão | Funcionalidades de Teste |
| :--- | :--- | :--- | :--- |
| **Administradora Geral** | `admin@amora.com` | `senha123` | Painel de controle, estoque e homologação de estornos |
| **Cliente Consumidora** | `mariana.vasconcelos@amora.com.br` | `senha123` | Catálogo, carrinho, checkout ViaCEP e rastreamento |
| **Supervisor de Logística** | `logistica@amora.com` | `senha123` | Emissão de laudo pericial de transporte LogiAmora |
| **Auditor Financeiro** | `auditoria@amora.com` | `senha123` | Conferência de conformidade e liquidação Pix |

*(Na tela de login, há atalhos de 1 clique para entrar rapidamente em qualquer perfil).*
