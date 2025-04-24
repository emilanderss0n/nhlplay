/**
 * Handle AJAX responses and fix any image paths
 * 
 * @param {string} responseText The HTML response from AJAX
 * @return {string} Fixed HTML with correct image paths
 */
export function fixAjaxResponseUrls(responseText) {
    // Create a temporary div to parse the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = responseText;

    // Fix all img src attributes with problematic paths
    tempDiv.querySelectorAll('img[src*="ajax/assets"], img[src*="pages/assets"]').forEach(img => {
        const correctSrc = img.getAttribute('src')
            .replace('ajax/assets', 'assets')
            .replace('pages/assets', 'assets');
        img.setAttribute('src', correctSrc);
    });

    // Also fix img URLs that might have accumulated double paths during navigation
    tempDiv.querySelectorAll('img[src*="/assets/img/"]').forEach(img => {
        const src = img.getAttribute('src');
        const match = src.match(/^(https?:\/\/[^\/]+)(\/[^\/]+)?(\/assets\/img\/.*)$/);
        if (match) {
            // If we have multiple paths before assets/img, fix it
            const fullDomain = match[1]; // e.g. http://localhost
            const basePath = match[2] || ''; // e.g. /nhl
            const assetPath = match[3]; // e.g. /assets/img/teams/24.svg

            // Only keep one copy of the path
            const correctedUrl = fullDomain + basePath + assetPath;
            img.setAttribute('src', correctedUrl);
        }
    });

    // Fix background images in inline styles
    tempDiv.querySelectorAll('[style*="ajax/assets"], [style*="pages/assets"]').forEach(el => {
        const style = el.getAttribute('style');
        if (style) {
            const fixedStyle = style
                .replace(/ajax\/assets/g, 'assets')
                .replace(/pages\/assets/g, 'assets');
            el.setAttribute('style', fixedStyle);
        }
    });

    return tempDiv.innerHTML;
}
