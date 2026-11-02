# TraveL Den Adventures Shop

The shop is isolated under `/shop/` and uses PHP sessions plus a JSON catalogue and JSON order records. It does not replace the existing static travel website.

## Requirements
- PHP 8.1+ with JSON, OpenSSL and session support
- Apache or LiteSpeed/.htaccess support on Hostinger
- HTTPS for secure cookies

## Sample state
All catalogue entries are clearly marked samples and are not real offers. Delivery fees are placeholders. Orders remain `pending_payment`; no payment gateway is called and payment is never marked paid by the shop.

## Configuration
- Catalogue: `data/products.json`
- Fees: `includes/config.php`
- Orders: `data/orders.json`, protected by `data/.htaccess`
- Logs: `data/shop-errors.log`, protected by `data/.htaccess`

The JSON prototype is suitable for low-volume initial use only. Before production scale, migrate products and orders to MySQL using `db/shop_schema.sql` and add an authenticated staff dashboard. Do not serve `orders.json` by removing its `.htaccess` protection.

## Adding products
Copy a catalogue object, change all fields, set `active: true`, and set an accurate `stock`, `price`, SKU, image paths, size/weight and created date. Replace the sample images with approved real product images. Never label verified real stock as a sample after replacement.

## Updating delivery fees
Edit only `includes/config.php` under the `delivery` array. The placeholder fees are `local: 500 KES`, `courier: 1200 KES`, and `pickup: 0 KES`.

## Pesapal integration
The `includes/payment/` classes define a modular gateway boundary. Implement test-mode hosted checkout in `PesapalGateway.php` only after credentials are supplied through protected server environment variables or hosting configuration. Never place credentials in source, `.env` committed to Git, HTML, JavaScript, CSS, JSON, or public directories. The server must verify callback status, amount and order ID, record the gateway reference, handle duplicate callbacks and never trust browser success parameters.

## Order management
The JSON version contains authoritative order records and supports no public admin page. A future staff dashboard should authenticate existing users, use server-side sessions, use the existing database, search orders, update allowed statuses, and add internal notes. The optional `shop_orders` and `shop_order_items` tables are in `db/shop_schema.sql`; migration requires an implementation of prepared statements and staff authentication.

## Security
- Session cart and totals are server-side.
- Product prices and availability are re-read from the catalogue before an order is created.
- Checkout forms use CSRF tokens and session duplicate-order nonces.
- Customer output uses HTML escaping.
- Checkout rate limiting is session-based and is a basic prototype control, not a distributed production rate limiter.
- PHP errors are hidden from browsers and logged inside the protected `/shop/data` directory.
- Orders are stored with a file lock and protected from direct browser access by Apache/LiteSpeed `.htaccess`.

## Local test server
From the project root:

```bash
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000/shop/`. PHP sessions and JSON writes require the checkout process to run from a writable project directory. Use only your local machine and test products.
