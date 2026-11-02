<?php
require __DIR__ . '/_auth.php';

if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $stmt = $pdo->prepare('DELETE FROM posts WHERE id = ?');
    $stmt->execute([intval($_POST['id'] ?? 0)]);
    header('Location: posts.php');
    exit;
}
$pdo->exec("ALTER TABLE posts ADD COLUMN IF NOT EXISTS cover_image VARCHAR(255) NULL");
$posts = $pdo->query('SELECT id, title, slug, cover_image, published, created_at FROM posts ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Blog Posts — Admin</title><link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div><div style="margin-left:auto"><a href="index.php">Dashboard</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px">
  <p><a href="index.php">&larr; Dashboard</a></p>
  <h2>Blog Posts <a href="post_edit.php" style="float:right" class="cta">Add Post</a></h2>
  <div class="card"><table style="width:100%;border-collapse:collapse"><thead><tr><th>Title</th><th>Slug</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($posts as $post): ?><tr><td><?php if ($post['cover_image']): ?><img src="../<?php echo htmlspecialchars($post['cover_image']) ?>" alt="" style="height:44px;width:64px;object-fit:cover"> <?php endif; ?><?php echo htmlspecialchars($post['title']) ?></td><td><?php echo htmlspecialchars($post['slug']) ?></td><td><?php echo $post['published'] ? 'Published' : 'Draft' ?></td><td><a href="post_edit.php?id=<?php echo intval($post['id']) ?>">Edit</a> <form method="post" style="display:inline"><input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?php echo intval($post['id']) ?>"><button type="submit">Delete</button></form></td></tr><?php endforeach; ?>
  </tbody></table></div>
</main></body></html>