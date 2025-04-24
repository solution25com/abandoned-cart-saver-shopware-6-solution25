<?php declare(strict_types=1);

namespace Admin\Storefront\Controller;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

#[Route(defaults: ['_routeScope' => ['storefront']], path: '/account')]
class AdminOrdersController extends StorefrontController
{
    private GenericPageLoader $genericPageLoader;
    private EntityRepository $abandonedCartRepository;

    public function __construct(GenericPageLoader $genericPageLoader, EntityRepository $abandonedCartRepository)
    {
        $this->genericPageLoader = $genericPageLoader;
        $this->abandonedCartRepository = $abandonedCartRepository;
    }

    #[Route(path: '/abandoned-carts', name: 'frontend.abandoned.carts', methods: ['GET'])]
    public function examplePage(Request $request, SalesChannelContext $context): Response
    {
        $page = $this->genericPageLoader->load($request, $context);
    
        $customer = $context->getCustomer();
        if (!$customer) {
            return $this->redirectToRoute('frontend.account.login.page');
        }
    
        $customerId = $customer->getId();
        $criteria = new Criteria();
        $criteria->addAssociation('customer');
        $criteria->addAssociation('lineItems'); 
        $criteria->addFilter(new EqualsFilter('customerId', $customerId));
    
        /** @var EntitySearchResult<AbandonedCartEntity> $abandonedCarts */
        $abandonedCarts = $this->abandonedCartRepository->search($criteria, $context->getContext());
    
        $cartData = [];
        /** @var AbandonedCartEntity $cart */
        foreach ($abandonedCarts as $cart) {
            $lineItemsData = [];
            foreach ($cart->getLineItems() as $lineItem) {
                $lineItemsData[] = [
                    'name' => $lineItem['label'],
                    'price' => $lineItem['price']['totalPrice'],
                    'quantity' => $lineItem['quantity'],
                ];
            }
        
            $cartData[] = [
                'id' => $cart->getId(),
                'lineItems' => $lineItemsData, 
                'createdAt' => $cart->getCreatedAt(),
            ];
        }
    
        return $this->renderStorefront('@Admin/storefront/page/account/adminOrders/index.html.twig', [
            'page' => $page,
            'abandonedCarts' => $cartData,
        ]);
    }
}    