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
    
    // Check if content overflows (like YouTube)
    function checkOverflow() {
        const scrollWidth = container.scrollWidth;
        const clientWidth = container.clientWidth;
        const hasOverflow = scrollWidth > clientWidth;
        
        // Add class to container for CSS styling
        if (hasOverflow) {
            container.classList.add('has-overflow');
        } else {
            container.classList.remove('has-overflow');
        }
        
        return hasOverflow;
    }
    
    // Update buttons based on scroll position and overflow
    function updateButtons() {
        const hasOverflow = checkOverflow();
        const scrollLeft = container.scrollLeft;
        const scrollWidth = container.scrollWidth;
        const clientWidth = container.clientWidth;
        const maxScroll = scrollWidth - clientWidth;
        
        // Only show arrows if there's overflow (like YouTube)
        if (!hasOverflow) {
            leftBtn.classList.remove('show');
            rightBtn.classList.remove('show');
            return;
        }
        
        // Show/hide arrows based on scroll position
        if (scrollLeft > 10) {
            leftBtn.classList.add('show');
        } else {
            leftBtn.classList.remove('show');
        }
        
        if (scrollLeft < maxScroll - 10) {
            rightBtn.classList.add('show');
        } else {
            rightBtn.classList.remove('show');
        }
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
    window.addEventListener('resize', function() {
        updateButtons();
    }, { passive: true });
    
    // Initial update
    updateButtons();
    
    // Check overflow on font load (in case custom fonts affect width)
    if ('fonts' in document) {
        document.fonts.ready.then(function() {
            updateButtons();
        });
    }
});