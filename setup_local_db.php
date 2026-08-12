<?php
// Database setup for local development
echo "Setting up local database for bora-website...\n";

// Local database connection (without specifying database name first)
try {
    $pdo = new PDO("mysql:host=localhost;charset=utf8", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS bora_website");
    echo "Database 'bora_website' created or already exists.\n";
    
    // Switch to the database
    $pdo->exec("USE bora_website");
    
    // Create blog_posts table
    $sql = "CREATE TABLE IF NOT EXISTS blog_posts (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) UNIQUE NOT NULL,
        excerpt TEXT,
        content LONGTEXT,
        featured_image VARCHAR(255),
        author_id INT,
        status ENUM('draft', 'published') DEFAULT 'published',
        views INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "blog_posts table created or already exists.\n";
    
    // Create users table (if needed for author_name)
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) UNIQUE NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'job_seeker', 'employer') DEFAULT 'job_seeker',
        is_verified BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "users table created or already exists.\n";
    
    // Insert sample admin user if it doesn't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, is_verified) VALUES (?, ?, ?, 'admin', 1)");
        $stmt->execute(['admin', 'admin@bora-website.com', password_hash('admin123', PASSWORD_DEFAULT)]);
        echo "Sample admin user created (username: admin, password: admin123).\n";
    }
    
    // Check if any blog posts exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        // Insert sample blog posts
        $sample_posts = [
            [
                'title' => 'Welcome to B&H Employment & Consultancy Inc',
                'slug' => 'welcome-to-bh-employment',
                'excerpt' => 'We are excited to launch our new website and blog where we will share insights about employment opportunities.',
                'content' => '<p>Welcome to B&H Employment & Consultancy Inc! We are thrilled to have you here.</p><p>Our company has been dedicated to connecting talented individuals with amazing employment opportunities. Through this blog, we will share valuable insights, tips, and updates about the job market.</p><p>Stay tuned for more exciting content!</p>',
                'status' => 'published'
            ],
            [
                'title' => 'Tips for Job Seekers in 2025',
                'slug' => 'tips-for-job-seekers-2025',
                'excerpt' => 'Essential tips and strategies to help you succeed in your job search this year.',
                'content' => '<p>The job market in 2025 presents both opportunities and challenges. Here are some essential tips to help you succeed:</p><h2>1. Update Your Skills</h2><p>Technology is constantly evolving. Make sure your skills are current and relevant to your industry.</p><h2>2. Network Effectively</h2><p>Building professional relationships is crucial for career success.</p><h2>3. Tailor Your Resume</h2><p>Customize your resume for each job application to highlight relevant experience.</p>',
                'status' => 'published'
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, excerpt, content, status, author_id, views) VALUES (?, ?, ?, ?, ?, 1, ?)");
        
        foreach ($sample_posts as $post) {
            $stmt->execute([
                $post['title'],
                $post['slug'],
                $post['excerpt'],
                $post['content'],
                $post['status'],
                rand(10, 100)
            ]);
        }
        
        echo "Sample blog posts created.\n";
    }
    
    // Create site_settings table
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "site_settings table created or already exists.\n";
    
    echo "\nDatabase setup completed successfully!\n";
    echo "You can now access your blog at: http://localhost/bora-website/blog.php\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nPlease make sure:\n";
    echo "1. MySQL/MariaDB is running\n";
    echo "2. You have the correct database credentials\n";
    echo "3. You have permission to create databases\n";
}
?>
