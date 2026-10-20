/**
 * Mapa Interativo Estilo Google Maps - Amora Artesanal
 * Integração com Leaflet + Camadas Google Maps (Ruas/Satélite) e Tema Artesanal
 */
(function() {
    'use strict';

    window.AmoraMapas = window.AmoraMapas || {};

    /**
     * Inicializa um mapa interativo para um pedido ou localização
     */
    window.AmoraMapas.init = function(containerId, options) {
        if (typeof L === 'undefined') {
            console.warn('Leaflet não carregado para o container:', containerId);
            return null;
        }

        var container = document.getElementById(containerId);
        if (!container) return null;

        var opt = Object.assign({
            origem: { lat: -8.76194, lng: -63.90389, nome: 'Ateliê Amora Artesanal', endereco: 'Av. Calama, 4985 - Porto Velho/RO' },
            destino: { lat: -8.7520, lng: -63.8810, nome: 'Destino do Cliente', endereco: 'Endereço de Entrega' },
            status: 'Enviado',
            transportadora: 'LogiAmora Transportes / Sedex',
            codigoRastreio: 'AM00000001BR',
            clienteNome: 'Cliente Amora',
            tipo: 'rastreamento' // 'rastreamento' ou 'loja'
        }, options || {});

        // 1. Criar Camadas de Mapa Reais
        var camadaGoogleRuas = L.tileLayer('https://mt1.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps'
        });

        var camadaGoogleSatelite = L.tileLayer('https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0', 'mt1', 'mt2', 'mt3'],
            attribution: '&copy; Google Maps Satélite'
        });

        var camadaAmoraArtesanal = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            subdomains: 'abcd',
            attribution: '&copy; <a href="https://carto.com/">CARTO</a> &bull; Tema Amora'
        });

        // Camada inicial padrão: Google Ruas
        var map = L.map(containerId, {
            center: [opt.origem.lat, opt.origem.lng],
            zoom: 13,
            layers: [camadaGoogleRuas],
            zoomControl: true,
            scrollWheelZoom: true
        });

        // 2. Marcador: Ateliê Amora (Origem)
        var iconOrigem = L.divIcon({
            className: 'amora-map-marker',
            html: '<div class="amora-pin origem"><i class="fa-solid fa-heart"></i></div>' +
                  '<div class="amora-pin-label">★ Ateliê Amora (Porto Velho)</div>',
            iconSize: [120, 60],
            iconAnchor: [60, 42],
            popupAnchor: [0, -40]
        });

        var markerOrigem = L.marker([opt.origem.lat, opt.origem.lng], { icon: iconOrigem }).addTo(map);
        markerOrigem.bindPopup(
            '<div class="amora-popup-box">' +
                '<div class="amora-popup-header"><i class="fa-solid fa-store"></i> Ateliê Amora Artesanal</div>' +
                '<div class="amora-popup-body">' +
                    '<span class="amora-popup-badge" style="background: #fdeeed; color: #c86d7e;">Ponto de Origem e Produção</span>' +
                    '<p><strong>Endereço:</strong> ' + opt.origem.endereco + '</p>' +
                    '<p><strong>Telefone:</strong> (69) 3219-4500 | WhatsApp: (69) 98412-3456</p>' +
                    '<p style="margin: 0; color: #4e8d53;"><i class="fa-solid fa-circle-check"></i> Encomenda embalada com carinho e proteção.</p>' +
                '</div>' +
                '<div class="amora-popup-footer">' +
                    '<span>Hub Porto Velho/RO</span>' +
                    '<span>GPS: -08°45\'S, -63°54\'W</span>' +
                '</div>' +
            '</div>'
        );

        // Se for página de contato (apenas a loja), encerra aqui
        if (opt.tipo === 'loja') {
            markerOrigem.openPopup();
            return { map: map, camadas: { ruas: camadaGoogleRuas, satelite: camadaGoogleSatelite, amora: camadaAmoraArtesanal } };
        }

        // 3. Marcador: Destino do Cliente
        var iconDestino = L.divIcon({
            className: 'amora-map-marker',
            html: '<div class="amora-pin destino"><i class="fa-solid fa-house-chimney"></i></div>' +
                  '<div class="amora-pin-label">📍 ' + (opt.clienteNome ? opt.clienteNome : 'Destino') + '</div>',
            iconSize: [120, 60],
            iconAnchor: [60, 42],
            popupAnchor: [0, -40]
        });

        var markerDestino = L.marker([opt.destino.lat, opt.destino.lng], { icon: iconDestino }).addTo(map);
        markerDestino.bindPopup(
            '<div class="amora-popup-box">' +
                '<div class="amora-popup-header" style="background: #4e8d53;"><i class="fa-solid fa-location-dot"></i> Endereço de Entrega</div>' +
                '<div class="amora-popup-body">' +
                    '<span class="amora-popup-badge" style="background: #e7f8ee; color: #208743;">Destino Final da Encomenda</span>' +
                    '<p><strong>Destinatário:</strong> ' + opt.clienteNome + '</p>' +
                    '<p><strong>Endereço:</strong> ' + opt.destino.endereco + '</p>' +
                    '<p style="margin: 0;"><strong>Código Rastreio:</strong> <code style="color: #c86d7e; font-weight: bold;">' + opt.codigoRastreio + '</code></p>' +
                '</div>' +
                '<div class="amora-popup-footer">' +
                    '<span>Previsão: Entrega Prioritária</span>' +
                    '<span>Status: ' + opt.status + '</span>' +
                '</div>' +
            '</div>'
        );

        // 4. Calcular Posição do Veículo Conforme o Status
        var fracPos = 0.55; // Padrão 'Enviado' (metade do caminho)
        if (opt.status === 'Pedido realizado') fracPos = 0.05;
        else if (opt.status === 'Em preparação') fracPos = 0.15;
        else if (opt.status === 'Enviado') fracPos = 0.65;
        else if (opt.status === 'Entregue') fracPos = 1.0;

        var veiculoLat = opt.origem.lat + (opt.destino.lat - opt.origem.lat) * fracPos;
        var veiculoLng = opt.origem.lng + (opt.destino.lng - opt.origem.lng) * fracPos;

        // Marcador do Veículo / Caminhão LogiAmora
        var iconVeiculo = L.divIcon({
            className: 'amora-veiculo-marker',
            html: '<div class="veiculo-radar-ring"></div>' +
                  '<div class="veiculo-radar-ring ring-2"></div>' +
                  '<div class="veiculo-card-icon"><i class="fa-solid ' + (opt.status === 'Entregue' ? 'fa-box-open' : 'fa-truck-fast') + '"></i></div>' +
                  '<div class="veiculo-tag-status">' + (opt.status === 'Entregue' ? 'ENTREGUE' : 'LOGIAMORA 65 km/h') + '</div>',
            iconSize: [60, 60],
            iconAnchor: [30, 30],
            popupAnchor: [0, -32]
        });

        var markerVeiculo = L.marker([veiculoLat, veiculoLng], { icon: iconVeiculo }).addTo(map);
        markerVeiculo.bindPopup(
            '<div class="amora-popup-box">' +
                '<div class="amora-popup-header"><i class="fa-solid fa-truck-ramp-box"></i> ' + opt.transportadora + '</div>' +
                '<div class="amora-popup-body">' +
                    '<span class="amora-popup-badge" style="background: #fff0f3; color: #c86d7e;">Monitoramento Satelital em Tempo Real</span>' +
                    '<p><strong>Status Atual:</strong> <span style="font-weight: 700; color: #4e8d53;">' + opt.status + '</span></p>' +
                    '<p><strong>Veículo:</strong> Furgão Iveco Daily &bull; Placa: <code>AMO-2026</code></p>' +
                    '<p><strong>Motorista Responsável:</strong> Carlos E. / LogiAmora Express</p>' +
                    '<p style="margin: 0; color: #8a6a59;"><i class="fa-solid fa-shield-halved" style="color: #4e8d53;"></i> Carga segurada e protegida contra avarias térmicas.</p>' +
                '</div>' +
                '<div class="amora-popup-footer">' +
                    '<span>GPS Atualizado há 1 min</span>' +
                    '<span style="color: #4e8d53; font-weight: 600;">Sinal 100% Estável</span>' +
                '</div>' +
            '</div>'
        );

        // 5. Linha de Rota (Polyline com curva realista)
        var midLat = (opt.origem.lat + opt.destino.lat) / 2 + (opt.origem.lng - opt.destino.lng) * 0.08;
        var midLng = (opt.origem.lng + opt.destino.lng) / 2 - (opt.origem.lat - opt.destino.lat) * 0.08;
        var pontosRota = [
            [opt.origem.lat, opt.origem.lng],
            [midLat, midLng],
            [opt.destino.lat, opt.destino.lng]
        ];

        // Linha externa de glow
        L.polyline(pontosRota, {
            color: '#c86d7e',
            weight: 7,
            opacity: 0.35,
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(map);

        // Linha interna tracejada
        L.polyline(pontosRota, {
            color: '#8c2a41',
            weight: 3.5,
            opacity: 0.9,
            dashArray: '8, 8',
            lineCap: 'round'
        }).addTo(map);

        // 6. Enquadrar na Rota Completa com Margem Suave
        var bounds = L.latLngBounds([
            [opt.origem.lat, opt.origem.lng],
            [opt.destino.lat, opt.destino.lng],
            [veiculoLat, veiculoLng]
        ]);
        map.fitBounds(bounds, { padding: [45, 45], maxZoom: 16 });

        // Abre o popup do veículo por padrão para dar sensação de interatividade imediata
        setTimeout(function() {
            markerVeiculo.openPopup();
        }, 600);

        // 7. Conectar Ações dos Botões da Barra de Controle (se existirem)
        var wrapper = container.closest('.mapa-interativo-wrapper');
        if (wrapper) {
            // Comutador de Camadas
            var btnsCamada = wrapper.querySelectorAll('.btn-camada-mapa');
            btnsCamada.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var tipo = this.getAttribute('data-camada');
                    btnsCamada.forEach(function(b) { b.classList.remove('active'); });
                    this.classList.add('active');

                    map.removeLayer(camadaGoogleRuas);
                    map.removeLayer(camadaGoogleSatelite);
                    map.removeLayer(camadaAmoraArtesanal);

                    if (tipo === 'satelite') {
                        map.addLayer(camadaGoogleSatelite);
                    } else if (tipo === 'amora') {
                        map.addLayer(camadaAmoraArtesanal);
                    } else {
                        map.addLayer(camadaGoogleRuas);
                    }
                });
            });

            // Botão Focar Rota
            var btnFocar = wrapper.querySelector('.btn-focar-rota');
            if (btnFocar) {
                btnFocar.addEventListener('click', function(e) {
                    e.preventDefault();
                    map.fitBounds(bounds, { padding: [45, 45], maxZoom: 16 });
                });
            }

            // Botão Seguir Veículo
            var btnVeiculo = wrapper.querySelector('.btn-seguir-veiculo');
            if (btnVeiculo) {
                btnVeiculo.addEventListener('click', function(e) {
                    e.preventDefault();
                    map.flyTo([veiculoLat, veiculoLng], 16, { animate: true, duration: 1.2 });
                    markerVeiculo.openPopup();
                });
            }

            // Botão Tela Cheia
            var btnFullscreen = wrapper.querySelector('.btn-mapa-fullscreen');
            if (btnFullscreen) {
                btnFullscreen.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (!document.fullscreenElement) {
                        wrapper.requestFullscreen().then(function() {
                            setTimeout(function() { map.invalidateSize(); }, 200);
                        }).catch(function(err) {
                            console.warn('Erro ao entrar em fullscreen:', err);
                        });
                    } else {
                        document.exitFullscreen().then(function() {
                            setTimeout(function() { map.invalidateSize(); }, 200);
                        });
                    }
                });
            }
        }

        return {
            map: map,
            markerOrigem: markerOrigem,
            markerDestino: markerDestino,
            markerVeiculo: markerVeiculo,
            bounds: bounds
        };
    };

})();
