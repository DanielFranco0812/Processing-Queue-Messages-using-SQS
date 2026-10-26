<?php

namespace Lab;

use Aws\Sqs\SqsClient;
use Exception;
use PDO;

final class QueueWorker
{
    public function __construct(
        private PDO $db,
        private SqsClient $sqsClient,
        private string $queueUrl,
        private EmailSender $emailSender
    ) {}

    public function processMessages(int $maxMessages = 10): int
    {
        $result = $this->sqsClient->receiveMessage([
            'QueueUrl'            => $this->queueUrl,
            'MaxNumberOfMessages' => min($maxMessages, 10),
            'WaitTimeSeconds'     => 5,
        ]);

        $messages = $result->get('Messages');
        if (empty($messages)) {
            return 0;
        }

        $processedCount = 0;

        foreach ($messages as $msg) {
            $receiptHandle = $msg['ReceiptHandle'];
            $sqsMessageId  = $msg['MessageId'] ?? null;
            $body          = json_decode($msg['Body'], true);

            if (!$body || !isset($body['request_id'])) {
                continue;
            }

            $requestId  = $body['request_id'];
            $campaignId = $body['campaign_id'];
            $customerId = $body['customer_id'];
            $promoCode  = $body['promo_code'];
            $subject    = $body['subject'];
            $timestamp  = gmdate('Y-m-d H:i:s');

            // 1. Fetch customer details
            $stmt = $this->db->prepare("SELECT full_name, email, marketing_opt_in FROM customers WHERE customer_id = :id");
            $stmt->execute([':id' => $customerId]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer || (int)$customer['marketing_opt_in'] !== 1) {
                // Customer opted out or missing -> Mark SKIPPED
                $insertStmt = $this->db->prepare("
                    INSERT IGNORE INTO sent_marketing_emails
                    (campaign_id, customer_id, request_id, promo_code, recipient_email, subject, status, provider, message_id, reason, processed_at)
                    VALUES
                    (:campaign_id, :customer_id, :request_id, :promo_code, :recipient_email, :subject, 'SKIPPED', 'Mailtrap', :message_id, 'Customer opted out', :processed_at)
                ");
                $insertStmt->execute([
                    ':campaign_id'     => $campaignId,
                    ':customer_id'     => $customerId,
                    ':request_id'      => $requestId,
                    ':promo_code'      => $promoCode,
                    ':recipient_email' => $customer['email'] ?? 'unknown',
                    ':subject'         => $subject,
                    ':message_id'      => $sqsMessageId,
                    ':processed_at'    => $timestamp,
                ]);

                $this->sqsClient->deleteMessage(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $receiptHandle]);
                continue;
            }

            // 2. DISPATCH VIA MAILTRAP SMTP
            try {
                $mimeMessageId = $this->emailSender->send($customer, $body);

                // Sleep to respect Mailtrap's rate limit
                $delay = (int)(getenv('MAILTRAP_DELAY_SECONDS') ?: 11);
                sleep($delay);

                // 3. Mark SENT after successful Mailtrap SMTP call
                $insertStmt = $this->db->prepare("
                    INSERT IGNORE INTO sent_marketing_emails
                    (campaign_id, customer_id, request_id, promo_code, recipient_email, subject, status, provider, message_id, processed_at, sent_at)
                    VALUES
                    (:campaign_id, :customer_id, :request_id, :promo_code, :recipient_email, :subject, 'SENT', 'Mailtrap', :message_id, :processed_at, :sent_at)
                ");
                $insertStmt->execute([
                    ':campaign_id'     => $campaignId,
                    ':customer_id'     => $customerId,
                    ':request_id'      => $requestId,
                    ':promo_code'      => $promoCode,
                    ':recipient_email' => $customer['email'],
                    ':subject'         => $subject,
                    ':message_id'      => $mimeMessageId,
                    ':processed_at'    => $timestamp,
                    ':sent_at'         => $timestamp,
                ]);

                // 4. Delete from SQS queue
                $this->sqsClient->deleteMessage(['QueueUrl' => $this->queueUrl, 'ReceiptHandle' => $receiptHandle]);

                $processedCount++;
                echo "Successfully sent email via Mailtrap to {$customer['email']}\n";

            } catch (Exception $e) {
                echo "Mailtrap Send Failed: " . $e->getMessage() . "\n";
            }
        }

        return $processedCount;
    }
}