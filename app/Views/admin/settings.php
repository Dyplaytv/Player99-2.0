<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa fa-sliders-h text-danger me-2"></i> Painel de Configurações Root & Agente I.A.</h4>
            <p class="text-muted mb-0">Gerencie credenciais de APIs, WhatsApp Evolution e teste a comunicação em tempo real de cada serviço.</p>
        </div>
        <div>
            <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2">
                <i class="fa fa-shield-alt me-1"></i> Acesso Restrito a Superadmins
            </span>
        </div>
    </div>

    <?= $msg ?? '' ?>

    <form method="POST" id="settingsForm">
        <!-- 1. WhatsApp Evolution API & QR Code -->
        <div class="card bg-dark border-secondary border-opacity-25 mb-4">
            <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-success"><i class="fab fa-whatsapp me-2"></i> WhatsApp & Evolution API (Conexão Oficial)</h6>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="checkEvolutionStatus()">
                        <i class="fa fa-sync-alt me-1"></i> Checar Conexão
                    </button>
                    <button type="button" class="btn btn-sm btn-success" onclick="connectEvolution()">
                        <i class="fa fa-qrcode me-1"></i> Gerar QR Code
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="disconnectEvolution()">
                        <i class="fa fa-power-off me-1"></i> Desconectar
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="statusBadgeContainer" class="mb-3">
                    <span id="evoStatusBadge" class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50 px-3 py-2">
                        <i class="fa fa-circle me-1" style="font-size: 8px;"></i> Status: Clique em 'Checar Conexão'
                    </span>
                </div>

                <!-- Box Modal QR Code -->
                <div id="qrCodeWrapper" class="p-3 mb-3 bg-black rounded-3 text-center d-none border border-success border-opacity-50">
                    <h6 class="text-white fw-bold mb-2">Escaneie com o WhatsApp da Empresa</h6>
                    <p class="text-muted small mb-3">Abra o WhatsApp > Aparelhos Conectados > Conectar um aparelho e aponte para o código abaixo:</p>
                    <div id="qrCodeImgHolder" class="d-flex justify-content-center mb-2"></div>
                    <small class="text-info font-monospace" id="qrCodeTimer">Aguardando leitura...</small>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-md-4">
                        <label class="form-label text-muted small fw-bold">URL DA EVOLUTION API</label>
                        <input type="text" name="evolution_api_url" id="evolution_api_url" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['evolution_api_url'] ?? '') ?>" placeholder="http://172.17.0.1:8095">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label text-muted small fw-bold">API KEY GLOBAL</label>
                        <input type="password" name="evolution_api_key" id="evolution_api_key" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['evolution_api_key'] ?? '') ?>" placeholder="Sua chave secreta da Evolution">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label text-muted small fw-bold">NOME DA INSTÂNCIA</label>
                        <input type="text" name="evolution_instance_name" id="evolution_instance_name" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['evolution_instance_name'] ?? 'player99') ?>">
                    </div>
                </div>

                <div class="mt-3 p-2 rounded bg-black bg-opacity-40 border border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <small class="text-muted">
                        <i class="fa fa-info-circle text-info me-1"></i> <b>URL do Webhook do Agente I.A.:</b>
                        <code class="text-info ms-1"><?= htmlspecialchars($webhookUrl ?? ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'teste.player99.com.br') . "/api/evolution/webhook")) ?></code>
                    </small>
                    <small class="text-success"><i class="fa fa-bolt me-1"></i> Webhook + Verificação Ativa Automática habilitados</small>
                </div>
            </div>
        </div>

        <!-- 2. Agente de I.A. Humanizado & Provedores de IA -->
        <div class="card bg-dark border-secondary border-opacity-25 mb-4">
            <div class="card-header bg-dark border-secondary border-opacity-25 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0 fw-bold text-warning"><i class="fa fa-robot me-2"></i> Agente de Inteligência Artificial & Provedores de IA</h6>
                <div class="d-flex align-items-center gap-3">
                    <span id="autoSaveBadge" class="small text-info" style="display:none;"></span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="ai_agent_enabled" value="1" id="aiAgentEnabled" <?= ($settings['ai_agent_enabled'] ?? '0') === '1' ? 'checked' : '' ?> onchange="autoSaveAiSettings(false)">
                        <label class="form-check-label text-white fw-bold small" for="aiAgentEnabled">Ativar Agente no WhatsApp</label>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Configure os provedores de I.A. e teste a comunicação direta com cada serviço antes de colocar em produção.
                </p>

                <!-- Linha 1: Configuração do Agente -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold">MOTOR DE I.A. PRINCIPAL</label>
                        <select name="ai_provider" id="ai_provider" class="form-select bg-dark text-white border-secondary" onchange="autoSaveAiSettings(false)">
                            <option value="gemini" <?= ($settings['ai_provider'] ?? '') === 'gemini' ? 'selected' : '' ?>>Google Gemini Flash (Rápido & Econômico)</option>
                            <option value="openai" <?= ($settings['ai_provider'] ?? 'openai') === 'openai' ? 'selected' : '' ?>>OpenAI ChatGPT (GPT-4o-mini)</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold">DELAY DE DIGITAÇÃO HUMANA</label>
                        <div class="input-group">
                            <input type="number" name="ai_human_delay" id="ai_human_delay" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['ai_human_delay'] ?? '3') ?>" min="1" max="10" onchange="autoSaveAiSettings(false)">
                            <span class="input-group-text bg-dark text-muted border-secondary">segundos</span>
                        </div>
                        <small class="text-muted" style="font-size: 11px;">Simula presença "digitando..." no WhatsApp para parecer um atendimento natural.</small>
                    </div>
                </div>

                <!-- Linha 2: Chaves das IAs em Grid Organizado -->
                <div class="row g-4 mb-4">
                    <!-- Gemini API -->
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-muted small fw-bold d-block mb-1">GOOGLE GEMINI API KEY</label>
                            <div class="input-group mb-2">
                                <input type="password" name="gemini_api_key" id="gemini_api_key" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['gemini_api_key'] ?? '') ?>" placeholder="AIzaSy...">
                                <button type="button" class="btn btn-outline-info" onclick="testService('gemini')">
                                    <i class="fa fa-plug me-1"></i> Testar
                                </button>
                            </div>
                            <div id="badge_gemini" class="small text-muted py-1">
                                <i class="fa fa-info-circle me-1"></i> Status: Aguardando teste
                            </div>
                        </div>
                    </div>

                    <!-- OpenAI API -->
                    <div class="col-12 col-md-6">
                        <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-muted small fw-bold d-block mb-1">OPENAI API KEY (CHATGPT)</label>
                            <div class="input-group mb-2">
                                <input type="password" name="openai_api_key" id="openai_api_key" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['openai_api_key'] ?? '') ?>" placeholder="sk-proj-...">
                                <button type="button" class="btn btn-outline-info" onclick="testService('openai')">
                                    <i class="fa fa-plug me-1"></i> Testar
                                </button>
                            </div>
                            <div id="badge_openai" class="small text-muted py-1 text-break">
                                <i class="fa fa-info-circle me-1"></i> Status: Aguardando teste
                            </div>
                        </div>
                    </div>

                    <!-- Claude API -->
                    <div class="col-12">
                        <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25">
                            <label class="form-label text-muted small fw-bold d-block mb-1">ANTHROPIC CLAUDE API KEY (OPCIONAL)</label>
                            <input type="password" name="anthropic_api_key" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['anthropic_api_key'] ?? '') ?>" placeholder="sk-ant-...">
                        </div>
                    </div>
                </div>

                <!-- Prompt de Treinamento -->
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold d-flex justify-content-between flex-wrap gap-2">
                        <span>PROMPT DE TREINAMENTO DO AGENTE (PERSONA & DIRETRIZES DO ATENDIMENTO)</span>
                        <span class="text-info fw-normal" style="font-size: 12px;"><i class="fa fa-sparkles me-1"></i> Treinamento customizado</span>
                    </label>
                    <textarea name="ai_system_prompt" id="ai_system_prompt" rows="8" class="form-control bg-dark text-white border-secondary font-monospace" style="font-size: 13px; line-height: 1.5;"><?= htmlspecialchars($settings['ai_system_prompt'] ?? '') ?></textarea>
                </div>

                <!-- Botões de Ação Rápida do Agente + Simulador e Monitor -->
                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between p-3 rounded-3 bg-black bg-opacity-40 border border-secondary border-opacity-25 mb-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-cyan" onclick="autoSaveAiSettings(true)">
                            <i class="fa fa-save me-1"></i> Salvar & Sincronizar Agente Agora
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="simulateAiReply()">
                            <i class="fa fa-comment-dots me-1"></i> Simular Resposta da I.A.
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="pollWhatsAppNow(true)">
                            <i class="fab fa-whatsapp me-1"></i> Verificar Mensagens Recebidas Agora
                        </button>
                    </div>
                    <small class="text-muted" id="pollStatusLabel"><i class="fa fa-sync fa-spin me-1"></i> Monitorando WhatsApp automaticamente...</small>
                </div>

                <!-- Resultado da Simulação -->
                <div id="aiSimulationBox" class="p-3 rounded-3 bg-black border border-warning border-opacity-50 mb-3" style="display:none;">
                    <div class="small text-warning fw-bold mb-1"><i class="fa fa-robot me-1"></i> Resposta Gerada pelo Agente I.A.:</div>
                    <div id="aiSimulationText" class="text-white small" style="white-space: pre-wrap;"></div>
                </div>

                <!-- Monitor de Mensagens Processadas -->
                <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-info"><i class="fa fa-history me-1"></i> Últimas Mensagens Recebidas e Respondidas pelo Agente</span>
                        <span class="badge bg-dark border border-secondary text-muted" style="font-size:11px;">Atualização em tempo real</span>
                    </div>
                    <div id="aiRecentLogs" class="small text-muted">
                        Carregando histórico de atendimentos...
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Mercado Pago & Clima com Botões de Teste -->
        <div class="card bg-dark border-secondary border-opacity-25 mb-4">
            <div class="card-header bg-dark border-secondary border-opacity-25">
                <h6 class="mb-0 fw-bold text-info"><i class="fa fa-credit-card me-2"></i> Mercado Pago, Google OAuth & Clima</h6>
            </div>
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="google_login_enabled" value="1" id="googleEnabled" <?= ($settings['google_login_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <label class="form-check-label text-white" for="googleEnabled">Ativar botão "Entrar com Conta Google" na tela de login</label>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold">GOOGLE CLIENT ID</label>
                        <input type="text" name="google_client_id" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['google_client_id'] ?? '') ?>" placeholder="123456...apps.googleusercontent.com">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label text-muted small fw-bold">GOOGLE CLIENT SECRET</label>
                        <input type="password" name="google_client_secret" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['google_client_secret'] ?? '') ?>" placeholder="GOCSPX-...">
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Mercado Pago com Teste -->
                    <div class="col-12 col-md-7">
                        <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-muted small fw-bold d-block mb-1">MERCADO PAGO ACCESS TOKEN (PIX AUTOMÁTICO)</label>
                            <div class="input-group mb-2">
                                <input type="password" name="mercadopago_access_token" id="mercadopago_access_token" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['mercadopago_access_token'] ?? '') ?>" placeholder="APP_USR-...">
                                <button type="button" class="btn btn-outline-info" onclick="testService('mercadopago')">
                                    <i class="fa fa-plug me-1"></i> Testar
                                </button>
                            </div>
                            <div id="badge_mercadopago" class="small text-muted py-1 text-break">
                                <i class="fa fa-info-circle me-1"></i> Status: Aguardando teste
                            </div>
                        </div>
                    </div>

                    <!-- OpenWeather com Teste -->
                    <div class="col-12 col-md-5">
                        <div class="p-3 rounded-3 bg-black bg-opacity-30 border border-secondary border-opacity-25 h-100">
                            <label class="form-label text-muted small fw-bold d-block mb-1">OPENWEATHER API KEY (CLIMA)</label>
                            <div class="input-group mb-2">
                                <input type="password" name="openweather_api_key" id="openweather_api_key" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($settings['openweather_api_key'] ?? '') ?>">
                                <button type="button" class="btn btn-outline-info" onclick="testService('weather')">
                                    <i class="fa fa-plug me-1"></i> Testar
                                </button>
                            </div>
                            <div id="badge_weather" class="small text-muted py-1 text-break">
                                <i class="fa fa-info-circle me-1"></i> Status: Aguardando teste
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-cyan btn-lg px-5">
            <i class="fa fa-save me-2"></i> Salvar Todas as Configurações & Treinamento
        </button>
    </form>
</div>

<script>
function autoSaveAiSettings(showAlert) {
    const form = document.getElementById('settingsForm');
    if (!form) return;
    const badge = document.getElementById('autoSaveBadge');
    if (badge) {
        badge.style.display = 'inline';
        badge.className = 'small text-warning';
        badge.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Salvando e sincronizando...';
    }

    const fd = new FormData(form);
    fd.append('ajax', '1');

    fetch('/admin/configuracoes', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (badge && res.ok) {
            badge.className = 'small text-success fw-bold';
            badge.innerHTML = '<i class="fa fa-check-circle me-1"></i> Salvo & Sincronizado!';
            setTimeout(() => { badge.style.display = 'none'; }, 4000);
        }
        if (showAlert && res.ok) {
            pollWhatsAppNow(true);
        }
    })
    .catch(() => {
        if (badge) {
            badge.className = 'small text-danger';
            badge.innerHTML = '<i class="fa fa-times-circle me-1"></i> Erro ao salvar';
        }
    });
}

function testService(serviceName) {
    const badge = document.getElementById('badge_' + serviceName);
    if (!badge) return;

    badge.className = 'small text-warning py-1';
    badge.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Testando comunicação e saldo da API...';

    let val = '';
    if (serviceName === 'gemini') val = document.getElementById('gemini_api_key').value;
    if (serviceName === 'openai') val = document.getElementById('openai_api_key').value;
    if (serviceName === 'mercadopago') val = document.getElementById('mercadopago_access_token').value;
    if (serviceName === 'weather') val = document.getElementById('openweather_api_key').value;

    fetch('/admin/configuracoes/test-api?api=' + encodeURIComponent(serviceName) + '&val=' + encodeURIComponent(val))
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                badge.className = 'small text-success py-1 fw-bold';
                badge.innerHTML = '<i class="fa fa-check-circle me-1"></i> ' + res.msg;
                autoSaveAiSettings(false);
            } else {
                badge.className = 'small text-danger py-1 text-break';
                badge.innerHTML = '<i class="fa fa-times-circle me-1"></i> ' + res.msg;
            }
        })
        .catch(() => {
            badge.className = 'small text-danger py-1';
            badge.innerHTML = '<i class="fa fa-times me-1"></i> Erro na requisição';
        });
}

function simulateAiReply() {
    const box = document.getElementById('aiSimulationBox');
    const txt = document.getElementById('aiSimulationText');
    if (!box || !txt) return;

    box.style.display = 'block';
    txt.innerHTML = '<span class="text-warning"><i class="fa fa-spinner fa-spin me-1"></i> Gerando resposta de teste com o motor selecionado...</span>';

    const fd = new FormData();
    fd.append('api', 'ai_simulation');
    fd.append('provider', document.getElementById('ai_provider').value);
    fd.append('openai_key', document.getElementById('openai_api_key').value);
    fd.append('gemini_key', document.getElementById('gemini_api_key').value);
    fd.append('prompt', document.getElementById('ai_system_prompt').value);
    fd.append('question', 'Olá! Sou motorista de aplicativo e quero saber como funciona para colocar a tela da Player99 no meu carro.');

    fetch('/admin/configuracoes/test-api', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            txt.className = 'text-success small fw-bold';
            txt.innerText = res.msg;
        } else {
            txt.className = 'text-danger small fw-bold';
            txt.innerText = 'Erro ao gerar resposta: ' + res.msg;
        }
    })
    .catch(() => {
        txt.className = 'text-danger small';
        txt.innerText = 'Falha na comunicação com o servidor.';
    });
}

function pollWhatsAppNow(force) {
    const lbl = document.getElementById('pollStatusLabel');
    if (lbl && force) {
        lbl.innerHTML = '<i class="fa fa-spinner fa-spin text-info me-1"></i> Consultando mensagens na Evolution API...';
    }

    fetch('/api/evolution/poll' + (force ? '?force=1' : ''))
        .then(r => r.json())
        .then(data => {
            if (lbl) {
                lbl.innerHTML = '<i class="fa fa-check-circle text-success me-1"></i> Verificado às ' + new Date().toLocaleTimeString();
            }
            const container = document.getElementById('aiRecentLogs');
            if (!container) return;

            const logs = data.recent_logs || [];
            if (logs.length === 0) {
                container.innerHTML = '<span class="text-muted">Nenhuma mensagem recebida ainda após ativação do Agente. Envie um "Olá" de outro WhatsApp para testar!</span>';
                return;
            }

            let html = '<div class="table-responsive"><table class="table table-dark table-sm table-bordered mb-0" style="font-size:12px;">';
            html += '<thead><tr><th>Horário</th><th>Contato</th><th>Mensagem Recebida</th><th>Resposta da I.A. / Status</th></tr></thead><tbody>';
            logs.forEach(row => {
                let statusBadge = '';
                if (row.status === 'replied') {
                    statusBadge = '<span class="badge bg-success me-1">Respondido</span> ' + (row.ai_reply || '');
                } else if (row.status === 'processing') {
                    statusBadge = '<span class="badge bg-warning text-dark">Processando...</span>';
                } else {
                    statusBadge = '<span class="badge bg-danger me-1">Erro (' + (row.status || '') + ')</span> ' + (row.error_msg || '');
                }
                html += '<tr>';
                html += '<td class="text-nowrap text-muted">' + (row.created_at || '') + '</td>';
                html += '<td class="text-info">' + (row.remote_jid || '').replace('@s.whatsapp.net', '') + '</td>';
                html += '<td>' + (row.user_text || '') + '</td>';
                html += '<td>' + statusBadge + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table></div>';
            container.innerHTML = html;
        })
        .catch(() => {});
}

function getEvolutionPayload() {
    return {
        url: document.getElementById('evolution_api_url').value.trim(),
        key: document.getElementById('evolution_api_key').value.trim(),
        instance: document.getElementById('evolution_instance_name').value.trim()
    };
}

function checkEvolutionStatus() {
    const badge = document.getElementById('evoStatusBadge');
    badge.className = 'badge bg-warning bg-opacity-25 text-warning border border-warning border-opacity-50 px-3 py-2';
    badge.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Verificando conexão...';

    const payload = getEvolutionPayload();
    fetch('/admin/configuracoes/evolution-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(res => {
            const state = res.data?.instance?.state || res.data?.state || 'close';
            if (state === 'open') {
                badge.className = 'badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-2';
                badge.innerHTML = '<i class="fa fa-check-circle me-1"></i> Conectado e Operacional (WhatsApp Online)';
                document.getElementById('qrCodeWrapper').classList.add('d-none');
            } else if (state === 'connecting') {
                badge.className = 'badge bg-info bg-opacity-25 text-info border border-info border-opacity-50 px-3 py-2';
                badge.innerHTML = '<i class="fa fa-sync fa-spin me-1"></i> Conectando...';
            } else {
                badge.className = 'badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2';
                badge.innerHTML = '<i class="fa fa-times-circle me-1"></i> Desconectado (' + state + ') - Clique em Gerar QR Code';
            }
        })
        .catch(() => {
            badge.className = 'badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2';
            badge.innerHTML = '<i class="fa fa-exclamation-triangle me-1"></i> Erro ao consultar servidor Evolution';
        });
}

function connectEvolution() {
    const wrapper = document.getElementById('qrCodeWrapper');
    const holder = document.getElementById('qrCodeImgHolder');
    const timer = document.getElementById('qrCodeTimer');

    wrapper.classList.remove('d-none');
    holder.innerHTML = '<div class="text-info py-4"><i class="fa fa-spinner fa-spin fa-2x"></i><p class="mt-2 mb-0">Solicitando QR Code à Evolution...</p></div>';

    const payload = getEvolutionPayload();
    fetch('/admin/configuracoes/evolution-connect', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
        .then(res => res.json())
        .then(res => {
            if (res.data?.base64 || res.data?.qrcode?.base64) {
                const b64 = res.data.base64 || res.data.qrcode.base64;
                holder.innerHTML = '<img src="' + b64 + '" style="max-width: 260px; border-radius: 8px; border: 4px solid #fff;" alt="QR Code">';
                timer.innerText = 'QR Code gerado! Aponte o WhatsApp para conectar.';

                const interval = setInterval(() => {
                    fetch('/admin/configuracoes/evolution-status', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    })
                        .then(r => r.json())
                        .then(st => {
                            const state = st.data?.instance?.state || st.data?.state;
                            if (state === 'open') {
                                clearInterval(interval);
                                checkEvolutionStatus();
                                alert('WhatsApp Conectado com Sucesso!');
                            }
                        });
                }, 4000);
            } else if (res.data?.instance?.state === 'open') {
                holder.innerHTML = '<div class="alert alert-success m-0"><i class="fa fa-check-circle me-1"></i> Este WhatsApp já está conectado!</div>';
                checkEvolutionStatus();
            } else {
                holder.innerHTML = '<div class="alert alert-danger m-0">' + (res.error || 'Não foi possível gerar o QR Code. Verifique a URL e a API Key.') + '</div>';
            }
        })
        .catch(() => {
            holder.innerHTML = '<div class="alert alert-danger m-0">Falha na conexão com a Evolution API.</div>';
        });
}

function disconnectEvolution() {
    if (!confirm('Deseja realmente desconectar o WhatsApp da Player99?')) return;

    fetch('/admin/configuracoes/evolution-logout')
        .then(res => res.json())
        .then(() => {
            alert('Instância desconectada.');
            checkEvolutionStatus();
        });
}

document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('evolution_api_url').value.trim() !== '') {
        checkEvolutionStatus();
    }
    pollWhatsAppNow(false);
    setInterval(() => pollWhatsAppNow(false), 6000);
});
</script>
