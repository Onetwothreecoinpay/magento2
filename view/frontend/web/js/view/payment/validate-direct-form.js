define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Onetwothreecoinpays_Onetwothreecoinpays/js/model/validate-direct-form'
    ],
    function (
        Component,
        additionalValidators,
        validateDirectForm
    ) {
        'use strict';
        additionalValidators.registerValidator(validateDirectForm);
        return Component.extend({});
    }
);
