<?php
require_once 'config.php';

// Check if user is logged in
$is_logged_in = isLoggedIn();

// Check verification status
if ($is_logged_in && isJobSeeker() && !isVerified()) {
    if (!isset($_GET['ajax'])) {
        flashMessage("Your account requires verification before you can view job listings", "warning");
        redirect('verification-pending.php');
        exit;
    }
}

// Helper function to format dates
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Helper function to render job cards HTML
function renderJobCards($jobs, $is_logged_in) {
    if (empty($jobs)) {
        return '
        <div class="no-jobs-found" style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px; border: 1px solid #eee;">
            <i class="fas fa-search fa-3x" style="color: #ccc; margin-bottom: 20px;"></i>
            <h3 style="color: #2C3E50; margin-bottom: 10px;">No jobs found</h3>
            <p style="color: #666;">Try adjusting your filters or search keywords.</p>
        </div>';
    }
    
    $html = '';
    foreach ($jobs as $job) {
        $isFeatured = !empty($job['is_featured']);
        $featuredClass = $isFeatured ? 'featured-job' : '';
        $featuredRibbon = $isFeatured ? '<div class="featured-ribbon">Featured</div>' : '';
        $verifiedBadge = !empty($job['background_checked']) ? '<span class="badge verified-badge"><i class="fas fa-check-circle"></i> Verified</span>' : '';
        $urgentPill = (!empty($job['urgency']) && strtolower($job['urgency']) == 'urgent') ? '<span class="badge urgent-badge">Hiring Now</span>' : '';
        
        $salary = '';
        if (!empty($job['salary_min']) && !empty($job['salary_max'])) {
            $salary = '$' . number_format($job['salary_min']) . ' - $' . number_format($job['salary_max']) . '/hr';
        } elseif (!empty($job['salary_min'])) {
            $salary = 'From $' . number_format($job['salary_min']) . '/hr';
        } elseif (!empty($job['salary_max'])) {
            $salary = 'Up to $' . number_format($job['salary_max']) . '/hr';
        }
        
        $isActive = !isset($job['is_active']) || $job['is_active'] == 1;
        $activeBadge = $isActive ? '<span class="badge" style="background: #e6f4ea; color: #137333; border: 1px solid #ceead6;"><i class="fas fa-circle" style="font-size: 8px; vertical-align: middle; margin-right: 3px;"></i> Active</span>' : '<span class="badge" style="background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1;"><i class="fas fa-circle" style="font-size: 8px; vertical-align: middle; margin-right: 3px;"></i> Inactive</span>';
        
        if (!$isActive) {
            $featuredClass .= ' inactive-job';
            $btnText = "Closed";
            $link = "#";
        } else {
            if ($is_logged_in) {
                $link = "job-details.php?id=" . $job['id'];
                $btnText = "Apply Now";
            } else {
                $link = "submit-candidate?apply_job_id=" . $job['id'];
                $btnText = "Apply";
            }
        }
        
        $descSnippet = htmlspecialchars(substr(strip_tags($job['description']), 0, 150)) . '...';
        $datePosted = formatDate($job['created_at']);
        
        $logoHtml = '<div class="logo-circle"><i class="fas fa-briefcase"></i></div>';
        if (!empty($job['company_logo'])) {
            $logoHtml = '<img src="' . htmlspecialchars($job['company_logo']) . '" alt="Job Photo" style="width: 60px; height: 60px; object-fit: cover; border-radius: 50%; border: 2px solid #f1f5f9;">';
        }

        $html .= '
        <div class="job-card ' . $featuredClass . '">
            ' . $featuredRibbon . '
            <div class="job-card-logo">
                ' . $logoHtml . '
            </div>
            <div class="job-card-main">
                <div class="job-card-header">
                    <h3 class="job-card-title"><a href="' . $link . '">' . htmlspecialchars($job['title']) . '</a></h3>
                    <div class="job-card-badges">
                        ' . $activeBadge . '
                        ' . $urgentPill . '
                        ' . $verifiedBadge . '
                    </div>
                </div>
                <div class="job-card-meta">
                    <span class="meta-item"><i class="fas fa-building"></i> B&H Employment Client</span>
                    <span class="meta-item"><i class="fas fa-calendar"></i> Posted ' . $datePosted . '</span>
                </div>
                <p class="job-card-snippet">' . $descSnippet . '</p>
            </div>
            <div class="job-card-action">
                <div class="job-card-salary">' . $salary . '</div>
                <a href="' . $link . '" class="btn-apply" ' . (!$isActive ? 'style="background:#cbd5e1; border-color:#cbd5e1; cursor:not-allowed;" onclick="return false;"' : '') . '>' . $btnText . '</a>
            </div>
        </div>';
    }
    return $html;
}

// ==========================================
// AJAX HANDLER
// ==========================================
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    $search_type = $_GET['search_type'] ?? 'jobs';
    
    if ($search_type === 'candidates') {
        // --- CANDIDATE SEARCH LOGIC ---
        $query = "SELECT * FROM candidates WHERE is_active = 1 AND approval_status = 'approved'";
        $params = [];
        
        // Filter by Keyword
        if (!empty($_GET['keyword'])) {
            $query .= " AND (first_name LIKE ? OR last_name LIKE ? OR bio LIKE ? OR languages LIKE ?)";
            $keyword = '%' . $_GET['keyword'] . '%';
            array_push($params, $keyword, $keyword, $keyword, $keyword);
        }
        
        // Filter by Roles (Matches role name against the candidate's bio/roles_tags)
        if (!empty($_GET['roles'])) {
            $rolePlaceholders = str_repeat('?,', count($_GET['roles']) - 1) . '?';
            $roleStmt = $pdo->prepare("SELECT name FROM job_roles WHERE id IN ($rolePlaceholders)");
            $roleStmt->execute($_GET['roles']);
            $selectedRoleNames = $roleStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($selectedRoleNames)) {
                $roleConditions = [];
                foreach ($selectedRoleNames as $roleName) {
                    $roleConditions[] = "bio LIKE ?";
                    $params[] = '%' . $roleName . '%';
                }
                $query .= " AND (" . implode(" OR ", $roleConditions) . ")";
            }
        }
        
        $query .= " ORDER BY created_at DESC";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $html = '';
            if (empty($candidates)) {
                $html = '<div class="no-jobs-found" style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px; border: 1px solid #eee;"><i class="fas fa-users fa-3x" style="color: #ccc; margin-bottom: 20px;"></i><h3 style="color: #2C3E50; margin-bottom: 10px;">No candidates found</h3></div>';
            } else {
                foreach ($candidates as $cand) {
                    $photoHtml = '<div class="logo-circle" style="width: 70px; height: 70px; font-size: 28px;"><i class="fas fa-user"></i></div>';
                    if (!empty($cand['photo_path'])) {
                        $photoHtml = '<img src="' . htmlspecialchars($cand['photo_path']) . '" style="width: 70px; height: 70px; object-fit: cover; border-radius: 50%; border: 2px solid #f1f5f9;">';
                    }
                    $name = htmlspecialchars($cand['first_name'] . ' ' . substr($cand['last_name'], 0, 1) . '.'); // Privacy: last initial only
                    
                    $fullBio = nl2br(htmlspecialchars(strip_tags($cand['bio'])));
                    $bioSnippetText = strip_tags($cand['bio']);
                    $bioSnippet = htmlspecialchars(strlen($bioSnippetText) > 250 ? substr($bioSnippetText, 0, 250) . '...' : $bioSnippetText);
                    
                    $location = htmlspecialchars($cand['location'] ?? 'Location N/A');
                    $salary = htmlspecialchars($cand['salary_expectation'] ?? 'Open');
                    
                    $extraDetails = '<div class="candidate-details-full" style="display:none; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 15px;">';
                    $extraDetails .= '<strong>About Candidate:</strong><br>' . $fullBio . '<br><br>';
                    
                    if (!empty($cand['languages'])) {
                        $extraDetails .= '<strong>Languages:</strong> ' . htmlspecialchars($cand['languages']) . '<br>';
                    }
                    if (!empty($cand['roles_tags'])) {
                        $extraDetails .= '<strong>Skills/Roles:</strong> ' . htmlspecialchars($cand['roles_tags']) . '<br>';
                    }
                    if (!empty($cand['driving_license'])) {
                        $extraDetails .= '<strong>Driving License:</strong> ' . htmlspecialchars($cand['driving_license']) . '<br>';
                    }
                    
                    if (!empty($cand['resume_path'])) {
                        $extraDetails .= '<br><strong>Resume:</strong> <a href="' . htmlspecialchars($cand['resume_path']) . '" target="_blank" style="color: #0066cc; text-decoration: underline;"><i class="fas fa-file-alt"></i> View/Download Resume</a><br>';
                    }
                    
                    // Allow contacting if the user is logged in
                    if (isset($is_logged_in) && $is_logged_in) {
                        $extraDetails .= '<br><a href="mailto:' . htmlspecialchars($cand['email']) . '" style="display:inline-block; padding: 8px 20px; background: #0A192F; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; margin-top: 10px;">Contact ' . $name . '</a>';
                    } else {
                        $extraDetails .= '<br><a href="login.php" style="display:inline-block; padding: 8px 20px; background: #0A192F; color: white; border-radius: 4px; text-decoration: none; font-weight: bold; margin-top: 10px;">Log in to Contact</a>';
                    }
                    
                    $extraDetails .= '</div>';
                    
                    $bioDisplay = '<span class="bio-snippet">' . $bioSnippet . '</span>' .
                                  $extraDetails .
                                  ' <a href="#" onclick="const p = this.parentElement; p.querySelector(\'.bio-snippet\').style.display=\'none\'; p.querySelector(\'.candidate-details-full\').style.display=\'block\'; this.style.display=\'none\'; return false;" style="color: #d97706; text-decoration: none; font-weight: 600; display: inline-block; margin-top: 5px;">Read more</a>';
                    
                    $html .= '
                    <div class="job-card" style="padding: 25px; align-items: flex-start;">
                        <div class="job-card-logo" style="margin-right: 20px;">' . $photoHtml . '</div>
                        <div class="job-card-main" style="padding-right: 0; width: 100%;">
                            <div class="job-card-header" style="margin-bottom: 5px;">
                                <h3 class="job-card-title" style="font-size: 18px; margin-right: 10px;">' . $name . '</h3>
                            </div>
                            <div style="font-size: 13px; color: #4b5563; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
                                ' . $location . ' <span style="color: #10b981; font-weight: 500;"><i class="fas fa-circle" style="font-size: 8px; vertical-align: middle;"></i> Active now</span>
                            </div>
                            <div style="font-size: 12px; color: #4b5563; font-weight: 600; margin-bottom: 15px;">
                                <span><i class="fas fa-dollar-sign" style="color: #9ca3af; margin-right: 4px;"></i> ' . $salary . '</span>
                            </div>
                            <div class="job-card-snippet" style="color: #374151; line-height: 1.6;">
                                ' . $bioDisplay . '
                            </div>
                        </div>
                    </div>';
                }
            }
            echo json_encode(['success' => true, 'html' => $html, 'count' => count($candidates)]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A database error occurred while fetching candidates.']);
        }
        exit;
    } else {
        // --- JOB SEARCH LOGIC ---
        $query = "SELECT DISTINCT j.* FROM jobs j ";
        $params = [];
        $where = ["j.approval_status = 'approved'"];
        
        if (!empty($_GET['roles'])) {
            $query .= "INNER JOIN job_listings_roles jlr ON j.id = jlr.job_id ";
            $placeholders = str_repeat('?,', count($_GET['roles']) - 1) . '?';
            $where[] = "jlr.role_id IN ($placeholders)";
            foreach ($_GET['roles'] as $roleId) { $params[] = $roleId; }
        }
        
        if (!empty($_GET['keyword'])) {
            $where[] = "(j.title LIKE ? OR j.description LIKE ?)";
            $keyword = '%' . $_GET['keyword'] . '%';
            $params[] = $keyword; $params[] = $keyword;
        }
        
        if (!empty($_GET['background_checked']) && $_GET['background_checked'] == 'true') { $where[] = "j.background_checked = 1"; }
        if (!empty($_GET['live_in_out'])) { $where[] = "j.live_in_out = ?"; $params[] = $_GET['live_in_out']; }
        if (!empty($_GET['food_cert_required']) && $_GET['food_cert_required'] == 'true') { $where[] = "j.food_cert_required = 1"; }
        
        if (count($where) > 0) { $query .= " WHERE " . implode(" AND ", $where); }
        
        $sort = $_GET['sort'] ?? 'newest';
        if ($sort === 'salary') {
            $query .= " ORDER BY j.is_featured DESC, j.salary_max DESC, j.created_at DESC";
        } else {
            $query .= " ORDER BY j.is_featured DESC, j.created_at DESC";
        }
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $html = renderJobCards($jobs, $is_logged_in);
            echo json_encode(['success' => true, 'html' => $html, 'count' => count($jobs)]);
        } catch (PDOException $e) {
            error_log($e->getMessage());
            echo json_encode(['success' => false, 'error' => 'A database error occurred while fetching jobs.']);
        }
        exit;
    }
}

// ==========================================
// NORMAL PAGE LOAD
// ==========================================
try {
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE approval_status = 'approved' ORDER BY is_featured DESC, created_at DESC" . (!$is_logged_in ? " LIMIT 5" : ""));
    $stmt->execute();
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Try to fetch categories and roles dynamically
    $catStmt = $pdo->query("SELECT id, name FROM job_categories ORDER BY name");
    $categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $rolesStmt = $pdo->query("SELECT id, category_id, name FROM job_roles ORDER BY name");
    $allRoles = $rolesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groupedRoles = [];
    foreach ($categories as $cat) {
        $groupedRoles[$cat['id']] = ['name' => $cat['name'], 'roles' => []];
    }
    foreach ($allRoles as $role) {
        if (isset($groupedRoles[$role['category_id']])) {
            $groupedRoles[$role['category_id']]['roles'][] = $role;
        }
    }
} catch (PDOException $e) {
    // DB tables might not exist yet, set static defaults so UI doesn\'t break
    $groupedRoles = [];
}

// Static Fallback for Grouped Roles if DB tables are empty/missing
if (empty($groupedRoles)) {
    $groupedRoles = [
        1 => ['name' => 'Housework', 'roles' => [['id' => 1, 'name' => 'Nanny'], ['id' => 2, 'name' => 'Housekeeper'], ['id' => 3, 'name' => 'House Cook']]],
        2 => ['name' => 'Restaurant & Kitchen', 'roles' => [['id' => 4, 'name' => 'Sushi Chef'], ['id' => 5, 'name' => 'Tandoori Chef'], ['id' => 6, 'name' => 'Kitchen Helper']]],
        3 => ['name' => 'Store & Retail', 'roles' => [['id' => 7, 'name' => 'Cashier'], ['id' => 8, 'name' => 'Stock Guy']]]
    ];
}

$favicon_path = (isset($site_settings) && !empty($site_settings['favicon'])) ? '/' . ltrim($site_settings['favicon'], '/') : '/favicon.ico';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Job Search - B&H Employment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="icon" href="<?php echo $favicon_path; ?>" type="image/x-icon">
    
    <style>
        /* High-End Design System */
        :root {
            --navy: #0A192F;
            --charcoal: #2C3E50;
            --paper: #FAF9F6;
            --bronze: #C5A059;
            --light-grey: #e2e8f0;
            --bg-color: #f8fafc;
        }
        
        body { background-color: var(--bg-color); }

        .portal-header {
            background: var(--navy);
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        .portal-header h1 { color: white; margin-bottom: 10px; font-size: 2.5rem; }
        .portal-header p { color: #8892b0; font-size: 1.1rem; }

        .portal-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
            align-items: start;
        }

        /* Sidebar Styling */
        .sidebar-filter {
            background: var(--paper);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--light-grey);
            position: sticky;
            top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        }

        .filter-group { margin-bottom: 25px; }
        .filter-group h4 {
            color: var(--navy);
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--bronze);
            padding-bottom: 5px;
            display: inline-block;
        }

        .search-box {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
            background: #fff;
        }
        .search-box:focus {
            outline: none;
            border-color: var(--bronze);
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            cursor: pointer;
            color: var(--charcoal);
            font-size: 14px;
        }
        .checkbox-label input {
            margin-right: 10px;
            width: 16px;
            height: 16px;
            accent-color: var(--bronze);
        }

        .accordion-btn {
            background: #f1f5f9;
            border: 1px solid var(--light-grey);
            width: 100%;
            text-align: left;
            font-weight: bold;
            color: var(--navy);
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 4px;
            margin-bottom: 8px;
            transition: background 0.2s;
        }
        .accordion-btn:hover { background: #e2e8f0; }
        .accordion-content {
            padding: 10px 15px;
            display: none;
        }
        .accordion-content.active { display: block; }

        /* Main Content */
        .jobs-results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--light-grey);
        }
        .jobs-count {
            font-size: 18px;
            color: var(--charcoal);
        }
        .jobs-count strong { color: var(--navy); font-size: 20px; }
        
        .sort-box select {
            padding: 10px 18px;
            border: 1px solid #e2e8f0;
            border-radius: 50px;
            background: white;
            color: #334155;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
            outline: none;
            transition: border-color 0.2s;
        }
        .sort-box select:focus {
            border-color: #0066cc;
        }

        /* Job Cards (Horizontal) */
        .jobs-list-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .job-card {
            display: flex;
            background: #ffffff;
            border: 1px solid #f1f5f9;
            border-radius: 12px;
            padding: 25px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            transition: all 0.3s ease;
        }
        .job-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transform: translateY(-2px);
            border-color: #e2e8f0;
        }

        .featured-job {
            border: 2px solid var(--bronze);
            background: #fffcf8;
        }
        
        .featured-ribbon {
            position: absolute;
            top: 0;
            left: 0;
            background: var(--bronze);
            color: white;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: bold;
            border-radius: 6px 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .job-card-logo { flex-shrink: 0; margin-right: 25px; }
        .logo-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--navy);
            border: 1px solid var(--light-grey);
        }

        .job-card-main { flex-grow: 1; padding-right: 20px; }
        
        .job-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .job-card-title { margin: 0; font-size: 20px; }
        .job-card-title a { color: var(--navy); text-decoration: none; }
        .job-card-title a:hover { color: var(--bronze); }

        .job-card-badges { display: flex; gap: 8px; }
        .badge {
            font-size: 11px;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .verified-badge { background: #e6f4ea; color: #137333; border: 1px solid #ceead6; }
        .urgent-badge { background: #fce8e6; color: #c5221f; border: 1px solid #f8d8d6; }

        .job-card-meta {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            gap: 18px;
        }
        .job-card-meta i { margin-right: 4px; color: var(--bronze); }

        .job-card-snippet {
            font-size: 14px;
            color: var(--charcoal);
            line-height: 1.6;
            margin: 0;
        }

        .job-card-action {
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-end;
            min-width: 150px;
            border-left: 1px solid var(--light-grey);
            padding-left: 25px;
        }

        .job-card-salary {
            font-size: 18px;
            font-weight: bold;
            color: var(--navy);
            margin-bottom: 15px;
            text-align: right;
        }

        .btn-apply {
            background: linear-gradient(135deg, #0066cc, #0052a3);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 102, 204, 0.3);
        }
        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 102, 204, 0.4);
        }

        /* Mobile Responsive */
        .mobile-search-toggle {
            display: none;
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid var(--light-grey);
            margin-bottom: 15px;
            gap: 20px;
            justify-content: center;
        }
        .mobile-search-toggle label {
            margin-bottom: 0;
            font-weight: 600;
        }
        
        .mobile-filter-btn {
            display: none;
            width: 100%;
            padding: 12px;
            background: var(--navy);
            color: white;
            border: none;
            font-weight: bold;
            border-radius: 4px;
            margin-bottom: 20px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        @media (max-width: 768px) {
            .portal-container { grid-template-columns: 1fr; }
            .mobile-search-toggle { display: flex; }
            .search-type-group { display: none; }
            .sidebar-filter {
                position: fixed;
                top: 0; left: -100%;
                width: 80%; max-width: 300px;
                height: 100%;
                z-index: 1000;
                overflow-y: auto;
                border-radius: 0;
                transition: left 0.3s ease;
                border-right: 1px solid #ccc;
            }
            .sidebar-filter.show { left: 0; }
            .overlay.show { display: block; }
            .mobile-filter-btn { display: block; }
            .job-card { flex-direction: column; padding: 20px; }
            .job-card-action {
                border-left: none;
                border-top: 1px solid var(--light-grey);
                padding-left: 0;
                padding-top: 20px;
                margin-top: 20px;
                align-items: flex-start;
            }
            .job-card-salary { text-align: left; }
            
            .post-need-dropdown {
                position: relative;
                margin-bottom: 20px;
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .dropdown-content {
                right: auto;
                width: 100%;
                text-align: center;
            }
            .dropdown-content a {
                justify-content: center;
            }
        }

        /* Post Your Need Dropdown */
        .post-need-dropdown {
            position: relative;
            display: inline-block;
            z-index: 100;
        }
        .btn-post-need {
            background: linear-gradient(135deg, #e63946, #d90429);
            color: white;
            padding: 12px 24px;
            font-size: 15px;
            border: 2px solid #ef4444;
            cursor: pointer;
            border-radius: 30px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(217, 4, 41, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(217, 4, 41, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(217, 4, 41, 0); }
            100% { box-shadow: 0 0 0 0 rgba(217, 4, 41, 0); }
        }
        .btn-post-need i {
            font-size: 13px;
        }
        .btn-post-need:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(217, 4, 41, 0.6);
            animation: none;
        }
        .dropdown-content {
            display: none;
            position: absolute;
            top: 110%;
            right: 0;
            background-color: #ffffff;
            min-width: 220px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.15);
            z-index: 101;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.05);
            text-align: left;
            animation: dropdownFadeIn 0.2s ease-out forwards;
            transform-origin: top right;
        }
        @keyframes dropdownFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .dropdown-content.show {
            display: block;
        }
        .dropdown-content a {
            color: var(--charcoal);
            padding: 14px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 15px;
            border-bottom: 1px solid #f1f5f9;
        }
        .dropdown-content a:last-child {
            border-bottom: none;
        }
        .dropdown-content a:hover {
            background-color: #f8fafc;
            color: #d90429;
            padding-left: 25px;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="portal-header">
        <div class="container" style="padding: 40px 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 30px;">
                <div style="flex: 1; min-width: 300px; text-align: left;">
                    <h1 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 15px; letter-spacing: -0.5px; line-height: 1.2;">Find Your Next Professional Opportunity</h1>
                    <p style="font-size: 1.15rem; color: #cbd5e1; margin-bottom: 0; font-weight: 300;">Exclusive placements in Households, Kitchens, and Retail</p>
                </div>
                
                <div class="post-need-dropdown" style="position: relative; flex-shrink: 0;">
                    <button class="btn-post-need" onclick="togglePostNeed()" style="padding: 14px 32px; font-size: 16px; border-radius: 50px; box-shadow: 0 8px 25px rgba(217, 4, 41, 0.5);">
                        Post your need <i class="fas fa-chevron-down" style="margin-left: 5px;"></i>
                    </button>
                    <div id="postNeedDropdown" class="dropdown-content" style="top: calc(100% + 10px); right: 0;">
                        <a href="<?php echo $base_path; ?>submit-candidate"><i class="fas fa-user-tie"></i> Find job</a>
                        <a href="<?php echo $base_path; ?>submit-job"><i class="fas fa-briefcase"></i> Find workers</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="portal-container">
        
        <?php
        $requestUri = $_SERVER['REQUEST_URI'];
        $isCandidatesUrl = (strpos($requestUri, '/candidate') !== false);
        $urlRoles = [];
        if (preg_match('/\/jobs\/([a-zA-Z0-9,-]+)/', $requestUri, $matches)) {
            $urlRoles = explode(',', strtolower($matches[1]));
        }
        ?>

        <!-- Mobile Toggle (Visible on Mobile Only) -->
        <div class="mobile-search-toggle">
            <label class="checkbox-label">
                <input type="radio" name="mobile_search_type" value="candidates" id="mobile_search_candidates" style="accent-color: var(--bronze);" <?php echo $isCandidatesUrl ? 'checked' : ''; ?>> Find Candidates
            </label>
            <label class="checkbox-label">
                <input type="radio" name="mobile_search_type" value="jobs" id="mobile_search_jobs" <?php echo !$isCandidatesUrl ? 'checked' : ''; ?> style="accent-color: var(--bronze);"> Get Jobs
            </label>
        </div>

        <button class="mobile-filter-btn" id="mobileFilterBtn"><i class="fas fa-filter"></i> Show Filters</button>
        <div class="overlay" id="sidebarOverlay"></div>

        <!-- Left Sidebar: Filters -->
        <aside class="sidebar-filter" id="sidebarFilter">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h4 style="margin: 0; color: var(--charcoal); font-size: 15px; font-weight: 600;">Search for:</h4>
                <button type="button" id="resetFilters" style="background:none; border:none; color: var(--bronze); font-size: 13px; font-weight: 600; cursor: pointer; padding: 0;">Reset Filters</button>
            </div>
            
            <form id="filterForm">
                <div class="filter-group search-type-group" style="margin-bottom: 20px;">
                    <label class="checkbox-label" style="margin-bottom: 8px;">
                        <input type="radio" name="search_type" value="candidates" id="search_candidates" class="filter-input" style="accent-color: var(--bronze);" <?php echo $isCandidatesUrl ? 'checked' : ''; ?>> Find Candidates
                    </label>
                    <label class="checkbox-label">
                        <input type="radio" name="search_type" value="jobs" id="search_jobs" class="filter-input" <?php echo !$isCandidatesUrl ? 'checked' : ''; ?> style="accent-color: var(--bronze);"> Get Jobs
                    </label>
                </div>
                
                <!-- Search -->
                <div class="filter-group">
                    <input type="text" name="keyword" class="search-box filter-input" placeholder="Search by Keyword">
                </div>

                <!-- Work Requirements -->
                <div class="filter-group">
                    <h4>Requirements</h4>
                    <div style="margin-top: 10px;">
                        <select name="live_in_out" class="search-box filter-input">
                            <option value="">Any Arrangement</option>
                            <option value="live-in">Live-in</option>
                            <option value="live-out">Live-out</option>
                        </select>
                    </div>
                </div>

                <!-- Roles by Category -->
                <div class="filter-group">
                    <h4>Job Roles</h4>
                    <?php foreach ($groupedRoles as $catId => $catData): ?>
                        <div class="accordion-item">
                            <button type="button" class="accordion-btn">
                                <?php echo htmlspecialchars($catData['name']); ?>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="accordion-content">
                                <?php foreach ($catData['roles'] as $role): ?>
                                    <?php 
                                        $roleSlug = strtolower(str_replace(' ', '-', trim($role['name'])));
                                        $isChecked = in_array($roleSlug, $urlRoles) ? 'checked' : '';
                                    ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="roles[]" value="<?php echo $role['id']; ?>" class="filter-input" <?php echo $isChecked; ?>>
                                        <?php echo htmlspecialchars($role['name']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            </form>
        </aside>

        <!-- Right Content: Job Listings -->
        <main class="jobs-results">
            <div class="jobs-results-header">
                <div class="jobs-count">
                    <strong id="jobCountDisplay"><?php echo count($jobs); ?></strong> <span id="jobCountText">Jobs Found</span>
                </div>
                <div class="sort-box">
                    <select id="sortSelect" class="filter-input">
                        <option value="newest">Sort by: Newest</option>
                        <option value="salary">Sort by: Highest Salary</option>
                    </select>
                </div>
            </div>

            <div class="jobs-list-container" id="jobsContainer">
                <?php echo renderJobCards($jobs, $is_logged_in); ?>
            </div>
            
            <?php if (!$is_logged_in): ?>
                <div style="text-align: center; margin-top: 40px; padding: 30px; background: white; border-radius: 8px; border: 1px solid var(--light-grey);">
                    <h3 style="margin-bottom: 15px; color: var(--charcoal);">Want to see more opportunities?</h3>
                    <p style="color: #666; margin-bottom: 20px;">Create an account to view all listings and apply directly.</p>
                    <a href="login.php" class="btn-apply" style="display: inline-block; width: auto; padding: 12px 30px;">Log in to See More Jobs</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- JavaScript for AJAX and UI interaction -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            // Accordion Logic
            const accordions = document.querySelectorAll('.accordion-btn');
            accordions.forEach(btn => {
                btn.addEventListener('click', function() {
                    this.classList.toggle('active');
                    const content = this.nextElementSibling;
                    const icon = this.querySelector('i');
                    if (content.classList.contains('active')) {
                        content.classList.remove('active');
                        icon.classList.replace('fa-chevron-up', 'fa-chevron-down');
                    } else {
                        content.classList.add('active');
                        icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    }
                });
            });

            // Mobile Sidebar Logic
            const mobileBtn = document.getElementById('mobileFilterBtn');
            const sidebar = document.getElementById('sidebarFilter');
            const overlay = document.getElementById('sidebarOverlay');

            mobileBtn.addEventListener('click', () => {
                sidebar.classList.add('show');
                overlay.classList.add('show');
            });
            overlay.addEventListener('click', () => {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });

            // AJAX Filtering Logic
            const filterForm = document.getElementById('filterForm');
            const sortSelect = document.getElementById('sortSelect');
            const jobsContainer = document.getElementById('jobsContainer');
            const jobCountDisplay = document.getElementById('jobCountDisplay');
            let debounceTimer;

            function fetchJobs() {
                // Show loading state
                jobsContainer.style.opacity = '0.5';
                
                const formData = new FormData(filterForm);
                const params = new URLSearchParams(formData);
                params.append('sort', sortSelect.value);
                
                // Update URL based on filters
                let newPath = window.location.pathname;
                
                // Reliably get search type directly from the DOM instead of FormData
                let searchType = 'jobs';
                const searchTypeRadio = document.querySelector('input[name="search_type"]:checked');
                if (searchTypeRadio) {
                    searchType = searchTypeRadio.value;
                }
                
                if (searchType === 'candidates') {
                    newPath = '/candidate';
                } else {
                    const roleCheckboxes = filterForm.querySelectorAll('input[name="roles[]"]:checked');
                    if (roleCheckboxes.length > 0) {
                        const selectedRoles = Array.from(roleCheckboxes).map(cb => {
                            return cb.parentElement.textContent.trim().toLowerCase().replace(/\s+/g, '-');
                        });
                        newPath = '/jobs/' + selectedRoles.join(',');
                    } else {
                        newPath = '/jobs';
                    }
                }
                
                if (window.history && window.history.pushState) {
                    window.history.pushState({}, '', newPath);
                }

                params.append('ajax', '1');

                fetch('jobs.php?' + params.toString())
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            jobsContainer.innerHTML = data.html;
                            jobCountDisplay.textContent = data.count;
                            
                            const isCandidateSearch = document.getElementById('search_candidates').checked;
                            document.getElementById('jobCountText').textContent = isCandidateSearch ? 'Candidates Found' : 'Jobs Found';
                        } else {
                            console.error('Filter Error:', data.error);
                            // Only log to console, don't break UI for user
                        }
                        jobsContainer.style.opacity = '1';
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                        jobsContainer.style.opacity = '1';
                    });
            }

            // Attach event listeners to all inputs in the form
            const inputs = filterForm.querySelectorAll('.filter-input');
            inputs.forEach(input => {
                if (input.type === 'text') {
                    input.addEventListener('input', () => {
                        clearTimeout(debounceTimer);
                        debounceTimer = setTimeout(fetchJobs, 500); // 500ms debounce
                    });
                } else {
                    input.addEventListener('change', function() {
                        // Sync back to mobile toggle if main radio changes
                        if (this.name === 'search_type') {
                            if (this.value === 'candidates') {
                                document.getElementById('mobile_search_candidates').checked = true;
                            } else {
                                document.getElementById('mobile_search_jobs').checked = true;
                            }
                        }
                        fetchJobs();
                    });
                }
            });

            // Sync mobile toggle to main form
            const mobileSearchRadios = document.querySelectorAll('input[name="mobile_search_type"]');
            mobileSearchRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'candidates') {
                        document.getElementById('search_candidates').checked = true;
                    } else {
                        document.getElementById('search_jobs').checked = true;
                    }
                    fetchJobs();
                });
            });

            // Attach event listener to Reset Filters
            document.getElementById('resetFilters').addEventListener('click', () => {
                filterForm.reset();
                document.getElementById('search_jobs').checked = true;
                document.getElementById('mobile_search_jobs').checked = true;
                fetchJobs();
            });

            // Attach event listener to Sort dropdown
            sortSelect.addEventListener('change', fetchJobs);
            
            // Toggle Post Need Dropdown
            window.togglePostNeed = function() {
                document.getElementById("postNeedDropdown").classList.toggle("show");
            }
            
            // Close dropdown if clicked outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.post-need-dropdown')) {
                    const dropdown = document.getElementById("postNeedDropdown");
                    if (dropdown && dropdown.classList.contains('show')) {
                        dropdown.classList.remove('show');
                    }
                }
            });
            
            // Initial load trigger based on URL
            const urlPath = window.location.pathname;
            if (urlPath.includes('/candidate') || document.querySelectorAll('input[name="roles[]"]:checked').length > 0) {
                fetchJobs();
            }
        });
    </script>
</body>
</html>