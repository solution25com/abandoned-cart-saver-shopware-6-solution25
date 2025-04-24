import './module/abandoned-cart';
import './module/extension/sw-customer'
// import './module/abandoned-cart/view/sw-product-detail-custom'
// import './module/abandoned-cart/page/sw-customer-detail'
import './styles/base.scss'
Shopware.Module.register('sw-admin-order-tab', {
    routeMiddleware(next, currentRoute) {
        const customRouteName = 'sw.customer.detail.custom'; 

        if (
            currentRoute.name === 'sw.customer.detail'
            && currentRoute.children.every((currentRoute) => currentRoute.name !== customRouteName)
        ) {
            currentRoute.children.push({
                name: customRouteName,
                path: '/sw/customer/detail/:id/custom', 
                component: 'sw-customer-detail-custom',
                meta: {
                    parentPath: 'sw.customer.index'
                }
            });
        }
        next(currentRoute);
    }
});