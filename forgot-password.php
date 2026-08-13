<?php
require_once 'config.php';

$success_msg = '';
$error_msg = '';

// Check if we need to add columns to the table (Auto-migration)
try {
    $pdo->query("SELECT reset_token FROM users LIMIT 1");
} catch (PDOException $e) {
    // Column doesn't exist, let's create them
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_expires DATETIME NULL");
    } catch (PDOException $e2) {
        error_log("Failed to alter users table for reset tokens: " . $e2->getMessage());
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please enter a valid email address.';
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generate a secure token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now using PHP time
            
            // Save token to DB and set expiration
            $updateStmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
            if ($updateStmt->execute([$token, $expires, $user['id']])) {
                
                // Construct the reset link
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'];
                $reset_link = $protocol . $host . '/reset-password?token=' . $token;
                
                // Send email
                $to = $email;
                $subject = "Password Reset Request - B&H Employment";
                
                $message = "Hello " . $user['first_name'] . ",\n\n";
                $message .= "We received a request to reset your password. Please click the link below to create a new password:\n\n";
                $message .= $reset_link . "\n\n";
                $message .= "This link will expire in 1 hour. If you did not request this, please ignore this email.\n\n";
                $message .= "Best regards,\nB&H Employment Team";
                
                $headers = "From: noreply@bhemployment.com\r\n";
                $headers .= "Reply-To: noreply@bhemployment.com\r\n";
                $headers .= "X-Mailer: PHP/" . phpversion();
                
                if (mail($to, $subject, $message, $headers)) {
                    $success_msg = 'If your email address is found in our system, you will receive a password reset link shortly.';
                } else {
                    $error_msg = 'There was an error sending the email. Please try again later.';
                    error_log("Mail failed to send to $email");
                }
            } else {
                $error_msg = 'Database error. Please try again later.';
            }
        } else {
            // For security, do not reveal if the email exists or not
            $success_msg = 'If your email address is found in our system, you will receive a password reset link shortly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - B&H Employment</title>
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
                <h2>Forgot Password</h2>
                <p class="desc">Enter your email address and we'll send you a link to reset your password.</p>
                
                <?php if (!empty($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_msg); ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success_msg); ?></div>
                <?php else: ?>
                    <form action="forgot-password.php" method="POST">
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="Enter your email">
                        </div>
                        
                        <button type="submit" class="submit-btn">Send Reset Link</button>
                    </form>
                <?php endif; ?>
                
                <div class="auth-links">
                    Remember your password? <a href="login.php">Log In</a>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
