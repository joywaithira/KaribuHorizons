<?php
require __DIR__ . '/_auth.php';

$stmt = $pdo->query('SELECT id, title, price, image FROM products ORDER BY created_at DESC');
$products = $stmt->fetchAll();
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Products — Admin</title>
<link rel="stylesheet" href="/assets/css/site.css"></head><body>
<main class="container" style="margin-top:20px">
  <h2>Products <a href="product_edit.php" style="float:right" class="cta">Add Product</a></h2>
  <div class="card">
    <table style="width:100%;border-collapse:collapse">
      <thead><tr><th>ID</th><th>Title</th><th>Price</th><th>Image</th><th></th></tr></thead>
      <tbody>
      <?php foreach($products as $p): ?>
        <tr>
          <td><?php echo htmlspecialchars($p['id'])?></td>
          <td><?php echo htmlspecialchars($p['title'])?></td>
          <td><?php echo htmlspecialchars($p['price'])?></td>
          <td><?php if($p['image']): ?><img src="/<?php echo htmlspecialchars($p['image'])?>" style="height:44px"><?php endif; ?></td>
          <td><a href="product_edit.php?id=<?php echo urlencode($p['id'])?>">Edit</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main></body></html>
