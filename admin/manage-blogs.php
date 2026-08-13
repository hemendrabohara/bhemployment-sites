<?php
require_once '../config.php';
require_once '../includes/blog-functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    flashMessage("You must be logged in as an admin to access this page", "danger");
    redirect('../login.php');
}

// Handle blog post actions (delete, change status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['post_id'])) {
    $post_id = (int)$_POST['post_id'];
    $action = $_POST['action'];
    
    try {
        // Verify that the post exists
        $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE id = :post_id");
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            flashMessage("Blog post not found", "danger");
            redirect('manage-blogs.php');
        }
        
        switch ($action) {
            case 'delete':
                // Delete the post
                $stmt = $pdo->prepare("DELETE FROM blog_posts WHERE id = :post_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                flashMessage("Blog post deleted successfully", "success");
                break;
                
            case 'publish':
                // Publish the post
                $stmt = $pdo->prepare("UPDATE blog_posts SET status = 'published' WHERE id = :post_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                flashMessage("Blog post published successfully", "success");
                break;
                
            case 'draft':
                // Set to draft
                $stmt = $pdo->prepare("UPDATE blog_posts SET status = 'draft' WHERE id = :post_id");
                $stmt->bindParam(':post_id', $post_id);
                $stmt->execute();
                flashMessage("Blog post set to draft", "success");
                break;
                
            default:
                flashMessage("Invalid action", "danger");
        }
    } catch (PDOException $e) {
        error_log("Error performing blog post action: " . $e->getMessage());
        flashMessage("An error occurred. Please try again.", "danger");
    }
    
    redirect('manage-blogs.php');
}

// Get all blog posts with pagination
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$blog_posts_data = getAllBlogPosts($current_page, $per_page, false); // Get all posts, not just published
$blog_posts = $blog_posts_data['posts'];
$total_pages = $blog_posts_data['total_pages'];

// Get site settings for favicon
$favicon_path = '';
if (isset($site_settings) && !empty($site_settings['favicon'])) {
    $favicon_path = '/' . ltrim($site_settings['favicon'], '/');
} else {
    $favicon_path = '/favicon.ico';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blog Posts - B&H Employment & Consultancy Inc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/updated-styles.css">
    <?php if (!empty($favicon_path)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
</head>
<body>
<?php include '../includes/header.php'; ?> 
<section class="page-title">
        <div class="container">
            <h1>Manage Blogs</h1>
            <p>Add, Delete and manage blog posts</p>
        </div>
    </section>
    <section class="dashboard-section">
        <div class="container">
            <div class="dashboard-container">
                <div class="dashboard-sidebar">
                    <?php include 'sidebar.php'; ?>
                </div>
                
                <div class="dashboard-content">
                    <?php displayFlashMessage(); ?>
                    
                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-blog"></i> Manage Blog Posts</h2>
                            <a href="add-blog.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Post</a>
                        </div>
                        
                        <div class="content-body">
                            <?php if (empty($blog_posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-blog"></i>
                                    <h3>No Blog Posts Yet</h3>
                                    <p>Start creating engaging content for your website visitors.</p>
                                    <a href="add-blog.php" class="btn-primary">Create Your First Post</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="data-table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Status</th>
                                                <th>Author</th>
                                                <th>Views</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($blog_posts as $post): ?>
                                                <tr>
                                                    <td>
                                                        <div class="blog-title">
                                                            <?php if (!empty($post['featured_image'])): ?>
                                                                <div class="blog-thumbnail">
                                                                    <img src="../<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="title-link"><?php echo htmlspecialchars($post['title']); ?></a>
                                                                <span class="blog-slug">/blog/<?php echo htmlspecialchars($post['slug']); ?></span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge <?php echo $post['status'] === 'published' ? 'status-active' : 'status-inactive'; ?>">
                                                            <?php echo ucfirst($post['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($post['author_name'] ?? 'Unknown'); ?></td>
                                                    <td><?php echo number_format($post['views']); ?></td>
                                                    <td><?php echo formatBlogDate($post['created_at']); ?></td>
                                                    <td>
                                                        <div class="action-buttons">
                                                            <a href="../blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn-icon" title="View Post" target="_blank">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <a href="edit-blog.php?id=<?php echo $post['id']; ?>" class="btn-icon" title="Edit Post">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            
                                                            <?php if ($post['status'] === 'draft'): ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                                    <input type="hidden" name="action" value="publish">
                                                                    <button type="submit" class="btn-icon" title="Publish Post" onclick="return confirm('Are you sure you want to publish this post?')">
                                                                        <i class="fas fa-check-circle"></i>
                                                                    </button>
                                                                </form>
                                                            <?php else: ?>
                                                                <form method="POST" style="display: inline;">
                                                                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                                    <input type="hidden" name="action" value="draft">
                                                                    <button type="submit" class="btn-icon" title="Set to Draft" onclick="return confirm('Are you sure you want to set this post to draft?')">
                                                                        <i class="fas fa-file-alt"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn-icon delete" title="Delete Post" onclick="return confirm('Are you sure you want to delete this post? This action cannot be undone.')">
                                                                    <i class="fas fa-trash-alt"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <?php if ($total_pages > 1): ?>
                                    <div class="pagination">
                                        <?php if ($current_page > 1): ?>
                                            <a href="?page=<?php echo $current_page - 1; ?>" class="pagination-link">
                                                <i class="fas fa-chevron-left"></i> Previous
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <a href="?page=<?php echo $i; ?>" class="pagination-link <?php echo $i === $current_page ? 'active' : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($current_page < $total_pages): ?>
                                            <a href="?page=<?php echo $current_page + 1; ?>" class="pagination-link">
                                                Next <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php include '../includes/footer.php'; ?>
    <script>
        // Add any JavaScript functionality here
    </script>
</body>
</html>
