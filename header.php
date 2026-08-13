<?php
require_once __DIR__ . '/../config.php';

// Get current page to highlight active link
$current_page = basename($_SERVER['PHP_SELF']);

// Determine if we're in the root directory or a subdirectory
$is_root = (dirname($_SERVER['PHP_SELF']) == '/' || dirname($_SERVER['PHP_SELF']) == '\\');
$base_path = $is_root ? '' : '../';

// Get site settings
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

// Set default values if settings are not found
$site_title = isset($site_settings['site_title']) ? html_entity_decode($site_settings['site_title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'B&H Employment & Consultancy Inc';
$site_description = $site_settings['site_description'] ?? 'Professional employment agency connecting qualified candidates with top employers';
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
    <title><?php echo $site_title; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($site_description); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_path; ?>css/styles.css">

    <!-- Dynamic Favicon -->
    <?php
    $favicon_path = '';
    if (!empty($site_settings['favicon'])) {
        $favicon_path = $base_path . $site_settings['favicon'];
        $favicon_type = pathinfo($site_settings['favicon'], PATHINFO_EXTENSION);
        $favicon_type = ($favicon_type === 'ico') ? 'x-icon' : $favicon_type;
    } else {
        $favicon_path = $base_path . 'images/favicon.ico';
        $favicon_type = 'x-icon';
    }
    ?>
    <link rel="icon" href="<?php echo $favicon_path; ?>?v=<?php echo time(); ?>" type="image/<?php echo $favicon_type; ?>">
    <link rel="shortcut icon" href="<?php echo $favicon_path; ?>?v=<?php echo time(); ?>" type="image/<?php echo $favicon_type; ?>">

    <!-- Mobile-friendly meta tags -->
    <meta name="theme-color" content="#0066cc">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>

<!-- Top Utility Bar -->
<div class="top-utility-bar" style="position: relative; z-index: 9999; overflow: visible;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; padding: 0 15px;">
        <div class="utility-left">
            <a href="tel:+19293856177"><i class="fas fa-phone-alt"></i> +929-385-6177</a>
            <a href="mailto:bh.jobagency@gmail.com"><i class="fas fa-envelope"></i> bh.jobagency@gmail.com</a>
        </div>
        <div class="utility-right">
            <div class="post-need-dropdown" style="position: relative; z-index: 9999; overflow: visible;">
                <button class="btn-post-need" onclick="this.nextElementSibling.classList.toggle('show')">Post your need <i class="fas fa-chevron-down"></i></button>
                <div class="dropdown-content" style="z-index: 10000;">
                    <a href="<?php echo $base_path; ?>submit-candidate.php"><i class="fas fa-user-tie"></i> Find job</a>
                    <a href="<?php echo $base_path; ?>submit-job.php"><i class="fas fa-briefcase"></i> Find workers</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Header -->
<header>
    <div class="container">
        <div class="header-content">
            <div class="logo">
                <a href="<?php echo $base_path; ?>index.php">
                    <img src="<?php echo $base_path; ?>images/logo.png" alt="B&H Employment Logo">
                </a>
            </div>

            <button class="mobile-menu-btn" aria-label="Toggle menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>

            <div class="main-navigation">
                <ul class="nav-menu">
                    <li><a href="<?php echo $base_path; ?>index.php" class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>"><i class="fas fa-home"></i> Home</a></li>
                    
                    <!-- Jobs Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle <?php echo in_array($current_page, ['jobs.php', 'job-form.php', 'submit-job.php']) ? 'active' : ''; ?>"><i class="fas fa-briefcase"></i> Jobs <i class="fas fa-caret-down dropdown-icon"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base_path; ?>jobs.php"><i class="fas fa-search"></i> Browse Jobs</a></li>
                            <li><a href="<?php echo $base_path; ?>job-form.php"><i class="fas fa-file-alt"></i> Job Form</a></li>
                            <li><a href="<?php echo $base_path; ?>submit-job.php"><i class="fas fa-plus-circle"></i> Submit a Job</a></li>
                        </ul>
                    </li>

                    <li><a href="<?php echo $base_path; ?>index.php#services" class="<?php echo $current_page === 'services.php' ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> Services</a></li>
                    <li><a href="<?php echo $base_path; ?>blog.php" class="<?php echo $current_page === 'blog.php' || $current_page === 'blog-post.php' ? 'active' : ''; ?>"><i class="fas fa-blog"></i> Blog</a></li>
                    <li><a href="<?php echo $base_path; ?>index.php#about" class="<?php echo $current_page === 'about.php' ? 'active' : ''; ?>"><i class="fas fa-info-circle"></i> About Us</a></li>
                    
                    <!-- Contact Dropdown -->
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle <?php echo in_array($current_page, ['contact.php', 'request-appointment.php']) ? 'active' : ''; ?>"><i class="fas fa-envelope"></i> Contact <i class="fas fa-caret-down dropdown-icon"></i></a>
                        <ul class="dropdown-menu">
                            <li><a href="<?php echo $base_path; ?>request-appointment.php"><i class="fas fa-calendar-alt"></i> Request Appointment</a></li>
                            <li><a href="<?php echo $base_path; ?>index.php#contact"><i class="fas fa-map-marker-alt"></i> Office Location</a></li>
                        </ul>
                    </li>
                </ul>

                <ul class="auth-menu">
                    <?php if (isLoggedIn()): ?>
                        <li class="user-menu">
                            <a href="#" class="user-toggle" aria-expanded="false">
                                <i class="fas fa-user-circle"></i>
                                <?php echo isset($_SESSION['user_name']) ? explode(' ', $_SESSION['user_name'])[0] : 'User'; ?>
                                <i class="fas fa-caret-down"></i>
                            </a>
                            <ul class="user-dropdown">
                                <?php if (isAdmin()): ?>
                                    <li><a href="<?php echo $base_path; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Admin Dashboard</a></li>
                                <?php elseif (isJobSeeker()): ?>
                                    <li><a href="<?php echo $base_path; ?>job-seeker/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                <?php endif; ?>
                                <li><a href="<?php echo $base_path; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_path; ?>login.php" class="login-btn <?php echo $current_page === 'login.php' ? 'active' : ''; ?>"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="<?php echo $base_path; ?>register.php" class="highlight-btn <?php echo $current_page === 'register.php' ? 'active' : ''; ?>"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</header>
<style>
/* Top Utility Bar */
.top-utility-bar {
    position: relative;
    z-index: 1001;
    background-color: #0A192F;
    color: #e2e8f0;
    padding: 8px 0;
    font-size: 13px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.utility-left a, .utility-right a {
    color: #e2e8f0;
    text-decoration: none;
    margin-right: 20px;
    transition: color 0.2s ease;
    font-weight: 500;
}

.utility-right a {
    margin-right: 0;
}

.utility-left a:hover, .utility-right a:hover {
    color: #C5A059;
}

.utility-left i, .utility-right i {
    margin-right: 6px;
    color: #C5A059;
}

header {
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    background-color: #fff;
    transition: all 0.3s ease;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
}

.logo {
    flex: 0 0 auto;
    z-index: 101;
}

.logo img {
    height: 90px;
    max-width: 100%;
    transition: all 0.3s ease;
}

.main-navigation {
    display: flex;
    justify-content: space-between;
    flex: 1;
    margin-left: 20px;
}

.nav-menu,
.auth-menu {
    display: flex;
    list-style: none;
    margin: 0;
    padding: 0;
    align-items: center;
}

.nav-menu {
    flex-grow: 1;
    justify-content: center;
    flex-wrap: wrap;
}

.auth-menu {
    margin-left: 15px;
}

.nav-menu li,
.auth-menu li {
    margin: 0 5px;
    position: relative;
}

.nav-menu a,
.auth-menu a {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    color: #333;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border-radius: 30px;
}

.nav-menu a i,
.auth-menu a i {
    margin-right: 5px;
    font-size: 16px;
}

.nav-menu a:hover,
.auth-menu a:hover {
    color: #0066cc;
    background-color: rgba(0, 102, 204, 0.05);
}

.nav-menu a.active,
.auth-menu a.active {
    color: #0066cc;
    background-color: rgba(0, 102, 204, 0.08);
}

/* Dropdown styling */
.nav-menu .dropdown {
    position: relative;
}

.nav-menu .dropdown-toggle {
    display: flex;
    align-items: center;
}

.nav-menu .dropdown-icon {
    margin-left: 5px;
    font-size: 12px;
    transition: transform 0.2s ease;
}

.nav-menu .dropdown:hover .dropdown-icon {
    transform: rotate(180deg);
}

.nav-menu .dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background-color: white;
    min-width: 220px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    border-radius: 8px;
    padding: 10px 0;
    margin: 0;
    list-style: none;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 200;
}

.nav-menu .dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.nav-menu .dropdown-menu li {
    margin: 0;
    width: 100%;
}

.nav-menu .dropdown-menu a {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    border-radius: 0;
    font-weight: 500;
    color: #333;
    border-bottom: 1px solid #f8fafc;
}

.nav-menu .dropdown-menu a:hover {
    background-color: #f8fafc;
    color: #0066cc;
    padding-left: 25px; /* Slight indent effect */
}

.login-btn,
.highlight-btn {
    padding: 8px 20px;
}

.login-btn {
    border: 2px solid #0066cc;
    color: #0066cc !important;
}

.highlight-btn {
    background: linear-gradient(135deg, #0066cc, #0052a3);
    color: white !important;
    box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
}

.login-btn:hover {
    background-color: rgba(0, 102, 204, 0.1);
}

.highlight-btn:hover {
    background: linear-gradient(135deg, #0052a3, #004080);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
}

/* User dropdown menu */
.user-menu {
    position: relative;
}

.user-toggle {
    cursor: pointer;
    display: flex;
    align-items: center;
}

.user-toggle i.fa-caret-down {
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.user-menu:hover .user-toggle i.fa-caret-down {
    transform: rotate(180deg);
}

.user-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    background-color: white;
    min-width: 200px;
    border-radius: 8px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    padding: 10px 0;
    z-index: 101;
    transform: translateY(10px);
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.user-dropdown.active,
.user-menu:hover .user-dropdown {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
}

.user-dropdown li {
    margin: 0;
    padding: 0;
}

.user-dropdown a {
    padding: 10px 20px;
    display: flex;
    align-items: center;
    color: #333;
    border-radius: 0;
}

.user-dropdown a i {
    margin-right: 10px;
    color: #0066cc;
}

.user-dropdown a:hover {
    background-color: #f5f5f5;
    color: #0066cc;
}

/* Mobile menu button */
.mobile-menu-btn {
    display: none;
    background: none;
    border: none;
    color: #333;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
    z-index: 200; /* Increased z-index */
}

/* Mobile menu styles */
@media (max-width: 991px) {
    .top-utility-bar {
        display: none;
    }

    .nav-menu .dropdown-menu {
        position: relative;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        background-color: #f8fafc;
        padding-left: 15px;
        display: none;
    }
    
    .nav-menu .dropdown:hover .dropdown-menu,
    .nav-menu .dropdown.active .dropdown-menu {
        display: block;
    }

    .mobile-menu-btn {
        display: block;
        position: relative;
        z-index: 200;
    }

    .main-navigation {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100vh;
        background-color: #fff;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        padding-top: 80px;
        padding-bottom: 30px; /* Add padding at the bottom */
        transform: translateX(-100%);
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 150;
        overflow-y: auto; /* Ensure scrolling works */
        margin-left: 0;
        max-height: 100vh; /* Limit height to viewport height */
    }

    .main-navigation.active {
        transform: translateX(0);
        opacity: 1;
        visibility: visible;
        overflow-y: auto;
    }

    .nav-menu, .auth-menu {
        width: 100%;
        flex-direction: column;
        margin: 0;
        padding: 0;
    }

    .nav-menu {
        margin-bottom: 0;
        padding-bottom: 10px;
    }

    .auth-menu {
        margin-top: 10px;
        margin-left: 0;
        order: 2;
        padding-bottom: 50px; /* Add extra padding at the bottom */
        position: relative;
    }

    .nav-menu li, .auth-menu li {
        width: 100%;
        margin: 0;
    }

    .nav-menu a, .auth-menu a {
        width: 100%;
        padding: 15px 20px;
        border-radius: 0;
        border-bottom: 1px solid #f0f0f0;
    }

    /* Special handling for login and register buttons */
    .auth-menu .login-btn,
    .auth-menu .highlight-btn {
        margin: 10px 20px;
        width: calc(100% - 40px);
        justify-content: center;
        text-align: center;
        border-radius: 30px;
        display: flex;
        align-items: center;
        position: relative;
    }

    /* Fix for Register button */
    .auth-menu .highlight-btn {
        margin-top: 0;
        background: linear-gradient(135deg, #0066cc, #0052a3);
        color: white !important;
        border: none;
        padding: 12px 20px;
        margin-bottom: 20px;
    }

    /* Support scrolling */
    body.menu-open {
        overflow: hidden;
        position: fixed;
        width: 100%;
        height: 100%;
    }
}

/* Post Your Need Dropdown */
.post-need-dropdown {
    position: relative;
    display: inline-block;
    z-index: 100;
}
.btn-post-need {
    background: linear-gradient(135deg, #0066cc, #0052a3);
    color: white;
    padding: 6px 15px;
    font-size: 13px;
    border: 1px solid rgba(255,255,255,0.2);
    cursor: pointer;
    border-radius: 30px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.btn-post-need i {
    font-size: 11px;
}
.btn-post-need:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 15px rgba(0, 102, 204, 0.4);
}
.post-need-dropdown .dropdown-content {
    display: none;
    position: absolute;
    top: 110%;
    right: 0;
    background-color: #ffffff;
    min-width: 200px;
    box-shadow: 0px 10px 25px rgba(0,0,0,0.15);
    z-index: 10000;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,0.05);
    text-align: left;
}
.post-need-dropdown:hover .dropdown-content,
.post-need-dropdown .dropdown-content.show {
    display: block;
    animation: dropdownFadeIn 0.2s ease-out forwards;
}
@keyframes dropdownFadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
}
.post-need-dropdown .dropdown-content a {
    color: #2C3E50;
    padding: 12px 16px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
    transition: all 0.2s ease;
    font-weight: 500;
    font-size: 14px;
    border-bottom: 1px solid #f1f5f9;
}
.post-need-dropdown .dropdown-content a:last-child {
    border-bottom: none;
}
.post-need-dropdown .dropdown-content a:hover {
    background-color: #f8fafc;
    color: #0066cc;
    padding-left: 20px;
}
</style>

