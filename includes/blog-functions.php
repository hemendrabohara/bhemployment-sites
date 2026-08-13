<?php
/**
 * Blog-related functions
 */

/**
 * Generate a slug from a title
 *
 * @param string $title The title to convert to a slug
 * @return string The generated slug
 */
function generateSlug($title) {
    // Convert to lowercase and replace spaces with hyphens
    $slug = strtolower(trim($title));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug;
}

/**
 * Upload a blog featured image
 *
 * @param array $file The uploaded file ($_FILES['featured_image'])
 * @return string|false Path to the uploaded image or false on failure
 */
function uploadBlogImage($file) {
    // Check if file was uploaded successfully
    if (!isset($file) || $file['error'] != 0) {
        error_log("File upload error: " . $file['error']);
        return false;
    }

    // Validate file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        error_log("Invalid file type: " . $file['type']);
        return false;
    }

    // Validate file size (max 5MB)
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        error_log("File too large: " . $file['size']);
        return false;
    }

    // Create uploads directory if it doesn't exist
    $upload_dir = dirname(dirname(__FILE__)) . '/uploads/blog_images/';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            error_log("Failed to create directory: " . $upload_dir);
            return false;
        }
    }

    // Generate a unique filename
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_filename = 'blog_' . time() . '_' . rand(1000, 9999) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Return the relative path for database storage
        return 'uploads/blog_images/' . $new_filename;
    } else {
        error_log("Failed to move uploaded file from {$file['tmp_name']} to {$upload_path}");
        return false;
    }
}

/**
 * Get recent blog posts
 *
 * @param int $limit Number of posts to retrieve
 * @param bool $published_only Whether to retrieve only published posts
 * @return array Array of blog posts
 */
function getRecentBlogPosts($limit = 5, $published_only = true) {
    global $pdo;

    try {
        $sql = "SELECT b.*, u.username as author_name
                FROM blog_posts b
                LEFT JOIN users u ON b.author_id = u.id ";

        if ($published_only) {
            $sql .= "WHERE b.status = 'published' ";
        }

        $sql .= "ORDER BY b.created_at DESC LIMIT :limit";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching recent blog posts: " . $e->getMessage());
        return [];
    }
}

/**
 * Get a single blog post by slug
 *
 * @param string $slug The post slug
 * @return array|false The blog post or false if not found
 */
function getBlogPostBySlug($slug) {
    global $pdo;

    try {
        $stmt = $pdo->prepare("SELECT b.*, u.username as author_name
                              FROM blog_posts b
                              LEFT JOIN users u ON b.author_id = u.id
                              WHERE b.slug = :slug");
        $stmt->bindParam(':slug', $slug);
        $stmt->execute();

        $post = $stmt->fetch();

        if ($post) {
            // Increment view count
            $update_stmt = $pdo->prepare("UPDATE blog_posts SET views = views + 1 WHERE id = :id");
            $update_stmt->bindParam(':id', $post['id']);
            $update_stmt->execute();
        }

        return $post;
    } catch (PDOException $e) {
        error_log("Error fetching blog post by slug: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all blog posts with pagination
 *
 * @param int $page Current page number
 * @param int $per_page Number of posts per page
 * @param bool $published_only Whether to retrieve only published posts
 * @return array Array containing 'posts' and 'total_pages'
 */
function getAllBlogPosts($page = 1, $per_page = 10, $published_only = true) {
    global $pdo;

    try {
        // Calculate offset
        $offset = ($page - 1) * $per_page;

        // Get total count for pagination
        $count_sql = "SELECT COUNT(*) as total FROM blog_posts";
        if ($published_only) {
            $count_sql .= " WHERE status = 'published'";
        }

        $count_stmt = $pdo->prepare($count_sql);
        $count_stmt->execute();
        $total_posts = $count_stmt->fetch()['total'];
        $total_pages = ceil($total_posts / $per_page);

        // Get posts for current page
        $sql = "SELECT b.*, u.username as author_name
                FROM blog_posts b
                LEFT JOIN users u ON b.author_id = u.id ";

        if ($published_only) {
            $sql .= "WHERE b.status = 'published' ";
        }

        $sql .= "ORDER BY b.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':limit', $per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'posts' => $stmt->fetchAll(),
            'total_pages' => $total_pages,
            'current_page' => $page,
            'total_posts' => $total_posts
        ];
    } catch (PDOException $e) {
        error_log("Error fetching all blog posts: " . $e->getMessage());
        return [
            'posts' => [],
            'total_pages' => 0,
            'current_page' => $page,
            'total_posts' => 0
        ];
    }
}

/**
 * Format a date for display
 *
 * @param string $date The date to format
 * @return string The formatted date
 */
function formatBlogDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Create excerpt from content
 *
 * @param string $content The content to create an excerpt from
 * @param int $length The maximum length of the excerpt
 * @return string The excerpt
 */
function createExcerpt($content, $length = 150) {
    // Strip HTML tags
    $text = strip_tags($content);

    // Trim to length
    if (strlen($text) > $length) {
        $text = substr($text, 0, $length) . '...';
    }

    return $text;
}
?>
