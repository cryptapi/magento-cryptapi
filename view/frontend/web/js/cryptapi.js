/* @api */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cryptapi_Cryptapi/cryptapi'
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
            var selected = document.getElementById("cryptapi_payment_cryptocurrency_id").value;

            return selected;
        }
    });
});
