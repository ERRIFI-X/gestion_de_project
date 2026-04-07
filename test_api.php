<?php

$url = "http://localhost/gestiondeproject/index.php?page=auth&action=login";
$data = [
    'username' => 'admin',
    'password' => 'admin123'
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Login failed!";
} else {
    $response = json_decode($result, true);
    echo "Login successful!\n";
    echo "Token: " . $response['token'] . "\n";
    
    // Save token to an artifact or local file for subsequent requests
    file_put_contents('token.txt', $response['token']);
    $token = $response['token'];

    // Test GET /servers
    echo "\nTesting GET /servers...\n";
    $servers_url = "http://localhost/gestiondeproject/index.php?page=servers";
    $server_options = [
        'http' => [
            'header'  => "Authorization: Bearer $token\r\n",
            'method'  => 'GET'
        ]
    ];
    $server_context  = stream_context_create($server_options);
    $server_result = file_get_contents($servers_url, false, $server_context);
    
    if ($server_result === FALSE) {
        echo "Failed to fetch servers!\n";
    } else {
        $servers = json_decode($server_result, true);
        echo "Successfully fetched " . count($servers) . " servers.\n";
        print_r($servers);
    }
}
