import os

img_dir = r"c:\Users\Aluno\Documents\amora_artesanal\assets\img"
os.makedirs(img_dir, exist_ok=True)

svgs = {
    "placeholder.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
  <rect width="100%" height="100%" fill="#fdeeed" rx="20"/>
  <circle cx="150" cy="130" r="50" fill="#edd5d8"/>
  <path d="M120 180 Q150 220 180 180" stroke="#c86d7e" stroke-width="4" fill="none" stroke-linecap="round"/>
  <text x="150" y="240" font-family="'Poppins', sans-serif" font-size="16" fill="#5c3d2e" font-weight="600" text-anchor="middle">Amora Artesanal</text>
</svg>""",

    "hero_artesanal.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" width="100%" height="100%">
  <rect width="100%" height="100%" fill="#fffaf5" rx="30"/>
  <circle cx="200" cy="200" r="160" fill="#fdeeed"/>
  <circle cx="200" cy="200" r="130" fill="#f9e2e5"/>
  <!-- Vela Central -->
  <rect x="150" y="170" width="100" height="120" rx="16" fill="#ffffff" stroke="#e8b4b8" stroke-width="3"/>
  <rect x="158" y="200" width="84" height="60" rx="8" fill="#fdeeed"/>
  <text x="200" y="235" font-family="'Playfair Display', serif" font-size="12" fill="#c86d7e" text-anchor="middle" font-weight="bold">Amora</text>
  <text x="200" y="250" font-family="'Poppins', sans-serif" font-size="9" fill="#5c3d2e" text-anchor="middle">Aromaterapia</text>
  <!-- Pavio e Chama -->
  <line x1="200" y1="170" x2="200" y2="150" stroke="#5c3d2e" stroke-width="3" stroke-linecap="round"/>
  <path d="M200 115 Q218 135 200 150 Q182 135 200 115 Z" fill="#f59e0b"/>
  <path d="M200 125 Q210 138 200 148 Q190 138 200 125 Z" fill="#fef08a"/>
  <!-- Flores secas ao redor -->
  <circle cx="120" cy="260" r="18" fill="#c86d7e" opacity="0.8"/>
  <circle cx="280" cy="260" r="18" fill="#c86d7e" opacity="0.8"/>
  <circle cx="110" cy="180" r="14" fill="#df8d9c" opacity="0.7"/>
  <circle cx="290" cy="180" r="14" fill="#df8d9c" opacity="0.7"/>
  <text x="200" y="340" font-family="'Playfair Display', serif" font-size="18" fill="#5c3d2e" text-anchor="middle" font-weight="bold">Feito à Mão com Amor</text>
</svg>""",

    "vela_cerejeira.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fff7f8" rx="16"/>
  <ellipse cx="150" cy="250" rx="80" ry="20" fill="#edd5d8"/>
  <rect x="95" y="110" width="110" height="135" rx="14" fill="#ffffff" stroke="#c86d7e" stroke-width="2"/>
  <rect x="105" y="145" width="90" height="70" rx="8" fill="#fdeeed"/>
  <text x="150" y="175" font-family="'Playfair Display', serif" font-size="11" fill="#c86d7e" text-anchor="middle" font-weight="bold">Flor de Cerejeira</text>
  <text x="150" y="195" font-family="'Poppins', sans-serif" font-size="9" fill="#5c3d2e" text-anchor="middle">Cera 100% Vegetal</text>
  <line x1="150" y1="110" x2="150" y2="92" stroke="#5c3d2e" stroke-width="3" stroke-linecap="round"/>
  <path d="M150 55 Q165 75 150 90 Q135 75 150 55 Z" fill="#f59e0b"/>
  <path d="M150 67 Q158 78 150 88 Q142 78 150 67 Z" fill="#fef08a"/>
</svg>""",

    "vela_lavanda.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fbf7ff" rx="16"/>
  <ellipse cx="150" cy="250" rx="80" ry="20" fill="#e9dbf7"/>
  <rect x="95" y="110" width="110" height="135" rx="14" fill="#ffffff" stroke="#9067c6" stroke-width="2"/>
  <rect x="105" y="145" width="90" height="70" rx="8" fill="#f3ebfc"/>
  <text x="150" y="175" font-family="'Playfair Display', serif" font-size="11" fill="#7148a8" text-anchor="middle" font-weight="bold">Lavanda Francesa</text>
  <text x="150" y="195" font-family="'Poppins', sans-serif" font-size="9" fill="#5c3d2e" text-anchor="middle">Aromaterapia</text>
  <line x1="150" y1="110" x2="150" y2="92" stroke="#5c3d2e" stroke-width="3" stroke-linecap="round"/>
  <path d="M150 55 Q165 75 150 90 Q135 75 150 55 Z" fill="#f59e0b"/>
</svg>""",

    "vela_canela.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fffbf5" rx="16"/>
  <ellipse cx="150" cy="250" rx="80" ry="20" fill="#f0dfcb"/>
  <rect x="95" y="110" width="110" height="135" rx="14" fill="#ffffff" stroke="#b45309" stroke-width="2"/>
  <rect x="105" y="145" width="90" height="70" rx="8" fill="#fcedd9"/>
  <text x="150" y="175" font-family="'Playfair Display', serif" font-size="11" fill="#92400e" text-anchor="middle" font-weight="bold">Canela & Especiarias</text>
  <text x="150" y="195" font-family="'Poppins', sans-serif" font-size="9" fill="#5c3d2e" text-anchor="middle">Aconchego & Calor</text>
  <line x1="150" y1="110" x2="150" y2="92" stroke="#5c3d2e" stroke-width="3" stroke-linecap="round"/>
  <path d="M150 55 Q165 75 150 90 Q135 75 150 55 Z" fill="#ea580c"/>
</svg>""",

    "sabonete_rosa.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fff7f8" rx="16"/>
  <ellipse cx="150" cy="240" rx="90" ry="22" fill="#edd5d8"/>
  <rect x="80" y="130" width="140" height="90" rx="20" fill="#f9d2d9" stroke="#c86d7e" stroke-width="3"/>
  <rect x="95" y="145" width="110" height="60" rx="12" fill="#fdeeed"/>
  <text x="150" y="172" font-family="'Playfair Display', serif" font-size="12" fill="#c86d7e" text-anchor="middle" font-weight="bold">Argila Rosa</text>
  <text x="150" y="190" font-family="'Poppins', sans-serif" font-size="9" fill="#5c3d2e" text-anchor="middle">Saponificação a Frio</text>
  <!-- Ramos botânicos -->
  <path d="M120 100 Q150 120 180 100" stroke="#c86d7e" stroke-width="2" fill="none"/>
  <circle cx="150" cy="108" r="6" fill="#c86d7e"/>
</svg>""",

    "sabonete_cafe.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fffaf5" rx="16"/>
  <ellipse cx="150" cy="240" rx="90" ry="22" fill="#e8dacb"/>
  <rect x="80" y="130" width="140" height="90" rx="20" fill="#8c5835" stroke="#5c3d2e" stroke-width="3"/>
  <rect x="95" y="145" width="110" height="60" rx="12" fill="#b07a53"/>
  <text x="150" y="172" font-family="'Playfair Display', serif" font-size="12" fill="#fff" text-anchor="middle" font-weight="bold">Café & Mel</text>
  <text x="150" y="190" font-family="'Poppins', sans-serif" font-size="9" fill="#fdeeed" text-anchor="middle">Esfoliação Suave</text>
  <!-- Grãos de café -->
  <ellipse cx="150" cy="105" rx="12" ry="8" fill="#5c3d2e"/>
  <line x1="140" y1="105" x2="160" y2="105" stroke="#fff" stroke-width="1.5"/>
</svg>""",

    "sabonete_aveia.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fefdf9" rx="16"/>
  <ellipse cx="150" cy="240" rx="90" ry="22" fill="#ede6d6"/>
  <rect x="80" y="130" width="140" height="90" rx="20" fill="#f0e2c8" stroke="#a38965" stroke-width="3"/>
  <rect x="95" y="145" width="110" height="60" rx="12" fill="#faf4e8"/>
  <text x="150" y="172" font-family="'Playfair Display', serif" font-size="12" fill="#5c3d2e" text-anchor="middle" font-weight="bold">Aveia & Camomila</text>
  <text x="150" y="190" font-family="'Poppins', sans-serif" font-size="9" fill="#8a6a59" text-anchor="middle">Pele Sensível</text>
  <circle cx="150" cy="105" r="8" fill="#f59e0b"/>
</svg>""",

    "amigurumi_ursinho.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fcf6f0" rx="16"/>
  <ellipse cx="150" cy="260" rx="70" ry="18" fill="#ead8c7"/>
  <!-- Orelhas -->
  <circle cx="100" cy="110" r="22" fill="#bc8a5f" stroke="#8a5a36" stroke-width="2"/>
  <circle cx="100" cy="110" r="12" fill="#fdeeed"/>
  <circle cx="200" cy="110" r="22" fill="#bc8a5f" stroke="#8a5a36" stroke-width="2"/>
  <circle cx="200" cy="110" r="12" fill="#fdeeed"/>
  <!-- Cabeça -->
  <circle cx="150" cy="145" r="55" fill="#bc8a5f" stroke="#8a5a36" stroke-width="2"/>
  <!-- Focinho -->
  <ellipse cx="150" cy="160" rx="22" ry="16" fill="#fdeeed"/>
  <polygon points="144,153 156,153 150,161" fill="#5c3d2e"/>
  <!-- Olhos -->
  <circle cx="130" cy="138" r="5" fill="#2d1e18"/>
  <circle cx="170" cy="138" r="5" fill="#2d1e18"/>
  <circle cx="132" cy="136" r="1.5" fill="#ffffff"/>
  <circle cx="172" cy="136" r="1.5" fill="#ffffff"/>
  <!-- Gravatinha laço -->
  <path d="M135 205 L150 213 L135 221 Z M165 205 L150 213 L165 221 Z" fill="#c86d7e"/>
  <circle cx="150" cy="213" r="4" fill="#a04b5b"/>
  <!-- Corpo -->
  <ellipse cx="150" cy="235" rx="42" ry="30" fill="#bc8a5f" stroke="#8a5a36" stroke-width="2"/>
</svg>""",

    "amigurumi_coelho.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fff5f7" rx="16"/>
  <ellipse cx="150" cy="260" rx="70" ry="18" fill="#edd5d8"/>
  <!-- Orelhas compridas -->
  <ellipse cx="120" cy="75" rx="14" ry="45" fill="#ffffff" stroke="#c86d7e" stroke-width="2"/>
  <ellipse cx="120" cy="75" rx="8" ry="32" fill="#fdeeed"/>
  <ellipse cx="180" cy="75" rx="14" ry="45" fill="#ffffff" stroke="#c86d7e" stroke-width="2"/>
  <ellipse cx="180" cy="75" rx="8" ry="32" fill="#fdeeed"/>
  <!-- Cabeça -->
  <circle cx="150" cy="150" r="50" fill="#ffffff" stroke="#c86d7e" stroke-width="2"/>
  <!-- Focinho -->
  <ellipse cx="150" cy="162" rx="14" ry="10" fill="#fdeeed"/>
  <polygon points="146,158 154,158 150,164" fill="#c86d7e"/>
  <circle cx="132" cy="144" r="4" fill="#5c3d2e"/>
  <circle cx="168" cy="144" r="4" fill="#5c3d2e"/>
  <!-- Vestidinho floral -->
  <path d="M125 200 L175 200 L190 255 L110 255 Z" fill="#f9d2d9" stroke="#c86d7e" stroke-width="2"/>
  <circle cx="135" cy="225" r="4" fill="#c86d7e"/>
  <circle cx="165" cy="225" r="4" fill="#c86d7e"/>
  <circle cx="150" cy="240" r="4" fill="#c86d7e"/>
</svg>""",

    "amigurumi_polvo.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fef6f8" rx="16"/>
  <ellipse cx="150" cy="260" rx="70" ry="18" fill="#edd5d8"/>
  <!-- Cabeça redonda -->
  <ellipse cx="150" cy="130" rx="55" ry="60" fill="#f7b7c2" stroke="#c86d7e" stroke-width="2.5"/>
  <!-- Olhinhos e bochechas -->
  <circle cx="132" cy="135" r="5" fill="#5c3d2e"/>
  <circle cx="168" cy="135" r="5" fill="#5c3d2e"/>
  <ellipse cx="122" cy="148" rx="7" ry="4" fill="#ff7088" opacity="0.6"/>
  <ellipse cx="178" cy="148" rx="7" ry="4" fill="#ff7088" opacity="0.6"/>
  <path d="M144 148 Q150 155 156 148" stroke="#5c3d2e" stroke-width="2" fill="none" stroke-linecap="round"/>
  <!-- Tentáculos em espiral -->
  <path d="M110 185 Q100 230 115 245" stroke="#f7b7c2" stroke-width="12" fill="none" stroke-linecap="round"/>
  <path d="M130 190 Q125 240 140 250" stroke="#f7b7c2" stroke-width="12" fill="none" stroke-linecap="round"/>
  <path d="M150 190 Q155 240 160 250" stroke="#f7b7c2" stroke-width="12" fill="none" stroke-linecap="round"/>
  <path d="M170 190 Q175 240 180 250" stroke="#f7b7c2" stroke-width="12" fill="none" stroke-linecap="round"/>
  <path d="M190 185 Q200 230 190 245" stroke="#f7b7c2" stroke-width="12" fill="none" stroke-linecap="round"/>
</svg>""",

    "kit_mimo.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fffaf5" rx="16"/>
  <ellipse cx="150" cy="255" rx="85" ry="20" fill="#ecd9c6"/>
  <!-- Caixa kraft -->
  <rect x="75" y="140" width="150" height="95" rx="12" fill="#d2a679" stroke="#936437" stroke-width="2"/>
  <!-- Fita e laço rosa -->
  <rect x="140" y="140" width="20" height="95" fill="#c86d7e"/>
  <path d="M130 125 C115 110 135 95 150 125 C165 95 185 110 170 125 Z" fill="#c86d7e"/>
  <circle cx="150" cy="126" r="6" fill="#a04b5b"/>
  <!-- Itens espiando da caixa -->
  <rect x="95" y="105" width="35" height="40" rx="6" fill="#fdeeed" stroke="#c86d7e" stroke-width="1.5"/>
  <circle cx="195" cy="115" r="14" fill="#f9d2d9"/>
</svg>""",

    "kit_spa.svg": """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300">
  <rect width="100%" height="100%" fill="#fffaf5" rx="16"/>
  <ellipse cx="150" cy="255" rx="85" ry="20" fill="#ecd9c6"/>
  <!-- Cesta de vime ou caixa elegante -->
  <rect x="70" y="135" width="160" height="100" rx="14" fill="#eed9c4" stroke="#8a5a36" stroke-width="2"/>
  <!-- Toalhinha enrolada -->
  <rect x="85" y="100" width="35" height="50" rx="8" fill="#ffffff" stroke="#c86d7e" stroke-width="1.5"/>
  <!-- Vela aromática -->
  <rect x="130" y="90" width="40" height="55" rx="6" fill="#fdeeed" stroke="#c86d7e" stroke-width="1.5"/>
  <path d="M150 75 Q156 82 150 88 Q144 82 150 75 Z" fill="#f59e0b"/>
  <!-- Sabonete -->
  <rect x="180" y="110" width="35" height="40" rx="8" fill="#f3ebfc" stroke="#9067c6" stroke-width="1.5"/>
  <rect x="60" y="170" width="180" height="16" rx="4" fill="#c86d7e" opacity="0.9"/>
</svg>"""
}

for filename, content in svgs.items():
    path = os.path.join(img_dir, filename)
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)

print(f"Generated {len(svgs)} SVGs in {img_dir}")
