<?php
function shop_catalog(): array {
    static $catalog = null;
    if ($catalog !== null) return $catalog;
    $file = __DIR__ . '/../data/products.json';
    $decoded = json_decode((string)file_get_contents($file), true);
    if (!is_array($decoded)) return [];
    $catalog = [];
    foreach ($decoded as $product) {
        if (!empty($product['active'])) $catalog[$product['id']] = $product;
    }
    return $catalog;
}
function shop_product(string $id): ?array { return shop_catalog()[$id] ?? null; }
function shop_category_list(): array { $categories=[]; foreach(shop_catalog() as $p){$categories[$p['category']]=$p['category'];} ksort($categories); return array_values($categories); }
