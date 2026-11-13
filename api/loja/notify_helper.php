<?php
require_once __DIR__ . '/../../app/Services/EvolutionService.php';
require_once __DIR__ . '/../../app/Services/WhatsAppWelcomeService.php';

use App\Services\EvolutionService;
use App\Services\WhatsAppWelcomeService;

/**
 * Busca as credenciais da Evolution API no banco system_settings e envia uma mensagem de texto.
 */
function enviarWhatsAppLoja(string $rawPhone, string $message): bool
{
    $phone = WhatsAppWelcomeService::formatBrazilPhone($rawPhone);
    if (!$phone || trim($message) === '') {
        return false;
    }

    try {
        $usersDbPath = __DIR__ . '/../db/studiolivecode_users.db';
        if (!file_exists($usersDbPath)) {
            return false;
        }
        $pdoUsers = new PDO("sqlite:{$usersDbPath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        $settings = [];
        $stmt = $pdoUsers->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('evolution_api_url', 'evolution_api_key', 'evolution_instance_name')");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $evoUrl = trim((string)($settings['evolution_api_url'] ?? ''));
        $evoKey = trim((string)($settings['evolution_api_key'] ?? ''));
        $evoInst = trim((string)($settings['evolution_instance_name'] ?? 'player99')) ?: 'player99';

        if ($evoUrl === '' || $evoKey === '') {
            return false;
        }

        $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);
        $res = $evo->sendText($phone, $message, '', 8);
        return !empty($res['success']);
    } catch (\Throwable $e) {
        error_log("Erro WhatsApp Loja: " . $e->getMessage());
        return false;
    }
}

/**
 * Aprova o pedido na loja, gera voucher/número da venda (se necessário) e envia as mensagens de WhatsApp para cliente e vendedor.
 */
function processarVendaAprovadaLoja(SQLite3 $dbLoja, int $pedido_id, string $mp_payment_id = ''): ?array
{
    $stmt = $dbLoja->prepare("
        SELECT p.*, pr.titulo, pr.preco, pr.observacoes_pos_compra, pr.whatsapp_vendedor
        FROM pedidos p
        JOIN produtos pr ON p.produto_id = pr.id
        WHERE p.id = :id
        LIMIT 1
    ");
    $stmt->bindValue(':id', $pedido_id, SQLITE3_INTEGER);
    $res = $stmt->execute();
    $pedido = $res->fetchArray(SQLITE3_ASSOC);

    if (!$pedido) {
        return null;
    }

    $numeroVenda = trim((string)($pedido['numero_venda'] ?? ''));
    if ($numeroVenda === '') {
        $numeroVenda = 'VND-' . date('Ymd') . '-' . str_pad((string)$pedido_id, 4, '0', STR_PAD_LEFT);
    }

    $voucher = trim((string)($pedido['voucher'] ?? ''));
    if ($voucher === '') {
        $voucher = 'VOUCHER-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
    }

    if (($pedido['status_pagamento'] ?? '') !== 'pago') {
        $stmtUpd = $dbLoja->prepare("
            UPDATE pedidos
            SET status_pagamento = 'pago',
                mp_payment_id = COALESCE(NULLIF(:mp_id, ''), mp_payment_id),
                voucher = :voucher,
                numero_venda = :num
            WHERE id = :id
        ");
        $stmtUpd->bindValue(':mp_id', $mp_payment_id, SQLITE3_TEXT);
        $stmtUpd->bindValue(':voucher', $voucher, SQLITE3_TEXT);
        $stmtUpd->bindValue(':num', $numeroVenda, SQLITE3_TEXT);
        $stmtUpd->bindValue(':id', $pedido_id, SQLITE3_INTEGER);
        $stmtUpd->execute();

        $pedido['status_pagamento'] = 'pago';
        $pedido['voucher'] = $voucher;
        $pedido['numero_venda'] = $numeroVenda;
    }

    if ((int)($pedido['whatsapp_notificado'] ?? 0) === 0) {
        $stmtMark = $dbLoja->prepare("UPDATE pedidos SET whatsapp_notificado = 1 WHERE id = :id");
        $stmtMark->bindValue(':id', $pedido_id, SQLITE3_INTEGER);
        $stmtMark->execute();

        $valorFormatado = 'R$ ' . number_format((float)($pedido['preco'] ?? 0), 2, ',', '.');
        $nomeCliente = trim((string)($pedido['cliente_nome'] ?? 'Cliente'));
        $tituloProduto = trim((string)($pedido['titulo'] ?? 'Produto'));
        $obsPosCompra = trim((string)($pedido['observacoes_pos_compra'] ?? ''));

        // 1. Mensagem para o Cliente
        $clienteWhats = trim((string)($pedido['cliente_whatsapp'] ?? ''));
        if ($clienteWhats !== '') {
            $msgCliente = "Olá, *{$nomeCliente}*! 🎉\n\n"
                . "Muito obrigado pela sua compra na *Player99*! Seu pagamento foi confirmado com sucesso.\n\n"
                . "🧾 *Detalhes da sua Compra:*\n"
                . "• *Nº da Venda:* `{$numeroVenda}`\n"
                . "• *Produto:* {$tituloProduto}\n"
                . "• *Valor Pago:* {$valorFormatado}\n"
                . "• *Voucher:* `{$voucher}`\n";

            if ($obsPosCompra !== '') {
                $msgCliente .= "\n📝 *Observações do Vendedor:*\n{$obsPosCompra}\n";
            }

            $msgCliente .= "\nQualquer dúvida, estamos à disposição! 🚀";
            enviarWhatsAppLoja($clienteWhats, $msgCliente);
        }

        // 2. Mensagem para o Vendedor
        $vendedorWhats = trim((string)($pedido['whatsapp_vendedor'] ?? ''));
        if ($vendedorWhats !== '') {
            $emailCliente = trim((string)($pedido['cliente_email'] ?? '-'));
            $msgVendedor = "💰 *Nova Venda Confirmada na Loja!* 🚀\n\n"
                . "• *Nº da Venda:* `{$numeroVenda}`\n"
                . "• *Produto:* {$tituloProduto}\n"
                . "• *Valor:* {$valorFormatado}\n"
                . "• *Voucher:* `{$voucher}`\n\n"
                . "👤 *Dados do Comprador:*\n"
                . "• *Nome:* {$nomeCliente}\n"
                . "• *WhatsApp:* " . ($clienteWhats !== '' ? $clienteWhats : '-') . "\n"
                . "• *E-mail:* {$emailCliente}";

            enviarWhatsAppLoja($vendedorWhats, $msgVendedor);
        }
    }

    return $pedido;
}
