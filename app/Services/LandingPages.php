<?php
namespace App\Services;

/**
 * Gerencia os "containers" (banners empilhados) das pÃ¡ginas pÃºblicas:
 * Home, Parceiros e Afiliados.
 *
 * Os dados ficam em uploads/landing/pages.json. Enquanto uma pÃ¡gina ainda nÃ£o
 * foi editada pelo painel, usa os arquivos legados (img1-3 / parc1-3 / afil1-3).
 */
class LandingPages
{
    public const PAGES = [
        'home' => [
            'label' => 'Home',
            'icon' => 'fa-home',
            'url' => '/',
            'legacy_prefix' => 'img',
            'default_link' => '',
        ],
        'parceiros' => [
            'label' => 'Parceiros',
            'icon' => 'fa-handshake',
            'url' => '/parceiros',
            'legacy_prefix' => 'parc',
            'default_link' => '/cadastro?ref=parceiro',
        ],
        'afiliados' => [
            'label' => 'Afiliados',
            'icon' => 'fa-bullhorn',
            'url' => '/afiliados',
            'legacy_prefix' => 'afil',
            'default_link' => '/cadastro?ref=afiliado',
        ],
    ];

    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const MAX_BYTES = 15 * 1024 * 1024;

    public static function isValidPage(string $page): bool
    {
        return isset(self::PAGES[$page]);
    }

    public static function dir(): string
    {
        $dir = dirname(__DIR__, 2) . '/uploads/landing/';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @chmod($dir, 0777);
        return $dir;
    }

    private static function manifestPath(): string
    {
        return self::dir() . 'pages.json';
    }

    /** Lista bruta de containers de uma pÃ¡gina: [['id','file','link'], ...] */
    public static function raw(string $page): array
    {
        $manifest = [];
        $path = self::manifestPath();
        if (is_file($path)) {
            $decoded = json_decode((string)@file_get_contents($path), true);
            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        if (isset($manifest[$page]) && is_array($manifest[$page])) {
            return array_values($manifest[$page]);
        }

        // Fallback: arquivos legados ainda nÃ£o migrados para o manifesto
        $items = [];
        $prefix = self::PAGES[$page]['legacy_prefix'] ?? '';
        for ($i = 1; $i <= 3; $i++) {
            $file = $prefix . $i . '.jpg';
            if ($prefix !== '' && is_file(self::dir() . $file)) {
                $items[] = ['id' => $prefix . $i, 'file' => $file, 'link' => ''];
            }
        }
        return $items;
    }

    /** Containers prontos para exibiÃ§Ã£o/ediÃ§Ã£o (com URL, link efetivo e flag de existÃªncia). */
    public static function items(string $page): array
    {
        $defaultLink = self::PAGES[$page]['default_link'] ?? '';
        $out = [];
        foreach (self::raw($page) as $item) {
            $file = basename((string)($item['file'] ?? ''));
            if ($file === '') {
                continue;
            }
            $full = self::dir() . $file;
            $exists = is_file($full);
            $link = (string)($item['link'] ?? '');
            $out[] = [
                'id' => (string)($item['id'] ?? $file),
                'file' => $file,
                'link' => $link,
                'effective_link' => $link !== '' ? $link : $defaultLink,
                'exists' => $exists,
                'url' => '/uploads/landing/' . rawurlencode($file) . ($exists ? '?v=' . filemtime($full) : ''),
            ];
        }
        return $out;
    }

    /** Apenas os containers cujo arquivo existe (usado nas pÃ¡ginas pÃºblicas). */
    public static function publicItems(string $page): array
    {
        return array_values(array_filter(self::items($page), fn($i) => $i['exists']));
    }

    private static function saveItems(string $page, array $items): bool
    {
        $path = self::manifestPath();
        $manifest = [];
        if (is_file($path)) {
            $decoded = json_decode((string)@file_get_contents($path), true);
            if (is_array($decoded)) {
                $manifest = $decoded;
            }
        }

        // Migra as demais pÃ¡ginas para o manifesto para nÃ£o perder o fallback legado
        foreach (array_keys(self::PAGES) as $p) {
            if (!isset($manifest[$p])) {
                $manifest[$p] = self::raw($p);
            }
        }
        $manifest[$page] = array_values($items);

        $ok = @file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX) !== false;
        if ($ok) {
            @chmod($path, 0666);
        }
        return $ok;
    }

    private static function validUpload(array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if (($file['size'] ?? 0) > self::MAX_BYTES) {
            return null;
        }
        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return null;
        }
        if (@getimagesize($file['tmp_name']) === false) {
            return null;
        }
        return $ext === 'jpeg' ? 'jpg' : $ext;
    }

    private static function storeUpload(array $file, string $page, string $ext): ?string
    {
        $name = $page . '_' . bin2hex(random_bytes(5)) . '.' . $ext;
        $dest = self::dir() . $name;
        if (move_uploaded_file($file['tmp_name'], $dest) || @copy($file['tmp_name'], $dest)) {
            @chmod($dest, 0666);
            return $name;
        }
        return null;
    }

    private static function deleteFile(string $file): void
    {
        $file = basename($file);
        if ($file !== '' && $file !== 'logo.png' && $file !== 'pages.json') {
            @unlink(self::dir() . $file);
        }
    }

    public static function sanitizeLink(string $link): string
    {
        $link = trim($link);
        if ($link === '') {
            return '';
        }
        if (preg_match('#^(/(?!/)|https?://)#i', $link)) {
            return substr($link, 0, 500);
        }
        return '';
    }

    /** Adiciona um ou mais containers. Retorna quantos foram adicionados. */
    public static function add(string $page, array $files, string $link = ''): int
    {
        if (!self::isValidPage($page)) {
            return 0;
        }
        $items = self::raw($page);
        $added = 0;
        $link = self::sanitizeLink($link);

        // Normaliza $_FILES de mÃºltiplos arquivos
        $list = [];
        if (isset($files['name']) && is_array($files['name'])) {
            foreach ($files['name'] as $i => $n) {
                $list[] = [
                    'name' => $n,
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i],
                ];
            }
        } elseif (!empty($files)) {
            $list[] = $files;
        }

        foreach ($list as $file) {
            $ext = self::validUpload($file);
            if ($ext === null) {
                continue;
            }
            $name = self::storeUpload($file, $page, $ext);
            if ($name !== null) {
                $items[] = ['id' => bin2hex(random_bytes(4)), 'file' => $name, 'link' => $link];
                $added++;
            }
        }

        if ($added > 0) {
            self::saveItems($page, $items);
        }
        return $added;
    }

    /** Troca a imagem e/ou o link de um container. */
    public static function update(string $page, string $id, ?array $file, ?string $link): bool
    {
        if (!self::isValidPage($page)) {
            return false;
        }
        $items = self::raw($page);
        $changed = false;

        foreach ($items as &$item) {
            if ((string)($item['id'] ?? '') !== $id) {
                continue;
            }
            if ($file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $ext = self::validUpload($file);
                if ($ext !== null && ($name = self::storeUpload($file, $page, $ext)) !== null) {
                    self::deleteFile((string)$item['file']);
                    $item['file'] = $name;
                    $changed = true;
                }
            }
            if ($link !== null) {
                $item['link'] = self::sanitizeLink($link);
                $changed = true;
            }
            break;
        }
        unset($item);

        return $changed ? self::saveItems($page, $items) : false;
    }

    public static function remove(string $page, string $id): bool
    {
        if (!self::isValidPage($page)) {
            return false;
        }
        $items = self::raw($page);
        $kept = [];
        $removed = false;
        foreach ($items as $item) {
            if ((string)($item['id'] ?? '') === $id) {
                self::deleteFile((string)($item['file'] ?? ''));
                $removed = true;
                continue;
            }
            $kept[] = $item;
        }
        return $removed ? self::saveItems($page, $kept) : false;
    }

    /** Move um container uma posiÃ§Ã£o para cima ('up') ou para baixo ('down'). */
    public static function move(string $page, string $id, string $direction): bool
    {
        if (!self::isValidPage($page)) {
            return false;
        }
        $items = self::raw($page);
        foreach ($items as $i => $item) {
            if ((string)($item['id'] ?? '') !== $id) {
                continue;
            }
            $j = $direction === 'up' ? $i - 1 : $i + 1;
            if (!isset($items[$j])) {
                return false;
            }
            [$items[$i], $items[$j]] = [$items[$j], $items[$i]];
            return self::saveItems($page, $items);
        }
        return false;
    }
}
