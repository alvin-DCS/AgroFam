<?php
session_start();

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);


if ($conn->connect_error) {
    exit(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'addProduct') {
    if (!isset($_SESSION['farmer'])) {
        exit(json_encode(["status" => "error", "message" => "Please log in to add products"]));
    }
    $farmerUsername = $_SESSION['farmer'];
    $stmt = $conn->prepare("SELECT `Farmer ID` FROM farmer WHERE username = ?");
    if (!$stmt) {
        exit(json_encode(["status" => "error", "message" => "Database error: " . $conn->error]));
    }
    $stmt->bind_param("s", $farmerUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        exit(json_encode(["status" => "error", "message" => "Farmer not found"]));
    }
    $farmer = $result->fetch_assoc();
    $farmerId = $farmer['Farmer ID'];
    $productName = htmlspecialchars(trim($_POST['productName']));
    $pricePerKilo = filter_var($_POST['pricePerKilo'], FILTER_VALIDATE_FLOAT);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $endTime = $_POST['bidTime'];
    $country = htmlspecialchars(trim($_POST['country']));
    $state = htmlspecialchars(trim($_POST['state']));
    $district = htmlspecialchars(trim($_POST['district']));
    $startTime = date("Y-m-d H:i:s");
    $bidPrice = $pricePerKilo * $quantity;

    if ($pricePerKilo === false || $quantity === false) {
        exit(json_encode(["status" => "error", "message" => "Invalid price or quantity."]));
    }

    if (!isset($_FILES["productImage"]) || $_FILES["productImage"]["error"] !== UPLOAD_ERR_OK) {
        exit(json_encode(["status" => "error", "message" => "No image uploaded or file upload error."]));
    }

    $targetDir = "uploads/";
    if (!file_exists($targetDir) && !mkdir($targetDir, 0775, true)) {
        exit(json_encode(["status" => "error", "message" => "Failed to create upload directory."]));
    }

    $fileName = time() . "_" . basename($_FILES["productImage"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    if ($_FILES["productImage"]["size"] > 5 * 1024 * 1024) {
        exit(json_encode(["status" => "error", "message" => "File size exceeds 5MB."]));
    }

    $mimeType = mime_content_type($_FILES["productImage"]["tmp_name"]);
    $allowedMimeTypes = ["image/jpeg", "image/png", "image/gif"];
    if (!in_array($mimeType, $allowedMimeTypes)) {
        exit(json_encode(["status" => "error", "message" => "Invalid image format."]));
    }

    if (!move_uploaded_file($_FILES["productImage"]["tmp_name"], $targetFilePath)) {
        exit(json_encode(["status" => "error", "message" => "Image upload failed."]));
    }

    $imagePath = "php/uploads/" . $fileName; 

    $stmt = $conn->prepare("INSERT INTO product (`Farmer ID`, `Prod-Name`, `Quantity`, `startTime`, `endTime`, `Country`, `State`, `ImagePath`, `Place`, `Bid-Price`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        exit(json_encode(["status" => "error", "message" => "Database error: " . $conn->error]));
    }

    $stmt->bind_param("isdssssssd", $farmerId, $productName, $quantity, $startTime, $endTime, $country, $state, $imagePath, $district, $bidPrice);
    if ($stmt->execute()) {
        exit(json_encode(["status" => "success", "message" => "Product added successfully"]));
    } else {
        exit(json_encode(["status" => "error", "message" => "Failed to add product"]));
    }
}
$conn->close();
?>

