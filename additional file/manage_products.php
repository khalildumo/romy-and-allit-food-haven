<?php
require __DIR__ . '/auth.php';
if (!currentUser() || currentUser()['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied.');
}

if (empty($_SESSION['product_csrf'])) {
    $_SESSION['product_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$editProduct = null;
$categoryRows = $pdo->query('SELECT name FROM product_categories ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
$categoryLabels = array_combine($categoryRows, array_map('ucfirst', $categoryRows));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['product_csrf'], $token)) {
        http_response_code(400);
        exit('Invalid request token. Refresh the page and try again.');
    }

    $action = (string) ($_POST['action'] ?? 'save');
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $statement = $pdo->prepare('DELETE FROM menu_products WHERE id = ?');
        $statement->execute([$id]);
        $success = 'Product deleted.';
    } else {
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = (string) ($_POST['category'] ?? '');
        $description = trim((string) ($_POST['description'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $stockQuantity = filter_var($_POST['stock_quantity'] ?? 0, FILTER_VALIDATE_INT);
        $stockEnabled = isset($_POST['stock_enabled']) ? 1 : 0;
        $imagePath = '';
        $available = isset($_POST['is_available']) ? 1 : 0;

        if ($name === '' || !in_array($category, $categoryRows, true) || $price === false || $price < 0 || $stockQuantity === false || $stockQuantity < 0) {
            $error = 'Enter a product name, valid category, non-negative price, and stock quantity.';
        } else {
            $upload = $_FILES['product_image'] ?? null;
            if ($upload && $upload['error'] !== UPLOAD_ERR_NO_FILE) {
                $mimeTypes = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                ];
                $fileInfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $fileInfo->file($upload['tmp_name']);

                if ($upload['error'] !== UPLOAD_ERR_OK || $upload['size'] > 5 * 1024 * 1024 || !isset($mimeTypes[$mimeType])) {
                    $error = 'Choose a JPG, PNG, or WebP image up to 5 MB.';
                } else {
                    $uploadDirectory = __DIR__ . '/uploads/products';
                    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
                        $error = 'The product image folder could not be created.';
                    } else {
                        $fileName = bin2hex(random_bytes(16)) . '.' . $mimeTypes[$mimeType];
                        if (!move_uploaded_file($upload['tmp_name'], $uploadDirectory . '/' . $fileName)) {
                            $error = 'The product image could not be saved.';
                        } else {
                            $imagePath = 'uploads/products/' . $fileName;
                        }
                    }
                }
            }

            if ($error === '') {
                if ($id > 0) {
                    if ($imagePath === '') {
                        $currentImage = $pdo->prepare('SELECT image_path FROM menu_products WHERE id = ?');
                        $currentImage->execute([$id]);
                        $imagePath = (string) $currentImage->fetchColumn();
                    }
                    $statement = $pdo->prepare('UPDATE menu_products SET name = ?, category = ?, description = ?, price = ?, image_path = ?, is_available = ?, stock_quantity = ?, stock_enabled = ? WHERE id = ?');
                    $statement->execute([$name, $category, $description, $price, $imagePath, $available, $stockQuantity, $stockEnabled, $id]);
                    $success = 'Product updated.';
                } else {
                    $statement = $pdo->prepare('INSERT INTO menu_products (name, category, description, price, image_path, is_available, stock_quantity, stock_enabled) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                    $statement->execute([$name, $category, $description, $price, $imagePath, $available, $stockQuantity, $stockEnabled]);
                    $success = 'Product added.';
                }
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $statement = $pdo->prepare('SELECT * FROM menu_products WHERE id = ?');
    $statement->execute([(int) $_GET['edit']]);
    $editProduct = $statement->fetch() ?: null;
}

$products = $pdo->query('SELECT * FROM menu_products ORDER BY category, name')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage products | Romy and Allit Food Haven</title>
<link rel="stylesheet" href="styles.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <a class="brand" href="dashboard.php">Admin dashboard</a>
        <div class="dashboard-actions">
            <a class="text-link" href="index.php">View customer site</a>
            <a class="button dark" href="logout.php">Log out</a>
        </div>
    </header>
    <main class="product-manager">
        <a class="back-button" href="dashboard.php" aria-label="Back to admin dashboard">&larr; Back</a>
        <p class="eyebrow"><span></span> Menu catalog</p>
        <h1>Manage products</h1>
        <?php if ($error !== ''): ?><p class="product-notice"><?= e($error) ?></p><?php endif; ?>
        <?php if ($success !== ''): ?><p class="product-notice product-success"><?= e($success) ?></p><?php endif; ?>

        <form class="product-form" method="post" enctype="multipart/form-data">
            <h2><?= $editProduct ? 'Edit product' : 'Add a product' ?></h2>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['product_csrf']) ?>">
            <input type="hidden" name="id" value="<?= (int) ($editProduct['id'] ?? 0) ?>">
            <label>Product name
                <input name="name" required maxlength="120" value="<?= e((string) ($editProduct['name'] ?? '')) ?>">
            </label>
            <label>Category
                <select name="category" required>
                    <?php foreach ($categoryLabels as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($editProduct['category'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Description
                <textarea name="description" maxlength="255"><?= e((string) ($editProduct['description'] ?? '')) ?></textarea>
            </label>
            <label>Price (₱)
                <input name="price" type="number" min="0" step="0.01" required value="<?= e((string) ($editProduct['price'] ?? '40.00')) ?>">
            </label>
            <label>Available servings
                <input name="stock_quantity" type="number" min="0" step="1" value="<?= e((string) ($editProduct['stock_quantity'] ?? '0')) ?>">
            </label>
            <label class="checkbox-label"><input type="checkbox" name="stock_enabled" value="1" <?= isset($editProduct['stock_enabled']) && (int) $editProduct['stock_enabled'] === 1 ? 'checked' : '' ?>> Enforce stock limit</label>
            <label class="wide">Product image (JPG, PNG, or WebP, up to 5 MB)
                <input name="product_image" type="file" accept="image/jpeg,image/png,image/webp">
            </label>
            <label class="checkbox-label wide"><input type="checkbox" name="is_available" value="1" <?= !isset($editProduct['is_available']) || (int) $editProduct['is_available'] === 1 ? 'checked' : '' ?>> Available on menu</label>
            <div class="wide">
                <button class="button dark" type="submit" name="action" value="save"><?= $editProduct ? 'Save changes' : 'Add product' ?></button>
                <?php if ($editProduct): ?> <a class="text-link" href="manage_products.php">Cancel edit</a><?php endif; ?>
            </div>
        </form>

        <section class="product-list">
            <h2>Products (<?= count($products) ?>)</h2>
            <div class="orders-table-wrap">
                <table class="product-table">
                    <thead><tr><th>Image</th><th>Product</th><th>Category</th><th>Price</th><th>Stock</th><th>Availability</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php if (!$products): ?>
                            <tr><td colspan="6">No products added yet.</td></tr>
                        <?php else: foreach ($products as $product): ?>
                            <tr>
                                <td><?php if ($product['image_path'] !== ''): ?><img src="<?= e($product['image_path']) ?>" alt="" width="56" height="56" style="object-fit:cover;border-radius:5px"><?php else: ?>—<?php endif; ?></td>
                                <td><?= e($product['name']) ?></td>
                                <td><?= e($categoryLabels[$product['category']]) ?></td>
                                <td>₱<?= number_format((float) $product['price'], 2) ?></td>
                                <td><?= (int) $product['stock_enabled'] === 1 ? (int) $product['stock_quantity'] . ' left' : 'Unlimited' ?></td>
                                <td><?= (int) $product['is_available'] === 1 ? 'Available' : 'Hidden' ?></td>
                                <td class="actions">
                                    <a class="action-button edit-button" href="manage_products.php?edit=<?= (int) $product['id'] ?>">Edit</a>
                                    <form method="post" onsubmit="return confirm('Delete this product?')">
                                        <input type="hidden" name="csrf_token" value="<?= e($_SESSION['product_csrf']) ?>">
                                        <input type="hidden" name="id" value="<?= (int) $product['id'] ?>">
                                        <button class="action-button delete-button" type="submit" name="action" value="delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
