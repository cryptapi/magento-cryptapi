define(
    [
        'mage/translate',
        'Magento_Ui/js/model/messageList'
    ],
    function ($t, messageList) {
        'use strict';
        return {
            validate: function () {
                var isValid = false;

                if(!document.getElementById('cryptapi')) {
                    isValid = true;
                    return isValid;
                }

                if (document.getElementById("cryptapi_payment_cryptocurrency_id").value) {
                    isValid = true;
                }

                if(!document.getElementById("cryptapi").checked) {
                    isValid = true;
                }

                if (!isValid) {
                    messageList.addErrorMessage({message: $t('Please select a cryptocurrency.')});
                }

                return isValid;
            }
        }
    }
);
