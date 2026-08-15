<?php
session_start();
if (!isset($_SESSION['csrf'])) {
	$_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Checkout — Karibu Horizons</title>
	<link rel="stylesheet" href="assets/css/site.css">
</head>
<body>
<nav class="site-nav">
	<div class="container nav-inner">
		<div class="brand">
			<div class="logo-mark">KH</div>
			<div>
				<h1>Karibu Horizons</h1>
				<p>Bringing Africa's Heritage to Your Home</p>
			</div>
		</div>
		<button class="mobile-toggle" aria-label="Menu">☰</button>
		<div class="nav-links">
			<a href="index.html">Home</a>
			<a href="about.html">About</a>
			<a href="packages.html">Packages</a>
			<a href="blog.html">Blog</a>
			<a href="shop.html">Shop</a>
			<div class="dropdown">
				<button class="dropdown-toggle">Trips ▾</button>
				<div class="dropdown-menu">
					  <a href="safaritours.html">Safari Trips</a>
					<a href="kenyantrips.html">Kenyan Trips</a>
					<a href="internationaltrips.html">International Trips</a>
				</div>
			</div>
			<div class="dropdown">
				<button class="dropdown-toggle">About Us ▾</button>
				<div class="dropdown-menu">
					<a href="whyus.html">Why Us</a>
					<a href="contact.html">Contact</a>
					<a href="terms.html">Terms</a>
				</div>
			</div>
		</div>
	</div>
</nav>

<main class="container">
	<h2>Checkout</h2>
	<div class="cards">
		<div class="card" id="cartCard">
			<h3>Your Cart</h3>
			<div id="cartItems" class="muted">Loading items...</div>
		</div>
			<div class="card">
				<h3>Delivery & Payment</h3>
				<form id="checkoutForm" method="post" action="handlers/checkout.php">
					<input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf']); ?>">
					<input type="hidden" name="cart" id="cartInput">
					<div class="form-row"><input id="name" name="name" placeholder="Full name" required></div>
					<div class="form-row"><input id="phone" name="phone" placeholder="Phone (WhatsApp preferred)" required></div>
					<div class="form-row"><input id="address" name="address" placeholder="Delivery address" required></div>
					<div class="form-row"><button type="submit" class="primary">Place order</button></div>
				</form>
			</div>
	</div>
</main>

<script src="assets/js/site.js"></script>
<script>
async function renderCart(){
	const cart = JSON.parse(localStorage.getItem('kh_cart')||'[]');
	const el=document.getElementById('cartItems');
	if(cart.length===0){ el.innerHTML='<div class="muted">Your cart is empty.</div>'; return }
	const res = await fetch('assets/data/products.json'); const products = await res.json();
	const table = document.createElement('div'); table.style.display='flex'; table.style.flexDirection='column'; table.style.gap='12px';
	let total=0;
	cart.forEach(ci=>{
		const p = products.find(pp=>pp.id===ci.id) || {title:ci.id,price:0};
		total += (p.price||0)*ci.qty;
		const row = document.createElement('div'); row.style.display='flex'; row.style.justifyContent='space-between';
		row.innerHTML = `<div>${p.title} <span class="muted">x${ci.qty}</span></div><div>${(p.price||0).toFixed(2)}</div>`;
		table.appendChild(row);
	});
	const tot = document.createElement('div'); tot.className='totals'; tot.textContent = 'Total: $'+total.toFixed(2);
	el.innerHTML=''; el.appendChild(table); el.appendChild(tot);
}

	// attach cart JSON to hidden input before submit
	document.getElementById('checkoutForm').addEventListener('submit', function(e){
		const cart = JSON.parse(localStorage.getItem('kh_cart')||'[]');
		document.getElementById('cartInput').value = JSON.stringify(cart);
	});

	// if redirected back with success flag, clear localStorage and show a message
	if (new URLSearchParams(window.location.search).get('success') === '1'){
		localStorage.removeItem('kh_cart');
		alert('Thank you! Your order was received. We will contact you on WhatsApp for confirmation.');
	}
	renderCart();
</script>
<footer class="site-footer">
	<div class="footer-inner">
		<div>
			<div class="footer-brand">
				<div class="logo-mark">KH</div>
				<div>
					<strong>Karibu Horizons</strong>
					<div class="footer-note">Bringing Africa's Heritage to Your Home</div>
				</div>
			</div>
			<div class="footer-note">Email: info@karibuhorizons.co.ke • WhatsApp: +254 711 595474</div>
		</div>
		<div class="footer-links">
			<strong>Orders</strong>
			<a href="checkout.php">Checkout</a>
			<a href="shop.html">Shop</a>
		</div>
		<div>
			<strong>Follow us</strong>
			<div class="socials">
				<a href="#">Facebook</a>
				<a href="#">Instagram</a>
			</div>
			<div class="footer-note">© Karibu Horizons. Support African artisans. Designed with care.</div>
		</div>
	</div>
</footer>
</body>
</html>
