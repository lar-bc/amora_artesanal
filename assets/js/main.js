/**
 * Amora Artesanal - Interações Front-end (JavaScript)
 * Atende aos requisitos RF04, RF06 e Critérios de Aceite CA03, CA04
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Fechar alertas automaticamente após 5 segundos
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // 2. Contador de caracteres para campo de personalização (RF05)
    const customInput = document.getElementById('personalizacao');
    const customCounter = document.getElementById('personalizacao-count');
    if (customInput && customCounter) {
        customInput.addEventListener('input', () => {
            customCounter.textContent = `${customInput.value.length} / 250 caracteres`;
        });
    }

    // 3. Controle de Quantidade Dinâmico no Carrinho (RF06, CA04)
    const cartItems = document.querySelectorAll('.cart-item');
    cartItems.forEach(item => {
        const key = item.dataset.key;
        const btnMinus = item.querySelector('.btn-qty-minus');
        const btnPlus = item.querySelector('.btn-qty-plus');
        const inputQty = item.querySelector('.qty-input');
        const priceUnit = parseFloat(item.dataset.price || 0);
        const subtotalEl = item.querySelector('.cart-item-total');

        const updateItemTotal = (qty) => {
            if (subtotalEl) {
                const total = qty * priceUnit;
                subtotalEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
            }
            recalcularTotalGeral();
        };

        if (btnMinus && inputQty) {
            btnMinus.addEventListener('click', () => {
                let current = parseInt(inputQty.value) || 1;
                if (current > 1) {
                    inputQty.value = current - 1;
                    updateItemTotal(current - 1);
                    enviarAtualizacaoCarrinho(key, current - 1);
                }
            });
        }

        if (btnPlus && inputQty) {
            btnPlus.addEventListener('click', () => {
                let current = parseInt(inputQty.value) || 1;
                const max = parseInt(inputQty.max || 99);
                if (current < max) {
                    inputQty.value = current + 1;
                    updateItemTotal(current + 1);
                    enviarAtualizacaoCarrinho(key, current + 1);
                }
            });
        }

        if (inputQty) {
            inputQty.addEventListener('change', () => {
                let val = parseInt(inputQty.value) || 1;
                const max = parseInt(inputQty.max || 99);
                if (val < 1) val = 1;
                if (val > max) val = max;
                inputQty.value = val;
                updateItemTotal(val);
                enviarAtualizacaoCarrinho(key, val);
            });
        }
    });

    // Função para recalcular o total geral na página de carrinho
    function recalcularTotalGeral() {
        let total = 0;
        let totalItens = 0;
        document.querySelectorAll('.cart-item').forEach(item => {
            const qty = parseInt(item.querySelector('.qty-input')?.value || 0);
            const price = parseFloat(item.dataset.price || 0);
            total += qty * price;
            totalItens += qty;
        });

        const totalGeralEl = document.getElementById('cart-total-geral');
        const subtotalGeralEl = document.getElementById('cart-subtotal-geral');
        const badgeCount = document.querySelector('.badge-count');

        if (totalGeralEl) totalGeralEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        if (subtotalGeralEl) subtotalGeralEl.textContent = 'R$ ' + total.toFixed(2).replace('.', ',');
        if (badgeCount) badgeCount.textContent = totalItens;
    }

    // Enviar atualização de quantidade para o servidor via Fetch
    function enviarAtualizacaoCarrinho(key, quantidade) {
        const formData = new FormData();
        formData.append('acao', 'atualizar');
        formData.append('key', key);
        formData.append('quantidade', quantidade);

        fetch('carrinho.php', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .catch(err => console.log('Sincronização em segundo plano:', err));
    }
});
