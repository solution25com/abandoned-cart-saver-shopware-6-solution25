import './page/abandoned-cart-list';
// import './page/sw-customer-detail'


Shopware.Module.register('abandoned-cart', {
    type: 'plugin',
    name: 'abandoned-cart',
    title: 'Admin Orders',
    description: 'Manage abandoned carts efficiently',
    color: '#ff3d3d',
    icon: 'default-shopping-paper-bag',

    routes: {
        list: {
            component: 'abandoned-cart-list',
            path: 'list',
        },
    },

    navigation: [
        {
            id: 'abandoned-cart',
            label: 'Admin Orders',
            color: '#ff3d3d',
            path: 'abandoned.cart.list',
            icon: 'default-shopping-paper-bag',
            parent: 'sw-order', 
            position: 50, 
        },
    ],
});