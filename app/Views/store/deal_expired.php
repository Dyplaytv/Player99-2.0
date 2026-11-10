<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Oferta Esgotada - Player99</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #0b0d14;
            color: #ffffff;
            font-family: system-ui, -apple-system, sans-serif;
            padding-bottom: 40px;
        }
        .deal-card {
            background: #141824;
            border: 1px solid #23293d;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s;
        }
        .deal-card:hover {
            transform: translateY(-3px);
            border-color: #00f2fe;
        }
    </style>
</head>
<body>
    <div class="container py-4" style="max-width: 600px;">
        <div class="text-center mb-4">
            <div class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-50 px-3 py-2 mb-3">
                <i class="fa fa-exclamation-triangle me-1"></i> Oferta Esgotada
            </div>
            <h4 class="fw-bold">Essa oferta acabou de expirar!</h4>
            <p class="text-muted small">
                O produto que você escaneou no veículo pode ter encerrado a promoção ou esgotado o estoque.<br>
                Mas não perca a viagem: confira abaixo outros achadinhos ativos com desconto!
            </p>
        </div>

        <h6 class="fw-bold text-warning mb-3"><i class="fa fa-fire me-2"></i> Achadinhos em Destaque Hoje:</h6>

        <div class="row g-3">
            <?php foreach ($otherDeals as $d): ?>
                <div class="col-6">
                    <a href="/o/<?= $d['id'] ?>" class="text-decoration-none">
                        <div class="deal-card p-2 h-100 d-flex flex-column justify-content-between">
                            <div>
                                <?php if (!empty($d['image_url'])): ?>
                                    <img src="<?= htmlspecialchars($d['image_url']) ?>" class="w-100 rounded mb-2" style="height: 120px; object-fit: cover;" alt="Foto">
                                <?php else: ?>
                                    <div class="w-100 rounded mb-2 bg-dark d-flex align-items-center justify-content-center" style="height: 120px;">
                                        <i class="fa fa-shopping-bag fa-2x text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <h6 class="text-white small fw-bold text-truncate mb-1"><?= htmlspecialchars($d['title']) ?></h6>
                            </div>
                            <div class="mt-2">
                                <?php if ($d['price_new'] > 0): ?>
                                    <span class="text-success fw-bold">R$ <?= number_format($d['price_new'], 2, ',', '.') ?></span>
                                    <?php if ($d['discount_pct'] > 0): ?>
                                        <span class="badge bg-danger float-end">-<?= $d['discount_pct'] ?>%</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <button class="btn btn-sm btn-outline-info w-100 mt-2 py-1" style="font-size: 11px;">
                                    Ver Oferta <i class="fa fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-5">
            <small class="text-muted">Player99 DOOH & Vitrine de Ofertas</small>
        </div>
    </div>
</body>
</html>
