-- Seed products for Karibu Horizons
-- Import this file in phpMyAdmin: Select database -> Import -> choose this file -> Go

INSERT INTO products (id, title, price, image, description) VALUES
('spoons','Wildlife Wooden Spoons',12.99,'assets/images/curios/curio1.jpeg','Hand-carved wooden spoons with zebra and giraffe handle designs. Food-safe finish.')
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description);

INSERT INTO products (id, title, price, image, description) VALUES
('salad-set','Safari Wooden Salad Set',24.99,'assets/images/curios/curio2.jpeg','Unique hand-carved zebra & giraffe handles — ideal for buffets and home décor.')
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description);

INSERT INTO products (id, title, price, image, description) VALUES
('zebra-bowl','Hand-carved Zebra Decorative Bowl',34.50,'assets/images/curios/curio3.jpeg','Elegant and functional zebra bowl — perfect as a fruit bowl or centerpiece.')
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description);

INSERT INTO products (id, title, price, image, description) VALUES
('figurines','Safari Animal Figurines',18.00,'assets/images/curios/curio4.jpeg','Choose from Zebra, Elephant, or Lion figurines. Great souvenirs and decor.')
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description);

INSERT INTO products (id, title, price, image, description) VALUES
('curio5','Handcrafted Decorative Plate',28.00,'assets/images/curios/curio5.jpeg','A beautifully finished plate showcasing local carving techniques.')
ON DUPLICATE KEY UPDATE title=VALUES(title), price=VALUES(price), image=VALUES(image), description=VALUES(description);
