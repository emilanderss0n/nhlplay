export function initRedditPosts() {
    // Initialize all Reddit feeds on the page
    const redditFeeds = document.querySelectorAll('.reddit-feed');
    redditFeeds.forEach(feedContainer => {
        initRedditFeed(feedContainer);
    });
}

/**
 * Initialize a single Reddit feed container
 * @param {HTMLElement} feedContainer - The feed container element
 */
function initRedditFeed(feedContainer) {
    if (!feedContainer) return;
    
    // Create an Intersection Observer to detect when reddit-feed comes into view
    const redditFeedObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // If the element is in view
            if (entry.isIntersecting) {
                // Pass the container element to loadRedditFeed
                loadRedditFeed(entry.target);
                // Stop observing after loading once
                observer.unobserve(entry.target);
            }
        });
    }, {
        // Start loading when element is 200px from viewport
        rootMargin: '200px',
        threshold: 0.1
    });
    
    // Start observing the reddit-feed element
    redditFeedObserver.observe(feedContainer);
      // Setup event listeners for post interactions once they're loaded
    function setupPostInteractions(container) {
        if (!container) return;
        
        const redditPosts = container.querySelector('.reddit-posts');
        if (!redditPosts) return;
        
        // Add event listeners for post hover
        const postElements = redditPosts.querySelectorAll(".reddit-post");
        postElements.forEach(post => {
            post.addEventListener('mouseenter', function() {
                this.classList.add('active');
            });
            
            post.addEventListener('mouseleave', function() {
                this.classList.remove('active');
            });
        });
    }
      // Create MutationObserver to watch for when posts are added to the DOM
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                // If posts were added to the DOM, setup interactions
                setupPostInteractions(feedContainer);
            }
        }
    });
    
    // Start observing the reddit-posts container for added nodes
    const redditPosts = feedContainer.querySelector('.reddit-posts');
    if (redditPosts) {
        observer.observe(redditPosts, { childList: true });
    }
      /**
     * Load Reddit posts for a specific feed container
     * @param {HTMLElement} feedContainer - The feed container element
     */
    function loadRedditFeed(feedContainer) {
        if (!feedContainer) return;
        
        const redditPosts = feedContainer.querySelector('.reddit-posts');
        if (!redditPosts) return;
        
        // Show loading indicator
        const loadElement = redditPosts.querySelector('.load');
        if (loadElement) {
            loadElement.style.display = 'flex';
        }        // Get subreddit from data attribute or default to 'hockey'
        const subreddit = feedContainer.dataset.subreddit || 'hockey';
        
        // Get limit from data attribute or default to 12
        const limit = feedContainer.dataset.limit || 12;        // Determine which endpoint to use based on whether it's a team feed or main feed
        const endpoint = feedContainer.id === 'team-reddit-feed-section' ? 'team-reddit-feed.php' : 'reddit-feed.php';
        
        // Determine the correct base URL for the environment
        let baseUrl;
        if (window.location.hostname === 'localhost') {
            baseUrl = '/nhl/ajax/';
        } else {
            baseUrl = '/ajax/';
        }
        
        // Build the final URL
        const ajaxUrl = window.location.origin + baseUrl + endpoint;
        
        // Fetch Reddit posts from our AJAX endpoint
        fetch(`${ajaxUrl}?subreddit=${subreddit}&limit=${limit}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(posts => {
            // Check if we got posts
            if (posts && Array.isArray(posts) && posts.length > 0) {
                let postsHTML = '';
                
                // Generate HTML for each post
                posts.forEach(post => {
                    // Format the timestamp to a readable date
                    const postedTime = new Date(post.created_utc * 1000).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });
                    
                    // Format score with k for thousands
                    let score = post.score;
                    if (score >= 1000) {
                        score = (score / 1000).toFixed(1) + 'k';
                    }
                    
                    // Format number of comments with k for thousands
                    let comments = post.comments;
                    if (comments >= 1000) {
                        comments = (comments / 1000).toFixed(1) + 'k';
                    }
                      // Create post HTML
                    postsHTML += `
                        <div class="reddit-post">
                            <div class="post-header">
                                <div class="post-subreddit">r/${subreddit}</div>
                                <div class="post-time">${postedTime}</div>
                            </div>
                            <a href="https://www.reddit.com${post.permalink}" target="_blank" rel="noopener noreferrer" class="post-link">
                                <h4 class="post-title">${post.title}</h4>
                            </a>
                            <div class="post-footer">
                                <div class="post-author">u/${post.author}</div>
                                <div class="post-stats">
                                    <span class="post-score"><i class="bi bi-arrow-up-circle"></i> ${score}</span>
                                    <span class="post-comments"><i class="bi bi-chat-left-text"></i> ${comments}</span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                // Add posts to container
                redditPosts.innerHTML = postsHTML;
                
                // Apply animation to posts with staggered delay
                const postElements = redditPosts.querySelectorAll('.reddit-post');
                if (postElements.length > 0) {
                    postElements.forEach((post, index) => {
                        post.classList.add('fadeInTop');
                        post.style.animationDelay = `${(index * 0.1) + 0.2}s`;
                    });
                }
            } else {                // Show error message if no posts found
                redditPosts.innerHTML = `
                    <div class="alert info">
                        <div class="alert-content">
                            <i class="bi bi-info-circle"></i>
                            <span>No posts available from r/${subreddit} at the moment. Please try again later or <a href="https://www.reddit.com/r/${subreddit}/" target="_blank" rel="noopener noreferrer">visit r/${subreddit} directly</a>.</span>
                        </div>
                    </div>
                `;            }
        })
        .catch(error => {
            redditPosts.innerHTML = `
                <div class="alert danger">
                    <div class="alert-content">
                        <i class="bi bi-exclamation-triangle"></i>
                        <span>There was an error loading Reddit posts. Please try again later.</span>
                    </div>
                </div>
            `;
        })
        .finally(() => {
            // Hide loading indicator
            if (loadElement) {
                loadElement.style.display = 'none';
            }
        });
    }
      // If posts are already in the DOM (not lazy loaded), set up interactions
    setupPostInteractions(feedContainer);
}

 // Import the Reddit thread handler functions
import { initRedditThreadObservers } from './reddit-thread-handler.js';

// Wait for DOM to be fully loaded
export function initRedditGameThread() {
    // Initialize the Reddit thread observers
    initRedditThreadObservers();
}