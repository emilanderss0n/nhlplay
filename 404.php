<?php
http_response_code(404);

include_once 'path.php';
include_once 'includes/functions.php';

include_once 'header.php';
?>
<style>
    .error-message {
        margin-top: 5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 3rem;
    }
    .error-message h1 {
        font-size: 3rem;
    }
    .error-message .btn {
        margin-top: 1.5rem;
        width: fit-content;
    }
</style>
<main>
    <div class="wrap">
        <div class="error-message not-found">
            <div class="error-icon">
                <img src="<?= BASE_URL ?>/assets/img/puck.gif" alt="404 Not Found" width="200" height="200">
            </div>
            <div class="error-text">
                <h1>404 - Page Not Found</h1>
                <p>Sorry, the page you are looking for does not exist.</p>
                <a href="<?= BASE_URL ?>" class="btn">Go to Home</a>
            </div>
        </div>
    </div>
</main>