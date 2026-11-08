<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$mac = $_GET['mac'] ?? null;
if (!$mac) {
    die('Erro: MAC não fornecido.');
}
$vencimento = date('Y-m-d', strtotime('+31 days'));
$db = new SQLite3("../db/studiolivecode_clientes.db");
$stmt = $db->prepare("UPDATE ibo SET expire_date = :vencimento WHERE mac_address = :mac AND active = 1");
$stmt->bindValue(':vencimento', $vencimento, SQLITE3_TEXT);
$stmt->bindValue(':mac', $mac, SQLITE3_TEXT);
$stmt->execute();
$changes = $db->changes();
$db->close();
$log = fopen('debug_sucesso.txt', 'a');
fwrite($log, date('Y-m-d H:i:s') . " - MAC: $mac | Venc: $vencimento | Changes: $changes\n");
fclose($log);
if ($changes === 0) {
    die('Erro: Cliente não encontrado (MAC: ' . htmlspecialchars($mac) . '). Verifique DB.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Renovação Confirmada</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .box { 
            background: #0f3460;
            border-radius: 20px;
            padding: 35px 30px;
            max-width: 420px;
            width: 100%;
            box-shadow: 0 15px 50px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            animation: slideUp 0.5s ease-out;
            text-align: center;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        

        
        h1 { 
            font-size: 26px;
            color: #4ecca3;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        p { 
            font-size: 16px;
            color: #e8e8e8;
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .highlight {
            color: #4ecca3;
            font-weight: 600;
        }
        
        .divider {
            height: 1px;
            background: rgba(255,255,255,0.1);
            margin: 20px 0;
        }
        
        .mac-box {
            background: rgba(255,255,255,0.05);
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid rgba(78, 204, 163, 0.3);
            margin: 15px 0;
        }
        
        .mac-box .label {
            font-size: 12px;
            color: #93deff;
            font-weight: 600;
            margin-bottom: 5px;
            display: block;
        }
        
        .mac-box .address {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            color: #fff;
            letter-spacing: 1px;
        }
        
        .date-box { 
            background: rgba(78, 204, 163, 0.15);
            padding: 15px;
            margin: 15px 0;
            border: 1px solid #4ecca3;
            border-radius: 10px;
        }
        
        .date-box strong {
            color: #4ecca3;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }
        
        .date-box span {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
        }
        
        .restart-notice {
            background: rgba(255, 193, 7, 0.15);
            color: #ffc107;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
            border: 1px solid rgba(255, 193, 7, 0.3);
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Responsividade */
        @media (max-width: 600px) {
            .box {
                padding: 30px 25px;
                max-width: 380px;
            }
            
            h1 {
                font-size: 24px;
            }
            

            
            .mac-box .address {
                font-size: 14px;
            }
        }
        
        @media (max-width: 400px) {
            .box {
                padding: 25px 20px;
            }
            
            h1 {
                font-size: 22px;
            }
            
            p {
                font-size: 15px;
            }
            
            .date-box span {
                font-size: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="box">

        <h1>Renovação Aprovada!</h1>
        <p>Seu acesso foi renovado por <span class="highlight">+30 dias</span></p>
        
        <div class="divider"></div>
        
        <div class="mac-box">
            <span class="label">Seu MAC:</span>
            <div class="address"><?= htmlspecialchars($mac) ?></div>
        </div>
        
        <div class="date-box">
            <strong>📅 NOVO VENCIMENTO</strong>
            <span><?= date('d/m/Y', strtotime($vencimento)) ?></span>
        </div>
        
        <div class="restart-notice">
            🔄 Reinicie seu App para ATUALIZAR!
        </div>
    </div>
</body>
</html>