<?php
session_start();

$user_otp = $_POST['otp'];

if (isset($_SESSION['email_otp']) && $_SESSION['email_otp'] == $user_otp) {
    $_SESSION['otp_verified'] = true;
    echo json_encode(["status" => "success", "message" => "OTP verified successfully"]);
    unset($_SESSION['email_otp']); 
} else {
    echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
}
?>
