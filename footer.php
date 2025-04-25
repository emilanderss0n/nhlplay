<div class="support-banner">
    <div class="content">
        <h3>Enjoying NHLPLAY?</h3>
        <p>Enjoying the ad-free experience? Consider supporting NHLPLAY.online with a small donation. This site is 100% non-profit and run with love for the game.</p>
        <div class="content-links">
            <a href="https://ko-fi.com/moxopixel" target="_blank" rel="noopener noreferrer" class="btn"><i class="bi bi-cup-hot-fill"></i> Ko-Fi</a>
            <a href="https://paypal.me/moxopixel" target="_blank" rel="noopener noreferrer" class="btn"><i class="bi bi-paypal"></i> PayPal</a>
        </div>
    </div>
</div>
<footer>
    <div class="wrapper">
        <div class="footer-info">Copyright © <?php echo date('Y'); ?> <span>/</span> <strong>NHLPLAY</strong> <span>/</span> <a class="social-btn-twitter" href="https://twitter.com/NHLPlayOnline" target="_blank"><i class="bi bi-twitter"></i> Follow</a></div>
        <div class="credit">Created by <a href="https://emils.graphics" target="_blank">emils.graphics</a></div>
    </div>
</footer>

<?php if (!$detect->isMobile()) { ?><script src="assets/js/datatables.min.js" defer></script><?php } ?>
<script src="assets/js/swiper.js" defer></script>
<script src="assets/js/chart.js" defer></script>
<script type="module" src="assets/js/global.js"></script>

<script>
    ajaxPath = '<?= BASE_URL ?>/ajax/'; 
    var season = '<?= $season ?>';

    document.addEventListener("DOMContentLoaded", function() {
        <?php if (!$detect->isMobile()) { ?>
        let dt = new jsdatatables.JSDataTable('#leagueTable', {
            paging: false,
            searchable: true,
        });
        <?php } ?>
    });

    // Only run in development environment
    if (window.location.hostname === 'localhost') {
        // Use the correct path for the event source
        const evtSource = new EventSource('<?= BASE_URL ?>/event-stream.php');
        
        let reloadTimer = null;
        let lastReloadTime = 0;
        const RELOAD_THRESHOLD = 2000; // Minimum time between reloads (ms)
        
        evtSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                
                if (data.reload) {
                    console.log('File changed:', data.file);
                    const currentTime = new Date().getTime();
                    
                    // For CSS files, inject new stylesheet instead of full page reload
                    if (data.file.includes('.css')) {
                        clearTimeout(reloadTimer);
                        
                        // Force reload all stylesheets by adding/removing them
                        const links = Array.from(document.getElementsByTagName('link'));
                        for (let i = 0; i < links.length; i++) {
                            const link = links[i];
                            if (link.rel === 'stylesheet') {
                                // Skip Google Fonts links, we'll handle them separately
                                if (link.href.includes('fonts.googleapis.com')) {
                                    continue;
                                }
                                
                                // Create a new link element
                                const newLink = document.createElement('link');
                                newLink.rel = 'stylesheet';
                                newLink.type = 'text/css';
                                newLink.href = link.href.split('?')[0] + '?v=' + currentTime;
                                
                                // Add the new link before removing the old one
                                link.parentNode.insertBefore(newLink, link.nextSibling);
                                
                                // Remove the old link after a small delay
                                setTimeout(() => {
                                    link.parentNode.removeChild(link);
                                }, 100);
                            }
                        }
                        
                        // Force reload Google Fonts by removing and re-adding them
                        const googleFonts = Array.from(document.querySelectorAll('link[href*="fonts.googleapis.com"]'));
                        if (googleFonts.length > 0) {
                            // First store all Google Font URLs with their full original href
                            const fontLinks = googleFonts.map(link => {
                                return {
                                    rel: link.rel,
                                    href: link.href,
                                    as: link.getAttribute('as'),
                                    crossOrigin: link.getAttribute('crossorigin')
                                };
                            });
                            
                            // Remove the current Google Font links
                            googleFonts.forEach(link => {
                                link.parentNode.removeChild(link);
                            });
                            
                            // Re-add Google Fonts with their original URLs and attributes
                            setTimeout(() => {
                                fontLinks.forEach(fontLink => {
                                    const newFontLink = document.createElement('link');
                                    newFontLink.rel = fontLink.rel;
                                    
                                    // Keep the original URL structure (preserving any query parameters)
                                    if (fontLink.href.includes('?')) {
                                        // URL already has parameters, make sure we don't add duplicate v= params
                                        if (fontLink.href.includes('v=')) {
                                            // Replace the v= parameter with our new timestamp
                                            newFontLink.href = fontLink.href.replace(/v=\d+/, 'v=' + currentTime);
                                        } else {
                                            // Append our v= parameter
                                            newFontLink.href = fontLink.href + '&v=' + currentTime;
                                        }
                                    } else {
                                        // URL has no parameters, add our v= parameter
                                        newFontLink.href = fontLink.href + '?v=' + currentTime;
                                    }
                                    
                                    // Restore other attributes if they existed
                                    if (fontLink.as) newFontLink.setAttribute('as', fontLink.as);
                                    if (fontLink.crossOrigin) newFontLink.setAttribute('crossorigin', fontLink.crossOrigin);
                                    
                                    document.head.appendChild(newFontLink);
                                });
                            }, 50);
                        }
                    } else if (currentTime - lastReloadTime > RELOAD_THRESHOLD) {
                        // For non-CSS files, do a full page reload
                        // But only if it's been at least RELOAD_THRESHOLD ms since last reload
                        lastReloadTime = currentTime;
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Error processing event:', error);
            }
        };
        
        evtSource.onerror = function(error) {
            console.error('EventSource error:', error);
            // Close the connection to prevent automatic reconnection
            evtSource.close();
        };
    }
</script>
</body>
</html>