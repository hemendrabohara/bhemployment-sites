<?php
require_once 'config.php';

$errors = [];
$success = '';
$first_name = '';
$last_name = '';
$email = '';
$phone = '';
$location = '';
$bio = '';
$salary_expectation = '';
$languages = '';
$driving_license = 'Yes';
$roles_tags = '';
$username = '';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_candidate'])) {
    
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token. Please refresh the page and try again.";
    }
    $first_name = sanitizeInput($_POST['first_name'] ?? '');
    $last_name = sanitizeInput($_POST['last_name'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $location = sanitizeInput($_POST['location'] ?? '');
    $bio = sanitizeInput($_POST['bio'] ?? '');
    $salary_expectation = sanitizeInput($_POST['salary_expectation'] ?? '');
    $languages = sanitizeInput($_POST['languages'] ?? '');
    $driving_license = sanitizeInput($_POST['driving_license'] ?? '');
    $roles_tags = sanitizeInput($_POST['roles_tags'] ?? ''); // Comma separated roles

    // File Uploads
    $photo_path = null;
    $resume_path = null;
    
    $uploadDir = 'uploads/candidates/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Photo Upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $maxPhotoSize = 1 * 1024 * 1024; // 1MB Limit
        $fileName = time() . '_photo_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['photo']['name']));
        $targetFilePath = $uploadDir . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
        $fileType = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedTypes = array('jpg', 'jpeg', 'png', 'webp');
        
        if ($_FILES['photo']['size'] > $maxPhotoSize) {
            $errors[] = "Photo is too large. Maximum allowed size is 1MB.";
        } elseif (in_array($fileType, $allowedTypes)) {
            $info = getimagesize($_FILES['photo']['tmp_name']);
            if ($info !== false) {
                $image = null;
                if ($info['mime'] == 'image/jpeg') $image = imagecreatefromjpeg($_FILES['photo']['tmp_name']);
                elseif ($info['mime'] == 'image/png') $image = imagecreatefrompng($_FILES['photo']['tmp_name']);
                elseif ($info['mime'] == 'image/gif') $image = imagecreatefromgif($_FILES['photo']['tmp_name']);
                elseif ($info['mime'] == 'image/webp') $image = imagecreatefromwebp($_FILES['photo']['tmp_name']);
                
                if ($image) {
                    $width = imagesx($image);
                    $height = imagesy($image);
                    $newWidth = $width > 600 ? 600 : $width;
                    $newHeight = floor($height * ($newWidth / $width));
                    
                    $tmpImage = imagecreatetruecolor($newWidth, $newHeight);
                    if ($info['mime'] == 'image/png' || $info['mime'] == 'image/gif') {
                        imagefill($tmpImage, 0, 0, imagecolorallocate($tmpImage, 255, 255, 255));
                    }
                    imagecopyresampled($tmpImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
                    
                    // Compress to 60 quality to achieve lowest practical size
                    if (imagejpeg($tmpImage, $targetFilePath, 60)) {
                        $photo_path = $targetFilePath;
                    } else {
                        $errors[] = "Failed to compress and save photo.";
                    }
                    imagedestroy($image);
                    imagedestroy($tmpImage);
                } else {
                    $errors[] = "Invalid image file format.";
                }
            } else {
                $errors[] = "File is not a valid image.";
            }
        } else {
            $errors[] = "Only JPG, JPEG, PNG, & WEBP files are allowed for the photo.";
        }
    }

    // Resume Upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $maxResumeSize = 2 * 1024 * 1024; // 2MB limit
        $fileName = time() . '_resume_' . preg_replace("/[^a-zA-Z0-9.]/", "_", basename($_FILES['resume']['name']));
        $targetFilePath = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = array('pdf', 'doc', 'docx');
        
        if ($_FILES['resume']['size'] > $maxResumeSize) {
            $errors[] = "Resume is too large. Maximum allowed size is 2MB.";
        } elseif (in_array($fileType, $allowedTypes)) {
            
            // MIME type check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['resume']['tmp_name']);
            finfo_close($finfo);
            
            $allowedMimeTypes = [
                'application/pdf', 
                'application/msword', 
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ];
            
            if (!in_array($mimeType, $allowedMimeTypes)) {
                $errors[] = "Invalid file content. Please upload a valid PDF or Word document.";
            } elseif (move_uploaded_file($_FILES['resume']['tmp_name'], $targetFilePath)) {
                $resume_path = $targetFilePath;
            } else {
                $errors[] = "Failed to upload resume.";
            }
        } else {
            $errors[] = "Only PDF, DOC, & DOCX files are allowed for the resume.";
        }
    }

    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
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
                $stmt->execute([':email' => $email, ':username' => $username]);
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
            $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
            
            if (!$user_id) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, username, password, role) VALUES (:first_name, :last_name, :email, :phone, :username, :password, 'job_seeker')");
                $stmt->execute([
                    ':first_name' => $first_name,
                    ':last_name' => $last_name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':username' => $username,
                    ':password' => $hashed_password
                ]);
                $user_id = $pdo->lastInsertId();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'job_seeker';
                $_SESSION['user_name'] = trim("$first_name $last_name");
                $_SESSION['user_email'] = $email;
            }

            // We append the tags into the bio if candidate_roles table isn't used right now
            $full_bio = "$bio\n\nRoles Sought: $roles_tags";

            $stmt = $pdo->prepare("INSERT INTO candidates 
                (user_id, first_name, last_name, email, phone, location, bio, salary_expectation, photo_path, resume_path, languages, driving_license) 
                VALUES 
                (:user_id, :first_name, :last_name, :email, :phone, :location, :bio, :salary_expectation, :photo_path, :resume_path, :languages, :driving_license)");
            
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':first_name', $first_name, PDO::PARAM_STR);
            $stmt->bindParam(':last_name', $last_name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':location', $location, PDO::PARAM_STR);
            $stmt->bindParam(':bio', $full_bio, PDO::PARAM_STR);
            $stmt->bindParam(':salary_expectation', $salary_expectation, PDO::PARAM_STR);
            $stmt->bindParam(':photo_path', $photo_path, PDO::PARAM_STR);
            $stmt->bindParam(':resume_path', $resume_path, PDO::PARAM_STR);
            $stmt->bindParam(':languages', $languages, PDO::PARAM_STR);
            $stmt->bindParam(':driving_license', $driving_license, PDO::PARAM_STR);
            
            $stmt->execute();
            $success = "Your candidate profile has been submitted successfully!";
            
            // Apply for the job if apply_job_id is set
            $apply_job_id = isset($_POST['apply_job_id']) ? intval($_POST['apply_job_id']) : 0;
            if ($apply_job_id > 0) {
                try {
                    // Make sure resume_path is set, fallback to empty string if not (since it's NOT NULL)
                    $app_resume_path = !empty($resume_path) ? $resume_path : '';
                    $applyStmt = $pdo->prepare("INSERT IGNORE INTO job_applications (job_id, user_id, resume_path, status) VALUES (?, ?, ?, 'pending')");
                    $applyStmt->execute([$apply_job_id, $user_id, $app_resume_path]);
                    
                    // Increment application count for the job
                    $pdo->prepare("UPDATE jobs SET applications = applications + 1 WHERE id = ?")->execute([$apply_job_id]);
                    
                    $success = "Your profile was submitted and you have successfully applied for the job!";
                } catch(PDOException $e) {
                    error_log("Failed to apply for job: " . $e->getMessage());
                }
            }
            
        } catch(PDOException $e) {
            $errors[] = "An error occurred while submitting your profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find a Job - Candidate Application</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Reusing the clean card UI from submit-job.php */
        body { background-color: #f8fafc; font-family: 'Inter', sans-serif; }
        .page-header-simple { text-align: center; padding: 40px 0 20px; }
        .page-header-simple h1 { color: #0A192F; font-size: 28px; font-weight: 600; }
        .wizard-container { max-width: 800px; margin: 0 auto 60px; padding: 0 20px; }
        .wizard-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .section-title { font-size: 14px; font-weight: 700; color: #64748b; margin-bottom: 25px; margin-top: 30px; }
        .section-title:first-child { margin-top: 0; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 20px; }
        .input-group { display: flex; flex-direction: column; margin-bottom: 20px; }
        .input-group label { font-size: 13px; color: #64748b; margin-bottom: 8px; font-weight: 500; }
        .input-control { border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 16px; font-size: 14px; width: 100%; box-sizing: border-box; }
        .upload-box { border: 1px dashed #cbd5e1; border-radius: 6px; padding: 30px; text-align: center; background: #f8fafc; cursor: pointer; position: relative; margin-bottom: 20px;}
        .upload-box i { font-size: 32px; color: #cbd5e1; margin-bottom: 15px; }
        .filename-display { margin-top: 10px; font-size: 13px; font-weight: bold; color: #10b981; }
        .btn-primary { background: #0A192F; color: white; border: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 15px; }
        select.input-control { appearance: none; padding-right: 32px; background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2394a3b8%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E"); background-repeat: no-repeat; background-position: right 12px top 50%; background-size: 10px auto; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-top: 8px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="page-header-simple">
        <img src="images/logo.png" alt="B&H Employment" style="max-height: 80px; margin-bottom: 15px; display: block; margin-left: auto; margin-right: auto;">
        <h1>Submit Your Profile</h1>
    </div>

    <div class="wizard-container">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px; color: #c5221f;">
                <ul style="margin:0; padding-left:20px;">
                    <?php foreach ($errors as $error) echo "<li>$error</li>"; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" style="margin-bottom: 20px; color: #137333; font-weight: bold;"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="wizard-card">
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <?php 
                $apply_job_id = isset($_GET['apply_job_id']) ? intval($_GET['apply_job_id']) : (isset($_POST['apply_job_id']) ? intval($_POST['apply_job_id']) : 0);
                if ($apply_job_id > 0): 
                ?>
                <input type="hidden" name="apply_job_id" value="<?php echo $apply_job_id; ?>">
                <div class="alert alert-info" style="margin-bottom: 20px; color: #0066cc; background: #e6f2ff; padding: 15px; border-radius: 6px; font-weight: bold;">
                    <i class="fas fa-info-circle"></i> Complete your profile below to finalize your job application!
                </div>
                <?php endif; ?>
                
                <div class="section-title">PERSONAL DETAILS</div>
                
                <div class="form-row">
                    <div class="input-group">
                        <label>First Name*</label>
                        <input type="text" name="first_name" class="input-control" value="<?php echo htmlspecialchars($first_name); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Last Name*</label>
                        <input type="text" name="last_name" class="input-control" value="<?php echo htmlspecialchars($last_name); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Email Address*</label>
                        <input type="email" name="email" class="input-control" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="input-control" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>
                </div>

                <?php if (!isLoggedIn()): ?>
                <div class="form-row">
                    <div class="input-group">
                        <label>Username*</label>
                        <input type="text" name="username" class="input-control" value="<?php echo htmlspecialchars($username); ?>" required placeholder="Choose a username">
                    </div>
                    <div class="input-group">
                        <label>Password*</label>
                        <input type="password" name="password" class="input-control" required placeholder="Create a password">
                    </div>
                </div>
                <?php endif; ?>

                <div class="input-group">
                    <label>Location</label>
                    <input type="text" name="location" class="input-control" value="<?php echo htmlspecialchars($location); ?>" placeholder="City, State">
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Upload Profile Photo</label>
                        <div class="upload-box" onclick="document.getElementById('photo').click();">
                            <i class="far fa-user-circle"></i>
                            <div>Select Photo</div>
                            <div class="filename-display" id="photoDisplay"></div>
                            <input type="file" name="photo" id="photo" accept="image/*" style="opacity: 0; position: absolute; z-index: -1;">
                        </div>
                    </div>
                    <div class="input-group">
                        <label>Upload Resume (PDF/DOC)*</label>
                        <div class="upload-box" onclick="document.getElementById('resume').click();">
                            <i class="far fa-file-pdf"></i>
                            <div>Select Resume</div>
                            <div class="filename-display" id="resumeDisplay"></div>
                            <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx" style="opacity: 0; position: absolute; z-index: -1;" required>
                        </div>
                    </div>
                </div>

                <div class="section-title">PROFESSIONAL DETAILS</div>

                <div class="input-group">
                    <label>Desired Roles / Tags*</label>
                    <input type="text" name="roles_tags" class="input-control" value="<?php echo htmlspecialchars($roles_tags); ?>" placeholder="e.g. Executive Assistant, Private Chef" required>
                </div>

                <div class="form-row">
                    <div class="input-group">
                        <label>Salary Expectation</label>
                        <input type="text" name="salary_expectation" class="input-control" value="<?php echo htmlspecialchars($salary_expectation); ?>" placeholder="e.g. $100k/yr or $50/hr">
                    </div>
                    <div class="input-group">
                        <label>Driving License</label>
                        <select name="driving_license" class="input-control">
                            <option value="Yes" <?php echo $driving_license === 'Yes' ? 'selected' : ''; ?>>Yes</option>
                            <option value="No" <?php echo $driving_license === 'No' ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="input-group">
                    <label>Languages Spoken</label>
                    <input type="text" name="languages" class="input-control" value="<?php echo htmlspecialchars($languages); ?>" placeholder="e.g. English, Spanish">
                </div>

                <div class="input-group">
                    <label>Professional Bio / Summary*</label>
                    <textarea name="bio" class="input-control" rows="5" required placeholder="Tell employers about your experience and skills..."><?php echo htmlspecialchars($bio); ?></textarea>
                </div>

                <div class="checkbox-group" style="margin-bottom: 30px;">
                    <input type="checkbox" name="terms" id="terms" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
                    <label for="terms">I agree that my profile may be shared with potential employers.</label>
                </div>

                <div style="text-align: right;">
                    <button type="submit" name="submit_candidate" class="btn-primary">Submit Application</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
        document.getElementById('photo').addEventListener('change', function(e) {
            if (e.target.files.length > 0) document.getElementById('photoDisplay').textContent = e.target.files[0].name;
        });
        document.getElementById('resume').addEventListener('change', function(e) {
            if (e.target.files.length > 0) document.getElementById('resumeDisplay').textContent = e.target.files[0].name;
        });
    </script>
</body>
</html>
