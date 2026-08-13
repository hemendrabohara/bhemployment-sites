<?php
require_once '../config.php';
require_once '../includes/blog-functions.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    flashMessage("You must be logged in as an admin to access this page", "danger");
    redirect('../login.php');
}

// Check if post ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    flashMessage("No blog post specified", "danger");
    redirect('manage-blogs.php');
}

$post_id = (int)$_GET['id'];
$errors = [];
$success = '';
$post = [];

// Get the blog post
try {
    $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
    $stmt->bindParam(':id', $post_id);
    $stmt->execute();

    $post = $stmt->fetch();

    if (!$post) {
        flashMessage("Blog post not found", "danger");
        redirect('manage-blogs.php');
    }
} catch (PDOException $e) {
    error_log("Error fetching blog post: " . $e->getMessage());
    flashMessage("An error occurred. Please try again.", "danger");
    redirect('manage-blogs.php');
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = $_POST['content'] ?? ''; // Don't sanitize content as it contains HTML
    $excerpt = sanitizeInput($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $current_slug = $post['slug'];

    // Debug information
    error_log("Form submitted with title: " . $title);
    error_log("Content length: " . strlen($content));

    // Validate form data
    if (empty($title)) {
        $errors[] = "Title is required";
    }

    if (empty($content)) {
        $errors[] = "Content is required";
    }

    // Generate new slug if title has changed
    $new_slug = $current_slug;
    if ($title !== $post['title']) {
        $new_slug = generateSlug($title);
        error_log("Generated new slug: " . $new_slug);

        // Check if new slug already exists (excluding current post)
        try {
            $stmt = $pdo->prepare("SELECT id FROM blog_posts WHERE slug = :slug AND id != :id");
            $stmt->bindParam(':slug', $new_slug);
            $stmt->bindParam(':id', $post_id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // Append a random number to make the slug unique
                $new_slug = $new_slug . '-' . rand(1000, 9999);
                error_log("Slug already exists, new slug: " . $new_slug);
            }
        } catch (PDOException $e) {
            error_log("Error checking slug uniqueness: " . $e->getMessage());
            $errors[] = "An error occurred. Please try again.";
        }
    }

    // If no excerpt provided, create one from content
    if (empty($excerpt)) {
        $excerpt = createExcerpt($content);
        error_log("Generated excerpt: " . substr($excerpt, 0, 50) . "...");
    }

    // Handle featured image upload
    $featured_image = $post['featured_image'];
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] == 0) {
        error_log("Processing featured image upload: " . $_FILES['featured_image']['name']);
        $new_featured_image = uploadBlogImage($_FILES['featured_image']);

        if ($new_featured_image === false) {
            error_log("Featured image upload failed");
            $errors[] = "Failed to upload featured image. Please check file type and size.";
        } else {
            error_log("Featured image uploaded successfully: " . $new_featured_image);
            $featured_image = $new_featured_image;
        }
    } else if (isset($_FILES['featured_image'])) {
        error_log("Featured image upload error code: " . $_FILES['featured_image']['error']);
    }

    // If no errors, update the blog post
    if (empty($errors)) {
        try {
            error_log("Attempting to update blog post");
            $stmt = $pdo->prepare("UPDATE blog_posts SET
                                  title = :title,
                                  slug = :slug,
                                  content = :content,
                                  excerpt = :excerpt,
                                  featured_image = :featured_image,
                                  status = :status,
                                  updated_at = CURRENT_TIMESTAMP
                                  WHERE id = :id");

            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':slug', $new_slug);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':excerpt', $excerpt);
            $stmt->bindParam(':featured_image', $featured_image);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $post_id);

            $result = $stmt->execute();

            if ($result) {
                error_log("Blog post updated successfully");
                flashMessage("Blog post updated successfully", "success");

                // Refresh post data
                $stmt = $pdo->prepare("SELECT * FROM blog_posts WHERE id = :id");
                $stmt->bindParam(':id', $post_id);
                $stmt->execute();
                $post = $stmt->fetch();

                $success = "Blog post updated successfully";
            } else {
                error_log("Failed to execute SQL statement");
                $errors[] = "Failed to update blog post. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Error updating blog post: " . $e->getMessage());
            $errors[] = "An error occurred while updating the blog post: " . $e->getMessage();
        }
    } else {
        error_log("Form has errors: " . implode(", ", $errors));
    }
}

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
    <title>Edit Blog Post - B&H Employment & Consultancy Inc</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="../css/updated-styles.css">
    <?php if (!empty($favicon_path)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($favicon_path); ?>" type="image/x-icon">
    <?php endif; ?>
    <!-- Include TinyMCE for rich text editing -->
    <script src="https://cdn.tiny.cloud/1/7zpmnzaskc3dle4yh3frv2p4ta5j7qj9per7g5byxx4etl2p/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        tinymce.init({
          selector: '#content',
          plugins: 'anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount',
          toolbar: 'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | numlist bullist indent outdent | emoticons charmap | removeformat',
          height: 500,
          setup: function(editor) {
            editor.on('change', function() {
              editor.save(); // This ensures the content is saved to the textarea
            });
          }
        });
      });
    </script>
</head>
<body>
    <section class="dashboard-section">
        <div class="container">
            <div class="dashboard-container">
                <div class="dashboard-sidebar">
                    <?php include 'sidebar.php'; ?>
                </div>

                <div class="dashboard-content">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>

                    <div class="content-box">
                        <div class="content-header">
                            <h2><i class="fas fa-edit"></i> Edit Blog Post</h2>
                            <div>
                                <a href="../blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="btn-secondary" target="_blank"><i class="fas fa-eye"></i> View Post</a>
                                <a href="manage-blogs.php" class="btn-secondary"><i class="fas fa-arrow-left"></i> Back to Blog Posts</a>
                            </div>
                        </div>

                        <div class="content-body">
                            <form method="POST" enctype="multipart/form-data" id="blog-form">
                                <div class="form-group">
                                    <label for="title">Title <span class="required">*</span></label>
                                    <input type="text" id="title" name="title" class="form-control" required value="<?php echo htmlspecialchars($post['title']); ?>">
                                </div>

                                <div class="form-group">
                                    <label for="featured_image">Featured Image</label>
                                    <?php if (!empty($post['featured_image'])): ?>
                                        <div class="current-image">
                                            <img src="../<?php echo htmlspecialchars($post['featured_image']); ?>" alt="Featured Image" style="max-width: 300px; margin-bottom: 10px;">
                                            <p>Current featured image. Upload a new one to replace it.</p>
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" id="featured_image" name="featured_image" class="form-control" accept="image/*">
                                    <small class="form-text">Recommended size: 1200x630 pixels. Max file size: 5MB.</small>
                                </div>

                                <div class="form-group">
                                    <label for="excerpt">Excerpt</label>
                                    <textarea id="excerpt" name="excerpt" class="form-control" rows="3"><?php echo htmlspecialchars($post['excerpt']); ?></textarea>
                                    <small class="form-text">A short summary of the post. If left empty, it will be generated automatically.</small>
                                </div>

                                <div class="form-group">
                                    <label for="content">Content <span class="required">*</span></label>
                                    <textarea id="content" name="content" class="form-control" required><?php echo htmlspecialchars($post['content']); ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="status">Status</label>
                                    <select id="status" name="status" class="form-control">
                                        <option value="draft" <?php echo $post['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
                                        <option value="published" <?php echo $post['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>Post Information</label>
                                    <div class="info-grid">
                                        <div class="info-item">
                                            <span class="info-label">Created</span>
                                            <span class="info-value"><?php echo formatBlogDate($post['created_at']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Last Updated</span>
                                            <span class="info-value"><?php echo formatBlogDate($post['updated_at']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">Views</span>
                                            <span class="info-value"><?php echo number_format($post['views']); ?></span>
                                        </div>
                                        <div class="info-item">
                                            <span class="info-label">URL Slug</span>
                                            <span class="info-value">/blog/<?php echo htmlspecialchars($post['slug']); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-actions">
                                    <button type="submit" class="btn-primary" id="submit-btn"><i class="fas fa-save"></i> Update Post</button>
                                    <a href="manage-blogs.php" class="btn-secondary">Cancel</a>
                                </div>
                            </form>

                            <script>
                                // Add form submission handler
                                document.addEventListener('DOMContentLoaded', function() {
                                    document.getElementById('blog-form').addEventListener('submit', function(e) {
                                        // Make sure TinyMCE content is updated to the textarea before submission
                                        if (typeof tinymce !== 'undefined') {
                                            var editor = tinymce.get('content');
                                            if (editor) {
                                                editor.save();
                                            }
                                        }

                                        // Check if content is empty
                                        var content = document.getElementById('content').value;
                                        if (!content.trim()) {
                                            e.preventDefault();
                                            alert('Please enter content for the blog post.');
                                            return false;
                                        }
                                    });
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>
</html>
