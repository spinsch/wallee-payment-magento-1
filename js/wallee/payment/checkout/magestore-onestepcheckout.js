/**
 * wallee Magento 1
 *
 * This Magento extension enables to process payments with wallee (https://www.wallee.com/).
 *
 * @package Wallee_Payment
 * @author customweb GmbH (http://www.customweb.com/)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)
 */

MageWallee.Checkout.Type.MageStoreOneStepCheckout = Class.create(
    MageWallee.Checkout.Type, {
    initialize : function () {
        $$('#checkout-payment-method-load dt input').invoke(
            'observe', 'click', function (e) {
            var element = e.element();
            if (element.checked) {
                this.switchMethod(element.value);
            }
            }.bind(this)
        );
        this.switchMethod(this.getPaymentMethodCode());

        var self = this;
        Validation.prototype.validate = Validation.prototype.validate.wrap(
            function (callOriginal) {
            return self.validate.bind(self)(callOriginal, this.form);
            }
        );
    },

    disableSubmitButton : function () {
        $('onestepcheckout-button-place-order').removeClassName('onestepcheckout-btn-checkout');
        $('onestepcheckout-button-place-order').addClassName('place-order-loader');
        $('onestepcheckout-button-place-order').disabled = true;
    },

    enableSubmitButton : function () {
        $('onestepcheckout-button-place-order').addClassName('onestepcheckout-btn-checkout');
        $('onestepcheckout-button-place-order').removeClassName('place-order-loader');
        $('onestepcheckout-button-place-order').disabled = false;
    },

    getPaymentMethodCode : function () {
        var checked = $$('#checkout-payment-method-load dt input:checked');
        if (checked && checked[0]) {
            return checked[0].value;
        }
    },

    /**
     * Initializes the payment iframe when the customer switches the payment method.
     */
    switchMethod : function (method) {
        if (method) {
            this.createHandler(
                method, function () {
                paymentLoad();
                }, function (validationResult) {
                paymentShow();
                if (validationResult.success) {
                    this.createOrder();
                } else {
                    this.enableSubmitButton();
                }
                }.bind(this), function () {
                paymentShow();
                }
            );
        }
    },

    /**
     * Validates the payment information when the customer submits the order.
     */
    validate : function (callOriginal, form) {
        if (form.identify() == 'one-step-checkout-form' && this.isSupportedPaymentMethod(this.getPaymentMethodCode())) {
            this.disableSubmitButton();
            this.getPaymentMethod(this.getPaymentMethodCode()).handler.validate();
            return false;
        }

        return callOriginal();
    },

    createOrder : function () {
        $('onestepcheckout-place-order-loading').show();

        var form = $('one-step-checkout-form');
        new Ajax.Request(
            form.readAttribute('action'), {
            method : 'post',
            parameters : Form.serialize(form),
            onSuccess : this.onOrderCreated.bind(this),
            onFailure : function () {
                $('onestepcheckout-place-order-loading').hide();
                this.enableSubmitButton();
            }.bind(this)
            }
        );
    },

    onOrderCreated : function (response) {
        if (response && response.status == 200 && response.responseText == 'OK') {
            this.getPaymentMethod(this.getPaymentMethodCode()).handler.submit();
        } else if (response.responseText) {
            document.open('text/html');
            document.write(response.responseText);
            document.close();
        } else {
            location.reload();
        }
    }
    }
);
MageWallee.Checkout.type = MageWallee.Checkout.Type.MageStoreOneStepCheckout;