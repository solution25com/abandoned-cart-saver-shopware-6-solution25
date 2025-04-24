<?php

declare(strict_types=1);

namespace Admin\Core\Checkout\AbandonedCart;

use Doctrine\DBAL\Exception;
use Admin\Core\Checkout\Cart\CartRepository;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;


final class AbandonedCartManager
{
    public function __construct(
        private readonly CartRepository $cartRepository,
        private readonly EntityRepository $abandonedCartRepository,
    ) {
    }

    /**
     * @return int The number of generated "abandoned" carts.
     * @throws Exception
     */
    public function generate(): int
    {
        $cnt = 0;
        $batchSize = 100; 
        $context = new Context(new SystemSource());
    
        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(false, $batchSize) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);
    
            if ($abandonedCart->getCustomerId() === null) {
                continue;
            }
    
            $this->abandonedCartRepository->upsert([
                [
                    'cartToken' => $abandonedCart->getCartToken(),
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'customerId' => $abandonedCart->getCustomerId(),
                    'firstName' => $abandonedCart->getFirstName(),
                    'lastName' => $abandonedCart->getLastName(),
                ],
            ], $context);
    
            $cnt++;
        }
    
        return $cnt;
    }

    /**
     * Updates abandoned carts that have been modified since they were marked as abandoned.
     *
     * @throws Exception
     */
    public function updateAbandonedCarts(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findAbandonedCartsWithCriteria(true) as $cart) {
            $abandonedCart = AbandonedCartFactory::createFromArray($cart);

            $abandonedCartId = $this->findAbandonedCartIdByToken($abandonedCart->getCartToken());

            $this->abandonedCartRepository->upsert([
                [
                    'id' => $abandonedCartId,
                    'price' => $abandonedCart->getPrice(),
                    'lineItems' => $abandonedCart->getLineItems(),
                    'firstName' => $abandonedCart->getFirstName(), 
                    'lastName' => $abandonedCart->getLastName(), 
                ],
            ], new Context(new SystemSource()));

            $cnt++;
        }

        return $cnt;
    }

    /**
     * @return int The number of deleted "abandoned" carts.
     * @throws Exception
     */
    public function cleanUp(): int
    {
        $cnt = 0;

        foreach ($this->cartRepository->findOrphanedAbandonedCartTokens() as $token) {
            $abandonedCartId = $this->findAbandonedCartIdByToken($token);

            if ($abandonedCartId !== null) {
                $this->abandonedCartRepository->delete([
                    [
                        'id' => $abandonedCartId,
                    ],
                ], new Context(new SystemSource()));

                $cnt++;
            }
        }

        return $cnt;
    }

    private function findAbandonedCartIdByToken(string $token): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('cartToken', $token));

        return $this->abandonedCartRepository
            ->searchIds($criteria, new Context(new SystemSource()))
            ->firstId();
    }
}