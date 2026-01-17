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

    $stmt = $conn->prepare("SELECT * FROM farmer WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $farmer = $result->fetch_assoc();
        $hashedPassword = $farmer['password']; 

        
        if (password_verify($password, $hashedPassword)) {
            
            $_SESSION['farmer'] = $farmer['username']; 

            echo json_encode([
                "status" => "success",
                "farmerId" => $farmer['Farmer ID'],
                "username" => $farmer['username'],
                "email" => $farmer['email'],
                "phone_no" => $farmer['phone'],
                "address" => $farmer['address']
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
