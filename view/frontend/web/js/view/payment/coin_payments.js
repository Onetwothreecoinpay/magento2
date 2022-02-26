define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'coin_payments',
                component: 'Onetwothreecoinpays_Onetwothreecoinpays/js/view/payment/method-renderer/coin-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
