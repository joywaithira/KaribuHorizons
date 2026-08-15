<?php
require __DIR__ . '/_auth.php';

$id = $_GET['id'] ?? null;
$product = null;
if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        $pid = trim($_POST['id'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $description = trim($_POST['description'] ?? '');

        // generate slug if user didn't provide one
        if (empty($pid) && $title) {
            $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($title));
            $slug = trim($slug, '-');
            if ($slug === '') $slug = uniqid('p_');
            $pid = $slug;
        }

        // image upload with validation and per-product directory
        $imagePath = $product['image'] ?? null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $up = $_FILES['image'];
            if ($up['error'] !== UPLOAD_ERR_OK) {
                $error = 'Image upload error';
            } else {
                $maxBytes = 2 * 1024 * 1024; // 2MB
                if ($up['size'] > $maxBytes) {
                    $error = 'Image too large (max 2MB)';
                } else {
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->file($up['tmp_name']);
                    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                    if (!isset($allowed[$mime])) {
                        $error = 'Invalid image type (use JPG, PNG, GIF)';
                    } else {
                        $ext = $allowed[$mime];
                        $dirSlug = $pid ?: ($product['id'] ?? uniqid('p_'));
                        $uploadDir = __DIR__ . '/../assets/images/curios/' . $dirSlug;
                        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                        $name = uniqid('img_') . '.' . $ext;
                        $dest = $uploadDir . '/' . $name;
                        if (move_uploaded_file($up['tmp_name'], $dest)) {
                            $imagePath = 'assets/images/curios/' . $dirSlug . '/' . $name;
                        } else {
                            $error = 'Failed to move uploaded file';
                        }
                    }
                }
            }
        }

        if ($pid && $title && $error === '') {
            // insert or update while preserving created_at when present
                $sql = 'INSERT INTO products (id, title, price, image, description, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description)';
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute([$pid, $title, $price, $imagePath, $description]);
                header('Location: products.php'); exit;
            } catch (Exception $e) {
                $error = 'DB error: ' . $e->getMessage();
            }
        } else {
            if ($error === '') $error = 'Please provide ID and title';
        }
    }
}

if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Edit Product</title>
<link rel="stylesheet" href="/assets/css/site.css"></head><body>
<main class="container" style="margin-top:20px">
  <h2><?php echo $product ? 'Edit' : 'Add' ?> Product</h2>
  <?php if ($error): ?><div class="card" style="background:#ffecec;color:#900;padding:12px"><?php echo htmlspecialchars($error) ?></div><?php endif; ?>
  <div class="card">
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
      <div class="form-row"><label>ID (slug)</label><input name="id" value="<?php echo htmlspecialchars($product['id'] ?? '')?>" required></div>
      <div class="form-row"><label>Title</label><input name="title" value="<?php echo htmlspecialchars($product['title'] ?? '')?>" required></div>
      <div class="form-row"><label>Price</label><input name="price" value="<?php echo htmlspecialchars($product['price'] ?? '')?>"></div>
      <div class="form-row"><label>Image (optional)</label><input type="file" name="image"></div>
      <?php if(!empty($product['image'])): ?><div class="form-row"><img src="/<?php echo htmlspecialchars($product['image'])?>" style="height:84px"></div><?php endif; ?>
      <div class="form-row"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($product['description'] ?? '')?></textarea></div>
      <div class="form-row"><button class="primary"><?php echo $product ? 'Save' : 'Add' ?></button></div>
    </form>
    </div>
</main>

<script>
// auto-generate slug from title
function slugify(s){
    return s.toString().toLowerCase().trim()
        .replace(/[^a-z0-9]+/g,'-')
        .replace(/^-+|-+$/g,'');
}
document.addEventListener('DOMContentLoaded', function(){
    const title = document.querySelector('input[name="title"]');
    const idIn = document.querySelector('input[name="id"]');
    if (!title || !idIn) return;
    title.addEventListener('input', function(){
        if (!idIn.dataset.userEdited) {
            idIn.value = slugify(title.value);
        }
    });
    // mark as user edited if they change id manually
    idIn.addEventListener('input', function(){ idIn.dataset.userEdited = '1'; });
});
</script>

</body></html>
