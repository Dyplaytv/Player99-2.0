<?php
/**
 * CRON - ALERTAS AUTOMÁTICOS POR WHATSAPP
 *
 * 1) Lembrete de vencimento: 3 dias antes e no dia do vencimento (com link de pagamento).
 * 2) Aviso de tablet offline: tela sem sinal há mais de 30 min (somente das 07h às 22h,
 *    no máximo 1 aviso por tela por dia, e só para telas que estavam ativas nos últimos 7 dias).
 *
 * Cada alerta é registrado na tabela `alertas_whatsapp` para NUNCA enviar duplicado.
 *
 * Execução (aaPanel > Cron > Shell, a cada 30 minutos):
 *   php /www/wwwroot/player99.com.br/cron_alertas_whatsapp.php
 * Teste sem enviar nada:
 *   php /www/wwwroot/player99.com.br/cron_alertas_whatsapp.php --dry
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die('Execute apenas via linha de comando (cron).');
}

date_default_timezone_set('America/Sao_Paulo');
$isDry = in_array('--dry', $argv ?? []);

require_once __DIR__ . '/includes/db_cliente.php';
require_once __DIR__ . '/whatsapp_api.php';

$dbClientes = openSqliteSafe(dirname(__DIR__) . '/api/db/studiolivecode_clientes.db');

$dbClientes->exec("CREATE TABLE IF NOT EXISTS alertas_whatsapp(
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    id_cliente INTEGER,
    tipo TEXT,
    chave TEXT,
    enviado_em TEXT,
    UNIQUE(id_cliente, tipo, chave)
)");

$whatsData = [];
$whatsFile = __DIR__ . '/whats.json';
if (file_exists($whatsFile)) {
    $whatsData = json_decode(file_get_contents($whatsFile), true) ?: [];
}

function logAlerta($txt) {
    echo '[' . date('Y-m-d H:i:s') . '] ' . $txt . PHP_EOL;
}

function jaEnviado($db, $idCliente, $tipo, $chave) {
    $stmt = $db->prepare("SELECT 1 FROM alertas_whatsapp WHERE id_cliente = :c AND tipo = :t AND chave = :k");
    $stmt->bindValue(':c', $idCliente, SQLITE3_INTEGER);
    $stmt->bindValue(':t', $tipo, SQLITE3_TEXT);
    $stmt->bindValue(':k', $chave, SQLITE3_TEXT);
    $r = $stmt->execute()->fetchArray();
    return (bool)$r;
}

function registrarEnvio($db, $idCliente, $tipo, $chave) {
    $stmt = $db->prepare("INSERT OR IGNORE INTO alertas_whatsapp (id_cliente, tipo, chave, enviado_em) VALUES (:c, :t, :k, :d)");
    $stmt->bindValue(':c', $idCliente, SQLITE3_INTEGER);
    $stmt->bindValue(':t', $tipo, SQLITE3_TEXT);
    $stmt->bindValue(':k', $chave, SQLITE3_TEXT);
    $stmt->bindValue(':d', date('Y-m-d H:i:s'), SQLITE3_TEXT);
    $stmt->execute();
}

function enviarAlerta($db, $isDry, $cliente, $numero, $tipo, $chave, $mensagem) {
    if ($isDry) {
        logAlerta("[DRY] {$tipo} -> {$cliente['title']} ({$numero})");
        return;
    }
    $resp = enviarMensagemWasender($numero, $mensagem);
    $ok = isset($resp['key']) || isset($resp['messageId']) || (is_array($resp) && !isset($resp['error']));
    if ($ok) {
        registrarEnvio($db, (int)$cliente['id'], $tipo, $chave);
        logAlerta("OK {$tipo} -> {$cliente['title']}");
    } else {
        $err = isset($resp['error']) ? (is_array($resp['error']) ? json_encode($resp['error']) : $resp['error']) : 'erro desconhecido';
        logAlerta("FALHA {$tipo} -> {$cliente['title']}: {$err}");
    }
    sleep(rand(4, 9)); // antibanimento
}

function numeroDoCliente($whatsData, $cliente) {
    $mac = strtoupper(trim($cliente['mac_address'] ?? ''));
    $raw = !empty($cliente['whatsapp']) ? $cliente['whatsapp'] : ($whatsData[$mac]['whatsapp'] ?? '');
    if (empty($raw)) return '';
    $n = preg_replace('/[^0-9]/', '', $raw);
    return strlen($n) >= 10 ? $n : '';
}

// Carrega clientes ativos
$clientes = [];
$res = $dbClientes->query("SELECT id, title, mac_address, expire_date, link_payment, whatsapp FROM ibo WHERE active = 1");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $clientes[(int)$row['id']] = $row;
}

$hoje = date('Y-m-d');
$hora = (int)date('G');

/* ============ 1) LEMBRETE DE VENCIMENTO ============ */
foreach ($clientes as $c) {
    if (empty($c['expire_date'])) continue;
    $exp = $c['expire_date'];
    if (strpos($exp, '/') !== false) { // normaliza dd/mm/YYYY
        $p = explode('/', $exp);
        if (count($p) == 3) $exp = $p[2] . '-' . $p[1] . '-' . $p[0];
    }
    $tsExp = strtotime($exp);
    if (!$tsExp) continue;
    $dias = (int)round(($tsExp - strtotime($hoje)) / 86400);
    if ($dias !== 3 && $dias !== 0) continue;
    if ($hora < 8 || $hora > 20) continue; // horário comercial

    $numero = numeroDoCliente($whatsData, $c);
    if ($numero === '') continue;

    $tipo = $dias === 3 ? 'vencimento_3d' : 'vencimento_hoje';
    $chave = date('Y-m-d', $tsExp);
    if (jaEnviado($dbClientes, (int)$c['id'], $tipo, $chave)) continue;

    $dataFmt = date('d/m/Y', $tsExp);
    $link = !empty($c['link_payment']) ? "\n\n💳 Pague pelo link:\n" . $c['link_payment'] : '';
    if ($dias === 3) {
        $msg = "Olá {$c['title']}! 👋\nSeu Player99 vence em *3 dias* ({$dataFmt}).\nRenove antes para a tela não parar de rodar.{$link}";
    } else {
        $msg = "Olá {$c['title']}! ⚠️\nSeu Player99 vence *hoje* ({$dataFmt}).\nRenove agora para evitar o bloqueio da sua tela.{$link}";
    }
    enviarAlerta($dbClientes, $isDry, $c, $numero, $tipo, $chave, $msg);
}

/* ============ 2) TABLET OFFLINE ============ */
if ($hora >= 7 && $hora <= 22) {
    $agora = time();
    $res = $dbCliente->query("SELECT id, id_cliente, nome_tela, ultimo_ping FROM telas WHERE ultimo_ping IS NOT NULL AND ultimo_ping != ''");
    $offlinePorCliente = [];
    while ($t = $res->fetchArray(SQLITE3_ASSOC)) {
        $idCli = (int)$t['id_cliente'];
        if (!isset($clientes[$idCli])) continue;
        $diff = $agora - strtotime($t['ultimo_ping']);
        // offline há mais de 30 min, mas ativo na última semana (evita avisar tela abandonada)
        if ($diff > 1800 && $diff < 7 * 86400) {
            $offlinePorCliente[$idCli][] = $t;
        }
    }

    foreach ($offlinePorCliente as $idCli => $telas) {
        $c = $clientes[$idCli];
        $numero = numeroDoCliente($whatsData, $c);
        if ($numero === '') continue;

        $chave = $hoje; // 1 aviso por dia por cliente
        if (jaEnviado($dbClientes, $idCli, 'tela_offline', $chave)) continue;

        $nomes = [];
        foreach ($telas as $t) {
            $min = (int)round(($agora - strtotime($t['ultimo_ping'])) / 60);
            $tempo = $min >= 120 ? round($min / 60) . 'h' : $min . ' min';
            $nomes[] = "• {$t['nome_tela']} (sem sinal há {$tempo})";
        }
        $msg = "Olá {$c['title']}! 📺\nSua(s) tela(s) estão sem comunicação:\n" . implode("\n", $nomes)
             . "\n\nConfira se o tablet está ligado, carregando e com internet. Ao reabrir o Player99 ele volta a tocar sozinho.";
        enviarAlerta($dbClientes, $isDry, $c, $numero, 'tela_offline', $chave, $msg);
    }
}

logAlerta('Concluído.' . ($isDry ? ' (modo teste, nada enviado)' : ''));
