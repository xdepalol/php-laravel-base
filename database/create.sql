CREATE DATABASE plaravel_base CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lbaseuser'@'localhost' IDENTIFIED BY 'V7LX14t0w0MTY0';
GRANT ALL PRIVILEGES ON plaravel_base.* TO 'lbaseuser'@'localhost';
FLUSH PRIVILEGES;