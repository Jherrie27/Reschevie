-- SQL inserts for new products from resources images

-- Chain
INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_status, product_featured, product_emoji)
VALUES ('Chain', '1.3 grams of premium Saudi Gold', 'necklace', 'Saudi Arabia', 'Saudi Gold', '22K', '1.3g', NULL, 'available', 0, '⛓');
-- Love Ring
INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_status, product_featured, product_emoji)
VALUES ('Love Ring', '3 grams of premium Saudi Gold', 'ring', 'Saudi Arabia', 'Saudi Gold', '22K', '3g', NULL, 'available', 0, '💍');
-- Paper Clip
INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_status, product_featured, product_emoji)
VALUES ('Paper Clip', '1.4 grams of premium Saudi Gold', 'bracelet', 'Saudi Arabia', 'Saudi Gold', '22K', '1.4g', NULL, 'available', 0, '🔗');
-- TwineGold
INSERT INTO products (product_name, product_description, product_type, product_origin, product_materials, product_karat, product_weight, product_price, product_status, product_featured, product_emoji)
VALUES ('TwineGold', '2.6 grams of premium Saudi Gold', 'bracelet', 'Saudi Arabia', 'Saudi Gold', '22K', '2.6g', NULL, 'available', 0, '🧵');

-- After inserting, link images in product_images table using the new product_id
-- Example:
-- INSERT INTO product_images (product_id, p_image_url, is_primary) VALUES (NEW_ID, 'resources/Chain • 1.3 grams of premium Saudi Gold.jpg', 1);
-- Repeat for each product after you know their product_id.
