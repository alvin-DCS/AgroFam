<?php
session_start();


if (!isset($_GET['product_id'])) {
    die("Product ID is missing.");
}

$product_id = $_GET['product_id'];


$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT `Prod-Name`, `Bid-Price`, `Quantity` FROM product WHERE `Product ID` = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Product not found.");
}

$product = $result->fetch_assoc();
$current_bid_price = $product['Bid-Price']; 
$stmt->close();

$stmt = $conn->prepare("SELECT MAX(bid_amount) AS highest_bid FROM bids WHERE `Product ID` = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$bid_result = $stmt->get_result();
$highest_bid = $bid_result->fetch_assoc()['highest_bid'] ?? 0;

$stmt->close();
$conn->close();

$next_min_bid = max($highest_bid, $current_bid_price) + 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid for <?php echo htmlspecialchars($product['Prod-Name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex items-center justify-center h-screen">

<div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
    
    <img src="bidai.jpg" alt="Product Image" class="w-full h-64 object-cover rounded mb-4">
    <h2 class="text-2xl font-bold text-center text-gray-800 mb-4"><?php echo htmlspecialchars($product['Prod-Name']); ?></h2>
    
    <p class="text-sm text-gray-600 mb-4">Bid Price (Latest): ₹<strong><?php echo $current_bid_price; ?></strong></p>
    <p class="text-sm text-gray-600 mb-4">Quantity Available: <?php echo htmlspecialchars($product['Quantity']); ?> Kilos</p>
    
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-info bg-blue-500 text-white p-4 rounded mb-4">
            <?php
            echo htmlspecialchars($_SESSION['message']);
            unset($_SESSION['message']); 
            ?>
        </div>
    <?php endif; ?>

    <form action="place_bid.php" method="POST">
        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">

        <div class="mb-4">
            <label for="bid_amount" class="block text-sm text-gray-600">Your Bid Amount (₹):</label>
            <input type="number" name="bid_amount" id="bid_amount" class="w-full px-4 py-2 border rounded" required min="<?php echo $next_min_bid; ?>" placeholder="Enter your bid" />
        </div>

        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded w-full hover:bg-blue-700">Place Bid</button>
    </form>

    <p class="text-center text-sm text-gray-600 mt-4">
        <a href="../bidding.html" class="text-blue-600 underline">Back to Products</a>
    </p>
</div>

</body>
</html>
