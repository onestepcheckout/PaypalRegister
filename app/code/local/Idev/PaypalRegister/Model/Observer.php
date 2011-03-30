<?php
class Idev_PaypalRegister_Model_Observer extends Varien_Object
{

    public $count = 0;

    public function _construct(){
        parent::_construct();
        $this->setPaypalMethods(array('paypal_express'));
        $config = new Varien_Object(Mage::getStoreConfig('paypalregister', Mage::app()->getStore()));
        $this->setConfig($config);
    }

    /**
     *
     * Manipulate the quote customer object before it is saved
     * @param object $observer
     */
    public function salesOrderBeforeSave($observer)
    {
        //paypal sets users as quests if they are not logged in although they wanted to register
        if($this->isEnabled()){

            $this->setQuote($observer->getEvent()->getQuote());
            $paymentMethod = $this->getQuote()->getPayment()->getMethod();
            $isRegistered = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($this->getQuote()->getBillingAddress()->getEmail());

            //if we are dealing with paypal methods
            if (!$isRegistered->getId() && $this->isAllowedMethod($paymentMethod)) {

                //if user does not have password set
                if(!$this->getQuote()->getPasswordHash()){
                    $password = $this->getQuote()->getCustomer()->generatePassword();
                    $this->getQuote()->setData('password_hash',$this->getQuote()->getCustomer()->encryptPassword($password));
                }

                //reset what paypal sets
                $this->getQuote()
                ->setCustomerIsGuest('0')
                ->setCheckoutMethod(Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER);

                //prepare customer in quote
                $this->getCheckout()->prepareNewCustomerQuote();

            }
            return $this;
        }
    }

    /**
     * Get OPC instance with public wrappers to private methods
     */
    public function getCheckout(){
        return Mage::getSingleton('paypalregister/checkout_type_onepage');
    }

    public function saveOrderAfter($observer){

        if($this->count == 0){

            if($this->isEnabled()){

                $this->setOrder($observer->getEvent()->getOrder());
                $paymentMethod = $this->getOrder()->getPayment()->getMethod();
                $isRegistered = Mage::getModel('customer/customer')->setWebsiteId(Mage::app()->getStore()->getWebsiteId())->loadByEmail($this->getOrder()->getBillingAddress()->getEmail());

                //if we are dealing with paypal methods and guest users
                if (!$isRegistered->getId() && $this->isAllowedMethod($paymentMethod)) {
                    $this->getCheckout()->involveNewCustomer();
                }
                return $this;

            }

            $this->count = $this->count + 1;

        }
    }

    public function isAllowedMethod($paymentMethod = false){
        $paymentMethods = $this->getPaypalMethods();
        if($paymentMethod && !empty($paymentMethods) && in_array($paymentMethod, $paymentMethods)){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if this method is enabled
     *
     * $return bool
     */
    public function isEnabled(){
        if(!$this->isLoggedIn() && $this->getConfig()->getGeneral('enable_paypalregister')){
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check customer is logged in
     *
     * @return bool
     */
    public function isLoggedIn()
    {
        return Mage::helper('customer')->isLoggedIn();
    }

}