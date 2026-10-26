<?php

namespace Lab;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

final class EmailSender
{
    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private string $fromAddress
    ) {}

    public function send(array $customer, array $job): string
    {
        $mail = new PHPMailer(true);

        // Mailtrap SMTP Settings
        $mail->isSMTP();
        $mail->Host       = $this->host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->username;
        $mail->Password   = $this->password;
        $mail->Port       = $this->port;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

        // TODO E1: Add recipient
        $mail->setFrom($this->fromAddress, 'Marketing Team');
        $mail->addAddress($customer['email'], $customer['full_name'] ?? '');

        // TODO E2: Campaign Subject and Body with Promo Code
        $mail->isHTML(false);
        $mail->Subject = $job['subject'];
        $mail->Body    = "Hello " . ($customer['full_name'] ?? 'Customer') . ",\n\n"
                       . $job['body'] . "\n\n"
                       . "Your promo code: " . $job['promo_code'];

        $mail->send();

        return $mail->getLastMessageID();
    }
}