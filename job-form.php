<?php
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-YY4L2E1XDJ"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-YY4L2E1XDJ');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Application Form - B&H Employment & Consultancy Inc</title>
    <meta name="description" content="Complete our job application form to apply for available positions at B&H Employment & Consultancy Inc.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
<?php
// Get site settings for favicon
try {
    $site_settings_stmt = $pdo->prepare("SELECT * FROM site_settings");
    $site_settings_stmt->execute();
    $site_settings_rows = $site_settings_stmt->fetchAll();

    // Convert to associative array
    $site_settings = [];
    foreach ($site_settings_rows as $row) {
        $site_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching site settings: " . $e->getMessage());
    $site_settings = [];
}

$favicon_path = '';
if (isset($site_settings) && !empty($site_settings['favicon'])) {
    $favicon_path = '/' . ltrim($site_settings['favicon'], '/');
} else {
    $favicon_path = '/favicon.ico';
}
?>
<!-- Dynamic Favicon -->
<link rel="icon" href="<?php echo $favicon_path; ?>?v=<?php echo time(); ?>" type="image/<?php echo pathinfo($favicon_path, PATHINFO_EXTENSION) === 'ico' ? 'x-icon' : pathinfo($favicon_path, PATHINFO_EXTENSION); ?>">
<link rel="shortcut icon" href="<?php echo $favicon_path; ?>?v=<?php echo time(); ?>" type="image/<?php echo pathinfo($favicon_path, PATHINFO_EXTENSION) === 'ico' ? 'x-icon' : pathinfo($favicon_path, PATHINFO_EXTENSION); ?>">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="page-title">
        <div class="container">
            <h1><i class="fas fa-file-alt"></i> Job Application Form</h1>
            <p>Complete the form below to apply for available positions</p>
        </div>
    </section>

    <section class="job-form-section">
        <div class="container">
            <?php displayFlashMessage(); ?>
            
            <div class="form-intro">
                <div class="info-card">
                    <i class="fas fa-info-circle"></i>
                    <div class="info-content">
                        <h3>Application Instructions</h3>
                        <p>Please fill out all required fields in the form below. Make sure to provide accurate and complete information to help us process your application efficiently.</p>
                    </div>
                </div>
            </div>

            <div class="form-container">
                <div class="form-wrapper">
                    <iframe 
                        src="https://docs.google.com/forms/d/e/1FAIpQLSdHPcp7Mu5sfX64YDp_LH1c1b2MlomTQSXvL89NyZNRhzwlzg/viewform?embedded=true" 
                        width="100%" 
                        height="2000" 
                        frameborder="0" 
                        marginheight="0" 
                        marginwidth="0"
                        class="google-form-iframe"
                        sandbox="allow-scripts allow-forms allow-same-origin allow-popups"
                        loading="lazy">
                        <p>Your browser does not support iframes. Please <a href="https://docs.google.com/forms/d/e/1FAIpQLSdHPcp7Mu5sfX64YDp_LH1c1b2MlomTQSXvL89NyZNRhzwlzg/viewform" target="_blank">click here</a> to access the form.</p>
                    </iframe>
                </div>
            </div>

            <div class="form-footer">
                <div class="help-card">
                    <i class="fas fa-question-circle"></i>
                    <div class="help-content">
                        <h4>Need Help?</h4>
                        <p>If you encounter any issues with the form or have questions about your application, please <a href="index.php#contact">contact us</a> directly.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <style>
        .job-form-section {
            padding: 40px 0 60px;
            background-color: #f8f9fa;
        }

        .form-intro {
            margin-bottom: 30px;
        }

        .info-card {
            background: linear-gradient(135deg, #0066cc 0%, #004999 100%);
            border-radius: 12px;
            padding: 25px 30px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.2);
            color: white;
        }

        .info-card i {
            font-size: 32px;
            color: rgba(255, 255, 255, 0.9);
            flex-shrink: 0;
            margin-top: 5px;
        }

        .info-content h3 {
            margin: 0 0 10px 0;
            font-size: 20px;
            color: white;
        }

        .info-content p {
            margin: 0;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.95);
        }

        .form-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .form-wrapper {
            position: relative;
            width: 100%;
            overflow: hidden;
            border-radius: 8px;
            background: #fff;
        }

        .google-form-iframe {
            display: block;
            width: 100%;
            min-height: 2000px;
            border: none;
            background: transparent;
            pointer-events: auto;
        }
        
        .form-wrapper {
            pointer-events: auto;
        }

        .form-footer {
            margin-top: 30px;
        }

        .help-card {
            background: white;
            border-radius: 12px;
            padding: 20px 25px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #28a745;
        }

        .help-card i {
            font-size: 24px;
            color: #28a745;
            flex-shrink: 0;
            margin-top: 3px;
        }

        .help-content h4 {
            margin: 0 0 8px 0;
            font-size: 16px;
            color: #333;
        }

        .help-content p {
            margin: 0;
            line-height: 1.6;
            color: #666;
            font-size: 14px;
        }

        .help-content a {
            color: #0066cc;
            text-decoration: none;
            font-weight: 600;
        }

        .help-content a:hover {
            text-decoration: underline;
        }

        /* Loading state */
        .form-wrapper::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0066cc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            z-index: 1;
        }

        .google-form-iframe:not([src=""]) ~ .form-wrapper::before {
            display: none;
        }

        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .job-form-section {
                padding: 30px 0 40px;
            }

            .form-container {
                padding: 20px 15px;
                border-radius: 8px;
            }

            .info-card {
                flex-direction: column;
                padding: 20px;
                gap: 15px;
            }

            .info-card i {
                font-size: 28px;
            }

            .info-content h3 {
                font-size: 18px;
            }

            .help-card {
                flex-direction: column;
                padding: 15px 20px;
                gap: 10px;
            }

            .google-form-iframe {
                min-height: 1800px;
            }
        }

        @media (max-width: 480px) {
            .google-form-iframe {
                min-height: 2200px;
            }
        }

        /* Custom scrollbar for the page */
        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #0066cc;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #004999;
        }
    </style>
    
    <script src="js/script.js"></script>
    <script>
        // Adjust iframe height based on content if needed
        window.addEventListener('message', function(e) {
            if (e.data && e.data.height) {
                var iframe = document.querySelector('.google-form-iframe');
                if (iframe) {
                    iframe.style.height = e.data.height + 'px';
                }
            }
        });

        // Show loading indicator while iframe loads
        document.addEventListener('DOMContentLoaded', function() {
            var iframe = document.querySelector('.google-form-iframe');
            if (iframe) {
                iframe.addEventListener('load', function() {
                    console.log('Form loaded successfully');
                });
            }
        });
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
