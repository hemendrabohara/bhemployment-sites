<?php
require_once 'config.php';
require_once 'includes/blog-functions.php';

// Initialize variables
$post = null;
$recent_posts = [];
$error_message = '';

// Check if slug is provided
if (!isset($_GET['slug']) || empty($_GET['slug'])) {
    redirect('blog.php');
}

$slug = $_GET['slug'];

try {
    $post = getBlogPostBySlug($slug);
    
    // If post not found, redirect to blog listing
    if (!$post) {
        flashMessage("Blog post not found", "danger");
        redirect('blog.php');
    }
    
    // Get recent posts for sidebar
    $recent_posts = getRecentBlogPosts(5);
    
} catch (Exception $e) {
    error_log("Error loading blog post: " . $e->getMessage());
    $error_message = "There was an error loading the blog post. Please try again later.";
}

// Get site settings for favicon
$favicon_path = '';
if (isset($site_settings) && !empty($site_settings['favicon'])) {
    $favicon_path = '/' . ltrim($site_settings['favicon'], '/');
} else {
    $favicon_path = '/favicon.ico';
}

// Set page title and description
$page_title = ($post ? $post['title'] : 'Blog Post') . " - B&H Employment & Consultancy Inc";
$page_description = $post ? $post['excerpt'] : 'Read our latest blog posts and updates.';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (!empty($favicon_path)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <style>
        /* Premium Clean Blog Post Design */
        :root {
            --primary: #0A192F;
            --secondary: #0066cc;
            --accent: #10b981;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            color: var(--text-main);
            line-height: 1.7;
        }

        .blog-post-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            align-items: flex-start;
        }
        
        .blog-post-main {
            flex: 3;
            min-width: 320px;
        }
        
        .blog-post-sidebar {
            flex: 1;
            min-width: 300px;
            position: sticky;
            top: 20px;
        }
        
        .blog-post {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        }
        
        .blog-post-image {
            width: 100%;
            max-height: 550px;
            overflow: hidden;
            position: relative;
        }
        
        .blog-post-image::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,0.1) 100%);
            pointer-events: none;
        }
        
        .blog-post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .blog-post-content {
            padding: 40px;
        }
        
        .blog-post-header {
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .blog-post-title {
            font-size: 38px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 20px;
            line-height: 1.3;
            letter-spacing: -0.5px;
        }
        
        .blog-post-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .blog-post-meta span {
            display: flex;
            align-items: center;
        }
        
        .blog-post-meta i {
            margin-right: 8px;
            color: var(--secondary);
        }
        
        .blog-post-body {
            color: var(--text-main);
            font-size: 17px;
            line-height: 1.8;
        }
        
        .blog-post-body h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin: 40px 0 20px;
            line-height: 1.4;
        }
        
        .blog-post-body h3 {
            font-size: 22px;
            font-weight: 600;
            color: var(--primary);
            margin: 35px 0 15px;
        }
        
        .blog-post-body p {
            margin-bottom: 25px;
            color: #334155;
        }
        
        .blog-post-body img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            margin: 30px 0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        
        .blog-post-body ul, .blog-post-body ol {
            margin-bottom: 25px;
            padding-left: 25px;
            color: #334155;
        }
        
        .blog-post-body li {
            margin-bottom: 12px;
        }
        
        .blog-post-body blockquote {
            border-left: 4px solid var(--secondary);
            padding: 25px 30px;
            background-color: #f8fafc;
            border-radius: 0 8px 8px 0;
            margin: 30px 0;
            font-style: italic;
            font-size: 18px;
            color: var(--primary);
        }
        
        .blog-sidebar-section {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .blog-sidebar-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f1f5f9;
        }
        
        .recent-posts-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .recent-post-item {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .recent-post-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .recent-post-link {
            display: block;
            color: var(--primary);
            font-weight: 600;
            font-size: 15px;
            line-height: 1.5;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .recent-post-link:hover {
            color: var(--secondary);
        }
        
        .recent-post-date {
            display: block;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 8px;
            font-weight: 500;
        }
        
        .back-to-blog {
            display: inline-flex;
            align-items: center;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 15px;
            text-decoration: none;
            margin-bottom: 25px;
            padding: 8px 16px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 50px;
            transition: all 0.2s ease;
        }
        
        .back-to-blog i {
            margin-right: 8px;
            transition: transform 0.2s ease;
        }
        
        .back-to-blog:hover {
            color: var(--primary);
            background: #f1f5f9;
            border-color: #cbd5e1;
        }
        
        .back-to-blog:hover i {
            transform: translateX(-4px);
        }
        
        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .blog-post-container {
                flex-direction: column;
                padding: 40px 15px;
            }
            
            .blog-post-sidebar {
                width: 100%;
                position: static;
            }
            
            .blog-post-title {
                font-size: 28px;
            }
            
            .blog-post-content {
                padding: 25px;
            }
            
            .blog-post-body {
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="blog-post-container">
            <?php if (!empty($error_message)): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <h2>Unable to Load Blog Post</h2>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                    <a href="blog.php" class="back-to-blog" style="display: inline-block; margin-top: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Blog
                    </a>
                </div>
            <?php elseif (!$post): ?>
                <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <h2>Blog Post Not Found</h2>
                    <p>The requested blog post could not be found.</p>
                    <a href="blog.php" class="back-to-blog" style="display: inline-block; margin-top: 10px;">
                        <i class="fas fa-arrow-left"></i> Back to Blog
                    </a>
                </div>
            <?php else: ?>
            <div class="blog-post-main">
                <a href="blog.php" class="back-to-blog">
                    <i class="fas fa-arrow-left"></i> Back to Blog
                </a>
                
                <article class="blog-post">
                    <?php if (!empty($post['featured_image'])): ?>
                        <div class="blog-post-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>">
                        </div>
                    <?php endif; ?>
                    
                    <div class="blog-post-content">
                        <header class="blog-post-header">
                            <h1 class="blog-post-title"><?php echo html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></h1>
                            
                            <div class="blog-post-meta">
                                <span><i class="fas fa-user"></i> <?php echo html_entity_decode($post['author_name'] ?? 'Admin', ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></span>
                                <span><i class="fas fa-calendar"></i> <?php echo formatBlogDate($post['created_at']); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo number_format($post['views']); ?> views</span>
                            </div>
                        </header>
                        
                        <div class="blog-post-body">
                            <?php echo $post['content']; ?>
                        </div>
                    </div>
                </article>
            </div>
            
            <div class="blog-post-sidebar">
                <div class="blog-sidebar-section">
                    <h3 class="blog-sidebar-title">Recent Posts</h3>
                    
                    <?php if (!empty($recent_posts)): ?>
                        <ul class="recent-posts-list">
                            <?php foreach ($recent_posts as $recent_post): ?>
                                <?php if ($recent_post['id'] != $post['id']): ?>
                                    <li class="recent-post-item">
                                        <a href="blog-post.php?slug=<?php echo urlencode($recent_post['slug']); ?>" class="recent-post-link">
                                            <?php echo html_entity_decode($recent_post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>
                                        </a>
                                        <span class="recent-post-date"><?php echo formatBlogDate($recent_post['created_at']); ?></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No recent posts available.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Include footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
