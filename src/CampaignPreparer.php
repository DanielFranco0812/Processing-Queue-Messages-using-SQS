<?php

namespace Lab;

use Exception;
use PDO;

final class CampaignPreparer
{
    public function __construct(private PDO $db) {}

    public function prepare(string $campaignId, string $promoCode, string $subject, string $body): void
    {
        // TODO C3: Check existing outbox rows for the campaign
        $checkStmt = $this->db->prepare("SELECT COUNT(*) FROM marketing_outbox WHERE campaign_id = :campaign_id");
        $checkStmt->execute([':campaign_id' => $campaignId]);
        $existingCount = (int)$checkStmt->fetchColumn();

        if ($existingCount === 50) {
            echo "Campaign '{$campaignId}' already has 50 prepared requests. Preserving existing selection.\n";
            return;
        }

        if ($existingCount > 0 && $existingCount !== 50) {
            throw new Exception("Inconsistent state: Campaign '{$campaignId}' has {$existingCount} existing requests.");
        }

        // TODO C2: Select exactly 50 distinct random opted-in customers (marketing_opt_in = 1)
        $selectStmt = $this->db->prepare("
            SELECT customer_id
            FROM customers
            WHERE marketing_opt_in = 1
            ORDER BY RAND()
            LIMIT 50
        ");
        $selectStmt->execute();
        $selectedCustomers = $selectStmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($selectedCustomers) < 50) {
            throw new Exception("Not enough eligible opted-in customers to select 50 recipients.");
        }

        // Prepare insertion into marketing_outbox
        $insertStmt = $this->db->prepare("
            INSERT INTO marketing_outbox (request_id, campaign_id, customer_id, promo_code, subject, body, status, created_at)
            VALUES (:request_id, :campaign_id, :customer_id, :promo_code, :subject, :body, 'PENDING', :created_at)
        ");

        $createdAt = gmdate('Y-m-d H:i:s');

        $this->db->beginTransaction();

        try {
            foreach ($selectedCustomers as $customerId) {
                // Generate 32-character hexadecimal request ID
                $requestId = bin2hex(random_bytes(16));

                // TODO C1: Execute insert with seven values
                $insertStmt->execute([
                    ':request_id'  => $requestId,
                    ':campaign_id' => $campaignId,
                    ':customer_id' => $customerId,
                    ':promo_code'  => $promoCode,
                    ':subject'     => $subject,
                    ':body'        => $body,
                    ':created_at'  => $createdAt,
                ]);
            }

            $this->db->commit();
            echo "Successfully prepared campaign '{$campaignId}' with 50 recipients.\n";
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}