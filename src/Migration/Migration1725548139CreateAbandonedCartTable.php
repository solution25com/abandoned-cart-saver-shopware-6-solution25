<?php

/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Admin\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Creates a table for {@see AbandonedCartEntity}.
 *
 */
final class Migration1725548139CreateAbandonedCartTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1725548139;
    }

    /**
     * @throws Exception
     */
    public function update(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            CREATE TABLE IF NOT EXISTS `sl_25_abandoned_cart` (
                `id` BINARY(16) NOT NULL,
                `cart_token` VARCHAR(50) COLLATE utf8mb4_unicode_ci,
                `price` FLOAT NOT NULL,
                `line_items` JSON,
                `customer_id` BINARY(16) NOT NULL,
                `first_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `last_name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
                `created_at` DATETIME(3) NOT NULL,
                `updated_at` DATETIME(3),
                PRIMARY KEY (`id`),
                UNIQUE KEY (`cart_token`),
                INDEX (`customer_id`)
            )
            ENGINE = InnoDB
            DEFAULT CHARSET = utf8mb4
            COLLATE = utf8mb4_unicode_ci;
        SQL);
    }

    /**
     * @throws Exception
     */
    public function updateDestructive(Connection $connection): void
    {
        $connection->executeStatement(<<<SQL
            DROP TABLE IF EXISTS `sl_25_abandoned_cart`;
        SQL);
    }
}
