<?php declare(strict_types=1);

namespace Admin\Controller;
use Shopware\Core\Checkout\Order\SalesChannel\OrderService;
use Shopware\Core\Framework\Context;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Checkout\Cart\CartPersister;

class AbandonedCartController extends AbstractController
{
    private CartPersister $cartPersister;
    private OrderService $orderService;

    public function __construct(CartPersister $cartPersister, OrderService $orderService)
    {
        $this->cartPersister = $cartPersister;
        $this->orderService = $orderService;
    }

    /**
     * @Route("/api/abandoned-cart/{customerId}/create-order", name="api.abandoned_cart.create_order", methods={"POST"})
     */
    public function createOrderFromCart(string $customerId, Context $context): JsonResponse
    {
        // Load the cart for the given customer
        $cart = $this->cartPersister->load($customerId, $context);

        if (!$cart) {
            return new JsonResponse(['success' => false, 'message' => 'Cart not found'], 404);
        }

        // Create an order from the cart
        $this->orderService->createOrder($cart, $context);

        return new JsonResponse(['success' => true, 'message' => 'Order created successfully']);
    }
}
