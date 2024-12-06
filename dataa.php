<?php
// Get the 'id' from the URL parameters
$url = isset($_GET['id']) ? $_GET['id'] : null;

if ($url) {
    // Extract the video ID from the URL
    function extractIdFromUrl($url) {
        $path = parse_url($url, PHP_URL_PATH); // Extract the path from the URL
        $segments = explode('/', rtrim($path, '/')); // Split the path into segments
        return end($segments); // Return the last segment as the ID
    }

    $id = extractIdFromUrl($url);

    if (!$id) {
        echo 'Error: Could not extract ID from the provided URL.';
        exit;
    }

    // API endpoint for the first request
    $apiUrl = "https://terabox.hnn.workers.dev/api/get-info?shorturl={$id}&pwd=";

    // Initialize a cURL session for the first request
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    // Check for cURL errors
    if ($response === false) {
        echo 'Curl error: ' . curl_error($ch);
        curl_close($ch);
        exit;
    }

    // Decode the JSON response from the first request
    $jsonResult = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON response: ' . json_last_error_msg();
        curl_close($ch);
        exit;
    }

    // Close the cURL session for the first request
    curl_close($ch);

    // Prepare data for the second API request
    $postData = json_encode([
        'shareid' => $jsonResult['shareid'],
        'uk' => $jsonResult['uk'],
        'sign' => $jsonResult['sign'],
        'timestamp' => $jsonResult['timestamp'],
        'fs_id' => $jsonResult['list'][0]['fs_id']
    ]);

    // API endpoint for the second request
    $apiUrl2 = "https://terabox.hnn.workers.dev/api/get-download";

    // Initialize a cURL session for the second request
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $apiUrl2);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'accept: */*',
        'accept-language: en-US,en;q=0.9,hi;q=0.8',
        'content-type: application/json',
        'origin: https://terabox.hnn.workers.dev',
        'priority: u=1, i',
        'referer: https://terabox.hnn.workers.dev/',
        'sec-ch-ua: "Not/A)Brand";v="8", "Chromium";v="126", "Google Chrome";v="126"',
        'sec-ch-ua-mobile: ?0',
        'sec-ch-ua-platform: "macOS"',
        'sec-fetch-dest: empty',
        'sec-fetch-mode: cors',
        'sec-fetch-site: same-origin',
        'user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36'
    ]);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, $postData);

    // Execute the cURL request for the second API
    $response2 = curl_exec($ch2);

    // Check for cURL errors
    if ($response2 === false) {
        echo 'Curl error: ' . curl_error($ch2);
        curl_close($ch2);
        exit;
    }

    // Decode the JSON response from the second request
    $jsonResult2 = json_decode($response2, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo 'Error decoding JSON response: ' . json_last_error_msg();
        curl_close($ch2);
        exit;
    }

    // Close the cURL session for the second request
    curl_close($ch2);

    // Display the JSON result from the second request for debugging purposes
    header('Content-Type: application/json');
    echo json_encode($jsonResult2, JSON_PRETTY_PRINT);

    // Check if the download link is valid
    if (isset($jsonResult2['downloadLink'])) {
        echo "Download Link: " . $jsonResult2['downloadLink'];
        header("Location: ".$jsonResult2['downloadLink']."");
        die();
    } else {
        echo "Error: Download link not found or invalid. Full response: " . json_encode($jsonResult2);
    }
} else {
    echo 'Error: No URL provided.';
}

