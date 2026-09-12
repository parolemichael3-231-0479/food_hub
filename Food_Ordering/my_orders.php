<?php
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT group_order_id, item_name, price, status, order_date FROM orders WHERE user_id = ? ORDER BY order_date DESC");
$stmt->execute([$_SESSION['user_id']]);
$my_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders — FoodHub</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="menu.php" class="navbar-brand">🍽️ FoodHub</a>
        <ul class="navbar-nav">
            <li><a href="menu.php">Menu</a></li>
            <li><a href="my_orders.php" class="active">📋 My Orders</a></li>
            <li><a href="cart.php">🛒 Cart</a></li>
            <li><span class="user-badge">👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1 class="page-title">My Orders</h1>
        <p class="page-subtitle">Track the status of everything you've ordered.</p>

        <div class="card">
            <?php if(empty($my_orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No orders yet</h3>
                    <p>Your order history will appear here.</p>
                    <div class="mt-4"><a href="menu.php" class="btn btn-primary">Start Ordering</a></div>
                </div>
            <?php else: ?>
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th><th>Item</th><th>Price</th><th>Status</th><th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($my_orders as $o): ?>
                            <tr>
                                <td style="font-size: 12px; color: var(--gray-500);"><?php echo htmlspecialchars($o['group_order_id']); ?></td>
                                <td><strong><?php echo htmlspecialchars($o['item_name']); ?></strong></td>
                                <td class="text-success" style="font-weight: 600;">₱<?php echo number_format($o['price'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $o['status'] == 'Done' ? 'badge-done' : 'badge-processing'; ?>">
                                        <?php echo $o['status']; ?>
                                    </span>
                                </td>
                                <td style="font-size: 13px;"><?php echo date('M d, Y h:i A', strtotime($o['order_date'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>