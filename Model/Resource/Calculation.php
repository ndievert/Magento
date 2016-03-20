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
 
class Ia_TaxCloud_Model_Resource_Calculation extends Mage_Tax_Model_Resource_Calculation
{

    /**
     * Retrieve Calculation Process
     *
     * @param Varien_Object $request
     * @param array $rates
     * @return array
     */
    public function getCalculationProcess($request, $rates = null)
    {
        // not sure whether this class/method is used in older versions, but just to be safe...
        if(Mage::getVersion() < '1.8.1.0'){
            return parent::getCalculationProcess($request,$rates);
        }

        if (is_null($rates)) {
            $rates = $this->_getRates($request);
        }

        $shippingRateKey = false;
        $productsRateKey = false;

        foreach($rates as $key=>$rate){
            if(strtoupper(trim($rates[$key]['title']))=='TAXCLOUD'){
                if(Mage::getStoreConfig('iataxcloud_options/configuration/enabled',Mage::app()->getStore())=='1'){
                    $taxCloudRate = 0;
                    $taxCloudModel = Mage::getSingleton('iataxcloud/calculation');
                    $basedOnAddress = Mage::helper('iataxcloud/data')->getBasedOnAddress();
                    $rule_id = $rate['tax_calculation_rule_id'];
                    $rule = Mage::getModel('tax/calculation_rule')->load($rule_id);
                    switch(strtoupper(trim($rule->getCode()))){
                        case 'TAXCLOUDSHIPPING':                      
                            $rates[$key]['title'] = 'Tax - ('.$basedOnAddress->getRegionCode().')';                    
                            $taxCloudModel->setIncludeShipping(true)
                                        ->setIncludeProducts(false);
                            $taxCloudRate = $taxCloudModel->getRate($request);
                            if($taxCloudRate>0){
                                $rates[$key]['value'] = $taxCloudRate;
                                $shippingRateKey = $key;
                            } else {
                                unset($rates[$key]);
                            }                      
                            Ia_TaxCloud_Model_Log::log('Shipping Rate: '.$taxCloudRate);
                            //$taxCloudRate = 1;
                            break;
                        case 'TAXCLOUDPRODUCTS':
                            $rates[$key]['title'] = 'Tax - ('.$basedOnAddress->getRegionCode().')';                    
                            $taxCloudModel->setIncludeShipping(false)
                                        ->setIncludeProducts(true);
                            $taxCloudRate = $taxCloudModel->getRate($request);
                            if($taxCloudRate>0){
                                $rates[$key]['value'] = $taxCloudRate;
                                $productsRateKey = $key;
                            } else {
                                unset($rates[$key]);
                            }     
                            Ia_TaxCloud_Model_Log::log('Products Rate: '.$taxCloudRate);
                            //$taxCloudRate = 2;
                            break;
                        default:
                            Ia_TaxCloud_Model_Log::log('Warning! You are using the TaxCloud rate but cannot determine whether this rule `'.$rule->getCode().'` applies to products or shipping.  Recognized rule names are TaxCloudShipping and TaxCloudProducts');
                            unset($rates[$key]);
                            return parent::getCalculationProcess($request, $rates);
                            break;
                    }
                    
                } else {
                    unset($rates[$key]);
                }
            }
        }

        return parent::getCalculationProcess($request, $rates);
    }    

}