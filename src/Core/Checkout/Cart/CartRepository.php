<?php

declare(strict_types=1);

namespace Admin\Core\Checkout\Cart;

use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Shopware\Core\System\SystemConfig\SystemConfigService;

final class CartRepository
{
    private Connection $connection;
    private SystemConfigService $systemConfigService;

    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * Fetch abandoned carts based on the given criteria.
     *
     * @param bool $retrieveUpdated Whether to fetch updated abandoned carts.
     * @return array The abandoned carts data.
     * @throws Exception
     */

    public function findAbandonedCartsWithCriteria(bool $retrieveUpdated = false): iterable
    {
        $selectAbandonedCartTokensQuery = $this->generateAbandonedCartTokensQuery();
    
        $qb = $this->connection->createQueryBuilder();
        $qb->select('c.token', 'c.payload', 'c.created_at', 'ac.updated_at')
            ->from('cart', 'c')
            ->leftJoin('c', 'sl_25_abandoned_cart', 'ac', 'c.token = ac.cart_token')
            ->where($qb->expr()->in('c.token', $selectAbandonedCartTokensQuery))
            ->orderBy('c.created_at', 'ASC');

    
        if (!$retrieveUpdated) {
            $qb->andWhere('ac.id IS NULL');
        } else {
            $qb->andWhere(
                $qb->expr()->orX(
                    'c.created_at > ac.created_at',
                    $qb->expr()->andX(
                        'ac.updated_at IS NULL',
                        'c.created_at > ac.updated_at'
                    )
                )
            );
        }
    
        $stmt = $qb->executeQuery();
    
        while ($cart = $stmt->fetchAssociative()) {
            if (empty($cart['payload'])) {
                continue;
            }
    
            $cartPayload = @unserialize($cart['payload']);
    
            if (!$cartPayload || $cartPayload->getBehavior()->isRecalculation()) {
                continue;
            }
    
            $firstAddress = $cartPayload->getDeliveries()?->getAddresses()?->first();
            $customerId = $firstAddress?->getCustomerId();
    
            if (!$firstAddress || !$customerId) {
                continue;
            }
    
            $customerDetails = $this->connection->createQueryBuilder()
                ->select('first_name', 'last_name')
                ->from('customer')
                ->where('id = :customerId')
                ->setParameter('customerId', hex2bin($customerId))
                ->executeQuery()
                ->fetchAssociative();

                if (empty($customerDetails['first_name']) || empty($customerDetails['last_name'])) {
                    continue; 
                }
            yield [
                'token' => $cart['token'],
                'payload' => $cart['payload'], 
                'created_at' => $cart['created_at'],
                'updated_at' => $cart['updated_at'],
                'customer_id' => $customerId,
                'first_name' => $customerDetails['first_name'] ?? '',
                'last_name' => $customerDetails['last_name'] ?? '',
                'price' => $cartPayload->getPrice()->getTotalPrice(),
                'line_item_count' => count($cartPayload->getLineItems()),
            ];
        }
    }

    /**
     * Returns an array of cart tokens that are considered "abandoned" and no longer exist in the cart table,
     * but still have an association in the abandoned_cart table.
     * @throws Exception
     */
    public function findOrphanedAbandonedCartTokens(): array
    {
        $selectAbandonedCartTokensQuery = $this->generateAbandonedCartTokensQuery();

        $statement = $this->connection->prepare(<<<SQL
            SELECT sl_25_abandoned_cart.cart_token AS token
            FROM sl_25_abandoned_cart

            LEFT JOIN cart ON sl_25_abandoned_cart.cart_token = cart.token
                AND cart.token IN ($selectAbandonedCartTokensQuery)

            WHERE cart.token IS NULL;
        SQL);

        return array_column(
            $statement->executeQuery()->fetchAllAssociative(),
            'token'
        );
    }

    /**
     * Generates an SQL query to retrieve tokens of carts that are considered abandoned.
     *
     * @return string The SQL query string to retrieve abandoned cart tokens.
     */
    private function generateAbandonedCartTokensQuery(): string
    {
        $considerAbandonedAfter = (new DateTime())->modify(sprintf(
            '-%d seconds',
            $this->systemConfigService->get('MailCampaignsAbandonedCart.config.markAbandonedAfter')
        ));

        return <<<SQL
            SELECT cart.token
            FROM cart
            WHERE cart.created_at < '{$considerAbandonedAfter->format('Y-m-d H:i:s.v')}'
        SQL;
    }
}