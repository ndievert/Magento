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
 
class Ia_TaxCloud_Model_Observer
    extends Mage_Core_Model_Abstract {

    /**
     * sales_order_place_after
     */    
	public function authorizeCapture($observer){
	
		$order = $observer->getEvent()->getOrder();
        Ia_TaxCloud_Model_Debug::log('AuthorizeCapture');
        $api = Mage::getModel('iataxcloud/api');
        if($order->getCustomer()->getId())
            $api->setCustomerId($order->getCustomer()->getId());
        $api->setOrderId($order->getIncrementId());
        $api->authorizeCapture();
        if($api->isError()){
            Ia_TaxCloud_Model_Log::log('TaxCloud Authorize/Capture Error:');
            Ia_TaxCloud_Model_Log::log($api->getErrors());
        }
	}
    
    /**
     * sales_order_creditmemo_refund
     */    
    public function returnItems($observer){
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $order = $creditmemo->getOrder();
        $items = $creditmemo->getAllItems();
        $api = Mage::getModel('iataxcloud/api');
        $products = array();
        $originalShipping = $order->getShippingAmount();
        $refundedShipping = $creditmemo->getShippingAmount();
        if(sizeof($order->getAllItems())==sizeof($items) && $refundedShipping==$originalShipping){
            Ia_TaxCloud_Model_Debug::log('ALL ITEMS BEING RETURNED');
            $refundedShipping = 0; //we set this to 0 because we are going to be refunding the entire order
        } else {
            Ia_TaxCloud_Model_Debug::log('SOME ITEMS BEING RETURNED:');
            $cartItems = array();
            foreach($items as $creditItem){ 
                $item = $creditItem->getOrderItem();
                Ia_TaxCloud_Model_Debug::log($item->getName());
                Ia_TaxCloud_Model_Debug::log(get_class($item));
                $productModel = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                $products[] = array(
                    'itemid' => $item->getId(),
                    'productid' => $item->getProductId(),
                    'price' => $creditItem->getPrice(),
                    'qty' => $creditItem->getQty(),
                    'tic' => $productModel->getData('iataxcloud_tic'),
                );            
            }
            Ia_TaxCloud_Model_Debug::log($products);
        }
        $api->returnOrder($order->getIncrementId(),$products,$refundedShipping);
        if($api->isError()){
            Ia_TaxCloud_Model_Log::log('TaxCloud Error Returning:');
            Ia_TaxCloud_Model_Log::log($api->getErrors());
        } else {
            Ia_TaxCloud_Model_Debug::log('SUCCESSFUL RETURN');
        }
    }

}