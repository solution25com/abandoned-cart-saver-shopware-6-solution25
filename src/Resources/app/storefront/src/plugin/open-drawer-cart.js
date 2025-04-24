import OffcanvasCartPlugin from 'src/plugin/offcanvas-cart/offcanvas-cart.plugin';

export default class OpenDrawerCart {
    init() {
        console.log('OpenDrawerCart plugin initialized!');
        this.registerEvents();
    }

    _registerEvents() {
        const buttons = document.querySelectorAll('.open-offcanvas-cart');

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                console.log('View Items button clicked!');
                this.openDrawer();
            });
        });
    }

    _openDrawer() {
        console.log('Attempting to open the offcanvas drawer...');
        const offcanvasCartPlugin = window.PluginManager.getPluginInstanceFromElement(
            document.querySelector('[data-offcanvas-cart]'),
            'OffcanvasCart'
        );

        if (offcanvasCartPlugin) {
            offcanvasCartPlugin.openOffcanvas();
            console.log('Offcanvas drawer opened!');
        } else {
            console.error('OffcanvasCartPlugin not found!');
        }
    }
}
