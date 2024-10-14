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
        //Recipients
        $mail->setFrom('csustore@gmail.com', 'CSU STORE');
        $mail->addAddress($email); // Add recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
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

    // Check the action
    if (isset($_GET['action']) && $_GET['action'] === 'login') {

        // Get the raw POST data and decode the JSON
        $data = json_decode(file_get_contents('php://input'), true);

        // Check if email and password are provided
        if (!empty($data['email']) && !empty($data['password'])) {
            $email = $data['email'];
            $password = $data['password'];

            // Check if the user exists
            $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verify the password
                if (password_verify($password, $user['password'])) {

                    // Generate a unique session token
                    $session_token = bin2hex(random_bytes(32)); // 64 characters

                    // Set expiration time for the session (e.g., 1 hour)
                    $session_expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

                    // Store the session token in the database
                    $stmt = $conn->prepare("INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param('iss', $user['id'], $session_token, $session_expires_at);

                    if ($stmt->execute()) {
                        // Store session token in the session and send it to the client
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['session_token'] = $session_token;

                        echo json_encode(['message' => 'Login successful', 'session_token' => $session_token, 'user_id' => $user['id']]);
                    } else {
                        echo json_encode(['error' => 'Failed to create session token']);
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

    } elseif (isset($_GET['action']) && $_GET['action'] === 'logout') {
        // Logout the user and destroy the session
        session_unset(); // Remove all session variables
        session_destroy(); // Destroy the session

        echo json_encode(['message' => 'Logout successful']);
    } else {
        echo json_encode(['error' => 'Invalid action']);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}

?>
