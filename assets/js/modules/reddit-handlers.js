export function initRedditPosts() {
    const redditContainer = document.querySelector('.reddit-feed');
    if (!redditContainer) return;
    
    // Create an Intersection Observer to detect when reddit-feed comes into view
    const redditFeedObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            // If the element is in view
            if (entry.isIntersecting) {
                loadRedditFeed();
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
    const redditFeed = document.querySelector('#reddit-feed-section');
    if (redditFeed) {
        redditFeedObserver.observe(redditFeed);
    }
    
    // Setup event listeners for post interactions once they're loaded
    function setupPostInteractions() {
        const redditPosts = redditContainer.querySelector('.reddit-posts');
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
                setupPostInteractions();
            }
        }
    });
    
    // Start observing the reddit-posts container for added nodes
    const redditPosts = redditContainer.querySelector('.reddit-posts');
    if (redditPosts) {
        observer.observe(redditPosts, { childList: true });
    }
    
    // Function to load Reddit posts via AJAX
    function loadRedditFeed() {
        const redditPosts = document.querySelector('.reddit-posts');
        if (!redditPosts) return;
        
        // Show loading indicator
        const loadElement = redditPosts.querySelector('.load');
        if (loadElement) {
            loadElement.style.display = 'flex';
        }
        
        // Fetch Reddit posts from our AJAX endpoint
        fetch(ajaxPath + 'reddit-feed.php?subreddit=hockey&limit=12', {
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
                                <div class="post-subreddit">r/hockey</div>
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
            } else {
                // Show error message if no posts found
                redditPosts.innerHTML = `
                    <div class="alert info">
                        <div class="alert-content">
                            <i class="bi bi-info-circle"></i>
                            <span>No posts available from r/hockey at the moment. Please try again later or <a href="https://www.reddit.com/r/hockey/" target="_blank" rel="noopener noreferrer">visit r/hockey directly</a>.</span>
                        </div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error fetching Reddit posts:', error);
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
    setupPostInteractions();
}
