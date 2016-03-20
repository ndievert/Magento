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

class Ia_TaxCloud_Helper_Data extends Mage_Core_Helper_Abstract
{

	public function getBasedOnAddress()
	{
		// check store for get current cart Quote
		if(Mage::app()->getStore()->isAdmin())
		{
			$quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
		}else{
			$quote = Mage::getSingleton('checkout/session')->getQuote();
		}
		//groups[calculation][fields][based_on][value]
		switch(Mage::getStoreConfig('tax/calculation/based_on')){
		    case 'shipping':
		        Ia_TaxCloud_Model_Debug::log('Per config, base tax on shipping address');
		        $basedOnAddress = $quote->getShippingAddress();
		        break;
		    default:
		        Ia_TaxCloud_Model_Debug::log('Per config, base tax on billing address');
		        $basedOnAddress = $quote->getBillingAddress();
		        break;
		}
		return $basedOnAddress;           
	}

}