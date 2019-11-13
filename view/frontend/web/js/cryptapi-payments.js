
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
                type: 'cryptapi',
                component: 'Cryptapi_Cryptapi/js/cryptapi'
            }
        );
        return Component.extend({});
    }
);