<?php
namespace App\Services;

use App\Core\Database;

class WhatsAppWelcomeService
{
    public const DEFAULT_TEMPLATE = "Olá, {nome}! 👋🚗\n\n"
        . "Seja muito bem-vindo(a) à *Player99*! Estamos muito felizes em ter você aqui com a gente.\n\n"
        . "📌 *Salve nosso número nos seus contatos!*\n"
        . "É por aqui que você receberá mais informações, novidades da plataforma, avisos das suas telas e atendimento sempre que precisar.\n\n"
        . "🔑 *Seu acesso já está liberado:*\n"
        . "• Usuário: `{usuario}`\n"
        . "• Portal: {portal_url}\n\n"
        . "Qualquer dúvida, basta responder esta mensagem. Conte com a nossa equipe! 🚀✨";

    /**
     * Normaliza números brasileiros garantindo DDI 55 quando informado apenas DDD + número (10 ou 11 dígitos).
     */
    public static function formatBrazilPhone(string $raw): ?string
    {
        $digits = preg_replace('/\D/', '', $raw);
        if (!is_string($digits)) {
            return null;
        }
        $digits = ltrim($digits, '0');
        $len = strlen($digits);

        if ($len < 10) {
            return null;
        }
        if ($len === 10 || $len === 11) {
            return '55' . $digits;
        }

        return $digits;
    }

    public static function buildPortalUrl(): string
    {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (($_SERVER['SERVER_PORT'] ?? '') == 443);
        $scheme = $isHttps ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'player99.com.br';
        return $scheme . '://' . $host . '/login';
    }

    public static function buildMessage(string $clientName, string $username, string $template = '', string $portalUrl = ''): string
    {
        $tpl = trim($template) !== '' ? $template : self::DEFAULT_TEMPLATE;
        $safeName = trim($clientName) !== '' ? trim($clientName) : ($username ?: 'Parceiro');
        $safeUser = trim($username) !== '' ? trim($username) : '-';
        $url = trim($portalUrl) !== '' ? $portalUrl : self::buildPortalUrl();

        return strtr($tpl, [
            '{nome}' => $safeName,
            '{usuario}' => $safeUser,
            '{portal_url}' => $url
        ]);
    }

    /**
     * Envia mensagem de boas-vindas no WhatsApp ao cadastrar um cliente/motorista.
     * Nunca lança exceção para não interromper o fluxo de cadastro caso a API esteja indisponível.
     */
    public static function sendOnRegistration(string $clientName, string $username, string $whatsapp): array
    {
        try {
            $phone = self::formatBrazilPhone($whatsapp);
            if ($phone === null) {
                return ['sent' => false, 'reason' => 'invalid_phone'];
            }

            $pdo = Database::users();
            $settings = [];
            try {
                $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
                while ($row = $stmt->fetch()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            } catch (\Throwable $e) {
                return ['sent' => false, 'reason' => 'settings_unavailable'];
            }

            if (isset($settings['welcome_whatsapp_enabled']) && $settings['welcome_whatsapp_enabled'] === '0') {
                return ['sent' => false, 'reason' => 'disabled'];
            }

            $evoUrl = trim((string)($settings['evolution_api_url'] ?? ''));
            $evoKey = trim((string)($settings['evolution_api_key'] ?? ''));
            $evoInst = trim((string)($settings['evolution_instance_name'] ?? 'player99')) ?: 'player99';

            if ($evoUrl === '' || $evoKey === '') {
                return ['sent' => false, 'reason' => 'evolution_not_configured'];
            }

            $text = self::buildMessage(
                $clientName,
                $username,
                (string)($settings['welcome_whatsapp_message'] ?? '')
            );

            $evo = new EvolutionService($evoUrl, $evoKey, $evoInst);
            $res = $evo->sendText($phone, $text, '', 6);

            $ok = !empty($res['success']);
            self::logAttempt($phone, $username, $ok, (string)($res['error'] ?? ('HTTP ' . ($res['http_code'] ?? 0))));

            return [
                'sent' => $ok,
                'phone' => $phone,
                'http_code' => $res['http_code'] ?? 0,
                'error' => $res['error'] ?? null
            ];
        } catch (\Throwable $e) {
            self::logAttempt($whatsapp, $username, false, $e->getMessage());
            return ['sent' => false, 'reason' => 'exception', 'error' => $e->getMessage()];
        }
    }

    private static function logAttempt(string $phone, string $username, bool $ok, string $detail): void
    {
        try {
            $logPath = dirname(__DIR__, 2) . '/api/db/welcome_whatsapp.log';
            $status = $ok ? 'OK' : 'FAIL';
            $line = date('[Y-m-d H:i:s] ') . "{$status} user={$username} phone={$phone} detail={$detail}\n";
            @file_put_contents($logPath, $line, FILE_APPEND);
        } catch (\Throwable $e) {}
    }
}
