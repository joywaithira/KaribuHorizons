<?php
require __DIR__ . '/_auth.php';
$id = intval($_GET['id'] ?? 0);
$post = null;
if ($id) { $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?'); $stmt->execute([$id]); $post = $stmt->fetch(); }
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL");
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) $error = 'Invalid CSRF token.';
    else {
        $title = trim($_POST['title'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $body = trim($_POST['body'] ?? '');
        $published = isset($_POST['published']) ? 1 : 0;
        $coverImage = $post['cover_image'] ?? null;
        if (!empty($_FILES['cover_image']['tmp_name'])) {
            $upload = $_FILES['cover_image'];
            if ($upload['error'] !== UPLOAD_ERR_OK) $error = 'Image upload error.';
            elseif ($upload['size'] > 2 * 1024 * 1024) $error = 'Image too large (max 2MB).';
            else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                if (!isset($allowed[$mime])) $error = 'Invalid image type (use JPG, PNG, or GIF).';
                else {
                    $uploadDir = __DIR__ . '/../assets/images/blog';
                    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) $error = 'Could not create blog image folder.';
                    else {
                        $fileName = uniqid('cover_', true) . '.' . $allowed[$mime];
                        if (move_uploaded_file($upload['tmp_name'], $uploadDir . '/' . $fileName)) $coverImage = 'assets/images/blog/' . $fileName;
                        else $error = 'Failed to move uploaded file.';
                    }
                }
            }
        }
        if (!$slug && $title) $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', strtolower($title)), '-');
        if (!$title || !$slug || !$body) $error = 'Title, slug, and body are required.';
        else {
            try {
                if ($error !== '') throw new RuntimeException($error);
                if ($id) { $stmt = $pdo->prepare('UPDATE posts SET title=?, slug=?, body=?, cover_image=?, published=?, updated_at=NOW() WHERE id=?'); $stmt->execute([$title, $slug, $body, $coverImage, $published, $id]); }
                else { $stmt = $pdo->prepare('INSERT INTO posts (title, slug, body, cover_image, author_id, published) VALUES (?, ?, ?, ?, ?, ?)'); $stmt->execute([$title, $slug, $body, $coverImage, $_SESSION['user_id'], $published]); }
                header('Location: posts.php'); exit;
            } catch (PDOException|RuntimeException $e) { if ($error === '') $error = 'Could not save post. The slug may already exist.'; }
        }
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?php echo $post ? 'Edit' : 'Add' ?> Post — Admin</title><link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div><div style="margin-left:auto"><a href="index.php">Dashboard</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="max-width:800px;margin-top:20px"><p><a href="posts.php">&larr; Blog Posts</a></p><h2><?php echo $post ? 'Edit' : 'Add' ?> Post</h2>
<?php if ($error): ?><div class="card" style="background:#ffecec;color:#900;padding:12px"><?php echo htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card"><form method="post" enctype="multipart/form-data"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']) ?>"><div class="form-row"><label>Title</label><input name="title" value="<?php echo htmlspecialchars($post['title'] ?? '') ?>" required></div><div class="form-row"><label>Slug</label><input name="slug" value="<?php echo htmlspecialchars($post['slug'] ?? '') ?>" required></div><div class="form-row"><label>Body</label><textarea name="body" rows="12" required><?php echo htmlspecialchars($post['body'] ?? '') ?></textarea></div><div class="form-row"><label>Cover image</label><input type="file" name="cover_image" accept="image/jpeg,image/png,image/gif"></div><?php if (!empty($post['cover_image'])): ?><div class="form-row"><img src="../<?php echo htmlspecialchars($post['cover_image']) ?>" alt="Current cover image" style="max-width:220px;max-height:140px"></div><?php endif; ?><div class="form-row"><label><input type="checkbox" name="published" <?php echo !empty($post['published']) ? 'checked' : '' ?>> Published</label></div><button class="primary">Save Post</button></form></div></main></body></html>