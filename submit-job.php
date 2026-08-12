<?php
require_once 'config.php';

$errors = [];
$success = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_job'])) {
    
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please refresh the page and try again.";
    }

    $title = sanitizeInput($_POST['title'] ?? '');
    $company_name = sanitizeInput($_POST['company_name'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $job_type = sanitizeInput($_POST['job_type'] ?? '');
    $salary_min = !empty($_POST['salary_min']) ? (int)$_POST['salary_min'] : null;
    $salary_max = !empty($_POST['salary_max']) ? (int)$_POST['salary_max'] : null;
    $description = sanitizeInput($_POST['description'] ?? '');
    
    // New UI fields
    $about_us = sanitizeInput($_POST['about_us'] ?? '');
    $submitter_phone_input = sanitizeInput($_POST['submitter_phone'] ?? '');
    $submitter_email_input = filter_var($_POST['submitter_email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (!empty($submitter_email_input) && !filter_var($submitter_email_input, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    $category = sanitizeInput($_POST['category'] ?? '');
    $tags = sanitizeInput($_POST['tags'] ?? '');
    $is_remote = isset($_POST['is_remote']) ? 1 : 0;
    $currency = sanitizeInput($_POST['currency'] ?? 'USD');
    $pay_type = sanitizeInput($_POST['pay_type'] ?? 'annually');
    $driving_license = sanitizeInput($_POST['driving_license'] ?? '');
    $languages = sanitizeInput($_POST['languages'] ?? ''); 
    $job_requirements = sanitizeInput($_POST['job_requirements'] ?? '');
    $live_in_out = sanitizeInput($_POST['live_in_out'] ?? '');

    // Handle File Upload
    $company_logo = null;
    if (empty($errors) && isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/logos/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['company_logo']['name']));
        $targetFilePath = $uploadDir . $fileName;
        
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['company_logo']['tmp_name']);
        finfo_close($finfo);
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        
        if ($_FILES['company_logo']['size'] > $maxFileSize) {
            $errors[] = "File size must be less than 5MB.";
        } elseif (!in_array($fileType, $allowedTypes) || !in_array($mimeType, $allowedMimeTypes)) {
            $errors[] = "Only valid JPG, JPEG, PNG, GIF, & WEBP images are allowed.";
        } else {
            if (move_uploaded_file($_FILES['company_logo']['tmp_name'], $targetFilePath)) {
                $company_logo = $targetFilePath;
            } else {
                $errors[] = "Failed to upload logo.";
            }
        }
    }

    if (empty($title)) $errors[] = "Job title is required";
    if (empty($company_name)) $errors[] = "Company name is required";
    if (empty($description)) $errors[] = "Job description is required";
    if (!isset($_POST['terms'])) $errors[] = "You must accept the terms of service";

    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!isLoggedIn()) {
        if (empty($username)) $errors[] = "Username is required to create an account.";
        elseif (strlen($username) < 4) $errors[] = "Username must be at least 4 characters.";
        
        if (empty($password)) $errors[] = "Password is required to create an account.";
        elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters.";
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
                $stmt->execute([':email' => $submitter_email_input, ':username' => $username]);
                if ($stmt->rowCount() > 0) {
                    $errors[] = "Email or Username already exists. Please login or use different credentials.";
                }
            } catch(PDOException $e) {
                $errors[] = "Database error verifying user details.";
            }
        }
    }

    if (empty($errors)) {
        try {
            $approval_status = 'pending';
            $is_active = 1;
            $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
            
            if (!$user_id) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $parts = explode(' ', trim($company_name), 2);
                $first_name = $parts[0] ?: 'Employer';
                $last_name = $parts[1] ?? '';
                
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password, role) VALUES (:first_name, :last_name, :email, :phone, :username, :password, 'employer')");
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':email' => $submitter_email_input,
                    ':phone' => $submitter_phone_input,
                    ':username' => $username,
                    ':password' => $hashed_password
                ]);
                $user_id = $pdo->lastInsertId();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'employer';
                $_SESSION['user_name'] = $company_name;
                $_SESSION['user_email'] = $submitter_email_input;
            }
            
            // Bundle new fields into text columns so the DB doesn't crash if columns aren't added yet
            $combined_requirements = "Category: $category\nTags: $tags\nLanguages: $languages\nDriving License: $driving_license\n\nRequirements:\n$job_requirements";
            $combined_description = "$description\n\nAbout:\n$about_us\nRemote: " . ($is_remote ? 'Yes' : 'No') . "\nCompensation: $currency $pay_type";

            $submitter_name = isLoggedIn() ? $_SESSION['user_name'] : $company_name;
            $submitter_email = isLoggedIn() ? $_SESSION['user_email'] : ($submitter_email_input ?: 'no-email@example.com');
            $submitter_phone = $submitter_phone_input;
            
            $experience_level = 'entry';
            $application_instructions = '';
            $contact_email = $submitter_email;
            $contact_phone = $submitter_phone;

            // Note: We are including company_logo here, assuming the user ran the ALTER TABLE SQL.
            $stmt = $pdo->prepare("INSERT INTO jobs 
                (title, company_name, company_logo, location, job_type, experience_level, 
                salary_min, salary_max, description, requirements, application_instructions, 
                contact_email, contact_phone, user_id, approval_status, is_active, 
                submitter_name, submitter_email, submitter_phone, live_in_out) 
                VALUES 
                (:title, :company_name, :company_logo, :location, :job_type, :experience_level, 
                :salary_min, :salary_max, :description, :requirements, :application_instructions, 
                :contact_email, :contact_phone, :user_id, :approval_status, :is_active, 
                :submitter_name, :submitter_email, :submitter_phone, :live_in_out)");
            
            $stmt->bindParam(':title', $title, PDO::PARAM_STR);
            $stmt->bindParam(':company_name', $company_name, PDO::PARAM_STR);
            $stmt->bindParam(':company_logo', $company_logo, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':job_type', $job_type, PDO::PARAM_STR);
            $stmt->bindParam(':experience_level', $experience_level, PDO::PARAM_STR);
            $stmt->bindParam(':salary_min', $salary_min, PDO::PARAM_INT);
            $stmt->bindParam(':salary_max', $salary_max, PDO::PARAM_INT);
            $stmt->bindParam(':description', $combined_description, PDO::PARAM_STR);
            $stmt->bindParam(':requirements', $combined_requirements, PDO::PARAM_STR);
            $stmt->bindParam(':application_instructions', $application_instructions, PDO::PARAM_STR);
            $stmt->bindParam(':contact_email', $contact_email, PDO::PARAM_STR);
            $stmt->bindParam(':contact_phone', $contact_phone, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':approval_status', $approval_status, PDO::PARAM_STR);
            $stmt->bindParam(':is_active', $is_active, PDO::PARAM_INT);
            $stmt->bindParam(':submitter_name', $submitter_name, PDO::PARAM_STR);
            $stmt->bindParam(':submitter_email', $submitter_email, PDO::PARAM_STR);
            $stmt->bindParam(':submitter_phone', $submitter_phone, PDO::PARAM_STR);
            $stmt->bindParam(':live_in_out', $live_in_out, PDO::PARAM_STR);
            
            $stmt->execute();
            $success = "Your job has been posted successfully and is pending review.";
            
            // --- Auto Send Email Notification ---
            // 1. Send notification to Admin (bhemploy account with unlimited storage)
            $admin_email = "bhemploy@bhemployment.com"; 
            $subject_admin = "New Job Submission: " . $title;
            $message_admin = "A new job has been submitted and is pending review.\n\n" .
                             "Job Title: $title\n" .
                             "Company: $company_name\n" .
                             "Submitter: $submitter_name ($submitter_email)\n\n" .
                             "Please log in to your admin panel to review and approve.";
            $headers_admin = "From: bhemploy@bhemployment.com\r\n" .
                             "Reply-To: $submitter_email\r\n" .
                             "X-Mailer: PHP/" . phpversion();
            
            @mail($admin_email, $subject_admin, $message_admin, $headers_admin);

            // 2. Send confirmation to the Submitter
            if (!empty($submitter_email) && $submitter_email !== 'no-email@example.com') {
                $subject_user = "Job Submission Received - B&H Employment";
                $message_user = "Hello $submitter_name,\n\n" .
                                "Thank you for submitting the job: '$title'.\n" .
                                "Your submission has been received and is currently pending review by our team. " .
                                "We will notify you once it has been approved.\n\n" .
                                "Best regards,\nB&H Employment Team";
                $headers_user = "From: bhemploy@bhemployment.com\r\n" .
                                "X-Mailer: PHP/" . phpversion();
                
                @mail($submitter_email, $subject_user, $message_user, $headers_user);
            }
            
        } catch(PDOException $e) {
            error_log($e->getMessage());
            $errors[] = "An error occurred while submitting your job posting. Please try again later.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post a Job - B&H Employment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Clean UI Redesign CSS */
        body {
            background-color: #f8fafc;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .page-header-simple {
            text-align: center;
            padding: 40px 0 20px;
        }
        
        .page-header-simple h1 {
            color: #0A192F;
            font-size: 28px;
            font-weight: 600;
        }

        .wizard-container {
            max-width: 800px;
            margin: 0 auto 60px;
            padding: 0 20px;
        }

        /* Step Indicators */
        .steps-nav {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .step-indicator {
            flex: 1;
            cursor: pointer;
        }
        .step-indicator.active .step-bar {
            background-color: #10b981; /* Green */
        }
        .step-indicator.active .step-text {
            color: #10b981;
        }
        .step-bar {
            height: 3px;
            background-color: #cbd5e1;
            margin-bottom: 8px;
            border-radius: 2px;
            transition: background-color 0.3s;
        }
        .step-text {
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 2px;
            display: block;
        }
        .step-desc {
            font-size: 13px;
            color: #64748b;
        }

        /* Card Form */
        .wizard-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 25px;
            margin-top: 30px;
        }
        .section-title:first-child { margin-top: 0; }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 20px;
        }
        .form-row-4 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .input-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        .input-group label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }
        .input-control {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            color: #334155;
            transition: border-color 0.2s;
            background: #fff;
            width: 100%;
            box-sizing: border-box;
        }
        .input-control:focus {
            outline: none;
            border-color: #94a3b8;
        }
        
        select.input-control {
            appearance: none;
            padding-right: 32px;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 12px top 50%;
            background-size: 10px auto;
        }

        .upload-box {
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            padding: 40px;
            text-align: center;
            background: #f8fafc;
            color: #64748b;
            cursor: pointer;
            transition: background 0.2s;
            margin-bottom: 20px;
            position: relative;
        }
        .upload-box:hover { background: #f1f5f9; }
        .upload-box i.fa-image { font-size: 32px; color: #cbd5e1; margin-bottom: 15px; }
        .filename-display { margin-top: 10px; font-size: 13px; font-weight: bold; color: #0A192F; }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 8px;
        }
        .checkbox-group input {
            width: 16px;
            height: 16px;
            accent-color: #0A192F;
        }
        .checkbox-group label {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 0;
        }
        
        .help-text {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 6px;
        }

        /* WYSIWYG Toolbar Simulation */
        .wysiwyg-container {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .wysiwyg-toolbar {
            background: #f1f5f9;
            padding: 10px;
            border-bottom: 1px solid #cbd5e1;
            display: flex;
            gap: 15px;
            color: #475569;
        }
        .wysiwyg-toolbar i { cursor: pointer; font-size: 14px; }
        .wysiwyg-container textarea {
            width: 100%;
            border: none;
            padding: 15px;
            resize: vertical;
            min-height: 120px;
            font-family: inherit;
        }
        .wysiwyg-container textarea:focus { outline: none; }

        /* Custom Multi-Select */
        .multi-select-container { position: relative; width: 100%; }
        .multi-select-box {
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 12px 16px;
            font-size: 14px;
            color: #334155;
            background: #fff;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .multi-select-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #cbd5e1;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 10;
            display: none;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .multi-select-dropdown.show { display: block; }
        .multi-select-option {
            padding: 10px 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .multi-select-option:hover { background: #f8fafc; }
        .multi-select-option input { pointer-events: none; }

        /* Footer Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }
        .btn-back {
            background: none;
            border: none;
            color: #0A192F;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 15px;
        }
        .btn-primary {
            background: #0A192F;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 15px;
            transition: background 0.2s;
        }
        .btn-primary:hover { background: #1e293b; }

        @media (max-width: 768px) {
            .form-row, .form-row-4 { grid-template-columns: 1fr; gap: 15px; }
            .wizard-card { padding: 25px; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-header-simple">
        <img src="images/logo.png" alt="B&H Employment" style="max-height: 80px; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto;">
        <h1>Post a Job</h1>
    </div>

    <div class="wizard-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <ul style="margin:0; padding-left:20px;">
                    <?php foreach ($errors as $error) echo "<li>" . htmlspecialchars($error) . "</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Step Nav -->
        <div class="steps-nav">
            <div class="step-indicator active" id="nav-step-1">
                <div class="step-bar"></div>
                <span class="step-text">Step 1</span>
                <span class="step-desc">Your Details</span>
            </div>
            <div class="step-indicator" id="nav-step-2">
                <div class="step-bar"></div>
                <span class="step-text">Step 2</span>
                <span class="step-desc">Job Details</span>
            </div>
        </div>

        <div class="wizard-card">
            <form action="" method="POST" id="jobForm" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <!-- STEP 1: COMPANY DETAILS -->
                <div class="form-step active" id="step-1">
                    <div class="section-title">YOUR DETAILS</div>
                    
                    <div class="input-group">
                        <label>Upload photo</label>
                        <div class="upload-box" id="uploadBox" onclick="document.getElementById('company_logo').click();">
                            <i class="far fa-image fa-image"></i>
                            <div>Upload photo <i class="fas fa-upload"></i></div>
                            <div class="filename-display" id="filenameDisplay"></div>
                            <input type="file" name="company_logo" id="company_logo" accept="image/png, image/jpeg, image/jpg, image/gif, image/webp" style="display: none;">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Your Name*</label>
                            <input type="text" name="company_name" class="input-control" required placeholder="e.g. John Doe">
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Your Phone Number</label>
                            <input type="text" name="submitter_phone" class="input-control" placeholder="e.g. +1 234 567 890">
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Your Email*</label>
                        <input type="email" name="submitter_email" class="input-control" required placeholder="e.g. john@example.com">
                    </div>

                    <?php if (!isLoggedIn()): ?>
                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Username*</label>
                            <input type="text" name="username" class="input-control" required placeholder="Choose a username">
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Password*</label>
                            <input type="password" name="password" class="input-control" required placeholder="Create a password">
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="input-group">
                        <label>About Us*</label>
                        <textarea name="about_us" class="input-control" rows="3" required placeholder="Tell us about your company"></textarea>
                    </div>

                    <div class="checkbox-group" style="margin-bottom: 30px;">
                        <input type="checkbox" name="terms" id="terms" required>
                        <label for="terms">I accept the <a href="#" style="color:#64748b;">terms of service</a></label>
                    </div>

                    <div class="form-actions" style="justify-content: flex-end;">
                        <button type="button" class="btn-primary" id="btn-next">Next</button>
                    </div>
                </div>

                <!-- STEP 2: JOB DETAILS -->
                <div class="form-step" id="step-2">
                    <div class="section-title">GENERAL INFO</div>
                    
                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Job Title*</label>
                            <input type="text" name="title" class="input-control" required>
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Job Type*</label>
                            <select name="job_type" class="input-control" required>
                                <option value="" disabled selected>Select option...</option>
                                <option value="full-time">Full-time</option>
                                <option value="part-time">Part-time</option>
                                <option value="contract">Contract</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Category</label>
                            <select name="category" class="input-control">
                                <option value="" disabled selected>Choose a category</option>
                                <optgroup label="Housework">
                                    <option value="Nanny">Nanny</option>
                                    <option value="Housekeeper">Housekeeper</option>
                                    <option value="House Cook cleaning">House Cook cleaning</option>
                                </optgroup>
                                <optgroup label="Restaurant & Kitchen">
                                    <option value="Sushi Chef">Sushi Chef</option>
                                    <option value="Tandoori Chef">Tandoori Chef</option>
                                    <option value="Kitchen Helper">Kitchen Helper</option>
                                </optgroup>
                                <optgroup label="Store & Retail">
                                    <option value="Cashier">Cashier</option>
                                    <option value="Stock Guy">Stock Guy</option>
                                </optgroup>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Location</label>
                            <input type="text" name="location" class="input-control" placeholder="Job Location">
                            <span class="help-text">Example: "Remote, USA Only", "San Francisco"</span>
                            <div class="checkbox-group" style="margin-top: 6px;">
                                <input type="checkbox" name="is_remote" id="is_remote">
                                <label for="is_remote">This job is remote</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Tags</label>
                            <input type="text" name="tags" class="input-control" placeholder="e.g. Chef, Estate Manager, House Keeper">
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Work Arrangement (Live-in / Live-out)</label>
                            <select name="live_in_out" class="input-control">
                                <option value="" disabled selected>Select arrangement...</option>
                                <option value="live-in">Live-in</option>
                                <option value="live-out">Live-out</option>
                                <option value="any">Any / Negotiable</option>
                            </select>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Description*</label>
                        <div class="wysiwyg-container">
                            <div class="wysiwyg-toolbar">
                                <i class="fas fa-bold"></i> <i class="fas fa-italic"></i> <i class="fas fa-underline"></i> <i class="fas fa-strikethrough"></i>
                                <span style="color:#cbd5e1;">|</span>
                                <i class="fas fa-align-left"></i> <i class="fas fa-align-center"></i> <i class="fas fa-align-right"></i> <i class="fas fa-align-justify"></i>
                                <span style="color:#cbd5e1;">|</span>
                                <i class="fas fa-list-ul"></i> <i class="fas fa-list-ol"></i>
                                <span style="color:#cbd5e1;">|</span>
                                <i class="fas fa-link"></i> <i class="fas fa-remove-format"></i> <i class="far fa-image"></i>
                            </div>
                            <textarea name="description" required></textarea>
                        </div>
                    </div>

                    <div class="input-group">
                        <label>Requirements</label>
                        <div class="wysiwyg-container">
                            <textarea name="job_requirements" rows="5" style="width: 100%; border: none; padding: 15px; resize: vertical; min-height: 120px; font-family: inherit;" placeholder="List the job requirements here..."></textarea>
                        </div>
                    </div>

                    <div class="section-title">COMPENSATION</div>
                    
                    <div class="form-row-4">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Minimum pay</label>
                            <input type="number" name="salary_min" class="input-control" placeholder="100000">
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Maximum pay</label>
                            <input type="number" name="salary_max" class="input-control" placeholder="120000">
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Currency</label>
                            <select name="currency" class="input-control">
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="GBP">GBP</option>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Type</label>
                            <select name="pay_type" class="input-control">
                                <option value="annually">annually</option>
                                <option value="hourly">hourly</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Driving License*</label>
                            <select name="driving_license" class="input-control" required>
                                <option value="" disabled selected>Select option...</option>
                                <option value="required">Required</option>
                                <option value="preferred">Preferred</option>
                                <option value="not_required">Not Required</option>
                            </select>
                        </div>
                        <div class="input-group" style="margin-bottom:0;">
                            <label>Language Requirements*</label>
                            
                            <!-- Custom Multi-Select -->
                            <div class="multi-select-container" id="languageSelect">
                                <div class="multi-select-box" id="languageSelectBox">
                                    <span id="languageSelectText">Select options...</span>
                                    <i class="fas fa-caret-down"></i>
                                </div>
                                <div class="multi-select-dropdown" id="languageDropdown">
                                    <label class="multi-select-option"><input type="checkbox" value="English"> English</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Nepali"> Nepali</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Hindi"> Hindi</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Tibetan"> Tibetan</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Bhutani"> Bhutani</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Spanish"> Spanish</label>
                                    <label class="multi-select-option"><input type="checkbox" value="Other"> Other</label>
                                </div>
                            </div>
                            <!-- Hidden input to store actual value for POST -->
                            <input type="hidden" name="languages" id="languagesInput" required>
                            
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-back" id="btn-back"><i class="fas fa-arrow-left"></i> Back</button>
                        <button type="submit" name="submit_job" class="btn-primary" id="btn-submit">Confirm & Post</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- File Upload Logic ---
            const fileInput = document.getElementById('company_logo');
            const filenameDisplay = document.getElementById('filenameDisplay');
            
            fileInput.addEventListener('change', function(e) {
                if (e.target.files.length > 0) {
                    filenameDisplay.textContent = 'Selected: ' + e.target.files[0].name;
                    filenameDisplay.style.color = '#10b981'; // Green to show success
                } else {
                    filenameDisplay.textContent = '';
                }
            });
            
            // --- Wizard Step Logic ---
            const step1 = document.getElementById('step-1');
            const step2 = document.getElementById('step-2');
            const navStep1 = document.getElementById('nav-step-1');
            const navStep2 = document.getElementById('nav-step-2');
            const btnNext = document.getElementById('btn-next');
            const btnBack = document.getElementById('btn-back');
            
            // Function to validate Step 1 before proceeding
            function validateStep1() {
                const requiredFields = step1.querySelectorAll('input[required], textarea[required]');
                let isValid = true;
                requiredFields.forEach(field => {
                    if (!field.value.trim() || (field.type === 'checkbox' && !field.checked)) {
                        field.style.borderColor = '#ef4444'; // Red border
                        isValid = false;
                    } else {
                        field.style.borderColor = '#cbd5e1';
                    }
                });
                return isValid;
            }

            btnNext.addEventListener('click', () => {
                if (validateStep1()) {
                    step1.classList.remove('active');
                    step2.classList.add('active');
                    navStep1.classList.remove('active');
                    navStep2.classList.add('active');
                    window.scrollTo(0, 0);
                }
            });

            btnBack.addEventListener('click', () => {
                step2.classList.remove('active');
                step1.classList.add('active');
                navStep2.classList.remove('active');
                navStep1.classList.add('active');
                window.scrollTo(0, 0);
            });


            // --- Custom Language Multi-Select Logic ---
            const selectBox = document.getElementById('languageSelectBox');
            const selectText = document.getElementById('languageSelectText');
            const dropdown = document.getElementById('languageDropdown');
            const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
            const hiddenInput = document.getElementById('languagesInput');

            // Toggle dropdown
            selectBox.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!document.getElementById('languageSelect').contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });

            // Handle checkbox changes
            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateSelectText);
            });

            function updateSelectText() {
                const selected = [];
                checkboxes.forEach(cb => {
                    if (cb.checked) selected.push(cb.value);
                });
                
                if (selected.length === 0) {
                    selectText.textContent = 'Select options...';
                    selectText.style.color = '#94a3b8';
                    hiddenInput.value = '';
                } else if (selected.length <= 2) {
                    selectText.textContent = selected.join(', ');
                    selectText.style.color = '#334155';
                    hiddenInput.value = selected.join(', ');
                } else {
                    selectText.textContent = selected.length + ' languages selected';
                    selectText.style.color = '#334155';
                    hiddenInput.value = selected.join(', ');
                }
                
                // Clear validation styling if valid
                if(hiddenInput.value) {
                    selectBox.style.borderColor = '#cbd5e1';
                }
            }

            // Form Submit Validation for custom inputs
            document.getElementById('jobForm').addEventListener('submit', function(e) {
                if (!hiddenInput.value) {
                    e.preventDefault();
                    selectBox.style.borderColor = '#ef4444';
                    alert("Please select at least one language requirement.");
                }
            });

            // Make sure required asterisk color matching
            const labels = document.querySelectorAll('.input-group label');
            labels.forEach(l => {
                if(l.textContent.includes('*')) {
                    l.innerHTML = l.textContent.replace('*', '<span style="color:#ef4444">*</span>');
                }
            });
        });
    </script>
</body>
</html>