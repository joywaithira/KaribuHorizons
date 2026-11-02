<?php
$current = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="site-nav shop-nav" aria-label="Main navigation">
  <div class="container nav-inner">
    <a class="brand shop-brand" href="/index.html" aria-label="Return to TraveL Den Adventures home">
      <img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures">
      <p>Explore Africa. Experience the World.</p>
    </a>
    <div class="shop-nav-actions">
      <a href="/shop/">Shop</a>
      <a href="/shop/products.php">Products</a>
      <a class="cart-link" href="/shop/cart.php" aria-label="Shopping cart with <?= cart_count() ?> items">Cart <span><?= cart_count() ?></span></a>
    </div>
  </div>
</nav>
<main class="shop-main">
