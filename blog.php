<?php
require_once 'config.php';
require_once 'includes/blog-functions.php';

// Get blog posts with pagination
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($site_settings['blog_posts_per_page']) ? (int)$site_settings['blog_posts_per_page'] : 6;
$blog_posts_data = getAllBlogPosts($current_page, $per_page);
$blog_posts = $blog_posts_data['posts'];
$total_pages = (int)$blog_posts_data['total_pages'];

// Get site settings for favicon
$favicon_path = '';
if (isset($site_settings) && !empty($site_settings['favicon'])) {
    $favicon_path = '/' . ltrim($site_settings['favicon'], '/');
} else {
    $favicon_path = '/favicon.ico';
}

// Set page title and description
$page_title = "Blog - B&H Employment & Consultancy Inc";
$page_description = "Read the latest news, insights, and updates from B&H Employment & Consultancy Inc.";
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
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php if (!empty($favicon_path)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <style>
        /* Premium Clean Blog Design */
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
        }

        .blog-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
        }
        
        .blog-header {
            text-align: center;
            margin-bottom: 60px;
            position: relative;
        }
        
        .blog-header h1 {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 15px;
            letter-spacing: -1px;
        }
        
        .blog-header p {
            color: var(--text-muted);
            font-size: 18px;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        .blog-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 40px;
        }
        
        .blog-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .blog-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border-color: #cbd5e1;
        }
        
        .blog-image {
            height: 240px;
            overflow: hidden;
            position: relative;
        }
        
        .blog-image::after {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,0.3) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .blog-card:hover .blog-image::after {
            opacity: 1;
        }

        .blog-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s ease;
        }
        
        .blog-card:hover .blog-image img {
            transform: scale(1.05);
        }
        
        .blog-content {
            padding: 30px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .blog-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .blog-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .blog-meta i {
            color: var(--secondary);
        }
        
        .blog-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .blog-title a {
            color: var(--primary);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .blog-title a:hover {
            color: var(--secondary);
        }
        
        .blog-excerpt {
            color: var(--text-muted);
            margin-bottom: 25px;
            line-height: 1.7;
            font-size: 15px;
            flex-grow: 1;
        }
        
        .blog-read-more {
            display: inline-flex;
            align-items: center;
            color: var(--secondary);
            font-weight: 600;
            text-decoration: none;
            font-size: 15px;
            transition: all 0.3s ease;
            margin-top: auto;
        }
        
        .blog-read-more:hover {
            color: #004d99;
        }
        
        .blog-read-more i {
            margin-left: 8px;
            font-size: 12px;
            transition: transform 0.3s ease;
        }
        
        .blog-read-more:hover i {
            transform: translateX(4px);
        }
        
        /* Pagination Styling */
        .blog-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 60px;
            gap: 8px;
        }
        
        .pagination-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            padding: 0 15px;
            background-color: var(--card-bg);
            color: var(--text-muted);
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid var(--border-color);
        }
        
        .pagination-link:hover {
            background-color: #f1f5f9;
            color: var(--primary);
            border-color: #cbd5e1;
        }
        
        .pagination-link.active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 10px rgba(10, 25, 47, 0.2);
        }

        .pagination-link i {
            font-size: 12px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background-color: var(--card-bg);
            border-radius: 16px;
            border: 1px dashed #cbd5e1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            max-width: 600px;
            margin: 0 auto;
        }
        
        .empty-state i {
            font-size: 54px;
            color: #cbd5e1;
            margin-bottom: 25px;
        }
        
        .empty-state h3 {
            font-size: 24px;
            color: var(--primary);
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .empty-state p {
            color: var(--text-muted);
            font-size: 16px;
        }
        
        /* Responsive adjustments */
        @media screen and (max-width: 768px) {
            .blog-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .blog-header h1 {
                font-size: 32px;
            }
            
            .blog-header p {
                font-size: 16px;
                padding: 0 15px;
            }
            
            .blog-container {
                padding: 40px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Include header -->
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="blog-container">
            <div class="blog-header">
                <h1>Our Blog</h1>
                <p>Latest news, insights, and updates from B&H Employment & Consultancy Inc</p>
            </div>
            
            <?php if (empty($blog_posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <h3>No Blog Posts Yet</h3>
                    <p>Check back soon for new content!</p>
                </div>
            <?php else: ?>
                <div class="blog-grid">
                    <?php foreach ($blog_posts as $post): ?>
                        <div class="blog-card">
                            <?php if (!empty($post['featured_image'])): ?>
                                <div class="blog-image">
                                    <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="blog-content">
                                <h2 class="blog-title">
                                    <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>"><?php echo htmlspecialchars($post['title']); ?></a>
                                </h2>
                                
                                <div class="blog-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($post['author_name'] ?? 'Admin'); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo formatBlogDate($post['created_at']); ?></span>
                                </div>
                                
                                <div class="blog-excerpt">
                                    <?php echo htmlspecialchars($post['excerpt']); ?>
                                </div>
                                
                                <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="blog-read-more">
                                    Read More <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="blog-pagination">
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
    </main>
    
    <!-- Include footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
