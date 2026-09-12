<?php
require 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$message = '';
$error = '';

// Handle Add Item (WITH PHOTO UPLOAD)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $category = $_POST['category'];
    $stock = $_POST['stock'];
    $image_name = null;
    
    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['image']['type'], $allowed)) {
            $error = "Only JPG, PNG, WEBP, or GIF images are allowed.";
        } elseif ($_FILES['image']['size'] > $max_size) {
            $error = "Image must be under 2MB.";
        } else {
            // Ensure uploads folder exists
            if (!is_dir('uploads')) mkdir('uploads', 0755, true);
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = 'item_' . time() . '_' . uniqid() . '.' . strtolower($ext);
            $dest = 'uploads/' . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                $error = "Failed to upload image.";
                $image_name = null;
            }
        }
    }
    
    if (empty($error)) {
        $stmt = $pdo->prepare("INSERT INTO menu (name, price, category, stock, available, image) VALUES (?, ?, ?, ?, 1, ?)");
        if ($stmt->execute([$name, $price, $category, $stock, $image_name])) {
            $message = "New item '<strong>" . htmlspecialchars($name) . "</strong>' added!";
        }
    }
}

// Update Item
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_item'])) {
    $stmt = $pdo->prepare("UPDATE menu SET stock = ?, available = ? WHERE id = ?");
    $stmt->execute([$_POST['stock'], isset($_POST['available']) ? 1 : 0, $_POST['id']]);
    $message = "Item updated.";
}

// Delete Item (and its image)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_item'])) {
    $stmt = $pdo->prepare("SELECT image FROM menu WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $img = $stmt->fetchColumn();
    if ($img && file_exists('uploads/' . $img)) unlink('uploads/' . $img);
    
    $pdo->prepare("DELETE FROM menu WHERE id = ?")->execute([$_POST['id']]);
    $message = "Item deleted.";
}

// Mark Order Done
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_done'])) {
    $pdo->prepare("UPDATE orders SET status = 'Done' WHERE group_order_id = ?")->execute([$_POST['group_order_id']]);
    $message = "Order marked as Done.";
}

// Delete User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    if ($_POST['user_id'] == $_SESSION['user_id']) {
        $error = "You cannot delete your own account!";
    } else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM orders WHERE user_id = ?")->execute([$_POST['user_id']]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$_POST['user_id']]);
            $pdo->commit();
            $message = "User deleted.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Could not delete user.";
        }
    }
}

$menu_items = $pdo->query("SELECT * FROM menu ORDER BY category, name")->fetchAll();
$orders = $pdo->query("SELECT orders.*, users.username FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.order_date DESC")->fetchAll();
$users_list = $pdo->query("
    SELECT users.id, users.username, users.role,
           COUNT(orders.id) AS total_orders,
           COALESCE(SUM(orders.price * orders.quantity), 0) AS total_spent
    FROM users LEFT JOIN orders ON users.id = orders.user_id
    GROUP BY users.id ORDER BY users.role DESC, users.username ASC
")->fetchAll();

$total_revenue = $pdo->query("SELECT COALESCE(SUM(price * quantity), 0) FROM orders")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(DISTINCT group_order_id) FROM orders")->fetchColumn();
$pending_orders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'Processing'")->fetchColumn();
$total_customers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard — FoodHub</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .image-upload-area {
            border: 2px dashed var(--gray-300);
            border-radius: var(--radius-sm);
            padding: 16px;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
            background: var(--gray-50);
        }
        .image-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .image-upload-area input[type="file"] { display: none; }
        .image-preview {
            max-width: 100%;
            max-height: 120px;
            margin-top: 10px;
            border-radius: var(--radius-sm);
            display: none;
        }
        .item-thumb {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--gray-100);
            display: block;
        }
        .item-thumb-placeholder {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="admin.php" class="navbar-brand">🍽️ FoodHub <span style="font-size:12px; background: var(--primary-light); color: var(--primary-dark); padding: 3px 8px; border-radius: 10px;">OWNER</span></a>
        <ul class="navbar-nav">
            <li><span class="user-badge">👑 <?php echo htmlspecialchars($_SESSION['username']); ?></span></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Manage your menu, orders, and customers.</p>

        <?php if($message): ?><div class="alert alert-success">✅ <?php echo $message; ?></div><?php endif; ?>
        <?php if($error): ?><div class="alert alert-error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-icon">💰</div><div class="stat-label">Revenue</div><div class="stat-value">₱<?php echo number_format($total_revenue, 2); ?></div></div>
            <div class="stat-card"><div class="stat-icon">🧾</div><div class="stat-label">Orders</div><div class="stat-value"><?php echo $total_orders; ?></div></div>
            <div class="stat-card"><div class="stat-icon">⏳</div><div class="stat-label">Pending</div><div class="stat-value" style="color: var(--warning);"><?php echo $pending_orders; ?></div></div>
            <div class="stat-card"><div class="stat-icon">👥</div><div class="stat-label">Customers</div><div class="stat-value"><?php echo $total_customers; ?></div></div>
        </div>

        <!-- ADD ITEM WITH PHOTO -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">➕ Add New Item</div>
                    <div class="card-description">Add a new food or beverage with a photo</div>
                </div>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="name" class="form-input" placeholder="e.g., Iced Latte" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" step="0.01" name="price" class="form-input" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            <option value="Food">Food</option>
                            <option value="Beverage">Beverage</option>
                            <option value="Dessert">Dessert</option>
                            <option value="Snack">Snack</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-input" placeholder="10" min="0" required>
                    </div>
                </div>
                <div class="form-group mt-4">
                    <label class="form-label">Item Photo (Optional)</label>
                    <label class="image-upload-area" for="image-input">
                        <div style="font-size: 28px;">📷</div>
                        <div style="font-weight: 600; color: var(--gray-700); margin-top: 6px;">Click to upload a photo</div>
                        <div style="font-size: 12px; color: var(--gray-500);">JPG, PNG, WEBP · Max 2MB</div>
                        <img id="preview" class="image-preview" alt="Preview">
                        <input type="file" name="image" id="image-input" accept="image/*" onchange="previewImage(event)">
                    </label>
                </div>
                <button type="submit" name="add_item" class="btn btn-success mt-4">Add to Menu</button>
            </form>
        </div>

        <!-- INVENTORY -->
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">📦 Inventory</div>
                    <div class="card-description">Manage your stock and availability</div>
                </div>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Photo</th><th>Name</th><th>Category</th><th>Price</th><th>Stock & Available</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($menu_items as $item): ?>
                        <tr>
                            <td>
                                <?php if(!empty($item['image']) && file_exists('uploads/' . $item['image'])): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="" class="item-thumb">
                                <?php else: ?>
                                    <div class="item-thumb-placeholder">🍽️</div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><span class="badge badge-category"><?php echo htmlspecialchars($item['category']); ?></span></td>
                            <td class="text-success" style="font-weight: 600;">₱<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 6px; align-items: center;">
                                    <input type="number" name="stock" value="<?php echo $item['stock']; ?>" min="0" class="form-input" style="width: 80px; padding: 6px 10px;">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <input type="checkbox" name="available" value="1" <?php echo $item['available'] ? 'checked' : ''; ?> style="width:18px; height:18px;">
                                    <button type="submit" name="update_item" class="btn btn-primary btn-sm">Save</button>
                                </form>
                            </td>
                            <td>
                                <?php if($item['available'] && $item['stock'] > 0): ?>
                                    <span class="badge badge-done">In Stock</span>
                                <?php else: ?>
                                    <span class="badge badge-processing">Out</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="delete_item" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ORDERS -->
        <div class="card">
            <div class="card-header"><div><div class="card-title">🧾 Order History</div><div class="card-description">Track and manage orders</div></div></div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Order ID</th><th>Customer</th><th>Item</th><th>Price</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php if(empty($orders)): ?><tr><td colspan="7" class="text-center text-muted">No orders yet.</td></tr><?php endif; ?>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td style="font-size: 12px; color: var(--gray-500);"><?php echo htmlspecialchars($order['group_order_id']); ?></td>
                            <td><strong><?php echo htmlspecialchars($order['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['item_name']); ?></td>
                            <td class="text-success" style="font-weight: 600;">₱<?php echo number_format($order['price'], 2); ?></td>
                            <td style="font-size: 13px;"><?php echo date('M d, h:i A', strtotime($order['order_date'])); ?></td>
                            <td><span class="badge <?php echo $order['status'] == 'Done' ? 'badge-done' : 'badge-processing'; ?>"><?php echo $order['status']; ?></span></td>
                            <td>
                                <?php if($order['status'] == 'Processing'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="group_order_id" value="<?php echo $order['group_order_id']; ?>">
                                        <button type="submit" name="mark_done" class="btn btn-success btn-sm">✔ Done</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 13px;">Completed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- USERS -->
        <div class="card">
            <div class="card-header"><div><div class="card-title">👥 Registered Users</div><div class="card-description">Everyone using your platform</div></div></div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>ID</th><th>Username</th><th>Role</th><th>Orders</th><th>Total Spent</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach($users_list as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong></td>
                            <td><span class="badge <?php echo $u['role'] == 'admin' ? 'badge-owner' : 'badge-customer'; ?>"><?php echo $u['role'] == 'admin' ? '👑 Owner' : '🛒 Customer'; ?></span></td>
                            <td><?php echo $u['total_orders']; ?></td>
                            <td class="text-success" style="font-weight: 600;">₱<?php echo number_format($u['total_spent'], 2); ?></td>
                            <td>
                                <?php if($u['id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this user and all their orders?');">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 13px;">(You)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('preview');
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>