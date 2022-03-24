require([
    'jquery',
    'mage/url',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals-processor/default'
], function ($, url, quote,
             totalsDefaultProvider) {
    $(function () {
        $.getJSON(url.build('cryptapi/index/cartquote') + '?selected=', function (data) {
            totalsDefaultProvider.estimateTotals(quote.shippingAddress());
        });

        $('body').on('change', function () {

            var cryptoSelector = $('#cryptapi_payment_cryptocurrency_id');

            var linkUrl = url.build('cryptapi/index/cartquote');

            var feeContainer = $('.totals.fee.excl');

            setInterval(function () {
                if($('body').attr('aria-busy') === 'false') {
                    if (quote.paymentMethod._latestValue.method === 'cryptapi' && parseFloat($('.totals.fee.excl .price').html().replace(/\D/g,'')) > 0 ) {
                        feeContainer.show();
                    } else {
                        feeContainer.hide();
                    }
                }
            }, 1000);

            cryptoSelector.unbind('change');
            cryptoSelector.on('change', function () {
                $.getJSON(linkUrl + '?selected=' + cryptoSelector.val(), function (data) {
                    totalsDefaultProvider.estimateTotals(quote.shippingAddress());
                });
            });


        })
    });
});

