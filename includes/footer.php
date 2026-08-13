<?php
// Get site settings if not already loaded in header
if (!isset($site_settings)) {
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
}

// Set default values if settings are not found
$site_title = isset($site_settings['site_title']) ? html_entity_decode($site_settings['site_title'], ENT_QUOTES | ENT_HTML5, 'UTF-8') : 'B&H Employment & Consultancy Inc';
$contact_email = $site_settings['contact_email'] ?? 'bh.jobagency@gmail.com';
$contact_phone = $site_settings['contact_phone'] ?? '(1)347680-2869';
$mobile_phone = $site_settings['mobile_phone'] ?? '(929)823-7040';
$contact_address = $site_settings['contact_address'] ?? '37-51 75th St.1A, Jackson Heights, NY 11372';
$social_facebook = $site_settings['social_facebook'] ?? '#';
$social_twitter = $site_settings['social_twitter'] ?? '#';
$social_linkedin = $site_settings['social_linkedin'] ?? '#';
$social_instagram = $site_settings['social_instagram'] ?? '#';
?>

<!-- Footer -->
<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-column">
                <h3 style="color: white;">About Us</h3>
                <p style="color: #bbb; line-height: 1.6; margin-bottom: 20px;">B&H Employment & Consultancy Inc — Connecting NYC homeowners with trusted Nepali, Bhutanese, and Tibetan nannies, housekeepers, house cooks, and cleaners since 2018. Based in Jackson Heights, Queens. Serving all of New York City and New Jersey.</p>
                <div class="social-links">
                    <a href="<?php echo htmlspecialchars($social_facebook); ?>" class="social-link" target="_blank" aria-label="Visit B&H Employment on Facebook"><i class="fab fa-facebook"></i></a>
                    <a href="<?php echo htmlspecialchars($social_twitter); ?>" class="social-link" target="_blank" aria-label="Visit B&H Employment on Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="<?php echo htmlspecialchars($social_linkedin); ?>" class="social-link" target="_blank" aria-label="Visit B&H Employment on LinkedIn"><i class="fab fa-linkedin"></i></a>
                    <a href="<?php echo htmlspecialchars($social_instagram); ?>" class="social-link" target="_blank" aria-label="Visit B&H Employment on Instagram"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3 style="color: white;">Quick Links</h3>
                <ul class="footer-menu">
                    <li><a href="/index.php">Home</a></li>
                    <li><a href="/jobs.php">Jobs</a></li>
                    <li><a href="/index.php#services">Services</a></li>
                    <li><a href="/index.php#about">About Us</a></li>
                    <li><a href="/index.php#contact">Contact</a></li>
                    <li><a href="/privacy.php">Privacy Policy</a></li>
                    <li><a href="/terms.php">Terms & Conditions</a></li>
                </ul>
            </div>
            <div class="footer-column footer-contact">
                <h3 style="color: white;">Contact Info</h3>
                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($contact_address); ?></p>
                <p><i class="fas fa-phone"></i> (Office) <?php echo htmlspecialchars($contact_phone); ?></p>
                <p><i class="fas fa-mobile-alt"></i> (Mobile) <?php echo htmlspecialchars($mobile_phone); ?></p>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?> &nbsp;|&nbsp; info@bhemployment.com</p>
                <p><i class="fas fa-globe"></i> www.bhemployment.com</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> <?php echo $site_title; ?>. All Rights Reserved.</p>
        </div>
    </div>
</footer>

<script>

    // User dropdown toggle
    const userToggle = document.querySelector('.user-toggle');
    if (userToggle) {
        userToggle.addEventListener('click', function(e) {
            e.preventDefault();
            this.nextElementSibling.classList.toggle('active');
        });
    }

    // Close the dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (userToggle && !userToggle.contains(e.target)) {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown && dropdown.classList.contains('active')) {
                dropdown.classList.remove('active');
            }
        }
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();

            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                document.querySelector('.nav-menu').classList.remove('active');
            }
        });
    });

    // Scroll animation for elements
    function handleScrollAnimation() {
        const elements = document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in, .contact-form');

        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const screenPosition = window.innerHeight / 1.2;

            if (elementPosition < screenPosition) {
                element.classList.add('active');
            }
        });
    }

    // Run on load and scroll
    window.addEventListener('load', function() {
        handleScrollAnimation();

        // Add animation classes to elements
        document.querySelectorAll('.service-card').forEach((card, index) => {
            card.classList.add('fade-in');
            card.style.transitionDelay = `${0.1 * index}s`;
        });

        document.querySelectorAll('.job-card').forEach((card, index) => {
            card.classList.add('fade-in');
            card.style.transitionDelay = `${0.1 * index}s`;
        });

        document.querySelectorAll('.info-item').forEach((item, index) => {
            item.classList.add(index % 2 === 0 ? 'slide-in-left' : 'slide-in-right');
            item.style.transitionDelay = `${0.1 * index}s`;
        });

        document.querySelectorAll('.section-title').forEach(title => {
            title.classList.add('scale-in');
        });
    });

    window.addEventListener('scroll', handleScrollAnimation);

    // Auto-hide flash messages after 5 seconds
    const flashMessages = document.querySelectorAll('.alert');
    if (flashMessages.length > 0) {
        setTimeout(() => {
            flashMessages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 500);
            });
        }, 5000);
    }
</script>