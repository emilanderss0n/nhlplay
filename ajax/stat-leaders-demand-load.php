<?php
// Make sure to include path.php first to define BASE_URL
include_once '../path.php';
include_once '../includes/functions.php';

// Check if this is a batch request with multiple categories
if (isset($_POST['batch']) && $_POST['batch'] === 'true') {
    processBatchRequest();
} else {
    // Process single category request (legacy support)
    processSingleRequest();
}

/**
 * Process a batch request for multiple categories
 * Returns a JSON object with each category's HTML content
 */
function processBatchRequest() {
    if (!isset($_POST['categories']) || !isset($_POST['type'])) {
        sendError('Missing required parameters');
        return;
    }
    
    $categories = json_decode($_POST['categories'], true);
    $type = $_POST['type'];
    $season = $_POST['season'];
    $playoffs = isset($_POST['playoffs']) ? filter_var($_POST['playoffs'], FILTER_VALIDATE_BOOLEAN) : false;
    $loadOnDemand = true;
    
    $results = [];
    
    foreach ($categories as $category) {
        $results[$category] = renderStatHolder($type, $category, $season, $playoffs, $loadOnDemand);
    }
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($results);
}

/**
 * Process a single category request
 * Returns HTML content directly
 */
function processSingleRequest() {
    $type = $_POST['type'] ?? null;
    $category = $_POST['category'] ?? null;
    $season = $_POST['season'] ?? null;
    $playoffs = isset($_POST['playoffs']) ? filter_var($_POST['playoffs'], FILTER_VALIDATE_BOOLEAN) : false;
    $loadOnDemand = isset($_POST['loadOnDemand']) ? filter_var($_POST['loadOnDemand'], FILTER_VALIDATE_BOOLEAN) : false;

    if (!$type || !$category) {
        sendError('Missing required parameters');
        return;
    }
    
    $output = renderStatHolder($type, $category, $season, $playoffs, $loadOnDemand);
    echo $output;
}

/**
 * Send error response
 */
function sendError($message) {
    header('HTTP/1.1 400 Bad Request');
    echo '<div class="error">' . htmlspecialchars($message) . '</div>';
}
?>
