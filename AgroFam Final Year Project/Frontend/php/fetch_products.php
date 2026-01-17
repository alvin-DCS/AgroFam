<?php
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$query = "SELECT `Product ID`, `Prod-Name`, `Quantity`, `Bid-Price`, `endTime`, `ImagePath` FROM product WHERE `endTime` >= NOW() ORDER BY `endTime` ASC";
$result = $conn->query($query);

$products = [];

while ($row = $result->fetch_assoc()) {
    $products[] = [
        "id" => $row["Product ID"],
        "name" => $row["Prod-Name"],
        "quantity" => $row["Quantity"],
        "bid_price" => $row["Bid-Price"],
        "end_time" => $row["endTime"],
        "image" => "/Frontend/" . $row["ImagePath"]
    ];
}

echo json_encode($products);

$conn->close();
?>