<?php

declare(strict_types=1);

namespace Admin\Core\Checkout\AbandonedCart;

use Admin\Exception\InvalidCartDataException;
use Admin\Exception\MissingCartDataException;
use Shopware\Core\Checkout\Cart\Cart;


class AbandonedCartFactory
{
    /**
     * @var string[]
     */
    private static array $requiredValues = [
        'token',
        'price',
        'payload',
        'customer_id',
        'first_name', 
        'last_name',  
    ];

    /**
     * @throws Admin\Exception\MissingCartDataException 
     * @throws InvalidCartDataException if the given 'cart' value is not an instance of {@see Cart}.
     */
    public static function createFromArray(array $data): AbandonedCartEntity
    {
        self::validateData($data);

        $cart = unserialize($data['payload']);

        if (!$cart instanceof Cart) {
            throw new InvalidCartDataException('cart', Cart::class, $cart);
        }

        $entity = new AbandonedCartEntity();
        $entity->setCartToken($data['token']);
        $entity->setPrice((float)$data['price']);
        $entity->setLineItems($cart->getLineItems()->jsonSerialize());
        $entity->setCustomerId($data['customer_id']);
        $entity->setFirstName($data['first_name']);
        $entity->setLastName($data['last_name']);  
        return $entity;
    }

    /**
     * @throws Admin\MissingCartDataException if a required value is missing.
     */
    private static function validateData(array $data): void
    {
        foreach (self::$requiredValues as $requiredValue) {
            if (array_key_exists($requiredValue, $data) === false) {
                throw new MissingCartDataException($requiredValue);
            }
        }
    }
}
