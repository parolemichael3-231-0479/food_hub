<?php
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit;
}

$message = '';
if (isset($_POST['add_to_cart'])) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ? AND available = 1 AND stock > 0");
    $stmt->execute([$_POST['item_id']]);
    $item = $stmt->fetch();
    if ($item) {
        if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
        $_SESSION['cart'][$_POST['item_id']] = ['name' => $item['name'], 'price' => $item['price']];
        $message = "✅ " . $item['name'] . " added to cart!";
    } else {
        $message = "❌ Item unavailable.";
    }
}
$stmt = $pdo->query("SELECT * FROM menu WHERE available = 1 AND stock > 0 ORDER BY category, name");
$menu_items = $stmt->fetchAll();
$cart_count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu — FoodHub</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .menu-item { padding: 0; overflow: hidden; }
        .menu-item-image {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: var(--gray-100);
            display: block;
        }
        .menu-item-image-placeholder {
            width: 100%;
            height: 160px;
            background: linear-gradient(135deg, var(--primary-light), #c7d2fe);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }
        .menu-item-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="menu.php" class="navbar-brand">🍽️ FoodHub</a>
        <ul class="navbar-nav">
            <li><a href="menu.php" class="active">Menu</a></li>
            <li><a href="my_orders.php">📋 My Orders</a></li>
            <li><a href="cart.php">🛒 Cart (<?php echo $cart_count; ?>)</a></li>
            <li><span class="user-badge">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1 class="page-title">Today's Menu</h1>
        <p class="page-subtitle">Freshly prepared, delivered to your table.</p>

        <?php if($message): ?>
            <div class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-error'; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if(empty($menu_items)): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-icon">🍽️</div>
                    <h3>No items available</h3>
                    <p>Please check back later.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="menu-grid">
                <?php foreach($menu_items as $item): ?>
                    <div class="menu-item">
                        <?php if(!empty($item['image']) && file_exists('uploads/' . $item['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="menu-item-image">
                        <?php else: ?>
                            <div class="menu-item-image-placeholder">🍽️</div>
                        <?php endif; ?>
                        <div class="menu-item-body">
                            <span class="badge badge-category"><?php echo htmlspecialchars($item['category']); ?></span>
                            <div class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="menu-item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="menu-item-stock">📦 <?php echo $item['stock']; ?> in stock</div>
                            <form method="POST">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-block">Add to Cart</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>