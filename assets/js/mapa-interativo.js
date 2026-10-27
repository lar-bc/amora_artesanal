/**
 * Mapa Interativo Estilo Google Maps - Amora Artesanal
 * Integração com Leaflet + Camadas Google Maps (Ruas/Satélite) e Tema Artesanal
 * Suporte a Deslocamento Real pelas Ruas, Drone (Curta Duração) e Rodoviário (Longa Duração)
 */
(function() {
    'use strict';

    window.AmoraMapas = window.AmoraMapas || {};

    /**
     * Gera uma malha de pontos intermediários simulando ruas reais e rodovias
     */
    function gerarTrajetoRuasReais(origem, destino, isCurtaDuracao) {
        var waypoints = [];
        waypoints.push([origem.lat, origem.lng]); // IFRO Calama (Av. Calama, 4985)

        if (isCurtaDuracao) {
            // Curta duração em Porto Velho (Corredor Urbano / Av. Calama e Avenidas Cruzadas)
            waypoints.push([-8.7458, -63.8775]); // Av. Calama x Av. Guaporé
            waypoints.push([-8.7478, -63.8860]); // Av. Calama x Av. Rio Madeira
            waypoints.push([-8.7510, -63.8950]); // Av. Calama x Av. Gov. Jorge Teixeira
            // Manobra de aproximação para a rua de destino do cliente
            var midLat = (waypoints[waypoints.length - 1][0] + destino.lat) / 2;
            var midLng = (waypoints[waypoints.length - 1][1] + destino.lng) / 2;
            waypoints.push([midLat, waypoints[waypoints.length - 1][1]]);
            waypoints.push([midLat, destino.lng]);
        } else {
            // Longa duração rodoviária (Saída via Av. Calama -> Jorge Teixeira -> Trevo BR-364 -> Rodovia)
            waypoints.push([-8.7470, -63.8820]); // Av. Calama
            waypoints.push([-8.7520, -63.8960]); // Av. Jorge Teixeira
            waypoints.push([-8.7660, -63.8890]); // Viaduto Trevo do Roque
            waypoints.push([-8.7770, -63.8750]); // Acesso Rodovia Federal BR-364
            waypoints.push([-8.7910, -63.8500]); // Rodovia BR-364 Corredor Logístico
            // Curva suave na rodovia em direção ao destino interestadual/intermunicipal
            var midRodLat = (origem.lat + destino.lat) / 2 + (origem.lng - destino.lng) * 0.05;
            var midRodLng = (origem.lng + destino.lng) / 2 - (origem.lat - destino.lat) * 0.05;
            waypoints.push([midRodLat, midRodLng]);
        }

        waypoints.push([destino.lat, destino.lng]); // Ponto final da residência do cliente

        // Interpolar densamente os pontos para animação suave rua a rua
        var pontosDensos = [];
        for (var i = 0; i < waypoints.length - 1; i++) {
            var p1 = waypoints[i];
            var p2 = waypoints[i + 1];
            var steps = 24; // 24 micro-passos por trecho
            for (var s = 0; s < steps; s++) {
                var t = s / steps;
                var lat = p1[0] + (p2[0] - p1[0]) * t;
                var lng = p1[1] + (p2[1] - p1[1]) * t;
                pontosDensos.push([lat, lng]);
            }
        }
        pontosDensos.push([destino.lat, destino.lng]);
        return { waypoints: waypoints, pontosDensos: pontosDensos };
    }

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
            origem: {
                lat: -8.7441684,
                lng: -63.8718575,
                nome: 'Ateliê Amora Artesanal (IFRO Calama)',
                endereco: 'Av. Calama, 4985 - Bairro Flodoaldo Pontes Pinto, Porto Velho/RO (CEP 76820-441)'
            },
            destino: {
                lat: -8.7520,
                lng: -63.8810,
                nome: 'Cliente Amora',
                codigoCliente: 'CLI-00001',
                telefone: '(69) 98412-3456',
                email: 'cliente@gmail.com',
                endereco: 'Endereço de Entrega do Cliente',
                cidade: 'Porto Velho',
                estado: 'RO',
                cep: '76820-000'
            },
            status: 'Enviado',
            transportadora: 'LogiAmora Transportes / Sedex',
            codigoRastreio: 'AM00000001BR',
            clienteNome: 'Cliente Amora',
            tipoTransporte: 'drone', // 'drone' (curta duração) ou 'rodoviario' (longa duração)
            tipo: 'rastreamento' // 'rastreamento' ou 'loja'
        }, options || {});

        // 1. Criar Camadas de Mapa Reais (Google Maps Ruas, Satélite e Amora)
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

        // 2. Marcador: Ateliê Amora (Origem - IFRO Calama)
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
                    '<p><strong>Endereço Oficial:</strong> ' + opt.origem.endereco + '</p>' +
                    '<p><strong>Telefone:</strong> (69) 3219-4500 | WhatsApp: (69) 98412-3456</p>' +
                    '<p style="margin: 0; color: #4e8d53;"><i class="fa-solid fa-circle-check"></i> Encomenda embalada com carinho, etiqueta de conferência e segurança.</p>' +
                '</div>' +
                '<div class="amora-popup-footer">' +
                    '<span>Hub Porto Velho/RO</span>' +
                    '<span>IFRO Calama &bull; GPS: -08°45\'S, -63°52\'W</span>' +
                '</div>' +
            '</div>'
        );

        // Se for página de contato (apenas a loja), encerra aqui
        if (opt.tipo === 'loja') {
            markerOrigem.openPopup();
            return { map: map, camadas: { ruas: camadaGoogleRuas, satelite: camadaGoogleSatelite, amora: camadaAmoraArtesanal } };
        }

        // 3. Marcador: Destino do Cliente com Todas as Informações Pessoais Verificadas
        var destNome = (opt.destino && opt.destino.nome) ? opt.destino.nome : opt.clienteNome;
        var destCod = (opt.destino && opt.destino.codigoCliente) ? opt.destino.codigoCliente : 'CLI-00001';
        var destTel = (opt.destino && opt.destino.telefone) ? opt.destino.telefone : 'Não informado';
        var destEmail = (opt.destino && opt.destino.email) ? opt.destino.email : '';
        var destEnd = (opt.destino && opt.destino.endereco) ? opt.destino.endereco : 'Endereço cadastrado';
        var destCep = (opt.destino && opt.destino.cep) ? opt.destino.cep : '';

        var iconDestino = L.divIcon({
            className: 'amora-map-marker',
            html: '<div class="amora-pin destino"><i class="fa-solid fa-house-chimney"></i></div>' +
                  '<div class="amora-pin-label">📍 ' + destNome + '</div>',
            iconSize: [120, 60],
            iconAnchor: [60, 42],
            popupAnchor: [0, -40]
        });

        var markerDestino = L.marker([opt.destino.lat, opt.destino.lng], { icon: iconDestino }).addTo(map);
        markerDestino.bindPopup(
            '<div class="amora-popup-box">' +
                '<div class="amora-popup-header" style="background: #4e8d53;"><i class="fa-solid fa-location-dot"></i> Endereço de Entrega Verificado</div>' +
                '<div class="amora-popup-body">' +
                    '<span class="amora-popup-badge" style="background: #e7f8ee; color: #208743;">Destino Final da Encomenda</span>' +
                    '<p><strong>Destinatário:</strong> ' + destNome + ' <span style="font-family: monospace; font-size: 0.75rem; background: #eee; padding: 1px 5px; border-radius: 4px;">#' + destCod + '</span></p>' +
                    '<p><strong>Contato:</strong> ' + destTel + (destEmail ? ' &bull; ' + destEmail : '') + '</p>' +
                    '<p><strong>Endereço Completo:</strong> ' + destEnd + (destCep ? ' (CEP: ' + destCep + ')' : '') + '</p>' +
                    '<p style="margin: 0;"><strong>Código Rastreio:</strong> <code style="color: #c86d7e; font-weight: bold;">' + opt.codigoRastreio + '</code></p>' +
                '</div>' +
                '<div class="amora-popup-footer">' +
                    '<span>Status: ' + opt.status + '</span>' +
                    '<span style="color: #208743; font-weight: 600;"><i class="fa-solid fa-shield-check"></i> Dados Sincronizados</span>' +
                '</div>' +
            '</div>'
        );

        // 4. Determinar Meio de Transporte: Drone (Curta Duração) vs Rodoviário (Longa Duração)
        var modoAtual = (opt.tipoTransporte === 'drone') ? 'drone' : 'rodoviario';
        var isCurta = (modoAtual === 'drone');

        // Gerar rota realista seguindo as ruas / rodovias
        var rotaData = gerarTrajetoRuasReais(opt.origem, opt.destino, isCurta);
        var waypoints = rotaData.waypoints;
        var pontosDensos = rotaData.pontosDensos;

        // Desenhar Polylines da Rota
        var polyGlow = L.polyline(waypoints, {
            color: isCurta ? '#4caf50' : '#c86d7e',
            weight: 7,
            opacity: 0.35,
            lineCap: 'round',
            lineJoin: 'round'
        }).addTo(map);

        var polyLine = L.polyline(waypoints, {
            color: isCurta ? '#2e7d32' : '#8c2a41',
            weight: 3.5,
            opacity: 0.9,
            dashArray: '8, 8',
            lineCap: 'round'
        }).addTo(map);

        // 5. Marcador de Veículo com Suporte a Drone e Rodoviário
        function criarHtmlVeiculo(modo, status) {
            if (modo === 'drone') {
                return '<div class="veiculo-radar-ring" style="border-color: #4caf50;"></div>' +
                       '<div class="veiculo-radar-ring ring-2" style="border-color: #81c784;"></div>' +
                       '<div class="veiculo-card-icon" style="border-color: #2e7d32; color: #2e7d32;">' +
                           '<i class="fa-solid fa-helicopter drone-propeller"></i>' +
                       '</div>' +
                       '<div class="veiculo-tag-status" style="border-color: #4caf50;">' + 
                           (status === 'Entregue' ? 'DRONE POUSADO' : '🚁 DRONE SKYDELIVERY 45 km/h') + 
                       '</div>';
            } else {
                return '<div class="veiculo-radar-ring" style="border-color: #0288d1;"></div>' +
                       '<div class="veiculo-radar-ring ring-2" style="border-color: #4fc3f7;"></div>' +
                       '<div class="veiculo-card-icon" style="border-color: #0277bd; color: #0277bd;">' +
                           '<i class="fa-solid ' + (status === 'Entregue' ? 'fa-box-open' : 'fa-truck-fast') + '"></i>' +
                       '</div>' +
                       '<div class="veiculo-tag-status" style="border-color: #0288d1;">' + 
                           (status === 'Entregue' ? 'ENTREGUE NA PORTA' : '🚛 RODOVIÁRIO BR-364 75 km/h') + 
                       '</div>';
            }
        }

        function criarPopupVeiculo(modo, status, lat, lng) {
            if (modo === 'drone') {
                return '<div class="amora-popup-box">' +
                    '<div class="amora-popup-header" style="background: #2e7d32;"><i class="fa-solid fa-helicopter"></i> LogiAmora SkyDelivery &bull; EcoDrone Autônomo</div>' +
                    '<div class="amora-popup-body">' +
                        '<span class="amora-popup-badge" style="background: #e8f5e9; color: #2e7d32;">Curta Duração &bull; Entrega Expressa por Drone</span>' +
                        '<p><strong>Status do Voo:</strong> <span style="font-weight: 700; color: #2e7d32;">' + status + '</span></p>' +
                        '<p><strong>Telemetria:</strong> Altitude 45m AGL &bull; Velocidade 45 km/h &bull; Bateria: 92%</p>' +
                        '<p><strong>Destinatário:</strong> ' + destNome + ' (#' + destCod + ')</p>' +
                        '<p><strong>Endereço de Entrega:</strong> ' + destEnd + '</p>' +
                        '<p style="margin: 0; color: #2e7d32;"><i class="fa-solid fa-fan fa-spin"></i> Hélices ativas em corredor aéreo direto pelas ruas de Porto Velho.</p>' +
                    '</div>' +
                    '<div class="amora-popup-footer">' +
                        '<span>GPS: ' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '</span>' +
                        '<span style="color: #2e7d32; font-weight: 600;">Sinal 100% Estável</span>' +
                    '</div>' +
                '</div>';
            } else {
                return '<div class="amora-popup-box">' +
                    '<div class="amora-popup-header" style="background: #0277bd;"><i class="fa-solid fa-truck-fast"></i> ' + opt.transportadora + '</div>' +
                    '<div class="amora-popup-body">' +
                        '<span class="amora-popup-badge" style="background: #e1f5fe; color: #0277bd;">Longa Duração &bull; Rodovias e Malha Viária</span>' +
                        '<p><strong>Status da Viagem:</strong> <span style="font-weight: 700; color: #0277bd;">' + status + '</span></p>' +
                        '<p><strong>Veículo:</strong> Furgão Iveco Daily &bull; Placa: <code>AMO-2026</code> &bull; 75 km/h</p>' +
                        '<p><strong>Destinatário:</strong> ' + destNome + ' (#' + destCod + ')</p>' +
                        '<p><strong>Endereço de Entrega:</strong> ' + destEnd + '</p>' +
                        '<p style="margin: 0; color: #8a6a59;"><i class="fa-solid fa-shield-halved" style="color: #4e8d53;"></i> Carga segurada e rastreada via satélite na BR-364.</p>' +
                    '</div>' +
                    '<div class="amora-popup-footer">' +
                        '<span>GPS: ' + lat.toFixed(4) + ', ' + lng.toFixed(4) + '</span>' +
                        '<span style="color: #4e8d53; font-weight: 600;">Sinal Satelital OK</span>' +
                    '</div>' +
                '</div>';
            }
        }

        // Ponto de partida inicial para o veículo
        var totalPontos = pontosDensos.length;
        var pontoInicialIdx = Math.floor(totalPontos * 0.45);
        if (opt.status === 'Pedido realizado') pontoInicialIdx = 0;
        else if (opt.status === 'Em preparação') pontoInicialIdx = Math.floor(totalPontos * 0.15);
        else if (opt.status === 'Enviado') pontoInicialIdx = Math.floor(totalPontos * 0.60);
        else if (opt.status === 'Entregue') pontoInicialIdx = totalPontos - 1;

        var posAtual = pontosDensos[pontoInicialIdx];
        var iconVeiculo = L.divIcon({
            className: 'amora-veiculo-marker ' + modoAtual,
            html: criarHtmlVeiculo(modoAtual, opt.status),
            iconSize: [60, 60],
            iconAnchor: [30, 30],
            popupAnchor: [0, -32]
        });

        var markerVeiculo = L.marker(posAtual, { icon: iconVeiculo }).addTo(map);
        markerVeiculo.bindPopup(criarPopupVeiculo(modoAtual, opt.status, posAtual[0], posAtual[1]));

        // 6. Enquadrar na Rota Completa com Margem Suave
        var bounds = L.latLngBounds([
            [opt.origem.lat, opt.origem.lng],
            [opt.destino.lat, opt.destino.lng],
            posAtual
        ]);
        map.fitBounds(bounds, { padding: [45, 45], maxZoom: 16 });

        setTimeout(function() {
            markerVeiculo.openPopup();
        }, 600);

        // 7. Animação de Deslocamento Real pelas Ruas (Precisa se locomover na rua)
        var animando = (opt.status === 'Enviado');
        var stepIndex = pontoInicialIdx;
        var direcao = 1; // 1 = avança em direção ao destino, -1 = patrulha
        var animInterval = null;

        function moverVeiculoPasso() {
            if (!animando || pontosDensos.length < 2) return;

            stepIndex += direcao;
            if (stepIndex >= pontosDensos.length - 1) {
                stepIndex = pontosDensos.length - 1;
                direcao = -1; // Volta patrulhando o corredor viário
            } else if (stepIndex <= 0) {
                stepIndex = 0;
                direcao = 1;
            }

            var novaPos = pontosDensos[stepIndex];
            markerVeiculo.setLatLng(novaPos);

            // Atualiza conteúdo do popup mantendo o endereço e destinatário corretos
            if (markerVeiculo.isPopupOpen()) {
                markerVeiculo.setPopupContent(criarPopupVeiculo(modoAtual, opt.status, novaPos[0], novaPos[1]));
            }
        }

        // Inicia o motor de animação rua a rua (passo a cada 120ms para movimento realista e fluido)
        if (animando) {
            animInterval = setInterval(moverVeiculoPasso, 120);
        }

        // 8. Conectar Ações dos Botões da Barra de Controle
        var wrapper = container.closest('.mapa-interativo-wrapper');
        if (wrapper) {
            // Comutador de Camadas Estilo Google Maps
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

            // Botão Seguir Veículo no Mapa
            var btnVeiculo = wrapper.querySelector('.btn-seguir-veiculo');
            if (btnVeiculo) {
                btnVeiculo.addEventListener('click', function(e) {
                    e.preventDefault();
                    var currentPos = markerVeiculo.getLatLng();
                    map.flyTo(currentPos, 16, { animate: true, duration: 1.0 });
                    markerVeiculo.openPopup();
                });
            }

            // Botão Pausar / Retomar Deslocamento na Rua
            var btnAnimToggle = wrapper.querySelector('.btn-anim-toggle');
            if (btnAnimToggle) {
                btnAnimToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    animando = !animando;
                    if (animando && !animInterval) {
                        animInterval = setInterval(moverVeiculoPasso, 120);
                    } else if (!animando && animInterval) {
                        clearInterval(animInterval);
                        animInterval = null;
                    }
                    var icon = this.querySelector('i');
                    var label = this.querySelector('.anim-label');
                    if (animando) {
                        if (icon) icon.className = 'fa-solid fa-pause';
                        if (label) label.textContent = 'Pausar';
                    } else {
                        if (icon) icon.className = 'fa-solid fa-play';
                        if (label) label.textContent = 'Mover na Rua';
                    }
                });
            }

            // Botão Alternar Modo (Drone vs Rodoviário)
            var btnAlternarModo = wrapper.querySelector('.btn-alternar-modo');
            if (btnAlternarModo) {
                btnAlternarModo.addEventListener('click', function(e) {
                    e.preventDefault();
                    modoAtual = (modoAtual === 'drone') ? 'rodoviario' : 'drone';
                    isCurta = (modoAtual === 'drone');

                    // Recalcula trajeto conforme o novo meio
                    var novaRota = gerarTrajetoRuasReais(opt.origem, opt.destino, isCurta);
                    waypoints = novaRota.waypoints;
                    pontosDensos = novaRota.pontosDensos;
                    stepIndex = Math.min(stepIndex, pontosDensos.length - 1);

                    // Atualiza polyline
                    polyGlow.setLatLngs(waypoints);
                    polyGlow.setStyle({ color: isCurta ? '#4caf50' : '#c86d7e' });
                    polyLine.setLatLngs(waypoints);
                    polyLine.setStyle({ color: isCurta ? '#2e7d32' : '#8c2a41' });

                    // Atualiza ícone do marcador
                    var curPos = pontosDensos[stepIndex];
                    markerVeiculo.setIcon(L.divIcon({
                        className: 'amora-veiculo-marker ' + modoAtual,
                        html: criarHtmlVeiculo(modoAtual, opt.status),
                        iconSize: [60, 60],
                        iconAnchor: [30, 30],
                        popupAnchor: [0, -32]
                    }));
                    markerVeiculo.setPopupContent(criarPopupVeiculo(modoAtual, opt.status, curPos[0], curPos[1]));
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
