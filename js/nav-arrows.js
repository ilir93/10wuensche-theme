document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.nav-scroll-container');
    const leftBtn = document.querySelector('.nav-arrow-left');
    const rightBtn = document.querySelector('.nav-arrow-right');
    const homeLink = document.getElementById('home-link');
    
    if (!container || !leftBtn || !rightBtn) return;
    
    // CRITICAL: Set scroll position BEFORE any other operations
    const savedPosition = sessionStorage.getItem('menuScrollPos');
    const cameFromHome = sessionStorage.getItem('cameFromHome');
    
    if (savedPosition && !cameFromHome) {
        // Set position IMMEDIATELY without any delay
        container.style.scrollBehavior = 'auto';
        container.scrollLeft = parseInt(savedPosition, 10);
        // Re-enable smooth scrolling after position is set
        setTimeout(() => {
            container.style.scrollBehavior = 'smooth';
        }, 0);
    }
    
    // Clear storage
    sessionStorage.removeItem('menuScrollPos');
    sessionStorage.removeItem('cameFromHome');
    
    // Update buttons WITHOUT animations
    function updateButtons() {
        const scrollLeft = container.scrollLeft;
        const scrollWidth = container.scrollWidth;
        const clientWidth = container.clientWidth;
        const maxScroll = scrollWidth - clientWidth;
        
        // Only show arrows on mobile
        const isMobile = window.innerWidth <= 768;
        
        if (!isMobile || maxScroll <= 0) {
            leftBtn.style.visibility = 'hidden';
            rightBtn.style.visibility = 'hidden';
            return;
        }
        
        // Use visibility instead of display to prevent layout shift
        leftBtn.style.visibility = scrollLeft > 10 ? 'visible' : 'hidden';
        rightBtn.style.visibility = scrollLeft < maxScroll - 10 ? 'visible' : 'hidden';
    }
    
    // Save position when clicking menu links
    container.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (link) {
            sessionStorage.setItem('menuScrollPos', container.scrollLeft);
        }
    });
    
    // Home link clears position
    if (homeLink) {
        homeLink.addEventListener('click', function() {
            sessionStorage.setItem('cameFromHome', 'true');
        });
    }
    
    // Scroll function
    function scrollMenu(direction) {
        const scrollAmount = 250;
        container.scrollBy({
            left: direction === 'left' ? -scrollAmount : scrollAmount,
            behavior: 'smooth'
        });
    }
    
    // Arrow clicks
    leftBtn.onclick = (e) => {
        e.preventDefault();
        scrollMenu('left');
    };
    
    rightBtn.onclick = (e) => {
        e.preventDefault();
        scrollMenu('right');
    };
    
    // Update on scroll
    container.addEventListener('scroll', updateButtons, { passive: true });
    
    // Update on resize
    window.addEventListener('resize', updateButtons, { passive: true });
    
    // Initial update
    updateButtons();
});