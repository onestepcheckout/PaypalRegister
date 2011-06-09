<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Paypal
 * @copyright   Copyright (c) 2010 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Wrapper that performs Paypal Express and Checkout communication
 * Use current Paypal Express method instance
 */
class Idev_PaypalRegister_Model_Express_Checkout extends Mage_Paypal_Model_Express_Checkout
{

    /**
     * Update quote when returned from PayPal
     * @param string $token
     */
    public function returnFromPaypal($token)
    {

        if(!Mage::getStoreConfig('paypalregister/general/enable_paypalregister', Mage::app()->getStore())){
            parent::returnFromPaypal($token);
        } else {

            $this->_getApi();
            $this->_api->setToken($token)
                ->callGetExpressCheckoutDetails();

            // import billing address
            /* we don't need their billing address
            $billingAddress = $this->_quote->getBillingAddress();
            $exportedBillingAddress = $this->_api->getExportedBillingAddress();
            foreach ($exportedBillingAddress->getExportedKeys() as $key) {
                $billingAddress->setDataUsingMethod($key, $exportedBillingAddress->getData($key));
            }*/

            // import shipping address
            $exportedShippingAddress = $this->_api->getExportedShippingAddress();
            if (!$this->_quote->getIsVirtual()) {
                $shippingAddress = $this->_quote->getShippingAddress();
                if ($shippingAddress) {
                    if ($exportedShippingAddress) {
                        foreach ($exportedShippingAddress->getExportedKeys() as $key) {
                            $shippingAddress->setDataUsingMethod($key, $exportedShippingAddress->getData($key));
                        }
                        $shippingAddress->setCollectShippingRates(true)->collectShippingRates();
                    }

                    // import shipping method
                    $code = '';
                    if ($this->_api->getShippingRateCode()) {
                        if ($code = $this->_matchShippingMethodCode($shippingAddress, $this->_api->getShippingRateCode())) {
                             // possible bug of double collecting rates :-/
                            $shippingAddress->setShippingMethod($code)->setCollectShippingRates(true);
                        }
                    }
                    $this->_quote->getPayment()->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_SHIPPING_METHOD, $code);
                }
            }
            $this->ignoreAddressValidation();

            // import payment info
            $payment = $this->_quote->getPayment();
            $payment->setMethod($this->_methodType);
            Mage::getSingleton('paypal/info')->importToPayment($this->_api, $payment);
            $payment->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_PAYER_ID, $this->_api->getPayerId())
                ->setAdditionalInformation(self::PAYMENT_INFO_TRANSPORT_TOKEN, $token)
            ;
            $this->_quote->collectTotals()->save();
        }
    }

    public function ignoreAddressValidation()
    {
        $this->_quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$this->_quote->getIsVirtual()) {
            $this->_quote->getShippingAddress()->setShouldIgnoreValidation(true);
        }
    }

}
