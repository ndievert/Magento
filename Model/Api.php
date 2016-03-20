<?php
/**
 * Information ArchiTECH, LLC
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@informationarchitech.com so we can send you a copy immediately.
 *
 *
 * @copyright  Copyright (c) 2013 Information ArchiTECH, LLC (http://www.informationarchitech.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author     Information ArchiTECH <contact@informationarchitech.com>
 */
 
class Ia_TaxCloud_Model_Api {

    protected $_client = null;
    
    protected $_customer_id = null;
    
    protected $_cart_id = null;
    
    protected $_order_id = null;
    
    protected $_is_error = false;
    
    protected $_errors = null;
    
    public function isError()
    {
        return $this->_is_error;
    }
    
    public function unsetError()
    {
        $this->_is_error = false;
        $this->_errors = null;
    }
    
    public function getErrors()
    {
        return $this->_errors;
    }    
    
    public function setErrors($errors)
    {
        $this->_is_error = true;
        $this->_errors = $errors;
    }
    
    public function clearErrors()
    {
        $this->_is_error = false;
        $this->_errors = null;
    }
    
    public function setCustomerId($customerId)
    {
        $this->_customer_id = $customerId;
    }   
    
    public function getCustomerId()
    {
        if($this->_customer_id==null)
            return Mage::getStoreConfig('iataxcloud_options/configuration/guestCustomerId',Mage::app()->getStore());
        else
            return $this->_customer_id;
    }
    
    public function setCartId($cartId)
    {
        Mage::getSingleton('core/session')->setIaTaxCloudCartId($cartId);
        $this->_cart_id = $cartId;
    }   
    
    public function getCartId()
    {
        if($this->_cart_id === null){
            if($cartId = Mage::getSingleton('core/session')->getIaTaxCloudCartId()){
                $this->_cart_id = $cartId;
            }
        }
        return $this->_cart_id;
    }
    
    public function setOrderId($orderId)
    {
        $this->_order_id = $orderId;
    }   
    
    public function getOrderId()
    {
        return $this->_order_id;
    }
    
    
    public function getClient()
    {
        if($this->_client==null){
            $this->_client = new SoapClient("https://api.taxcloud.net/1.0/TaxCloud.asmx?wsdl");
        }
        return $this->_client;    
    }
    
    public function returnOrder($order_id,$products=array(),$shippingRefund=0)
    {
        $returnDate = date("Y-m-d");
        $returnDate = $returnDate . "T00:00:00";
        $index = 0;
            
        if(!$products){
            $cartItems = null;
        } else {
            $cartItems = array();
            foreach ($products as $k => $product) {
                $cartItem = Mage::getModel('iataxcloud/api_cart_item');
                $cartItem->setItemID($product['productid']); //change this to how your cart stores the product ID
                $cartItem->setIndex($index); // Each cart item must have a unique index
                $tic = $product['tic'];
                if(!$tic) {
                    //no TIC has been assigned to this product, use default
                    $tic = $this->_getDefaultTic();
                    Ia_TaxCloud_Model_Debug::log('USING DEFAULT TIC: '.$tic);
                } else {
                    Ia_TaxCloud_Model_Debug::log('PRODUCT TIC: '.$tic);
                }
                $cartItem->setTIC($tic);
                $price = $product['price']; // change this to how your cart stores the price for the product
                $cartItem->setPrice($price); // Price of each item
                $cartItem->setQty($product['qty']); // Quantity - change this to how your cart stores the quantity
                $cartItems[$index] = $cartItem;
                $index++;
            } 
        }
        
        if($shippingRefund>0){
            if($cartItems===null)
                $cartItems = array();

            //Shipping as a cart item - shipping needs to be taxed
            $cartItem = Mage::getModel('iataxcloud/api_cart_item');
            $cartItem->setItemID('shipping');
            $cartItem->setIndex($index);
            $cartItem->setTIC($this->_getShippingTic());
            $cartItem->setPrice($shippingRefund);  // The shipping cost from your cart
            $cartItem->setQty(1);
            $cartItems[$index] = $cartItem;        
        }        
                
        $params = array(
            "apiLoginID" => $this->_getApiId(),
            "apiKey" => $this->_getApiKey(),
            "customerID" => $this->getCustomerId(),
            "orderID" => $order_id,
            "cartItems" => $cartItems,
            "returnedDate" => $returnDate
        );            

        try {
            $returnResponse = $this->getClient()->Returned($params);
            Ia_TaxCloud_Model_Debug::log($params);
            Ia_TaxCloud_Model_Debug::log($returnResponse);
            return true;
        } catch (Exception $e) { 
            $this->setErrors(array($e->getMessage()));
            return false;
        }    
    
    }
    
    
    /**
     * Look up tax using TaxCloud web services
     * @param $product
     * @param $origin
     * @param $destination
     * @param $shipping
     * @return array
     */
    function lookupTaxes($products, $origin, $destination, $shipping) {
    
        $taxesOnShipping = 0.00;
        $taxesOnProducts = 0.00; 

        $errMsg = array();
    
        $client = $this->getClient();

        if(is_null($origin)){
            $this->setErrors(array('Origin address is required.'));
            return false;
        }        

        // These address checks are sometimes needed to ensure that the user has logged in. This may not be necessary for your cart.
        if(is_null($destination)){
            $this->setErrors(array('Destination address is required.'));
            return false;
        }

        $cartItems = Array();

        $index = 0;

        foreach ($products as $k => $product) {

            $cartItem = Mage::getModel('iataxcloud/api_cart_item');

            $cartItem->setItemID($product['productid']); //change this to how your cart stores the product ID
            $cartItem->setIndex($index); // Each cart item must have a unique index

            $tic = $product['tic'];
            if(!$tic) {
                //no TIC has been assigned to this product, use default
                $tic = $this->_getDefaultTic();
                Ia_TaxCloud_Model_Debug::log('USING DEFAULT TIC: '.$tic);
            } else {
                Ia_TaxCloud_Model_Debug::log('PRODUCT TIC: '.$tic);
            }

            $cartItem->setTIC($tic);

            $price = $product['price']; // change this to how your cart stores the price for the product

            $cartItem->setPrice($price); // Price of each item
            $cartItem->setQty($product['qty']); // Quantity - change this to how your cart stores the quantity

            $cartItems[$index] = $cartItem;

            $index++;

        }

        $shipping_item_index = false;

        if($shipping > 0){
            //Shipping as a cart item - shipping needs to be taxed
            $cartItem = Mage::getModel('iataxcloud/api_cart_item');
            $cartItem->setItemID('shipping');
            $cartItem->setIndex($index);
            $cartItem->setTIC($this->_getShippingTic());
            $cartItem->setPrice($shipping);  // The shipping cost from your cart
            $cartItem->setQty(1);
            $cartItems[$index] = $cartItem;
            $shipping_item_index = $index;
        }

        // Here we are loading a stored exemption certificate. You will probably want to allow the user to select from a list
        // of stored exemption certificates and pass that certificate into this method. 
        //$exemptCert = func_taxcloud_get_exemption_certificates($customer);
        //$exemptCert = (isset($exemptCert[0])) ? $exemptCert[0] : false;
        $exemptCert = null;
        
        $params = array( "apiLoginID" => $this->_getApiId(),
                 "apiKey" => $this->_getApiKey(),
                 "customerID" => $this->getCustomerId(),
                 "cartID" => $this->getCartId(),
                 "cartItems" => $cartItems,
                 "origin" => $origin,
                 "destination" => $destination,
                 "deliveredBySeller" => false,
                 "exemptCert" => $exemptCert
        );

        //Call the TaxCloud web service
        try {

            $lookupResponse = $client->lookup( $params );
            Ia_TaxCloud_Model_Debug::log($lookupResponse);
                
        } catch (Exception $e) {

            //retry
            try {
                $lookupResponse = $client->lookup( $params );
                Ia_TaxCloud_Model_Debug::log($lookupResponse);
            } catch (Exception $e) {

                $errMsg[] = "Error encountered looking up tax amount ".$e->getMessage();
                $this->setErrors($errMsg);
                return false;
            }
        }

        Ia_TaxCloud_Model_Debug::log('TAX CLOUD RESPONSE');
        Ia_TaxCloud_Model_Debug::log($lookupResponse);
        Ia_TaxCloud_Model_Debug::log('/TAX CLOUD RESPONSE');

        $lookupResult = $lookupResponse->{'LookupResult'};

        if($lookupResult->ResponseType == 'OK' || $lookupResult->ResponseType == 'Informational') {
            $cartItemsResponse = $lookupResult->{'CartItemsResponse'};
            $cartItemResponse = $cartItemsResponse->{'CartItemResponse'};
            $taxes = Array();
            $index = 0;

            //response may be an array
            if ( is_array($cartItemResponse) ) {
                foreach ($cartItemResponse as $c) {
                    if($c->CartItemIndex===$shipping_item_index){
                        $taxesOnShipping += $c->TaxAmount;
                    } else {
                        $taxesOnProducts += $c->TaxAmount;
                    }
                }
            } else {
                if($cartItemResponse->CartItemIndex===$shipping_item_index){
                    $taxesOnShipping += $cartItemResponse->TaxAmount;
                } else {
                    $taxesOnProducts += $cartItemResponse->TaxAmount;
                }
            }

            return array('products'=>$taxesOnProducts,'shipping'=>$taxesOnShipping);

        } else {
            $errMsgs = $lookupResult->{'Messages'};
            foreach($errMsgs as $err) {
                $errMsg[] = "Error encountered looking up tax amount ".$err->{'Message'};
            }
            $this->setErrors($errMsg);
            return false;
            
            //Handle the error appropriately 
        }
    }

    /**
     * Authorized with Capture
     * This represents the combination of the Authorized and Captured process in one step. You can 
     * also make these calls separately if you use a two stepped commit. 
     */
    public function authorizeCapture() {
    
        $errMsg = array();
        $client = $this->getClient();
    
        $result = 0;
        $dup = "This purchase has already been marked as authorized";
        // Current date - example of format: '2010-09-08T00:00:00';
        $dateAuthorized = date("Y-m-d");
        $dateAuthorized = $dateAuthorized . "T00:00:00";
        $params = array( "apiLoginID" => $this->_getApiId(),
                         "apiKey" => $this->_getApiKey(),
                         "customerID" => $this->getCustomerId(),
                         "cartID" => $this->getCartId(),
                         "orderID" => $this->getOrderId(),
                         "dateAuthorized" => $dateAuthorized,
                         "dateCaptured" => $dateAuthorized);
                         
        Ia_TaxCloud_Model_Debug::log($params);

        // The authorizedResponse array contains the response verification (Error, OK, ...)
        $authorizedResponse = null;
        try {
            $authorizedResponse = $client->authorizedWithCapture( $params );
            Ia_TaxCloud_Model_Debug::log('authCapture response:');
            Ia_TaxCloud_Model_Debug::log($authorizedResponse);
            

        } catch (Exception $e) {
            //infrastructure error, try again
            try {
                $authorizedResponse = $client->authorizedWithCapture( $params );
                $authorizedResult = $authorizedResponse->{'AuthorizedWithCaptureResult'};
                if ($authorizedResult->ResponseType != 'OK') {
                    $msgs = $authorizedResult->{'Messages'};
                    $respMsg = $msgs->{'ResponseMessage'};
                    //duplicate means the the previous call was good. Therefore, consider this to be good
                    if (trim ($respMsg->Message) == $dup) {
                        $errMsg[] = "Duplicate transaction";
                        $this->setErrors($errMsg);
                        return $this;
                    }
                } else if ($authorizedResult->ResponseType == 'Error') {
                    $msgs = $authorizedResult->{'Messages'};
                    $respMsg = $msgs->{'ResponseMessage'};
                    //duplicate means the the previous call was good. Therefore, consider this to be good
                    if (trim ($respMsg->Message) == $dup) {
                        $errMsg[] = "Duplicate transaction";
                        $this->setErrors($errMsg);
                        return $this;
                    } else {
                        $errMsg[] = "Error encountered looking up tax amount ".$respMsg;
                        $this->setErrors($errMsg);
                        return $this;
                    }
                } else {
                    $errMsg[] = "An unknown error occured";
                    $this->setErrors($errMsg);
                    return $this;
                }

            } catch (Exception $e) {
                //give up
                $errMsg[] = $e->getMessage();
                $this->setErrors($errMsg);
                return $this;
                // Handle this error appropriately
            }
        }
        $authorizedResult = $authorizedResponse->{'AuthorizedWithCaptureResult'};
        if ($authorizedResult->ResponseType == 'OK') {
            return $this;
        } else {
            $msgs = $authorizedResult->{'Messages'};
            $respMsg = $msgs->{'ResponseMessage'};
            $errMsg [] = $respMsg->Message;
            $this->setErrors($errMsg);
        }
        return $this;
    }    
    
    /*
     * If strict, will not attempt to correct zip code, will return error
     */    
    public function verifyAddress($address,$strict=false)
    {
        $err = array();
        
        $client = $this->getClient();
    
       // Verify the address through the TaxCloud verify address service
        $params = array( "uspsUserID" => $this->_getUspsId(),
                     "address1" => $address->getAddress1(),
                     "address2" => $address->getAddress2(),
                     "city" => $address->getCity(),
                     "state" => $address->getState(),
                     "zip5" => $address->getZip5(),
                     "zip4" => $address->getZip4());
		
        try {

            $verifyaddressresponse = $client->verifyAddress( $params );

        } catch (Exception $e) {

            //retry in case of timeout
            try {
                $verifyaddressresponse = $client->verifyAddress( $params );
            } catch (Exception $e) {

                $err[] = "Error encountered while verifying address ".$address->getAddress1().
                " ".$address->getState()." ".$address->getCity()." "." ".$address->getZip5().
                " ".$e->getMessage();
                $this->setErrors($err);
                //irreparable, return
                return false;
            }
        }

        if($verifyaddressresponse->{'VerifyAddressResult'}->ErrNumber == 0) {
        
            if($strict && $address->getZip5() != $verifyaddressresponse->{'VerifyAddressResult'}->Zip5){
                $err[] = 'Address failed strict validation. Provided zip code `'.$address->getZip5().'` does not match USPS suggested value `'.$verifyaddressresponse->{'VerifyAddressResult'}->Zip5.'`.';
                $this->setErrors($err);
                //irreparable, return
                return false;
            }
        
            // Use the verified address values
            $address->setAddress1($verifyaddressresponse->{'VerifyAddressResult'}->Address1);
            if(isset($verifyaddressresponse->{'VerifyAddressResult'}->Address2))
                $address->setAddress2($verifyaddressresponse->{'VerifyAddressResult'}->Address2);
            $address->setCity($verifyaddressresponse->{'VerifyAddressResult'}->City);
            $address->setState($verifyaddressresponse->{'VerifyAddressResult'}->State);
            $address->setZip5($verifyaddressresponse->{'VerifyAddressResult'}->Zip5);
            $address->setZip4($verifyaddressresponse->{'VerifyAddressResult'}->Zip4);

        } else {

            $err[] = "Error encountered while verifying address ".$address->getAddress1().
                " ".$address->getState()." ".$address->getCity()." "." ".$address->getZip5().
                " ".$verifyaddressresponse->{'VerifyAddressResult'}->ErrDescription;
            $this->setErrors($err);

            return null;
        }
        return $address;        
    }
    
    protected function _getApiId()
    {
        return Mage::getStoreConfig('iataxcloud_options/configuration/apiId',Mage::app()->getStore());
    }
    
    
    protected function _getApiKey()
    {
        return Mage::getStoreConfig('iataxcloud_options/configuration/apiKey',Mage::app()->getStore());
    }

    protected function _getUspsId()
    {
        return Mage::getStoreConfig('iataxcloud_options/configuration/uspsId',Mage::app()->getStore());
    }

    protected function _getDefaultTic()
    {
        $defaultTic = Mage::getStoreConfig('iataxcloud_options/configuration/defaultTic',Mage::app()->getStore());
        if(!$defaultTic){
            $defaultTic = '000000';
        }
        return $defaultTic;
    }
    
    protected function _getShippingTic()
    {
        $shippingTic = Mage::getStoreConfig('iataxcloud_options/configuration/shippingTic',Mage::app()->getStore());
        if(!$shippingTic){
            $shippingTic = '10010';
        }
        return $shippingTic;
    }

}