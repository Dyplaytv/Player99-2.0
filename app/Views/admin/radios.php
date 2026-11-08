<div class="row g-4">
    <!-- Coluna Esquerda: Cadastro e Buscador de Rádios -->
    <div class="col-12 col-lg-5">
        <?= $msg ?? '' ?>

        <!-- 1. Formulário Manual -->
        <div class="glass-card mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-plus-circle text-info"></i> Adicionar Nova Rádio
            </h5>
            <form method="POST">
                <input type="hidden" name="add_radio" value="1">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Nome da Emissora</label>
                    <input type="text" name="nome" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Jovem Pan FM, Antena 1..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">URL do Streaming de Áudio</label>
                    <input type="url" name="url" class="form-control bg-dark text-white border-secondary" placeholder="https://stream.exemplo.com/live.mp3" required>
                    <div class="form-text text-muted small">Formatos suportados: MP3, AAC, M3U8 de áudio.</div>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                    <i class="fa fa-save me-1"></i> Salvar Rádio
                </button>
            </form>
        </div>

        <!-- 2. Buscador Automático via API Gratuita -->
        <div class="glass-card">
            <h5 class="fw-bold mb-2 d-flex align-items-center gap-2">
                <i class="fa fa-globe text-success"></i> Buscador de Rádios Online
            </h5>
            <p class="text-muted small mb-3">Pesquise por mais de 40.000 estações do Brasil e do mundo.</p>

            <div class="input-group mb-3">
                <input type="text" id="searchRadioInput" class="form-control bg-dark text-white border-secondary" placeholder="Digite o nome da rádio..." onkeypress="if(event.key === 'Enter') searchRadios();">
                <button type="button" class="btn btn-success fw-bold px-3" onclick="searchRadios()">
                    <i class="fa fa-search me-1"></i> Buscar
                </button>
            </div>

            <div id="searchResults" style="max-height: 380px; overflow-y: auto;">
                <div class="text-center py-4 text-muted small">
                    <i class="fa fa-broadcast-tower fa-2x mb-2 d-block opacity-50"></i>
                    Digite um nome acima para localizar emissoras automaticamente.
                </div>
            </div>
        </div>
    </div>

    <!-- Coluna Direita: Lista de Rádios Cadastradas -->
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-broadcast-tower text-warning"></i> Rádios Cadastradas
                </h5>
                <span class="badge bg-secondary bg-opacity-25 border border-secondary border-opacity-50 px-3 py-2">
                    Total: <?= count($radios) ?> emissora(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Emissora</th>
                            <th class="text-center">Status</th>
                            <th class="text-center" style="width: 140px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($radios)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="fa fa-radio fa-2x mb-2 d-block opacity-50"></i>
                                    Nenhuma rádio cadastrada no momento. Adicione uma pelo formulário ao lado.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($radios as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($r['nome']) ?></div>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;" title="<?= htmlspecialchars($r['url']) ?>">
                                            <?= htmlspecialchars($r['url']) ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="id_radio" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="current_visible" value="<?= (int)$r['is_visible'] ?>">
                                            <input type="hidden" name="toggle_visible" value="1">
                                            <?php if ((int)$r['is_visible'] === 1): ?>
                                                <button type="submit" class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-50 px-3 py-1 cursor-pointer" title="Clique para ocultar">
                                                    <i class="fa fa-eye me-1"></i> Ativa
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="badge bg-secondary bg-opacity-25 text-muted border border-secondary border-opacity-50 px-3 py-1 cursor-pointer" title="Clique para ativar">
                                                    <i class="fa fa-eye-slash me-1"></i> Oculta
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-2">
                                            <button type="button" class="btn btn-sm btn-outline-info text-info border-info border-opacity-50" onclick="togglePlayPreview('<?= htmlspecialchars($r['url']) ?>', this)" title="Testar Áudio">
                                                <i class="fa fa-play"></i>
                                            </button>
                                            <form method="POST" onsubmit="return confirm('Deseja realmente excluir esta rádio?');" class="d-inline m-0">
                                                <input type="hidden" name="id_radio" value="<?= (int)$r['id'] ?>">
                                                <input type="hidden" name="delete_radio" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-danger text-danger border-danger border-opacity-50" title="Excluir">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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

<audio id="adminAudioPlayer" style="display:none;"></audio>

<script>
    let currentAudioUrl = null;
    const audioPlayer = document.getElementById('adminAudioPlayer');
    let currentPlayingBtn = null;

    function togglePlayPreview(url, btnElement) {
        if (currentAudioUrl === url && !audioPlayer.paused) {
            audioPlayer.pause();
            btnElement.innerHTML = '<i class="fa fa-play"></i>';
            btnElement.classList.remove('btn-warning');
            btnElement.classList.add('btn-outline-info');
            return;
        }

        if (currentPlayingBtn) {
            currentPlayingBtn.innerHTML = '<i class="fa fa-play"></i>';
            currentPlayingBtn.classList.remove('btn-warning');
            currentPlayingBtn.classList.add('btn-outline-info');
        }

        audioPlayer.src = url;
        audioPlayer.play().then(() => {
            currentAudioUrl = url;
            currentPlayingBtn = btnElement;
            btnElement.innerHTML = '<i class="fa fa-stop"></i>';
            btnElement.classList.remove('btn-outline-info');
            btnElement.classList.add('btn-warning');
        }).catch(err => {
            alert('Não foi possível reproduzir este streaming. O link pode estar indisponível ou em formato não aceito pelo navegador.');
            console.error(err);
        });
    }

    function searchRadios() {
        const query = document.getElementById('searchRadioInput').value.trim();
        const resultsDiv = document.getElementById('searchResults');
        
        if (!query) return;

        resultsDiv.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-info spinner-border-sm mb-2" role="status"></div><p class="text-muted small">Buscando emissoras...</p></div>';

        fetch('https://de1.api.radio-browser.info/json/stations/search?name=' + encodeURIComponent(query) + '&limit=15&hidebroken=true&order=clickcount&reverse=true')
        .then(res => res.json())
        .then(data => {
            if (!data || data.length === 0) {
                resultsDiv.innerHTML = '<div class="text-center py-4 text-muted small">Nenhuma rádio encontrada para esse termo.</div>';
                return;
            }

            let html = '<div class="list-group list-group-flush bg-transparent">';
            data.forEach(station => {
                const name = (station.name || '').replace(/"/g, '&quot;');
                const url = (station.url_resolved || station.url || '').replace(/"/g, '&quot;');
                const tags = station.tags ? station.tags.split(',')[0] : 'Web Rádio';
                
                html += `
                    <div class="list-group-item bg-dark border-secondary border-opacity-25 px-3 py-2 mb-2 rounded d-flex justify-content-between align-items-center">
                        <div style="max-width: 65%;">
                            <div class="fw-bold text-white text-truncate" title="${name}">${name}</div>
                            <span class="badge bg-info bg-opacity-15 text-info small">${tags}</span>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info" onclick="togglePlayPreview('${url}', this)" title="Ouvir Prévia">
                                <i class="fa fa-play"></i>
                            </button>
                            <form method="POST" class="m-0">
                                <input type="hidden" name="add_radio" value="1">
                                <input type="hidden" name="nome" value="${name}">
                                <input type="hidden" name="url" value="${url}">
                                <button type="submit" class="btn btn-sm btn-success fw-bold" title="Adicionar Rádio">
                                    <i class="fa fa-plus"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            resultsDiv.innerHTML = html;
        })
        .catch(err => {
            resultsDiv.innerHTML = '<div class="text-center py-4 text-danger small">Erro ao conectar à API de rádios. Verifique sua conexão.</div>';
            console.error(err);
        });
    }
</script>
