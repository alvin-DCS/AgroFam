<?php

require __DIR__ . '/../vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;
use Dotenv\Dotenv;

// Load .env variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$client = new Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope(Gmail::GMAIL_SEND);
$client->setAccessType('offline');
$client->setPrompt('consent');

if (isset($_GET['code'])) {
    $authCode = $_GET['code'];
    $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
    if (isset($accessToken['error'])) {
        die("❌ Authentication failed: " . $accessToken['error_description']);
    }
    $existingToken = !empty($_ENV['GOOGLE_TOKEN']) ? json_decode($_ENV['GOOGLE_TOKEN'], true) : [];
    $updatedToken = array_merge($existingToken, $accessToken);
    // Manual step: update GOOGLE_TOKEN in .env with the new token
    echo "✅ Authentication successful! Please update GOOGLE_TOKEN in your .env file with this token:<br><textarea rows='10' cols='100'>" . json_encode($updatedToken) . "</textarea>";
    header("Refresh: 2; URL=http://localhost/Frontend/php/process_expired_bids.php");
    exit;
} else {
    $authUrl = $client->createAuthUrl();
    echo "<h3>Open this link in your browser to authorize:</h3>";
    echo "<a href='$authUrl' target='_blank'>$authUrl</a>";
}
?>
