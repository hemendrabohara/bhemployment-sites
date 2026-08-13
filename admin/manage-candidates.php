<?php
// Adjust this path if your config file is located elsewhere (e.g., '../config.php')
require_once '../config.php';

// Simple authentication check - make sure you add your actual admin check here!
// if (!isset($_SESSION['admin_logged_in'])) { redirect('login.php'); }

$success_msg = '';
$error_msg = '';

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['candidate_id']) && isset($_POST['action'])) {
        $candidate_id = (int)$_POST['candidate_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE candidates SET approval_status = 'approved' WHERE id = ?");
                $stmt->execute([$candidate_id]);
                $success_msg = "Candidate #$candidate_id has been approved and is now live on the site!";
            } elseif ($action === 'reject') {
                // By default, we delete rejected candidates to keep the database clean.
                // Change "DELETE FROM" to "UPDATE candidates SET approval_status = 'rejected' WHERE" if you want to keep records.
                $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
                $stmt->execute([$candidate_id]);
                $success_msg = "Candidate #$candidate_id has been rejected and removed.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}

// Fetch all Candidates
try {
    $stmt = $pdo->query("SELECT * FROM candidates ORDER BY created_at DESC");
    $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Error fetching candidates: " . $e->getMessage();
    $candidates = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Candidates - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f7f6; margin: 0; padding: 0; display: flex; }
        .main-content { padding: 30px; flex-grow: 1; margin-left: 250px; /* Assumes a 250px sidebar */ }
        
        h1 { color: #2C3E50; margin-top: 0; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .admin-table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .admin-table th, .admin-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .admin-table th { background-color: #0A192F; color: white; font-weight: 500; }
        .admin-table tr:hover { background-color: #f8fafc; }
        
        .btn { padding: 8px 12px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 13px; text-decoration: none; display: inline-block; }
        .btn-approve { background-color: #10b981; color: white; }
        .btn-approve:hover { background-color: #059669; }
        .btn-reject { background-color: #ef4444; color: white; }
        .btn-reject:hover { background-color: #dc2626; }
        .btn-view { background-color: #3b82f6; color: white; }
        
        .action-buttons { display: flex; gap: 8px; }
        
        /* Mobile responsive */
        @media (max-width: 768px) {
            .main-content { margin-left: 0; padding: 15px; }
            .admin-table { display: block; overflow-x: auto; }
        }
    </style>
</head>
<body>

    <!-- Include your Admin Sidebar here -->
    <?php 
    if (file_exists('sidebar.php')) {
        include 'sidebar.php'; 
    } else {
        // Fallback placeholder if sidebar isn't found
        echo '<div style="width: 250px; background: #0A192F; color: white; padding: 20px; position: fixed; height: 100vh;"><h3>Admin Panel</h3><ul><li><a href="manage-candidates.php" style="color:white;">Manage Candidates</a></li></ul></div>';
    }
    ?>

    <div class="main-content">
        <h1>Manage All Candidates</h1>
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success"><?php echo $success_msg; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger"><?php echo $error_msg; ?></div>
        <?php endif; ?>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Roles Sought</th>
                    <th>Salary</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($candidates)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 30px; color: #666;">
                            No candidates found.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($candidates as $cand): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($cand['id']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($cand['first_name'] . ' ' . $cand['last_name']); ?></strong><br>
                                <span style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($cand['email']); ?></span>
                            </td>
                            <td>
                                <?php echo htmlspecialchars(substr(strip_tags($cand['bio']), 0, 50)) . '...'; ?>
                            </td>
                            <td><?php echo htmlspecialchars($cand['salary_expectation']); ?></td>
                            <td>
                                <?php if ($cand['approval_status'] === 'approved'): ?>
                                    <span style="color: #10b981; font-weight: bold;">Active</span>
                                <?php else: ?>
                                    <span style="color: #f59e0b; font-weight: bold;">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <?php if ($cand['approval_status'] === 'pending'): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to approve this candidate?');">
                                            <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-approve"><i class="fas fa-check"></i> Approve</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to REJECT and DELETE this candidate?');">
                                        <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-reject"><i class="fas fa-trash"></i> <?php echo $cand['approval_status'] === 'approved' ? 'Remove Post' : 'Reject'; ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</body>
</html>
