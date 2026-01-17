<?php
session_start();

if (!isset($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
    die("OTP not verified. Please verify OTP first.");
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "registration";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $plain_password = $_POST['password'];
    $hashed_password = password_hash($plain_password, PASSWORD_BCRYPT); 
    $address = $_POST['address'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
     

    $sql = "SELECT password FROM bidder";
    $result = $conn->query($sql);

    $password_exists = false;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            
            if (password_verify($plain_password, $row['password'])) {
                $password_exists = true;
                break;
            }
        }
    }

    
    if ($password_exists) {
        echo "<script>
            alert('Password already exists. Please choose a different password.');
            window.history.back(); // Go back to the previous page
        </script>";
        exit;
    }

    
    $sql = "INSERT INTO bidder (username, password, address, email, phone)
            VALUES (?, ?, ?, ?, ?)";

    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $hashed_password, $address, $email, $phone);

    
    if ($stmt->execute()) {
        header("Location: /Frontend/index.html?status=success");
    } else {
        echo "Error: " . $stmt->error;
    }

    
    $stmt->close();
}

$conn->close();
?>
