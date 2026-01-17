<?php

require __DIR__ . '/../vendor/autoload.php';
use Dotenv\Dotenv;

use Google\Client;
use Google\Service\Gmail;
use Google\Service\Gmail\Message;


$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";
$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

$query = "SELECT 
            b.`bidder ID`, b.bid_amount, p.`Product ID`, p.`Farmer ID`, 
            f.email AS farmer_email, f.phone AS farmer_phone, f.username AS farmer_name, 
            bd.email AS bidder_email, bd.phone AS bidder_phone, bd.username AS bidder_name
          FROM bids b
          JOIN product p ON b.`Product ID` = p.`Product ID`
          JOIN farmer f ON p.`Farmer ID` = f.`Farmer ID`
          JOIN bidder bd ON b.`bidder ID` = bd.`bidder ID`
          WHERE p.endTime < NOW() 
          AND b.bid_amount = (SELECT MAX(bid_amount) FROM bids WHERE `Product ID` = p.`Product ID`)";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $product_id = $row['Product ID'];
        $bidder_email = $row['bidder_email'];
        $bidder_name = $row['bidder_name'];
        $bidder_phone = $row['bidder_phone'];
        $farmer_name = $row['farmer_name'];
        $farmer_email = $row['farmer_email'];
        $farmer_phone = $row['farmer_phone'];

        if (sendEmailToBidder($bidder_email, $bidder_name, $farmer_name, $farmer_email, $farmer_phone)) {
            
            sendEmailToFarmer($farmer_email, $farmer_name, $bidder_name, $bidder_email, $bidder_phone);
            
            $deleteBids = $conn->prepare("DELETE FROM bids WHERE `Product ID` = ?");
            $deleteBids->bind_param("i", $product_id);
            if ($deleteBids->execute()) {
                echo "✅ Bids for Product ID $product_id deleted successfully.<br>";
            } else {
                echo "❌ Error deleting bids: " . $deleteBids->error . "<br>";
            }
            $deleteBids->close();

            $deleteProduct = $conn->prepare("DELETE FROM product WHERE `Product ID` = ?");
            $deleteProduct->bind_param("i", $product_id);
            if ($deleteProduct->execute()) {
                echo "✅ Product ID $product_id deleted successfully.<br>";
            } else {
                echo "❌ Error deleting product: " . $deleteProduct->error . "<br>";
            }
            $deleteProduct->close();
        }
    }
} else {
    echo "No expired bids found.";
}

$conn->close();


function sendEmailToBidder($bidder_email, $bidder_name, $farmer_name, $farmer_email, $farmer_phone) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $client = new Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->setAccessType('offline');
    $client->addScope(Gmail::GMAIL_SEND);

    if (!empty($_ENV['GOOGLE_TOKEN'])) {
        $client->setAccessToken(json_decode($_ENV['GOOGLE_TOKEN'], true));
    } else {
        die("❌ Token not found in .env. Please authenticate using Gmail API.");
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            // Manual step: update GOOGLE_TOKEN in .env with the new token
            echo "⚠️ Token refreshed. Please update GOOGLE_TOKEN in your .env file:<br><textarea rows='10' cols='100'>" . json_encode($client->getAccessToken()) . "</textarea>";
        } else {
            die("❌ Token expired and no refresh token found.");
        }
    }

    $service = new Gmail($client);

    $messageBody = "Hello $bidder_name,\n\n".
                   "🎉 Congratulations! You are the winning bidder.\n\n".
                   "📌 Farmer Contact Details:\n".
                   "👤 Name: $farmer_name\n".
                   "📧 Email: $farmer_email\n".
                   "📞 Phone: $farmer_phone\n\n".
                   "Please contact the farmer to complete the deal.";

    $rawMessageString = "From: your_email@gmail.com\r\n";
    $rawMessageString .= "To: $bidder_email\r\n";
    $rawMessageString .= "Subject: Winning Bid Confirmation\r\n\r\n";
    $rawMessageString .= $messageBody;

    $encodedMessage = base64_encode($rawMessageString);
    $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], $encodedMessage);

    $message = new Message();
    $message->setRaw($encodedMessage);

    try {
        $service->users_messages->send('me', $message);
        echo "✅ Email sent successfully to $bidder_email.<br>";
        return true;
    } catch (Exception $e) {
        echo "❌ Error sending email to bidder: " . $e->getMessage() . "<br>";
        return false;
    }
}

function sendEmailToFarmer($farmer_email, $farmer_name, $bidder_name, $bidder_email, $bidder_phone) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    $client = new Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->setAccessType('offline');
    $client->addScope(Gmail::GMAIL_SEND);

    if (!empty($_ENV['GOOGLE_TOKEN'])) {
        $client->setAccessToken(json_decode($_ENV['GOOGLE_TOKEN'], true));
    } else {
        die("❌ Token not found in .env. Please authenticate using Gmail API.");
    }

    if ($client->isAccessTokenExpired()) {
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            // Manual step: update GOOGLE_TOKEN in .env with the new token
            echo "⚠️ Token refreshed. Please update GOOGLE_TOKEN in your .env file:<br><textarea rows='10' cols='100'>" . json_encode($client->getAccessToken()) . "</textarea>";
        } else {
            die("❌ Token expired and no refresh token found.");
        }
    }

    $service = new Gmail($client);

    $messageBody = "Hello $farmer_name,\n\n".
                   "🚜 Your auction has ended, and a bidder has won!\n\n".
                   "📌 Winning Bidder Details:\n".
                   "👤 Name: $bidder_name\n".
                   "📧 Email: $bidder_email\n".
                   "📞 Phone: $bidder_phone\n\n".
                   "Please contact the bidder to complete the deal.";

    $rawMessageString = "From: your_email@gmail.com\r\n";
    $rawMessageString .= "To: $farmer_email\r\n";
    $rawMessageString .= "Subject: Winning Bidder Notification\r\n\r\n";
    $rawMessageString .= $messageBody;

    $encodedMessage = base64_encode($rawMessageString);
    $encodedMessage = str_replace(['+', '/', '='], ['-', '_', ''], $encodedMessage);

    $message = new Message();
    $message->setRaw($encodedMessage);

    try {
        $service->users_messages->send('me', $message);
        echo "✅ Email sent successfully to farmer $farmer_email.<br>";
        return true;
    } catch (Exception $e) {
        echo "❌ Error sending email to farmer: " . $e->getMessage() . "<br>";
        return false;
    }
}
?>
