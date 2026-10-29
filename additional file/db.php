<?php
$host = 'localhost';
$db   = 'db_karenderya';
$user = 'root'; // Change if your DB username is different
$pass = '';     // Change if your DB password is different
$charset = 'utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $serverPdo = new PDO("mysql:host=$host;charset=$charset", $user, $pass, $options);
    $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `db_karenderya` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT NOT NULL AUTO_INCREMENT,
        order_number VARCHAR(30) NOT NULL UNIQUE,
        student_name VARCHAR(120) NOT NULL,
        student_id VARCHAR(60) NOT NULL,
        payment_method ENUM('cash','gcash','bank_transfer') NOT NULL,
        payment_status ENUM('pay_on_pickup','paid') NOT NULL DEFAULT 'pay_on_pickup',
        total_amount DECIMAL(10,2) NOT NULL,
        items_json LONGTEXT NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $orderMigrations = [
        "ALTER TABLE orders ADD COLUMN customer_id INT NULL AFTER id",
        "ALTER TABLE orders ADD COLUMN fulfillment_type ENUM('pickup','dine_in') NOT NULL DEFAULT 'pickup' AFTER payment_status",
        "ALTER TABLE orders ADD COLUMN preferred_time VARCHAR(40) NOT NULL DEFAULT '' AFTER fulfillment_type",
        "ALTER TABLE orders ADD COLUMN special_instructions VARCHAR(500) NOT NULL DEFAULT '' AFTER preferred_time",
        "ALTER TABLE orders ADD COLUMN order_status ENUM('pending','preparing','ready','completed','cancelled') NOT NULL DEFAULT 'pending' AFTER special_instructions",
    ];
    foreach ($orderMigrations as $migration) {
        try { $pdo->exec($migration); } catch (PDOException $exception) { /* Existing installations already have this column. */ }
    }
} catch (\PDOException $e) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL in XAMPP and refresh this page.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS menu_products (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    category VARCHAR(80) NOT NULL,
    description VARCHAR(255) NOT NULL DEFAULT '',
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    image_path VARCHAR(255) NOT NULL DEFAULT '',
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    stock_quantity INT NOT NULL DEFAULT 0,
    stock_enabled TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Upgrade existing databases created before the Snacks category was added.
try { $pdo->exec("ALTER TABLE menu_products MODIFY category VARCHAR(80) NOT NULL"); } catch (PDOException $exception) { }
foreach ([
    "ALTER TABLE menu_products ADD COLUMN stock_quantity INT NOT NULL DEFAULT 0 AFTER is_available",
    "ALTER TABLE menu_products ADD COLUMN stock_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER stock_quantity",
] as $migration) {
    try { $pdo->exec($migration); } catch (PDOException $exception) { }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS canteen_profile (
    id TINYINT NOT NULL PRIMARY KEY,
    canteen_name VARCHAR(160) NOT NULL DEFAULT 'Romy and Allit Food Haven',
    operating_hours VARCHAR(160) NOT NULL DEFAULT 'Monday - Saturday, 6:00 AM - 5:00 PM',
    contact_info VARCHAR(255) NOT NULL DEFAULT '',
    pickup_location VARCHAR(255) NOT NULL DEFAULT 'Main pickup counter',
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$pdo->exec("INSERT IGNORE INTO canteen_profile (id) VALUES (1)");

$pdo->exec("CREATE TABLE IF NOT EXISTS product_categories (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL UNIQUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
$categoryInsert = $pdo->prepare('INSERT IGNORE INTO product_categories (name) VALUES (?)');
foreach (['mains', 'breakfast', 'snacks', 'drinks'] as $category) $categoryInsert->execute([$category]);

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM menu_products')->fetchColumn();
if ($productCount === 0) {
    $seedProducts = [
        ['Pork Adobo', 'mains', 'Filipino-style pork braised in savory sauce', 40, 'Dishes/Pork Adobo.jpeg'],
        ['Menudo', 'mains', 'Classic Filipino pork and tomato stew', 40, 'Dishes/Menudo.jpeg'],
        ['Pork Sinigang', 'mains', 'Sour, comforting soup with gabi', 40, 'Dishes/Pork Sinigang.jpeg'],
        ['Fried Chicken', 'mains', 'Crispy, golden and freshly cooked', 40, 'Dishes/Fried Chicken.jpeg'],
        ['Egado', 'mains', 'Savory Ilocano pork stew', 40, 'Dishes/Egado.jpeg'],
        ['Sweet and Sour', 'mains', 'Tangy sweet and sour favorite', 40, 'Dishes/Sweet and Sour.jpeg'],
        ['Chicken Adobo', 'mains', 'Tender chicken simmered in savory adobo sauce', 40, 'Dishes/Chicken Adobo.jpeg'],
        ['Longganisa, Hotdog and Egg', 'breakfast', 'Breakfast favorites served together', 40, 'Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg'],
        ['Luncheon Meat and Egg', 'breakfast', 'Seared luncheon meat with egg', 40, 'Breakfast/95e538ce-91f3-4275-bfc2-e197714c097a.jpeg'],
        ['Siomai', 'breakfast', 'Steamed savory siomai', 40, 'Breakfast/eac20c86-2f3f-44f6-9459-b30a5f51ee4f.jpeg'],
        ['Coke', 'drinks', 'Chilled Coca-Cola', 40, 'Drink/Coke.jpeg'],
        ['Mountain Dew', 'drinks', 'Chilled Mountain Dew', 40, 'Drink/Mountain Dew.jpeg'],
    ];
    $insertProduct = $pdo->prepare('INSERT INTO menu_products (name, category, description, price, image_path) VALUES (?, ?, ?, ?, ?)');
    foreach ($seedProducts as $product) {
        $insertProduct->execute($product);
    }
    
/*
|--------------------------------------------------------------------------
| Email OTP Verification Table
|--------------------------------------------------------------------------
| Stores hashed OTPs, expiration times, attempts, and usage status.
*/

$pdo->exec("
    CREATE TABLE IF NOT EXISTS email_otps (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        email VARCHAR(255) NOT NULL,
        otp_hash VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
        used_at DATETIME NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        INDEX idx_email_otps_email (email),
        INDEX idx_email_otps_expiry (expires_at)
    ) ENGINE=InnoDB
      DEFAULT CHARSET=utf8mb4
      COLLATE=utf8mb4_general_ci
");

}
?>
