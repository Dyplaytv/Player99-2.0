<?php
/** @var string $docType */
/** @var string $pageTitle */
/** @var string $pageDescription */
$isPrivacy = ($docType ?? 'privacidade') === 'privacidade';
$host = $_SERVER['HTTP_HOST'] ?? 'player99.com.br';
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')) ? 'https' : 'http';
$siteUrl = $scheme . '://' . $host;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?= \App\Core\View::logoUrl() ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 0;
            background-color: #0f1015;
            color: #e2e8f0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.7;
        }
        .glass-nav {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 950px;
            background: rgba(15, 16, 21, 0.85);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 40px;
            padding: 8px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }
        .glass-nav .logo-img { max-height: 38px; width: auto; object-fit: contain; }
        .glass-nav .nav-links { display: flex; align-items: center; gap: 12px; }
        .glass-nav .nav-btn {
            color: #ffffff;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            transition: all 0.25s ease;
        }
        .glass-nav .nav-btn:hover { background: rgba(255, 255, 255, 0.12); color: #34e2e4; }
        .glass-nav .nav-btn.current { color: #34e2e4; background: rgba(52, 226, 228, 0.12); }

        .legal-wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 120px 20px 60px;
        }
        .legal-card {
            background: rgba(18, 20, 30, 0.92);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: 3px solid #34e2e4;
            border-radius: 20px;
            padding: 36px 32px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
        }
        .legal-badge {
            display: inline-block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #34e2e4;
            background: rgba(52, 226, 228, 0.12);
            border: 1px solid rgba(52, 226, 228, 0.3);
            padding: 4px 12px;
            border-radius: 20px;
            margin-bottom: 12px;
        }
        h1 {
            margin: 0 0 8px;
            font-size: 2rem;
            color: #ffffff;
        }
        .legal-meta {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 28px;
            padding-bottom: 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        h2 {
            color: #34e2e4;
            font-size: 1.15rem;
            margin: 26px 0 10px;
        }
        p, li { color: #cbd5e1; font-size: 0.96rem; }
        ul { padding-left: 20px; }
        a.inline-link { color: #34e2e4; text-decoration: none; }
        a.inline-link:hover { text-decoration: underline; }
        .doc-switch {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .doc-switch a {
            padding: 10px 18px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #fff;
            background: rgba(255, 255, 255, 0.05);
        }
        .doc-switch a:hover {
            border-color: #34e2e4;
            color: #34e2e4;
        }
        footer {
            background-color: #0a0c10;
            padding: 25px 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
            color: #8e9bb0;
            font-size: 14px;
        }
        footer a { color: #34e2e4; text-decoration: none; margin: 0 8px; }
        footer a:hover { text-decoration: underline; }

        @media (max-width: 850px) {
            .glass-nav { flex-direction: column; gap: 10px; padding: 12px; border-radius: 20px; top: 10px; }
            .glass-nav .nav-links { flex-wrap: wrap; justify-content: center; gap: 6px; }
            .legal-wrapper { padding-top: 155px; }
            .legal-card { padding: 24px 18px; }
            h1 { font-size: 1.55rem; }
        }
    </style>
</head>
<body>

    <nav class="glass-nav">
        <a href="/">
            <img src="<?= \App\Core\View::logoUrl() ?>" alt="Player99" class="logo-img" onerror="this.src='/assets/images/logo.png'">
        </a>
        <div class="nav-links">
            <a href="/" class="nav-btn">Início</a>
            <a href="/privacidade" class="nav-btn <?= $isPrivacy ? 'current' : '' ?>">Privacidade</a>
            <a href="/termos" class="nav-btn <?= !$isPrivacy ? 'current' : '' ?>">Termos de Serviço</a>
            <a href="/login" class="nav-btn" style="background: linear-gradient(135deg, #0A38D1, #34e2e4); border-radius: 25px;">Entrar</a>
        </div>
    </nav>

    <main class="legal-wrapper">
        <div class="legal-card">
            <?php if ($isPrivacy): ?>
                <span class="legal-badge">LGPD &amp; Google OAuth</span>
                <h1>Política de Privacidade — Player99</h1>
                <div class="legal-meta">
                    Última atualização: <?= date('d/m/Y') ?> &bull; Plataforma: <a class="inline-link" href="<?= htmlspecialchars($siteUrl) ?>"><?= htmlspecialchars($host) ?></a>
                </div>

                <p>
                    A plataforma <strong>Player99</strong> respeita a sua privacidade e trata os dados pessoais em conformidade com a Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018). Esta Política descreve como coletamos, utilizamos e protegemos suas informações ao acessar o site, os painéis administrativos/clientes e o login integrado com o Google.
                </p>

                <h2>1. Dados coletados</h2>
                <ul>
                    <li><strong>Dados cadastrais:</strong> nome completo ou razão social, nome de usuário, e-mail, número de WhatsApp/telefone e informações de identificação de telas/players vinculados à conta.</li>
                    <li><strong>Autenticação via Google (OAuth 2.0):</strong> quando você escolhe <em>"Entrar com Google"</em>, solicitamos apenas os escopos básicos de identidade (<code>openid</code>, <code>email</code> e <code>profile</code>), recebendo seu nome, status de verificação do e-mail e endereço de e-mail exclusivamente para localizar e autenticar sua conta na Player99.</li>
                    <li><strong>Dados operacionais e mídias:</strong> vídeos, imagens, playlists, registros de acesso/sessão e status de conectividade dos players vinculados ao usuário.</li>
                    <li><strong>Dados de pagamento:</strong> identificadores de pedidos e status de transações processadas por provedores terceiros (como Mercado Pago), sem armazenar dados completos de cartão de crédito em nossos servidores.</li>
                </ul>

                <h2>2. Finalidade do uso dos dados</h2>
                <ul>
                    <li>Autenticar usuários, motoristas parceiros, franqueados e administradores com segurança.</li>
                    <li>Gerenciar telas de mídia indoor (DOOH), playlists, validades, créditos e campanhas publicitárias.</li>
                    <li>Enviar notificações operacionais, avisos de vencimento, confirmações de pedidos e suporte técnico.</li>
                    <li>Cumprir obrigações legais, prevenir fraudes e manter backups de integridade do sistema.</li>
                </ul>

                <h2>3. Uso e proteção de dados do Google</h2>
                <p>
                    Os dados obtidos por meio do Login do Google são utilizados <strong>estritamente para autenticação e identificação da conta</strong> dentro da plataforma Player99. Não vendemos, alugamos nem compartilhamos seus dados do Google com terceiros para fins publicitários ou de rastreamento externo, e não solicitamos acesso permanente off-line ao seu e-mail ou arquivos do Google.
                </p>

                <h2>4. Compartilhamento e armazenamento</h2>
                <p>
                    As informações podem ser processadas apenas por serviços essenciais à operação contratada (infraestrutura de hospedagem, gateway de pagamento Mercado Pago e notificações via WhatsApp quando aplicável). Adotamos controles de acesso por perfil, sessões protegidas, criptografia de senhas e backups periódicos com retenção limitada.
                </p>

                <h2>5. Cookies e sessões</h2>
                <p>
                    Utilizamos cookies estritamente necessários para manter sua sessão autenticada, proteger o fluxo de login (parâmetro <code>state</code> anti-CSRF no OAuth) e preservar preferências de navegação e código de indicação.
                </p>

                <h2>6. Direitos do titular e contato</h2>
                <p>
                    Você pode solicitar a confirmação, atualização, exportação ou exclusão dos seus dados pessoais a qualquer momento pelo e-mail <a class="inline-link" href="mailto:player99.pix@gmail.com">player99.pix@gmail.com</a> ou pelos canais de atendimento oficiais dentro do painel.
                </p>
            <?php else: ?>
                <span class="legal-badge">Condições de Uso</span>
                <h1>Termos de Serviço — Player99</h1>
                <div class="legal-meta">
                    Última atualização: <?= date('d/m/Y') ?> &bull; Plataforma: <a class="inline-link" href="<?= htmlspecialchars($siteUrl) ?>"><?= htmlspecialchars($host) ?></a>
                </div>

                <p>
                    Ao acessar ou utilizar a plataforma <strong>Player99</strong> (<code><?= htmlspecialchars($host) ?></code>), seus aplicativos de exibição (Player TV / PWA), painel do motorista/cliente, portal do franqueado ou loja virtual, você concorda integralmente com estes Termos de Serviço.
                </p>

                <h2>1. Objeto do serviço</h2>
                <p>
                    A Player99 oferece uma plataforma SaaS para gestão e transmissão de mídia indoor / DOOH (Digital Out-of-Home), gerenciamento de telas, playlists de vídeos/imagens, widgets informativos, controle de créditos/assinaturas e vitrine comercial integrada.
                </p>

                <h2>2. Cadastro, acesso e autenticação</h2>
                <ul>
                    <li>O usuário deve fornecer informações verídicas e manter suas credenciais em sigilo.</li>
                    <li>O acesso pode ser realizado por usuário/e-mail e senha ou via <strong>Login com Google</strong>, desde que o e-mail esteja previamente autorizado ou cadastrado na plataforma.</li>
                    <li>Contas utilizadas em desacordo com estes Termos ou com pagamentos expirados podem ter a exibição de telas suspensa ou bloqueada até regularização.</li>
                </ul>

                <h2>3. Conteúdos e responsabilidades do usuário</h2>
                <ul>
                    <li>O usuário é integralmente responsável pelos vídeos, imagens, áudios, anúncios e links exibidos em suas telas, declarando possuir os direitos de imagem e propriedade intelectual necessários.</li>
                    <li>É proibido publicar ou veicular conteúdo ilícito, ofensivo, enganoso, que viole direitos autorais de terceiros ou a legislação vigente.</li>
                </ul>

                <h2>4. Planos, créditos e pagamentos</h2>
                <p>
                    A ativação de telas, renovações, créditos e compras na loja seguem os valores e prazos exibidos no momento da contratação. Pagamentos digitais são processados via intermediadores autorizados (como Mercado Pago), sendo a liberação efetuada após confirmação do pagamento.
                </p>

                <h2>5. Disponibilidade e limitações</h2>
                <p>
                    Empregamos boas práticas de segurança, backups e monitoramento para manter a plataforma disponível continuamente. Eventuais interrupções por manutenção programada, falhas de internet local do dispositivo player ou indisponibilidade de serviços externos serão tratadas com prioridade técnica, sem garantia de funcionamento ininterrupto em caso de força maior.
                </p>

                <h2>6. Alterações e contato</h2>
                <p>
                    Estes Termos podem ser atualizados a qualquer momento mediante publicação nesta página. Dúvidas ou solicitações podem ser enviadas para <a class="inline-link" href="mailto:player99.pix@gmail.com">player99.pix@gmail.com</a>.
                </p>
            <?php endif; ?>

            <div class="doc-switch">
                <a href="/privacidade"><i class="fas fa-user-shield" style="margin-right:6px;"></i>Política de Privacidade</a>
                <a href="/termos"><i class="fas fa-file-contract" style="margin-right:6px;"></i>Termos de Serviço</a>
                <a href="/login"><i class="fas fa-sign-in-alt" style="margin-right:6px;"></i>Ir para Login</a>
                <a href="/"><i class="fas fa-home" style="margin-right:6px;"></i>Voltar ao Início</a>
            </div>
        </div>
    </main>

    <footer>
        <p style="margin: 0 0 8px; font-weight: 500;">player99 2.6</p>
        <div>
            <a href="/privacidade">Política de Privacidade</a> &bull;
            <a href="/termos">Termos de Serviço</a>
        </div>
    </footer>

</body>
</html>
