<?php
/**
 * Python4Physics - Dedicated Admin Login
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../site_config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/auth.php';

if (is_admin_logged_in()) {
    header("Location: " . get_base_url() . "/admin/index.php");
    exit;
}

$error = "";
$success = "";

if (isset($_GET['logged_out'])) {
    $success = "You have been logged out successfully.";
}

if (!isset($conn) || $conn === null) {
    $error = "Database connection error: " . ($db_error ?? "Unable to connect to MySQL. Please verify db_config.php.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!isset($conn) || $conn === null) {
        $error = "Cannot sign in: Database is not connected (" . ($db_error ?? "Check db_config.php") . ").";
    } elseif (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        try {
            // Auto-provision default admin if not yet present and logging in with default credentials
            if (strtolower($username) === 'admin' && $password === 'admin123') {
                $check_admin = $conn->query("SELECT * FROM `admin_users` WHERE `username` = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if (!$check_admin) {
                    $hash = password_hash('admin123', PASSWORD_DEFAULT);
                    $ins = $conn->prepare("INSERT INTO `admin_users` (`username`, `password_text`, `password`, `name`, `email`, `is_superuser`, `status`) VALUES ('admin', 'admin123', :hash, 'Administrator', 'admin@python4physics.in', 1, 1)");
                    $ins->execute([':hash' => $hash]);
                }
            }

            $stmt = $conn->prepare("SELECT * FROM `admin_users` WHERE `username` = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && ($user['status'] == 1 || $user['status'] === '1' || !isset($user['status']))) {
                $password_matches = false;

                if (!empty($user['password']) && password_verify($password, $user['password'])) {
                    $password_matches = true;
                } elseif (!empty($user['password_text']) && $password === $user['password_text']) {
                    $password_matches = true;
                }

                if ($password_matches) {
                    $_SESSION['p4p_admin_logged_in'] = true;
                    $_SESSION['p4p_admin_id'] = $user['admin_id'];
                    $_SESSION['p4p_admin_username'] = $user['username'];
                    $_SESSION['p4p_admin_name'] = !empty($user['name']) ? $user['name'] : $user['username'];
                    $_SESSION['p4p_admin_email'] = $user['email'] ?? '';
                    $_SESSION['p4p_admin_superuser'] = $user['is_superuser'] ?? 0;

                    header("Location: " . get_base_url() . "/admin/index.php");
                    exit;
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid credentials or account is inactive.";
            }
        } catch (Throwable $e) {
            $error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Portal Login &bull; Python4Physics</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo get_base_url(); ?>/assets/css/main.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 10%, rgba(14, 165, 233, 0.15) 0%, rgba(15, 23, 42, 0.95) 80%), #0b0f19;
            padding: 1.5rem;
            box-sizing: border-box;
            overflow-x: hidden;
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: rgba(17, 24, 39, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.25rem;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 30px rgba(14, 165, 233, 0.15);
        }
        .login-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: 800;
            color: #f8fafc;
        }
        .login-brand i {
            color: var(--accent);
            font-size: 1.75rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
            text-align: left;
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #94a3b8;
            margin-bottom: 0.5rem;
        }
        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .input-wrapper i.prefix-icon {
            position: absolute;
            left: 1rem;
            color: #64748b;
            font-size: 1rem;
            pointer-events: none;
        }
        .form-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            color: #f8fafc;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.25);
            background: rgba(15, 23, 42, 0.9);
        }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            font-size: 1rem;
            font-weight: 700;
            border-radius: 0.75rem;
            border: none;
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
            box-shadow: 0 4px 14px rgba(14, 165, 233, 0.4);
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(14, 165, 233, 0.55);
        }
        .alert-box {
            padding: 0.75rem 1rem;
            border-radius: 0.65rem;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        .alert-success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #34d399;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-brand">
        <i class="fa-solid fa-atom"></i>
        <span>Python4Physics <span style="font-size: 0.8rem; background: var(--accent); color: #000; padding: 2px 8px; border-radius: 6px; margin-left: 4px; vertical-align: middle;">ADMIN</span></span>
    </div>

    <div style="text-align: center; margin-bottom: 1.75rem;">
        <h2 style="font-size: 1.35rem; font-weight: 700; margin-bottom: 0.25rem;">Control Center Login</h2>
        <p style="color: #94a3b8; font-size: 0.875rem; margin: 0;">Sign in to manage menus, codes & assignments</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert-box alert-error">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert-box alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <span><?php echo htmlspecialchars($success); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label" for="username">Admin Username</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-user prefix-icon"></i>
                <input type="text" id="username" name="username" class="form-input" placeholder="e.g. anirban" required autocomplete="username" autofocus>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <div class="input-wrapper">
                <i class="fa-solid fa-lock prefix-icon"></i>
                <input type="password" id="password" name="password" class="form-input" placeholder="Enter password" required autocomplete="current-password">
            </div>
        </div>

        <button type="submit" class="btn-submit">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Dashboard
        </button>
    </form>

    <div style="margin-top: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 1.25rem;">
        <a href="<?php echo get_base_url(); ?>/index.php" style="color: #94a3b8; font-size: 0.875rem; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='#94a3b8'">
            <i class="fa-solid fa-arrow-left"></i> Return to Main Website
        </a>
    </div>
</div>

</body>
</html>
