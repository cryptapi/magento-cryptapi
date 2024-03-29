/* @api */
define([
    'Magento_Checkout/js/view/payment/default',
    'jquery',
    'domReady!'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cryptapi_Cryptapi/checkout/payment/cryptapi'
        },
        getCryptocurrencies: function () {
            return window.checkoutConfig.payment.cryptapi.cryptocurrencies;
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.cryptapi.instructions;
        },
        getData: function () {
            return {
                "method": 'cryptapi',
                "additional_data": {
                    "cryptapi_coin": this.getSelectedCoin()
                }
            };
        },
        getSelectedCoin() {
            return document.getElementById("cryptapi_payment_cryptocurrency_id")?.value ? document.getElementById("cryptapi_payment_cryptocurrency_id")?.value : '';
        }
    });
});
