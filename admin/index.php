<?php
require __DIR__ . '/_auth.php';
?>
<!doctype html>
<html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Dashboard</title>
<link rel="stylesheet" href="/assets/css/site.css"></head><body>
<nav class="site-nav"><div class="container nav-inner"><div class="brand"><img class="site-logo" src="/assets/images/TransparentTravelDen-Logo.png" alt="TraveL Den Adventures"><div><h1>Admin</h1></div></div>
<div style="margin-left:auto"><a href="/">View Site</a> | <a href="logout.php">Logout</a></div></div></nav>
<main class="container" style="margin-top:20px">
  <h2>Dashboard</h2>
  <div class="cards">
    <div class="card"><h3>Blog Posts</h3><p class="muted">Create and edit posts</p><p><a href="posts.php">Open</a></p></div>
    <div class="card"><h3>Trips & Packages</h3><p class="muted">Manage each public trip category</p><p><a href="safari_trips.php">Safari</a> | <a href="kenyan_trips.php">Kenyan</a> | <a href="international_trips.php">International</a> | <a href="package_trips.php">Packages</a></p></div>
    <div class="card"><h3>Trip Bookings</h3><p class="muted">Review customer trip reservations</p><p><a href="bookings.php">Open</a></p></div>
  </div>
</main></body></html>
