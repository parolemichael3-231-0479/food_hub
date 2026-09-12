<?php
require 'db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ---------- LOGIN ----------
    if (isset($_POST['login'])) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$_POST['username']]);
        $user = $stmt->fetch();
        
        $login_ok = false;
        if ($user) {
            // Support both old plain-text AND new hashed passwords
            if (password_verify($_POST['password'], $user['password']) || $_POST['password'] === $user['password']) {
                $login_ok = true;
            }
        }
        
        if ($login_ok) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: " . ($_SESSION['role'] == 'admin' ? "admin.php" : "menu.php"));
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
    
    // ---------- REGISTER WITH STRONG PASSWORD ----------
    if (isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        // Validate password strength
        $errors = [];
        if (strlen($password) < 8) $errors[] = "at least 8 characters";
        if (!preg_match('/[A-Z]/', $password)) $errors[] = "1 uppercase letter";
        if (!preg_match('/[a-z]/', $password)) $errors[] = "1 lowercase letter";
        if (!preg_match('/[0-9]/', $password)) $errors[] = "1 number";
        if (!preg_match('/[\W_]/', $password)) $errors[] = "1 special character (!@#$%^&*)";
        
        if (!empty($errors)) {
            $error = "Password must contain: " . implode(", ", $errors) . ".";
        } else {
            // Check if username exists
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $error = "Username already taken.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'customer')");
                if ($stmt->execute([$username, $hashed])) {
                    $success = "Account created successfully! Please log in.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — FoodHub</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .password-meter {
            height: 6px;
            background: var(--gray-100);
            border-radius: 3px;
            overflow: hidden;
            margin-top: 6px;
        }
        .password-meter-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 3px;
        }
        .password-hint {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 6px;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .password-hint span.rule {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .password-hint span.valid { color: var(--success); font-weight: 600; }
        .password-hint span.valid::before { content: "✓"; }
        .password-hint span.invalid::before { content: "○"; }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">🍽️</div>
                <h1 class="auth-title">FoodHub</h1>
                <p class="auth-subtitle">Order your favorites in seconds</p>
            </div>

            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <div class="auth-tabs">
                <button class="auth-tab active" onclick="showTab('login', this)">Login</button>
                <button class="auth-tab" onclick="showTab('register', this)">Register</button>
            </div>

            <!-- LOGIN -->
            <form method="POST" class="auth-form" id="login-form">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-input" placeholder="Enter your password" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-block">Sign In</button>
            </form>

            <!-- REGISTER -->
            <form method="POST" class="auth-form" id="register-form" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-input" placeholder="Choose a username" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" id="reg-password" class="form-input" placeholder="Create a strong password" required oninput="checkStrength()">
                    <div class="password-meter"><div class="password-meter-bar" id="strength-bar"></div></div>
                    <div class="password-hint" id="password-hint">
                        <span class="rule invalid" data-rule="length">At least 8 characters</span>
                        <span class="rule invalid" data-rule="upper">1 uppercase letter</span>
                        <span class="rule invalid" data-rule="lower">1 lowercase letter</span>
                        <span class="rule invalid" data-rule="number">1 number</span>
                        <span class="rule invalid" data-rule="special">1 special character</span>
                    </div>
                </div>
                <button type="submit" name="register" class="btn btn-primary btn-block" id="register-btn">Create Account</button>
            </form>
        </div>
    </div>

    <script>
        function showTab(tab, el) {
            document.querySelectorAll('.auth-tab').forEach(t => t.classList.remove('active'));
            el.classList.add('active');
            document.getElementById('login-form').style.display = tab === 'login' ? 'flex' : 'none';
            document.getElementById('register-form').style.display = tab === 'register' ? 'flex' : 'none';
        }

        function checkStrength() {
            const pw = document.getElementById('reg-password').value;
            const rules = {
                length: pw.length >= 8,
                upper: /[A-Z]/.test(pw),
                lower: /[a-z]/.test(pw),
                number: /[0-9]/.test(pw),
                special: /[\W_]/.test(pw)
            };
            
            // Update rules display
            document.querySelectorAll('.password-hint .rule').forEach(el => {
                const key = el.dataset.rule;
                if (rules[key]) {
                    el.classList.add('valid'); el.classList.remove('invalid');
                } else {
                    el.classList.add('invalid'); el.classList.remove('valid');
                }
            });
            
            // Strength calculation
            const passed = Object.values(rules).filter(Boolean).length;
            const bar = document.getElementById('strength-bar');
            const widths = ['0%', '20%', '40%', '60%', '80%', '100%'];
            const colors = ['#e5e7eb', '#ef4444', '#f97316', '#f59e0b', '#84cc16', '#10b981'];
            bar.style.width = widths[passed];
            bar.style.background = colors[passed];
        }
    </script>
</body>
</html>