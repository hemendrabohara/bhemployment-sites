<?php
require_once '../config.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    flashMessage("You must be logged in as an admin to access this page", "danger");
    redirect('../login.php');
}

$errors = [];
$success = '';

// Handle favicon upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_favicon'])) {
    // Check if a file was uploaded
    if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] == 0) {
        $allowed_types = ['image/x-icon', 'image/png', 'image/jpeg', 'image/gif', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024; // 2MB

        // Validate file type and size
        if (!in_array($_FILES['favicon']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only ICO, PNG, JPG, GIF, and SVG files are allowed.";
        } elseif ($_FILES['favicon']['size'] > $max_size) {
            $errors[] = "File size is too large. Maximum size is 2MB.";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/favicon/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate a unique filename
            $file_extension = pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION);
            $new_filename = 'favicon_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $upload_path)) {
                // Update favicon path in database
                $favicon_path = 'uploads/favicon/' . $new_filename;

                try {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = 'favicon'");
                    $stmt->bindParam(':value', $favicon_path);
                    $stmt->execute();

                    // If the setting doesn't exist, create it
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('favicon', :value)");
                        $stmt->bindParam(':value', $favicon_path);
                        $stmt->execute();
                    }

                    $success = "Favicon uploaded and updated successfully!";
                } catch (PDOException $e) {
                    error_log("Error updating favicon: " . $e->getMessage());
                    $errors[] = "An error occurred while updating the favicon. Please try again.";
                }
            } else {
                $errors[] = "Failed to upload file. Please try again.";
            }
        }
    } else {
        $errors[] = "Please select a file to upload.";
    }
}

// Handle hero image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_hero_image'])) {
    // Check if a file was uploaded
    if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $max_size = 5 * 1024 * 1024; // 5MB

        // Validate file type and size
        if (!in_array($_FILES['hero_image']['type'], $allowed_types)) {
            $errors[] = "Invalid file type. Only JPG and PNG files are allowed for the hero image.";
        } elseif ($_FILES['hero_image']['size'] > $max_size) {
            $errors[] = "File size is too large. Maximum size is 5MB.";
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = '../uploads/hero/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate a unique filename
            $file_extension = pathinfo($_FILES['hero_image']['name'], PATHINFO_EXTENSION);
            $new_filename = 'hero_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            // Move uploaded file
            if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $upload_path)) {
                // Update hero image path in database
                $hero_image_path = 'uploads/hero/' . $new_filename;

                try {
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = 'hero_image'");
                    $stmt->bindParam(':value', $hero_image_path);
                    $stmt->execute();

                    // If the setting doesn't exist, create it
                    if ($stmt->rowCount() === 0) {
                        $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('hero_image', :value)");
                        $stmt->bindParam(':value', $hero_image_path);
                        $stmt->execute();
                    }

                    $success = "Hero image uploaded and updated successfully!";
                } catch (PDOException $e) {
                    error_log("Error updating hero image: " . $e->getMessage());
                    $errors[] = "An error occurred while updating the hero image. Please try again.";
                }
            } else {
                $errors[] = "Failed to upload file. Please try again.";
            }
        }
    } else {
        $errors[] = "Please select a file to upload.";
    }
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Get form data
    $site_title = sanitizeInput($_POST['site_title']);
    $site_description = sanitizeInput($_POST['site_description']);
    $contact_email = sanitizeInput($_POST['contact_email']);
    $contact_phone = sanitizeInput($_POST['contact_phone']);
    $mobile_phone = sanitizeInput($_POST['mobile_phone']); // Add mobile phone
    $contact_address = sanitizeInput($_POST['contact_address']);
    
    // Handle social media URLs with proper validation and formatting
    $social_facebook = trim($_POST['social_facebook']);
    $social_twitter = trim($_POST['social_twitter']);
    $social_linkedin = trim($_POST['social_linkedin']);
    $social_instagram = trim($_POST['social_instagram']);
    
    // Function to validate and normalize URLs
    function normalizeUrl($url) {
        if (empty($url)) {
            return '';
        }
        
        // Add https:// if no protocol is specified
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'https://' . $url;
        }
        
        // Validate the URL
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        return '';
    }
    
    // Normalize social media URLs
    $social_facebook = normalizeUrl($social_facebook);
    $social_twitter = normalizeUrl($social_twitter);
    $social_linkedin = normalizeUrl($social_linkedin);
    $social_instagram = normalizeUrl($social_instagram);

    // Validate inputs
    if (empty($site_title)) {
        $errors[] = "Site title is required";
    }

    if (empty($site_description)) {
        $errors[] = "Site description is required";
    }

    if (empty($contact_email)) {
        $errors[] = "Contact email is required";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid contact email format";
    }

    if (empty($contact_phone)) {
        $errors[] = "Contact phone is required";
    }

    if (empty($mobile_phone)) {
        $errors[] = "Mobile phone is required";
    }

    if (empty($contact_address)) {
        $errors[] = "Contact address is required";
    }    // If no errors, update settings
    if (empty($errors)) {
        try {
            // Log the update attempt
            error_log("Site settings update attempt - User ID: " . $_SESSION['user_id']);
            
            // Update settings
            $settings = [
                'site_title' => $site_title,
                'site_description' => $site_description,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'mobile_phone' => $mobile_phone, // Add mobile phone
                'contact_address' => $contact_address,
                'social_facebook' => $social_facebook,
                'social_twitter' => $social_twitter,
                'social_linkedin' => $social_linkedin,
                'social_instagram' => $social_instagram
            ];

            $updated_count = 0;
            foreach ($settings as $key => $value) {
                // Check if setting exists
                $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM site_settings WHERE setting_key = :key");
                $check_stmt->bindParam(':key', $key);
                $check_stmt->execute();
                $exists = $check_stmt->fetchColumn() > 0;
                
                if ($exists) {
                    // Update existing setting
                    $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = :key");
                    $stmt->bindParam(':value', $value);
                    $stmt->bindParam(':key', $key);
                    $stmt->execute();
                    error_log("Updated setting: $key = $value");
                } else {
                    // Insert new setting
                    $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value)");
                    $stmt->bindParam(':key', $key);
                    $stmt->bindParam(':value', $value);
                    $stmt->execute();
                    error_log("Inserted new setting: $key = $value");
                }
                $updated_count++;
            }

            error_log("Successfully updated $updated_count settings");
            $success = "Settings updated successfully! ($updated_count settings saved)";
        } catch (PDOException $e) {
            error_log("Error updating settings: " . $e->getMessage());
            $errors[] = "An error occurred while updating settings. Please try again. Error: " . $e->getMessage();
        }
    } else {
        error_log("Settings update failed with errors: " . implode(", ", $errors));
    }
}

// Handle legal documents update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_legal_documents'])) {
    $terms_of_service = $_POST['terms_of_service'];
    $privacy_policy = $_POST['privacy_policy'];

    try {
        // Update terms of service
        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = 'terms_of_service'");
        $stmt->bindParam(':value', $terms_of_service);
        $stmt->execute();

        // If the setting doesn't exist, create it
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('terms_of_service', :value)");
            $stmt->bindParam(':value', $terms_of_service);
            $stmt->execute();
        }

        // Update privacy policy
        $stmt = $pdo->prepare("UPDATE site_settings SET setting_value = :value WHERE setting_key = 'privacy_policy'");
        $stmt->bindParam(':value', $privacy_policy);
        $stmt->execute();

        // If the setting doesn't exist, create it
        if ($stmt->rowCount() === 0) {
            $stmt = $pdo->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES ('privacy_policy', :value)");
            $stmt->bindParam(':value', $privacy_policy);
            $stmt->execute();
        }

        $success = "Legal documents updated successfully!";
    } catch (PDOException $e) {
        error_log("Error updating legal documents: " . $e->getMessage());
        $errors[] = "An error occurred while updating legal documents. Please try again.";
    }
}

// Get current settings
try {
    $stmt = $pdo->prepare("SELECT * FROM site_settings");
    $stmt->execute();
    $settings_rows = $stmt->fetchAll();

    // Convert to associative array
    $settings = [];
    foreach ($settings_rows as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $settings = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - B&H Employment & Consultancy Inc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/updated-styles.css">
<?php
// Get site settings for favicon
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
    <?php include '../includes/header.php'; ?>

    <section class="page-title">
        <div class="container">
            <h1>Site Settings</h1>
            <p>Manage website information and configuration</p>
        </div>
    </section>

    <section class="dashboard-section">
        <div class="container">
            <div class="dashboard-container">
                <div class="dashboard-sidebar">
                    <?php include 'sidebar.php'; ?>
                </div>

                <div class="dashboard-content">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <?php displayFlashMessage(); ?>

                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-image"></i> Site Favicon</h2>
                        </div>

                        <div class="content-body">
                            <div class="current-favicon">
                                <h3>Current Favicon</h3>
                                <div class="favicon-preview">
                                    <?php if (!empty($settings['favicon'])): ?>
                                        <img src="../<?php echo htmlspecialchars($settings['favicon']); ?>?v=<?php echo time(); ?>" alt="Current Favicon">
                                    <?php else: ?>
                                        <div class="no-favicon">No favicon set</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="site-settings.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="favicon">Upload New Favicon</label>
                                    <input type="file" id="favicon" name="favicon" class="form-control" accept=".ico,.png,.jpg,.jpeg,.gif,.svg">
                                    <small class="form-text">Recommended formats: ICO, PNG, or SVG. Maximum size: 2MB.</small>
                                </div>

                                <button type="submit" name="upload_favicon" class="submit-btn">Upload Favicon</button>
                            </form>
                        </div>
                    </div>

                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-image"></i> Hero Image</h2>
                        </div>

                        <div class="content-body">
                            <div class="current-hero-image">
                                <h3>Current Hero Image</h3>
                                <div class="hero-image-preview">
                                    <?php if (!empty($settings['hero_image'])): ?>
                                        <img src="../<?php echo htmlspecialchars($settings['hero_image']); ?>?v=<?php echo time(); ?>" alt="Current Hero Image">
                                    <?php else: ?>
                                        <div class="no-hero-image">No custom hero image set (using default)</div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <form action="site-settings.php" method="POST" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="hero_image">Upload New Hero Image</label>
                                    <input type="file" id="hero_image" name="hero_image" class="form-control" accept=".jpg,.jpeg,.png">
                                    <small class="form-text">Recommended formats: JPG or PNG. <strong>Recommended dimensions: 1200x800 pixels with landscape orientation (width > height)</strong>. The image will be displayed at a fixed height with automatic width adjustment to maintain aspect ratio. Maximum file size: 5MB.</small>
                                </div>

                                <button type="submit" name="upload_hero_image" class="submit-btn">Upload Hero Image</button>
                            </form>
                        </div>
                    </div>                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-sliders-h"></i> General Settings</h2>
                        </div>

                        <div class="content-body">
                            <!-- Debug info (remove in production) -->
                            <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                            <div style="background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; border-left: 4px solid #007bff;">
                                <h4>Debug Information:</h4>
                                <p><strong>Current Settings in Database:</strong></p>
                                <ul>
                                    <?php foreach ($settings as $key => $value): ?>
                                        <li><strong><?php echo htmlspecialchars($key); ?>:</strong> <?php echo htmlspecialchars($value); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Last POST data:</strong> <?php echo isset($_POST) ? json_encode($_POST) : 'None'; ?></p>
                            </div>
                            <?php endif; ?>

                            <form action="site-settings.php" method="POST">
                                <div class="form-group">
                                    <label for="site_title">Site Title</label>
                                    <input type="text" id="site_title" name="site_title" class="form-control" value="<?php echo isset($settings['site_title']) ? $settings['site_title'] : ''; ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="site_description">Site Description</label>
                                    <textarea id="site_description" name="site_description" class="form-control" rows="3" required><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                </div>

                                <hr>
                                <h3>Contact Information</h3>

                                <div class="form-group">
                                    <label for="contact_email">Email Address</label>
                                    <input type="email" id="contact_email" name="contact_email" class="form-control" value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="contact_phone">Phone Number</label>
                                    <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="mobile_phone">Mobile Phone</label>
                                    <input type="text" id="mobile_phone" name="mobile_phone" class="form-control" value="<?php echo htmlspecialchars($settings['mobile_phone'] ?? ''); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label for="contact_address">Office Address</label>
                                    <textarea id="contact_address" name="contact_address" class="form-control" rows="2" required><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                                </div>

                                <hr>
                                <h3>Social Media Links</h3>                                <div class="form-group">
                                    <label for="social_facebook">Facebook</label>
                                    <div class="input-with-icon">
                                        <i class="fab fa-facebook-f"></i>
                                        <input type="text" id="social_facebook" name="social_facebook" class="form-control" placeholder="https://facebook.com/yourpage" value="<?php echo htmlspecialchars($settings['social_facebook'] ?? ''); ?>">
                                    </div>
                                    <small class="form-text">Enter full URL including https://</small>
                                </div>

                                <div class="form-group">
                                    <label for="social_twitter">Twitter</label>
                                    <div class="input-with-icon">
                                        <i class="fab fa-twitter"></i>
                                        <input type="text" id="social_twitter" name="social_twitter" class="form-control" placeholder="https://twitter.com/yourusername" value="<?php echo htmlspecialchars($settings['social_twitter'] ?? ''); ?>">
                                    </div>
                                    <small class="form-text">Enter full URL including https://</small>
                                </div>

                                <div class="form-group">
                                    <label for="social_linkedin">LinkedIn</label>
                                    <div class="input-with-icon">
                                        <i class="fab fa-linkedin-in"></i>
                                        <input type="text" id="social_linkedin" name="social_linkedin" class="form-control" placeholder="https://linkedin.com/company/yourcompany" value="<?php echo htmlspecialchars($settings['social_linkedin'] ?? ''); ?>">
                                    </div>
                                    <small class="form-text">Enter full URL including https://</small>
                                </div>

                                <div class="form-group">
                                    <label for="social_instagram">Instagram</label>
                                    <div class="input-with-icon">
                                        <i class="fab fa-instagram"></i>
                                        <input type="text" id="social_instagram" name="social_instagram" class="form-control" placeholder="https://instagram.com/yourusername" value="<?php echo htmlspecialchars($settings['social_instagram'] ?? ''); ?>">
                                    </div>
                                    <small class="form-text">Enter full URL including https://</small>
                                </div>

                                <button type="submit" name="update_settings" class="submit-btn">Save Settings</button>
                            </form>
                        </div>
                    </div>

                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-file-contract"></i> Legal Documents</h2>
                        </div>

                        <div class="content-body">
                            <form action="site-settings.php" method="POST">
                                <div class="form-group">
                                    <label for="terms_of_service">Terms of Service</label>
                                    <textarea id="terms_of_service" name="terms_of_service" class="form-control" rows="15"><?php echo htmlspecialchars($settings['terms_of_service'] ?? ''); ?></textarea>
                                    <small class="form-text">HTML formatting is supported. This content will be displayed on the Terms of Service page.</small>
                                </div>

                                <div class="form-group">
                                    <label for="privacy_policy">Privacy Policy</label>
                                    <textarea id="privacy_policy" name="privacy_policy" class="form-control" rows="15"><?php echo htmlspecialchars($settings['privacy_policy'] ?? ''); ?></textarea>
                                    <small class="form-text">HTML formatting is supported. This content will be displayed on the Privacy Policy page.</small>
                                </div>

                                <button type="submit" name="update_legal_documents" class="submit-btn">Save Legal Documents</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script src="../js/script.js"></script>
    
    <script>
    // Add form validation and debugging
    document.addEventListener('DOMContentLoaded', function() {
        const settingsForm = document.querySelector('form[action="site-settings.php"]');
        if (settingsForm && settingsForm.querySelector('input[name="update_settings"]')) {
            settingsForm.addEventListener('submit', function(e) {
                console.log('Settings form being submitted...');
                
                // Check for empty required fields
                const requiredFields = settingsForm.querySelectorAll('input[required], textarea[required]');
                let hasErrors = false;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        hasErrors = true;
                        field.style.borderColor = '#dc3545';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (hasErrors) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
                
                // Show loading state
                const submitBtn = settingsForm.querySelector('button[name="update_settings"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                }
            });
        }
        
        // Auto-format URLs on blur
        const urlFields = ['social_facebook', 'social_twitter', 'social_linkedin', 'social_instagram'];
        urlFields.forEach(fieldName => {
            const field = document.getElementById(fieldName);
            if (field) {
                field.addEventListener('blur', function() {
                    let url = this.value.trim();
                    if (url && !url.match(/^https?:\/\//)) {
                        this.value = 'https://' + url;
                    }
                });
            }
        });
    });
    </script>

    <style>
    .input-with-icon {
        position: relative;
    }

    .input-with-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #0066cc;
    }

    .input-with-icon input {
        padding-left: 40px;
    }

    hr {
        margin: 25px 0;
        border: 0;
        border-top: 1px solid #eee;
    }

    h3 {
        margin-bottom: 20px;
        color: #333;
        font-size: 20px;
    }

    .current-favicon {
        margin-bottom: 25px;
    }

    .favicon-preview {
        display: inline-block;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        background-color: #f9f9f9;
        min-width: 100px;
        text-align: center;
    }

    .favicon-preview img {
        max-width: 64px;
        max-height: 64px;
    }

    .no-favicon,
    .no-hero-image {
        color: #888;
        font-style: italic;
    }

    .hero-image-preview {
        display: inline-block;
        padding: 15px;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        background-color: #f9f9f9;
        max-width: 100%;
        text-align: center;
    }

    .hero-image-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .current-hero-image {
        margin-bottom: 25px;
    }
    </style>
</body>
</html>