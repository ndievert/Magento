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

/*
 * US region source model
 */
 
class Ia_TaxCloud_Model_System_Config_Source_Usregion
    extends Mage_Adminhtml_Model_System_Config_Source_Allregion {

    /**
     * return @array
     */
    public function toOptionArray($isMultiselect=false)
    {
		$regionsCollection = Mage::getResourceModel('directory/region_collection')->addCountryFilter('US')->load();
		$regionOptions = array();
		foreach ($regionsCollection as $region) {
			$regionOptions[$region->getDefaultName()] = array('label'=>$region->getDefaultName(), 'value'=>$region->getId());
		}
		
		ksort($regionOptions);
        return array("value"=>"") + $regionOptions;
    }
}
