define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Cryptapi_Cryptapi/js/model/validate-cryptocurrency'
    ],
    function (Component, additionalValidators, validateCryptocurrency) {
        'use strict';
        additionalValidators.registerValidator(validateCryptocurrency);
        return Component.extend({});
    }
);
