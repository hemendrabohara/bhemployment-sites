<?php
require_once 'config.php';

// Format date
function formatDate($date) {
    return date('M j, Y', strtotime($date));
}

// Get all candidates from database - show only approved candidates
$candidates = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE approval_status = 'approved' ORDER BY created_at DESC");
    $stmt->execute();
    $candidates = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching candidates: " . $e->getMessage());
}
?>

<?php include 'includes/header.php'; ?>

    <section class="page-title">
        <div class="container">
            <h1>Browse Candidates</h1>
            <p>Find the perfect worker for your household or business</p>
        </div>
    </section>

    <section style="padding: 60px 0; background-color: #f8fafc;">
        <div class="container">
            <div class="jobs-header" style="margin-bottom: 30px;">
                <div class="jobs-count">
                    <strong><?php echo count($candidates); ?></strong> candidates found
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px;">
                <?php if (empty($candidates)): ?>
                    <div class="no-jobs-found" style="grid-column: 1 / -1; text-align: center; padding: 60px; background: white; border-radius: 8px;">
                        <i class="fas fa-users fa-3x" style="color: #cbd5e1; margin-bottom: 20px;"></i>
                        <h3 style="color: #334155;">No candidates found</h3>
                        <p style="color: #64748b;">Check back later for new applicant profiles.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($candidates as $candidate): ?>
                    <div style="background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; flex-direction: column; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                            <div>
                                <h3 style="font-size: 22px; color: #334155; font-weight: 600; margin: 0 0 5px 0;"><?php echo htmlspecialchars($candidate['first_name'] . ' ' . substr($candidate['last_name'], 0, 1) . '.'); ?></h3>
                                <p style="color: #64748b; font-size: 14px; margin: 0;"><?php echo htmlspecialchars($candidate['location'] ?? 'Location N/A'); ?></p>
                            </div>
                            <?php if (!empty($candidate['photo_path'])): ?>
                                <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden;">
                                    <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>" alt="Profile" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; border-radius: 50%; background-color: #cbd5e1; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px;">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div style="margin-bottom: 25px; flex-grow: 1;">
                            <p style="color: #475569; font-size: 14px; font-weight: 600; margin: 0 0 8px 0;">Reasons to hire me</p>
                            <p style="color: #475569; font-size: 14px; line-height: 1.6; margin: 0;">
                                <?php 
                                    $bio = strip_tags($candidate['bio']);
                                    echo htmlspecialchars(strlen($bio) > 150 ? substr($bio, 0, 150) . '...' : $bio); 
                                ?>
                            </p>
                        </div>
                        
                        <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
                        
                        <div style="margin-bottom: 20px;">
                            <p style="color: #64748b; font-size: 12px; font-weight: 600; margin: 0 0 5px 0;">EXPECTED PAY</p>
                            <p style="color: #334155; font-size: 18px; font-weight: 700; margin: 0;">
                                <?php 
                                    $salary = $candidate['salary_expectation'] ?? 'Negotiable'; 
                                    if (is_numeric($salary)) {
                                        echo '$' . number_format($salary, 2) . '/hour';
                                    } else {
                                        echo htmlspecialchars($salary);
                                    }
                                ?>
                            </p>
                        </div>
                        
                        <a href="#" onclick="alert('Please log in as an employer to view full candidate details and contact information.'); return false;" style="display: block; padding: 12px 20px; border: 1px solid #0066cc; color: white; background-color: #0066cc; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 4px; text-align: center; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#0052a3';" onmouseout="this.style.backgroundColor='#0066cc';">Contact Candidate</a>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <script src="js/script.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>
