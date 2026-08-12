<?php
require_once 'config.php';
require_once 'includes/blog-functions.php';

// Get site settings for use throughout the page
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

// Set defaults if not found
$site_title = $site_settings['site_title'] ?? 'B&H Employment & Consultancy Inc';
$site_description = $site_settings['site_description'] ?? 'Professional employment agency connecting qualified candidates with top employers';
$page_title = 'Nepali & Hindi-Speaking Nanny, Housekeeper & Cook in NYC | B&H Employment Agency';
$page_description = 'B&H Employment connects NYC homeowners with trusted, background-checked Nepali, Bhutanese & Tibetan nannies, housekeepers, cooks & cleaners. Serving Queens, Manhattan, Brooklyn & all of NYC. Call (929) 385-6177.';
$page_keywords = 'nepali nanny nyc, hindi speaking housekeeper new york, bhutanese nanny queens, tibetan housekeeper nyc, nepali cook nyc, nanny agency jackson heights, nepali household worker new york, hindi speaking nanny new york city, indian family nanny nyc, affordable nanny agency queens';
$schema_markup = '{
  "@context": "https://schema.org",
  "@type": "EmploymentAgency",
  "name": "B&H Employment & Consultancy Inc",
  "url": "https://www.bhemployment.com",
  "logo": "https://www.bhemployment.com/images/logo.png",
  "description": "NYC employment agency specializing in Nepali, Bhutanese, and Tibetan nannies, housekeepers, house cooks, and cleaners. Serving Queens, Manhattan, Brooklyn, and all of New York City.",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "37-51 75th St, Suite 1A",
    "addressLocality": "Jackson Heights",
    "addressRegion": "NY",
    "postalCode": "11372",
    "addressCountry": "US"
  },
  "telephone": ["+19293856177", "+13476802869"],
  "email": ["bh.jobagency@gmail.com", "info@bhemployment.com"],
  "foundingDate": "2018",
  "areaServed": ["Queens", "Manhattan", "Brooklyn", "Bronx", "Staten Island", "Long Island", "Jersey City", "Hoboken", "Edison"],
  "serviceType": ["Nanny Placement", "Housekeeper Placement", "House Cook Placement", "Home Cleaning", "Restaurant Staff Placement"],
  "openingHours": "Mo-Fr 09:00-18:00"
}';
?>

<!-- Header -->
<?php include 'includes/header.php'; ?>
<main>
<style>
/* Hero Split Layout */
.hero-split {
    position: relative;
    padding: 160px 0 100px 0;
    overflow: hidden;
}
.hero-background {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #0066cc, #003366);
    z-index: 1;
}
.hero-split .container {
    position: relative;
    z-index: 2;
    max-width: 1300px;
}
.hero-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}
.hero-text-side h1 {
    color: #ffffff;
    font-weight: 800;
    letter-spacing: -1px;
    font-size: 46px;
    line-height: 1.2;
    margin-bottom: 20px;
}
.hero-text-side h2 {
    color: #e2e8f0;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 20px;
}
.hero-text-side p {
    color: #cbd5e1;
    font-size: 18px;
    line-height: 1.6;
    margin-bottom: 40px;
}
.cta-container-split {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: flex-start;
}
.hero-cta-btn {
    display: inline-block;
    padding: 16px 36px;
    border-radius: 50px;
    font-size: 18px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}
.hero-cta-btn.primary-gold {
    background-color: #FF9800; /* Logo Orange */
    color: white;
    box-shadow: 0 10px 20px rgba(255, 152, 0, 0.3);
}
.hero-cta-btn.primary-gold:hover {
    transform: translateY(-3px);
    background-color: #e68a00;
    box-shadow: 0 15px 30px rgba(255, 152, 0, 0.4);
    color: #ffffff !important;
}
.hero-cta-btn.outline-white {
    background-color: transparent;
    color: white;
    border: 2px solid white;
}
.hero-cta-btn.outline-white:hover {
    background-color: white;
    color: #0A192F !important;
}
.hero-image-side {
    position: relative;
    width: 100%;
}
.hero-main-img {
    width: 100%;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    border: 6px solid rgba(255, 255, 255, 0.1);
    object-fit: cover;
    display: block;
    height: 500px;
}

/* Trust Banner */
.trust-banner {
    background-color: #FAF9F6;
    padding: 30px 0;
    border-bottom: 1px solid #e2e8f0;
}
.trust-item {
    display: flex;
    align-items: center;
    gap: 15px;
}
.trust-icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
    font-weight: bold;
}
.trust-text {
    color: #2C3E50; /* Warm Charcoal */
    font-weight: 700;
    font-size: 16px;
    line-height: 1.4;
}

@media (max-width: 991px) {
    .hero-split {
        padding: 120px 0 60px 0;
    }
    .hero-grid {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 40px;
    }
    .hero-text-side h1, .hero-text-side h2, .hero-text-side p {
        text-align: center;
    }
    .cta-container-split {
        justify-content: center;
    }
    .hero-main-img {
        height: 400px;
    }
    .trust-banner {
        padding: 40px 0;
    }
    .trust-item {
        margin-bottom: 20px;
    }
}
@media (max-width: 575px) {
    .hero-text-side h1 {
        font-size: 32px;
    }
    .hero-main-img {
        height: 300px;
    }
    .trust-item {
        flex-direction: column;
        text-align: center;
    }
}
</style>

    <!-- Hero Section Split Layout -->
    <section class="hero-split">
        <div class="hero-background"></div>
        <div class="container">
            <div class="hero-grid">
                <div class="hero-text-side">
                    <h1>Hire Trusted Nepali, Bhutanese & Hindi-Speaking Nannies, Housekeepers & Cooks</h1>
                    <h2>Background-Checked Live-in & Live-out Workers Nationwide.</h2>
                    <p>Connect with reliable household staff and hospitality workers in NYC and across the USA.</p>
                    <div class="cta-container-split">
                        <a href="https://bhemployment.com/submit-job.php" class="hero-cta-btn primary-gold">Hire Someone Now &rarr;</a>
                        <a href="https://bhemployment.com/candidate" class="hero-cta-btn outline-white">Browse Available Workers &rarr;</a>
                    </div>
                </div>
                <div class="hero-image-side">
                    <picture>
                      <!-- <source srcset="images/hero.webp" type="image/webp"> -->
                      <img src="<?php echo !empty($site_settings['hero_image']) ? $site_settings['hero_image'] : 'images/hero.jpeg'; ?>"
                        alt="Nepali and Hindi-Speaking Nannies, Housekeepers, and Cooks in NYC"
                        class="hero-main-img"
                        width="882" height="504"
                        fetchpriority="high">
                    </picture>
                </div>
            </div>
        </div>
    </section>

    <!-- Trust Banner -->
    <section class="trust-banner">
        <div class="container" style="max-width: 1200px;">
            <div style="display: flex; flex-wrap: wrap; justify-content: center; align-items: flex-start; gap: 40px;">
                <div class="trust-item">
                    <div class="trust-icon-circle" style="background-color: #4CAF50; color: #000000;">7+</div>
                    <div class="trust-text">Jobs Available<br>Daily</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon-circle" style="background-color: #FF9800;"><i class="fas fa-briefcase"></i></div>
                    <div class="trust-text">Multiple Industries<br>Served</div>
                </div>
                <div class="trust-item">
                    <div class="trust-icon-circle" style="background-color: #C5A059;"><i class="fas fa-users"></i></div>
                    <div class="trust-text">Nannies, Chefs, Helpers,<br>Cashiers, Waiters, Servers</div>
                </div>
            </div>
        </div>
    </section>
<!-- SEO Services Section -->
<section id="services" style="padding: 80px 0; background-color: #FAF9F6;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 38px; color: #0A192F; font-weight: 800; margin-bottom: 15px; letter-spacing: -1px;">Our Household Staffing Services in New York City</h2>
            <p style="font-size: 18px; color: #2C3E50; max-width: 800px; margin: 0 auto; line-height: 1.6;">We provide pre-screened, experienced Nepali, Bhutanese, and Tibetan household workers for families and homeowners across the NYC metro area.</p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <!-- Service 1 -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;">
                <div style="color: #0066cc; font-size: 32px; margin-bottom: 20px;"><i class="fas fa-baby"></i></div>
                <h3 style="font-size: 22px; color: #0A192F; margin-bottom: 15px; font-weight: 700;">Nepali & Tibetan Nannies in NYC</h3>
                <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 0;">Looking for a caring, reliable nanny in New York City? Our Nepali and Tibetan nannies are experienced in childcare, speak Hindi, Nepali, and English, and understand South Asian family values and culture. Trusted by hundreds of Indian, American, and Nepali families across Queens, Manhattan, and Brooklyn.</p>
            </div>
            
            <!-- Service 2 -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;">
                <div style="color: #0066cc; font-size: 32px; margin-bottom: 20px;"><i class="fas fa-broom"></i></div>
                <h3 style="font-size: 22px; color: #0A192F; margin-bottom: 15px; font-weight: 700;">Hindi-Speaking Housekeepers in Queens & Manhattan</h3>
                <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 0;">Our Nepali and Bhutanese housekeepers bring dedication, reliability, and attention to detail to every home. Whether you need full-time, part-time, or live-in housekeeping in New York City, we match you with the right person for your household needs.</p>
            </div>
            
            <!-- Service 3 -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;">
                <div style="color: #0066cc; font-size: 32px; margin-bottom: 20px;"><i class="fas fa-utensils"></i></div>
                <h3 style="font-size: 22px; color: #0A192F; margin-bottom: 15px; font-weight: 700;">Nepali & Indian House Cooks for NYC Families</h3>
                <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 0;">Enjoy home-cooked Nepali, Indian, or Tibetan meals every day. Our experienced house cooks are familiar with South Asian recipes, dal-bhat, curries, momos, and more. Ideal for Indian and Nepali families in NYC looking for a trusted cook who understands their cuisine.</p>
            </div>
            
            <!-- Service 4 -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;">
                <div style="color: #0066cc; font-size: 32px; margin-bottom: 20px;"><i class="fas fa-sparkles"></i></div>
                <h3 style="font-size: 22px; color: #0A192F; margin-bottom: 15px; font-weight: 700;">Home Cleaning Services — NYC, Queens & New Jersey</h3>
                <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 0;">Our trained Nepali and Bhutanese home cleaners provide thorough, professional cleaning services for homes and apartments across New York City. Available for regular weekly cleaning, deep cleaning, and move-in/move-out cleaning.</p>
            </div>
            
            <!-- Service 5 -->
            <div style="background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); padding: 30px; border: 1px solid #e2e8f0;">
                <div style="color: #0066cc; font-size: 32px; margin-bottom: 20px;"><i class="fas fa-fire-burner"></i></div>
                <h3 style="font-size: 22px; color: #0A192F; margin-bottom: 15px; font-weight: 700;">Sushi Chefs, Tandoori Chefs & Kitchen Helpers — NYC Restaurants</h3>
                <p style="color: #475569; font-size: 15px; line-height: 1.6; margin-bottom: 0;">We also supply experienced kitchen staff to restaurants across New York City. Our candidates include trained sushi chefs, tandoori chefs, and skilled kitchen helpers ready to join your restaurant team.</p>
            </div>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section id="why-choose-us" style="padding: 80px 0; background-color: #ffffff;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 36px; color: #0A192F; font-weight: 800; margin-bottom: 15px;">Why NYC Families Trust B&H Employment</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 40px;">
            <div style="display: flex; gap: 20px;">
                <div style="color: #10b981; font-size: 24px;"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <h3 style="font-size: 20px; color: #0A192F; margin-bottom: 10px; font-weight: 700;">1. Background-Checked Workers</h3>
                    <p style="color: #475569; font-size: 16px; line-height: 1.6;">Every candidate placed through B&H Employment undergoes a thorough background check before being introduced to your family. Your safety and peace of mind is our top priority.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div style="color: #10b981; font-size: 24px;"><i class="fas fa-globe-asia"></i></div>
                <div>
                    <h3 style="font-size: 20px; color: #0A192F; margin-bottom: 10px; font-weight: 700;">2. Cultural & Language Match</h3>
                    <p style="color: #475569; font-size: 16px; line-height: 1.6;">We specialize in Nepali, Bhutanese, Tibetan, and Hindi-speaking workers — so your household staff understands your language, culture, food preferences, and family values from day one.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div style="color: #10b981; font-size: 24px;"><i class="fas fa-building"></i></div>
                <div>
                    <h3 style="font-size: 20px; color: #0A192F; margin-bottom: 10px; font-weight: 700;">3. Serving NYC Since 2018</h3>
                    <p style="color: #475569; font-size: 16px; line-height: 1.6;">Since 2018, B&H Employment & Consultancy Inc has helped hundreds of families across Queens, Manhattan, Brooklyn, and New Jersey find reliable household workers they can trust long-term.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div style="color: #10b981; font-size: 24px;"><i class="fas fa-user-friends"></i></div>
                <div>
                    <h3 style="font-size: 20px; color: #0A192F; margin-bottom: 10px; font-weight: 700;">4. Personalized Matching</h3>
                    <p style="color: #475569; font-size: 16px; line-height: 1.6;">We take time to understand your family's specific needs — schedule, language preference, cooking style, and childcare requirements — before recommending any candidate.</p>
                </div>
            </div>
            
            <div style="display: flex; gap: 20px;">
                <div style="color: #10b981; font-size: 24px;"><i class="fas fa-handshake"></i></div>
                <div>
                    <h3 style="font-size: 20px; color: #0A192F; margin-bottom: 10px; font-weight: 700;">5. Support After Placement</h3>
                    <p style="color: #475569; font-size: 16px; line-height: 1.6;">Our service doesn't end when you hire. We follow up after placement to make sure both the homeowner and the worker are satisfied with the arrangement.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Fetch latest candidates
$top_candidates = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM candidates WHERE approval_status = 'approved' ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $top_candidates = $stmt->fetchAll();
} catch (PDOException $e) {
    // Fallback if approval_status column doesn't exist
    try {
        $stmt = $pdo->prepare("SELECT * FROM candidates ORDER BY created_at DESC LIMIT 3");
        $stmt->execute();
        $top_candidates = $stmt->fetchAll();
    } catch (PDOException $e2) {
        // Silently fail or log
    }
}
?>

<?php if (!empty($top_candidates)): ?>
<!-- Recent Available Workers Section -->
<section style="padding: 80px 0; background-color: #f8fafc;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 50px;">
            <div style="width: 40px; height: 3px; background-color: #fbbf24; margin: 0 auto 15px;"></div>
            <h2 style="font-size: 32px; color: #475569; font-weight: 700; margin-bottom: 15px;">Recent available workers</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <?php foreach ($top_candidates as $candidate): ?>
            <div style="background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; display: flex; flex-direction: column; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h3 style="font-size: 22px; color: #334155; font-weight: 600; margin: 0 0 5px 0;"><?php echo htmlspecialchars($candidate['first_name']); ?></h3>
                        <p style="color: #64748b; font-size: 14px; margin: 0;"><?php echo htmlspecialchars($candidate['location'] ?? 'New York'); ?></p>
                    </div>
                    <?php if (!empty($candidate['photo_path'])): ?>
                        <div style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($candidate['photo_path']); ?>" alt="Profile" width="50" height="50" style="width: 100%; height: 100%; object-fit: cover;">
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
                            echo htmlspecialchars(strlen($bio) > 80 ? substr($bio, 0, 80) . '...' : $bio); 
                        ?>
                    </p>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #e2e8f0; margin-bottom: 20px;">
                
                <div style="margin-bottom: 20px;">
                    <p style="color: #64748b; font-size: 12px; font-weight: 600; margin: 0 0 5px 0;">FROM</p>
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
                
                <a href="https://bhemployment.com/jobs?view=candidates" style="display: block; padding: 12px 20px; border: 1px solid #334155; color: #334155; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 4px; text-align: center; transition: all 0.2s ease;" onmouseover="this.style.backgroundColor='#334155'; this.style.color='white';" onmouseout="this.style.backgroundColor='transparent'; this.style.color='#334155';">View profile</a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
<!-- Dual Browse Options Section -->
<section style="padding: 60px 0; background-color: #ffffff;">
    <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 40px;">
            <!-- Applicants Card -->
            <div style="background: #f8fafc; border-radius: 16px; padding: 50px 40px; text-align: center; border: 1px solid #e2e8f0; transition: transform 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 70px; height: 70px; background: #e0f2fe; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: #0ea5e9; font-size: 30px;">
                    <i class="fas fa-users"></i>
                </div>
                <h2 style="font-size: 26px; color: #0A192F; font-weight: 700; margin-bottom: 15px;">Browse Our Applicants</h2>
                <p style="color: #64748b; font-size: 17px; margin-bottom: 30px; line-height: 1.6;">View our list of available applicants to fit the service you need.</p>
                <a href="https://bhemployment.com/candidate" style="display: inline-block; background-color: #0066cc; color: white; padding: 14px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 6px rgba(0, 102, 204, 0.2);">Browse Applicants</a>
            </div>

            <!-- Jobs Card -->
            <div style="background: #f8fafc; border-radius: 16px; padding: 50px 40px; text-align: center; border: 1px solid #e2e8f0; transition: transform 0.3s ease; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="width: 70px; height: 70px; background: #f0fdf4; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 25px; color: #10b981; font-size: 30px;">
                    <i class="fas fa-briefcase"></i>
                </div>
                <h2 style="font-size: 26px; color: #0A192F; font-weight: 700; margin-bottom: 15px;">Browse Our Job Listings</h2>
                <p style="color: #64748b; font-size: 17px; margin-bottom: 30px; line-height: 1.6;">View and apply for our latest job postings.</p>
                <a href="https://bhemployment.com/jobs" style="display: inline-block; background-color: #10b981; color: #000000; padding: 14px 30px; border-radius: 50px; text-decoration: none; font-weight: 600; transition: all 0.2s ease; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);">Browse Job Listings</a>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section id="testimonials" style="padding: 80px 0; background-color: #f8fafc;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 36px; color: #0A192F; font-weight: 800; margin-bottom: 15px;">What NYC Families Are Saying</h2>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            <div style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div style="color: #fbbf24; font-size: 20px; margin-bottom: 15px;"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p style="color: #475569; font-size: 16px; line-height: 1.6; font-style: italic; margin-bottom: 20px;">"We found the most wonderful Nepali nanny through B&H Employment. She speaks Hindi fluently, cooks amazing dal and rice for the kids, and our whole family loves her. Highly recommend to any Indian family in NYC."</p>
                <p style="color: #0A192F; font-weight: 700; margin: 0;">— Priya S., Upper West Side, Manhattan</p>
            </div>
            <div style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div style="color: #fbbf24; font-size: 20px; margin-bottom: 15px;"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p style="color: #475569; font-size: 16px; line-height: 1.6; font-style: italic; margin-bottom: 20px;">"Finding a housekeeper who speaks Nepali and understands our household culture was difficult until we found B&H. They matched us perfectly within one week."</p>
                <p style="color: #0A192F; font-weight: 700; margin: 0;">— Ramesh T., Jackson Heights, Queens</p>
            </div>
            <div style="background: white; border-radius: 16px; padding: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <div style="color: #fbbf24; font-size: 20px; margin-bottom: 15px;"><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i></div>
                <p style="color: #475569; font-size: 16px; line-height: 1.6; font-style: italic; margin-bottom: 20px;">"Professional, fast, and reliable. Our Bhutanese housekeeper has been with us for over a year and we couldn't be happier. B&H Employment made the whole process easy."</p>
                <p style="color: #0A192F; font-weight: 700; margin: 0;">— Jennifer M., Hoboken, New Jersey</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section id="faq" style="padding: 80px 0; background-color: #ffffff;">
    <div class="container" style="max-width: 900px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 50px;">
            <h2 style="font-size: 36px; color: #0A192F; font-weight: 800; margin-bottom: 15px;">Frequently Asked Questions — Hiring Nepali & Hindi-Speaking Household Staff in NYC</h2>
        </div>
        <div class="faq-list">
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q1: How do I hire a Nepali nanny in New York City?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Simply contact B&H Employment by phone at (929) 385-6177 or email bh.jobagency@gmail.com or info@bhemployment.com. Tell us your requirements — schedule, language preference, and duties — and we will match you with a pre-screened Nepali nanny in NYC as quickly as possible.</p>
            </div>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q2: Do you provide background checks for housekeepers and nannies?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Yes. All candidates placed through B&H Employment & Consultancy Inc undergo a background check process before being introduced to homeowners. We prioritize the safety and trust of every family we serve.</p>
            </div>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q3: Do you serve families outside of Queens?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Absolutely. We serve homeowners and families across all five New York City boroughs — Queens, Manhattan, Brooklyn, the Bronx, and Staten Island — as well as Long Island and New Jersey, including Jersey City, Hoboken, and Edison.</p>
            </div>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q4: Can I hire a live-in Nepali housekeeper or nanny through B&H?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Yes, we place both live-in and live-out household workers depending on your needs. Contact us to discuss live-in arrangements, schedules, and compensation guidelines.</p>
            </div>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q5: How much does it cost to hire a Hindi-speaking housekeeper in NYC?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Rates vary depending on the type of work, hours, and experience level of the worker. Contact us directly for current rate information and to discuss your specific household staffing needs.</p>
            </div>
            <div style="margin-bottom: 30px;">
                <h3 style="font-size: 20px; color: #0066cc; font-weight: 700; margin-bottom: 10px;">Q6: Do you place workers for restaurants and kitchens in NYC?</h3>
                <p style="color: #475569; font-size: 16px; line-height: 1.6;">Yes. In addition to household placements, we also connect restaurants across New York City with experienced Nepali and Tibetan kitchen staff, including sushi chefs, tandoori chefs, and kitchen helpers.</p>
            </div>
        </div>
    </div>
</section>

<!-- Areas We Serve Section -->
<section id="areas-served" style="padding: 80px 0; background-color: #f8fafc;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div class="section-title" style="text-align: center; margin-bottom: 40px;">
            <h2 style="font-size: 36px; color: #0A192F; font-weight: 800; margin-bottom: 15px;">Areas We Serve — NYC & Surrounding Regions</h2>
            <p style="color: #475569; font-size: 18px; max-width: 800px; margin: 0 auto; line-height: 1.6;">B&H Employment & Consultancy Inc is based in Jackson Heights, Queens, and proudly serves homeowners and families across the New York metropolitan area. Our household staffing services are available throughout:</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; text-align: center; margin-bottom: 40px;">
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: #0066cc; margin-bottom: 10px; font-weight: 700;">Queens</h3>
                <p style="color: #64748b; font-size: 14px;">Jackson Heights, Flushing, Astoria, Forest Hills, Woodside, Elmhurst, Jamaica</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: #0066cc; margin-bottom: 10px; font-weight: 700;">Manhattan</h3>
                <p style="color: #64748b; font-size: 14px;">Upper East Side, Upper West Side, Midtown, Downtown, Harlem</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: #0066cc; margin-bottom: 10px; font-weight: 700;">Brooklyn</h3>
                <p style="color: #64748b; font-size: 14px;">Park Slope, Bay Ridge, Flatbush, Williamsburg</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: #0066cc; margin-bottom: 10px; font-weight: 700;">The Bronx & SI</h3>
                <p style="color: #64748b; font-size: 14px;">Riverdale, Fordham, Pelham Bay, Staten Island</p>
            </div>
            <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
                <h3 style="color: #0066cc; margin-bottom: 10px; font-weight: 700;">Long Island & NJ</h3>
                <p style="color: #64748b; font-size: 14px;">Great Neck, Garden City, Hempstead, Jersey City, Hoboken, Edison, Newark</p>
            </div>
        </div>
        <p style="color: #475569; font-size: 18px; text-align: center; max-width: 800px; margin: 0 auto; line-height: 1.6; font-weight: 500;">If you are searching for a Nepali nanny, Hindi-speaking housekeeper, or Bhutanese home cook near you in New York, <a href="#contact" style="color: #0066cc; text-decoration: underline;">contact us today</a>.</p>
    </div>
</section>

<!-- CTA Banner -->
<section id="cta-banner" style="padding: 60px 0; background: linear-gradient(135deg, #0066cc, #003366); text-align: center; color: white;">
    <div class="container" style="max-width: 900px; margin: 0 auto; padding: 0 20px;">
        <h2 style="font-size: 32px; font-weight: 800; margin-bottom: 20px;">Ready to Hire a Trusted Nepali or Hindi-Speaking Household Worker in NYC?</h2>
        <p style="font-size: 18px; margin-bottom: 30px; opacity: 0.9; line-height: 1.6;">Contact B&H Employment & Consultancy Inc today. Our team is based in Jackson Heights, Queens, and ready to match you with the right nanny, housekeeper, cook, or cleaner for your New York home.</p>
        <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
            <a href="tel:9293856177" style="display: inline-block; padding: 14px 30px; border-radius: 50px; background-color: #10b981; color: #000000; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.2s;"><i class="fas fa-phone-alt" style="margin-right: 8px;"></i> Call Us: (929) 385-6177</a>
            <a href="mailto:bh.jobagency@gmail.com" style="display: inline-block; padding: 14px 30px; border-radius: 50px; background-color: white; color: #0066cc; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.2s;"><i class="fas fa-envelope" style="margin-right: 8px;"></i> bh.jobagency@gmail.com</a>
            <a href="mailto:info@bhemployment.com" style="display: inline-block; padding: 14px 30px; border-radius: 50px; background-color: white; color: #0066cc; text-decoration: none; font-weight: 600; font-size: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.2); transition: transform 0.2s;"><i class="fas fa-envelope" style="margin-right: 8px;"></i> info@bhemployment.com</a>
            <a href="request-appointment.php" style="display: inline-block; padding: 14px 30px; border-radius: 50px; background-color: transparent; border: 2px solid white; color: white; text-decoration: none; font-weight: 600; font-size: 16px; transition: background-color 0.2s;">Book an Appointment &rarr;</a>
        </div>
    </div>
</section>

<!-- About Us Snippet Section -->
<section id="about" style="padding: 80px 0; background-color: #ffffff;">
    <div class="container" style="max-width: 1000px; margin: 0 auto; padding: 0 20px;">
        <div style="display: flex; flex-wrap: wrap; gap: 40px; align-items: center;">
            <div style="flex: 1; min-width: 300px;">
                <picture>
                  <!-- <source srcset="/images/about.webp" type="image/webp"> -->
                  <img src="/images/about.png" alt="B&H Employment Consultancy Office Jackson Heights Queens" width="600" height="400" style="width: 100%; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
                </picture>
            </div>
            <div style="flex: 1; min-width: 300px;">
                <h2 style="font-size: 32px; color: #0A192F; font-weight: 800; margin-bottom: 20px;">About B&H Employment & Consultancy Inc — Jackson Heights, NYC</h2>
                <p style="color: #475569; font-size: 16px; line-height: 1.8; margin-bottom: 20px;">Founded in 2018, B&H Employment & Consultancy Inc is a trusted nanny and household staffing agency based in Jackson Heights, Queens, New York. We specialize in connecting homeowners and families across NYC with reliable, background-checked Nepali, Bhutanese, and Tibetan household workers — including nannies, housekeepers, house cooks, and cleaners.</p>
                <p style="color: #475569; font-size: 16px; line-height: 1.8; margin-bottom: 30px;">Our mission is simple: help New York City families find household staff who are not only skilled and trustworthy, but also share their language and cultural background. We understand how important that connection is, especially for Indian, Nepali, and South Asian families in New York.</p>
                <a href="about.php" style="color: #0066cc; font-weight: 700; text-decoration: none; font-size: 16px;">Learn More About Us &rarr;</a>
            </div>
        </div>
    </div>
</section>

<!-- Blog Quick Link Section -->
<?php
// Get the most recent published blog posts (3)
$recent_blog_posts = getRecentBlogPosts(3);
if (!empty($recent_blog_posts)) {
?>
<section id="blog-preview" class="blog-preview-section">
    <div class="container">
        <div class="blog-preview-container">
            <div class="blog-preview-content">
                <div class="blog-preview-header">
                    <h2>From Our Blog</h2>
                    <a href="blog.php" class="view-all-link">View All Posts <i class="fas fa-arrow-right"></i></a>
                </div>

                <div class="blog-preview-grid">
                    <?php 
                    $blog_counter = 1;
                    foreach ($recent_blog_posts as $post): 
                        $display_title = html_entity_decode($post['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        if ($blog_counter == 1) {
                            $display_title = "How to Find a Reliable Nepali Nanny in New York City";
                        } elseif ($blog_counter == 2) {
                            $display_title = "Affordable Nanny Services in NYC With Background Checks — Nepali & Tibetan Caregivers";
                        } elseif ($blog_counter == 3) {
                            $display_title = "Why Indian & American Families in NYC Prefer Nepali Nannies";
                        }
                    ?>
                    <div class="blog-preview-card">
                        <?php if (!empty($post['featured_image'])): ?>
                        <div class="blog-preview-image">
                            <img src="<?php echo htmlspecialchars($post['featured_image']); ?>" alt="<?php echo htmlspecialchars($display_title); ?>" width="400" height="250">
                        </div>
                        <?php endif; ?>
                        <div class="blog-preview-text">
                            <h3><?php echo htmlspecialchars($display_title); ?></h3>
                            <div class="blog-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo formatBlogDate($post['created_at']); ?></span>
                            </div>
                            <p><?php echo html_entity_decode($post['excerpt'], ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?></p>
                            <a href="blog-post.php?slug=<?php echo urlencode($post['slug']); ?>" class="read-more-link">Read Full Article: <?php echo htmlspecialchars($display_title); ?></a>
                        </div>
                    </div>
                    <?php 
                    $blog_counter++;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.blog-preview-section {
    padding: 60px 0;
    background-color: #f9f9f9;
}

.blog-preview-container {
    max-width: 1200px;
    margin: 0 auto;
}

.blog-preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.blog-preview-header h2 {
    font-size: 32px;
    color: #333;
    margin: 0;
}

.view-all-link {
    color: #0066cc;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

.view-all-link i {
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.view-all-link:hover {
    color: #0052a3;
}

.view-all-link:hover i {
    transform: translateX(5px);
}

.blog-preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
}

.blog-preview-card {
    display: flex;
    flex-direction: column;
    background-color: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    height: 100%;
}

.blog-preview-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.1);
}

.blog-preview-image {
    height: 200px;
    overflow: hidden;
}

.blog-preview-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.blog-preview-card:hover .blog-preview-image img {
    transform: scale(1.1);
}

.blog-preview-text {
    padding: 25px;
    display: flex;
    flex-direction: column;
    flex-grow: 1;
}

.blog-preview-text h3 {
    font-size: 20px;
    margin-top: 0;
    margin-bottom: 15px;
    color: #333;
}

.blog-meta {
    display: flex;
    gap: 20px;
    color: #666;
    font-size: 14px;
    margin-bottom: 15px;
}

.blog-meta i {
    color: #0066cc;
    margin-right: 5px;
}

.blog-preview-text p {
    color: #666;
    margin-bottom: 20px;
    line-height: 1.6;
    flex-grow: 1;
}

.read-more-link {
    display: inline-flex;
    align-items: center;
    color: #0066cc;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-top: auto;
}

.read-more-link i {
    margin-left: 5px;
    transition: transform 0.3s ease;
}

.read-more-link:hover {
    color: #0052a3;
}

.read-more-link:hover i {
    transform: translateX(5px);
}

@media (max-width: 991px) {
    .blog-preview-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 767px) {
    .blog-preview-grid {
        grid-template-columns: 1fr;
    }

    .blog-preview-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    .blog-preview-header h2 {
        font-size: 24px;
    }
}
</style>
<?php } ?>

<!-- Eye-Catching Appointment Booking Section -->
<section id="book-appointment" class="eye-catching-appointment">
    <div class="container">
        <div class="appointment-cta">
            <div class="cta-content">
                <div class="cta-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="cta-text">
                    <h2>Ready to take the next step?</h2>
                    <p>Our experts are available to guide your career journey</p>
                </div>
            </div>
            <a href="request-appointment.php" class="cta-btn pulse-btn">Book an Appointment</a>
        </div>
    </div>
</section>

<style>
.eye-catching-appointment {
    padding: 50px 0;
    background: linear-gradient(135deg, #0066cc, #40BFBF);
    margin: 40px 0;
    position: relative;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0, 102, 204, 0.3);
}

.appointment-cta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.cta-content {
    display: flex;
    align-items: center;
    gap: 20px;
    flex: 2;
}

.cta-icon {
    background-color: rgba(255, 255, 255, 0.2);
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
}

.cta-icon i {
    font-size: 30px;
    color: white;
}

.cta-text {
    color: white;
}

.cta-text h2 {
    margin-bottom: 5px;
    font-size: 24px;
}

.cta-text p {
    margin: 0;
    opacity: 0.9;
}

.cta-btn {
    background-color: white;
    color: #0066cc;
    padding: 12px 25px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.cta-btn:hover {
    background-color: #f0f0f0;
    transform: translateY(-3px);
}

</style>
    <!-- Contact Form Section -->
<!-- Contact Form Section -->
<section id="contact" class="contact">
    <div class="container">
        <div class="section-title">
            <h2>Get In Touch</h2>
            <p>Have questions or need assistance? Fill out the form below and our team will get back to you soon.</p>
        </div>
        <?php
        // Process contact form submission
        $contact_success = '';
        $contact_error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
            $name = sanitizeInput($_POST['name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $subject = sanitizeInput($_POST['subject']);
            $message = sanitizeInput($_POST['message']);

            if (empty($name) || empty($email) || empty($subject) || empty($message)) {
                $contact_error = "Please fill in all required fields.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $contact_error = "Please enter a valid email address.";
            } else {
                // Store message in database
                try {
                    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, phone, subject, message)
                                           VALUES (:name, :email, :phone, :subject, :message)");
                    $stmt->bindParam(':name', $name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':subject', $subject);
                    $stmt->bindParam(':message', $message);
                    $stmt->execute();

                    $contact_success = "Your message has been sent successfully! We'll get back to you shortly.";

                    // Reset form fields
                    $name = $email = $phone = $subject = $message = '';
                } catch (PDOException $e) {
                    error_log("Error saving contact message: " . $e->getMessage());
                    $contact_error = "An error occurred while sending your message. Please try again later.";
                }
            }
        }
        ?>

        <?php if (!empty($contact_success)): ?>
            <div class="alert alert-success"><?php echo $contact_success; ?></div>
        <?php endif; ?>

        <?php if (!empty($contact_error)): ?>
            <div class="alert alert-danger"><?php echo $contact_error; ?></div>
        <?php endif; ?>

        <form class="contact-form" method="POST" action="#contact">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? $name : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? $email : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number (Optional)</label>
                <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo isset($phone) ? $phone : ''; ?>">
            </div>
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? $subject : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="message">Your Message</label>
                <textarea class="form-control" id="message" name="message" required><?php echo isset($message) ? $message : ''; ?></textarea>
            </div>
            <button type="submit" name="contact_submit" class="submit-btn">Send Message</button>
        </form>
    </div>
</section>
    <!-- Company Info Section -->
<style>
.info-icon {
    flex-shrink: 0;
    width: 50px;
    height: 50px;
    min-width: 50px;
    min-height: 50px;
    max-width: 50px;
    max-height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
}
</style>
<!-- Company Info Section -->
<section class="company-info">
    <div class="container">
        <div class="info-container">
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-envelope"></i>
                </div>
                <div class="info-content">
                    <h2>Email Us</h2>
                    <p><?php echo htmlspecialchars($site_settings['contact_email'] ?? 'bh.jobagency@gmail.com'); ?><br>info@bhemployment.com</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-globe"></i>
                </div>
                <div class="info-content">
                    <h2>Website</h2>
                    <p>www.bhemployment.com</p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-phone"></i>
                </div>
                <div class="info-content">
                    <h2>Call Us</h2>
                    <p>(Office) <?php echo htmlspecialchars($site_settings['contact_phone'] ?? '(347) 680-2869'); ?></p>
                    <p>(Mobile) <?php echo htmlspecialchars($site_settings['mobile_phone'] ?? '(929) 385-6177'); ?></p>
                </div>
            </div>
            <div class="info-item">
                <div class="info-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="info-content">
                    <h2>Our Location</h2>
                    <p><?php echo htmlspecialchars($site_settings['contact_address'] ?? '37-51 75th St, Suite 1A, Jackson Heights, NY 11372'); ?></p>
                </div>
            </div>
        </div>
    </div>
</section>
</main>
    <?php include 'includes/footer.php'; ?>
<script>// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            e.preventDefault();

            const target = document.querySelector(targetId);
            if (target) {
                window.scrollTo({
                    top: target.offsetTop - 80,
                    behavior: 'smooth'
                });

                // Close mobile menu if open
                const navMenu = document.querySelector('.nav-menu');
                if (navMenu && navMenu.classList.contains('active')) {
                    navMenu.classList.remove('active');
                }
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

    // Run animation on load
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

    // Run animation on scroll
    window.addEventListener('scroll', handleScrollAnimation);

    // Job save functionality
    const saveBtns = document.querySelectorAll('.job-save[data-job-id]');
    saveBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            if (this.getAttribute('href')) return; // Let the login link work normally

            const jobId = this.getAttribute('data-job-id');
            const icon = this.querySelector('i');
            const text = this.querySelector('span');
            const isSaved = icon.classList.contains('fas');

            // Create AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/save-job.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            if (isSaved) {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                text.textContent = 'Save Job';
                            } else {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                text.textContent = 'Saved';

                                // Add heart animation
                                icon.style.transform = 'scale(1.3)';
                                setTimeout(() => {
                                    icon.style.transform = 'scale(1)';
                                }, 300);
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                }
            };

            xhr.send('job_id=' + jobId + '&action=' + (isSaved ? 'unsave' : 'save'));
        });
    });

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
});</script>
    <script src="js/script.js" defer></script>
</body>
</html>

