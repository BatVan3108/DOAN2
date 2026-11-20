-- Tài khoản (admin + khách hàng) KHÔNG nằm trong file này: web tự tạo CSDL riêng doan2_accounts để xóa/import lại doan2 không mất mật khẩu.
CREATE DATABASE IF NOT EXISTS doan2 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE doan2;
CREATE TABLE categories(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL);
CREATE TABLE brands(id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL);
CREATE TABLE products(
 id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(200) NOT NULL,
 category_id INT NOT NULL, brand_id INT NOT NULL,
 price INT NOT NULL, old_price INT NULL, description TEXT,
 is_new TINYINT(1) DEFAULT 0, stock INT NOT NULL DEFAULT 20, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(category_id) REFERENCES categories(id), FOREIGN KEY(brand_id) REFERENCES brands(id));
CREATE TABLE announcements(id INT AUTO_INCREMENT PRIMARY KEY, type ENUM('thongbao','khuyenmai') NOT NULL,
 title VARCHAR(200) NOT NULL, content TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 category VARCHAR(50) NULL, kind VARCHAR(30) NULL, applies VARCHAR(255) NULL, image VARCHAR(120) NULL);
CREATE TABLE orders(id INT AUTO_INCREMENT PRIMARY KEY, product_id INT NOT NULL, qty INT NOT NULL,
 customer VARCHAR(120) NOT NULL, phone VARCHAR(20) NOT NULL, email VARCHAR(120), address VARCHAR(255) NOT NULL, note TEXT,
 status ENUM('moi','daxuly') DEFAULT 'moi', created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 customer_id INT NULL, total INT NULL, receive VARCHAR(20) NULL, ship VARCHAR(40) NULL, pay VARCHAR(20) NULL, extras VARCHAR(255) NULL, invoice TINYINT(1) NOT NULL DEFAULT 0,
 FOREIGN KEY(product_id) REFERENCES products(id));
CREATE TABLE pricelists(id INT AUTO_INCREMENT PRIMARY KEY, original_name VARCHAR(255) NOT NULL,
 stored_name VARCHAR(100) NOT NULL, uploaded_by INT, uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP);

INSERT INTO categories(name) VALUES('Laptop'),('Máy tính để bàn'),('Màn hình'),('Linh kiện'),('Phụ kiện');
INSERT INTO brands(name) VALUES('Dell'),('ASUS'),('HP'),('Lenovo'),('Acer'),('Logitech');
INSERT INTO products(name,category_id,brand_id,price,old_price,description,is_new) VALUES
('Dell Inspiron 15 3530',1,1,13990000,15490000,'Core i5-1335U, RAM 16GB, SSD 512GB, màn hình 15.6 inch FHD 120Hz.',1),
('ASUS Vivobook 14 OLED',1,2,16490000,NULL,'Core i5-13500H, RAM 16GB, SSD 512GB, màn hình OLED 14 inch 2.8K.',1),
('HP Pavilion 15',1,3,18900000,20900000,'Core i7-1355U, RAM 16GB, SSD 512GB, pin 8 giờ, vỏ kim loại.',0),
('Lenovo LOQ 15 Gaming',1,4,24990000,NULL,'Ryzen 7 7840HS, RTX 4050 6GB, RAM 16GB, SSD 512GB, 144Hz.',1),
('Acer Nitro V 15',1,5,22490000,24990000,'Core i5-13420H, RTX 4050, RAM 16GB, SSD 512GB.',0),
('Dell OptiPlex 7010 SFF',2,1,9800000,NULL,'Core i5-13500, RAM 8GB, SSD 256GB, nhỏ gọn cho văn phòng.',0),
('HP ProDesk 400 G9',2,3,11900000,12900000,'Core i5-12500, RAM 16GB, SSD 512GB, Windows 11 bản quyền.',0),
('Lenovo ThinkCentre M70s',2,4,10500000,NULL,'Core i3-13100, RAM 8GB, SSD 256GB, bảo hành 36 tháng.',1),
('Dell UltraSharp U2723QE 27"',3,1,14500000,16500000,'4K IPS Black, USB-C 90W, phủ 98% DCI-P3.',0),
('ASUS TUF VG249Q3A 24"',3,2,4390000,4990000,'FHD IPS 180Hz, 1ms, FreeSync Premium.',1),
('Acer Nitro XV272U 27"',3,5,6290000,NULL,'2K IPS 170Hz, HDR400, loa tích hợp.',0),
('SSD ASUS ROG Strix 1TB NVMe',4,2,2490000,2890000,'PCIe 4.0 x4, đọc 7.000MB/s, ghi 6.500MB/s.',0),
('RAM Acer Predator 16GB DDR5',4,5,1390000,NULL,'Bus 6000MHz, tản nhiệt nhôm, kit 1x16GB.',1),
('Chuột Logitech MX Master 3S',5,6,2290000,2590000,'8000 DPI, yên lặng, cuộn MagSpeed, kết nối 3 thiết bị.',0),
('Bàn phím Logitech MX Keys S',5,6,2390000,NULL,'Phím cắt cầu, đèn nền thông minh, sạc USB-C.',1),
('Tai nghe Logitech G435',5,6,1190000,1390000,'Không dây Lightspeed, nhẹ 165g, micro tích hợp.',0);
INSERT INTO announcements(type,title,content) VALUES
('thongbao','Giờ mở cửa','Cửa hàng mở cửa 8:00 – 21:00 mỗi ngày, kể cả Chủ nhật.'),
('thongbao','Cập nhật bảng giá','Bảng giá được cập nhật vào thứ Hai hằng tuần. Tải bảng giá mới nhất ở mục Bảng giá.'),
('thongbao','Bảo hành','Laptop và PC chính hãng bảo hành tối thiểu 12 tháng, 1 đổi 1 trong 30 ngày đầu.'),
('khuyenmai','Mùa tựu trường: giảm đến 2.000.000đ','Áp dụng cho laptop Dell, HP và ASUS khi mua kèm balo.'),
('khuyenmai','Tặng chuột + bàn di cho đơn laptop gaming','Số lượng có hạn, áp dụng đến hết tháng.'),
('khuyenmai','Trả góp 0% qua thẻ tín dụng','Kỳ hạn 6 – 12 tháng, không phí chuyển đổi.');
INSERT INTO brands(name) VALUES('Apple'),('MSI'),('Samsung'),('LG'),('Gigabyte'),('Kingston');
INSERT INTO products(name,category_id,brand_id,price,old_price,description,is_new) VALUES
('MacBook Air 15 M5 2026 10CPU/10GPU/32GB/4TB',1,(SELECT id FROM brands WHERE name='Apple'),92990000,102490000,'Chip M5, RAM 32GB, SSD 4TB, màn hình Liquid Retina 15.3 inch, sạc USB-C 70W.',1),
('MacBook Air 13 M4 16GB/256GB',1,(SELECT id FROM brands WHERE name='Apple'),24990000,26990000,'Chip M4, RAM 16GB, SSD 256GB, pin đến 18 giờ.',0),
('MSI Prestige 14 AI+ D3MG',1,(SELECT id FROM brands WHERE name='MSI'),44490000,47990000,'Core Ultra 7, RAM 32GB, SSD 1TB, màn hình OLED 14 inch, vỏ kim loại.',1),
('MSI Thin 15 B13VE',1,(SELECT id FROM brands WHERE name='MSI'),19990000,NULL,'Core i5-13420H, RTX 4050, RAM 16GB, SSD 512GB, 144Hz.',0),
('Samsung Odyssey G5 27" 2K 165Hz',3,(SELECT id FROM brands WHERE name='Samsung'),6990000,7990000,'Tấm nền VA cong 1000R, 2K, 165Hz, 1ms, HDR10.',1),
('LG UltraGear 27GS75Q 27" 180Hz',3,(SELECT id FROM brands WHERE name='LG'),6490000,NULL,'IPS 2K 180Hz, 1ms, HDR10, G-Sync tương thích.',0),
('Mainboard Gigabyte B650 AORUS Elite AX',4,(SELECT id FROM brands WHERE name='Gigabyte'),5290000,5790000,'Socket AM5, DDR5, PCIe 5.0, WiFi 6E.',0),
('SSD Kingston NV3 1TB NVMe',4,(SELECT id FROM brands WHERE name='Kingston'),1990000,NULL,'PCIe 4.0 x4, đọc 6.000MB/s, ghi 5.000MB/s.',1);
CREATE TABLE IF NOT EXISTS order_items(id INT AUTO_INCREMENT PRIMARY KEY, order_id INT NOT NULL, product_id INT NOT NULL, qty INT NOT NULL, price INT NOT NULL, old_price INT NULL, INDEX(order_id));
