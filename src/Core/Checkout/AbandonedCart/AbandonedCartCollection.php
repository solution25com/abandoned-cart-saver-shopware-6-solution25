<?php

declare(strict_types=1);

namespace Admin\Core\Checkout\AbandonedCart;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 *
 * @method void                     add(AbandonedCartEntity $entity)
 * @method void                     set(string $key, AbandonedCartEntity $entity)
 * @method AbandonedCartEntity[]    getIterator()
 * @method AbandonedCartEntity[]    getElements()
 * @method AbandonedCartEntity|null get(string $key)
 * @method AbandonedCartEntity|null first()
 * @method AbandonedCartEntity|null last()
 */
final class AbandonedCartCollection extends EntityCollection
{
    public function getExpectedClass(): string
    {
        return AbandonedCartEntity::class;
    }
}