-- Optional future MySQL migration for the shop.
-- Run only after backup and only when the staff dashboard/database integration is ready.
CREATE TABLE IF NOT EXISTS shop_orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) UNIQUE NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  email VARCHAR(254) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  county VARCHAR(100) NOT NULL,
  address TEXT NULL,
  delivery_method VARCHAR(30) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  delivery_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL,
  status VARCHAR(30) NOT NULL DEFAULT 'pending_payment',
  payment_status VARCHAR(30) NOT NULL DEFAULT 'pending',
  gateway VARCHAR(50) NULL,
  gateway_reference VARCHAR(255) NULL,
  order_notes TEXT NULL,
  internal_notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status(status), INDEX idx_created(created_at)
);

CREATE TABLE IF NOT EXISTS shop_order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  product_id VARCHAR(64) NOT NULL,
  product_name VARCHAR(255) NOT NULL,
  sku VARCHAR(100) NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  line_total DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES shop_orders(id) ON DELETE CASCADE
);
