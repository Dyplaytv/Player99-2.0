<div class="row g-4">
    <!-- Coluna Esquerda: Upload de Mídia de Bloqueio -->
    <div class="col-12 col-lg-5">
        <?= $msg ?? '' ?>

        <div class="glass-card">
            <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
                <i class="fa fa-upload text-info"></i> Nova Mídia de Bloqueio
            </h5>
            <p class="text-muted small mb-4">
                Esta mídia será exibida nas telas dos carros quando o dispositivo estiver em standby ou bloqueado.
            </p>

            <form method="POST" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Título / Identificação</label>
                    <input type="text" name="title" class="form-control bg-dark text-white border-secondary" placeholder="Ex: Anúncio Standby ou Logo Oficial" required>
                </div>

                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Arquivo de Imagem ou Vídeo</label>
                    <input type="file" name="fileToUpload" class="form-control bg-dark text-white border-secondary" accept="image/*,video/*" required>
                    <div class="form-text text-muted small mt-2">
                        Formatos aceitos: JPG, PNG, GIF, MP4, WEBM. Vídeos em loop automático recomendados.
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 fw-bold py-2 shadow-sm">
                    <i class="fa fa-cloud-upload-alt me-1"></i> Fazer Upload e Ativar
                </button>
            </form>
        </div>
    </div>

    <!-- Coluna Direita: Mídias Cadastradas -->
    <div class="col-12 col-lg-7">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
                    <i class="fa fa-ban text-warning"></i> Mídias Ativas em Standby
                </h5>
                <span class="badge bg-secondary bg-opacity-25 border border-secondary border-opacity-50 px-3 py-2">
                    Total: <?= count($items) ?> mídia(s)
                </span>
            </div>

            <div class="table-responsive">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Prévia</th>
                            <th>Título</th>
                            <th class="text-center" style="width: 100px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($items)): ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">
                                    <i class="fa fa-film fa-2x mb-2 d-block opacity-50"></i>
                                    Nenhuma mídia de bloqueio configurada no momento.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($items as $item): ?>
                                <?php 
                                    $path = htmlspecialchars($item['caminho']); 
                                    $isVid = preg_match('/\.(mp4|webm|mkv)$/i', $path);
                                ?>
                                <tr>
                                    <td style="width: 140px;">
                                        <div style="width: 120px; height: 75px; border-radius: 8px; overflow: hidden; background: #000; border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center;">
                                            <?php if ($isVid): ?>
                                                <video src="/<?= $path ?>" style="width: 100%; height: 100%; object-fit: cover;" autoplay muted loop></video>
                                            <?php else: ?>
                                                <img src="/<?= $path ?>" alt="Prévia" style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($item['title']) ?></div>
                                        <span class="badge bg-info bg-opacity-15 text-info mt-1">
                                            <?= $isVid ? 'Vídeo MP4/WEBM' : 'Imagem Estática' ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form method="POST" onsubmit="return confirm('Deseja realmente remover esta mídia de bloqueio?');" class="d-inline m-0">
                                            <input type="hidden" name="delete" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger text-danger border-danger border-opacity-50" title="Excluir Mídia">
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
