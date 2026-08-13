<?php
require_once 'config.php';

$success_msg = '';
$error_msg = '';
$token_valid = false;
$user_id = 0;

// Verify token
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $now = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > ?");
    $stmt->execute([$token, $now]);
    $user = $stmt->fetch();
    
    if ($user) {
        $token_valid = true;
        $user_id = $user['id'];
    } else {
        $error_msg = 'This password reset link is invalid or has expired. Please request a new one.';
    }
} elseif ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $error_msg = 'No reset token provided. Please use the link sent to your email.';
}

// Handle password reset submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password']) && isset($_POST['confirm_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    $user_id = $_POST['user_id'];
    
    if (strlen($password) < 6) {
        $error_msg = 'Password must be at least 6 characters long.';
        $token_valid = true; // Keep form open
    } elseif ($password !== $confirm_password) {
        $error_msg = 'Passwords do not match.';
        $token_valid = true; // Keep form open
    } else {
        // Double check token is still valid
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ? AND reset_expires > ?");
        $stmt->execute([$user_id, $token, $now]);
        
        if ($stmt->fetch()) {
            // Update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Clear token and update password
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            if ($updateStmt->execute([$hashed_password, $user_id])) {
                $success_msg = 'Your password has been successfully reset! You can now log in with your new password.';
                $token_valid = false; // Hide form
            } else {
                $error_msg = 'Failed to update password in database. Please try again.';
                $token_valid = true;
            }
        } else {
            $error_msg = 'Your reset session expired while you were typing. Please request a new link.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - B&H Employment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .auth-section { padding: 60px 0; background: #f8fafc; min-height: 60vh; }
        .auth-container { max-width: 450px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .auth-container h2 { text-align: center; color: #0A192F; margin-bottom: 10px; font-size: 24px; }
        .auth-container p.desc { text-align: center; color: #64748b; margin-bottom: 30px; font-size: 15px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; color: #334155; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit; font-size: 15px; }
        .form-control:focus { outline: none; border-color: #0066cc; box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1); }
        .submit-btn { width: 100%; padding: 12px; background: #0066cc; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 16px; cursor: pointer; transition: background 0.2s; }
        .submit-btn:hover { background: #0052a3; }
        .auth-links { text-align: center; margin-top: 20px; font-size: 14px; color: #64748b; }
        .auth-links a { color: #0066cc; text-decoration: none; font-weight: 600; }
        .auth-links a:hover { text-decoration: underline; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <h2>Reset Password</h2>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                    <div style="text-align:center; margin-top: 20px;">
                        <a href="login.php" class="submit-btn" style="display:inline-block; text-decoration:none;">Go to Login</a>
                    </div>
                <?php elseif ($token_valid): ?>
                    <p class="desc">Please enter your new password below.</p>
                    <form action="reset-password.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_id); ?>">
                        
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Confirm your new password">
                        </div>
                        
                        <button type="submit" class="submit-btn">Reset Password</button>
                    </form>
                <?php else: ?>
                    <div class="auth-links">
                        <a href="forgot-password.php">Request a new password reset link</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
