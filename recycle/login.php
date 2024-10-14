<?php

require 'db.php';
require 'vendor/autoload.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'balotcfpro@gmail.com';
        $mail->Password = 'opgq cepd tibf cved';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom('csustore@gmail.com', 'CSU STORE');
        $mail->addAddress($email); 

        
        $mail->isHTML(true); 
        $mail->Subject = 'Your Login Verification Code';
        $mail->Body = "Your verification code is: {$code}";

        $mail->send();
        echo 'Message has been sent';
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        echo json_encode(['error' => 'Failed to send verification code']);
    }
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

   
    if (isset($_GET['action']) && $_GET['action'] === 'login') {

        
        $data = json_decode(file_get_contents('php://input'), true);

        // Check if email and password are provided
        if (!empty($data['email']) && !empty($data['password'])) {
            $email = $data['email'];
            $password = $data['password'];

            
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $user['password'])) {

                    date_default_timezone_set('UTC');

                    
                    $verification_code = random_int(100000, 999999);

                    
                    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    
                    $stmt = $conn->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param('iss', $user['id'], $verification_code, $expires_at);

                    if ($stmt->execute()) {
                       
                        sendVerificationEmail($email, $verification_code);
                        echo json_encode(['message' => 'Verification code sent', 'user_id' => $user['id']]);
                    } else {
                        echo json_encode(['error' => 'Failed to generate verification code']);
                    }
                } else {
                    echo json_encode(['error' => 'Invalid password']);
                }
            } else {
                echo json_encode(['error' => 'User not found']);
            }
        } else {
            echo json_encode(['error' => 'Invalid input data']);
        }

    } elseif (isset($_GET['action']) && $_GET['action'] === 'verify_code') {

        // Get the raw POST data and decode the JSON
        $data = json_decode(file_get_contents('php://input'), true);

        if (!empty($data['user_id']) && !empty($data['verification_code'])) {
            $user_id = $data['user_id'];
            $verification_code = $data['verification_code'];

            // Check if the verification code is valid
            $stmt = $conn->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
            $stmt->bind_param('is', $user_id, $verification_code);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Verification code is valid and not expired
                echo json_encode(['message' => 'Verification successful']);
            } else {
                // Code is either incorrect or expired
                echo json_encode(['error' => 'Invalid or expired verification code']);
            }
        } else {
            echo json_encode(['error' => 'Invalid input data']);
        }

    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

?>
