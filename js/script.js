// Mobile Navigation Script
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navMenu = document.querySelector('.main-navigation');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            document.body.classList.toggle('menu-open');
            
            // Toggle aria-expanded for accessibility
            const isExpanded = navMenu.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
            
            // Toggle menu icon between hamburger and close
            const menuIcon = this.querySelector('i');
            if (menuIcon) {
                if (isExpanded) {
                    menuIcon.classList.remove('fa-bars');
                    menuIcon.classList.add('fa-times');
                    
                    // Set focus on the menu to enable easy scrolling
                    setTimeout(() => {
                        navMenu.focus();
                        // Ensure the auth menu is in viewport
                        const authMenu = document.querySelector('.auth-menu');
                        if (authMenu) {
                            // Make sure it's visible in the initial view
                            const registerBtn = document.querySelector('.auth-menu .highlight-btn');
                            if (registerBtn && !isElementInViewport(registerBtn)) {
                                registerBtn.scrollIntoView({behavior: 'smooth', block: 'center'});
                            }
                        }
                    }, 100);
                } else {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
        });
    }
    
    // Helper function to check if an element is in viewport
    function isElementInViewport(el) {
        const rect = el.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    }
    
    // User dropdown toggle
    const userToggle = document.querySelector('.user-toggle');
    if (userToggle) {
        userToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const dropdown = this.nextElementSibling;
            dropdown.classList.toggle('active');
            
            // Toggle aria-expanded for accessibility
            const isExpanded = dropdown.classList.contains('active');
            this.setAttribute('aria-expanded', isExpanded);
        });
    }
    
    // Close the dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (userToggle && !userToggle.contains(e.target)) {
            const dropdown = document.querySelector('.user-dropdown');
            if (dropdown && dropdown.classList.contains('active')) {
                dropdown.classList.remove('active');
                userToggle.setAttribute('aria-expanded', 'false');
            }
        }
    });
    
    // Add close functionality for sub-menu items on mobile
    const menuLinks = document.querySelectorAll('.nav-menu a, .auth-menu a');
    menuLinks.forEach(link => {
        // Don't close menu for parent links with dropdown
        if (!link.classList.contains('user-toggle')) {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    navMenu.classList.remove('active');
                    document.body.classList.remove('menu-open');
                    
                    // Reset hamburger icon
                    if (mobileMenuBtn) {
                        mobileMenuBtn.setAttribute('aria-expanded', 'false');
                        const menuIcon = mobileMenuBtn.querySelector('i');
                        if (menuIcon) {
                            menuIcon.classList.remove('fa-times');
                            menuIcon.classList.add('fa-bars');
                        }
                    }
                }
            });
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            // Reset mobile menu state when viewport becomes desktop-size
            navMenu.classList.remove('active');
            document.body.classList.remove('menu-open');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.setAttribute('aria-expanded', 'false');
                const menuIcon = mobileMenuBtn.querySelector('i');
                if (menuIcon) {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                }
            }
        }
    });
    
    // Handle scroll behavior
    let lastScrollPosition = 0;
    const header = document.querySelector('header');
    
    window.addEventListener('scroll', function() {
        // Add scrolled class for styling
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        // Hide/show header on scroll
        const currentScrollPosition = window.scrollY;
        
        // Don't hide header when menu is open
        if (!document.body.classList.contains('menu-open')) {
            if (currentScrollPosition > lastScrollPosition && currentScrollPosition > 200) {
                // Scrolling down - hide header
                header.classList.add('hidden');
            } else {
                // Scrolling up - show header
                header.classList.remove('hidden');
            }
        }
        
        lastScrollPosition = currentScrollPosition;
    });
});