<?php
require_once 'config.php';

// if logged in, redirect to index
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - B&H Employment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .register-container { max-width: 800px; margin: 80px auto; padding: 0 20px; text-align: center; min-height: 50vh;}
        .register-container h1 { color: #0A192F; font-size: 32px; font-weight: 700; margin-bottom: 10px; }
        .register-container p { color: #64748b; font-size: 16px; margin-bottom: 40px; }
        
        .options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        @media (max-width: 600px) { .options-grid { grid-template-columns: 1fr; } }
        
        .option-card {
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 40px 30px;
            text-decoration: none;
            color: #0A192F;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .option-card:hover {
            border-color: #10b981;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .option-icon {
            font-size: 48px;
            color: #10b981;
            margin-bottom: 20px;
        }
        .option-title { font-size: 20px; font-weight: 700; margin-bottom: 10px; }
        .option-desc { color: #64748b; font-size: 14px; text-align: center; }
        .login-link { margin-top: 40px; font-size: 15px; color: #64748b; }
        .login-link a { color: #0A192F; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="register-container">
        <img src="images/logo.png" alt="B&H Employment" style="max-height: 80px; margin-bottom: 20px; display: block; margin-left: auto; margin-right: auto;">
        <h1>Join B&H Employment</h1>
        <p>Choose your account type to get started</p>

        <div class="options-grid">
            <a href="<?php echo $base_path; ?>submit-candidate" class="option-card">
                <i class="fas fa-user-tie option-icon"></i>
                <div class="option-title">I am a Candidate</div>
                <div class="option-desc">Looking for job opportunities and want to submit my profile.</div>
            </a>
            
            <a href="<?php echo $base_path; ?>submit-job" class="option-card">
                <i class="fas fa-building option-icon"></i>
                <div class="option-title">I am an Employer</div>
                <div class="option-desc">Looking to hire top talent and post job openings.</div>
            </a>
        </div>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Log In</a>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>