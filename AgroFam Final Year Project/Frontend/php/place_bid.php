<?php
session_start();

if (!isset($_SESSION['bidder_id'])) {
    die("Please log in to place a bid.");
}

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $bid_amount = $_POST['bid_amount'];
    $bidder_id = $_SESSION['bidder_id'];

    
    $stmt = $conn->prepare("SELECT `Bid-Price` FROM product WHERE `Product ID` = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows == 0) {
        $_SESSION['message'] = "Product not found.";
    } else {
        $product = $result->fetch_assoc();
        $current_bid_price = $product['Bid-Price'];

        if ($current_bid_price < 1000) {
            $min_increment = 10;
        } elseif ($current_bid_price < 10000) {
            $min_increment = 50;
        } elseif ($current_bid_price < 50000) {
            $min_increment = 250;
        } else {
            $min_increment = 1000;
        }

        $next_min_bid = $current_bid_price + $min_increment;

        if ($bid_amount < $next_min_bid) {
            $_SESSION['message'] = "Your bid must be at least ₹$min_increment higher than ₹$current_bid_price.";
        } else {
            $stmt = $conn->prepare("INSERT INTO bids (`Product ID`, `bidder ID`, bid_amount) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $product_id, $bidder_id, $bid_amount);
            $stmt->execute();
            $stmt->close();
            $update_stmt = $conn->prepare("UPDATE product SET `Bid-Price` = ? WHERE `Product ID` = ?");
            $update_stmt->bind_param("ii", $bid_amount, $product_id);
            $update_stmt->execute();
            $update_stmt->close();

            $_SESSION['message'] = "Bid placed successfully!";
        }
    }

    header('Location: bid_page.php?product_id=' . $product_id);
    exit();
}

$conn->close();
?>
