<?php

/**
 * Quote submit service model
 */
class Idev_PaypalRegister_Model_Sales_Service_Quote extends Mage_Sales_Model_Service_Quote
{
    /**
     * Submit the quote. Quote submit process will create the order based on quote data
     *
     * @return Mage_Sales_Model_Order
     */
    public function submitOrder()
    {
        //we only need to hook in to quote before all this is executed
        Mage::dispatchEvent('paypalregister_save_order_before', array('quote'=>$this->_quote));
        //rest is handled by default
        $return = parent::submitOrder();
        return $return;
    }
}
