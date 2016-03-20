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
 
class Ia_TaxCloud_Model_Calculation extends Mage_Tax_Model_Calculation
{
    /**
     * boolean Whether we should include products in request
     */
    protected $_includeProducts = true;

    /**
     * boolean Whether we should include shipping in request
     */
    protected $_includeShipping = true;

    /**
     * Include products in our request (1.8.1.0+ only)
     *
     * @param   boolean $include
     * @return  Ia_TaxCloud_Model_Calculation
     */
    public function setIncludeProducts($include)
    {
        if($include)
            Ia_TaxCloud_Model_Debug::log('Including products in calculation');
        else
            Ia_TaxCloud_Model_Debug::log('NOT including products in calculation');

        $this->_includeProducts = $include;
        return $this;
    }

    /**
     * Include shipping in our request (1.8.1.0+ only)
     *
     * @param   boolean $include
     * @return  Ia_TaxCloud_Model_Calculation
     */
    public function setIncludeShipping($include)
    {
        if($include)
            Ia_TaxCloud_Model_Debug::log('Including shipping in calculation');
        else
            Ia_TaxCloud_Model_Debug::log('NOT including shipping in calculation');

        $this->_includeShipping = $include;
        return $this;

    }

    /**
     * Get calculation tax rate by specific request
     *
     * @param   Varien_Object $request
     * @return  float
     */
    public function getRate($request)
    {
		if (!$request->getCountryId() || !$request->getCustomerClassId() || !$request->getProductClassId()) {
		
            return 0;
        }
        if(Mage::getStoreConfig('iataxcloud_options/configuration/enabled',Mage::app()->getStore())=='1'){
			
            return $this->_getTaxCloudRate($request);
        } else {
            return parent::getRate($request);
        }    
    }
    
    protected function _getTaxCloudRate($request)
    {	
        if($request->getRegionId()==0){
			
            Ia_TaxCloud_Model_Debug::log('Tax rate is 0 due to no region id');
            return 0;
        } else {
		
			// check store for get current cart Quote
			if(Mage::app()->getStore()->isAdmin()){
				$quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();
			}else{
				$quote = Mage::getSingleton('checkout/session')->getQuote();
			}
            $basedOnAddress = Mage::helper('iataxcloud/data')->getBasedOnAddress();
            
            $productsTotal = 0;
            $shippingTotal = 0;
            
            // Create product Array
            $products = array();
			
            foreach($quote->getAllItems() as $item){
                $productModel = Mage::getModel('catalog/product')->load($item->getProduct()->getId());
                $products[] = array(
                    'itemid' => $item->getId(),
                    'productid' => $item->getProductId(),
                    'price' => $item->getPrice(),
                    'qty' => $item->getQty(),
                    'tic' => $productModel->getData('iataxcloud_tic'),
                );
                $productsTotal += ($item->getPrice() * $item->getQty());
				
            } 

            $shipping = 0;
            $shipping = $quote->getShippingAddress()->getShippingAmount();
            $shippingTotal = $shipping;

            $cache_key = 'RATES_';
            if($quote->getCustomer()->getId()){
                $cache_key .= $quote->getCustomer()->getId().'_'.$quote->getId().'_';
            } else {
                $cache_key .= 'GUEST_0_';
            }
            $cache_key .= $shippingTotal.'_'.$productsTotal.'_'.$quote->getId().'_';
            $cache_key .= $basedOnAddress->getStreet1().'_'.$basedOnAddress->getPostcode();

            Ia_TaxCloud_Model_Debug::log('Cache key (before md5...): '.$cache_key);
            $cache_key = md5($cache_key);
            
            $cache = Mage::app()->getCache();

            $rates = unserialize($cache->load($cache_key));

            Ia_TaxCloud_Model_Debug::log('RATES FROM CACHE');
            Ia_TaxCloud_Model_Debug::log($rates);
			
            if(!$rates){

                Ia_TaxCloud_Model_Debug::log('No rates from cache');
                // $shipping_rate = floatval($rate);
                // Ia_TaxCloud_Model_Debug::log('Retrieved rate ['.$rate.'] from cache');
                // return $rate;
            //} else {
            
                // Create the 'origin' address - your company's business address
                $origin = Mage::getModel('iataxcloud/api_address');
                $origin->setAddress1(Mage::getStoreConfig('iataxcloud_options/configuration/originAddress1',Mage::app()->getStore()));
                $origin->setAddress2(Mage::getStoreConfig('iataxcloud_options/configuration/originAddress2',Mage::app()->getStore()));
                $origin->setCity(Mage::getStoreConfig('iataxcloud_options/configuration/originCity',Mage::app()->getStore()));
                $region = Mage::getModel('directory/region')->load(Mage::getStoreConfig('iataxcloud_options/configuration/originState',Mage::app()->getStore()));
                $origin->setState($region->getCode());
                $origin->setZip5(Mage::getStoreConfig('iataxcloud_options/configuration/originZip',Mage::app()->getStore()));
                $origin->setZip4('');    

                Ia_TaxCloud_Model_Debug::log($origin);

				if($basedOnAddress->getCity() == "")
				{
                    if(!($city = $cache->load('city_by_zip_'.$basedOnAddress->getPostcode()))){

    					// create curl resource
    					$ch = curl_init();

    					// set url
    					curl_setopt($ch, CURLOPT_URL, "https://www.zipwise.com/webservices/zipinfo.php?key=evn8litublfdgavs&format=json&zip=".trim($basedOnAddress->getPostcode()));

    					//return the transfer as a string
    					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    					// $output contains the output string
    					$output = curl_exec($ch);
    					
    					// close curl resource to free up system resources
    					curl_close($ch);    

    					$results = json_decode($output,true);
                        if(isset($results['results']['cities'][0]['city']))
                            $city = $results['results']['cities'][0]['city'];
                        else
                            $city = 'Unknown';

                        if($city)
                            $cache->save($city, 'city_by_zip_'.$basedOnAddress->getPostcode());
                    }
					
				}else{
					$city = $basedOnAddress->getCity();
				}

                $destination = Mage::getModel('iataxcloud/api_address');
                $destination->setAddress1($basedOnAddress->getStreet1());
                $destination->setAddress2($basedOnAddress->getStreet2());
                $destination->setCity($city);
                $destination->setState($basedOnAddress->getRegionCode());
                $destination->setZip5(trim($basedOnAddress->getPostcode()));
                $destination->setZip4(''); 
                
				
                Ia_TaxCloud_Model_Debug::log($destination);
                
                $api = Mage::getModel('iataxcloud/api');
                
                if($quote->getCustomer()->getId())
                    $api->setCustomerId($quote->getCustomer()->getId());
                    
                $api->setCartId($quote->getId());

                Ia_TaxCloud_Model_Debug::log('***SETTING CARD ID: '.$quote->getId().'***');
				
                //verify address with USPS
                $verifiedAddress = $api->verifyAddress($destination);
                
                //handle error???
                if($api->isError()){
                    Ia_TaxCloud_Model_Log::log('TaxCloud Tax Verify Address Error:');
                    Ia_TaxCloud_Model_Log::log($api->getErrors());
                    //Ia_TaxCloud_Model_Log::log('Returning backup rate');
                    //return parent::getRate($request);
                    Ia_TaxCloud_Model_Debug::log('Continuing anyway...');
                    $api->clearErrors();
                    $verifiedAddress = $destination;
                } else {
                    Ia_TaxCloud_Model_Debug::log('Verified Address');
                    Ia_TaxCloud_Model_Debug::log($verifiedAddress);
                }
                
                if($shippingTotal==0 && $productsTotal==0){
                    return 0.00;
                } else {
                    $taxAmounts = $api->lookupTaxes($products, $origin, $verifiedAddress, $shipping);
                    if($api->isError()){
                        Ia_TaxCloud_Model_Log::log('TaxCloud Tax Verify Address Error:');
                        Ia_TaxCloud_Model_Log::log($api->getErrors());
                        if(Mage::getVersion()>='1.8.1.0'){
                            //in version 1.8.1.0+ we don't return "backup" rate because TaxCloud is its own rate.
                            //backups should be configured sepaately
                            Ia_TaxCloud_Model_Log::log('Returning rate of 0');
                            return 0;
                        } else {
                            Ia_TaxCloud_Model_Log::log('Returning backup rate');
                            return parent::getRate($request);
                        }
                    }
					$productRate = 0.00 ;
					$shippingRate = 0.00 ;
					if($productsTotal > 0)
					{
						$productRate = $taxAmounts['products']/$productsTotal ;
					}
					if($shippingTotal > 0)
					{
						$shippingRate = $taxAmounts['shipping']/$shippingTotal ;
					}
                     $rates = array(
                                'products'=>round(($productRate*100),2),
                                'shipping'=>round(($shippingRate*100),2),
                                'both'=>round(((($taxAmounts['products'] + $taxAmounts['shipping'])/($productsTotal + $shippingTotal))*100),2)
                                );
								
                    $cache->save(serialize($rates),$cache_key);
                    Ia_TaxCloud_Model_Debug::log('stored values ['.print_r($rates,1).'] in cache');
                }  
            }

            $rate = 0.00;
            if($this->_includeProducts && !$this->_includeShipping)
                $rate = $rates['products'];
            elseif(!$this->_includeProducts && $this->_includeShipping)
                $rate = $rates['shipping'];
            else
                $rate = $rates['both'];

			//echo $rate;
			$cacheKey = $this->_getRequestCacheKey($request);
			
			if (!$this->hasRateValue()) {
                
                $this->setCalculationProcess($rate);
                $this->setRateValue($rate);
            } else {
                $this->setCalculationProcess($this->_formCalculationProcess());
            }
            $this->_rateCache[$cacheKey] = $this->getRateValue();
            $this->_rateCalculationProcess[$cacheKey] = $this->getCalculationProcess();
			
            return $rate;

        }
		
		
    }
	
	 public function getAppliedRates($request)
    {
        if (!$request->getCountryId() || !$request->getCustomerClassId() || !$request->getProductClassId()) {
            return array();
        }
		
		 if(Mage::getStoreConfig('iataxcloud_options/configuration/enabled',Mage::app()->getStore())=='1'){
			if($request->getRegionId() !=0){
				$cacheKey = $this->_getRequestCacheKey($request);
			
				if (!isset($this->_rateCalculationProcess[$cacheKey])) {
					$this->_rateCalculationProcess[$cacheKey] = $this->_getResource()->getCalculationProcess($request);
				}
				return $this->_rateCalculationProcess[$cacheKey];
			}
		}else{
		
			$cacheKey = $this->_getRequestCacheKey($request);
			if (!isset($this->_rateCalculationProcess[$cacheKey])) {
				$this->_rateCalculationProcess[$cacheKey] = $this->_getResource()->getCalculationProcess($request);
			}
			return $this->_rateCalculationProcess[$cacheKey];
		
		}
    }
	
	
	
	
    
}
