<?php
// login.php
require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_nama'] = $user['nama'];
            $_SESSION['admin_user'] = $user['username'];
            header('Location: ' . BASE_URL . '/index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0F172A;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative background circles */
        body::before, body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: .12;
        }
        body::before {
            width: 600px; height: 600px;
            background: radial-gradient(circle, #2563EB, transparent);
            top: -200px; right: -100px;
        }
        body::after {
            width: 500px; height: 500px;
            background: radial-gradient(circle, #06B6D4, transparent);
            bottom: -150px; left: -100px;
        }

        .login-wrap {
            position: relative; z-index: 2;
            width: 100%; max-width: 420px;
        }

        .login-brand {
            text-align: center; margin-bottom: 32px;
        }
        .brand-icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #2563EB, #06B6D4);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; color: #fff;
            margin-bottom: 14px;
            box-shadow: 0 8px 32px rgba(37,99,235,.35);
        }
        .login-brand h2 {
            color: #fff; font-weight: 800;
            font-size: 26px; margin: 0 0 4px;
        }
        .login-brand p {
            color: rgba(255,255,255,.4);
            font-size: 13px; margin: 0;
        }

        .login-card {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 20px;
            padding: 32px;
            backdrop-filter: blur(10px);
        }

        .login-card label {
            color: rgba(255,255,255,.7);
            font-size: 13px; font-weight: 600;
            margin-bottom: 6px;
        }

        .login-card .form-control {
            background: rgba(255,255,255,.08);
            border: 1px solid rgba(255,255,255,.15);
            color: #fff;
            border-radius: 10px;
            padding: 11px 14px;
            font-size: 14px;
        }
        .login-card .form-control::placeholder { color: rgba(255,255,255,.3); }
        .login-card .form-control:focus {
            background: rgba(255,255,255,.12);
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,.2);
            color: #fff;
        }

        .input-icon {
            position: relative;
        }
        .input-icon .form-control {
            padding-left: 40px;
        }
        .input-icon i {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,.35); font-size: 14px;
            pointer-events: none;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #2563EB, #06B6D4);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 15px; font-weight: 700;
            color: #fff;
            cursor: pointer;
            transition: opacity .2s, transform .15s;
            margin-top: 4px;
        }
        .btn-login:hover {
            opacity: .9; transform: translateY(-1px);
        }

        .error-box {
            background: rgba(239,68,68,.15);
            border: 1px solid rgba(239,68,68,.3);
            border-radius: 10px;
            color: #FCA5A5;
            padding: 10px 14px;
            font-size: 13px;
            margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }

        .login-hint {
            text-align: center;
            margin-top: 20px;
            color: rgba(255,255,255,.25);
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-brand">
        <div class="brand-icon"><i class="fas fa-soap"></i></div>
        <h2><?= APP_NAME ?></h2>
        <p><?= APP_TAGLINE ?></p>
    </div>

    <div class="login-card">
        <?php if ($error): ?>
        <div class="error-box">
            <i class="fas fa-exclamation-circle"></i> <?= $error ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" class="form-control"
                           placeholder="Masukkan username"
                           value="<?= clean($_POST['username'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" class="form-control"
                           placeholder="Masukkan password" required>
                </div>
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt mr-2"></i>Masuk
            </button>
        </form>
    </div>

    <div class="login-hint">
        Default: <strong style="color:rgba(255,255,255,.4)">admin</strong> / <strong style="color:rgba(255,255,255,.4)">password</strong>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
