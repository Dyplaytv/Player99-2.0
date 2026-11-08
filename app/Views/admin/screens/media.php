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
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Título da Campanha / Anunciante</label>
                    <input type="text" name="media_title" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Comercial Restaurante Sabor" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Arquivo de Mídia (MP4, WEBM, JPG, PNG)</label>
                    <input type="file" name="media_file" class="form-control bg-dark text-white border-secondary" accept="video/mp4,video/webm,image/*" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
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
