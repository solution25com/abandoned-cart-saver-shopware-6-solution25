import template from "./sw-customer-detail-custom.html.twig";

const {Component, Mixin} = Shopware;

Component.register("sw-customer-detail-custom", {
    template,

    inject: ["repositoryFactory", "orderService"],

    mixins: [Mixin.getByName("notification")],

    data() {
        return {
            isLoading: false,
            carts: [],
            repository: null,
            showLineItemsModal: false,
            selectedCartLineItems: [],
            showCreateOrderModal: false,
            selectedCartForOrder: null,
            showDeleteConfirmationModal: false,
            selectedCartForDeletion: null,
            total: 0,
            page: 1,
            limit: 10,
            lineItemColumns: [
                {property: "label", label: "Product", sortable: false},
                {property: "quantity", label: "Quantity", sortable: false},
                {property: "unitPrice", label: "Price", sortable: false},
            ],
            columns: [
                {property: "fullName", label: "Name", sortable: true},
                {property: "price", label: "Price", sortable: true},
                {
                    property: "line_item_count",
                    label: "Line Item Count",
                    sortable: true,
                },
                {property: "created_at", label: "Created At", sortable: true},
                {property: "actions", label: "Actions", sortable: false},
            ],
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('sl_25_abandoned_cart');
        this.loadCarts();
    },    

    methods: {
        async exciseTax(cart, lineItem) {

            try {
                const payload = {
                    cart: cart,
                    lineItem: lineItem,
                };
                const response = await fetch(`/api/excise`, {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${Shopware.Context.api.authToken.access}`,
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }

                const data = await response.json();
                return data;
            } catch (error) {
                console.error("Fetch error:", error);
                throw error;
            }
        },
        openCreateOrderModal(cart) {
            this.selectedCartForOrder = cart;
            this.showCreateOrderModal = true;
        },

        closeCreateOrderModal() {
            this.showCreateOrderModal = false;
            this.selectedCartForOrder = null;
        },
        openDeleteConfirmationModal(cart) {
            this.selectedCartForDeletion = cart;
            this.showDeleteConfirmationModal = true;
        },

        confirmDeleteCart() {
            if (this.selectedCartForDeletion) {
                this.deleteCart(this.selectedCartForDeletion.id);
            }
            this.showDeleteConfirmationModal = false;
            this.selectedCartForDeletion = null;
        },
        confirmCreateOrder() {
            if (this.selectedCartForOrder) {
                this.createOrder(this.selectedCartForOrder);
            }
            this.closeCreateOrderModal();
        },
        openLineItemsModal(cart) {
            this.selectedCartLineItems = cart.lineItems.map((item) => ({
                label: item.label,
                quantity: item.quantity,
                unitPrice: item.price?.unitPrice || 0,
                totalPrice: item.price?.totalPrice || 0,
            }));
            this.showLineItemsModal = true;
        },
        closeLineItemsModal() {
            this.showLineItemsModal = false;
            this.selectedCartLineItems = [];
        },

        formatCurrency(value) {
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
                minimumFractionDigits: 2,
            }).format(value);
        },

        async loadCarts() {
            this.isLoading = true;
            try {
                this.repository = this.repositoryFactory.create("sl_25_abandoned_cart");

                const result = await this.repository.search(
                    this.createCriteria(),
                    Shopware.Context.api
                );
                result.forEach((item, index) => {
                    // console.log(`Cart ${index + 1}:`, JSON.stringify(item.lineItems, null, 2));
                });
                this.carts = result.map((item) => ({
                    id: item.id,
                    fullName: `${item.firstName} ${item.lastName}`,
                    token: item.cartToken,
                    customer_id: item.customerId,
                    price: item.price,
                    line_item_count: item.lineItems.length,
                    created_at: this.formatDate(item.createdAt),
                    lineItems: item.lineItems,
                }));

                this.total = result.total || 0;
            } catch (error) {
                console.error("Error fetching abandoned carts:", error);
            } finally {
                this.isLoading = false;
            }
        },
        onPageChange(newPageData) {
            if (typeof newPageData === 'object') {
                if (newPageData.page && newPageData.page > 0) {
                    this.page = parseInt(newPageData.page, 10);
                }
                if (newPageData.limit && newPageData.limit > 0 && newPageData.limit <= 100) {
                    this.limit = parseInt(newPageData.limit, 10);
                }
            } else if (typeof newPageData === 'number' && newPageData > 0) {
                this.page = parseInt(newPageData, 10);
            } else {
                return;
            }

            this.loadCarts();
        },

        formatDate(dateString) {
            if (!dateString) return "";
            return new Intl.DateTimeFormat("en-US", {
                year: "numeric",
                month: "short",
                day: "2-digit",
                hour: "2-digit",
                minute: "2-digit",
            }).format(new Date(dateString));
        },

        createCriteria() {
            const criteria = new Shopware.Data.Criteria(this.page, this.limit)
                .addSorting(Shopware.Data.Criteria.sort("createdAt", "DESC"));
        
            const customerId = this.$route.params.id;
        
            if (customerId) {
                criteria.addFilter(Shopware.Data.Criteria.equals("customerId", customerId));
            }
        
            return criteria;
        },
        
        async createOrder(cart) {
            try {
                const taxDataEntries = await Promise.all(
                    cart.lineItems.map(async (lineItem) => {
                        const tax = await this.exciseTax(cart, lineItem);
                        return [lineItem.id, tax];
                    })
                );

                const taxDataById = Object.fromEntries(taxDataEntries);
                const currencyId = Shopware.Context.app.systemCurrencyId;

                const customerRepository = this.repositoryFactory.create("customer");
                const customer = await customerRepository.get(
                    cart.customer_id,
                    Shopware.Context.api
                );
                const salesChannelId = customer.salesChannelId;
                const billingAddressId = customer.defaultBillingAddressId;

                const addressRepository =
                    this.repositoryFactory.create("customer_address");
                const billingAddress = await addressRepository.get(
                    billingAddressId,
                    Shopware.Context.api
                );

                const stateRepository = this.repositoryFactory.create(
                    "state_machine_state"
                );
                const orderStateCriteria = new Shopware.Data.Criteria().addFilter(
                    Shopware.Data.Criteria.equals("technicalName", "open")
                );
                const defaultOrderState = await stateRepository.search(
                    orderStateCriteria,
                    Shopware.Context.api
                );
                const orderStateId = defaultOrderState[0]?.id;

                const deliveryStateCriteria = new Shopware.Data.Criteria().addFilter(
                    Shopware.Data.Criteria.equals("technicalName", "open")
                );
                const defaultDeliveryState = await stateRepository.search(
                    deliveryStateCriteria,
                    Shopware.Context.api
                );
                const deliveryStateId = defaultDeliveryState[0]?.id;

                const paymentStateCriteria = new Shopware.Data.Criteria().addFilter(
                    Shopware.Data.Criteria.equals("technicalName", "open")
                );
                const defaultPaymentState = await stateRepository.search(
                    paymentStateCriteria,
                    Shopware.Context.api
                );
                const paymentStateId = defaultPaymentState[0]?.id;

                const shippingMethodRepository =
                    this.repositoryFactory.create("shipping_method");
                const shippingMethodCriteria = new Shopware.Data.Criteria().addFilter(
                    Shopware.Data.Criteria.equals("active", true)
                );
                const shippingMethods = await shippingMethodRepository.search(
                    shippingMethodCriteria,
                    Shopware.Context.api
                );
                const shippingMethodId = shippingMethods[0]?.id;

                const now = new Date();
                const shippingDateEarliest = now.toISOString();
                const shippingDateLatest = new Date(
                    now.setDate(now.getDate() + 7)
                ).toISOString();

                if (
                    !currencyId ||
                    !salesChannelId ||
                    !billingAddressId ||
                    !orderStateId ||
                    !paymentStateId
                ) {
                    throw new Error(
                        "Missing required data: currencyId, salesChannelId, billingAddressId, orderStateId, or paymentStateId."
                    );
                }

                const numberRangeService = Shopware.Service('numberRangeService');
                const orderNumber = await numberRangeService.reserve('order', salesChannelId);
                const orderNumberValue = orderNumber.number.toString();

                const shippingCost = 20.0;

                const totalTaxAmount = cart.lineItems.reduce((acc, lineItem) => {
                    const taxRate = lineItem.price.taxRules?.[0]?.taxRate || 0;  // Use tax rate from lineItem's taxRules
                    const unitPrice = lineItem.price.unitPrice;
                    const quantity = lineItem.quantity;

                    if (taxRate === 0) return acc;

                    const tax = (unitPrice * quantity) * (taxRate / 100);
                    return acc + tax;
                }, 0);

                const totalPriceWithTax = cart.price + totalTaxAmount;
                const existingTaxesFromCart = cart.lineItems.flatMap(lineItem => lineItem.price.calculatedTaxes || []);

                console.log('Wed april');

                const payload = {
                    customerId: cart.customer_id,
                    orderNumber: orderNumberValue,
                    billingAddressId: billingAddressId,
                    salesChannelId: salesChannelId,
                    currencyId: currencyId,
                    currencyFactor: 1,
                    orderDateTime: new Date().toISOString(),
                    stateId: orderStateId,
                    cartToken: cart.token,
                    orderCustomer: {
                        customerId: cart.customer_id,
                        email: customer.email,
                        salutationId: customer.salutationId,
                        firstName: customer.firstName,
                        lastName: customer.lastName,
                        company: customer.company || null,
                        title: customer.title || null,
                        vatIds: customer.vatIds || null,
                        customerNumber: customer.customerNumber,
                    },
                    deliveries: [
                        {
                            stateId: deliveryStateId,
                            shippingMethodId: shippingMethodId,
                            shippingDateEarliest: shippingDateEarliest,
                            shippingDateLatest: shippingDateLatest,
                            shippingCosts: {
                                totalPrice: shippingCost,
                                calculatedTaxes: [],
                                taxRules: [],
                                unitPrice: shippingCost,
                                quantity: 1,
                            },
                            shippingOrderAddress: {
                                id: billingAddressId,
                                versionId: null,
                                countryId: billingAddress.countryId,
                                orderId: null,
                                salutationId: billingAddress.salutationId,
                                firstName: billingAddress.firstName,
                                lastName: billingAddress.lastName,
                                street: billingAddress.street,
                                city: billingAddress.city,
                                customFields: {},
                            },
                        },
                    ],
                    transactions: [
                        {
                            paymentMethodId: customer.defaultPaymentMethodId,
                            amount: {
                                totalPrice: cart.price,
                                calculatedTaxes: [],
                                taxRules: [],
                                unitPrice: cart.price,
                                quantity: 1,
                            },
                            stateId: paymentStateId,
                        },
                    ],
                    shippingCosts: {
                        totalPrice: shippingCost,
                        calculatedTaxes: [],
                        taxRules: [],
                        unitPrice: shippingCost,
                        quantity: 1,
                    },
                    lineItems: cart.lineItems.map((lineItem, index) => {
                        const unitPrice = lineItem.price.unitPrice;
                        const quantity = lineItem.quantity;

                        const calculatedTaxes = lineItem.price.calculatedTaxes || [];
                        const taxRules = lineItem.price.taxRules || [];

                        const totalTax = calculatedTaxes.reduce((acc, tax) => acc + tax.tax, 0); // Sum all the taxes

                        return {
                            identifier: `${lineItem.productId}-${index}`,
                            productId: lineItem.productId,
                            quantity: lineItem.quantity,
                            label: lineItem.label,
                            // type: 'product',
                            // referenceId: lineItem.referencedId,
                            // payload: payload.productNumber,
                            price: {
                                unitPrice: unitPrice,
                                totalPrice: unitPrice * quantity + totalTax,
                                quantity: lineItem.quantity,
                                calculatedTaxes: taxDataById[lineItem.id].newTaxes,
                                taxRules: taxDataById[lineItem.id].taxRules,
                            },
                        };
                    }),
                    price: {
                        totalPrice: totalPriceWithTax,
                        netPrice: cart.price,
                        positionPrice: cart.price,
                        rawTotal: cart.price,
                        taxStatus: "net",
                        calculatedTaxes: cart.lineItems.flatMap(lineItem => taxDataById[lineItem.id]?.newTaxes || []),
                        taxRules: cart.lineItems.flatMap(lineItem => taxDataById[lineItem.id]?.taxRules || []),
                    },
                    itemRounding: {
                        decimals: 2,
                        interval: 0.01,
                        roundForNet: true,
                    },
                    totalRounding: {
                        decimals: 2,
                        interval: 0.01,
                        roundForNet: true,
                    },
                };

                await Shopware.Service("storeService").httpClient.post(
                    "/order",
                    payload,
                    {
                        headers: {
                            Authorization: `Bearer ${Shopware.Context.api.authToken.access}`,
                        },
                    }
                );

                if (cart.lineItems && cart.lineItems.length > 0) {
                    const httpClient =
                        Shopware.Application.getContainer("init").httpClient;

                    for (const lineItem of cart.lineItems) {
                        if (!lineItem.id) {
                            continue;
                        }

                        try {
                            await httpClient.delete(`/order-line-item/${lineItem.id}`, {
                                headers: {
                                    Authorization: `Bearer ${Shopware.Context.api.authToken.access}`,
                                },
                            });
                        } catch (error) {
                            console.error(
                                `Failed to delete line item with ID: ${lineItem.id}`,
                                error
                            );
                        }
                    }
                }

                if (!cart.id) {
                    throw new Error("Cart ID is missing. Cannot delete the cart.");
                }
                await this.repository.delete(cart.id, Shopware.Context.api);

                this.createNotificationSuccess({
                    title: "Order Created",
                    message: `Order successfully created for ${cart.fullName}.`,
                });
                this.loadCarts();
            } catch (error) {
                this.createNotificationError({
                    title: "Order Creation Failed",
                    message: `An error occurred while creating the order: ${error.message}`,
                });
            }
        },

        async deleteCart(cartId) {
            this.isLoading = true;
            try {
                if (!cartId) {
                    throw new Error("Cart ID is required to delete the cart.");
                }

                await this.repository.delete(cartId, Shopware.Context.api);

                this.carts = this.carts.filter((cart) => cart.id !== cartId);

                this.createNotificationSuccess({
                    title: "Cart Deleted",
                    message: "The abandoned cart was successfully deleted.",
                });
            } catch (error) {
                this.createNotificationError({
                    title: "Deletion Failed",
                    message: `An error occurred while deleting the cart: ${error.message}`,
                });
                console.error("Error deleting cart:", error);
            } finally {
                this.isLoading = false;
            }
        },
    },
});
