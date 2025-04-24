<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$gameId = $_GET['gameId'] ?? '';
if (empty($gameId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Game ID required']);
    exit;
}

$apiUrl = "https://api-web.nhle.com/v1/gamecenter/{$gameId}/boxscore";
$opts = [
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3'
        ]
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($opts);
$response = file_get_contents($apiUrl, false, $context);

if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch data']);
    exit;
}

echo $response;