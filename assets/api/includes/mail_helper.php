<?php
// includes/mail_helper.php
require_once 'C:/xampp/php/PEAR/PHPMailer/PHPMailerAutoload.php'; // adjust path if needed
// If the above fails, download PHPMailer from https://github.com/PHPMailer/PHPMailer and place in includes/PHPMailer/

function sendMail($to, $subject, $body) {
    $mail = new PHPMailer(true);
    try {
        // Server settings (SMTP – you can use Gmail or your university's mail server)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';          // e.g., smtp.gmail.com
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';    // replace with your email
        $mail->Password   = 'your-app-password';       // replace with your password or app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('no-reply@limkokwing.edu.sl', 'Limkokwing USCMS');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail error: " . $mail->ErrorInfo);
        return false;
    }
}