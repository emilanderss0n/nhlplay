/**
 * YouTube Videos Module
 * Handles displaying YouTube videos from the NHL channel
 */

export function initYouTubeVideos() {
    const container = document.getElementById("videos");
    
    if (!container) {
        console.warn('YouTube videos container not found');
        return;
    }

    // Create modal if it doesn't exist
    createVideoModal();

    // Check if video data was provided by PHP
    if (typeof window.youtubeVideosData !== 'undefined' && window.youtubeVideosData.items) {
        renderYouTubeVideos(window.youtubeVideosData.items, container);
    } else {
        console.warn('No YouTube video data available');
        container.innerHTML = '<div class="alert info">No videos available at the moment</div>';
    }
}

function renderYouTubeVideos(videos, container) {
    container.innerHTML = ''; // Clear any existing content

    // Build Swiper structure
    const swiperEl = document.createElement('div');
    swiperEl.className = 'swiper youtube-swiper';

    const wrapper = document.createElement('div');
    wrapper.className = 'swiper-wrapper';
    swiperEl.appendChild(wrapper);

    // Navigation & pagination elements (scoped inside container)
    const prevBtn = document.createElement('button');
    prevBtn.className = 'youtube-swiper-prev swiper-button-prev';
    prevBtn.setAttribute('aria-label', 'Previous videos');

    const nextBtn = document.createElement('button');
    nextBtn.className = 'youtube-swiper-next swiper-button-next';
    nextBtn.setAttribute('aria-label', 'Next videos');

    const pagination = document.createElement('div');
    pagination.className = 'youtube-swiper-pagination swiper-pagination';

    // Create slides
    videos.forEach(item => {
        if (!item.snippet || !item.snippet.resourceId) {
            return; // Skip invalid items
        }

        const videoId = item.snippet.resourceId.videoId;
        const title = stripEmoji(item.snippet.title || '');
        const thumbnail = item.snippet.thumbnails?.standard?.url || item.snippet.thumbnails?.high?.url;
        const channelTitle = item.snippet.channelTitle || '';

        const slideEl = document.createElement('div');
        slideEl.className = 'swiper-slide';

        const videoElement = createVideoElement(videoId, title, thumbnail, channelTitle);
        // Keep existing card markup inside the slide
        slideEl.appendChild(videoElement);

        wrapper.appendChild(slideEl);
    });

    // Append navigation/pagination and swiper to container
    swiperEl.appendChild(pagination);
    swiperEl.appendChild(prevBtn);
    swiperEl.appendChild(nextBtn);
    container.appendChild(swiperEl);

    // Initialize Swiper if available. If not yet loaded, retry on window load and poll briefly.
    function tryInitSwiper() {
        if (typeof Swiper === 'undefined') return false;

        try {
            new Swiper(swiperEl, {
                slidesPerView: 1.1,
                spaceBetween: 12,
                centeredSlides: false,
                loop: false,
                lazy: {
                    loadPrevNext: true,
                },
                keyboard: {
                    enabled: true,
                },
                breakpoints: {
                    640: { slidesPerView: 2.05, spaceBetween: 12 },
                    900: { slidesPerView: 3.05, spaceBetween: 14 }
                },
                pagination: {
                    el: pagination,
                    clickable: true,
                },
                navigation: {
                    nextEl: nextBtn,
                    prevEl: prevBtn,
                }
            });
            return true;
        } catch (e) {
            console.warn('Failed to initialize Swiper for YouTube videos', e);
            return false;
        }
    }

    if (!tryInitSwiper()) {
        // If Swiper isn't available yet, wait for the window load event (external deferred scripts will have run)
        window.addEventListener('load', () => {
            if (!tryInitSwiper()) {
                // As a final fallback, poll a few times in case of timing edge-cases
                let attempts = 0;
                const maxAttempts = 6; // ~3s
                const interval = setInterval(() => {
                    attempts += 1;
                    if (tryInitSwiper() || attempts >= maxAttempts) {
                        clearInterval(interval);
                    }
                }, 500);
            }
        });
    }
}

function createVideoElement(videoId, title, thumbnail, channelTitle) {
    const videoDiv = document.createElement('div');
    videoDiv.className = 'youtube-video-card';
    videoDiv.tabIndex = 0;
    
    videoDiv.innerHTML = `
        <div class="video-thumbnail-container">
            <img src="${thumbnail}" alt="${title}" class="video-thumbnail" loading="lazy">
        </div>
        <h4 class="video-title">${title}</h4>
    `;
    
    // Add click handler to open modal
    const thumbnailContainer = videoDiv.querySelector('.video-thumbnail-container');
    
    // Also allow clicking the thumbnail image to open modal
    thumbnailContainer.addEventListener('click', () => {
        openVideoModal(videoId, channelTitle);
    });
    
    return videoDiv;
}

function createVideoModal() {
    // Check if modal already exists
    if (document.getElementById('youtube-video-modal')) {
        return;
    }

    // Add basic dialog styles if not already added
    if (!document.getElementById('youtube-modal-styles')) {
        const style = document.createElement('style');
        style.id = 'youtube-modal-styles';
        document.head.appendChild(style);
    }

    const dialog = document.createElement('dialog');
    dialog.id = 'youtube-video-modal';
    dialog.innerHTML = `
        <div class="modal-header">
            <h3 class="modal-title"></h3>
            <button class="modal-close" aria-label="Close video">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <div class="youtube-video-container"></div>
        </div>
    `;

    document.body.appendChild(dialog);

    // Add event listeners
    const closeBtn = dialog.querySelector('.modal-close');
    
    closeBtn.addEventListener('click', () => {
        dialog.close();
    });
    
    // Close on clicking outside (native dialog backdrop click)
    dialog.addEventListener('click', (e) => {
        if (e.target === dialog) {
            dialog.close();
        }
    });
    
    // Handle dialog close event
    dialog.addEventListener('close', () => {
        const videoContainer = dialog.querySelector('.youtube-video-container');
        // Clear iframe to stop video
        videoContainer.innerHTML = '';
    });
}

function openVideoModal(videoId, channelTitle) {
    const dialog = document.getElementById('youtube-video-modal');
    const modalTitle = dialog.querySelector('.modal-title');
    const videoContainer = dialog.querySelector('.youtube-video-container');
    
    // Set title
    modalTitle.textContent = channelTitle;
    
    // Create iframe
    const iframe = document.createElement('iframe');
    iframe.width = "100%";
    iframe.height = "400";
    iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1&rel=0`;
    iframe.frameBorder = "0";
    iframe.allowFullscreen = true;
    iframe.allow = "accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture";
    
    // Clear previous content and add iframe
    videoContainer.innerHTML = '';
    videoContainer.appendChild(iframe);
    
    // Show modal using native dialog method
    dialog.showModal();
}

// Small helper: remove emoji characters from a string while preserving other characters.
function stripEmoji(str) {
    if (!str) return '';
    try {
        // Prefer Unicode property if supported
        return str.replace(/\p{Extended_Pictographic}/gu, '').replace(/\uFE0F/g, '').trim();
    } catch (e) {
        // Fallback for environments without Unicode property support
        return str.replace(/[\u2700-\u27BF]|[\uE000-\uF8FF]|[\u1F600-\u1F64F]|[\u1F300-\u1F5FF]|[\u1F680-\u1F6FF]|[\u1F1E6-\u1F1FF]|[\u1F900-\u1F9FF]|[\u1FA70-\u1FAFF]/g, '').replace(/\uFE0F/g, '').trim();
    }
}