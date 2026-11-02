<?php
require __DIR__ . '/_auth.php';

$productFile = __DIR__ . '/../shop/data/products.json';
function loadAdminProducts(string $file): array {
    $products = is_file($file) ? json_decode((string) file_get_contents($file), true) : [];
    return is_array($products) ? array_values($products) : [];
}
function saveAdminProducts(string $file, array $products): bool {
    $dir = dirname($file);
    $lock = fopen($dir . '/products.lock', 'c');
    if (!$lock) return false;
    flock($lock, LOCK_EX);
    $saved = file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), LOCK_EX);
    flock($lock, LOCK_UN);
    fclose($lock);
    return $saved !== false;
}
function slugAdmin(string $value): string {
    $slug = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $value), '-'));
    return $slug;
}

if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$error = '';
$message = '';
$products = loadAdminProducts($productFile);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'delete') {
            $id = trim((string) ($_POST['id'] ?? ''));
            $products = array_values(array_filter($products, fn($product) => (string) ($product['id'] ?? '') !== $id));
            if (!saveAdminProducts($productFile, $products)) $error = 'Could not save the product catalogue.';
            else $message = 'Product deleted.';
        } else {
            $id = trim((string) ($_POST['id'] ?? ''));
            $name = trim((string) ($_POST['name'] ?? ''));
            $slug = trim((string) ($_POST['slug'] ?? ''));
            $category = trim((string) ($_POST['category'] ?? ''));
            $sku = trim((string) ($_POST['sku'] ?? ''));
            $mainImage = trim((string) ($_POST['main_image'] ?? '/assets/images/curios/curios1.jpeg'));
            $price = max(0, (int) ($_POST['price'] ?? 0));
            $stock = max(0, (int) ($_POST['stock'] ?? 0));

            if (!$name || !$category || !$sku) {
                $error = 'Name, category, and SKU are required.';
            } else {
                if ($id === '') {
                    $id = 'product-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
                    $existing = loadAdminProducts($productFile);
                    $createdAt = gmdate('Y-m-d');
                } else {
                    $existing = $products;
                    $createdAt = gmdate('Y-m-d');
                    foreach ($products as $product) {
                        if ((string) ($product['id'] ?? '') === $id) $createdAt = (string) ($product['created_at'] ?? $createdAt);
                    }
                }
                if (!$slug) $slug = slugAdmin($name);

                if (!empty($_FILES['image']['tmp_name'])) {
                    $upload = $_FILES['image'];
                    if ($upload['error'] === UPLOAD_ERR_OK) {
                        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                        $types = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
                        if (!isset($types[$mime])) $error = 'Invalid image type. Use JPG, PNG, GIF, or WebP.';
                        elseif ($upload['size'] > 3 * 1024 * 1024) $error = 'Image must be 3 MB or smaller.';
                        else {
                            $dir = __DIR__ . '/../assets/images/curios';
                            if (!is_dir($dir) && !mkdir($dir, 0755, true)) $error = 'Could not create the image folder.';
                            else {
                                $filename = 'product_' . uniqid('', true) . '.' . $types[$mime];
                                if (move_uploaded_file($upload['tmp_name'], $dir . '/' . $filename)) $mainImage = '/assets/images/curios/' . $filename;
                                else $error = 'Could not upload the image.';
                            }
                        }
                    } else $error = 'Image upload failed.';
                }

                if (!$error) {
                    $product = [
                        'id' => $id, 'name' => $name, 'slug' => $slug, 'category' => $category,
                        'short_description' => trim((string) ($_POST['short_description'] ?? '')),
                        'full_description' => trim((string) ($_POST['full_description'] ?? '')),
                        'price' => $price, 'stock' => $stock, 'sku' => $sku, 'main_image' => $mainImage,
                        'additional_images' => [], 'weight_size' => trim((string) ($_POST['weight_size'] ?? '')),
                        'active' => isset($_POST['active']), 'featured' => isset($_POST['featured']), 'created_at' => $createdAt,
                    ];
                    $found = false;
                    foreach ($products as $index => $old) {
                        if ((string) ($old['id'] ?? '') === $id) { $products[$index] = $product; $found = true; break; }
                    }
                    if (!$found) $products[] = $product;
                    if (!saveAdminProducts($productFile, $products)) $error = 'Could not save the product catalogue.';
                    else { $message = 'Product saved.'; $products = loadAdminProducts($productFile); }
                }
            }
        }
    }
}

$editId = trim((string) ($_GET['edit'] ?? ''));
$edit = null;
foreach ($products as $product) if ((string) ($product['id'] ?? '') === $editId) $edit = $product;
$blank = ['id'=>'','name'=>'','slug'=>'','category'=>'','short_description'=>'','full_description'=>'','price'=>'','stock'=>'','sku'=>'','main_image'=>'/assets/images/curios/curios1.jpeg','weight_size'=>'','active'=>true,'featured'=>false];
$form = $edit ?: $blank;
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Shop Products — Admin</title><link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div><div style="margin-left:auto"><a href="index.php">Dashboard</a> | <a href="/shop/products.php" target="_blank">View Shop</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px"><p><a href="index.php">&larr; Dashboard</a></p><h2>Shop Catalogue</h2>
<?php if ($error): ?><div class="card" style="background:#ffecec;color:#900;padding:12px"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($message): ?><div class="card" style="background:#eaf6ea;color:#246; padding:12px"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:minmax(300px,1fr) minmax(300px,2fr);gap:18px"><div class="card"><h3><?= $edit ? 'Edit product' : 'Add product' ?></h3><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= htmlspecialchars((string) $form['id']) ?>"><label>Name<input name="name" value="<?= htmlspecialchars((string) $form['name']) ?>" required></label><label>Slug<input name="slug" value="<?= htmlspecialchars((string) $form['slug']) ?>" placeholder="Generated automatically"></label><label>Category<input name="category" value="<?= htmlspecialchars((string) $form['category']) ?>" required></label><label>SKU<input name="sku" value="<?= htmlspecialchars((string) $form['sku']) ?>" required></label><label>Price (KES)<input name="price" type="number" min="0" value="<?= htmlspecialchars((string) $form['price']) ?>" required></label><label>Stock<input name="stock" type="number" min="0" value="<?= htmlspecialchars((string) $form['stock']) ?>" required></label><label>Short description<textarea name="short_description" rows="3"><?= htmlspecialchars((string) $form['short_description']) ?></textarea></label><label>Full description<textarea name="full_description" rows="5"><?= htmlspecialchars((string) $form['full_description']) ?></textarea></label><label>Weight / size<input name="weight_size" value="<?= htmlspecialchars((string) $form['weight_size']) ?>"></label><label>Main image path<input name="main_image" value="<?= htmlspecialchars((string) $form['main_image']) ?>"></label><label>Or upload image<input name="image" type="file" accept="image/jpeg,image/png,image/gif,image/webp"></label><label><input type="checkbox" name="active" <?= !empty($form['active']) ? 'checked' : '' ?>> Active in shop</label><label><input type="checkbox" name="featured" <?= !empty($form['featured']) ? 'checked' : '' ?>> Featured</label><button class="primary" type="submit">Save Product</button></form></div>
<div class="card"><h3>Products</h3><table style="width:100%;border-collapse:collapse"><thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead><tbody><?php foreach ($products as $product): ?><tr><td><?= htmlspecialchars((string) $product['name']) ?><br><small><?= htmlspecialchars((string) $product['sku']) ?></small></td><td>KSh <?= number_format((float) ($product['price'] ?? 0), 2) ?></td><td><?= (int) ($product['stock'] ?? 0) ?></td><td><?= !empty($product['active']) ? 'Active' : 'Hidden' ?></td><td><a href="shop_products.php?edit=<?= urlencode((string) $product['id']) ?>">Edit</a><form method="post" style="display:inline" onsubmit="return confirm('Delete this product?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= htmlspecialchars((string) $product['id']) ?>"><button type="submit" class="secondary" style="margin-left:8px">Delete</button></form></td></tr><?php endforeach; ?></tbody></table></div></div></main></body></html>
