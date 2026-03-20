<?php

namespace App\Services\Normalization;

use App\Models\NormalizedProduct;

class ImageUrlCleanerService
{
    /**
     * Nombres de columna en raw_data que pueden contener URLs de imagen.
     *
     * @var array<int, string>
     */
    protected array $imageColumnKeys = [
        'image', 'imagen', 'url_imagen', 'image_url', 'url', 'foto', 'photo',
        'img', 'picture', 'imagen_1', 'image1', 'url_imagen_1',
    ];

    /**
     * Limpia y prepara lista de URLs por producto; guarda en normalized_products.image_urls.
     * No descarga imágenes.
     */
    public function cleanAndSaveForProducts(array $normalizedProductIds): array
    {
        $updated = 0;
        $products = NormalizedProduct::with('supplierImportRow')->whereIn('id', $normalizedProductIds)->get();

        foreach ($products as $product) {
            $urls = $this->imageCandidatesForProduct($product);
            $prev = $product->image_urls ?? [];
            if ($urls !== $prev) {
                $product->image_urls = $urls;
                $product->save();
                $updated++;
            }
        }

        return ['updated' => $updated, 'total' => $products->count()];
    }

    /**
     * Devuelve todas las URLs de imagen detectadas para un producto, combinando
     * lo ya guardado con lo que todavía pueda existir en el raw del proveedor.
     *
     * @return array<int, string>
     */
    public function imageCandidatesForProduct(NormalizedProduct $product): array
    {
        return $this->cleanUrlList($this->extractUrlsFromProduct($product));
    }

    /**
     * Devuelve únicamente las URLs detectadas desde el payload original del proveedor.
     *
     * @return array<int, string>
     */
    public function rawImageCandidatesForProduct(NormalizedProduct $product): array
    {
        return $this->cleanUrlList(
            $this->extractUrlsFromRawPayload($product->supplierImportRow?->raw_data)
        );
    }

    /**
     * Extrae URLs desde normalized_product (tags, description) o desde raw_data.
     */
    protected function extractUrlsFromProduct(NormalizedProduct $product): array
    {
        $urls = is_array($product->image_urls) ? $product->image_urls : [];

        $urls = array_merge(
            $urls,
            $this->extractUrlsFromRawPayload($product->supplierImportRow?->normalized_data)
        );

        $urls = array_merge(
            $urls,
            $this->extractUrlsFromRawPayload($product->supplierImportRow?->raw_data)
        );

        $text = ($product->description ?? '') . ' ' . ($product->tags ?? '');
        if ($text !== ' ') {
            $urls = array_merge($urls, $this->extractUrlsFromString($text));
        }

        return $urls;
    }

    /**
     * Busca URLs dentro del payload original, recorriendo arrays anidados y
     * dando prioridad a columnas típicas de imagen si existen.
     *
     * @return array<int, string>
     */
    public function extractUrlsFromRawPayload(mixed $payload): array
    {
        $urls = [];

        if (is_array($payload)) {
            foreach ($this->imageColumnKeys as $key) {
                if (isset($payload[$key]) && is_scalar($payload[$key])) {
                    $urls = array_merge($urls, $this->extractUrlsFromString((string) $payload[$key]));
                }
            }

            foreach ($this->flattenPayloadStrings($payload) as $value) {
                $urls = array_merge($urls, $this->extractUrlsFromString($value));
            }
        } elseif (is_scalar($payload)) {
            $urls = array_merge($urls, $this->extractUrlsFromString((string) $payload));
        }

        return $urls;
    }

    /**
     * @return array<int, string>
     */
    protected function flattenPayloadStrings(array $payload): array
    {
        $values = [];

        foreach ($payload as $value) {
            if (is_array($value)) {
                $values = array_merge($values, $this->flattenPayloadStrings($value));
                continue;
            }

            if (is_scalar($value) && trim((string) $value) !== '') {
                $values[] = (string) $value;
            }
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    protected function extractUrlsFromString(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $urls = [];
        if (preg_match_all('#https?://[^\s,;|<>"\']+#u', $value, $matches)) {
            foreach ($matches[0] as $match) {
                $urls[] = trim($match);
            }

            return $urls;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return [$value];
        }

        foreach (preg_split('/[\r\n,;|]+/u', $value) ?: [] as $chunk) {
            $chunk = trim($chunk);
            if ($chunk !== '' && filter_var($chunk, FILTER_VALIDATE_URL)) {
                $urls[] = $chunk;
            }
        }

        return $urls;
    }

    /**
     * Elimina vacías, duplicadas, normaliza separadores implícitos.
     *
     * @param  array<int, string>  $urls
     * @return array<int, string>
     */
    public function cleanUrlList(array $urls): array
    {
        $out = [];
        $seen = [];
        foreach ($urls as $u) {
            $u = trim((string) $u);
            if ($u === '') {
                continue;
            }

            foreach ($this->extractUrlsFromString($u) as $candidate) {
                if (filter_var($candidate, FILTER_VALIDATE_URL) && $this->isLikelyImageUrl($candidate)) {
                    $canonicalKey = $this->canonicalUrlKey($candidate);
                    if (isset($seen[$canonicalKey])) {
                        continue;
                    }

                    $seen[$canonicalKey] = true;
                    $out[] = html_entity_decode(trim($candidate), ENT_QUOTES | ENT_HTML5, 'UTF-8');
                }
            }
        }

        return array_values($out);
    }

    protected function isLikelyImageUrl(string $url): bool
    {
        $decoded = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = parse_url($decoded);

        if ($parts === false) {
            return false;
        }

        $path = rawurldecode((string) ($parts['path'] ?? ''));
        $path = strtolower($path);

        if ($path === '' || $path === '/') {
            return false;
        }

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg|avif)(?:$|\?)/i', $path)) {
            return true;
        }

        // URLs de ficha/producto no son imágenes.
        foreach (['/producto/', '/product/', '/categoria/', '/category/'] as $blockedFragment) {
            if (str_contains($path, $blockedFragment)) {
                return false;
            }
        }

        $lastSegment = trim((string) basename($path));
        if ($lastSegment === '') {
            return false;
        }

        // Directorios o marcadores genéricos que aparecen en proveedores como Tico.
        if (in_array($lastSegment, ['large', 'medium', 'small', 'thumb', 'thumbnail', 'image', 'images', 'product', 'producto'], true)) {
            return false;
        }

        // Aceptar algunos patrones típicos de CDN/directorio imagen sin extensión visible.
        if (
            str_contains($path, '/images/')
            || str_contains($path, '/image/')
            || str_contains($path, '/media/')
            || str_contains($path, '/uploads/products/')
            || str_contains($path, '/uploads/images/')
        ) {
            return preg_match('/[a-z0-9_-]{4,}$/i', $lastSegment) === 1;
        }

        return false;
    }

    protected function canonicalUrlKey(string $url): string
    {
        $decoded = html_entity_decode(trim($url), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $parts = parse_url($decoded);

        if ($parts === false) {
            return $decoded;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';
        $path = (string) ($parts['path'] ?? '');
        $path = rawurldecode($path);
        $path = preg_replace('#/+#', '/', $path) ?? $path;
        $path = preg_replace('#/width/\d+/#i', '/width/*/', $path) ?? $path;
        $path = preg_replace('#/image_\d+/#i', '/image_*/', $path) ?? $path;

        // GEWA expone la misma imagen en múltiples tamaños con distinto path.
        if ($host === 'gmedia.gewamusic.com' && preg_match('#/([^/]+\.(?:jpg|jpeg|png|gif|webp))$#i', $path, $matches)) {
            $path = '/preview/' . strtolower($matches[1]);
        }

        return "{$scheme}://{$host}{$port}{$path}";
    }
}
