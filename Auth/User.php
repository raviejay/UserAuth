<?php

namespace Auth;

require '../vendor/autoload.php';

use database\Database;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserAuthentication
{
    private $conn;
    private $mail;

    public function __construct(Database $db)
    {

        $this->conn = $db->getConnection();
        $this->mail = new PHPMailer(true);
    }

    private function checkVerificationExpiration($user_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND expires_at > NOW()");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return ['valid' => true, 'code' => $result->fetch_assoc()['code']];
        } else {
            return ['valid' => false];
        }
    }


    // Send Verification Email
    private function sendVerificationEmail($email, $code)
    {
        try {
            $this->mail->isSMTP();
            $this->mail->Host = 'smtp.gmail.com';
            $this->mail->SMTPAuth = true;
            $this->mail->Username = 'balotcfpro@gmail.com';
            $this->mail->Password = 'opgq cepd tibf cved';
            $this->mail->SMTPSecure = 'tls';
            $this->mail->Port = 587;

            $this->mail->setFrom('csustore@gmail.com', 'CSU STORE');
            $this->mail->addAddress($email);

            $this->mail->isHTML(true);
            $this->mail->Subject = 'Your Login Verification Code';
            $this->mail->Body = "Your verification code is: {$code}";

            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}");
            return false;
        }
    }

    // Handle Login
    public function login($email, $password)
    {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                // Check if valid pa ang verification code
                $verificationStatus = $this->checkVerificationExpiration($user['id']);
                if ($verificationStatus['valid']) {

                    return ['message' => 'Login successful', 'user_id' => $user['id'], 'verification_code' => $verificationStatus['code']];
                } else {
                    // If di ang valid code mag create nag new verification code
                    date_default_timezone_set('UTC');

                    $verification_code = random_int(100000, 999999);
                    $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                    $stmt = $this->conn->prepare("INSERT INTO verification_codes (user_id, code, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param('iss', $user['id'], $verification_code, $expires_at);

                    if ($stmt->execute()) {
                        if ($this->sendVerificationEmail($email, $verification_code)) {
                            return ['message' => 'Verification code sent', 'user_id' => $user['id']];
                        } else {
                            return ['error' => 'Failed to send verification code'];
                        }
                    } else {
                        return ['error' => 'Failed to generate verification code'];
                    }
                }
            } else {
                return ['error' => 'Invalid password'];
            }
        } else {
            return ['error' => 'User not found'];
        }
    }


    // Verify Code
    public function verifyCode($user_id, $verification_code)
    {
        $stmt = $this->conn->prepare("SELECT * FROM verification_codes WHERE user_id = ? AND code = ? AND expires_at > NOW()");
        $stmt->bind_param('is', $user_id, $verification_code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            return [
                'message' => 'Verification successful',
                'login_status' => 'Login successfully'
            ];
        } else {
            return ['error' => 'Invalid or expired verification code'];
        }
    }

    public function register($first_name, $last_name, $email, $phone, $address, $password)
    {

        if (empty($first_name) || empty($last_name) || empty($email) || empty($phone) || empty($address) || empty($password)) {
            return ['error' => 'Invalid input data'];
        }


        $hashed_password = password_hash($password, PASSWORD_DEFAULT);


        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param('ss', $email, $phone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // If the user doesn't exist, insert a new record
            $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, phone, address, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssss', $first_name, $last_name, $email, $phone, $address, $hashed_password);

            if ($stmt->execute()) {
                return ['message' => 'User registered successfully'];
            } else {
                return ['error' => 'Failed to register user'];
            }
        } else {
            return ['error' => 'User already exists'];
        }
    }
}
