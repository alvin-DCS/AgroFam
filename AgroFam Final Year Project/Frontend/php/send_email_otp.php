<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php';

session_start();
$otp = rand(100000, 999999);

$_SESSION['email_otp'] = $otp;
$_SESSION['email'] = $_POST['email']; 

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; 
    $mail->SMTPAuth = true;
    $mail->Username = 'soorajrrr555okl@gmail.com'; 
    $mail->Password = 'ygnsnmffhrdlnpga'; 
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->setFrom('soorajofficial123@gmail.com', 'AgroFam');
    $mail->addAddress($_POST['email']); 
    $mail->Subject = 'Your OTP for Registration';
    $mail->Body = "Your OTP is: $otp. It is valid for 5 minutes.";

    $mail->send();
    echo json_encode(["status" => "success", "message" => "OTP sent successfully"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Error: " . $mail->ErrorInfo]);
}
?>
