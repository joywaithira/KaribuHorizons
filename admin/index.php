<?php
require __DIR__ . '/_auth.php';
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Dashboard</title>
<link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><div class="logo-mark">KH</div><div><h1>Admin</h1><p class="muted">Karibu Horizons</p></div></div>
<div style="margin-left:auto"><a href="/">View Site</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px">
  <h2>Dashboard</h2>
  <div class="cards">
    <div class="card"><h3>Products</h3><p class="muted">Manage shop products</p><p><a href="products.php">Open</a></p></div>
    <div class="card"><h3>Blog Posts</h3><p class="muted">Create and edit posts</p><p><a href="#">Open</a></p></div>
    <div class="card"><h3>Trips & Packages</h3><p class="muted">Manage trip pages</p><p><a href="#">Open</a></p></div>
    <div class="card"><h3>Orders</h3><p class="muted">View and fulfil orders</p><p><a href="#">Open</a></p></div>
  </div>
</main></body></html>
