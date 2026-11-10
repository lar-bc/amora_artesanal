/**
 * Amora Artesanal - Sistema de Loja & Carrinho para GitHub Pages
 * Suporta armazenamento no localStorage, contagem de itens, modal de produto e notificações
 */
(function() {
    'use strict';

    var STORAGE_KEY = 'amora_carrinho_items';

    window.AmoraStore = {
        // Obter itens do carrinho
        getCart: function() {
            try {
                var items = localStorage.getItem(STORAGE_KEY);
                return items ? JSON.parse(items) : [];
            } catch (e) {
                return [];
            }
        },

        // Salvar itens
        saveCart: function(cart) {
            try {
                localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
                this.updateCartBadge();
            } catch (e) {
                console.error('Erro ao salvar carrinho:', e);
            }
        },

        // Adicionar item ao carrinho
        addItem: function(produtoId, quantidade, personalizacao) {
            quantidade = parseInt(quantidade, 10) || 1;
            personalizacao = personalizacao || '';

            var cart = this.getCart();
            var produto = this.findProduct(produtoId);
            if (!produto) return;

            // Verificar se o item já existe (com mesma personalização)
            var index = cart.findIndex(function(it) {
                return it.id === produto.id && it.personalizacao === personalizacao;
            });

            if (index > -1) {
                cart[index].quantidade += quantidade;
            } else {
                cart.push({
                    id: produto.id,
                    nome: produto.nome,
                    preco: produto.preco,
                    imagem_url: produto.imagem_url,
                    categoria: produto.nome_categoria,
                    quantidade: quantidade,
                    personalizacao: personalizacao
                });
            }

            this.saveCart(cart);
            this.showToast('Adicionado com sucesso!', produto.nome + ' foi incluído na sua sacola.');
        },

        // Remover item
        removeItem: function(index) {
            var cart = this.getCart();
            if (index >= 0 && index < cart.length) {
                cart.splice(index, 1);
                this.saveCart(cart);
            }
        },

        // Atualizar quantidade
        updateQty: function(index, delta) {
            var cart = this.getCart();
            if (index >= 0 && index < cart.length) {
                cart[index].quantidade += delta;
                if (cart[index].quantidade <= 0) {
                    cart.splice(index, 1);
                }
                this.saveCart(cart);
            }
        },

        // Esvaziar carrinho
        clearCart: function() {
            localStorage.removeItem(STORAGE_KEY);
            this.updateCartBadge();
        },

        // Buscar produto na base
        findProduct: function(id) {
            if (!window.AMORA_DADOS || !window.AMORA_DADOS.produtos) return null;
            return window.AMORA_DADOS.produtos.find(function(p) { return p.id === parseInt(id, 10); }) || null;
        },

        // Total de itens
        getCartCount: function() {
            var cart = this.getCart();
            return cart.reduce(function(total, item) { return total + (item.quantidade || 1); }, 0);
        },

        // Total em Reais
        getCartTotal: function() {
            var cart = this.getCart();
            return cart.reduce(function(total, item) { return total + (item.preco * item.quantidade); }, 0);
        },

        // Formatar Moeda
        formatarMoeda: function(valor) {
            return 'R$ ' + parseFloat(valor).toFixed(2).replace('.', ',');
        },

        // Atualizar badge no header
        updateCartBadge: function() {
            var badges = document.querySelectorAll('.cart-badge');
            var count = this.getCartCount();
            badges.forEach(function(b) {
                b.textContent = count;
                b.style.display = count > 0 ? 'inline-flex' : 'none';
            });
        },

        // Notificação flutuante elegante
        showToast: function(titulo, mensagem) {
            var toast = document.getElementById('amora-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'amora-toast';
                toast.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#2b1812;color:#fff;padding:14px 20px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.25);z-index:99999;display:flex;align-items:center;gap:12px;font-family:inherit;font-size:0.9rem;border-left:5px solid #c86d7e;transition:opacity 0.3s, transform 0.3s;opacity:0;transform:translateY(15px);pointer-events:none;';
                document.body.appendChild(toast);
            }

            toast.innerHTML = '<div style="font-size:1.5rem;color:#f3b2bd;"><i class="fa-solid fa-circle-check"></i></div>' +
                              '<div><strong style="display:block;font-size:0.95rem;">' + titulo + '</strong><span style="color:#d4c4bc;font-size:0.82rem;">' + mensagem + '</span></div>';
            
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            clearTimeout(this._toastTimeout);
            this._toastTimeout = setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(15px)';
            }, 3500);
        },

        // Modal de detalhes do produto
        openProductModal: function(produtoId) {
            var produto = this.findProduct(produtoId);
            if (!produto) return;

            var modal = document.getElementById('amora-product-modal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'amora-product-modal';
                modal.className = 'amora-modal-overlay';
                modal.innerHTML = '<div class="amora-modal-box">' +
                    '<button type="button" class="amora-modal-close" onclick="window.AmoraStore.closeProductModal()">&times;</button>' +
                    '<div id="amora-modal-body"></div>' +
                '</div>';
                document.body.appendChild(modal);
            }

            var body = document.getElementById('amora-modal-body');
            body.innerHTML = 
                '<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:24px;align-items:center;">' +
                    '<div style="background:#fcf8f5;border-radius:12px;padding:20px;text-align:center;">' +
                        '<img src="' + produto.imagem_url + '" alt="' + produto.nome + '" style="max-width:100%;max-height:260px;object-fit:contain;">' +
                    '</div>' +
                    '<div>' +
                        '<span class="card-badge" style="position:static;display:inline-block;margin-bottom:8px;">' + produto.nome_categoria + '</span>' +
                        '<h3 style="font-size:1.4rem;color:#4a2f24;margin-bottom:8px;">' + produto.nome + '</h3>' +
                        '<p style="color:#8a6a59;font-size:0.9rem;line-height:1.6;margin-bottom:14px;">' + produto.descricao + '</p>' +
                        '<div style="font-size:1.6rem;font-weight:700;color:#c86d7e;margin-bottom:14px;">' + this.formatarMoeda(produto.preco) + '</div>' +
                        '<div style="margin-bottom:16px;">' +
                            '<label style="display:block;font-size:0.85rem;font-weight:600;color:#4a2f24;margin-bottom:4px;">Personalização do Pedido (opcional):</label>' +
                            '<input type="text" id="modal-personalizacao" class="form-control" placeholder="Ex: Nome na tag, fita rosa, sem aroma..." style="width:100%;padding:8px 12px;border:1px solid #e2d3cb;border-radius:8px;">' +
                        '</div>' +
                        '<div style="display:flex;gap:12px;align-items:center;">' +
                            '<button type="button" class="btn btn-primary btn-lg" onclick="window.AmoraStore.addToCartFromModal(' + produto.id + ')">' +
                                '<i class="fa-solid fa-basket-shopping"></i> Adicionar à Sacola' +
                            '</button>' +
                            '<a href="carrinho.html" class="btn btn-secondary btn-lg">' +
                                '<i class="fa-solid fa-bag-shopping"></i> Ver Sacola' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                '</div>';

            modal.style.display = 'flex';
        },

        closeProductModal: function() {
            var modal = document.getElementById('amora-product-modal');
            if (modal) modal.style.display = 'none';
        },

        addToCartFromModal: function(id) {
            var input = document.getElementById('modal-personalizacao');
            var pers = input ? input.value : '';
            this.addItem(id, 1, pers);
            this.closeProductModal();
        },

        // Gerenciamento de Usuário e Sessão Local (Sem precisar de servidor)
        getUser: function() {
            try {
                var user = localStorage.getItem('amora_usuario_logado');
                if (user) return JSON.parse(user);
                // Usuário padrão de demonstração para ninguém precisar instalar nada
                return {
                    id: 2,
                    codigoCliente: 'CLI-00002',
                    nome: 'Larissa Benício',
                    email: 'larissa.benicio@gmail.com',
                    telefone: '(69) 98412-3456',
                    endereco: 'Av. Tiradentes, nº 3000 (Apto 204)',
                    bairro: 'Embratel',
                    cidade: 'Porto Velho',
                    estado: 'RO',
                    cep: '76820-120'
                };
            } catch (e) {
                return null;
            }
        },

        setUser: function(user) {
            try {
                localStorage.setItem('amora_usuario_logado', JSON.stringify(user));
                this.updateUserHeader();
            } catch (e) {
                console.error(e);
            }
        },

        logout: function() {
            localStorage.removeItem('amora_usuario_logado');
            this.updateUserHeader();
            this.showToast('Sessão encerrada', 'Você saiu da sua conta.');
            setTimeout(function() { window.location.href = 'index.html'; }, 600);
        },

        updateUserHeader: function() {
            var areas = document.querySelectorAll('.user-nav-area');
            var user = this.getUser();

            areas.forEach(function(area) {
                if (user) {
                    var primeiroNome = user.nome.split(' ')[0];
                    area.innerHTML = 
                        '<div style="display:flex;align-items:center;gap:10px;font-size:0.85rem;color:var(--cor-marrom-suave);">' +
                            '<span><i class="fa-regular fa-circle-user" style="color:var(--cor-rosa-queimado);"></i> Olá, <strong>' + primeiroNome + '</strong></span>' +
                            '<a href="pedidos.html" class="btn btn-secondary btn-sm"><i class="fa-solid fa-truck-fast"></i> Rastrear</a>' +
                            '<button type="button" class="btn btn-outline btn-sm" onclick="window.AmoraStore.logout()" style="padding:4px 8px;font-size:0.75rem;" title="Sair"><i class="fa-solid fa-arrow-right-from-bracket"></i></button>' +
                        '</div>';
                } else {
                    area.innerHTML = 
                        '<div style="display:flex;align-items:center;gap:8px;">' +
                            '<a href="login.html" class="btn btn-secondary btn-sm"><i class="fa-solid fa-right-to-bracket"></i> Entrar</a>' +
                            '<a href="cadastro.html" class="btn btn-outline btn-sm"><i class="fa-solid fa-user-plus"></i> Cadastrar</a>' +
                        '</div>';
                }
            });
        }
    };

    // Inicialização ao carregar página
    document.addEventListener('DOMContentLoaded', function() {
        window.AmoraStore.updateCartBadge();
        window.AmoraStore.updateUserHeader();
    });
})();
