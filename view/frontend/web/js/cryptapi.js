/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
    'Magento_Checkout/js/view/payment/default'
], function (Component) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Cryptapi_Cryptapi/cryptapi'
        },
        getInstructions: function () {
            return window.checkoutConfig.payment.instructions[this.item.method];
        },
        hasBtc: function() {
            return window.checkoutConfig.payment.cryptapi.btc;
        },
        hasBch: function() {
            return window.checkoutConfig.payment.cryptapi.bch;
        },
        hasLtc: function() {
            return window.checkoutConfig.payment.cryptapi.ltc;
        },
        hasEth: function() {
            return window.checkoutConfig.payment.cryptapi.eth;
        },
        hasXmr: function() {
            return window.checkoutConfig.payment.cryptapi.xmr;
        },
        hasIota: function() {
            return window.checkoutConfig.payment.cryptapi.iota;
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
            var selected = '';
            if (
                document.getElementById('payment_method_btc') !== 'undefined' &&
                document.getElementById('payment_method_btc') !== null &&
                document.getElementById('payment_method_btc').checked
            ) {
                selected = document.getElementById('payment_method_btc').value;
            } else if (
                document.getElementById('payment_method_bch') !== 'undefined' &&
                document.getElementById('payment_method_bch') !== null &&
                document.getElementById('payment_method_bch').checked
            ) {
                selected = document.getElementById('payment_method_bch').value;
            } else if (
                document.getElementById('payment_method_ltc') !== 'undefined' &&
                document.getElementById('payment_method_ltc') !== null &&
                document.getElementById('payment_method_ltc').checked
            ) {
                selected = document.getElementById('payment_method_ltc').value;
            } else if (
                document.getElementById('payment_method_eth') !== 'undefined' &&
                document.getElementById('payment_method_eth') !== null &&
                document.getElementById('payment_method_eth').checked
            ) {
                selected = document.getElementById('payment_method_eth').value;
            } else if (
                document.getElementById('payment_method_xmr') !== 'undefined' &&
                document.getElementById('payment_method_xmr') !== null &&
                document.getElementById('payment_method_xmr').checked
            ) {
                selected = document.getElementById('payment_method_xmr').value;
            } else if (
                document.getElementById('payment_method_iota') !== 'undefined' &&
                document.getElementById('payment_method_iota') !== null &&
                document.getElementById('payment_method_iota').checked
            ) {
                selected = document.getElementById('payment_method_iota').value;
            }
            
            return selected;
        }
    });
});