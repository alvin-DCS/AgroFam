<?php
session_start();

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "";
$dbName = "registration";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbName);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM bidder WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $bidder = $result->fetch_assoc();
        $hashedPassword = $bidder['password'];
        

        
        if (password_verify($password, $hashedPassword)) {
            $_SESSION['bidder'] = $bidder['username'];
            $_SESSION['bidder_id'] = $bidder['bidder ID'];  
            echo json_encode([
                "status" => "success",
                "bidder_id" => $bidder['bidder ID'],
                "username" => $bidder['username'],
                "email" => $bidder['email'],
                "phone_no" => $bidder['phone'],
                "address" => $bidder['address']
            ]);
            
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password"]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "User not found"]);
    }

    $stmt->close();
}

$conn->close();
?>
