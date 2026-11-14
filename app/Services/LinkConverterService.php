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
     * Faz scraping leve (OpenGraph / Meta tags / JSON-LD) da página do produto
     * para obter título, descrição, imagem e preço automaticamente.
     */
    public static function fetchProductMetadata(string $url): array
    {
        $meta = [
            'title' => '',
            'description' => '',
            'image' => '',
            'price' => 0.0
        ];

        if (empty($url) || !preg_match('#^https?://#i', $url)) {
            return $meta;
        }

        $fetchHtml = static function (string $targetUrl, string $userAgent): array {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $targetUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 6);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_ENCODING, '');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7'
            ]);
            curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
            $body = curl_exec($ch);
            $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return [$body ?: '', $code];
        };

        // Tenta primeiro como crawler social (WhatsApp/Facebook), pois Mercado Livre/Amazon/Shopee
        // costumam entregar og:image e og:title sem bloqueio anti-bot de datacenter.
        [$html, $httpCode] = $fetchHtml($url, 'WhatsApp/2.23.20.0 A');
        if ($html === '' || $httpCode >= 400 || stripos($html, 'og:image') === false) {
            [$htmlBrowser, $codeBrowser] = $fetchHtml($url, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');
            if ($htmlBrowser !== '' && $codeBrowser < 400) {
                $html = $htmlBrowser;
                $httpCode = $codeBrowser;
            }
        }

        if (!$html || $httpCode >= 400) {
            return $meta;
        }

        // Limita o HTML analisado aos primeiros ~300KB para manter performance
        $snippet = substr($html, 0, 310000);

        $extractMeta = static function (string $htmlContent, array $patterns): string {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $htmlContent, $m) && !empty(trim($m[1]))) {
                    return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
            return '';
        };

        // 1. Título
        $title = $extractMeta($snippet, [
            '/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:title["\']/i',
            '/<meta[^>]+name=["\']twitter:title["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<title[^>]*>([^<]+)<\/title>/i'
        ]);
        if ($title !== '') {
            $title = preg_replace('/\s*\|\s*(Mercado Livre|Amazon\.com\.br|Shopee Brasil|Magazine Luiza).*$/i', '', $title);
            $meta['title'] = mb_substr(trim($title), 0, 140);
        }

        // 2. Descrição
        $desc = $extractMeta($snippet, [
            '/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:description["\']/i',
            '/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']description["\']/i',
            '/<meta[^>]+name=["\']twitter:description["\'][^>]+content=["\']([^"\']+)["\']/i'
        ]);
        if ($desc !== '') {
            $desc = preg_replace('/\s+/', ' ', trim($desc));
            $meta['description'] = mb_substr($desc, 0, 280);
        }

        // 3. Imagem
        $img = $extractMeta($snippet, [
            '/<meta[^>]+property=["\']og:image:secure_url["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image:secure_url["\']/i',
            '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i',
            '/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i',
            '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\']/i'
        ]);
        if ($img !== '' && strpos($img, '//') === 0) {
            $img = 'https:' . $img;
        }
        if ($img !== '' && preg_match('#^https?://#i', $img)) {
            $meta['image'] = $img;
        }

        // Fallback específico Mercado Livre caso og:image não venha no HTML
        if ($meta['image'] === '' && preg_match('#https?://http2\.mlstatic\.com/D_NQ_NP_[^"\'\s<>]+\.(?:jpg|jpeg|png|webp)#i', $snippet, $mlImg)) {
            $meta['image'] = $mlImg[0];
        }

        // 4. Preço (meta tags ou JSON-LD)
        $priceStr = $extractMeta($snippet, [
            '/<meta[^>]+property=["\']product:price:amount["\'][^>]+content=["\']([0-9.,]+)["\']/i',
            '/<meta[^>]+itemprop=["\']price["\'][^>]+content=["\']([0-9.,]+)["\']/i'
        ]);
        if ($priceStr === '' && preg_match('/"price"\s*:\s*"?([0-9]+(?:\.[0-9]{1,2})?)"?/i', $snippet, $pm)) {
            $priceStr = $pm[1];
        }
        if ($priceStr !== '') {
            $norm = str_replace(',', '.', $priceStr);
            $val = (float)$norm;
            if ($val > 0) {
                $meta['price'] = $val;
            }
        }

        // Fallback via JSON-LD se faltou imagem ou descrição
        if (($meta['image'] === '' || $meta['description'] === '') && preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $snippet, $ldMatches)) {
            foreach ($ldMatches[1] as $ldRaw) {
                $ld = json_decode(trim($ldRaw), true);
                if (!is_array($ld)) continue;
                if ($meta['image'] === '' && !empty($ld['image'])) {
                    $candImg = is_array($ld['image']) ? ($ld['image'][0] ?? '') : $ld['image'];
                    if (is_string($candImg) && preg_match('#^https?://#i', $candImg)) {
                        $meta['image'] = $candImg;
                    }
                }
                if ($meta['description'] === '' && !empty($ld['description']) && is_string($ld['description'])) {
                    $meta['description'] = mb_substr(trim(strip_tags($ld['description'])), 0, 280);
                }
                if ($meta['title'] === '' && !empty($ld['name']) && is_string($ld['name'])) {
                    $meta['title'] = mb_substr(trim($ld['name']), 0, 140);
                }
            }
        }

        return $meta;
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
                $title = preg_replace('/[🔥💥⚡🚨😱🛒*]/u', '', $line);
                $title = trim($title);
                if (mb_strlen($title) >= 10) break;
            }
        }
        // Se título veio vazio, preço novo zerado, ou sempre para enriquecer imagem e descrição:
        $meta = self::fetchProductMetadata($converted['expanded_url']);

        if (empty($title) && !empty($meta['title'])) {
            $title = $meta['title'];
        }
        if (empty($title)) {
            $title = 'Super Oferta ' . ucfirst($converted['platform']);
        }
        $title = mb_substr($title, 0, 140);

        // Preços (ex: "De R$ 199 por R$ 89,90" ou "R$ 49,90")
        $priceOld = 0.0;
        $priceNew = 0.0;

        if (preg_match('/(?:de\s*:?\s*r?\$?\s*)([0-9]+[.,][0-9]{2})/i', $text, $pOld)) {
            $priceOld = (float)str_replace(',', '.', str_replace('.', '', $pOld[1]));
        }

        if (preg_match('/(?:por\s*:?\s*r?\$?\s*|r\$\s*)([0-9]+[.,][0-9]{2})/i', $text, $pNew)) {
            $priceNew = (float)str_replace(',', '.', str_replace('.', '', $pNew[1]));
        }

        if ($priceNew <= 0 && !empty($meta['price']) && $meta['price'] > 0) {
            $priceNew = (float)$meta['price'];
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
            'description' => $meta['description'] ?? '',
            'image_url' => $meta['image'] ?? '',
            'original_url' => $converted['original_url'],
            'affiliate_url' => $converted['affiliate_url'],
            'price_old' => $priceOld,
            'price_new' => $priceNew,
            'discount_pct' => $discountPct,
            'category' => $category
        ];
    }
}
