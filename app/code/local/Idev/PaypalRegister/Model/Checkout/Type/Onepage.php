<?php
/**
 * One page checkout processing model
 */
class Idev_PaypalRegister_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    public function prepareNewCustomerQuote(){
        $this->_prepareNewCustomerQuote();
        return $this;
    }

    public function involveNewCustomer(){
        $this->_involveNewCustomer();
    }
}
