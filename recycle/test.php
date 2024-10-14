<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Make sure you have PHPMailer installed via Composer

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'balotcfpro@gmail.com';
    $mail->Password = 'opgq cepd tibf cved';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Enable verbose debug output
    $mail->SMTPDebug = 2;

    $mail->setFrom('your-email@example.com', 'Your Name');
    $mail->addAddress('puspusraviejay@gmail.com', 'Neverdiet');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body    = 'This is a <b>test</b> email sent from PHPMailer.';
    $mail->AltBody = 'This is a test email sent from PHPMailer.';

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
}
