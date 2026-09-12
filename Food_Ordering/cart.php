<?php
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit;
}

$total = 0;
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$success = false;
$error = '';

if (isset($_POST['process_order']) && !empty($cart_items)) {
    try {
        $pdo->beginTransaction();
        $group_order_id = 'ORD-' . date('YmdHis') . '-' . $_SESSION['user_id'];
        
        foreach ($cart_items as $id => $item) {
            $pdo->prepare("UPDATE menu SET stock = stock - 1 WHERE id = ? AND stock > 0")->execute([$id]);
            $pdo->prepare("INSERT INTO orders (user_id, item_id, item_name, price, quantity, group_order_id, status) VALUES (?, ?, ?, ?, 1, ?, 'Processing')")
                ->execute([$_SESSION['user_id'], $id, $item['name'], $item['price'], $group_order_id]);
        }
        
        $pdo->commit();
        unset($_SESSION['cart']);
        $success = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}

$cart_count = count($cart_items);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart — FoodHub</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="menu.php" class="navbar-brand">🍽️ FoodHub</a>
        <ul class="navbar-nav">
            <li><a href="menu.php">Menu</a></li>
            <li><a href="my_orders.php">📋 My Orders</a></li>
            <li><a href="cart.php" class="active">🛒 Cart (<?php echo $cart_count; ?>)</a></li>
            <li><span class="user-badge">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container" style="max-width: 700px;">
        <?php if($success): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-icon">✅</div>
                    <h2 style="color: var(--success); margin-bottom: 8px;">Order Placed!</h2>
                    <p>Your order is now <strong style="color: var(--warning);">Processing</strong>.</p>
                    <div class="mt-4">
                        <a href="menu.php" class="btn btn-primary">Order Again</a>
                        <a href="my_orders.php" class="btn btn-outline">View My Orders</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <h1 class="page-title">Your Cart</h1>
            <p class="page-subtitle">Review your order before checkout.</p>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if(empty($cart_items)): ?>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p>Add some items from the menu to get started.</p>
                        <div class="mt-4">
                            <a href="menu.php" class="btn btn-primary">Browse Menu</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr><th>Item</th><th>Price</th></tr>
                            </thead>
                            <tbody>
    <?php foreach($cart_items as $id => $item): 
        // Fetch image
        $stmt = $pdo->prepare("SELECT image FROM menu WHERE id = ?");
        $stmt->execute([$id]);
        $img = $stmt->fetchColumn();
    ?>
        <tr>
            <td>
                <?php if($img && file_exists('uploads/' . $img)): ?>
                    <img src="uploads/<?php echo htmlspecialchars($img); ?>" style="width:40px; height:40px; border-radius:6px; object-fit:cover; vertical-align:middle; margin-right:10px;">
                <?php endif; ?>
                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
            </td>
            <td class="text-success" style="font-weight: 600;">₱<?php echo number_format($item['price'], 2); ?></td>
            <?php $total += $item['price']; ?>
        </tr>
    <?php endforeach; ?>
</tbody>
                        </table>
                    </div>

                    <div class="cart-summary">
                        <div class="flex-between">
                            <span>Subtotal (<?php echo $cart_count; ?> item<?php echo $cart_count > 1 ? 's' : ''; ?>)</span>
                            <strong>₱<?php echo number_format($total, 2); ?></strong>
                        </div>
                        <div class="cart-total">
                            <span>Total</span>
                            <span class="amount">₱<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <form method="POST" class="mt-4">
                        <button type="submit" name="process_order" class="btn btn-success btn-block">Place Order</button>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>