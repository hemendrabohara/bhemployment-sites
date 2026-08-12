<?php
require_once 'config.php';

// Check verification status - redirect to verification page if not verified
if (isLoggedIn() && isJobSeeker() && !isVerified()) {
    flashMessage("Your account requires verification before you can view job details", "warning");
    redirect('verification-pending.php');
    exit;
}

// Handle Quick Apply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_apply'])) {
    $job_id = isset($_POST['job_id']) ? intval($_POST['job_id']) : 0;
    $name = trim($_POST['applicant_name']);
    $email = trim($_POST['applicant_email']);
    $phone = trim($_POST['applicant_phone']);
    $notes = trim($_POST['applicant_notes']);
    
    // Create guest user if not logged in
    $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
    if (!$user_id) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing) {
            $user_id = $existing['id'];
        } else {
            // Auto create a candidate
            $dummy_pass = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role) VALUES (?, '', ?, ?, ?, 'job_seeker')");
            $stmt->execute([$name, $email, $phone, $dummy_pass]);
            $user_id = $pdo->lastInsertId();
        }
    }
    
    // Insert application
    try {
        $stmt = $pdo->prepare("INSERT INTO job_applications (job_id, user_id, resume_path, cover_letter, status) VALUES (?, ?, 'Quick Apply', ?, 'pending')");
        $stmt->execute([$job_id, $user_id, "Name: $name\nPhone: $phone\nNotes: $notes"]);
        
        $pdo->prepare("UPDATE jobs SET applications = applications + 1 WHERE id = ?")->execute([$job_id]);
        flashMessage("Your application has been submitted successfully!", "success");
        redirect("job-details.php?id=" . $job_id);
        exit;
    } catch(Exception $e) {
        flashMessage("You have already applied for this job, or an error occurred.", "error");
    }
}

// Get job ID from URL
$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($job_id === 0) {
    flashMessage("Invalid job ID", "error");
    redirect('jobs.php');
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment'])) {
    $comment = trim($_POST['comment']);
    
    if (!empty($comment)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO job_comments (job_id, user_id, comment) VALUES (?, ?, ?)");
            $stmt->execute([$job_id, $_SESSION['user_id'], $comment]);
            flashMessage("Your comment has been posted successfully", "success");
            redirect('job-details.php?id=' . $job_id);
            exit;
        } catch (PDOException $e) {
            error_log("Error posting comment: " . $e->getMessage());
            flashMessage("Failed to post comment. Please try again.", "error");
        }
    } else {
        flashMessage("Comment cannot be empty", "error");
    }
}

// Handle comment deletion (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment']) && isAdmin()) {
    $comment_id = intval($_POST['comment_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM job_comments WHERE id = ? AND job_id = ?");
        $stmt->execute([$comment_id, $job_id]);
        flashMessage("Comment has been deleted successfully", "success");
        redirect('job-details.php?id=' . $job_id);
        exit;
    } catch (PDOException $e) {
        error_log("Error deleting comment: " . $e->getMessage());
        flashMessage("Failed to delete comment. Please try again.", "error");
    }
}

// Get job details from database
try {
    $stmt = $pdo->prepare("SELECT j.*, u.first_name, u.last_name, u.company_name as employer_company 
                           FROM jobs j 
                           LEFT JOIN users u ON j.user_id = u.id 
                           WHERE j.id = ? AND j.is_active = 1 AND j.approval_status = 'approved'");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
    
    if (!$job) {
        flashMessage("Job not found or not available", "error");
        redirect('jobs.php');
        exit;
    }
    
    // Increment view count
    $update_views = $pdo->prepare("UPDATE jobs SET views = views + 1 WHERE id = ?");
    $update_views->execute([$job_id]);
    
} catch (PDOException $e) {
    error_log("Error fetching job: " . $e->getMessage());
    flashMessage("Failed to load job details", "error");
    redirect('jobs.php');
    exit;
}

// Get comments for this job
try {
    $stmt = $pdo->prepare("SELECT jc.*, u.first_name, u.last_name, u.role 
                           FROM job_comments jc 
                           JOIN users u ON jc.user_id = u.id 
                           WHERE jc.job_id = ? 
                           ORDER BY jc.created_at DESC");
    $stmt->execute([$job_id]);
    $comments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching comments: " . $e->getMessage());
    $comments = [];
}

// Get similar jobs
try {
    $similar_stmt = $pdo->prepare("SELECT j.*, u.company_name as employer_company 
                                   FROM jobs j 
                                   LEFT JOIN users u ON j.user_id = u.id 
                                   WHERE j.is_active = 1 AND j.approval_status = 'approved' 
                                   AND j.id != ? AND j.job_type = ? 
                                   ORDER BY j.created_at DESC LIMIT 2");
    $similar_stmt->execute([$job_id, $job['job_type']]);
    $similar_jobs = $similar_stmt->fetchAll();
    
    // If not enough similar jobs by type, just get recent jobs
    if (count($similar_jobs) < 2) {
        $recent_stmt = $pdo->prepare("SELECT j.*, u.company_name as employer_company 
                                       FROM jobs j 
                                       LEFT JOIN users u ON j.user_id = u.id 
                                       WHERE j.is_active = 1 AND j.approval_status = 'approved' 
                                       AND j.id != ? 
                                       ORDER BY j.created_at DESC LIMIT 2");
        $recent_stmt->execute([$job_id]);
        $similar_jobs = $recent_stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Error fetching similar jobs: " . $e->getMessage());
    $similar_jobs = [];
}

// Format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $timestamp);
    }
}
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
    <title><?php echo html_entity_decode($job['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?> - B&H Employment & Consultancy Inc</title>
    <meta name="description" content="<?php echo html_entity_decode(substr(strip_tags($job['description']), 0, 160), ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
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
    <?php include 'includes/header.php'; ?>

    <section style="background-color: white; border-bottom: 1px solid #eaeaea; padding: 40px 0 30px 0; margin-top: 0;">
        <div class="container">
            <div style="margin-bottom: 15px;">
                <a href="jobs.php" style="color: #666; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 5px; transition: color 0.2s;" onmouseover="this.style.color='#0066cc'" onmouseout="this.style.color='#666'"><i class="fas fa-arrow-left"></i> Back to Jobs</a>
            </div>
            <h1 style="color: #333; font-size: 28px; margin-bottom: 12px; font-weight: 600;">
                <?php echo html_entity_decode($job['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
            </h1>
            <div style="color: #666; font-size: 14px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                <span>Posted by: <strong><?php echo html_entity_decode($job['company_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></strong></span>
                <span style="color: #ccc;">|</span>
                <span>Location: <strong style="color: #0066cc;"><?php echo html_entity_decode($job['location'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></strong></span>
                <span style="color: #ccc;">|</span>
                <span>Job Id: <strong><?php echo $job['id']; ?></strong></span>
            </div>
        </div>
    </section>

    <section class="job-details-section">
        <div class="container">
            <?php displayFlashMessage(); ?>
            
            <div class="job-details-container">
                <!-- Main Job Details -->
                <div class="job-details-main">

                    <div class="job-section">
                        <h2><i class="fas fa-file-alt"></i> Job Description</h2>
                        <div class="job-content">
                            <?php echo nl2br(html_entity_decode($job['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
                        </div>
                    </div>

                    <div class="job-section">
                        <h2><i class="fas fa-check-circle"></i> Requirements</h2>
                        <div class="job-content">
                            <?php echo nl2br(html_entity_decode($job['requirements'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
                        </div>
                    </div>

                    <?php if (!empty($job['application_instructions'])): ?>
                        <div class="job-section">
                            <h2><i class="fas fa-info-circle"></i> Application Instructions</h2>
                            <div class="job-content">
                                <?php echo nl2br(html_entity_decode($job['application_instructions'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Job Location Map -->
                    <div class="job-section">
                        <h2><i class="fas fa-map-marked-alt"></i> Job Location</h2>
                        <div class="job-content" style="border-radius: 8px; overflow: hidden; border: 1px solid #e0e0e0;">
                            <iframe width="100%" height="350" frameborder="0" style="border:0" src="https://maps.google.com/maps?q=<?php echo urlencode($job['location']); ?>&t=&z=13&ie=UTF8&iwloc=&output=embed" allowfullscreen></iframe>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="job-section comments-section">
                        <h2><i class="fas fa-comments"></i> Discussion (<?php echo count($comments); ?>)</h2>
                        
                        <!-- Comment Form -->
                        <div class="comment-form-container">
                            <form method="POST" action="" class="comment-form">
                                <textarea name="comment" placeholder="Ask a question or share your thoughts about this job..." rows="4" required></textarea>
                                <button type="submit" name="submit_comment" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Post Comment
                                </button>
                            </form>
                        </div>

                        <!-- Comments List -->
                        <div class="comments-list">
                            <?php if (empty($comments)): ?>
                                <div class="no-comments">
                                    <i class="fas fa-comment-slash"></i>
                                    <p>No comments yet. Be the first to comment!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-item">
                                        <div class="comment-header">
                                            <div class="comment-author">
                                                <i class="fas fa-user-circle"></i>
                                                <strong><?php echo html_entity_decode($comment['first_name'] . ' ' . $comment['last_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></strong>
                                                <?php if ($comment['role'] === 'admin'): ?>
                                                    <span class="badge badge-admin">Admin</span>
                                                <?php elseif ($comment['role'] === 'employer'): ?>
                                                    <span class="badge badge-employer">Employer</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="comment-actions">
                                                <div class="comment-time">
                                                    <i class="fas fa-clock"></i>
                                                    <?php echo timeAgo($comment['created_at']); ?>
                                                </div>
                                                <?php if (isAdmin()): ?>
                                                    <form method="POST" action="" class="delete-comment-form" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                                        <button type="submit" name="delete_comment" class="btn-delete-comment" title="Delete comment">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="comment-body">
                                            <?php echo nl2br(html_entity_decode($comment['comment'], ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="job-details-sidebar">
                    <div class="sidebar-card">
                        <div style="text-align: center; margin-bottom: 25px;">
                            <div style="width: 60px; height: 60px; background: #e6f2ff; color: #0066cc; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 24px; margin: 0 auto 15px;">
                                <i class="fas fa-building"></i>
                            </div>
                            <h3 style="font-size: 18px; color: #333; margin-bottom: 5px; justify-content: center;">
                                <?php echo html_entity_decode($job['company_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                            </h3>
                            <a href="#about-us" style="color: #0066cc; font-size: 14px; text-decoration: none;">View Company Profile</a>
                        </div>
                        
                        <div class="summary-list">
                            <div class="summary-item">
                                <span class="summary-label">Job Type</span>
                                <span class="summary-val"><?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Experience</span>
                                <span class="summary-val"><?php echo ucfirst($job['experience_level']); ?> Level</span>
                            </div>
                            <?php if (!empty($job['salary_min']) || !empty($job['salary_max'])): ?>
                            <div class="summary-item">
                                <span class="summary-label">Expected Hourly</span>
                                <span class="summary-val">
                                    <?php 
                                        if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
                                            echo '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']);
                                        } elseif (!empty($job['salary_min'])) {
                                            echo 'From $' . number_format($job['salary_min']);
                                        } elseif (!empty($job['salary_max'])) {
                                            echo 'Up to $' . number_format($job['salary_max']);
                                        }
                                    ?>
                                </span>
                            </div>
                            <?php endif; ?>
                            <div class="summary-item">
                                <span class="summary-label">Start Date</span>
                                <span class="summary-val">ASAP</span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Location</span>
                                <span class="summary-val"><?php echo html_entity_decode($job['location'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Posted</span>
                                <span class="summary-val"><?php echo formatDate($job['created_at']); ?></span>
                            </div>
                        </div>

                        <div style="margin-top: 25px; display: flex; flex-direction: column; gap: 12px;">
                            <button onclick="document.getElementById('applyModal').style.display='flex'" class="btn-primary" style="width: 100%; text-align: center; padding: 14px; font-size: 16px; border-radius: 30px; background: #0066cc; border: none; color: white; cursor: pointer; font-weight: bold; box-shadow: 0 4px 10px rgba(0,102,204,0.3); transition: all 0.3s;">Apply Now</button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Similar Jobs Section -->
            <?php if (!empty($similar_jobs)): ?>
            <div style="margin-top: 50px;">
                <h2 style="font-size: 22px; margin-bottom: 20px; color: #333; font-weight: bold;">Looking for similar job postings?</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($similar_jobs as $s_job): ?>
                    <a href="job-details.php?id=<?php echo $s_job['id']; ?>" style="text-decoration: none; display: block; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; background: white; transition: box-shadow 0.3s; color: inherit;" onmouseover="this.style.boxShadow='0 4px 15px rgba(0,0,0,0.1)'" onmouseout="this.style.boxShadow='none'">
                        <div style="display: flex; gap: 15px;">
                            <div style="width: 50px; height: 50px; background: #f0f4f8; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #0066cc; font-size: 20px; flex-shrink: 0;">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div style="flex-grow: 1; overflow: hidden;">
                                <div style="font-size: 12px; color: #0066cc; margin-bottom: 5px; display: flex; justify-content: space-between;">
                                    <span>Career Recruitment</span>
                                    <span><?php echo timeAgo($s_job['created_at']); ?></span>
                                </div>
                                <h4 style="margin: 0 0 5px 0; font-size: 16px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo html_entity_decode($s_job['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                                </h4>
                                <div style="font-size: 13px; color: #666; margin-bottom: 8px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo html_entity_decode($s_job['employer_company'] ?? $s_job['company_name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                                </div>
                                <div style="font-size: 13px; color: #666; display: flex; align-items: center; gap: 5px;">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <span style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo html_entity_decode($s_job['location'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 20px; text-align: center;">
                    <a href="jobs.php?type=<?php echo urlencode($job['job_type']); ?>" style="display: inline-block; width: 100%; padding: 12px; background: white; border: 1px solid #333; color: #333; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='white'">
                        View more similar jobs <i class="fas fa-chevron-down"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #0066cc;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            color: #004999;
            transform: translateX(-5px);
        }

        .job-details-section {
            padding: 40px 0;
            background-color: #f8f9fa;
        }

        .job-details-container {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-top: 30px;
        }

        .job-details-main {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .job-header-info {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .job-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .job-meta-item i {
            color: #0066cc;
        }

        .job-section {
            margin-bottom: 40px;
        }

        .job-section h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .job-section h2 i {
            color: #0066cc;
        }

        .job-content {
            line-height: 1.8;
            color: #555;
        }

        .job-details-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .sidebar-card h3 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-card h3 i {
            color: #0066cc;
        }

        .contact-info p, .company-info p {
            margin: 10px 0;
            color: #666;
        }

        .contact-info a {
            color: #0066cc;
            text-decoration: none;
        }

        .contact-info a:hover {
            text-decoration: underline;
        }

        .job-stats .stat-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }

        .job-stats .stat-item:last-child {
            border-bottom: none;
        }

        .job-stats .stat-item i {
            color: #0066cc;
        }

        /* Comments Section */
        .comments-section {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 2px solid #f0f0f0;
        }

        .comment-form-container {
            margin-bottom: 30px;
        }

        .comment-form textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.3s;
        }

        .comment-form textarea:focus {
            outline: none;
            border-color: #0066cc;
        }

        .comment-form button {
            margin-top: 15px;
            padding: 12px 30px;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }

        .comment-form button:hover {
            background: #004999;
        }

        .comments-list {
            margin-top: 30px;
        }

        .comment-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .comment-author {
            display: flex;
            alignactions {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .comment-time {
            color: #999;
            font-size: 13px;
        }

        .delete-comment-form {
            display: inline-block;
            margin: 0;
        }

        .btn-delete-comment {
            background: transparent;
            border: none;
            color: #dc3545;
            cursor: pointer;
            padding: 5px 8px;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .btn-delete-comment:hover {
            background: #dc3545;
            color: white;
        }

        .btn-delete-comment i {
            pointer-events: none;
        }

        .comment-author i {
            color: #0066cc;
            font-size: 20px;
        }

        .comment-time {
            color: #999;
            font-size: 13px;
        }

        .comment-body {
            color: #555;
            line-height: 1.6;
            padding-left: 30px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-admin {
            background: #dc3545;
            color: white;
        }

        .badge-employer {
            background: #28a745;
            color: white;
        }

        .no-comments {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .no-comments i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ddd;
        }

        @media (max-width: 768px) {
            .job-details-container {
                grid-template-columns: 1fr;
            }

            .job-details-sidebar {
                order: -1;
            }

            .job-meta {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
    <style>
        /* Quick Apply Modal CSS */
        .apply-modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            align-items: center;
            justify-content: center;
        }
        .apply-modal-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 450px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            animation: modalFadeIn 0.3s;
        }
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .close-modal {
            position: absolute;
            top: 15px; right: 20px;
            font-size: 24px;
            color: #999;
            cursor: pointer;
            transition: color 0.2s;
        }
        .close-modal:hover { color: #333; }
        .apply-modal h3 { color: #0066cc; margin-top: 0; margin-bottom: 20px; font-size: 22px; }
        .apply-modal .form-group { margin-bottom: 15px; }
        .apply-modal .form-control { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-family: inherit; }
        .apply-modal .form-control:focus { outline: none; border-color: #0066cc; }
        
        /* Summary List CSS */
        .summary-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 12px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .summary-item:last-child { border-bottom: none; padding-bottom: 0; }
        .summary-label { color: #666; font-weight: 500; }
        .summary-val { color: #333; font-weight: 600; text-align: right; }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,102,204,0.4) !important;
        }
    </style>
    
    <div id="applyModal" class="apply-modal">
        <div class="apply-modal-content">
            <span class="close-modal" onclick="document.getElementById('applyModal').style.display='none'">&times;</span>
            <h3>Quick Apply</h3>
            <form action="" method="POST">
                <input type="hidden" name="quick_apply" value="1">
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                
                <div class="form-group">
                    <input type="text" name="applicant_name" class="form-control" placeholder="Full Name" required <?php echo isLoggedIn() ? 'value="'.htmlspecialchars($_SESSION['user_name']).'"' : ''; ?>>
                </div>
                <div class="form-group">
                    <input type="email" name="applicant_email" class="form-control" placeholder="Email Address" required <?php echo isLoggedIn() && isset($_SESSION['user_email']) ? 'value="'.htmlspecialchars($_SESSION['user_email']).'"' : ''; ?>>
                </div>
                <div class="form-group">
                    <input type="tel" name="applicant_phone" class="form-control" placeholder="Phone Number" required>
                </div>
                <div class="form-group">
                    <textarea name="applicant_notes" class="form-control" placeholder="Notes about this job (optional)..." rows="4"></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; border: none; border-radius: 6px; background: #0066cc; color: white; font-weight: bold; font-size: 16px; cursor: pointer;">Submit Application</button>
            </form>
        </div>
    </div>
    
    <script>
        // Close modal when clicking outside
        window.onclick = function(event) {
            var modal = document.getElementById('applyModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

    <script src="js/script.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>