<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <span class="badge bg-info bg-opacity-15 text-info border border-info border-opacity-25 font-monospace px-3 py-1 mb-2">
            CÓDIGO DO PLAYER: <?= htmlspecialchars($screen['player_code']) ?>
        </span>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($screen['title'] ?: 'Tela #' . $screen['id']) ?></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="/player/<?= htmlspecialchars($screen['player_code']) ?>" target="_blank" class="btn btn-cyan btn-sm fw-bold">
            <i class="fa fa-play me-1"></i> Testar Player ao Vivo
        </a>
        <a href="/admin/telas" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-arrow-left me-1"></i> Voltar para Lista
        </a>
    </div>
</div>

<?= $msg ?? '' ?>

<div class="row g-4">
    <!-- Coluna Esquerda: Upload de Mídias e Configurações Rápidas -->
    <div class="col-12 col-lg-5">
        <!-- 1. Enviar Vídeo / Anúncio -->
        <div class="glass-card mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-cloud-upload-alt text-info"></i> Adicionar Vídeo ou Anúncio
            </h5>
            <div id="chunkUploadAlert" class="d-none mb-3"></div>
            <form id="mediaUploadForm" method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Título da Campanha / Anunciante</label>
                    <input type="text" id="mediaTitleInput" name="media_title" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Comercial Restaurante Sabor" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Arquivo de Mídia (MP4, WEBM, JPG, PNG)</label>
                    <input type="file" id="mediaFileInput" name="media_file" class="form-control bg-dark text-white border-secondary" accept="video/mp4,video/webm,image/*" required>
                    <div class="form-text text-muted small mt-1">
                        Envio automático em blocos de 1.5 MB para evitar timeout em VTs grandes.
                    </div>
                </div>
                <div id="chunkUploadProgressWrap" class="d-none mb-3">
                    <div class="d-flex justify-content-between small text-muted mb-1">
                        <span id="chunkUploadStatusText">Preparando envio...</span>
                        <span id="chunkUploadPercentText" class="fw-bold text-info">0%</span>
                    </div>
                    <div class="progress bg-dark border border-secondary" style="height: 10px;">
                        <div id="chunkUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%;"></div>
                    </div>
                </div>
                <button type="submit" id="mediaUploadSubmitBtn" class="btn btn-primary w-100 fw-bold py-2">
                    <i class="fa fa-plus me-1"></i> Enviar para a Tela
                </button>
            </form>
        </div>

        <!-- 2. Configurações Técnicas da Tela -->
        <div class="glass-card">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-sliders-h text-warning"></i> Ajustes Remotos da Tela
            </h5>
            <form method="POST">
                <input type="hidden" name="action" value="update_settings">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nome de Identificação</label>
                    <input type="text" name="title" class="form-control bg-dark text-white border-secondary" value="<?= htmlspecialchars($screen['title'] ?? '') ?>" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">Rotação</label>
                        <select name="rotation" class="form-select bg-dark text-white border-secondary">
                            <option value="0" <?= (int)($screen['rotation'] ?? 0) === 0 ? 'selected' : '' ?>>0° (Paisagem)</option>
                            <option value="90" <?= (int)($screen['rotation'] ?? 0) === 90 ? 'selected' : '' ?>>90° (Vertical)</option>
                            <option value="180" <?= (int)($screen['rotation'] ?? 0) === 180 ? 'selected' : '' ?>>180° (Invertido)</option>
                            <option value="270" <?= (int)($screen['rotation'] ?? 0) === 270 ? 'selected' : '' ?>>270° (Vertical)</option>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label text-muted small fw-bold">Modo de Áudio</label>
                        <select name="audio_mode" class="form-select bg-dark text-white border-secondary">
                            <option value="video" <?= ($screen['audio_mode'] ?? 'video') === 'video' ? 'selected' : '' ?>>Com Áudio</option>
                            <option value="mute" <?= ($screen['audio_mode'] ?? '') === 'mute' ? 'selected' : '' ?>>Mudo</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn btn-outline-info w-100 fw-bold py-2">
                    <i class="fa fa-sync-alt me-1"></i> Salvar e Atualizar Player Remotamente
                </button>
            </form>
        </div>
    </div>

    <!-- Coluna Direita: Grade de Programação em Exibição -->
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-film text-success"></i> Grade de Mídias em Loop
                </h5>
                <span class="badge bg-secondary bg-opacity-25 border border-secondary border-opacity-50 px-3 py-1">
                    <?= count($videos) ?> mídia(s) na fila
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Prévia</th>
                            <th>Campanha / Arquivo</th>
                            <th class="text-center" style="width: 100px;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($videos)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">
                                    <i class="fa fa-photo-video fa-3x mb-2 d-block opacity-25"></i>
                                    Nenhum vídeo ou imagem nesta tela ainda.<br>
                                    Envie pelo formulário ao lado para iniciar a transmissão.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($videos as $v): 
                                $ext = strtolower(pathinfo($v['filename'], PATHINFO_EXTENSION));
                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                $mediaUrl = '/uploads/clientes/' . $clientId . '/' . htmlspecialchars($v['filename']);
                            ?>
                                <tr>
                                    <td style="width: 130px;">
                                        <div style="width: 110px; height: 65px; border-radius: 8px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                                            <?php if ($isImg): ?>
                                                <img src="<?= $mediaUrl ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <video src="<?= $mediaUrl ?>" style="width: 100%; height: 100%; object-fit: cover;" muted loop onmouseover="this.play()" onmouseout="this.pause()"></video>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($v['title'] ?: $v['filename']) ?></div>
                                        <span class="badge bg-dark border border-secondary text-muted small mt-1">
                                            <?= strtoupper($ext) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Remover esta mídia da programação?');" class="m-0">
                                            <input type="hidden" name="delete_media" value="<?= (int)$v['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const form = document.getElementById('mediaUploadForm');
    if (!form || typeof fetch === 'undefined') return;

    const fileInput = document.getElementById('mediaFileInput');
    const titleInput = document.getElementById('mediaTitleInput');
    const submitBtn = document.getElementById('mediaUploadSubmitBtn');
    const progressWrap = document.getElementById('chunkUploadProgressWrap');
    const progressBar = document.getElementById('chunkUploadProgressBar');
    const statusText = document.getElementById('chunkUploadStatusText');
    const percentText = document.getElementById('chunkUploadPercentText');
    const alertBox = document.getElementById('chunkUploadAlert');

    const CHUNK_SIZE = 1536 * 1024; // 1.5 MB por bloco (abaixo de qualquer limite 2M/50M e timeout 60s)
    const CHUNK_ENDPOINT = '/admin/telas/<?= (int)$screen['id'] ?>/midias/chunk';
    const REDIRECT_URL = '/admin/telas/<?= (int)$screen['id'] ?>/midias?msg=uploaded';

    function showError(msg) {
        alertBox.className = 'alert alert-danger d-flex align-items-center gap-2 mb-3';
        alertBox.innerHTML = '<i class="fa fa-times-circle"></i><span></span>';
        alertBox.querySelector('span').textContent = msg;
    }

    function formatMb(bytes) {
        return (bytes / 1048576).toFixed(1) + ' MB';
    }

    async function sendChunkWithRetry(formData, attempts) {
        let lastErr = null;
        for (let i = 0; i < attempts; i++) {
            try {
                const resp = await fetch(CHUNK_ENDPOINT, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                const text = await resp.text();
                let data = null;
                try {
                    data = JSON.parse(text);
                } catch (_) {
                    throw new Error('Resposta inesperada do servidor (HTTP ' + resp.status + ').');
                }
                if (!resp.ok || !data || !data.success) {
                    throw new Error((data && data.message) ? data.message : ('Erro HTTP ' + resp.status));
                }
                return data;
            } catch (err) {
                lastErr = err;
                if (i < attempts - 1) {
                    await new Promise(r => setTimeout(r, 800 * (i + 1)));
                }
            }
        }
        throw lastErr || new Error('Falha no envio do bloco.');
    }

    form.addEventListener('submit', async function (e) {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;

        e.preventDefault();
        alertBox.className = 'd-none mb-3';
        alertBox.textContent = '';

        const totalChunks = Math.max(1, Math.ceil(file.size / CHUNK_SIZE));
        if (totalChunks > 512) {
            showError('Arquivo muito grande (limite máximo de ~768 MB por VT).');
            return;
        }

        const uploadId = 'up_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
        const mediaTitle = (titleInput.value || '').trim() || file.name;

        submitBtn.disabled = true;
        fileInput.disabled = true;
        titleInput.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Enviando...';
        progressWrap.classList.remove('d-none');
        progressBar.style.width = '0%';
        percentText.textContent = '0%';
        statusText.textContent = 'Iniciando envio (' + formatMb(file.size) + ')...';

        try {
            for (let idx = 0; idx < totalChunks; idx++) {
                const start = idx * CHUNK_SIZE;
                const end = Math.min(file.size, start + CHUNK_SIZE);
                const blob = file.slice(start, end);

                const fd = new FormData();
                fd.append('upload_id', uploadId);
                fd.append('chunk_index', String(idx));
                fd.append('total_chunks', String(totalChunks));
                fd.append('filename', file.name);
                fd.append('media_title', mediaTitle);
                fd.append('chunk', blob, file.name + '.part');

                statusText.textContent = 'Enviando parte ' + (idx + 1) + ' de ' + totalChunks + ' (' + formatMb(end) + ' / ' + formatMb(file.size) + ')';
                await sendChunkWithRetry(fd, 3);

                const pct = Math.round(((idx + 1) / totalChunks) * 100);
                progressBar.style.width = pct + '%';
                percentText.textContent = pct + '%';
            }

            statusText.textContent = 'Finalizando e salvando mídia...';
            window.location.href = REDIRECT_URL;
        } catch (err) {
            showError(err && err.message ? err.message : 'Erro ao enviar arquivo.');
            submitBtn.disabled = false;
            fileInput.disabled = false;
            titleInput.disabled = false;
            submitBtn.innerHTML = '<i class="fa fa-plus me-1"></i> Enviar para a Tela';
        }
    });
})();
</script>
