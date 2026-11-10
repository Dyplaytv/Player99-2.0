<?php
namespace App\Services;

use App\Models\Deal;
use App\Core\Database;

class LinkConverterService
{
    /**
     * Resolve URLs encurtadas para encontrar o link final canônico
     */
    public static function expandUrl(string $url): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 6);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        
        curl_exec($ch);
        $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        return $finalUrl ?: $url;
    }

    /**
     * Detecta a plataforma e substitui a tag do vendedor pela nossa
     */
    public static function convertLink(string $rawUrl, ?string $subId = null): array
    {
        $expanded = self::expandUrl($rawUrl);
        $platform = 'outros';
        $affiliateUrl = $expanded;

        // Carrega configurações das plataformas
        $platforms = [];
        foreach (Deal::getPlatforms() as $p) {
            $platforms[$p['platform_key']] = $p;
        }

        // 1. AMAZON
        if (preg_match('/(amazon\.com|amzn\.to)/i', $expanded)) {
            $platform = 'amazon';
            $tag = trim($platforms['amazon']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                // Remove qualquer tag antiga e insere a nossa
                $clean = preg_replace('/([?&])tag=[^&]+/i', '$1', $expanded);
                $clean = rtrim($clean, '?&');
                $sep = (strpos($clean, '?') !== false) ? '&' : '?';
                $affiliateUrl = $clean . $sep . 'tag=' . urlencode($tag);
                if ($subId) {
                    $affiliateUrl .= '&ascsubtag=' . urlencode($subId);
                }
            }
        }
        // 2. MERCADO LIVRE
        elseif (preg_match('/(mercadolivre\.com|meli\.la)/i', $expanded)) {
            $platform = 'mercadolivre';
            $tag = trim($platforms['mercadolivre']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                // Remove tracking antigo
                $clean = preg_replace('/([?&])(matt|tracking_id)=[^&]+/i', '$1', $expanded);
                $clean = rtrim($clean, '?&');
                $sep = (strpos($clean, '?') !== false) ? '&' : '?';
                
                // Se a tag já tiver "matt=", anexa diretamente, senão usa como matt
                $tagParam = (strpos($tag, '=') !== false) ? $tag : ('matt=' . urlencode($tag));
                $affiliateUrl = $clean . $sep . $tagParam;
            }
        }
        // 3. SHOPEE
        elseif (preg_match('/(shopee\.com|shope\.ee)/i', $expanded)) {
            $platform = 'shopee';
            $tag = trim($platforms['shopee']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                $clean = preg_replace('/([?&])(aff_id|utm_[^=]+)=[^&]+/i', '$1', $expanded);
                $clean = rtrim($clean, '?&');
                $sep = (strpos($clean, '?') !== false) ? '&' : '?';
                $affiliateUrl = $clean . $sep . 'aff_id=' . urlencode($tag);
                if ($subId) {
                    $affiliateUrl .= '&sub_id=' . urlencode($subId);
                }
            }
        }
        // 4. MAGAZINE LUIZA (MAGALU)
        elseif (preg_match('/(magazineluiza\.com|magazinevoce\.com|maga\.lu)/i', $expanded)) {
            $platform = 'magalu';
            $tag = trim($platforms['magalu']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                // Se for loja magazinevoce: substitui ou anexa
                if (strpos($expanded, 'magazinevoce.com.br') !== false) {
                    $affiliateUrl = preg_replace('/magazinevoce\.com\.br\/[^\/]+/i', 'magazinevoce.com.br/' . $tag, $expanded);
                } else {
                    $sep = (strpos($expanded, '?') !== false) ? '&' : '?';
                    $affiliateUrl = $expanded . $sep . 'partner_id=' . urlencode($tag);
                }
            }
        }
        // 5. TEMU
        elseif (preg_match('/(temu\.com|temu\.to)/i', $expanded)) {
            $platform = 'temu';
            $tag = trim($platforms['temu']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                $sep = (strpos($expanded, '?') !== false) ? '&' : '?';
                $affiliateUrl = $expanded . $sep . '_af_code=' . urlencode($tag);
            }
        }
        // 6. ALIEXPRESS
        elseif (preg_match('/(aliexpress\.com)/i', $expanded)) {
            $platform = 'aliexpress';
            $tag = trim($platforms['aliexpress']['affiliate_tag'] ?? '');
            if (!empty($tag)) {
                $sep = (strpos($expanded, '?') !== false) ? '&' : '?';
                $affiliateUrl = $expanded . $sep . 'aff_platform=default&sk=' . urlencode($tag);
            }
        }

        return [
            'platform' => $platform,
            'original_url' => $rawUrl,
            'expanded_url' => $expanded,
            'affiliate_url' => $affiliateUrl
        ];
    }

    /**
     * Extrai informações de uma mensagem de texto de grupo de ofertas
     */
    public static function parseDealMessage(string $text): ?array
    {
        // Encontra o primeiro link que corresponda a um e-commerce
        if (!preg_match('#https?://[^\s]+#i', $text, $match)) {
            return null;
        }

        $rawUrl = $match[0];
        $converted = self::convertLink($rawUrl);

        // Se for de uma plataforma desconhecida, descarta
        if ($converted['platform'] === 'outros') {
            return null;
        }

        // Título: pega as primeiras linhas que não contenham URL
        $lines = explode("\n", trim($text));
        $title = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (!empty($line) && !preg_match('#https?://#i', $line)) {
                $title = preg_replace('/[🔥💥⚡🚨😱🛒]/u', '', $line);
                $title = trim($title);
                if (mb_strlen($title) >= 10) break;
            }
        }
        if (empty($title)) {
            $title = 'Super Oferta ' . ucfirst($converted['platform']);
        }
        $title = mb_substr($title, 0, 120);

        // Preços (ex: "De R$ 199 por R$ 89,90" ou "R$ 49,90")
        $priceOld = 0.0;
        $priceNew = 0.0;

        if (preg_match('/(?:de\s*:?\s*r?\$?\s*)([0-9]+[.,][0-9]{2})/i', $text, $pOld)) {
            $priceOld = (float)str_replace(',', '.', str_replace('.', '', $pOld[1]));
        }

        if (preg_match('/(?:por\s*:?\s*r?\$?\s*|r\$\s*)([0-9]+[.,][0-9]{2})/i', $text, $pNew)) {
            $priceNew = (float)str_replace(',', '.', str_replace('.', '', $pNew[1]));
        }

        // Desconto em %
        $discountPct = 0;
        if ($priceOld > $priceNew && $priceNew > 0) {
            $discountPct = (int)round((($priceOld - $priceNew) / $priceOld) * 100);
        } elseif (preg_match('/([0-9]{1,2})\s*%\s*off/i', $text, $pctMatch)) {
            $discountPct = (int)$pctMatch[1];
        }

        // Detecta Categoria básica por palavras-chave
        $category = 'Geral';
        $lower = mb_strtolower($text);
        if (preg_match('/(celular|smartphone|iphone|galaxy|xiaomi|fone|headphone|airpods|smartwatch|relogio|tablet|carregador|powerbank)/i', $lower)) {
            $category = 'Eletrônicos';
        } elseif (preg_match('/(carro|automotivo|pneu|suporte|carrinho|veiculo|led|farol|gps)/i', $lower)) {
            $category = 'Automotivo';
        } elseif (preg_match('/(perfume|beleza|maquiagem|shampoo|hidratante|skincare|cabelo)/i', $lower)) {
            $category = 'Beleza & Cuidados';
        } elseif (preg_match('/(panela|fritadeira|airfryer|cozinha|casa|cama|travesseiro|toalha|luminaria)/i', $lower)) {
            $category = 'Casa & Utilidades';
        } elseif (preg_match('/(tenis|camisa|camiseta|vestido|calca|bermuda|bolsa|mochila)/i', $lower)) {
            $category = 'Moda & Acessórios';
        }

        return [
            'platform' => $converted['platform'],
            'title' => $title,
            'original_url' => $converted['original_url'],
            'affiliate_url' => $converted['affiliate_url'],
            'price_old' => $priceOld,
            'price_new' => $priceNew,
            'discount_pct' => $discountPct,
            'category' => $category
        ];
    }
}
