<?php

namespace Lab;

use Aws\Sqs\SqsClient;
use Exception;
use PDO;

final class QueueDispatcher
{
    public function __construct(
        private PDO $db,
        private SqsClient $sqsClient,
        private string $queueUrl
    ) {}

    public function dispatchPending(string $campaignId): int
    {
        // 1. Fetch PENDING records for this campaign
        $selectStmt = $this->db->prepare("
            SELECT request_id, campaign_id, customer_id, promo_code, subject, body
            FROM marketing_outbox
            WHERE campaign_id = :campaign_id AND status = 'PENDING'
        ");
        $selectStmt->execute([':campaign_id' => $campaignId]);
        $pendingRows = $selectStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($pendingRows)) {
            echo "No pending records found for campaign '{$campaignId}'.\n";
            return 0;
        }

        // 2. Prepare update query for queued items
        $updateStmt = $this->db->prepare("
            UPDATE marketing_outbox
            SET status = 'QUEUED', queued_at = :queued_at, sqs_message_id = :sqs_message_id
            WHERE request_id = :request_id
        ");

        $dispatchedCount = 0;

        foreach ($pendingRows as $row) {
            $payload = json_encode([
                'request_id'  => $row['request_id'],
                'campaign_id' => $row['campaign_id'],
                'customer_id' => $row['customer_id'],
                'promo_code'  => $row['promo_code'],
                'subject'     => $row['subject'],
                'body'        => $row['body'],
            ]);

            try {
                // Send message to SQS
                $result = $this->sqsClient->sendMessage([
                    'QueueUrl'    => $this->queueUrl,
                    'MessageBody' => $payload,
                ]);

                $messageId = $result->get('MessageId');
                $queuedAt = gmdate('Y-m-d H:i:s');

                // Update outbox status to QUEUED
                $updateStmt->execute([
                    ':queued_at'      => $queuedAt,
                    ':sqs_message_id' => $messageId,
                    ':request_id'     => $row['request_id'],
                ]);

                $dispatchedCount++;
            } catch (Exception $e) {
                echo "Failed to dispatch request {$row['request_id']}: " . $e->getMessage() . "\n";
            }
        }

        return $dispatchedCount;
    }
}