import template from './sw-customer-detail.html.twig';

Shopware.Component.override('sw-customer-detail', {
    template,

    created() {
        console.log('sw-customer-detail override is applied');
    }
});