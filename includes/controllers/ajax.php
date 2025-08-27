<?php
/**
 * AJAX response helpers
 */
function send_json($data, $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json');
    }
    echo json_encode($data);
    exit;
}

function send_error($message, $status = 400)
{
    $payload = ['success' => false, 'error' => $message];
    send_json($payload, $status);
}

function send_success($data = [], $status = 200)
{
    $payload = is_array($data) ? array_merge(['success' => true], $data) : ['success' => true, 'data' => $data];
    send_json($payload, $status);
}
