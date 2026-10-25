<?php
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['staff_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = "Please enter both username and password.";
    } else {
        // Prepared statement for staff login lookup
        $stmt = $conn->prepare("SELECT staff_id, name, username, password_hash FROM staff WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['staff_id'] = (int)$row['staff_id'];
                $_SESSION['staff_name'] = $row['name'];
                $_SESSION['username'] = $row['username'];
                header("Location: index.php");
                exit;
            } else {
                $error = "Authentication Failed: Invalid username or password.";
            }
        } else {
            $error = "Authentication Failed: Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - College Library LMS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar">
    <span class="navbar-brand">College Library Management System</span>
</nav>
<div class="login-wrapper">
    <div class="card">
        <h2>Librarian / Staff Portal Login</h2>
        <?php if ($error !== ''): ?>
            <div class="alert alert-error"><?= h($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="username">Staff Username</label>
                <input type="text" id="username" name="username" class="form-control" required maxlength="60" placeholder="e.g., admin" value="<?= h($_POST['username'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter staff password">
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In to Library Desk</button>
        </form>
        <p style="margin-top: 14px; font-size: 12px; color: var(--muted);">
            Default Demo Credentials: <code>admin</code> / <code>password</code>
        </p>
    </div>
</div>
</body>
</html>
