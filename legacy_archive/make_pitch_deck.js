const pptxgen = require('pptxgenjs');
const path = require('path');
const fs = require('fs');

const pres = new pptxgen();

// Layout 16:9 Widescreen (13.333 x 7.5 inches)
pres.defineLayout({ name: 'WIDE169', width: 13.333, height: 7.5 });
pres.layout = 'WIDE169';

// Brand Colors
const C = {
  bgDark: '0B0D13',       // Fundo principal ultra dark
  cardDark: '141722',     // Fundo dos cards
  cardBorder: '252D3D',   // Borda sutil dos cards
  cyan: '34E2E4',         // Cor primária neon
  purple: '4721FB',       // Roxo elétrico
  magenta: 'AB1DFE',      // Magenta / violeta
  orange: 'FF6B00',       // Laranja / Destaque de ganhos
  orangeLight: 'FFA133',  // Laranja claro
  green: '00E676',        // Verde lucro / ROI
  white: 'FFFFFF',        // Branco puro
  grayLight: 'CBD5E1',    // Cinza claro legível
  grayMuted: '8E9BAE',    // Cinza suave para legendas
  badgeBg: '1E2433'       // Fundo dos badges
};

// Paths for images
const imgLogo = path.resolve('banners/logo.png');
const imgTablet = path.resolve('banners/pov_tablet_pizzaria.jpg');
const imgVertical = path.resolve('banners/propaganda_motoristas_vertical.jpg');

// Helper: Header padronizado para os slides
function addSlideHeader(slide, badgeText, titleText, subtitleText) {
  // Background
  slide.background = { color: C.bgDark };

  // Linha superior decorativa de gradiente sutil
  slide.addShape(pres.shapes.RECTANGLE, {
    x: 0, y: 0, w: 13.333, h: 0.08,
    fill: { color: C.cyan },
    line: { color: C.cyan }
  });

  // Badge da categoria
  if (badgeText) {
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 0.8, y: 0.45, w: 3.6, h: 0.38,
      fill: { color: C.badgeBg },
      line: { color: C.cyan, width: 1 },
      rectRadius: 0.08
    });
    slide.addText(badgeText.toUpperCase(), {
      x: 0.8, y: 0.45, w: 3.6, h: 0.38,
      fontSize: 10, bold: true, color: C.cyan,
      align: 'center', valign: 'middle', fontFace: 'Segoe UI'
    });
  }

  // Título Principal
  slide.addText(titleText, {
    x: 0.8, y: 0.9, w: 11.7, h: 0.65,
    fontSize: 24, bold: true, color: C.white,
    fontFace: 'Segoe UI'
  });

  // Subtítulo
  if (subtitleText) {
    slide.addText(subtitleText, {
      x: 0.8, y: 1.55, w: 11.7, h: 0.4,
      fontSize: 13, color: C.grayMuted,
      fontFace: 'Segoe UI'
    });
  }

  // Rodapé padrão
  slide.addShape(pres.shapes.LINE, {
    x: 0.8, y: 7.0, w: 11.733, h: 0,
    line: { color: C.cardBorder, width: 0.8 }
  });
  slide.addText('PLAYER99 • APRESENTAÇÃO ESTRATÉGICA PARA INVESTIDORES', {
    x: 0.8, y: 7.05, w: 7.0, h: 0.35,
    fontSize: 9, color: C.grayMuted, fontFace: 'Segoe UI'
  });
  slide.addText('CONFIDENCIAL • SMART MONEY', {
    x: 8.5, y: 7.05, w: 4.0, h: 0.35,
    fontSize: 9, bold: true, color: C.cyan, align: 'right', fontFace: 'Segoe UI'
  });
}

// ==========================================
// SLIDE 1: CAPA (TITLE SLIDE)
// ==========================================
{
  const slide = pres.addSlide();
  slide.background = { color: C.bgDark };

  // Linha superior
  slide.addShape(pres.shapes.RECTANGLE, {
    x: 0, y: 0, w: 13.333, h: 0.12,
    fill: { color: C.cyan },
    line: { color: C.cyan }
  });

  // Badge Topo
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 0.8, w: 4.2, h: 0.4,
    fill: { color: C.badgeBg },
    line: { color: C.cyan, width: 1.2 },
    rectRadius: 0.1
  });
  slide.addText('RODADA ESTRATÉGICA • SMART MONEY', {
    x: 0.8, y: 0.8, w: 4.2, h: 0.4,
    fontSize: 11, bold: true, color: C.cyan,
    align: 'center', valign: 'middle', fontFace: 'Segoe UI'
  });

  // Título e Logo
  if (fs.existsSync(imgLogo)) {
    slide.addImage({ path: imgLogo, x: 0.8, y: 1.4, w: 2.2, h: 2.2 });
  }

  slide.addText('PLAYER99', {
    x: 3.2, y: 1.6, w: 5.0, h: 0.9,
    fontSize: 44, bold: true, color: C.cyan,
    fontFace: 'Segoe UI'
  });

  slide.addText('A Nova Fronteira da Mídia DOOH em Carros de Aplicativo', {
    x: 3.2, y: 2.5, w: 5.5, h: 0.9,
    fontSize: 19, bold: true, color: C.white,
    fontFace: 'Segoe UI'
  });

  slide.addText(
    'A plataforma SaaS que transforma veículos de passageiros (Uber / 99) em vitrines digitais interativas de alta rentabilidade para motoristas e anunciantes locais.',
    {
      x: 0.8, y: 3.7, w: 7.2, h: 0.9,
      fontSize: 13, color: C.grayLight, lineSpacing: 20, fontFace: 'Segoe UI'
    }
  );

  // Cards de Destaque na Capa
  const metrics = [
    { label: 'Assinatura SaaS', value: 'R$ 99 /mês', color: C.cyan },
    { label: 'Mercado Potencial', value: '+1.5M Carros', color: C.orange },
    { label: 'Status da Plataforma', value: '100% Pronta', color: C.green }
  ];

  metrics.forEach((m, idx) => {
    const xPos = 0.8 + idx * 2.5;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: 4.8, w: 2.3, h: 1.4,
      fill: { color: C.cardDark },
      line: { color: C.cardBorder, width: 1 },
      rectRadius: 0.12
    });
    slide.addText(m.label, {
      x: xPos, y: 4.95, w: 2.3, h: 0.35,
      fontSize: 11, color: C.grayMuted, align: 'center', fontFace: 'Segoe UI'
    });
    slide.addText(m.value, {
      x: xPos, y: 5.35, w: 2.3, h: 0.6,
      fontSize: 18, bold: true, color: m.color, align: 'center', fontFace: 'Segoe UI'
    });
  });

  // Imagem do Tablet à Direita com moldura estilizada
  if (fs.existsSync(imgTablet)) {
    // Moldura do Device
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 8.8, y: 0.7, w: 3.7, h: 5.8,
      fill: { color: '050608' },
      line: { color: C.cyan, width: 2 },
      rectRadius: 0.25
    });
    // Imagem do tablet em ação (POV Pizzaria)
    slide.addImage({
      path: imgTablet,
      x: 9.0, y: 0.9, w: 3.3, h: 5.4
    });
  }

  // Footer da capa
  slide.addShape(pres.shapes.LINE, {
    x: 0.8, y: 6.8, w: 7.2, h: 0,
    line: { color: C.cardBorder, width: 1 }
  });
  slide.addText('Buscamos Investidor Estratégico com Expertise em Tráfego, Escala e Vendas Online.', {
    x: 0.8, y: 6.9, w: 7.2, h: 0.4,
    fontSize: 11, bold: true, color: C.cyan, fontFace: 'Segoe UI'
  });
}

// ==========================================
// SLIDE 2: O CENÁRIO & A GRANDE OPORTUNIDADE
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'O Tamanho da Oportunidade',
    'Uma Audiência Cativa de Milhões de Pessoas Todos os Dias',
    'O transporte por aplicativo transformou o deslocamento urbano, mas o espaço interno do veículo continuava subutilizado.'
  );

  const pillars = [
    {
      num: '1.5M+',
      title: 'Motoristas Ativos no Brasil',
      desc: 'Mais de 1,5 milhão de motoristas circulam pelas ruas todos os dias na Uber, 99 e InDrive, buscando urgentemente formas de aumentar seu ganho líquido por corrida.',
      accent: C.cyan
    },
    {
      num: '25 min',
      title: 'Atenção 100% Cativa',
      desc: 'Tempo médio de permanência do passageiro sentado no banco de trás, olhando diretamente para a altura do encosto. Sem ad-block, sem concorrência de outras abas.',
      accent: C.orange
    },
    {
      num: '+25%',
      title: 'Crescimento Anual do DOOH',
      desc: 'A mídia Digital Out-Of-Home é o segmento de publicidade física que mais cresce no mundo, impulsionado pelo consumo visual dinâmico e geolocalizado.',
      accent: C.green
    }
  ];

  pillars.forEach((p, idx) => {
    const xPos = 0.8 + idx * 4.0;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: 2.1, w: 3.7, h: 3.6,
      fill: { color: C.cardDark },
      line: { color: C.cardBorder, width: 1.2 },
      rectRadius: 0.15
    });

    // Barra de topo no card
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: 2.1, w: 3.7, h: 0.1,
      fill: { color: p.accent },
      line: { color: p.accent },
      rectRadius: 0.05
    });

    slide.addText(p.num, {
      x: xPos + 0.3, y: 2.4, w: 3.1, h: 0.7,
      fontSize: 32, bold: true, color: p.accent, fontFace: 'Segoe UI'
    });

    slide.addText(p.title, {
      x: xPos + 0.3, y: 3.15, w: 3.1, h: 0.5,
      fontSize: 15, bold: true, color: C.white, fontFace: 'Segoe UI'
    });

    slide.addText(p.desc, {
      x: xPos + 0.3, y: 3.75, w: 3.1, h: 1.7,
      fontSize: 12, color: C.grayLight, lineSpacing: 18, fontFace: 'Segoe UI'
    });
  });

  // Callout inferior
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 5.95, w: 11.733, h: 0.85,
    fill: { color: C.badgeBg },
    line: { color: C.cyan, width: 1 },
    rectRadius: 0.1
  });
  slide.addText(
    '💡 O INSIGHT CHAVE: O interior do veículo é o metro quadrado de mídia mais nobre e inexplorado da cidade. Um passageiro entediado com poder de consumo a centímetros de uma tela interativa.',
    {
      x: 1.1, y: 6.05, w: 11.1, h: 0.65,
      fontSize: 12.5, bold: true, color: C.white, fontFace: 'Segoe UI'
    }
  );
}

// ==========================================
// SLIDE 3: O PROBLEMA REAL DO MERCADO
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'As Dores Reais',
    'Dois Lados com Problemas Críticos. Nenhuma Solução Simples.',
    'A conta do motorista não fecha mais com tranquilidade, e o comércio local sofre para anunciar com resultado.'
  );

  // Card 1: Motoristas
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 2.1, w: 5.65, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: 'E53E3E', width: 1.5 }, // Borda avermelhada/alerta
    rectRadius: 0.15
  });

  slide.addText('🔴 A DOR DO MOTORISTA DE APLICATIVO', {
    x: 1.1, y: 2.3, w: 5.0, h: 0.4,
    fontSize: 13, bold: true, color: 'FC8181', fontFace: 'Segoe UI'
  });

  const motoristaPains = [
    'Combustível e manutenção batendo recordes históricos, corroendo o ganho líquido.',
    'Taxas das plataformas (Uber / 99) variando de 25% a 40%, reduzindo o valor por km.',
    'Limite físico e biológico: para pagar as contas, rodam 12h a 14h por dia até a exaustão.',
    'Falta de fontes alternativas: o carro roda milhares de km todo mês sem gerar nada além da corrida seca.'
  ];

  motoristaPains.forEach((p, idx) => {
    slide.addText('✕  ' + p, {
      x: 1.1, y: 2.8 + idx * 0.75, w: 5.0, h: 0.65,
      fontSize: 11.5, color: C.grayLight, lineSpacing: 16, fontFace: 'Segoe UI'
    });
  });

  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 1.1, y: 5.85, w: 5.0, h: 0.65,
    fill: { color: '2D1515' },
    line: { color: 'E53E3E', width: 1 },
    rectRadius: 0.08
  });
  slide.addText('Necessidade urgente de renda extra passiva sem precisar rodar mais horas.', {
    x: 1.2, y: 5.9, w: 4.8, h: 0.55,
    fontSize: 11, bold: true, color: 'FC8181', align: 'center', fontFace: 'Segoe UI'
  });

  // Card 2: Comércio Local
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 6.85, y: 2.1, w: 5.65, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: C.orange, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('🟠 A DOR DO COMÉRCIO LOCAL & REGIONAL', {
    x: 7.15, y: 2.3, w: 5.0, h: 0.4,
    fontSize: 13, bold: true, color: C.orangeLight, fontFace: 'Segoe UI'
  });

  const comercioPains = [
    'Anúncios no Meta Ads e Google estão cada vez mais caros, saturados e complexos.',
    'Tráfego desperdiçado: pequenas lojas pagam por cliques de pessoas de cidades ou bairros distantes que nunca consumirão.',
    'Panfletos e folhetos impressos vão direto para o lixo sem gerar retenção de marca.',
    'Outdoors convencionais custam caro (R$ 2.000 a R$ 5.000) e não são segmentados por rota ou público cativo.'
  ];

  comercioPains.forEach((p, idx) => {
    slide.addText('✕  ' + p, {
      x: 7.15, y: 2.8 + idx * 0.75, w: 5.0, h: 0.65,
      fontSize: 11.5, color: C.grayLight, lineSpacing: 16, fontFace: 'Segoe UI'
    });
  });

  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 7.15, y: 5.85, w: 5.0, h: 0.65,
    fill: { color: '2D1F15' },
    line: { color: C.orange, width: 1 },
    rectRadius: 0.08
  });
  slide.addText('Precisam de publicidade hiperlocal, visual e acessível no raio exato do seu negócio.', {
    x: 7.25, y: 5.9, w: 4.8, h: 0.55,
    fontSize: 11, bold: true, color: C.orangeLight, align: 'center', fontFace: 'Segoe UI'
  });
}

// ==========================================
// SLIDE 4: A SOLUÇÃO PLAYER99 (O GANCHO)
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'A Nossa Solução',
    'Player99: A Franquia de Mídia de Bolso para o Motorista',
    'Uma plataforma SaaS simples e poderosa que une a fome do motorista com a vontade do anunciante.'
  );

  const steps = [
    {
      step: '01',
      title: 'Tablet & Instalação em 5 Minutos',
      desc: 'O motorista coloca qualquer tablet Android no suporte do encosto de cabeça do banco e baixa o aplicativo Player99. Conexão instantânea via QR Code.',
      color: C.cyan
    },
    {
      step: '02',
      title: 'Assinatura Simbólica de R$ 99/mês',
      desc: 'Um valor acessível que cabe no bolso de qualquer profissional. Sem carência, sem contratos draconianos, barreira de entrada virtualmente zero.',
      color: C.purple
    },
    {
      step: '03',
      title: 'Captação de Anunciantes do Bairro',
      desc: 'O motorista vende cotas de anúncios para a pizzaria da esquina, barbearia, academia, clínica odontológica e autopeças da sua cidade.',
      color: C.orange
    },
    {
      step: '04',
      title: 'Lucro Líquido Todo Mês no Bolso',
      desc: 'Com 5 a 10 anunciantes pagando R$ 100 a R$ 200 cada, o motorista lucra de R$ 800 a R$ 1.500+ livres, pagando apenas R$ 99 para o Player99.',
      color: C.green
    }
  ];

  steps.forEach((s, idx) => {
    const yPos = 2.1 + idx * 1.15;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 0.8, y: yPos, w: 7.4, h: 1.05,
      fill: { color: C.cardDark },
      line: { color: C.cardBorder, width: 1.2 },
      rectRadius: 0.1
    });

    // Número
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 1.0, y: yPos + 0.18, w: 0.7, h: 0.7,
      fill: { color: C.badgeBg },
      line: { color: s.color, width: 1.5 },
      rectRadius: 0.08
    });
    slide.addText(s.step, {
      x: 1.0, y: yPos + 0.18, w: 0.7, h: 0.7,
      fontSize: 14, bold: true, color: s.color, align: 'center', valign: 'middle', fontFace: 'Segoe UI'
    });

    slide.addText(s.title, {
      x: 1.85, y: yPos + 0.12, w: 6.2, h: 0.35,
      fontSize: 13, bold: true, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(s.desc, {
      x: 1.85, y: yPos + 0.45, w: 6.2, h: 0.55,
      fontSize: 10.5, color: C.grayLight, lineSpacing: 14, fontFace: 'Segoe UI'
    });
  });

  // Imagem Vertical / Mockup à direita
  if (fs.existsSync(imgVertical)) {
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 8.6, y: 2.1, w: 3.9, h: 4.65,
      fill: { color: '050608' },
      line: { color: C.cyan, width: 2 },
      rectRadius: 0.2
    });
    slide.addImage({
      path: imgVertical,
      x: 8.75, y: 2.25, w: 3.6, h: 4.35
    });
  }
}

// ==========================================
// SLIDE 5: A MATEMÁTICA IRRECUSÁVEL DO MOTORISTA
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'A Proposta Irrecusável (No-Brainer)',
    'Por Que a Aceitação é Explosiva: O ROI do Motorista',
    'Uma oferta onde o cliente ganha de 8x a 15x o valor investido logo no primeiro mês gera retenção máxima.'
  );

  // Card Esquerda: Tabela de Ganhos do Motorista
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 2.1, w: 6.8, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: C.cyan, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('SIMULAÇÃO REAL DE LUCRO POR VEÍCULO', {
    x: 1.1, y: 2.3, w: 6.2, h: 0.35,
    fontSize: 13, bold: true, color: C.cyan, fontFace: 'Segoe UI'
  });

  // Linhas da simulação
  const rows = [
    { item: 'Custo da Assinatura Player99', val: '- R$ 99,00 /mês', color: 'FC8181' },
    { item: 'Captação Mínima: 6 Anunciantes x R$ 150/mês', val: '+ R$ 900,00 /mês', color: C.white },
    { item: 'Lucro Líquido no Bolso (Cenário Conservador)', val: '+ R$ 801,00 LIVRES', color: C.green, bold: true },
    { item: 'Captação Moderada: 10 Anunciantes x R$ 150/mês', val: '+ R$ 1.500,00 /mês', color: C.white },
    { item: 'Lucro Líquido no Bolso (Cenário Otimista)', val: '+ R$ 1.401,00 LIVRES', color: C.green, bold: true }
  ];

  rows.forEach((r, idx) => {
    const yLine = 2.8 + idx * 0.72;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: 1.1, y: yLine, w: 6.2, h: 0.6,
      fill: { color: r.bold ? '132A20' : C.badgeBg },
      line: { color: r.bold ? C.green : C.cardBorder, width: r.bold ? 1.5 : 1 },
      rectRadius: 0.08
    });
    slide.addText(r.item, {
      x: 1.25, y: yLine + 0.12, w: 4.0, h: 0.35,
      fontSize: 11, bold: r.bold || false, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(r.val, {
      x: 5.1, y: yLine + 0.12, w: 2.05, h: 0.35,
      fontSize: 11.5, bold: true, color: r.color, align: 'right', fontFace: 'Segoe UI'
    });
  });

  // Card Direita: As Consequências para o Negócio
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 7.85, y: 2.1, w: 4.65, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: C.cardBorder, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('O IMPACTO NA NOSSA EMPRESA', {
    x: 8.1, y: 2.3, w: 4.15, h: 0.35,
    fontSize: 13, bold: true, color: C.orange, fontFace: 'Segoe UI'
  });

  const businessImpacts = [
    {
      title: 'Churn Praticamente Zero',
      desc: 'Nenhum motorista cancela uma mensalidade de R$ 99 que coloca R$ 1.000 líquidos no bolso dele todo mês.'
    },
    {
      title: 'Efeito Viral em Comunidades',
      desc: 'Motoristas mostram o tablet para outros colegas nos pontos de apoio e grupos de WhatsApp. O produto se vende sozinho.'
    },
    {
      title: 'Barreira de Entrada Ridícula',
      desc: 'Qualquer tablet usado de R$ 300 a R$ 400 já roda o app perfeitamente, pagando-se no primeiro mês de uso.'
    }
  ];

  businessImpacts.forEach((bi, idx) => {
    const yPos = 2.8 + idx * 1.2;
    slide.addText('✓ ' + bi.title, {
      x: 8.1, y: yPos, w: 4.15, h: 0.3,
      fontSize: 12.5, bold: true, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(bi.desc, {
      x: 8.35, y: yPos + 0.3, w: 3.9, h: 0.75,
      fontSize: 10.5, color: C.grayLight, lineSpacing: 15, fontFace: 'Segoe UI'
    });
  });
}

// ==========================================
// SLIDE 6: O MODELO DE NEGÓCIO SAAS & ESCALA
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'Unit Economics & Projeções',
    'Receita Recorrente Previsível (MRR) de Software Puro',
    'Margem bruta superior a 85% e escalabilidade sem necessidade de comprar tablets ou gerenciar frotas físicas.'
  );

  const tiers = [
    {
      cars: '1.000 Carros',
      pct: '0,06% do Brasil',
      mrr: 'R$ 99.000',
      arr: 'R$ 1.188.000 /ano',
      badge: 'TRAÇÃO INICIAL',
      color: C.cyan
    },
    {
      cars: '5.000 Carros',
      pct: '0,33% do Brasil',
      mrr: 'R$ 495.000',
      arr: 'R$ 5.940.000 /ano',
      badge: 'EXPANSÃO REGIONAL',
      color: C.purple
    },
    {
      cars: '20.000 Carros',
      pct: '1,33% do Brasil',
      mrr: 'R$ 1.980.000',
      arr: 'R$ 23.760.000 /ano',
      badge: 'LIDERANÇA NACIONAL',
      color: C.orange
    },
    {
      cars: '50.000 Carros',
      pct: '3,33% do Brasil',
      mrr: 'R$ 4.950.000',
      arr: 'R$ 59.400.000 /ano',
      badge: 'PENETRAÇÃO CONSOLIDADA',
      color: C.green
    }
  ];

  tiers.forEach((t, idx) => {
    const xPos = 0.8 + idx * 3.0;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: 2.1, w: 2.75, h: 3.6,
      fill: { color: C.cardDark },
      line: { color: t.color, width: 1.5 },
      rectRadius: 0.15
    });

    // Badge de fase
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos + 0.2, y: 2.3, w: 2.35, h: 0.35,
      fill: { color: C.badgeBg },
      line: { color: t.color, width: 1 },
      rectRadius: 0.06
    });
    slide.addText(t.badge, {
      x: xPos + 0.2, y: 2.3, w: 2.35, h: 0.35,
      fontSize: 9, bold: true, color: t.color, align: 'center', valign: 'middle', fontFace: 'Segoe UI'
    });

    slide.addText(t.cars, {
      x: xPos + 0.15, y: 2.8, w: 2.45, h: 0.45,
      fontSize: 18, bold: true, color: C.white, align: 'center', fontFace: 'Segoe UI'
    });
    slide.addText(t.pct, {
      x: xPos + 0.15, y: 3.25, w: 2.45, h: 0.3,
      fontSize: 10, color: C.grayMuted, align: 'center', fontFace: 'Segoe UI'
    });

    slide.addShape(pres.shapes.LINE, {
      x: xPos + 0.3, y: 3.7, w: 2.15, h: 0,
      line: { color: C.cardBorder, width: 1 }
    });

    slide.addText('MRR (Mensal)', {
      x: xPos + 0.15, y: 3.85, w: 2.45, h: 0.25,
      fontSize: 9.5, color: C.grayMuted, align: 'center', fontFace: 'Segoe UI'
    });
    slide.addText(t.mrr, {
      x: xPos + 0.15, y: 4.1, w: 2.45, h: 0.45,
      fontSize: 17, bold: true, color: t.color, align: 'center', fontFace: 'Segoe UI'
    });

    slide.addText('ARR (Anual)', {
      x: xPos + 0.15, y: 4.65, w: 2.45, h: 0.25,
      fontSize: 9.5, color: C.grayMuted, align: 'center', fontFace: 'Segoe UI'
    });
    slide.addText(t.arr, {
      x: xPos + 0.15, y: 4.9, w: 2.45, h: 0.45,
      fontSize: 13, bold: true, color: C.white, align: 'center', fontFace: 'Segoe UI'
    });
  });

  // Callout Estratégico Internacional
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 5.95, w: 11.733, h: 0.85,
    fill: { color: C.badgeBg },
    line: { color: C.cyan, width: 1.2 },
    rectRadius: 0.1
  });
  slide.addText(
    '🌍 ESCALA GLOBAL: O modelo é 100% digital e sem barreiras geográficas. A mesma infraestrutura opera no Brasil, Colômbia, México, Argentina, Portugal e EUA com adaptação de moeda.',
    {
      x: 1.1, y: 6.05, w: 11.1, h: 0.65,
      fontSize: 12, bold: true, color: C.white, fontFace: 'Segoe UI'
    }
  );
}

// ==========================================
// SLIDE 7: TECNOLOGIA & PRODUTO 100% PRONTO
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'Execução Comprovada',
    'Não é Apenas um Conceito: A Plataforma Já Está Pronta',
    'Todo o ecossistema tecnológico foi construído, testado e validado. Foco total em tração comercial.'
  );

  const techs = [
    {
      title: 'APK Android Nativo Otimizado',
      features: [
        'Cache local de mídia: roda vídeos e banners mesmo sem internet.',
        'Boot automático: liga o app assim que o carro é ligado.',
        'Transições leves e fluidas sem travamentos no tablet.',
        'Pareamento instantâneo via código e QR Code de ativação.'
      ],
      icon: '📱',
      color: C.cyan
    },
    {
      title: 'Painel Central SaaS na Nuvem',
      features: [
        'Gestão centralizada de telas, frotas e motoristas por cidade.',
        'Módulo de agendamento de campanhas e rotação de anunciantes.',
        'Relatórios de visualizações e métricas de exibição.',
        'Multi-usuário: motorista tem acesso restrito às suas telas.'
      ],
      icon: '☁️',
      color: C.purple
    },
    {
      title: 'Faturamento Automatizado (PIX)',
      features: [
        'Integração nativa Mercado Pago Gold com QR Code dinâmico.',
        'Baixa automática do pagamento em menos de 2 segundos.',
        'Cron de renovação e emissão de cobranças automáticas.',
        'Bloqueio automático de telas inadimplentes sem desgaste manual.'
      ],
      icon: '⚡',
      color: C.orange
    },
    {
      title: 'Automação WhatsApp & CRM',
      features: [
        'Integração via Evolution API para avisos no WhatsApp.',
        'Envio automático de código PIX e lembretes de vencimento.',
        'Comunicação direta com o motorista para aumentar retenção.',
        'Canal de suporte ágil integrado ao ecossistema.'
      ],
      icon: '💬',
      color: C.green
    }
  ];

  techs.forEach((t, idx) => {
    const col = idx % 2;
    const row = Math.floor(idx / 2);
    const xPos = 0.8 + col * 5.95;
    const yPos = 2.1 + row * 2.35;

    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: yPos, w: 5.75, h: 2.2,
      fill: { color: C.cardDark },
      line: { color: C.cardBorder, width: 1.2 },
      rectRadius: 0.12
    });

    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos + 0.25, y: yPos + 0.2, w: 0.5, h: 0.5,
      fill: { color: C.badgeBg },
      line: { color: t.color, width: 1 },
      rectRadius: 0.08
    });
    slide.addText(t.icon, {
      x: xPos + 0.25, y: yPos + 0.2, w: 0.5, h: 0.5,
      fontSize: 16, align: 'center', valign: 'middle'
    });

    slide.addText(t.title, {
      x: xPos + 0.9, y: yPos + 0.22, w: 4.6, h: 0.4,
      fontSize: 13, bold: true, color: C.white, fontFace: 'Segoe UI'
    });

    t.features.forEach((f, fIdx) => {
      slide.addText('• ' + f, {
        x: xPos + 0.4, y: yPos + 0.75 + fIdx * 0.33, w: 5.1, h: 0.35,
        fontSize: 10, color: C.grayLight, fontFace: 'Segoe UI'
      });
    });
  });
}

// ==========================================
// SLIDE 8: A BUSCA POR INVESTIDOR SMART MONEY
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'Parceria Estratégica',
    'Buscamos Sócio com Domínio em Marketing Digital e Vendas',
    'A tecnologia está pronta e validada. Buscamos um parceiro com força de tração para dominar o Brasil com velocidade.'
  );

  // Coluna 1: O Que Nós Entregamos
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 2.1, w: 5.65, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: C.cyan, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('O QUE A PLAYER99 JÁ ENTREGA', {
    x: 1.1, y: 2.3, w: 5.0, h: 0.35,
    fontSize: 13, bold: true, color: C.cyan, fontFace: 'Segoe UI'
  });

  const weHave = [
    { t: 'Plataforma 100% Funcional', d: 'Software pronto, sem meses de espera para começar a vender.' },
    { t: 'Margem Bruta Elevadíssima (>85%)', d: 'Modelo SaaS puro com custo marginal quase zero por novo carro.' },
    { t: 'Engenharia de Software Própria', d: 'Capacidade de atualização rápida, novos recursos e segurança.' },
    { t: 'Modelo Econômico Validado', d: 'Oferta com ROI irresistível para os motoristas parceiros.' }
  ];

  weHave.forEach((item, idx) => {
    const yLine = 2.8 + idx * 0.95;
    slide.addText('✓ ' + item.t, {
      x: 1.1, y: yLine, w: 5.0, h: 0.3,
      fontSize: 12, bold: true, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(item.d, {
      x: 1.35, y: yLine + 0.3, w: 4.8, h: 0.55,
      fontSize: 10.5, color: C.grayLight, lineSpacing: 15, fontFace: 'Segoe UI'
    });
  });

  // Coluna 2: O Que Buscamos no Investidor Parceiro
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 6.85, y: 2.1, w: 5.65, h: 4.6,
    fill: { color: C.cardDark },
    line: { color: C.orange, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('O QUE O SÓCIO ESTRATÉGICO TRAZ', {
    x: 7.15, y: 2.3, w: 5.0, h: 0.35,
    fontSize: 13, bold: true, color: C.orange, fontFace: 'Segoe UI'
  });

  const partnerNeeds = [
    { t: 'Tráfego Pago de Alta Performance', d: 'Domínio de Meta Ads, TikTok Ads e Google para aquisição em massa de motoristas.' },
    { t: 'Copywriting & Funis de Conversão', d: 'Construção de narrativas sedutoras que convertem motoristas em assinantes recorrentes.' },
    { t: 'Growth Hacking & Viralidade', d: 'Criação de programas de indicação e parcerias com influenciadores do nicho.' },
    { t: 'Visão de Escala Comercial', d: 'Estratégia para capturar 10.000 a 50.000 veículos antes do surgimento de concorrentes.' }
  ];

  partnerNeeds.forEach((item, idx) => {
    const yLine = 2.8 + idx * 0.95;
    slide.addText('★ ' + item.t, {
      x: 7.15, y: yLine, w: 5.0, h: 0.3,
      fontSize: 12, bold: true, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(item.d, {
      x: 7.4, y: yLine + 0.3, w: 4.8, h: 0.55,
      fontSize: 10.5, color: C.grayLight, lineSpacing: 15, fontFace: 'Segoe UI'
    });
  });
}

// ==========================================
// SLIDE 9: PLANO DE GO-TO-MARKET & TRAÇÃO RÁPIDA
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'Plano de Expansão',
    'Roadmap de Crescimento: Rumo aos Primeiros 10.000 Carros',
    'Um plano de execução pragmático focado em retorno rápido sobre investimento em tráfego.'
  );

  const phases = [
    {
      phase: 'FASE 1: MÊS 1 A 3',
      title: 'Validação & Funil Direto',
      badge: 'Meta: 1.000 Carros (R$ 99k MRR)',
      bullets: [
        'Tráfego pago direto segmentado para motoristas Uber/99 em SP, RJ e capitais polo.',
        'Oferta do "Kit Anunciante Pronto": entregamos ao motorista os modelos de contrato e abordagem para fechar seus primeiros anúncios em 48h.',
        'Otimização contínua do custo por aquisição (CAC).'
      ],
      color: C.cyan
    },
    {
      phase: 'FASE 2: MÊS 4 A 6',
      title: 'Comunidades & Influenciadores',
      badge: 'Meta: 5.000 Carros (R$ 495k MRR)',
      bullets: [
        'Campanha com YouTubers e TikTokers gigantes que falam diretamente com motoristas de aplicativo.',
        'Lançamento do programa oficial de afiliados e indicação (indique 3 colegas e zere a sua assinatura).',
        'Criação de comunidades locais de motoristas Player99.'
      ],
      color: C.purple
    },
    {
      phase: 'FASE 3: MÊS 7 A 12',
      title: 'Frotas & Internacionalização',
      badge: 'Meta: 15.000+ Carros (R$ 1.48M MRR)',
      bullets: [
        'Parcerias corporativas com locadoras de veículos (Localiza, Unidas, Movida) e cooperativas de táxi.',
        'Abertura de canal B2B para grandes anunciantes nacionais comprarem cotas em rede.',
        'Início do piloto de expansão para América Latina (Colômbia e México).'
      ],
      color: C.green
    }
  ];

  phases.forEach((p, idx) => {
    const xPos = 0.8 + idx * 4.0;
    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos, y: 2.1, w: 3.7, h: 4.6,
      fill: { color: C.cardDark },
      line: { color: p.color, width: 1.5 },
      rectRadius: 0.15
    });

    slide.addText(p.phase, {
      x: xPos + 0.25, y: 2.3, w: 3.2, h: 0.3,
      fontSize: 10, bold: true, color: p.color, fontFace: 'Segoe UI'
    });

    slide.addText(p.title, {
      x: xPos + 0.25, y: 2.65, w: 3.2, h: 0.45,
      fontSize: 16, bold: true, color: C.white, fontFace: 'Segoe UI'
    });

    slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
      x: xPos + 0.25, y: 3.2, w: 3.2, h: 0.4,
      fill: { color: C.badgeBg },
      line: { color: p.color, width: 1 },
      rectRadius: 0.06
    });
    slide.addText(p.badge, {
      x: xPos + 0.25, y: 3.2, w: 3.2, h: 0.4,
      fontSize: 9.5, bold: true, color: C.white, align: 'center', valign: 'middle', fontFace: 'Segoe UI'
    });

    p.bullets.forEach((b, bIdx) => {
      slide.addText('▸ ' + b, {
        x: xPos + 0.25, y: 3.8 + bIdx * 0.95, w: 3.2, h: 0.85,
        fontSize: 10.5, color: C.grayLight, lineSpacing: 15, fontFace: 'Segoe UI'
      });
    });
  });
}

// ==========================================
// SLIDE 10: CONCLUSÃO & O CONVITE AOS SÓCIOS
// ==========================================
{
  const slide = pres.addSlide();
  addSlideHeader(
    slide,
    'Oportunidade Única',
    'Vamos Construir a Maior Rede de Telas Sobre Rodas do Brasil?',
    'A oportunidade existe hoje. Quem dominar a atenção veicular primeiro definirá os padrões do setor.'
  );

  // Box Principal Central
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 2.1, w: 11.733, h: 2.6,
    fill: { color: C.cardDark },
    line: { color: C.cyan, width: 2 },
    rectRadius: 0.15
  });

  slide.addText('POR QUE ENTRAR NESSE PROJETO AGORA?', {
    x: 1.2, y: 2.35, w: 11.0, h: 0.4,
    fontSize: 14, bold: true, color: C.cyan, fontFace: 'Segoe UI'
  });

  const closingPoints = [
    { t: '1. O Produto Não é Promessa:', d: 'Plataforma, APK, cobrança e sistema operacional estão 100% prontos para rodar amanhã.' },
    { t: '2. Demanda Reprimida Brutal:', d: 'Milhões de motoristas precisam pagar suas contas e adoram a proposta de ganhar R$ 1.000 a mais por mês.' },
    { t: '3. Modelo SaaS com Alto Múltiplo:', d: 'Negócios de receita recorrente com alta margem e churn baixo têm valorizações milionárias de mercado.' }
  ];

  closingPoints.forEach((cp, idx) => {
    slide.addText(cp.t, {
      x: 1.2, y: 2.85 + idx * 0.55, w: 3.5, h: 0.4,
      fontSize: 12, bold: true, color: C.white, fontFace: 'Segoe UI'
    });
    slide.addText(cp.d, {
      x: 4.8, y: 2.85 + idx * 0.55, w: 7.3, h: 0.45,
      fontSize: 11.5, color: C.grayLight, fontFace: 'Segoe UI'
    });
  });

  // CTA Box Inferior
  slide.addShape(pres.shapes.ROUNDED_RECTANGLE, {
    x: 0.8, y: 4.95, w: 11.733, h: 1.75,
    fill: { color: '0E232B' },
    line: { color: C.cyan, width: 1.5 },
    rectRadius: 0.15
  });

  slide.addText('PRÓXIMO PASSO: REUNIÃO DE ALINHAMENTO ESTRATÉGICO', {
    x: 1.2, y: 5.15, w: 11.0, h: 0.35,
    fontSize: 13, bold: true, color: C.orange, fontFace: 'Segoe UI'
  });

  slide.addText(
    'Demonstração prática da plataforma ao vivo, alinhamento de equity, definição de verba de teste para o primeiro funil e plano para alcançar os primeiros 1.000 assinantes.',
    {
      x: 1.2, y: 5.5, w: 11.0, h: 0.55,
      fontSize: 12, color: C.white, lineSpacing: 18, fontFace: 'Segoe UI'
    }
  );

  slide.addText('Equipe Fundadora Player99 • Contato Direto & Demonstração', {
    x: 1.2, y: 6.15, w: 11.0, h: 0.35,
    fontSize: 11, bold: true, color: C.cyan, fontFace: 'Segoe UI'
  });
}

// Salvar a apresentação
const outputPath = path.resolve('Player99_Pitch_Deck_Investidores.pptx');
pres.writeFile({ fileName: outputPath })
  .then(f => {
    console.log('SUCCESS: Apresentação gerada com sucesso em: ' + f);
  })
  .catch(err => {
    console.error('ERROR: ', err);
  });
